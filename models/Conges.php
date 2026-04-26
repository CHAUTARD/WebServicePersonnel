<?php
/**
 * Modèle Conges — opérations CRUD
 */

require_once __DIR__ . '/../config/Database.php';

class Conges
{
    private PDO $db;

    private const TYPES_VALIDES = [
        'annuel', 'maladie', 'maternite', 'paternite', 'sans_solde', 'autre'
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // ─── Lecture ───────────────────────────────────────────────────────────────

    public function listerTous(): array
    {
        $stmt = $this->db->query(
            'SELECT c.id, c.personnel_id,
                    CONCAT(p.nom, \' \', p.prenom) AS personnel_nom,
                    c.motif_id, m.libelle AS motif_libelle,
                    c.type_conge, c.date_debut, c.date_fin, c.nb_jours,
                    c.created_at, c.updated_at
             FROM conges c
             JOIN personnel p ON p.id = c.personnel_id
             LEFT JOIN motifs m ON m.id = c.motif_id
             ORDER BY c.date_debut DESC'
        );
        return $stmt->fetchAll();
    }

    public function trouverParId(int $id): array|false
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.personnel_id,
                    CONCAT(p.nom, \' \', p.prenom) AS personnel_nom,
                    c.motif_id, m.libelle AS motif_libelle,
                    c.type_conge, c.date_debut, c.date_fin, c.nb_jours,
                    c.created_at, c.updated_at
             FROM conges c
             JOIN personnel p ON p.id = c.personnel_id
             LEFT JOIN motifs m ON m.id = c.motif_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function listerParPersonnel(int $personnelId): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, personnel_id, motif_id, type_conge, date_debut, date_fin,
                    nb_jours, created_at, updated_at
             FROM conges
             WHERE personnel_id = :pid
             ORDER BY date_debut DESC'
        );
        $stmt->execute([':pid' => $personnelId]);
        return $stmt->fetchAll();
    }

    // ─── Création ──────────────────────────────────────────────────────────────

    public function creer(array $donnees): int
    {
        $this->valider($donnees);

        $stmt = $this->db->prepare(
            'INSERT INTO conges (personnel_id, motif_id, type_conge, date_debut, date_fin)
             VALUES (:personnel_id, :motif_id, :type_conge, :date_debut, :date_fin)'
        );

        $stmt->execute([
            ':personnel_id' => (int)$donnees['personnel_id'],
            ':motif_id'     => isset($donnees['motif_id']) ? (int)$donnees['motif_id'] : null,
            ':type_conge'   => $donnees['type_conge'],
            ':date_debut'   => $donnees['date_debut'],
            ':date_fin'     => $donnees['date_fin'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Crée un congé annuel pour tout le personnel actif.
     * Retourne le nombre d'enregistrements insérés.
     */
    public function creerPourTous(array $donnees): int
    {
        $this->valider($donnees, false);

        $personnel = $this->db->query(
            'SELECT id FROM personnel WHERE actif = 1'
        )->fetchAll();

        if (empty($personnel)) {
            return 0;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO conges (personnel_id, motif_id, type_conge, date_debut, date_fin)
             VALUES (:personnel_id, :motif_id, :type_conge, :date_debut, :date_fin)'
        );

        $nb = 0;
        foreach ($personnel as $emp) {
            $stmt->execute([
                ':personnel_id' => $emp['id'],
                ':motif_id'     => isset($donnees['motif_id']) ? (int)$donnees['motif_id'] : null,
                ':type_conge'   => 'annuel',
                ':date_debut'   => $donnees['date_debut'],
                ':date_fin'     => $donnees['date_fin'],
            ]);
            $nb++;
        }

        return $nb;
    }

    // ─── Modification ──────────────────────────────────────────────────────────

    public function modifier(int $id, array $donnees): int
    {
        $this->valider($donnees, false);

        $champs     = [];
        $parametres = [':id' => $id];

        $mapping = [
            'personnel_id' => ':personnel_id',
            'motif_id'     => ':motif_id',
            'type_conge'   => ':type_conge',
            'date_debut'   => ':date_debut',
            'date_fin'     => ':date_fin',
        ];

        foreach ($mapping as $champ => $param) {
            if (array_key_exists($champ, $donnees)) {
                $champs[]        = "$champ = $param";
                $parametres[$param] = $donnees[$champ];
            }
        }

        if (empty($champs)) {
            return 0;
        }

        $sql  = 'UPDATE conges SET ' . implode(', ', $champs) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametres);

        return $stmt->rowCount();
    }

    // ─── Suppression ──────────────────────────────────────────────────────────

    public function supprimer(int $id): int
    {
        $stmt = $this->db->prepare('DELETE FROM conges WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount();
    }

    // ─── Validation interne ───────────────────────────────────────────────────

    private function valider(array $d, bool $creation = true): void
    {
        $erreurs = [];

        if ($creation) {
            // Pour un congé annuel sans personnel_id : création collective → pas d'id requis
            $estAnnuelCollectif = (($d['type_conge'] ?? '') === 'annuel') && empty($d['personnel_id']);

            if (!$estAnnuelCollectif && empty($d['personnel_id'])) {
                $erreurs[] = 'L\'identifiant du personnel est obligatoire (sauf pour un congé annuel collectif).';
            }
            if (empty($d['date_debut'])) $erreurs[] = 'La date de début est obligatoire.';
            if (empty($d['date_fin']))   $erreurs[] = 'La date de fin est obligatoire.';
        }

        if (!empty($d['type_conge']) && !in_array($d['type_conge'], self::TYPES_VALIDES, true)) {
            $erreurs[] = 'Type de congé invalide. Valeurs acceptées : ' . implode(', ', self::TYPES_VALIDES);
        }

        if (!empty($d['date_debut']) && !self::dateValide($d['date_debut'])) {
            $erreurs[] = 'La date de début doit être au format YYYY-MM-DD.';
        }

        if (!empty($d['date_fin']) && !self::dateValide($d['date_fin'])) {
            $erreurs[] = 'La date de fin doit être au format YYYY-MM-DD.';
        }

        if (!empty($d['date_debut']) && !empty($d['date_fin']) && $d['date_fin'] < $d['date_debut']) {
            $erreurs[] = 'La date de fin ne peut pas être antérieure à la date de début.';
        }

        if (!empty($erreurs)) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['erreurs' => $erreurs]);
            exit;
        }
    }

    private static function dateValide(string $date): bool
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
