<?php

declare(strict_types=1);

namespace Iamgerwin\GsmModem\Models;

use DateTime;

class SmsMessage
{
    public function __construct(
        public readonly int $index,
        public readonly string $sender,
        public readonly string $message,
        public readonly DateTime $timestamp,
        public readonly string $status = 'UNREAD',
        public readonly array $raw = []
    ) {}

    public static function fromAtResponse(string $response): ?self
    {
        if (!preg_match('/\+CMGL:\s*(\d+),"([^"]+)","([^"]+)",[^,]*,"([^"]+)"/', $response, $matches)) {
            return null;
        }

        $index = (int) $matches[1];
        $status = $matches[2];
        $sender = $matches[3];
        $timestamp = DateTime::createFromFormat('y/m/d,H:i:sP', $matches[4]);

        $lines = explode("\n", $response);
        $message = isset($lines[1]) ? trim($lines[1]) : '';

        return new self(
            index: $index,
            sender: $sender,
            message: $message,
            timestamp: $timestamp ?: new DateTime(),
            status: $status,
            raw: ['response' => $response]
        );
    }

    public static function fromPdu(int $index, string $pdu, array $decoded): self
    {
        return new self(
            index: $index,
            sender: $decoded['sender'],
            message: $decoded['message'],
            timestamp: DateTime::createFromFormat('Y-m-d H:i:s', $decoded['timestamp']) ?: new DateTime(),
            status: 'UNREAD',
            raw: ['pdu' => $pdu, 'decoded' => $decoded]
        );
    }

    public function toArray(): array
    {
        return [
            'index' => $this->index,
            'sender' => $this->sender,
            'message' => $this->message,
            'timestamp' => $this->timestamp->format('Y-m-d H:i:s'),
            'status' => $this->status,
        ];
    }
}