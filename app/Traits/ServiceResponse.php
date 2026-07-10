<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ServiceResponse
{
    /**
     * Return a success payload
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return array
     */
    protected function successPayload($data, string $message = 'Success', int $code = 200): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'code' => $code,
        ];
    }

    /**
     * Return an error payload
     *
     * @param string $message
     * @param mixed $errors
     * @param int $code
     * @return array
     */
    protected function errorPayload(string $message, $errors = [], int $code = 400): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
        ];
    }

    /**
     * Return an auth success payload (standard OAuth2 root fields)
     *
     * @param array $data
     * @param string $message
     * @param int $code
     * @return array
     */
    protected function authSuccessPayload(array $data, string $message = 'Success', int $code = 200): array
    {
        return array_merge([
            'success' => true,
            'code' => $code,
            'message' => $message,
        ], $data);
    }
}
