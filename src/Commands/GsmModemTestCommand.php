<?php

namespace Iamgerwin\GsmModem\Commands;

use Iamgerwin\GsmModem\GsmModem;
use Illuminate\Console\Command;

class GsmModemTestCommand extends Command
{
    protected $signature = 'gsm-modem:test {port?} {--baudrate=115200}';

    protected $description = 'Test GSM modem connection and display information';

    public function handle(GsmModem $modem): int
    {
        $port = $this->argument('port') ?? config('gsm-modem.connections.default.port');
        $baudRate = (int) $this->option('baudrate');

        $this->info('Testing GSM Modem connection...');
        $this->info("Port: $port");
        $this->info("Baud Rate: $baudRate");

        if (!$modem->open($port, ['baudRate' => $baudRate])) {
            $this->error('Failed to open modem connection');
            return self::FAILURE;
        }

        $this->info('✓ Modem connected successfully');

        $this->line('');
        $this->info('Modem Information:');
        $modemInfo = $modem->getModemInfo();
        foreach ($modemInfo as $key => $value) {
            $this->line("  " . ucfirst($key) . ": $value");
        }

        $this->line('');
        $this->info('SIM Information:');
        $simInfo = $modem->getSimInfo();
        foreach ($simInfo as $key => $value) {
            $this->line("  " . ucfirst($key) . ": $value");
        }

        $this->line('');
        $this->info('Network Information:');
        $networkInfo = $modem->getNetworkInfo();
        foreach ($networkInfo as $key => $value) {
            $this->line("  " . ucfirst($key) . ": $value");
        }

        $signalStrength = $modem->getSignalStrength();
        if ($signalStrength !== null) {
            $this->line("  Signal Strength: $signalStrength%");
        }

        $ownNumber = $modem->getOwnNumber();
        if ($ownNumber) {
            $this->line("  Own Number: $ownNumber");
        }

        $modem->close();
        $this->info('✓ Test completed successfully');

        return self::SUCCESS;
    }
}