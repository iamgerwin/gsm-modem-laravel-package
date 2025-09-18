<?php

namespace Iamgerwin\GsmModem\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool open(string $port, array $options = [])
 * @method static void close()
 * @method static bool isOpen()
 * @method static string executeCommand(string $command, int $timeout = 10000)
 * @method static bool sendSms(string $number, string $message, array $options = [])
 * @method static array getInbox()
 * @method static bool deleteMessage(int $index)
 * @method static bool deleteAllMessages()
 * @method static ?int getSignalStrength()
 * @method static array getNetworkInfo()
 * @method static ?string getOwnNumber()
 * @method static bool makeCall(string $number)
 * @method static bool hangup()
 * @method static bool answerCall()
 * @method static ?string sendUssd(string $command)
 * @method static array getSimInfo()
 * @method static bool unlockSim(string $pin)
 * @method static array getModemInfo()
 *
 * @see \Iamgerwin\GsmModem\GsmModem
 */
class GsmModem extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Iamgerwin\GsmModem\GsmModem::class;
    }
}