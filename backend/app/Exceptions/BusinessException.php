<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception bisnis yang sengaja dilempar aplikasi dengan pesan aman
 * untuk ditampilkan langsung kepada pengguna (tidak membocorkan detail internal).
 */
class BusinessException extends RuntimeException
{
}