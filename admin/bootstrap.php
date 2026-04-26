<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

function db(): PDO
{
    $pdo = Database::getInstance();

    // Crée la table des comptes admin si elle n'existe pas encore.
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(80) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );

    // Migration legacy : transforme personnel.poste (texte) en personnel.poste_id (FK vers postes).
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS postes (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            libelle VARCHAR(100) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB'
    );

    $hasPosteId = (bool)$pdo->query("SHOW COLUMNS FROM personnel LIKE 'poste_id'")->fetch();
    if (!$hasPosteId) {
        $pdo->exec('ALTER TABLE personnel ADD COLUMN poste_id INT UNSIGNED NULL AFTER prenom');
    }

    $hasPosteText = (bool)$pdo->query("SHOW COLUMNS FROM personnel LIKE 'poste'")->fetch();
    if ($hasPosteText) {
        $pdo->exec("INSERT IGNORE INTO postes (libelle) SELECT DISTINCT TRIM(poste) FROM personnel WHERE poste IS NOT NULL AND TRIM(poste) <> ''");
        $pdo->exec('UPDATE personnel p JOIN postes s ON s.libelle = TRIM(p.poste) SET p.poste_id = s.id WHERE p.poste_id IS NULL');
    }

    $defaultPoste = $pdo->prepare('SELECT id FROM postes WHERE libelle = :libelle LIMIT 1');
    $defaultPoste->execute([':libelle' => 'Non defini']);
    $defaultPosteId = $defaultPoste->fetchColumn();
    if ($defaultPosteId === false) {
        $pdo->prepare('INSERT INTO postes (libelle) VALUES (:libelle)')->execute([':libelle' => 'Non defini']);
        $defaultPosteId = (int)$pdo->lastInsertId();
    } else {
        $defaultPosteId = (int)$defaultPosteId;
    }

    $fixNullPoste = $pdo->prepare('UPDATE personnel SET poste_id = :poste_id WHERE poste_id IS NULL');
    $fixNullPoste->execute([':poste_id' => $defaultPosteId]);

    $fkExistsStmt = $pdo->prepare(
        'SELECT COUNT(*)
         FROM information_schema.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND CONSTRAINT_NAME = :constraint_name'
    );
    $fkExistsStmt->execute([
        ':table_name' => 'personnel',
        ':constraint_name' => 'fk_personnel_poste',
    ]);
    $fkExists = (int)$fkExistsStmt->fetchColumn() > 0;

    if (!$fkExists) {
        $indexExistsStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :table_name
               AND INDEX_NAME = :index_name'
        );
        $indexExistsStmt->execute([
            ':table_name' => 'personnel',
            ':index_name' => 'idx_personnel_poste_id',
        ]);
        $indexExists = (int)$indexExistsStmt->fetchColumn() > 0;

        if (!$indexExists) {
            $pdo->exec('ALTER TABLE personnel ADD INDEX idx_personnel_poste_id (poste_id)');
        }
        $pdo->exec('ALTER TABLE personnel ADD CONSTRAINT fk_personnel_poste FOREIGN KEY (poste_id) REFERENCES postes(id) ON UPDATE CASCADE');
    }

    $hasPosteText = (bool)$pdo->query("SHOW COLUMNS FROM personnel LIKE 'poste'")->fetch();
    if ($hasPosteText) {
        $pdo->exec('ALTER TABLE personnel DROP COLUMN poste');
    }

    $isNullablePosteId = $pdo->query("SHOW COLUMNS FROM personnel LIKE 'poste_id'")->fetch();
    if (is_array($isNullablePosteId) && (($isNullablePosteId['Null'] ?? '') === 'YES')) {
        $pdo->exec('ALTER TABLE personnel MODIFY poste_id INT UNSIGNED NOT NULL');
    }

    return $pdo;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function isLoggedIn(): bool
{
    return isset($_SESSION['admin_user_id']) && is_int($_SESSION['admin_user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function flash(?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash_message'] = $message;
        return null;
    }

    if (!isset($_SESSION['flash_message'])) {
        return null;
    }

    $msg = (string)$_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
    return $msg;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrfToken(), $token)) {
        http_response_code(419);
        exit('Token CSRF invalide.');
    }
}
