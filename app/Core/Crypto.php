<?php
namespace App\Core;

/**
 * Symmetric encryption for stored portal credentials.
 * Uses libsodium secretbox with APP_KEY (32 bytes, base64: prefix).
 */
class Crypto
{
    private static function key(): string
    {
        $k = (string) env('APP_KEY', '');
        if (str_starts_with($k, 'base64:')) {
            $k = base64_decode(substr($k, 7));
        }
        if (strlen($k) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            // derive a 32-byte key from whatever is provided
            $k = hash('sha256', $k, true);
        }
        return $k;
    }

    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') return null;
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plain, $nonce, self::key());
        return $nonce . $cipher; // store raw bytes (VARBINARY)
    }

    public static function decrypt(?string $blob): ?string
    {
        if ($blob === null || $blob === '') return null;
        $nonce = substr($blob, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($blob, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, self::key());
        return $plain === false ? null : $plain;
    }
}
