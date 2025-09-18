<?php

declare(strict_types=1);

namespace Iamgerwin\GsmModem;

use Exception;
use Iamgerwin\GsmModem\Commands\AtCommand;
use Iamgerwin\GsmModem\Contracts\ModemInterface;
use Iamgerwin\GsmModem\Enums\MessageStatus;
use Iamgerwin\GsmModem\Enums\SmsMode;
use Iamgerwin\GsmModem\Helpers\PduHelper;
use Iamgerwin\GsmModem\Models\SmsMessage;
use Illuminate\Support\Collection;

class GsmModem implements ModemInterface
{
    private ?SerialPort $serialPort = null;
    private SmsMode $smsMode = SmsMode::TEXT;
    private array $eventListeners = [];
    private bool $debug = false;

    public function __construct(array $config = [])
    {
        $this->debug = $config['debug'] ?? false;
        $this->smsMode = isset($config['sms_mode']) ? SmsMode::from($config['sms_mode']) : SmsMode::TEXT;
    }

    public function open(string $port, array $options = []): bool
    {
        try {
            $this->serialPort = new SerialPort($port, $options);
            $this->serialPort->open();

            $this->initializeModem();

            return true;
        } catch (Exception $e) {
            $this->log('Error opening port: ' . $e->getMessage());
            return false;
        }
    }

    public function close(): void
    {
        if ($this->serialPort) {
            $this->serialPort->close();
            $this->serialPort = null;
        }
    }

    public function isOpen(): bool
    {
        return $this->serialPort && $this->serialPort->isOpen();
    }

    public function executeCommand(string $command, int $timeout = 10000): string
    {
        if (!$this->isOpen()) {
            throw new Exception('Modem is not connected');
        }

        $this->log("Sending: $command");

        $this->serialPort->flush();
        $this->serialPort->write(AtCommand::formatCommand($command));

        $response = $this->serialPort->readUntil('OK', $timeout);
        if (empty($response)) {
            $response = $this->serialPort->readUntil('ERROR', 1000);
        }

        $this->log("Received: $response");

        return $response;
    }

    public function sendSms(string $number, string $message, array $options = []): bool
    {
        try {
            if ($this->smsMode === SmsMode::PDU) {
                return $this->sendSmsPdu($number, $message);
            }

            return $this->sendSmsText($number, $message);
        } catch (Exception $e) {
            $this->log('Error sending SMS: ' . $e->getMessage());
            return false;
        }
    }

    private function sendSmsText(string $number, string $message): bool
    {
        $this->executeCommand(AtCommand::SET_TEXT_MODE);
        $this->executeCommand(AtCommand::SET_SMS_CHARSET . '"GSM"');

        $command = AtCommand::SEND_SMS . '"' . $number . '"';
        $this->serialPort->write(AtCommand::formatCommand($command));

        usleep(100000);

        $this->serialPort->write($message . AtCommand::CTRL_Z);

        $response = $this->serialPort->readUntil('OK', 30000);

        return AtCommand::isSuccessResponse($response);
    }

    private function sendSmsPdu(string $number, string $message): bool
    {
        $this->executeCommand(AtCommand::SET_PDU_MODE);

        $pdu = PduHelper::encodePdu($number, $message);
        $pduLength = (strlen($pdu) / 2) - 1;

        $command = AtCommand::SEND_SMS . $pduLength;
        $this->serialPort->write(AtCommand::formatCommand($command));

        usleep(100000);

        $this->serialPort->write($pdu . AtCommand::CTRL_Z);

        $response = $this->serialPort->readUntil('OK', 30000);

        return AtCommand::isSuccessResponse($response);
    }

    public function getInbox(): array
    {
        try {
            if ($this->smsMode === SmsMode::PDU) {
                return $this->getInboxPdu();
            }

            return $this->getInboxText();
        } catch (Exception $e) {
            $this->log('Error getting inbox: ' . $e->getMessage());
            return [];
        }
    }

