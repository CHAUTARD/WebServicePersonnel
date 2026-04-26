<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$pdo = db();
$nbUsers = (int)$pdo->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();

if ($nbUsers > 0) {
    header('Location: login.php');
    exit;
}

$error = null;
$usernameMaxLength = 50;
$passwordMaxLength = 72;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || strlen($username) < 3) {
        $error = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
    } elseif (strlen($username) > $usernameMaxLength) {
        $error = 'Le nom d\'utilisateur ne doit pas dépasser ' . $usernameMaxLength . ' caractères.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (strlen($password) > $passwordMaxLength) {
        $error = 'Le mot de passe ne doit pas dépasser ' . $passwordMaxLength . ' caractères.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash, actif) VALUES (:u, :p, 1)');
        $stmt->execute([':u' => $username, ':p' => $hash]);

        flash('Compte administrateur créé. Connectez-vous.');
        header('Location: login.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Initialisation Administration</title>
    <style>
        body{margin:0;background:linear-gradient(180deg,#e9eff6,#dfe8f2);font-family:Tahoma,Verdana,sans-serif;color:#2d3f52}
        .top{background:linear-gradient(180deg,#0c3d71,#072c54);color:#fff;padding:10px 14px;border-bottom:1px solid #001d3c;font-weight:700}
        .wrap{min-height:calc(100vh - 46px);display:grid;place-items:center;padding:18px}
        .card{width:min(460px,94vw);background:#f7f9fc;border:1px solid #b8c6d7;box-shadow:0 2px 8px rgba(0,0,0,.12)}
        .head{padding:9px 12px;background:linear-gradient(180deg,#fdfefe,#e9f0f8);border-bottom:1px solid #b8c6d7;font-weight:700;color:#20476e}
        .body{padding:12px}
        p{margin:0 0 10px;color:#61758a;font-size:12px}
        label{display:block;margin-top:10px;font-size:12px;color:#586d83;font-weight:700}
        input{width:100%;padding:8px;border:1px solid #aebdce;background:#fff;margin-top:4px;box-sizing:border-box}
        .password-wrap{display:flex;gap:8px;align-items:center}
        .password-wrap input{flex:1}
        button{margin-top:14px;background:linear-gradient(180deg,#3d8bd1,#1e6eb8);color:#fff;border:1px solid #16538b;padding:7px 12px;font-weight:700;cursor:pointer}
        .btn-toggle{margin-top:4px;background:linear-gradient(180deg,#f6f8fb,#dce6f2);color:#1c3e62;border:1px solid #9fb3ca}
        .err{background:#fde8e8;border:1px solid #efb9b9;color:#922d2d;padding:8px;margin-bottom:10px}
    </style>
</head>
<body>
<div class="top">Administration WebServices Personnel</div>
<div class="wrap">
<div class="card">
    <div class="head">Création du premier administrateur</div>
    <div class="body">
        <p>Cette étape initialise l'accès au back-office.</p>
        <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label>Nom d'utilisateur</label>
            <input name="username" required minlength="3" maxlength="<?= (int)$usernameMaxLength ?>" autocomplete="username">
            <label>Mot de passe</label>
            <div class="password-wrap">
                <input id="password" name="password" type="password" required minlength="8" maxlength="<?= (int)$passwordMaxLength ?>" autocomplete="new-password">
                <button id="togglePassword" class="btn-toggle" type="button" aria-controls="password" aria-label="Afficher le mot de passe">Afficher</button>
            </div>
            <button type="submit">Créer le compte</button>
        </form>
    </div>
</div>
</div>
<script>
const toggleButton = document.getElementById('togglePassword');
const passwordInput = document.getElementById('password');

if (toggleButton && passwordInput) {
    toggleButton.addEventListener('click', function () {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggleButton.textContent = isPassword ? 'Masquer' : 'Afficher';
        toggleButton.setAttribute('aria-label', isPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
    });
}
</script>
</body>
</html>
