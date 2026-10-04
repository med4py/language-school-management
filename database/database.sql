CREATE DATABASE IF NOT EXISTS language_school
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE language_school;

CREATE TABLE IF NOT EXISTS etudiants (
    idEtudiant INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cin VARCHAR(20) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    tel VARCHAR(30) NULL,
    login VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cours (
    idCours INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    langue VARCHAR(80) NOT NULL,
    niveau VARCHAR(80) NOT NULL,
    prix DECIMAL(10, 2) NOT NULL,
    dateDebut DATE NOT NULL,
    dateFin DATE NOT NULL,
    places_total INT UNSIGNED NOT NULL,
    places_restantes INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cours_date_debut (dateDebut),
    CONSTRAINT chk_cours_prix CHECK (prix >= 0),
    CONSTRAINT chk_cours_dates CHECK (dateFin >= dateDebut),
    CONSTRAINT chk_cours_places CHECK (places_total >= 1 AND places_restantes <= places_total)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inscriptions (
    idInscription INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idEtudiant INT UNSIGNED NOT NULL,
    idCours INT UNSIGNED NOT NULL,
    dateInscription TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en attente', 'confirmé', 'annulé') NOT NULL DEFAULT 'en attente',
    paiement ENUM('payé', 'non payé') NOT NULL DEFAULT 'non payé',
    UNIQUE KEY uq_etudiant_cours (idEtudiant, idCours),
    INDEX idx_inscriptions_statut_date (statut, dateInscription),
    CONSTRAINT fk_inscription_etudiant
        FOREIGN KEY (idEtudiant) REFERENCES etudiants(idEtudiant)
        ON DELETE CASCADE,
    CONSTRAINT fk_inscription_cours
        FOREIGN KEY (idCours) REFERENCES cours(idCours)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    idAdmin INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    login VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO cours (langue, niveau, prix, dateDebut, dateFin, places_total, places_restantes)
SELECT 'Anglais', 'Débutant', 1000.00, '2026-11-01', '2026-12-01', 20, 20
WHERE NOT EXISTS (SELECT 1 FROM cours WHERE langue = 'Anglais' AND niveau = 'Débutant' AND dateDebut = '2026-11-01');

INSERT INTO cours (langue, niveau, prix, dateDebut, dateFin, places_total, places_restantes)
SELECT 'Français', 'Intermédiaire', 1200.00, '2026-11-10', '2026-12-15', 15, 15
WHERE NOT EXISTS (SELECT 1 FROM cours WHERE langue = 'Français' AND niveau = 'Intermédiaire' AND dateDebut = '2026-11-10');

INSERT INTO cours (langue, niveau, prix, dateDebut, dateFin, places_total, places_restantes)
SELECT 'Espagnol', 'Avancé', 1500.00, '2026-11-20', '2026-12-20', 10, 10
WHERE NOT EXISTS (SELECT 1 FROM cours WHERE langue = 'Espagnol' AND niveau = 'Avancé' AND dateDebut = '2026-11-20');
