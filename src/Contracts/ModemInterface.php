<?php

declare(strict_types=1);

namespace Iamgerwin\GsmModem\Contracts;

interface ModemInterface
{
    public function open(string $port, array $options = []): bool;

    public function close(): void;

    public function isOpen(): bool;

    public function executeCommand(string $command, int $timeout = 10000): string;

    public function sendSms(string $number, string $message, array $options = []): bool;

    public function getInbox(): array;

    public function deleteMessage(int $index): bool;

    public function deleteAllMessages(): bool;

    public function getSignalStrength(): ?int;

    public function getNetworkInfo(): array;

    public function getOwnNumber(): ?string;

    public function makeCall(string $number): bool;

    public function hangup(): bool;

    public function answerCall(): bool;

    public function sendUssd(string $command): ?string;

    public function getSimInfo(): array;

    public function unlockSim(string $pin): bool;
}