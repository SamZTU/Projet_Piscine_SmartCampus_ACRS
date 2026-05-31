CREATE DATABASE IF NOT EXISTS smartcampus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcampus;

CREATE TABLE users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    prenom      VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,  -- stocké en hash (password_hash)
    role        ENUM('etudiant','enseignant','admin') NOT NULL DEFAULT 'etudiant',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. ETUDIANTS (infos académiques liées à un user)
-- ============================================================
CREATE TABLE etudiants (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL UNIQUE,
    numero_etudiant   VARCHAR(20) NOT NULL UNIQUE,
    filiere           VARCHAR(100),
    niveau            VARCHAR(20),       -- ex: L1, L2, L3, M1, M2
    date_naissance    DATE,
    telephone         VARCHAR(20),
    adresse           VARCHAR(255),
    annee_academique  VARCHAR(9),        -- ex: 2025-2026
    statut            ENUM('actif','inactif','suspendu') DEFAULT 'actif',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 3. ENSEIGNANTS
-- ============================================================
CREATE TABLE enseignants (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL UNIQUE,
    departement  VARCHAR(100),
    grade        VARCHAR(100),           -- ex: Professeur, Maître de conférences
    telephone    VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 4. COURS
-- ============================================================
CREATE TABLE cours (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    code           VARCHAR(20) NOT NULL UNIQUE,    -- ex: INFO101
    nom            VARCHAR(150) NOT NULL,
    description    TEXT,
    credits        INT DEFAULT 3,
    coefficient    FLOAT DEFAULT 1.0,
    capacite_max   INT DEFAULT 30,
    semestre       ENUM('S1','S2','S3','S4','S5','S6') DEFAULT 'S1',
    niveau         VARCHAR(20),
    departement    VARCHAR(100),
    enseignant_id  INT,                            -- enseignant responsable
    actif          TINYINT(1) DEFAULT 1,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enseignant_id) REFERENCES enseignants(id) ON DELETE SET NULL
);

-- ============================================================
-- 5. INSCRIPTIONS (étudiant <-> cours)
-- ============================================================
CREATE TABLE inscriptions (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id      INT NOT NULL,
    cours_id         INT NOT NULL,
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut           ENUM('actif','abandonne','valide') DEFAULT 'actif',
    UNIQUE KEY unique_inscription (etudiant_id, cours_id),  -- règle métier : pas de double inscription
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (cours_id)    REFERENCES cours(id) ON DELETE CASCADE
);

-- ============================================================
-- 6. NOTES
-- ============================================================
CREATE TABLE notes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id   INT NOT NULL,
    cours_id      INT NOT NULL,
    cc1           FLOAT,                  -- contrôle continu 1
    cc2           FLOAT,                  -- contrôle continu 2
    examen_final  FLOAT,                  -- note d'examen
    moyenne       FLOAT,                  -- calculée automatiquement
    resultat      ENUM('admis','ajourne','en_attente') DEFAULT 'en_attente',
    verrouille    TINYINT(1) DEFAULT 0,   -- règle métier : verrouillage après validation
    updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_note (etudiant_id, cours_id),
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (cours_id)    REFERENCES cours(id) ON DELETE CASCADE
);

-- ============================================================
-- 7. EMPLOI DU TEMPS
-- ============================================================
CREATE TABLE emploi_du_temps (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    cours_id     INT NOT NULL,
    jour         ENUM('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi') NOT NULL,
    heure_debut  TIME NOT NULL,
    heure_fin    TIME NOT NULL,
    salle        VARCHAR(50),
    semestre     ENUM('S1','S2','S3','S4','S5','S6') DEFAULT 'S1',
    FOREIGN KEY (cours_id) REFERENCES cours(id) ON DELETE CASCADE
);

-- ============================================================
-- 8. PRESENCES (optionnel mais recommandé)
-- ============================================================
CREATE TABLE presences (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    etudiant_id  INT NOT NULL,
    cours_id     INT NOT NULL,
    date_seance  DATE NOT NULL,
    statut       ENUM('present','absent','retard','justifie') DEFAULT 'absent',
    remarque     VARCHAR(255),
    FOREIGN KEY (etudiant_id) REFERENCES etudiants(id) ON DELETE CASCADE,
    FOREIGN KEY (cours_id)    REFERENCES cours(id) ON DELETE CASCADE
);

-- ============================================================
-- 9. NOTIFICATIONS (optionnel)
-- ============================================================
CREATE TABLE notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    titre       VARCHAR(150),
    message     TEXT,
    type        ENUM('note','absence','cours','systeme') DEFAULT 'systeme',
    lu          TINYINT(1) DEFAULT 0,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 10. MESSAGES (optionnel)
-- ============================================================
CREATE TABLE messages (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    expediteur_id   INT NOT NULL,
    destinataire_id INT NOT NULL,
    sujet           VARCHAR(200),
    contenu         TEXT,
    lu              TINYINT(1) DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expediteur_id)   REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_id) REFERENCES users(id) ON DELETE CASCADE
);


-- ============================================================
-- DONNÉES DE TEST
-- Mots de passe : tous "password123" hashés avec password_hash()
-- Hash utilisé : $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================

