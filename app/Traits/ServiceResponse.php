<?php

namespace App\Traits;

trait ServiceResponse
{
    protected function successPayload($data = [], string $message = 'success', int $code = 200): array
    {
        return $this->payload(true, $code, $message, $data);
    }

    protected function errorPayload(string $message = 'error', $data = [], int $code = 400): array
    {
        return $this->payload(false, $code, $message, $data);
    }

    protected function payload(bool $success, int $code, string $message, $data = []): array
    {
        return [
            'success' => $success,
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ];
    }
}