<?php

declare(strict_types=1);

namespace Iamgerwin\GsmModem;

use Exception;

class SerialPort
{
    private mixed $handle = null;
    private string $port;
    private array $options;
    private string $buffer = '';

    public function __construct(string $port, array $options = [])
    {
        $this->port = $port;
        $this->options = array_merge([
            'baudRate' => 9600,
            'dataBits' => 8,
            'parity' => 'none',
            'stopBits' => 1,
            'flowControl' => 'none',
        ], $options);
    }

    public function open(): bool
    {
        if ($this->isOpen()) {
            return true;
        }

        $this->configurePort();

        $this->handle = @fopen($this->port, 'r+b');
        if ($this->handle === false) {
            throw new Exception("Failed to open serial port: {$this->port}");
        }

        stream_set_blocking($this->handle, false);
        stream_set_timeout($this->handle, 0, 200000);

        return true;
    }

    public function close(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
            $this->buffer = '';
        }
    }

    public function isOpen(): bool
    {
        return $this->handle !== null && is_resource($this->handle);
    }

    public function write(string $data): int|false
    {
        if (!$this->isOpen()) {
            throw new Exception('Serial port is not open');
        }

        $written = fwrite($this->handle, $data);
        fflush($this->handle);

        return $written;
    }

    public function read(int $length = 1024, int $timeout = 1000): string
    {
        if (!$this->isOpen()) {
            throw new Exception('Serial port is not open');
        }

        $startTime = microtime(true) * 1000;
        $data = '';

        while ((microtime(true) * 1000 - $startTime) < $timeout) {
            $chunk = fread($this->handle, $length);
            if ($chunk !== false && $chunk !== '') {
                $data .= $chunk;
                if (strlen($data) >= $length) {
                    break;
                }
            }
            usleep(10000);
        }

        return $data;
    }

    public function readLine(int $timeout = 1000): ?string
    {
        if (!$this->isOpen()) {
            throw new Exception('Serial port is not open');
        }

        $startTime = microtime(true) * 1000;

        while ((microtime(true) * 1000 - $startTime) < $timeout) {
            $chunk = fread($this->handle, 1024);
            if ($chunk !== false && $chunk !== '') {
                $this->buffer .= $chunk;
            }

            if (str_contains($this->buffer, "\n")) {
                $lines = explode("\n", $this->buffer);
                $line = array_shift($lines);
                $this->buffer = implode("\n", $lines);
                return trim($line, "\r\n");
            }

            usleep(10000);
        }

        if ($this->buffer !== '') {
            $line = $this->buffer;
            $this->buffer = '';
            return trim($line, "\r\n");
        }

        return null;
    }

    public function readUntil(string $delimiter, int $timeout = 10000): string
    {
        if (!$this->isOpen()) {
            throw new Exception('Serial port is not open');
        }

        $startTime = microtime(true) * 1000;
        $data = '';

        while ((microtime(true) * 1000 - $startTime) < $timeout) {
            $chunk = fread($this->handle, 1024);
            if ($chunk !== false && $chunk !== '') {
                $data .= $chunk;
                if (str_contains($data, $delimiter)) {
                    return $data;
                }
            }
            usleep(10000);
        }

        return $data;
    }

    public function flush(): void
    {
        if (!$this->isOpen()) {
            return;
        }

        while (fread($this->handle, 1024) !== false) {
            // Keep reading until buffer is empty
        }
        $this->buffer = '';
    }

    private function configurePort(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->configureWindowsPort();
        } else {
            $this->configureUnixPort();
        }
    }

    private function configureUnixPort(): void
    {
        $sttyCommand = sprintf(
            'stty -F %s %d cs%d -%s -%s',
            escapeshellarg($this->port),
            $this->options['baudRate'],
            $this->options['dataBits'],
            $this->getParity(),
            $this->getStopBits()
        );

        if ($this->options['flowControl'] === 'hardware') {
            $sttyCommand .= ' crtscts';
        } else {
            $sttyCommand .= ' -crtscts';
        }

        $sttyCommand .= ' raw -echo -echoe -echok -echoctl -echoke -iexten -onlcr';

        exec($sttyCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Failed to configure serial port: " . implode("\n", $output));
        }
    }

    private function configureWindowsPort(): void
    {
        $modeCommand = sprintf(
            'mode %s: baud=%d data=%d parity=%s stop=%s',
            str_replace('/dev/', '', $this->port),
            $this->options['baudRate'],
            $this->options['dataBits'],
            substr($this->getParity(), 0, 1),
            $this->options['stopBits'] == 1 ? '1' : '2'
        );

        exec($modeCommand, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new Exception("Failed to configure serial port: " . implode("\n", $output));
        }
    }

    private function getParity(): string
    {
        return match ($this->options['parity']) {
            'none' => 'parenb',
            'even' => 'parenb -parodd',
            'odd' => 'parenb parodd',
            default => 'parenb',
        };
    }

    private function getStopBits(): string
    {
        return $this->options['stopBits'] == 2 ? 'cstopb' : 'cstopb';
    }
}