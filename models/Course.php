<?php
declare(strict_types=1);

final class Course
{
    public function __construct(private PDO $db)
    {
    }

    public function upcoming(): array
    {
        return $this->db->query(
            'SELECT * FROM cours WHERE dateDebut >= CURRENT_DATE ORDER BY dateDebut DESC'
        )->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM cours ORDER BY dateDebut DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->db->prepare('SELECT * FROM cours WHERE idCours = :id');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function create(array $data): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO cours (langue, niveau, prix, dateDebut, dateFin, places_total, places_restantes)
             VALUES (:langue, :niveau, :prix, :dateDebut, :dateFin, :places_total, :places_restantes)'
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare('SELECT places_total, places_restantes FROM cours WHERE idCours = :id FOR UPDATE');
            $statement->execute(['id' => $id]);
            $current = $statement->fetch();
            if (!$current) {
                throw new RuntimeException('Ce cours n’existe plus.');
            }

            $reserved = (int) $current['places_total'] - (int) $current['places_restantes'];
            if ($data['places_total'] < $reserved) {
                throw new DomainException('Le nombre total de places ne peut pas être inférieur aux places déjà réservées.');
            }
            $data['places_restantes'] = $data['places_total'] - $reserved;
            $data['id'] = $id;
            $update = $this->db->prepare(
                'UPDATE cours SET langue = :langue, niveau = :niveau, prix = :prix, dateDebut = :dateDebut,
                 dateFin = :dateFin, places_total = :places_total, places_restantes = :places_restantes WHERE idCours = :id'
            );
            $update->execute($data);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $statement = $this->db->prepare('DELETE FROM cours WHERE idCours = :id');
        $statement->execute(['id' => $id]);
        if ($statement->rowCount() !== 1) {
            throw new DomainException('Ce cours est introuvable.');
        }
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM cours')->fetchColumn();
    }
}
