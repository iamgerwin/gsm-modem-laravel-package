<?php

declare(strict_types=1);

namespace Iamgerwin\GsmModem\Helpers;

class PduHelper
{
    private static array $gsmAlphabet = [
        '@', '£', '$', '¥', 'è', 'é', 'ù', 'ì', 'ò', 'Ç', "\n", 'Ø', 'ø', "\r", 'Å', 'å',
        'Δ', '_', 'Φ', 'Γ', 'Λ', 'Ω', 'Π', 'Ψ', 'Σ', 'Θ', 'Ξ', "\x1B", 'Æ', 'æ', 'ß', 'É',
        ' ', '!', '"', '#', '¤', '%', '&', "'", '(', ')', '*', '+', ',', '-', '.', '/',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':', ';', '<', '=', '>', '?',
        '¡', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
        'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'Ä', 'Ö', 'Ñ', 'Ü', '§',
        '¿', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o',
        'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'ä', 'ö', 'ñ', 'ü', 'à'
    ];

    public static function encodePdu(string $number, string $message): string
    {
        $pdu = '00';
        $pdu .= '01';
        $pdu .= '00';

        $formattedNumber = self::formatPhoneNumber($number);
        $pdu .= sprintf('%02X', strlen($formattedNumber));
        $pdu .= '91';
        $pdu .= self::swapNibbles($formattedNumber);

        $pdu .= '00';
        $pdu .= '00';

        $encodedMessage = self::encode7bit($message);
        $pdu .= sprintf('%02X', strlen($message));
        $pdu .= $encodedMessage;

        return $pdu;
    }

    public static function decodePdu(string $pdu): array
    {
        $data = [];
        $index = 0;

        $smscLength = hexdec(substr($pdu, $index, 2)) * 2;
        $index += 2 + $smscLength;

        $pduType = substr($pdu, $index, 2);
        $index += 2;

        $senderLength = hexdec(substr($pdu, $index, 2));
        $index += 2;

        $senderType = substr($pdu, $index, 2);
        $index += 2;

        $senderNumberLength = $senderLength;
        if ($senderLength % 2 !== 0) {
            $senderNumberLength++;
        }

        $senderNumber = self::swapNibbles(substr($pdu, $index, $senderNumberLength));
        $senderNumber = str_replace('F', '', $senderNumber);
        $data['sender'] = '+' . $senderNumber;
        $index += $senderNumberLength;

        $protocolId = substr($pdu, $index, 2);
        $index += 2;

        $dataEncoding = substr($pdu, $index, 2);
        $index += 2;

        $timestamp = substr($pdu, $index, 14);
        $data['timestamp'] = self::decodeTimestamp($timestamp);
        $index += 14;

        $messageLength = hexdec(substr($pdu, $index, 2));
        $index += 2;

        $messageHex = substr($pdu, $index);
        $data['message'] = self::decode7bit($messageHex, $messageLength);

        return $data;
    }

    private static function formatPhoneNumber(string $number): string
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (strlen($number) % 2 !== 0) {
            $number .= 'F';
        }
        return $number;
    }

    private static function swapNibbles(string $hex): string
    {
        $result = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $result .= $hex[$i + 1] . $hex[$i];
        }
        return $result;
    }

    private static function encode7bit(string $text): string
    {
        $encoded = '';
        $shift = 0;
        $leftover = 0;

        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];
            $charCode = array_search($char, self::$gsmAlphabet);

            if ($charCode === false) {
                $charCode = 0x3F;
            }

            $byte = ($charCode << (7 - $shift)) | $leftover;
            $leftover = $charCode >> $shift;

            if ($shift === 7) {
                $shift = 0;
                $leftover = 0;
            } else {
                $encoded .= sprintf('%02X', $byte & 0xFF);
                $shift++;
            }
        }

        if ($shift > 0) {
            $encoded .= sprintf('%02X', $leftover);
        }

        return $encoded;
    }

    private static function decode7bit(string $hex, int $length): string
    {
        $decoded = '';
        $bits = '';

        for ($i = 0; $i < strlen($hex); $i += 2) {
            $byte = hexdec(substr($hex, $i, 2));
            $bits = str_pad(decbin($byte), 8, '0', STR_PAD_LEFT) . $bits;
        }

        for ($i = 0; $i < $length; $i++) {
            if (strlen($bits) >= 7) {
                $charBits = substr($bits, -7);
                $bits = substr($bits, 0, -7);
                $charCode = bindec($charBits);

                if ($charCode < count(self::$gsmAlphabet)) {
                    $decoded .= self::$gsmAlphabet[$charCode];
                } else {
                    $decoded .= '?';
                }
            }
        }

        return $decoded;
    }

    private static function decodeTimestamp(string $timestamp): string
    {
        $year = self::swapNibbles(substr($timestamp, 0, 2));
        $month = self::swapNibbles(substr($timestamp, 2, 2));
        $day = self::swapNibbles(substr($timestamp, 4, 2));
        $hour = self::swapNibbles(substr($timestamp, 6, 2));
        $minute = self::swapNibbles(substr($timestamp, 8, 2));
        $second = self::swapNibbles(substr($timestamp, 10, 2));

        return "20{$year}-{$month}-{$day} {$hour}:{$minute}:{$second}";
    }
}