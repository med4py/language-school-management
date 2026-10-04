<?php
declare(strict_types=1);

final class Registration
{
    public function __construct(private PDO $db)
    {
    }

    public function forStudent(int $studentId): array
    {
        $statement = $this->db->prepare(
            'SELECT i.*, c.langue, c.niveau, c.dateDebut
             FROM inscriptions i JOIN cours c ON c.idCours = i.idCours
             WHERE i.idEtudiant = :student ORDER BY i.dateInscription DESC'
        );
        $statement->execute(['student' => $studentId]);
        return $statement->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query(
            'SELECT i.*, e.nom AS etudiant_nom, e.cin, c.langue, c.niveau
             FROM inscriptions i
             JOIN etudiants e ON e.idEtudiant = i.idEtudiant
             JOIN cours c ON c.idCours = i.idCours
             ORDER BY i.dateInscription DESC'
        )->fetchAll();
    }

    public function pending(): array
    {
        return $this->db->query(
            "SELECT i.*, e.nom AS etudiant_nom, e.cin, c.langue, c.niveau
             FROM inscriptions i
             JOIN etudiants e ON e.idEtudiant = i.idEtudiant
             JOIN cours c ON c.idCours = i.idCours
             WHERE i.statut = 'en attente' ORDER BY i.dateInscription DESC"
        )->fetchAll();
    }

    public function enroll(int $studentId, int $courseId): void
    {
        $this->db->beginTransaction();
        try {
            $courseStatement = $this->db->prepare(
                'SELECT places_restantes FROM cours WHERE idCours = :course AND dateDebut >= CURRENT_DATE FOR UPDATE'
            );
            $courseStatement->execute(['course' => $courseId]);
            $course = $courseStatement->fetch();
            if (!$course) {
                throw new DomainException('Le cours sélectionné n’est pas disponible pour inscription.');
            }
            if ((int) $course['places_restantes'] < 1) {
                throw new DomainException('Ce cours est complet.');
            }

            $duplicate = $this->db->prepare(
                'SELECT idInscription FROM inscriptions WHERE idEtudiant = :student AND idCours = :course'
            );
            $duplicate->execute(['student' => $studentId, 'course' => $courseId]);
            if ($duplicate->fetch()) {
                throw new DomainException('Vous avez déjà une inscription pour ce cours.');
            }

            $insert = $this->db->prepare(
                "INSERT INTO inscriptions (idEtudiant, idCours, statut, paiement)
                 VALUES (:student, :course, 'en attente', 'non payé')"
            );
            $insert->execute(['student' => $studentId, 'course' => $courseId]);
            $decrement = $this->db->prepare(
                'UPDATE cours SET places_restantes = places_restantes - 1
                 WHERE idCours = :course AND places_restantes > 0'
            );
            $decrement->execute(['course' => $courseId]);
            if ($decrement->rowCount() !== 1) {
                throw new DomainException('Aucune place n’est actuellement disponible.');
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function deletePending(int $studentId, int $registrationId): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                "SELECT idCours FROM inscriptions
                 WHERE idInscription = :registration AND idEtudiant = :student AND statut = 'en attente' FOR UPDATE"
            );
            $statement->execute(['registration' => $registrationId, 'student' => $studentId]);
            $registration = $statement->fetch();
            if (!$registration) {
                throw new DomainException('Seules vos demandes en attente peuvent être supprimées.');
            }

            $delete = $this->db->prepare(
                "DELETE FROM inscriptions WHERE idInscription = :registration AND idEtudiant = :student AND statut = 'en attente'"
            );
            $delete->execute(['registration' => $registrationId, 'student' => $studentId]);
            $restore = $this->db->prepare(
                'UPDATE cours SET places_restantes = LEAST(places_total, places_restantes + 1) WHERE idCours = :course'
            );
            $restore->execute(['course' => $registration['idCours']]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function setStatus(int $registrationId, string $status): void
    {
        $this->db->beginTransaction();
        try {
            $statement = $this->db->prepare(
                'SELECT idCours, statut FROM inscriptions WHERE idInscription = :id FOR UPDATE'
            );
            $statement->execute(['id' => $registrationId]);
            $registration = $statement->fetch();
            if (!$registration) {
                throw new DomainException('Cette inscription est introuvable.');
            }
            if (!in_array($status, ['confirmé', 'annulé'], true)) {
                throw new DomainException('Statut invalide.');
            }
            if ($status === 'confirmé' && $registration['statut'] !== 'en attente') {
                throw new DomainException('Seules les demandes en attente peuvent être confirmées.');
            }
            if ($status === 'annulé' && $registration['statut'] === 'annulé') {
                throw new DomainException('Cette inscription est déjà annulée.');
            }
            if ($registration['statut'] !== 'annulé' && $status === 'annulé') {
                $restore = $this->db->prepare(
                    'UPDATE cours SET places_restantes = LEAST(places_total, places_restantes + 1) WHERE idCours = :course'
                );
                $restore->execute(['course' => $registration['idCours']]);
            }
            $update = $this->db->prepare('UPDATE inscriptions SET statut = :status WHERE idInscription = :id');
            $update->execute(['status' => $status, 'id' => $registrationId]);
            $this->db->commit();
        } catch (Throwable $exception) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $exception;
        }
    }

    public function markPaid(int $registrationId): void
    {
        $statement = $this->db->prepare(
            "UPDATE inscriptions SET paiement = 'payé' WHERE idInscription = :id"
        );
        $statement->execute(['id' => $registrationId]);
        if ($statement->rowCount() === 0) {
            $exists = $this->db->prepare('SELECT idInscription FROM inscriptions WHERE idInscription = :id');
            $exists->execute(['id' => $registrationId]);
            if (!$exists->fetch()) {
                throw new DomainException('Cette inscription est introuvable.');
            }
        }
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM inscriptions')->fetchColumn();
    }

    public function paidCount(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM inscriptions WHERE paiement = 'payé'")->fetchColumn();
    }
}
