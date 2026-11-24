<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

/** Returns ['locked'=>bool,'remaining'=>int,'retry_after'=>int]. */
function wa_rate_status(PDO $pdo, string $identifier): array
{
    $since = time() - LOCKOUT_SECONDS;
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM login_attempts WHERE identifier = ? AND success = 0 AND created_at >= ?');
    $stmt->execute([$identifier, $since]);
    $fails = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT MAX(created_at) AS last FROM login_attempts WHERE identifier = ? AND success = 0 AND created_at >= ?');
    $stmt->execute([$identifier, $since]);
    $last = (int)($stmt->fetchColumn() ?: 0);

    if ($fails >= MAX_LOGIN_ATTEMPTS && $last > 0) {
        $retry = ($last + LOCKOUT_SECONDS) - time();
        return ['locked' => $retry > 0, 'remaining' => 0, 'retry_after' => max(0, $retry)];
    }
    return ['locked' => false, 'remaining' => max(0, MAX_LOGIN_ATTEMPTS - $fails), 'retry_after' => 0];
}

function wa_record_attempt(PDO $pdo, string $identifier, bool $success): void
{
    $stmt = $pdo->prepare('INSERT INTO login_attempts (identifier, ip, success, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([
        $identifier,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $success ? 1 : 0,
        time(),
    ]);

    // Keep the table small.
    $pdo->exec('DELETE FROM login_attempts WHERE created_at < ' . (time() - 86400));
}

function wa_clear_attempts(PDO $pdo, string $identifier): void
{
    $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE identifier = ?');
    $stmt->execute([$identifier]);
}
