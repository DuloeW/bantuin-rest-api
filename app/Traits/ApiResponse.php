<?php

namespace App\Traits;

trait ApiResponse
{
    protected function successResponse($data = [], $message = 'success', $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse($message = 'error', $data = [], $code = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'data' => $data,
        ], $code);
    }
}