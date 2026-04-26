<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pdo = db();
$nbUsers = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
if ($nbUsers === 0) {
    header('Location: setup.php');
    exit;
}

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = null;
$flash = flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, username, password_hash, actif FROM admin_users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['actif'] !== 1 || !password_verify($password, (string)$user['password_hash'])) {
        $error = 'Identifiants invalides.';
    } else {
        $_SESSION['admin_user_id'] = (int)$user['id'];
        $_SESSION['admin_username'] = (string)$user['username'];
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion Administration</title>
    <style>
        body{margin:0;background:linear-gradient(180deg,#e9eff6,#dfe8f2);font-family:Tahoma,Verdana,sans-serif;color:#2d3f52}
        .top{background:linear-gradient(180deg,#0c3d71,#072c54);color:#fff;padding:10px 14px;border-bottom:1px solid #001d3c;font-weight:700}
        .wrap{min-height:calc(100vh - 46px);display:grid;place-items:center;padding:18px}
        .card{width:min(500px,94vw);background:#f7f9fc;border:1px solid #b8c6d7;box-shadow:0 2px 8px rgba(0,0,0,.12)}
        .head{padding:9px 12px;background:linear-gradient(180deg,#fdfefe,#e9f0f8);border-bottom:1px solid #b8c6d7;font-weight:700;color:#20476e}
        .body{padding:12px}
        label{display:block;margin-top:10px;font-size:12px;color:#586d83;font-weight:700}
        input{width:100%;padding:8px;border:1px solid #aebdce;background:#fff;margin-top:4px;box-sizing:border-box}
        .field-input{max-width:340px}
        .password-row{display:flex;align-items:center;gap:8px;max-width:340px}
        .password-row input{flex:1;max-width:none}
        .btn-eye{margin-top:4px;padding:7px 9px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(180deg,#f6f8fb,#dce6f2);border:1px solid #9fb3ca;color:#1c3e62;cursor:pointer}
        .btn-eye svg{display:block}
        button{margin-top:14px;background:linear-gradient(180deg,#3d8bd1,#1e6eb8);color:#fff;border:1px solid #16538b;padding:7px 12px;font-weight:700;cursor:pointer}
        .msg{background:#e6f4de;border:1px solid #b7d7a8;color:#2e5b23;padding:8px;margin-bottom:10px}
        .err{background:#fde8e8;border:1px solid #efb9b9;color:#922d2d;padding:8px;margin-bottom:10px}
        .hint{font-size:11px;color:#6b7f93;margin-top:8px}
    </style>
</head>
<body>
<div class="top">Administration WebServices Personnel</div>
<div class="wrap">
<div class="card">
    <div class="head">Connexion</div>
    <div class="body">
        <?php if ($flash): ?><div class="msg"><?= e($flash) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label>Nom d'utilisateur</label>
            <input class="field-input" name="username" required>
            <label>Mot de passe</label>
            <div class="password-row">
                <input id="password" class="field-input" name="password" type="password" required>
                <button id="togglePassword" class="btn-eye" type="button" aria-controls="password" aria-label="Afficher le mot de passe" title="Afficher/Masquer">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M1 12C3.8 7.5 7.6 5.25 12 5.25C16.4 5.25 20.2 7.5 23 12C20.2 16.5 16.4 18.75 12 18.75C7.6 18.75 3.8 16.5 1 12Z" stroke="currentColor" stroke-width="1.8"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8"/>
                    </svg>
                </button>
            </div>
            <button type="submit">Se connecter</button>
        </form>
        <div class="hint">Interface de gestion interne, acces reserve aux administrateurs.</div>
    </div>
</div>
</div>
<script>
const togglePasswordButton = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if (togglePasswordButton && passwordInput) {
    togglePasswordButton.addEventListener('click', function () {
        const showPassword = passwordInput.type === 'password';
        passwordInput.type = showPassword ? 'text' : 'password';
        togglePasswordButton.setAttribute('aria-label', showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
}
</script>
</body>
</html>
