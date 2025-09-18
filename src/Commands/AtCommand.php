<?php

declare(strict_types=1);

namespace Iamgerwin\GsmModem\Commands;

class AtCommand
{
    public const AT = 'AT';
    public const OK = 'OK';
    public const ERROR = 'ERROR';
    public const ECHO_OFF = 'ATE0';
    public const ECHO_ON = 'ATE1';
    public const GET_MANUFACTURER = 'AT+CGMI';
    public const GET_MODEL = 'AT+CGMM';
    public const GET_IMEI = 'AT+CGSN';
    public const GET_IMSI = 'AT+CIMI';
    public const GET_SIGNAL = 'AT+CSQ';
    public const GET_NETWORK = 'AT+COPS?';
    public const GET_OWN_NUMBER = 'AT+CNUM';
    public const SET_SMS_MODE = 'AT+CMGF=';
    public const SET_SMS_CHARSET = 'AT+CSCS=';
    public const SET_SMS_INDICATION = 'AT+CNMI=';
    public const SEND_SMS = 'AT+CMGS=';
    public const LIST_SMS = 'AT+CMGL=';
    public const READ_SMS = 'AT+CMGR=';
    public const DELETE_SMS = 'AT+CMGD=';
    public const DELETE_ALL_SMS = 'AT+CMGDA=';
    public const DIAL = 'ATD';
    public const ANSWER = 'ATA';
    public const HANGUP = 'ATH';
    public const GET_CALL_STATUS = 'AT+CLCC';
    public const SEND_USSD = 'AT+CUSD=';
    public const CHECK_PIN = 'AT+CPIN?';
    public const UNLOCK_PIN = 'AT+CPIN=';
    public const GET_SIM_STATUS = 'AT+CPIN?';
    public const SET_PDU_MODE = 'AT+CMGF=0';
    public const SET_TEXT_MODE = 'AT+CMGF=1';
    public const ENABLE_CALLER_ID = 'AT+CLIP=1';
    public const DISABLE_CALLER_ID = 'AT+CLIP=0';
    public const SET_STORAGE = 'AT+CPMS=';
    public const GET_STORAGE = 'AT+CPMS?';
    public const CTRL_Z = "\x1A";
    public const ESC = "\x1B";

    public static function formatCommand(string $command): string
    {
        return $command . "\r\n";
    }

    public static function isSuccessResponse(string $response): bool
    {
        return str_contains($response, self::OK);
    }

    public static function isErrorResponse(string $response): bool
    {
        return str_contains($response, self::ERROR) || str_contains($response, 'CME ERROR') || str_contains($response, 'CMS ERROR');
    }

    public static function parseSignalStrength(string $response): ?int
    {
        if (preg_match('/\+CSQ:\s*(\d+),/', $response, $matches)) {
            $rssi = (int) $matches[1];
            if ($rssi === 99) {
                return null;
            }
            return min(100, (int) (($rssi / 31) * 100));
        }
        return null;
    }

    public static function parseNetworkInfo(string $response): array
    {
        $info = [];
        if (preg_match('/\+COPS:\s*\d+,\d+,"([^"]+)",(\d+)/', $response, $matches)) {
            $info['operator'] = $matches[1];
            $info['mode'] = match ((int) $matches[2]) {
                0 => 'GSM',
                2 => '3G',
                7 => '4G',
                default => 'Unknown',
            };
        }
        return $info;
    }
}