<?php

namespace Iamgerwin\GsmModem;

use Iamgerwin\GsmModem\Commands\GsmModemMonitorCommand;
use Iamgerwin\GsmModem\Commands\GsmModemSendSmsCommand;
use Iamgerwin\GsmModem\Commands\GsmModemTestCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GsmModemServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('gsm-modem')
            ->hasConfigFile()
            ->hasCommands([
                GsmModemTestCommand::class,
                GsmModemSendSmsCommand::class,
                GsmModemMonitorCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(GsmModem::class, function ($app) {
            $config = $app['config']->get('gsm-modem');
            $modem = new GsmModem($config);

            if ($config['auto_connect'] ?? false) {
                $defaultConnection = $config['connections'][$config['default']] ?? [];
                if ($defaultConnection) {
                    $modem->open($defaultConnection['port'], [
                        'baudRate' => $defaultConnection['baud_rate'] ?? 115200,
                        'dataBits' => $defaultConnection['data_bits'] ?? 8,
                        'parity' => $defaultConnection['parity'] ?? 'none',
                        'stopBits' => $defaultConnection['stop_bits'] ?? 1,
                        'flowControl' => $defaultConnection['flow_control'] ?? 'none',
                    ]);

                    if ($config['pin'] ?? false) {
                        $modem->unlockSim($config['pin']);
                    }
                }
            }

            return $modem;
        });

        $this->app->alias(GsmModem::class, 'gsm-modem');
    }
}