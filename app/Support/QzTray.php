<?php

namespace App\Support;

use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * Signing for QZ Tray, the desktop agent that prints labels without the
 * browser's print dialog.
 *
 * QZ Tray prints silently only for requests signed by a certificate it trusts.
 * We sign with our own self-signed pair: the private key never leaves the
 * server, and each printing PC trusts the public certificate once by saving it
 * as QZ Tray's override.crt. One pair serves every tenant.
 */
class QzTray
{
    public static function certificatePath(): string
    {
        return config('services.qz.certificate');
    }

    public static function privateKeyPath(): string
    {
        return config('services.qz.private_key');
    }

    public static function isConfigured(): bool
    {
        return is_readable(self::certificatePath()) && is_readable(self::privateKeyPath());
    }

    public static function certificate(): ?string
    {
        return is_readable(self::certificatePath()) ? file_get_contents(self::certificatePath()) : null;
    }

    /**
     * Base64 SHA-512 signature of the message qz-tray.js asks us to sign.
     */
    public static function sign(string $message): string
    {
        $key = is_readable(self::privateKeyPath()) ? openssl_pkey_get_private(file_get_contents(self::privateKeyPath())) : false;

        if (! $key || ! openssl_sign($message, $signature, $key, OPENSSL_ALGO_SHA512)) {
            throw new RuntimeException('The QZ Tray signing key is missing or unreadable. Run php artisan qz:certificate.');
        }

        return base64_encode($signature);
    }

    public static function generate(string $organisation, int $days = 7300): void
    {
        // PHP's openssl needs a config file to add x509 extensions. QZ Tray treats
        // the override certificate as a root, so it is marked as a CA.
        $config = tempnam(sys_get_temp_dir(), 'qz-openssl');
        file_put_contents($config, implode("\n", [
            '[req]',
            'distinguished_name = dn',
            '[dn]',
            '[v3_ca]',
            'basicConstraints = critical,CA:TRUE',
            'subjectKeyIdentifier = hash',
            'authorityKeyIdentifier = keyid:always,issuer',
            '',
        ]));

        try {
            $options = [
                'config' => $config,
                'digest_alg' => 'sha256',
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'x509_extensions' => 'v3_ca',
            ];

            $key = openssl_pkey_new($options);
            $csr = $key ? openssl_csr_new(['commonName' => $organisation, 'organizationName' => $organisation], $key, $options) : false;
            $x509 = $csr ? openssl_csr_sign($csr, null, $key, $days, $options, random_int(1, PHP_INT_MAX)) : false;

            if (! $x509 || ! openssl_x509_export($x509, $certificate) || ! openssl_pkey_export($key, $privateKey, null, $options)) {
                throw new RuntimeException('OpenSSL could not generate the QZ Tray certificate: '.openssl_error_string());
            }
        } finally {
            @unlink($config);
        }

        File::ensureDirectoryExists(dirname(self::certificatePath()));
        File::ensureDirectoryExists(dirname(self::privateKeyPath()));
        File::put(self::certificatePath(), $certificate);
        File::put(self::privateKeyPath(), $privateKey);
        chmod(self::privateKeyPath(), 0600);
    }
}
