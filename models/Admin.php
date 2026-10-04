<?php
declare(strict_types=1);

final class Admin
{
    public function __construct(private PDO $db)
    {
    }

    public function authenticate(string $login, string $password): ?array
    {
        $statement = $this->db->prepare(
            'SELECT idAdmin, nom, password FROM admins WHERE login = :login OR email = :email LIMIT 1'
        );
        $statement->execute(['login' => $login, 'email' => $login]);
        $admin = $statement->fetch();
        return $admin && password_verify($password, $admin['password']) ? $admin : null;
    }
}
