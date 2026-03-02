<?php

declare(strict_types=1);

namespace App\Contracts;

interface OtpService
{
    public function send(string $phone, string $purpose): void;

    public function verify(string $phone, string $purpose, string $code): bool;

    public function consume(string $phone, string $purpose): void;

    public function canSend(string $phone, string $purpose): bool;
}
