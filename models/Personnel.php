<?php
/**
 * Modèle Personnel — opérations CRUD
 */

require_once __DIR__ . '/../config/Database.php';

class Personnel
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
            'SELECT pe.id, pe.nom, pe.prenom, pe.poste_id, po.libelle AS poste, pe.actif, pe.created_at, pe.updated_at
             FROM personnel pe
             JOIN postes po ON po.id = pe.poste_id
             ORDER BY nom, prenom'
        );
        return $stmt->fetchAll();
    }

    public function trouverParId(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT pe.id, pe.nom, pe.prenom, pe.poste_id, po.libelle AS poste, pe.actif, pe.created_at, pe.updated_at
             FROM personnel pe
             JOIN postes po ON po.id = pe.poste_id
             WHERE pe.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // ─── Création ──────────────────────────────────────────────────────────────

    public function creer(array $donnees): int
    {
        $this->valider($donnees);

        $posteId = $this->resoudrePosteId($donnees);

        $stmt = $this->db->prepare(
            'INSERT INTO personnel (nom, prenom, poste_id, actif)
             VALUES (:nom, :prenom, :poste_id, :actif)'
        );

        $stmt->execute([
            ':nom'   => trim($donnees['nom']),
            ':prenom' => trim($donnees['prenom']),
            ':poste_id' => $posteId,
            ':actif' => isset($donnees['actif']) ? (int)(bool)$donnees['actif'] : 1,
        ]);

        return (int)$this->db->lastInsertId();
    }

    // ─── Modification ──────────────────────────────────────────────────────────

    public function modifier(int $id, array $donnees): int
    {
        $this->valider($donnees, false);

        $champs    = [];
        $parametres = [':id' => $id];

        $mapping = [
            'nom'    => ':nom',
            'prenom' => ':prenom',
            'poste_id'  => ':poste_id',
            'actif'  => ':actif',
        ];

        if (array_key_exists('poste', $donnees) && !array_key_exists('poste_id', $donnees)) {
            $donnees['poste_id'] = $this->resoudrePosteId($donnees);
        }

        foreach ($mapping as $champ => $param) {
            if (array_key_exists($champ, $donnees)) {
                $champs[]           = "$champ = $param";
                $valeur             = $donnees[$champ];
                if ($champ === 'actif') $valeur = (int)(bool)$valeur;
                if ($champ === 'poste_id') $valeur = (int)$valeur;
                $parametres[$param] = $valeur;
            }
        }

        if (empty($champs)) {
            return 0;
        }

        $sql  = 'UPDATE personnel SET ' . implode(', ', $champs) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametres);

        return $stmt->rowCount();
    }

    // ─── Suppression ──────────────────────────────────────────────────────────

    public function supprimer(int $id): int
    {
        $stmt = $this->db->prepare('DELETE FROM personnel WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    // ─── Validation interne ───────────────────────────────────────────────────

    private function valider(array $d, bool $creation = true): void
    {
        $erreurs = [];

        if ($creation) {
            if (empty($d['nom']))    $erreurs[] = 'Le nom est obligatoire.';
            if (empty($d['prenom'])) $erreurs[] = 'Le prénom est obligatoire.';
            if (!isset($d['poste_id']) && empty($d['poste'])) $erreurs[] = 'Le poste est obligatoire.';
        }

        if (isset($d['poste_id']) && !$this->posteExiste((int)$d['poste_id'])) {
            $erreurs[] = 'Le poste sélectionné est invalide.';
        }

        if (!empty($erreurs)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erreurs' => $erreurs]);
            exit;
        }
    }

    private function posteExiste(int $posteId): bool
    {
        if ($posteId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT 1 FROM postes WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $posteId]);
        return (bool)$stmt->fetchColumn();
    }

    private function resoudrePosteId(array $donnees): int
    {
        if (isset($donnees['poste_id'])) {
            return (int)$donnees['poste_id'];
        }

        $libelle = trim((string)($donnees['poste'] ?? ''));
        if ($libelle === '') {
            return 0;
        }

        $stmt = $this->db->prepare('SELECT id FROM postes WHERE libelle = :libelle LIMIT 1');
        $stmt->execute([':libelle' => $libelle]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int)$id;
        }

        $create = $this->db->prepare('INSERT INTO postes (libelle) VALUES (:libelle)');
        $create->execute([':libelle' => $libelle]);
        return (int)$this->db->lastInsertId();
    }

}

