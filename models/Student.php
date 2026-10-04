<?php
declare(strict_types=1);

final class Student
{
    public function __construct(private PDO $db)
    {
    }

    public function authenticate(string $login, string $password): ?array
    {
        $statement = $this->db->prepare(
            'SELECT idEtudiant, nom, password FROM etudiants WHERE login = :login OR email = :email LIMIT 1'
        );
        $statement->execute(['login' => $login, 'email' => $login]);
        $student = $statement->fetch();
        return $student && password_verify($password, $student['password']) ? $student : null;
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare(
            'SELECT idEtudiant, cin, nom, email, tel, login FROM etudiants WHERE idEtudiant = :id'
        );
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function updateProfile(int $id, array $data): void
    {
        $statement = $this->db->prepare(
            'UPDATE etudiants SET cin = :cin, nom = :nom, email = :email, tel = :tel WHERE idEtudiant = :id'
        );
        $statement->execute([
            'cin' => $data['cin'],
            'nom' => $data['nom'],
            'email' => $data['email'],
            'tel' => $data['tel'],
            'id' => $id,
        ]);
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT idEtudiant, cin, nom, email, tel, login FROM etudiants ORDER BY nom'
        )->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM etudiants')->fetchColumn();
    }
}
