<?php
/**
 * Point d'entrée unique du Web Service
 *
 * Routes disponibles :
 *   GET    /personnel              → liste tout le personnel
 *   GET    /personnel/{id}         → détail d'un employé
 *   POST   /personnel              → créer un employé
 *   PUT    /personnel/{id}         → modifier un employé
 *   DELETE /personnel/{id}         → supprimer un employé
 *
 *   GET    /conges                 → liste tous les congés
 *   GET    /conges/{id}            → détail d'un congé
 *   GET    /conges/personnel/{id}  → congés d'un employé
 *   POST   /conges                 → créer un congé (si type_conge=annuel sans personnel_id → tout le personnel actif)
 *   PUT    /conges/{id}            → modifier un congé
 *   DELETE /conges/{id}            → supprimer un congé
 *
 *   GET    /motifs                 → liste tous les motifs
 *   GET    /motifs/{id}            → détail d'un motif
 *   POST   /motifs                 → créer un motif
 *   PUT    /motifs/{id}            → modifier un motif
 *   DELETE /motifs/{id}            → supprimer un motif
 *
 *   GET    /postes                 → liste tous les postes
 *   GET    /postes/{id}            → détail d'un poste
 *   POST   /postes                 → créer un poste
 *   PUT    /postes/{id}            → modifier un poste
 *   DELETE /postes/{id}            → supprimer un poste
 *
 * Authentification :
 *   En-tête  X-API-Key: <votre_clé>
 *   ou       Authorization: Bearer <votre_clé>
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/middleware/Auth.php';
require_once __DIR__ . '/models/Personnel.php';
require_once __DIR__ . '/models/Conges.php';
require_once __DIR__ . '/models/Motifs.php';
require_once __DIR__ . '/models/Postes.php';

// ── En-têtes globaux ───────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: '  . CORS_ORIGIN);
header('Access-Control-Allow-Methods: ' . CORS_METHODS);
header('Access-Control-Allow-Headers: ' . CORS_HEADERS);

// Réponse immédiate aux pre-flight CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Authentification ───────────────────────────────────────────────────────────
Auth::verifier();

// ── Parsing de la route ────────────────────────────────────────────────────────
$methode = $_SERVER['REQUEST_METHOD'];
$uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri     = '/' . trim($uri, '/');

// Normalise le chemin de base si le service est dans un sous-dossier
$base = str_replace(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '', str_replace('\\', '/', __DIR__));
if ($base !== '' && str_starts_with($uri, $base)) {
    $uri = substr($uri, strlen($base));
}
$uri = '/' . ltrim($uri, '/');

$segments = explode('/', trim($uri, '/'));  // ex: ['personnel', '3']
$ressource = $segments[0] ?? '';
$id        = isset($segments[1]) && ctype_digit($segments[1]) ? (int)$segments[1] : null;

// ── Corps de la requête ────────────────────────────────────────────────────────
$corps = [];
if (in_array($methode, ['POST', 'PUT', 'PATCH'], true)) {
    $brut = file_get_contents('php://input');
    $corps = json_decode($brut, true) ?? [];
}

// ── Routage ────────────────────────────────────────────────────────────────────
try {
    match ($ressource) {
        'personnel' => routerPersonnel($methode, $id, $segments, $corps),
        'conges'    => routerConges($methode, $id, $segments, $corps),
        'motifs'    => routerMotifs($methode, $id, $corps),
        'postes'    => routerPostes($methode, $id, $corps),
        default     => repondre(404, ['erreur' => "Ressource '$ressource' introuvable."]),
    };
} catch (PDOException $e) {
    $message = (APP_ENV === 'development') ? $e->getMessage() : 'Erreur interne du serveur.';
    repondre(500, ['erreur' => $message]);
}

// ═══════════════════════════════════════════════════════════════════════════════
// Routeur Personnel
// ═══════════════════════════════════════════════════════════════════════════════
function routerPersonnel(string $methode, ?int $id, array $segments, array $corps): void
{
    $model = new Personnel();

    match (true) {
        // GET /personnel
        $methode === 'GET' && $id === null =>
            repondre(200, $model->listerTous()),

        // GET /personnel/{id}
        $methode === 'GET' && $id !== null => (function () use ($model, $id) {
            $emp = $model->trouverParId($id);
            $emp ? repondre(200, $emp) : repondre(404, ['erreur' => "Personnel $id introuvable."]);
        })(),

        // POST /personnel
        $methode === 'POST' && $id === null => (function () use ($model, $corps) {
            $nouvelId = $model->creer($corps);
            repondre(201, ['message' => 'Personnel créé.', 'id' => $nouvelId]);
        })(),

        // PUT /personnel/{id}
        ($methode === 'PUT' || $methode === 'PATCH') && $id !== null => (function () use ($model, $id, $corps) {
            $nb = $model->modifier($id, $corps);
            $nb > 0
                ? repondre(200, ['message' => 'Personnel modifié.'])
                : repondre(404, ['erreur' => "Personnel $id introuvable ou aucune modification."]);
        })(),

        // DELETE /personnel/{id}
        $methode === 'DELETE' && $id !== null => (function () use ($model, $id) {
            $nb = $model->supprimer($id);
            $nb > 0
                ? repondre(200, ['message' => 'Personnel supprimé.'])
                : repondre(404, ['erreur' => "Personnel $id introuvable."]);
        })(),

        default => repondre(405, ['erreur' => 'Méthode non autorisée.']),
    };
}

// ═══════════════════════════════════════════════════════════════════════════════
// Routeur Congés
// ═══════════════════════════════════════════════════════════════════════════════
function routerConges(string $methode, ?int $id, array $segments, array $corps): void
{
    $model = new Conges();

    // Route spéciale : GET /conges/personnel/{id}
    if ($methode === 'GET' && ($segments[1] ?? '') === 'personnel' && isset($segments[2]) && ctype_digit($segments[2])) {
        repondre(200, $model->listerParPersonnel((int)$segments[2]));
        return;
    }

    match (true) {
        // GET /conges
        $methode === 'GET' && $id === null =>
            repondre(200, $model->listerTous()),

        // GET /conges/{id}
        $methode === 'GET' && $id !== null => (function () use ($model, $id) {
            $conge = $model->trouverParId($id);
            $conge ? repondre(200, $conge) : repondre(404, ['erreur' => "Congé $id introuvable."]);
        })(),

        // POST /conges
        // Si type_conge = 'annuel' et aucun personnel_id → création pour tout le personnel actif
        $methode === 'POST' && $id === null => (function () use ($model, $corps) {
            $estAnnuelCollectif = (($corps['type_conge'] ?? '') === 'annuel') && empty($corps['personnel_id']);
            if ($estAnnuelCollectif) {
                $nb = $model->creerPourTous($corps);
                repondre(201, ['message' => "Congé annuel créé pour $nb agent(s).", 'nb_crees' => $nb]);
            } else {
                $nouvelId = $model->creer($corps);
                repondre(201, ['message' => 'Congé créé.', 'id' => $nouvelId]);
            }
        })(),

        // PUT /conges/{id}
        ($methode === 'PUT' || $methode === 'PATCH') && $id !== null => (function () use ($model, $id, $corps) {
            $nb = $model->modifier($id, $corps);
            $nb > 0
                ? repondre(200, ['message' => 'Congé modifié.'])
                : repondre(404, ['erreur' => "Congé $id introuvable ou aucune modification."]);
        })(),

        // DELETE /conges/{id}
        $methode === 'DELETE' && $id !== null => (function () use ($model, $id) {
            $nb = $model->supprimer($id);
            $nb > 0
                ? repondre(200, ['message' => 'Congé supprimé.'])
                : repondre(404, ['erreur' => "Congé $id introuvable."]);
        })(),

        default => repondre(405, ['erreur' => 'Méthode non autorisée.']),
    };
}

// ═══════════════════════════════════════════════════════════════════════════════
// Routeur Motifs
// ═══════════════════════════════════════════════════════════════════════════════
function routerMotifs(string $methode, ?int $id, array $corps): void
{
    $model = new Motifs();

    match (true) {
        // GET /motifs
        $methode === 'GET' && $id === null =>
            repondre(200, $model->listerTous()),

        // GET /motifs/{id}
        $methode === 'GET' && $id !== null => (function () use ($model, $id) {
            $motif = $model->trouverParId($id);
            $motif ? repondre(200, $motif) : repondre(404, ['erreur' => "Motif $id introuvable."]);
        })(),

        // POST /motifs
        $methode === 'POST' && $id === null => (function () use ($model, $corps) {
            $nouvelId = $model->creer($corps);
            repondre(201, ['message' => 'Motif créé.', 'id' => $nouvelId]);
        })(),

        // PUT /motifs/{id}
        ($methode === 'PUT' || $methode === 'PATCH') && $id !== null => (function () use ($model, $id, $corps) {
            $nb = $model->modifier($id, $corps);
            $nb > 0
                ? repondre(200, ['message' => 'Motif modifié.'])
                : repondre(404, ['erreur' => "Motif $id introuvable ou aucune modification."]);
        })(),

        // DELETE /motifs/{id}
        $methode === 'DELETE' && $id !== null => (function () use ($model, $id) {
            $nb = $model->supprimer($id);
            $nb > 0
                ? repondre(200, ['message' => 'Motif supprimé.'])
                : repondre(404, ['erreur' => "Motif $id introuvable."]);
        })(),

        default => repondre(405, ['erreur' => 'Méthode non autorisée.']),
    };
}

// ═══════════════════════════════════════════════════════════════════════════════
// Routeur Postes
// ═══════════════════════════════════════════════════════════════════════════════
function routerPostes(string $methode, ?int $id, array $corps): void
{
    $model = new Postes();

    match (true) {
        // GET /postes
        $methode === 'GET' && $id === null =>
            repondre(200, $model->listerTous()),

        // GET /postes/{id}
        $methode === 'GET' && $id !== null => (function () use ($model, $id) {
            $poste = $model->trouverParId($id);
            $poste ? repondre(200, $poste) : repondre(404, ['erreur' => "Poste $id introuvable."]);
        })(),

        // POST /postes
        $methode === 'POST' && $id === null => (function () use ($model, $corps) {
            $nouvelId = $model->creer($corps);
            repondre(201, ['message' => 'Poste créé.', 'id' => $nouvelId]);
        })(),

        // PUT /postes/{id}
        ($methode === 'PUT' || $methode === 'PATCH') && $id !== null => (function () use ($model, $id, $corps) {
            $nb = $model->modifier($id, $corps);
            $nb > 0
                ? repondre(200, ['message' => 'Poste modifié.'])
                : repondre(404, ['erreur' => "Poste $id introuvable ou aucune modification."]);
        })(),

        // DELETE /postes/{id}
        $methode === 'DELETE' && $id !== null => (function () use ($model, $id) {
            $nb = $model->supprimer($id);
            $nb > 0
                ? repondre(200, ['message' => 'Poste supprimé.'])
                : repondre(404, ['erreur' => "Poste $id introuvable."]);
        })(),

        default => repondre(405, ['erreur' => 'Méthode non autorisée.']),
    };
}

// ═══════════════════════════════════════════════════════════════════════════════
// Helper : réponse JSON
// ═══════════════════════════════════════════════════════════════════════════════
function repondre(int $code, mixed $donnees): void
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