-- Admin
INSERT INTO users (nom, prenom, email, password, role) VALUES
('Admin', 'Super', 'admin@smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Enseignants
INSERT INTO users (nom, prenom, email, password, role) VALUES
('Dubois', 'Pierre', 'p.dubois@smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant'),
('Bernard', 'Marie', 'm.bernard@smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant'),
('Martin', 'Julien', 'j.martin@smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'enseignant');

INSERT INTO enseignants (user_id, departement, grade, telephone) VALUES
(2, 'Informatique', 'Professeur', '0601020304'),
(3, 'Mathématiques', 'Maître de conférences', '0602030405'),
(4, 'Physique', 'Maître de conférences', '0603040506');

-- Étudiants
INSERT INTO users (nom, prenom, email, password, role) VALUES
('Martin', 'Emma', 'emma.martin@etu.smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant'),
('Dupont', 'Lucas', 'lucas.dupont@etu.smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant'),
('Leroy', 'Sarah', 'sarah.leroy@etu.smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant'),
('Moreau', 'Thomas', 'thomas.moreau@etu.smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant'),
('Petit', 'Camille', 'camille.petit@etu.smartcampus.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'etudiant');

INSERT INTO etudiants (user_id, numero_etudiant, filiere, niveau, date_naissance, annee_academique) VALUES
(5, 'E20230001', 'Informatique', 'L2', '2002-03-12', '2025-2026'),
(6, 'E20230002', 'Informatique', 'L2', '2003-07-22', '2025-2026'),
(7, 'E20230003', 'Mathématiques', 'L2', '2002-11-05', '2025-2026'),
(8, 'E20230004', 'Informatique', 'L3', '2001-04-18', '2025-2026'),
(9, 'E20230005', 'Physique', 'L1', '2004-01-30', '2025-2026');

-- Cours
INSERT INTO cours (code, nom, description, credits, coefficient, capacite_max, semestre, niveau, departement, enseignant_id) VALUES
('INFO101', 'Algorithmique avancée', 'Structures de données, algorithmes de tri et de recherche', 5, 1.5, 30, 'S1', 'L2', 'Informatique', 1),
('INFO102', 'Bases de données', 'Modélisation et requêtes SQL, MySQL', 4, 1.0, 30, 'S1', 'L2', 'Informatique', 1),
('INFO103', 'Projet Web', 'Développement web dynamique PHP/React', 4, 1.0, 25, 'S2', 'L2', 'Informatique', 1),
('MATH201', 'Algèbre linéaire', 'Espaces vectoriels, applications linéaires', 4, 1.0, 35, 'S1', 'L2', 'Mathématiques', 2),
('PHYS101', 'Physique générale', 'Mécanique, électromagnétisme', 3, 1.0, 40, 'S1', 'L1', 'Physique', 3),
('MATH101', 'Analyse', 'Dérivées, intégrales, suites et séries', 4, 1.0, 35, 'S1', 'L1', 'Mathématiques', 2);

-- Emploi du temps
INSERT INTO emploi_du_temps (cours_id, jour, heure_debut, heure_fin, salle, semestre) VALUES
(1, 'Lundi',    '08:00', '10:00', 'S201', 'S1'),
(2, 'Mardi',    '10:45', '12:45', 'B105', 'S1'),
(3, 'Mercredi', '14:00', '16:00', 'Lab1', 'S2'),
(4, 'Jeudi',    '08:00', '10:00', 'S301', 'S1'),
(5, 'Vendredi', '10:00', '12:00', 'B102', 'S1'),
(6, 'Lundi',    '14:00', '16:00', 'S302', 'S1');

-- Inscriptions (Emma inscrite à INFO101, INFO102, MATH201)
INSERT INTO inscriptions (etudiant_id, cours_id) VALUES
(1, 1), (1, 2), (1, 4),
(2, 1), (2, 2), (2, 3),
(3, 4), (3, 6),
(4, 1), (4, 3),
(5, 5), (5, 6);

-- Notes
INSERT INTO notes (etudiant_id, cours_id, cc1, cc2, examen_final, moyenne, resultat) VALUES
(1, 1, 16.5, 15.0, 17.0, 16.5, 'admis'),
(1, 2, 14.0, 13.5, 15.0, 14.5, 'admis'),
(1, 4, 18.0, 17.5, 16.0, 17.0, 'admis'),
(2, 1, 12.5, 11.0, 10.5, 11.5, 'ajourne'),
(2, 2, 15.0, 16.5, 14.0, 15.0, 'admis'),
(3, 4, 19.0, 18.5, 17.0, 18.0, 'admis'),
(4, 1, 9.5,  11.0,  8.0,  9.5, 'ajourne');

-- Notifications
INSERT INTO notifications (user_id, titre, message, type) VALUES
(5, 'Note publiée', 'Votre note en Algorithmique avancée est disponible : 16.5/20', 'note'),
(5, 'Absence enregistrée', 'Une absence a été enregistrée en Physique le 15 Mai', 'absence'),
(2, 'Notes à saisir', '5 étudiants attendent leurs notes pour Algorithmique avancée', 'cours');

-- Messages
INSERT INTO messages (expediteur_id, destinataire_id, sujet, contenu) VALUES
(2, 5, 'Rapport Projet Web', 'Bonjour Emma, merci de rendre le rapport avant le 20 Mai.'),
(1, 2, 'Réunion pédagogique', 'Réunion pédagogique le 20 Mai à 14h. Voir les détails.');
