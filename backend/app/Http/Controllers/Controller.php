<?php

namespace App\Http\Controllers;

use App\Exceptions\BusinessException;
use Illuminate\Support\Facades\Log;

abstract class Controller
{
    /**
     * Ambil pesan error yang aman untuk ditampilkan ke pengguna.
     * Hanya BusinessException yang pesannya dipakai; exception lain di-log
     * dan diganti pesan generik agar detail internal tidak bocor ke client.
     */
    protected function safeErrorMessage(\Throwable $e): string
    {
        if ($e instanceof BusinessException) {
            return $e->getMessage();
        }

        Log::error('[safeErrorMessage] ' . get_class($e) . ': ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);

        return 'Terjadi kesalahan pada server. Silakan coba lagi.';
    }
}
