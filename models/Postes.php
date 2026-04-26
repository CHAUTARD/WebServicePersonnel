<?php
/**
 * Modele Postes — operations CRUD
 */

require_once __DIR__ . '/../config/Database.php';

class Postes
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function listerTous(): array
    {
        $stmt = $this->db->query('SELECT id, libelle, created_at FROM postes ORDER BY libelle');
        return $stmt->fetchAll();
    }

    public function trouverParId(int $id): array|false
    {
        $stmt = $this->db->prepare('SELECT id, libelle, created_at FROM postes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function creer(array $donnees): int
    {
        $this->valider($donnees);

        $stmt = $this->db->prepare('INSERT INTO postes (libelle) VALUES (:libelle)');
        $stmt->execute([':libelle' => trim((string)$donnees['libelle'])]);

        return (int)$this->db->lastInsertId();
    }

    public function modifier(int $id, array $donnees): int
    {
        $this->valider($donnees);

        $stmt = $this->db->prepare('UPDATE postes SET libelle = :libelle WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':libelle' => trim((string)$donnees['libelle']),
        ]);

        return $stmt->rowCount();
    }

    public function supprimer(int $id): int
    {
        $stmt = $this->db->prepare('DELETE FROM postes WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    private function valider(array $d): void
    {
        if (empty(trim((string)($d['libelle'] ?? '')))) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erreurs' => ['Le libelle du poste est obligatoire.']]);
            exit;
        }
    }
}
