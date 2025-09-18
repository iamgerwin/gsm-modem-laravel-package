<?php

namespace Iamgerwin\GsmModem\Commands;

use Iamgerwin\GsmModem\GsmModem;
use Illuminate\Console\Command;

class GsmModemSendSmsCommand extends Command
{
    protected $signature = 'gsm-modem:send-sms {number} {message} {--port=} {--mode=TEXT}';

    protected $description = 'Send an SMS message via GSM modem';

    public function handle(GsmModem $modem): int
    {
        $number = $this->argument('number');
        $message = $this->argument('message');
        $port = $this->option('port') ?? config('gsm-modem.connections.default.port');
        $mode = $this->option('mode');

        $this->info('Sending SMS...');
        $this->info("To: $number");
        $this->info("Message: $message");

        if (!$modem->isOpen()) {
            if (!$modem->open($port)) {
                $this->error('Failed to open modem connection');
                return self::FAILURE;
            }
        }

        if ($modem->sendSms($number, $message)) {
            $this->info('✓ SMS sent successfully');
            return self::SUCCESS;
        } else {
            $this->error('Failed to send SMS');
            return self::FAILURE;
        }
    }
}