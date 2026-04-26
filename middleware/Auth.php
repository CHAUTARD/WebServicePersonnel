<?php
/**
 * Middleware d'authentification par clé API
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';

class Auth
{
    /**
     * Vérifie la présence et la validité de la clé API.
     * Arrête l'exécution avec 401 si la clé est absente ou invalide.
     */
    public static function verifier(): void
    {
        $cle = self::extraireCle();

        if ($cle === null || $cle === '') {
            self::refuser(401, 'Clé API manquante. Fournissez l\'en-tête ' . API_KEY_HEADER . '.');
        }

        // Validation format : uniquement caractères alphanumériques et tirets/underscores
        if (!preg_match('/^[A-Za-z0-9_\-]{8,128}$/', $cle)) {
            self::refuser(401, 'Format de clé API invalide.');
        }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT id FROM api_keys WHERE api_key = :cle AND actif = 1 LIMIT 1'
        );
        $stmt->execute([':cle' => $cle]);

        if ($stmt->fetch() === false) {
            self::refuser(403, 'Clé API invalide ou désactivée.');
        }
    }

    // ─── Helpers privés ────────────────────────────────────────────────────────

    private static function extraireCle(): ?string
    {
        // Priorité 1 : en-tête HTTP X-API-Key
        $header = 'HTTP_' . strtoupper(str_replace('-', '_', API_KEY_HEADER));
        if (!empty($_SERVER[$header])) {
            return trim($_SERVER[$header]);
        }

        // Priorité 2 : en-tête Authorization: Bearer <cle>
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $parts = explode(' ', $_SERVER['HTTP_AUTHORIZATION'], 2);
            if (strtolower($parts[0]) === 'bearer' && isset($parts[1])) {
                return trim($parts[1]);
            }
        }

        return null;
    }

    private static function refuser(int $code, string $message): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['erreur' => $message]);
        exit;
    }
}
