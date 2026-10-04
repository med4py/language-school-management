<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    Database::connect();
    echo "Connexion MySQL réussie.\n";
} catch (Throwable $exception) {
    error_log((string) $exception);
    fwrite(STDERR, "Connexion à la base de données indisponible.\n");
    exit(1);
}
