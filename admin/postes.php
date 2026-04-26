<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/layout.php';
requireLogin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = (string)($_POST['action'] ?? '');

    try {
        if ($action === 'create_poste') {
            $stmt = $pdo->prepare('INSERT INTO postes (libelle) VALUES (:libelle)');
            $stmt->execute([':libelle' => trim((string)$_POST['libelle'])]);
            flash('Poste ajoute.');
        }

        if ($action === 'update_poste') {
            $stmt = $pdo->prepare('UPDATE postes SET libelle = :libelle WHERE id = :id');
            $stmt->execute([
                ':id' => (int)$_POST['id'],
                ':libelle' => trim((string)$_POST['libelle']),
            ]);
            flash('Poste modifie.');
        }

        if ($action === 'delete_poste') {
            $stmt = $pdo->prepare('DELETE FROM postes WHERE id = :id');
            $stmt->execute([':id' => (int)$_POST['id']]);
            flash('Poste supprime.');
        }

    } catch (Throwable $e) {
        flash('Erreur: ' . $e->getMessage());
    }

    header('Location: postes.php');
    exit;
}

$flash = flash();
$postes = $pdo->query('SELECT id, libelle FROM postes ORDER BY libelle')->fetchAll();

$map = [];
foreach ($postes as $p) {
    $map[(string)$p['id']] = ['libelle' => (string)$p['libelle']];
}
$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

adminRenderHeader('Service Postes', 'postes', $flash);
?>
<section class="card">
    <h2>Service Postes</h2>
    <div class="block">
        <form method="post" id="poste-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" id="poste-action" value="create_poste">
            <input type="hidden" name="id" id="poste-id" value="">
            <input name="libelle" id="poste-libelle" placeholder="Libelle poste" required>
            <div class="form-actions">
                <button type="submit" id="poste-submit">Ajouter</button>
                <button type="button" id="poste-reset" style="display:none">Abandon</button>
            </div>
        </form>

        <table>
            <tr><th>Actions</th><th>Libelle</th></tr>
            <?php foreach ($postes as $p): ?>
                <tr>
                    <td class="row-actions">
                        <a href="#" onclick="return editPoste('<?= (int)$p['id'] ?>')"><span class="icon icon-edit"></span>Editer</a>
                        <form class="inline" method="post" onsubmit="return confirm('Supprimer ce poste ?')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_poste">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button class="linklike" type="submit"><span class="icon icon-del"></span>Supprimer</button>
                        </form>
                    </td>
                    <td><?= e($p['libelle']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<?php
$script = '<script>
(() => {
  const data = ' . json_encode($map, $flags) . ';
    const action = document.getElementById("poste-action");
    const id = document.getElementById("poste-id");
    const libelle = document.getElementById("poste-libelle");
    const submit = document.getElementById("poste-submit");
    const reset = document.getElementById("poste-reset");
    const form = document.getElementById("poste-form");
    if (!action || !id || !libelle || !submit || !reset || !form) return;

    function setAddMode() {
        action.value = "create_poste";
        id.value = "";
        libelle.value = "";
        submit.textContent = "Ajouter";
        reset.style.display = "none";
    }

    window.editPoste = (posteId) => {
        const item = data[String(posteId)];
        if (!item) return false;
        action.value = "update_poste";
        id.value = String(posteId);
        libelle.value = item.libelle || "";
        submit.textContent = "Modifier";
        reset.style.display = "inline-block";
        form.scrollIntoView({behavior:"smooth", block:"center"});
        return false;
    };

    reset.addEventListener("click", setAddMode);
})();
</script>';

adminRenderFooter($script);
