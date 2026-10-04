<?php
declare(strict_types=1);

final class StudentController
{
    public function __construct(
        private PDO $db,
        private Student $students,
        private Course $courses,
        private Registration $registrations
    ) {
    }

    public function handle(string $action): void
    {
        $studentId = (int) $_SESSION['student_id'];
        if ($action === 'student-profile-save') {
            $data = [
                'cin' => trim(postString('cin')),
                'nom' => trim(postString('nom')),
                'email' => trim(postString('email')),
                'tel' => trim(postString('tel')),
            ];
            if ($data['cin'] === '' || strlen($data['cin']) > 20 ||
                $data['nom'] === '' || strlen($data['nom']) > 100 ||
                !filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 150 ||
                strlen($data['tel']) > 30) {
                throw new DomainException('Vérifiez les champs du profil et réessayez.');
            }
            $exists = $this->db->prepare(
                'SELECT idEtudiant FROM etudiants WHERE (cin = :cin OR email = :email) AND idEtudiant <> :id'
            );
            $exists->execute(['cin' => $data['cin'], 'email' => $data['email'], 'id' => $studentId]);
            if ($exists->fetch()) {
                throw new DomainException('Ce CIN ou cette adresse e-mail est déjà utilisé.');
            }
            $this->students->updateProfile($studentId, $data);
            $_SESSION['student_name'] = $data['nom'];
            setFlash('success', 'Votre profil a été mis à jour.');
            redirect('student-profile');
        }

        if ($action === 'student-enroll') {
            $courseId = filter_input(INPUT_POST, 'course_id', FILTER_VALIDATE_INT);
            if (!$courseId || $courseId < 1) {
                throw new DomainException('Cours invalide.');
            }
            $this->registrations->enroll($studentId, $courseId);
            setFlash('success', 'Votre demande d’inscription a été envoyée.');
            redirect('student-registrations');
        }

        if ($action === 'student-registration-delete') {
            $registrationId = filter_input(INPUT_POST, 'registration_id', FILTER_VALIDATE_INT);
            if (!$registrationId || $registrationId < 1) {
                throw new DomainException('Inscription invalide.');
            }
            $this->registrations->deletePending($studentId, $registrationId);
            setFlash('success', 'Votre demande en attente a été supprimée.');
            redirect('student-registrations');
        }

        throw new RuntimeException('Action étudiant inconnue.');
    }

    public function profile(): array
    {
        $student = $this->students->find((int) $_SESSION['student_id']);
        if (!$student) {
            throw new RuntimeException('Compte étudiant introuvable.');
        }
        return ['view' => 'student/profile', 'title' => 'Mon profil', 'student' => $student];
    }

    public function courses(): array
    {
        return ['view' => 'student/courses', 'title' => 'Inscription aux cours', 'courses' => $this->courses->upcoming()];
    }

    public function registrations(): array
    {
        return [
            'view' => 'student/registrations',
            'title' => 'Mes demandes',
            'registrations' => $this->registrations->forStudent((int) $_SESSION['student_id']),
        ];
    }
}