    private function getInboxText(): array
    {
        $this->executeCommand(AtCommand::SET_TEXT_MODE);

        $response = $this->executeCommand(AtCommand::LIST_SMS . MessageStatus::ALL->getAtCommand());

        $messages = [];
        $lines = explode("\n", $response);

        for ($i = 0; $i < count($lines); $i++) {
            if (str_starts_with($lines[$i], '+CMGL:')) {
                $messageLine = $lines[$i];
                if (isset($lines[$i + 1])) {
                    $messageLine .= "\n" . $lines[$i + 1];
                }

                $message = SmsMessage::fromAtResponse($messageLine);
                if ($message) {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }

    private function getInboxPdu(): array
    {
        $this->executeCommand(AtCommand::SET_PDU_MODE);

        $response = $this->executeCommand(AtCommand::LIST_SMS . '4');

        $messages = [];
        $lines = explode("\n", $response);

        for ($i = 0; $i < count($lines); $i++) {
            if (preg_match('/\+CMGL:\s*(\d+),/', $lines[$i], $matches)) {
                $index = (int) $matches[1];
                if (isset($lines[$i + 1])) {
                    $pdu = trim($lines[$i + 1]);
                    try {
                        $decoded = PduHelper::decodePdu($pdu);
                        $messages[] = SmsMessage::fromPdu($index, $pdu, $decoded);
                    } catch (Exception $e) {
                        $this->log('Error decoding PDU: ' . $e->getMessage());
                    }
                }
            }
        }

        return $messages;
    }

    public function deleteMessage(int $index): bool
    {
        try {
            $response = $this->executeCommand(AtCommand::DELETE_SMS . $index);
            return AtCommand::isSuccessResponse($response);
        } catch (Exception $e) {
            $this->log('Error deleting message: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteAllMessages(): bool
    {
        try {
            $this->executeCommand(AtCommand::SET_TEXT_MODE);
            $response = $this->executeCommand(AtCommand::DELETE_ALL_SMS . '"DEL ALL"');
            return AtCommand::isSuccessResponse($response);
        } catch (Exception $e) {
            $this->log('Error deleting all messages: ' . $e->getMessage());
            return false;
        }
    }

    public function getSignalStrength(): ?int
    {
        try {
            $response = $this->executeCommand(AtCommand::GET_SIGNAL);
            return AtCommand::parseSignalStrength($response);
        } catch (Exception $e) {
            $this->log('Error getting signal strength: ' . $e->getMessage());
            return null;
        }
    }

    public function getNetworkInfo(): array
    {
        try {
            $response = $this->executeCommand(AtCommand::GET_NETWORK);
            return AtCommand::parseNetworkInfo($response);
        } catch (Exception $e) {
            $this->log('Error getting network info: ' . $e->getMessage());
            return [];
        }
    }

    public function getOwnNumber(): ?string
    {
        try {
            $response = $this->executeCommand(AtCommand::GET_OWN_NUMBER);

            if (preg_match('/\+CNUM:[^,]*,"([^"]+)"/', $response, $matches)) {
                return $matches[1];
            }

            return null;
        } catch (Exception $e) {
            $this->log('Error getting own number: ' . $e->getMessage());
            return null;
        }
    }

    public function makeCall(string $number): bool
    {
        try {
            $command = AtCommand::DIAL . $number . ';';
            $response = $this->executeCommand($command);
            return AtCommand::isSuccessResponse($response);
        } catch (Exception $e) {
            $this->log('Error making call: ' . $e->getMessage());
            return false;
        }
    }

    public function hangup(): bool
    {
        try {
            $response = $this->executeCommand(AtCommand::HANGUP);
            return AtCommand::isSuccessResponse($response);
        } catch (Exception $e) {
            $this->log('Error hanging up: ' . $e->getMessage());
            return false;
        }
    }

    public function answerCall(): bool
    {
        try {
            $response = $this->executeCommand(AtCommand::ANSWER);
            return AtCommand::isSuccessResponse($response);
        } catch (Exception $e) {
            $this->log('Error answering call: ' . $e->getMessage());
            return false;
        }
    }

    public function sendUssd(string $command): ?string
    {
        try {
            $ussdCommand = AtCommand::SEND_USSD . '1,"' . $command . '",15';
            $response = $this->executeCommand($ussdCommand, 30000);

            if (preg_match('/\+CUSD:\s*\d+,"([^"]+)"/', $response, $matches)) {
                return $matches[1];
            }

            return null;
        } catch (Exception $e) {
            $this->log('Error sending USSD: ' . $e->getMessage());
            return null;
        }
    }

    public function getSimInfo(): array
    {
        $info = [];

        try {
            $response = $this->executeCommand(AtCommand::GET_IMSI);
            if (preg_match('/(\d{15})/', $response, $matches)) {
                $info['imsi'] = $matches[1];
            }

            $response = $this->executeCommand(AtCommand::GET_SIM_STATUS);
            if (str_contains($response, 'READY')) {
                $info['status'] = 'READY';
            } elseif (str_contains($response, 'SIM PIN')) {
                $info['status'] = 'PIN_REQUIRED';
            } else {
                $info['status'] = 'UNKNOWN';
            }

            return $info;
        } catch (Exception $e) {
            $this->log('Error getting SIM info: ' . $e->getMessage());
            return [];
        }
    }

    public function unlockSim(string $pin): bool
    {
        try {
            $response = $this->executeCommand(AtCommand::UNLOCK_PIN . '"' . $pin . '"');
            return AtCommand::isSuccessResponse($response);
        } catch (Exception $e) {
            $this->log('Error unlocking SIM: ' . $e->getMessage());
            return false;
        }
    }

    public function getModemInfo(): array
    {
        $info = [];

        try {
            $response = $this->executeCommand(AtCommand::GET_MANUFACTURER);
            if (preg_match('/([A-Za-z]+)/', $response, $matches)) {
                $info['manufacturer'] = $matches[1];
            }

            $response = $this->executeCommand(AtCommand::GET_MODEL);
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                if (!empty($line) && !str_contains($line, 'AT+') && !str_contains($line, 'OK')) {
                    $info['model'] = trim($line);
                    break;
                }
            }

            $response = $this->executeCommand(AtCommand::GET_IMEI);
            if (preg_match('/(\d{15})/', $response, $matches)) {
                $info['imei'] = $matches[1];
            }

            return $info;
        } catch (Exception $e) {
            $this->log('Error getting modem info: ' . $e->getMessage());
            return [];
        }
    }

    public function setSmsMode(SmsMode $mode): void
    {
        $this->smsMode = $mode;
    }

    public function on(string $event, callable $callback): void
    {
        if (!isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }

        $this->eventListeners[$event][] = $callback;
    }

    public function emit(string $event, ...$args): void
    {
        if (isset($this->eventListeners[$event])) {
            foreach ($this->eventListeners[$event] as $callback) {
                call_user_func_array($callback, $args);
            }
        }
    }

    private function initializeModem(): void
    {
        $this->executeCommand(AtCommand::AT);
        $this->executeCommand(AtCommand::ECHO_OFF);
        $this->executeCommand(AtCommand::SET_SMS_INDICATION . '2,1,0,0,0');
        $this->executeCommand(AtCommand::ENABLE_CALLER_ID);

        $modeCommand = $this->smsMode === SmsMode::PDU ? AtCommand::SET_PDU_MODE : AtCommand::SET_TEXT_MODE;
        $this->executeCommand($modeCommand);
    }

    private function log(string $message): void
    {
        if ($this->debug) {
            error_log('[GsmModem] ' . $message);
        }
    }
}