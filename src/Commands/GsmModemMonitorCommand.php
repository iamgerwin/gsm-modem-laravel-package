<?php

namespace Iamgerwin\GsmModem\Commands;

use Iamgerwin\GsmModem\GsmModem;
use Illuminate\Console\Command;

class GsmModemMonitorCommand extends Command
{
    protected $signature = 'gsm-modem:monitor {--port=} {--interval=5}';

    protected $description = 'Monitor GSM modem for incoming messages and calls';

    private bool $shouldStop = false;

    public function handle(GsmModem $modem): int
    {
        $port = $this->option('port') ?? config('gsm-modem.connections.default.port');
        $interval = (int) $this->option('interval');

        $this->info('Starting GSM Modem monitor...');
        $this->info("Port: $port");
        $this->info("Check interval: {$interval} seconds");
        $this->info('Press Ctrl+C to stop');

        if (!$modem->isOpen()) {
            if (!$modem->open($port)) {
                $this->error('Failed to open modem connection');
                return self::FAILURE;
            }
        }

        pcntl_signal(SIGINT, [$this, 'handleSignal']);
        pcntl_signal(SIGTERM, [$this, 'handleSignal']);

        $lastMessageCount = 0;

        while (!$this->shouldStop) {
            pcntl_signal_dispatch();

            $inbox = $modem->getInbox();
            $currentMessageCount = count($inbox);

            if ($currentMessageCount > $lastMessageCount) {
                $newMessages = $currentMessageCount - $lastMessageCount;
                $this->info("📱 New messages received: $newMessages");

                $recentMessages = array_slice($inbox, -$newMessages);
                foreach ($recentMessages as $message) {
                    $this->displayMessage($message);
                }

                $lastMessageCount = $currentMessageCount;
            }

            $signalStrength = $modem->getSignalStrength();
            if ($signalStrength !== null) {
                $this->line("Signal strength: {$signalStrength}%");
            }

            sleep($interval);
        }

        $modem->close();
        $this->info('Monitor stopped.');

        return self::SUCCESS;
    }

    private function displayMessage($message): void
    {
        $this->line('----------------------------');
        $this->info('New SMS Message:');
        $this->line('From: ' . $message->sender);
        $this->line('Time: ' . $message->timestamp->format('Y-m-d H:i:s'));
        $this->line('Message: ' . $message->message);
        $this->line('----------------------------');
    }

    public function handleSignal(int $signal): void
    {
        $this->shouldStop = true;
    }
}