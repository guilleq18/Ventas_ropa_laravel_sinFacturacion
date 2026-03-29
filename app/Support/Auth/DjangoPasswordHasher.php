<?php

namespace App\Support\Auth;

class DjangoPasswordHasher
{
    public function isLegacyHash(?string $hash): bool
    {
        return is_string($hash) && str_starts_with($hash, 'pbkdf2_sha256$');
    }

    public function check(string $plainPassword, ?string $storedHash): bool
    {
        if (! $this->isLegacyHash($storedHash)) {
            return false;
        }

        $parts = explode('$', (string) $storedHash, 4);

        if (count($parts) !== 4) {
            return false;
        }

        [, $iterations, $salt, $encodedHash] = $parts;

        if (! ctype_digit($iterations) || $salt === '' || $encodedHash === '') {
            return false;
        }

        $derived = hash_pbkdf2(
            'sha256',
            $plainPassword,
            $salt,
            (int) $iterations,
            32,
            true,
        );

        return hash_equals($encodedHash, base64_encode($derived));
    }
}
