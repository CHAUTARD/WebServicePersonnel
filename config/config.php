<?php
/**
 * Configuration générale de l'application
 */

// ── Environnement ──────────────────────────────────────────────────────────────
define('APP_ENV', 'development'); // 'development' | 'production'

// ── Base de données ────────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    3306);
define('DB_NAME',    'gestion_personnel');
define('DB_USER',    'root');          // Changez selon votre configuration
define('DB_PASS',    '');             // Changez selon votre configuration
define('DB_CHARSET', 'utf8mb4');

// ── En-têtes CORS ──────────────────────────────────────────────────────────────
// Restreignez les origines autorisées en production
define('CORS_ORIGIN',  '*');
define('CORS_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('CORS_HEADERS', 'Content-Type, X-API-Key');

// ── Sécurité ───────────────────────────────────────────────────────────────────
// Nom de l'en-tête HTTP attendu pour la clé API
define('API_KEY_HEADER', 'X-API-Key');
