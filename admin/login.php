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
        .card{width:min(430px,94vw);background:#f7f9fc;border:1px solid #b8c6d7;box-shadow:0 2px 8px rgba(0,0,0,.12)}
        .head{padding:9px 12px;background:linear-gradient(180deg,#fdfefe,#e9f0f8);border-bottom:1px solid #b8c6d7;font-weight:700;color:#20476e}
        .body{padding:12px}
        label{display:block;margin-top:10px;font-size:12px;color:#586d83;font-weight:700}
        input{width:100%;padding:8px;border:1px solid #aebdce;background:#fff;margin-top:4px}
        button{margin-top:14px;background:linear-gradient(180deg,#3d8bd1,#1e6eb8);color:#fff;border:1px solid #16538b;padding:7px 12px;font-weight:700;cursor:pointer}
        .msg{background:#e6f4de;border:1px solid #b7d7a8;color:#2e5b23;padding:8px;margin-bottom:10px}
        .err{background:#fde8e8;border:1px solid #efb9b9;color:#922d2d;padding:8px;margin-bottom:10px}
        .hint{font-size:11px;color:#6b7f93;margin-top:8px}
    </style>
</head>
<body>
<div class="top">Administration WebServices</div>
<div class="wrap">
<div class="card">
    <div class="head">Connexion</div>
    <div class="body">
        <?php if ($flash): ?><div class="msg"><?= e($flash) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="err"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <label>Nom d'utilisateur</label>
            <input name="username" required>
            <label>Mot de passe</label>
            <input name="password" type="password" required>
            <button type="submit">Se connecter</button>
        </form>
        <div class="hint">Interface de gestion interne, acces reserve aux administrateurs.</div>
    </div>
</div>
</div>
</body>
</html>
