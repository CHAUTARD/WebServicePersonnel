<?php
/**
 * Modèle Motifs — opérations CRUD
 */

require_once __DIR__ . '/../config/Database.php';

class Motifs
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Lecture ───────────────────────────────────────────────────────────────

    public function listerTous(): array
    {
        $stmt = $this->db->query(
            'SELECT id, libelle, created_at FROM motifs ORDER BY libelle'
        );
        return $stmt->fetchAll();
    }

    public function trouverParId(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT id, libelle, created_at FROM motifs WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ─── Création ──────────────────────────────────────────────────────────────

    public function creer(array $donnees): int
    {
        $this->valider($donnees);

        $stmt = $this->db->prepare(
            'INSERT INTO motifs (libelle) VALUES (:libelle)'
        );
        $stmt->execute([':libelle' => trim($donnees['libelle'])]);

        return (int)$this->db->lastInsertId();
    }

    // ─── Modification ──────────────────────────────────────────────────────────

    public function modifier(int $id, array $donnees): int
    {
        $this->valider($donnees);

        $stmt = $this->db->prepare(
            'UPDATE motifs SET libelle = :libelle WHERE id = :id'
        );
        $stmt->execute([
            ':libelle' => trim($donnees['libelle']),
            ':id'      => $id,
        ]);

        return $stmt->rowCount();
    }

    // ─── Suppression ──────────────────────────────────────────────────────────

    public function supprimer(int $id): int
    {
        $stmt = $this->db->prepare('DELETE FROM motifs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    // ─── Validation interne ───────────────────────────────────────────────────

    private function valider(array $d): void
    {
        if (empty($d['libelle'])) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erreurs' => ['Le libellé du motif est obligatoire.']]);
            exit;
        }
    }
}
