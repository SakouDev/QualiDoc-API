-- =========================================================
-- QualiDoc — Script de création de la base MariaDB
-- =========================================================
-- Usage : mysql -u root -p qualidoc < database/schema.sql
-- (la base "qualidoc" doit exister au préalable :
--  CREATE DATABASE qualidoc CHARACTER SET utf8mb4;)
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================
-- Table : specialites
-- =========================================
DROP TABLE IF EXISTS specialites;
CREATE TABLE specialites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Table : patients
-- =========================================
DROP TABLE IF EXISTS patients;
CREATE TABLE patients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  admin BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Table : medecins
-- Recherche multi-critères optimisée (point 5.3 du sujet) :
-- un champ de recherche unique côté front compare en préfixe
-- (LIKE 'terme%') sur nom, prenom et specialites.nom, donc des
-- index classiques sur nom/prenom suffisent (pas de FULLTEXT :
-- un seul mot par colonne, pas besoin de chercher en plein milieu).
-- =========================================
DROP TABLE IF EXISTS medecins;
CREATE TABLE medecins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(100) NOT NULL,
  prenom VARCHAR(100) NOT NULL,
  specialite_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (specialite_id) REFERENCES specialites(id),
  INDEX idx_medecins_specialite (specialite_id),
  INDEX idx_medecins_nom (nom),
  INDEX idx_medecins_prenom (prenom)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Table : disponibilites
-- Créneaux ponctuels par date (pas de récurrence).
-- Prévue pour le bonus "gestion des créneaux",
-- pas forcément exploitée dès la V1.
-- =========================================
DROP TABLE IF EXISTS disponibilites;
CREATE TABLE disponibilites (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  medecin_id INT UNSIGNED NOT NULL,
  date_dispo DATE NOT NULL,
  heure_debut TIME NOT NULL,
  heure_fin TIME NOT NULL,
  duree_creneau SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  FOREIGN KEY (medecin_id) REFERENCES medecins(id),
  INDEX idx_dispo_medecin_date (medecin_id, date_dispo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================
-- Table : rendez_vous
-- =========================================
DROP TABLE IF EXISTS rendez_vous;
CREATE TABLE rendez_vous (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  patient_id INT UNSIGNED NOT NULL,
  medecin_id INT UNSIGNED NOT NULL,
  date_heure DATETIME NOT NULL,
  statut ENUM('confirme','annule','honore') NOT NULL DEFAULT 'confirme',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (patient_id) REFERENCES patients(id),
  FOREIGN KEY (medecin_id) REFERENCES medecins(id),
  INDEX idx_rdv_patient (patient_id),
  INDEX idx_rdv_medecin_date (medecin_id, date_heure)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- Données de test
-- =========================================================

INSERT INTO specialites (nom) VALUES
  ('Généraliste'),
  ('Dentiste'),
  ('Dermatologue'),
  ('Cardiologue');

INSERT INTO medecins (nom, prenom, specialite_id) VALUES
  ('Durand', 'Jean', 1),
  ('Martin', 'Claire', 2),
  ('Lefevre', 'Paul', 3),
  ('Bernard', 'Sophie', 4);

-- mot de passe en clair pour les 2 comptes de test : "password"
-- hash généré avec password_hash('password', PASSWORD_DEFAULT) côté PHP
INSERT INTO patients (nom, prenom, email, password_hash, admin) VALUES
  ('Dupont', 'Luc', 'luc.dupont@example.com', '$2y$10$92I2VoiG/A2GfjP0Qw2VZOZz1H7v8dq7Ecd7nUq0X8zj3s5N0oW9K', FALSE),
  ('Admin', 'Qualidoc', 'admin@qualidoc.fr', '$2y$10$92I2VoiG/A2GfjP0Qw2VZOZz1H7v8dq7Ecd7nUq0X8zj3s5N0oW9K', TRUE);

INSERT INTO disponibilites (medecin_id, date_dispo, heure_debut, heure_fin, duree_creneau) VALUES
  (1, '2026-09-15', '09:00:00', '12:00:00', 20),
  (2, '2026-09-16', '14:00:00', '18:00:00', 30);

INSERT INTO rendez_vous (patient_id, medecin_id, date_heure, statut) VALUES
  (1, 1, '2026-09-15 09:00:00', 'confirme'),
  (1, 2, '2026-08-20 14:30:00', 'honore');