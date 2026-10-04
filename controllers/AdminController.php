<?php
declare(strict_types=1);

final class AdminController
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
        switch ($action) {
            case 'admin-course-save':
                $this->saveCourse();
                return;
            case 'admin-course-delete':
                $id = $this->postId('course_id');
                $this->courses->delete($id);
                setFlash('success', 'Le cours a été supprimé.');
                redirect('admin-courses');
            case 'admin-registration-confirm':
                $this->registrations->setStatus($this->postId('registration_id'), 'confirmé');
                setFlash('success', 'L’inscription a été confirmée.');
                redirect('admin-registrations');
            case 'admin-registration-cancel':
                $this->registrations->setStatus($this->postId('registration_id'), 'annulé');
                setFlash('success', 'L’inscription a été annulée.');
                redirect('admin-registrations');
            case 'admin-registration-paid':
                $this->registrations->markPaid($this->postId('registration_id'));
                setFlash('success', 'Le paiement a été enregistré.');
                redirect('admin-registrations');
            default:
                throw new RuntimeException('Action administration inconnue.');
        }
    }

    public function dashboard(): array
    {
        return [
            'view' => 'admin/dashboard',
            'title' => 'Tableau de bord',
            'stats' => [
                'students' => $this->students->count(),
                'courses' => $this->courses->count(),
                'registrations' => $this->registrations->count(),
                'paid' => $this->registrations->paidCount(),
            ],
        ];
    }

    public function courses(): array
    {
        $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
        $editing = $editId ? $this->courses->find($editId) : null;
        if ($editId && !$editing) {
            setFlash('warning', 'Le cours demandé est introuvable.');
        }
        return [
            'view' => 'admin/courses',
            'title' => 'Gestion des cours',
            'courses' => $this->courses->all(),
            'editing' => $editing,
        ];
    }

    public function students(): array
    {
        return ['view' => 'admin/students', 'title' => 'Étudiants', 'students' => $this->students->all()];
    }

    public function registrations(bool $pendingOnly = false): array
    {
        return [
            'view' => 'admin/registrations',
            'title' => $pendingOnly ? 'Inscriptions en attente' : 'Toutes les inscriptions',
            'registrations' => $pendingOnly ? $this->registrations->pending() : $this->registrations->all(),
            'pendingOnly' => $pendingOnly,
        ];
    }

    private function saveCourse(): void
    {
        $id = postString('course_id') !== ''
            ? filter_var(postString('course_id'), FILTER_VALIDATE_INT)
            : null;
        if (postString('course_id') !== '' && (!$id || $id < 1)) {
            throw new DomainException('Cours invalide.');
        }

        $data = [
            'langue' => trim(postString('langue')),
            'niveau' => trim(postString('niveau')),
            'prix' => filter_var(postString('prix'), FILTER_VALIDATE_FLOAT),
            'dateDebut' => trim(postString('dateDebut')),
            'dateFin' => trim(postString('dateFin')),
            'places_total' => filter_var(postString('places_total'), FILTER_VALIDATE_INT),
        ];
        if ($data['langue'] === '' || strlen($data['langue']) > 80 ||
            $data['niveau'] === '' || strlen($data['niveau']) > 80 ||
            $data['prix'] === false || $data['prix'] < 0 || $data['prix'] > 99999999.99 ||
            !$this->validDate($data['dateDebut']) || !$this->validDate($data['dateFin']) ||
            $data['dateFin'] < $data['dateDebut'] ||
            $data['places_total'] === false || $data['places_total'] < 1 || $data['places_total'] > 100000) {
            throw new DomainException('Vérifiez les informations du cours. Les dates doivent être valides et la date de fin postérieure ou égale au début.');
        }
        $data['prix'] = number_format((float) $data['prix'], 2, '.', '');
        $data['places_total'] = (int) $data['places_total'];
        if ($id) {
            $this->courses->update($id, $data);
            setFlash('success', 'Le cours a été modifié.');
        } else {
            $data['places_restantes'] = $data['places_total'];
            $this->courses->create($data);
            setFlash('success', 'Le cours a été créé.');
        }
        redirect('admin-courses');
    }

    private function postId(string $key): int
    {
        $id = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            throw new DomainException('Identifiant invalide.');
        }
        return $id;
    }

    private function validDate(string $date): bool
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
