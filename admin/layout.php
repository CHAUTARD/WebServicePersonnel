<?php
declare(strict_types=1);

if (!function_exists('adminRenderHeader')) {
    function adminRenderHeader(string $title, string $active, ?string $flash = null): void
    {
        $tabs = [
            'personnel' => ['label' => 'Personnel', 'href' => 'personnel.php'],
            'postes' => ['label' => 'Postes', 'href' => 'postes.php'],
            'motifs' => ['label' => 'Motifs', 'href' => 'motifs.php'],
            'conges' => ['label' => 'Conges', 'href' => 'conges.php'],
        ];
        ?>
        <!doctype html>
        <html lang="fr">
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?= e($title) ?> - Administration</title>
            <style>
                :root{--bg:#dfe7ef;--panel:#f7f9fc;--border:#b9c6d5;--head:#0c3d71;--head2:#072c54;--text:#263645;--muted:#5b6f84;--ok:#e6f4de;--okb:#b7d7a8;--danger:#c0392b;--link:#235a87;--headrow:#d5dee8}
                *{box-sizing:border-box}
                body{margin:0;background:linear-gradient(180deg,#ebf1f7 0,#dbe5ef 240px,#dfe7ef 100%);color:var(--text);font-family:Tahoma,Verdana,Segoe UI,sans-serif;font-size:13px}
                .topbar{background:linear-gradient(180deg,var(--head),var(--head2));color:#fff;border-bottom:1px solid #001c3b;box-shadow:0 2px 6px rgba(0,0,0,.18)}
                .topbar-inner{max-width:1280px;margin:0 auto;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px}
                .brand{font-weight:700;letter-spacing:.3px}
                .userline{font-size:12px;color:#cfe1f5}
                .userline a{color:#fff;text-decoration:none;font-weight:700}
                .userline a:hover{text-decoration:underline}
                .tabs{max-width:1280px;margin:0 auto;padding:0 14px;display:flex;gap:6px;overflow:auto;border-top:1px solid rgba(255,255,255,.14)}
                .tab{color:#d8e8f7;text-decoration:none;padding:8px 10px;border:1px solid transparent;border-bottom:0;border-radius:5px 5px 0 0;white-space:nowrap}
                .tab.active{background:#f6f8fc;color:#163a60;border-color:#9fb0c2;font-weight:700}
                .icon{display:inline-block;width:14px;height:14px;vertical-align:-2px;margin-right:4px;background-size:14px 14px;background-repeat:no-repeat}
                .icon-tab-structure{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Crect x='1' y='2' width='12' height='2' fill='%23a8c4de'/%3E%3Crect x='1' y='6' width='12' height='2' fill='%23cfe0ef'/%3E%3Crect x='1' y='10' width='12' height='2' fill='%23f0f6fb'/%3E%3C/svg%3E")}
                .icon-tab-sql{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cpath d='M2 2h10v10H2z' fill='%23e8f1fa' stroke='%23729bc0'/%3E%3Cpath d='M4 5h6M4 7h6M4 9h4' stroke='%23537799' stroke-width='1'/%3E%3C/svg%3E")}
                .icon-tab-search{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Ccircle cx='6' cy='6' r='4' fill='none' stroke='%239bc0df' stroke-width='2'/%3E%3Cpath d='M9 9l3 3' stroke='%23dceaf6' stroke-width='2'/%3E%3C/svg%3E")}
                .icon-tab-export{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cpath d='M7 2v6' stroke='%23cde1f3' stroke-width='2'/%3E%3Cpath d='M4 6l3 3 3-3' fill='none' stroke='%23cde1f3' stroke-width='2'/%3E%3Crect x='2' y='10' width='10' height='2' fill='%238fb3d2'/%3E%3C/svg%3E")}
                .icon-tab-priv{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Ccircle cx='7' cy='5' r='3' fill='%23c8ddf0'/%3E%3Crect x='3' y='9' width='8' height='3' fill='%238eb1cf'/%3E%3C/svg%3E")}
                .statusbar{max-width:1280px;margin:0 auto;padding:6px 14px;color:#d9e5f3;font-size:11px;border-top:1px solid rgba(255,255,255,.1)}
                .content-wrap{max-width:1280px;margin:12px auto;padding:0 14px 20px}
                .layout{display:flex;gap:12px;align-items:flex-start}
                .sidebar{width:210px;position:sticky;top:10px;background:var(--panel);border:1px solid var(--border);box-shadow:inset 0 1px 0 #fff}
                .sidebar h3{margin:0;padding:8px 10px;font-size:12px;color:#1c446a;border-bottom:1px solid var(--border);background:linear-gradient(180deg,#fdfefe,#e9f0f8);display:flex;align-items:center;gap:6px}
                .icon-cartouche{display:inline-block;width:14px;height:14px;vertical-align:-2px;background-size:14px 14px;background-repeat:no-repeat;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cellipse cx='4.2' cy='4.2' rx='2.1' ry='2.1' fill='%238ab0d3'/%3E%3Cellipse cx='9.8' cy='4.2' rx='2.1' ry='2.1' fill='%238ab0d3'/%3E%3Cpath d='M1.5 11.8c.4-2 1.6-3 2.7-3h.2c1.1 0 2.3 1 2.7 3' fill='%23c8ddf0'/%3E%3Cpath d='M6.8 11.8c.4-2 1.6-3 2.7-3h.2c1.1 0 2.3 1 2.7 3' fill='%23c8ddf0'/%3E%3C/svg%3E")}
                .sidebar a{display:block;padding:8px 10px;text-decoration:none;color:#214465;border-top:1px solid #e7edf4;font-size:12px}
                .sidebar a:hover{background:#eef4fb}
                .sidebar a.active{background:#dfe9f4;font-weight:700}
                .main-pane{flex:1;min-width:0}
                .msg{margin:0 0 10px;background:var(--ok);border:1px solid var(--okb);color:#2e5b23;padding:8px 10px}
                .card{background:var(--panel);border:1px solid var(--border);box-shadow:inset 0 1px 0 #fff}
                .card h2{margin:0;padding:8px 10px;font-size:13px;text-transform:uppercase;color:#1c446a;border-bottom:1px solid var(--border);background:linear-gradient(180deg,#fdfefe,#e9f0f8)}
                .block{padding:10px}
                .block > form{margin-bottom:10px}
                .small{font-size:11px;color:var(--muted)}
                input,select{width:100%;padding:7px 8px;border:1px solid #aebdcf;background:#fff;color:#203245;border-radius:2px;margin:3px 0}
                input[type="checkbox"]{width:auto;padding:0;margin:0;vertical-align:middle}
                .checkline{display:inline-flex;align-items:center;gap:6px}
                input:focus,select:focus{outline:0;border-color:#6b94bf;box-shadow:0 0 0 2px rgba(87,136,184,.15)}
                button{background:linear-gradient(180deg,#3d8bd1,#1e6eb8);color:#fff;border:1px solid #16538b;padding:6px 10px;border-radius:2px;cursor:pointer;font-size:12px;font-weight:700}
                button:hover{filter:brightness(1.06)}
                form button[type="submit"]:not(.linklike){display:block;margin-left:auto}
                .danger{background:linear-gradient(180deg,#db5f52,#bd3629);border-color:#8f2218}
                table{width:100%;border-collapse:collapse;font-size:12px;border-top:1px solid var(--border)}
                th,td{padding:7px 8px;border-bottom:1px solid #d6e0ea;text-align:left;vertical-align:middle}
                th{background:linear-gradient(180deg,#edf3f9,#dbe4ee);color:#35597c;font-weight:700;border-right:1px solid #c6d2de}
                tr:nth-child(even) td{background:#f9fbfd}
                tr:hover td{background:#f1f7fe}
                .edit-pane{margin-top:8px;padding:10px;border:1px solid #c8d4e3;background:#f2f7fc}
                form.inline{display:inline}
                .form-actions{display:flex;justify-content:flex-end;align-items:center;gap:8px;margin-top:-2px;margin-bottom:6px}
                .form-actions button[type="submit"]{display:inline-block;margin-left:0}
                .row-actions{white-space:nowrap}
                table th:first-child,table td.row-actions{width:130px}
                .row-actions a,.row-actions button.linklike{display:inline;background:none;border:0;padding:0;margin:0 8px 0 0;color:var(--link);text-decoration:none;font-size:12px;cursor:pointer}
                .row-actions a:hover,.row-actions button.linklike:hover{text-decoration:underline}
                .icon-edit{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Cpath d='M2 10l1.5 1.5L10.8 4.2 9.3 2.7z' fill='%23d6a03a'/%3E%3Cpath d='M8.7 2.1l1.5-1.5 1.7 1.7-1.5 1.5z' fill='%238a6b2c'/%3E%3Cpath d='M2 12l2.8-.7L2.7 9.2z' fill='%236b7a86'/%3E%3C/svg%3E")}
                .icon-del{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Ccircle cx='7' cy='7' r='6' fill='%23e5736d'/%3E%3Crect x='3' y='6' width='8' height='2' fill='white'/%3E%3C/svg%3E")}
                .icon-table{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Crect x='1' y='2' width='12' height='10' fill='%23f4f8fc' stroke='%2388a9c6'/%3E%3Cpath d='M1 5h12M1 8h12M5 2v10M9 2v10' stroke='%23b1c7da'/%3E%3C/svg%3E")}
                .icon-side-personnel{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Ccircle cx='7' cy='4' r='2.2' fill='%2387add0'/%3E%3Cpath d='M2.5 12c.5-2.4 2.1-3.8 4.5-3.8S11 9.6 11.5 12' fill='%23c7dcef'/%3E%3C/svg%3E")}
                .icon-side-postes{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Crect x='2' y='2' width='10' height='3' fill='%2387add0'/%3E%3Crect x='2' y='6' width='10' height='3' fill='%23a9c4dd'/%3E%3Crect x='2' y='10' width='10' height='2' fill='%23c8dcef'/%3E%3C/svg%3E")}
                .icon-side-motifs{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Crect x='2' y='1.8' width='10' height='10.2' rx='1' fill='%23eef5fc' stroke='%2387add0'/%3E%3Cpath d='M4 4.5h6M4 6.8h6M4 9.1h4' stroke='%235d84a9' stroke-width='1'/%3E%3C/svg%3E")}
                .icon-side-conges{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 14 14'%3E%3Crect x='1.7' y='2.2' width='10.6' height='9.8' rx='1' fill='%23eef5fc' stroke='%2387add0'/%3E%3Cpath d='M1.7 5h10.6' stroke='%2387add0'/%3E%3Ccircle cx='5' cy='7.7' r='1' fill='%235d84a9'/%3E%3Ccircle cx='8' cy='7.7' r='1' fill='%235d84a9'/%3E%3Ccircle cx='11' cy='7.7' r='1' fill='%235d84a9'/%3E%3C/svg%3E")}
                .toolbar-inline{margin-top:6px;color:#4f657b;font-size:12px}
                .toolbar-inline a{color:var(--link);text-decoration:none;margin-right:8px}
                .toolbar-inline a:hover{text-decoration:underline}
                @media (max-width:768px){.topbar-inner{flex-direction:column;align-items:flex-start}.layout{flex-direction:column}.sidebar{position:static;width:100%}}
            </style>
        </head>
        <body>
        <div class="topbar">
            <div class="topbar-inner">
                <div class="brand">Administration WebServices Personnel</div>
                <div class="userline">
                    Connecte: <?= e((string)($_SESSION['admin_username'] ?? 'admin')) ?> |
                    <a href="logout.php">Deconnexion</a>
                </div>
            </div>
            <nav class="tabs" aria-label="navigation">
                <?php foreach ($tabs as $key => $tab): ?>
                    <a class="tab <?= $active === $key ? 'active' : '' ?>" href="<?= e($tab['href']) ?>">
                        <span class="icon <?= $key === 'personnel' ? 'icon-side-personnel' : ($key === 'postes' ? 'icon-side-postes' : ($key === 'motifs' ? 'icon-side-motifs' : 'icon-side-conges')) ?>"></span><?= e($tab['label']) ?>
                    </a>
                <?php endforeach; ?>
                <a class="tab" href="#"><span class="icon icon-tab-export"></span>Exporter</a>
                <a class="tab" href="#"><span class="icon icon-tab-priv"></span>Privileges</a>
            </nav>
            <div class="statusbar">
                Serveur: localhost:3306 | Base de donnees: gestion_personnel | Utilisateur: <?= e((string)($_SESSION['admin_username'] ?? 'admin')) ?>
            </div>
        </div>

        <div class="content-wrap">
            <?php if ($flash): ?><div class="msg"><?= e($flash) ?></div><?php endif; ?>
            <div class="layout">
                <aside class="sidebar">
                    <h3><span class="icon-cartouche" aria-hidden="true"></span>Gestion du personnel</h3>
                    <a class="<?= $active === 'personnel' ? 'active' : '' ?>" href="personnel.php"><span class="icon icon-side-personnel"></span>Personnel</a>
                    <a class="<?= $active === 'postes' ? 'active' : '' ?>" href="postes.php"><span class="icon icon-side-postes"></span>Postes</a>
                    <a class="<?= $active === 'motifs' ? 'active' : '' ?>" href="motifs.php"><span class="icon icon-side-motifs"></span>Motifs</a>
                    <a class="<?= $active === 'conges' ? 'active' : '' ?>" href="conges.php"><span class="icon icon-side-conges"></span>Conges</a>
                </aside>
                <div class="main-pane">
        <?php
    }
}

if (!function_exists('adminRenderFooter')) {
    function adminRenderFooter(string $script = ''): void
    {
        echo "</div></div></div>";
        if ($script !== '') {
            echo $script;
        }
        echo "</body></html>";
    }
}
