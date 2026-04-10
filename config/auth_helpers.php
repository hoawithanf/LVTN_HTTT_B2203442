<?php

function nln_is_password_hash(string $storedHash): bool
{
    $info = password_get_info($storedHash);
    return !empty($info['algo']);
}

function nln_is_legacy_sha256(string $storedHash): bool
{
    return (bool) preg_match('/^[a-f0-9]{64}$/i', $storedHash);
}

function nln_hash_password(string $plainPassword): string
{
    return password_hash($plainPassword, PASSWORD_DEFAULT);
}

function nln_verify_password(string $plainPassword, string $storedHash): bool
{
    if ($storedHash === '') {
        return false;
    }

    if (nln_is_password_hash($storedHash)) {
        return password_verify($plainPassword, $storedHash);
    }

    if (nln_is_legacy_sha256($storedHash)) {
        return hash('sha256', $plainPassword) === strtolower($storedHash);
    }

    return hash_equals($storedHash, $plainPassword);
}

function nln_password_needs_rehash(string $storedHash): bool
{
    if (!nln_is_password_hash($storedHash)) {
        return true;
    }

    return password_needs_rehash($storedHash, PASSWORD_DEFAULT);
}

function nln_upgrade_password_hash(mysqli $conn, int $userId, string $plainPassword): bool
{
    $newHash = nln_hash_password($plainPassword);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param("si", $newHash, $userId);
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}
