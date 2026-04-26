-- ============================================================
-- Base de données : gestion_personnel
-- Web Service PHP / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS gestion_personnel
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE gestion_personnel;

-- ------------------------------------------------------------
-- Table : postes
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS postes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle     VARCHAR(100) NOT NULL UNIQUE,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : personnel
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS personnel (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL,
    prenom     VARCHAR(100) NOT NULL,
    poste_id   INT UNSIGNED NOT NULL,
    actif      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_personnel_poste FOREIGN KEY (poste_id)
        REFERENCES postes(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : motifs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS motifs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    libelle     VARCHAR(200) NOT NULL UNIQUE,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : conges
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS conges (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    personnel_id  INT UNSIGNED NOT NULL,
    motif_id      INT UNSIGNED DEFAULT NULL,
    type_conge    ENUM('annuel','maladie','maternite','paternite','sans_solde','autre') NOT NULL DEFAULT 'annuel',
    date_debut    DATE         NOT NULL,
    date_fin      DATE         NOT NULL,
    nb_jours      SMALLINT     GENERATED ALWAYS AS (DATEDIFF(date_fin, date_debut) + 1) STORED,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_conges_personnel FOREIGN KEY (personnel_id)
        REFERENCES personnel(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_conges_motif FOREIGN KEY (motif_id)
        REFERENCES motifs(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_dates CHECK (date_fin >= date_debut)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : api_keys  (gestion des clés d'accès)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_keys (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    api_key     VARCHAR(64)  NOT NULL UNIQUE,
    actif       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table : admin_users  (connexion back-office)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin_users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(80)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    actif         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Données initiales : une clé API par défaut
-- Remplacez la valeur ci-dessous par votre propre clé sécurisée
-- ------------------------------------------------------------
INSERT INTO api_keys (nom, api_key) VALUES
    ('admin', 'CHANGE_ON_INSTALL_CLE_API_SECRETE_256BITS_OU_PLUS');

-- ------------------------------------------------------------
-- Données de démonstration
-- ------------------------------------------------------------
INSERT INTO postes (libelle) VALUES
    ('Developpeur Senior'),
    ('Dentiste'),
    ('Administrateur');

INSERT INTO personnel (nom, prenom, poste_id) VALUES
    ('Chautard',  'Patrick',  1),
    ('Chautard',  'Coraline', 2),
    ('XX', 'Camille', 2),
    ('YY', 'Lola', 2),
    ('ZZ', 'Jean-Charles', 3);

INSERT INTO motifs (libelle) VALUES
    ('Vacances d''été'),
    ('Arrêt maladie'),
    ('Vacances');

INSERT INTO conges (personnel_id, motif_id, type_conge, date_debut, date_fin) VALUES
    (1, 1, 'annuel',  '2026-07-01', '2026-07-15'),
    (2, 2, 'maladie', '2026-04-10', '2026-04-12'),
    (3, 3, 'annuel',  '2026-08-01', '2026-08-20');
