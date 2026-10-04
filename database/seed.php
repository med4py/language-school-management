<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::connect();
    $db->beginTransaction();

    $student = $db->prepare(
        'INSERT INTO etudiants (cin, nom, email, tel, login, password)
         VALUES (:cin, :nom, :email, :tel, :login, :password)
         ON DUPLICATE KEY UPDATE nom = VALUES(nom), cin = VALUES(cin), email = VALUES(email),
         tel = VALUES(tel), password = VALUES(password)'
    );
    $student->execute([
        'cin' => 'EE123456',
        'nom' => 'Ahmed Student',
        'email' => 'student@example.com',
        'tel' => '0612345678',
        'login' => 'student',
        'password' => password_hash('Student123!', PASSWORD_DEFAULT),
    ]);

    $admin = $db->prepare(
        'INSERT INTO admins (nom, email, login, password)
         VALUES (:nom, :email, :login, :password)
         ON DUPLICATE KEY UPDATE nom = VALUES(nom), email = VALUES(email), password = VALUES(password)'
    );
    $admin->execute([
        'nom' => 'Admin',
        'email' => 'admin@example.com',
        'login' => 'admin',
        'password' => password_hash('Admin123!', PASSWORD_DEFAULT),
    ]);

    $db->commit();
    echo "Comptes de démonstration créés ou actualisés.\n";
} catch (Throwable $exception) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log((string) $exception);
    fwrite(STDERR, "Impossible de créer les comptes de démonstration. Vérifiez la base de données.\n");
    exit(1);
}
