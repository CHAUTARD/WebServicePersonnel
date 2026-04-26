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
        if ($action === 'create_personnel') {
            $stmt = $pdo->prepare('INSERT INTO personnel (nom, prenom, poste_id, actif) VALUES (:nom, :prenom, :poste_id, :actif)');
            $stmt->execute([
                ':nom' => trim((string)$_POST['nom']),
                ':prenom' => trim((string)$_POST['prenom']),
                ':poste_id' => (int)$_POST['poste_id'],
                ':actif' => isset($_POST['actif']) ? 1 : 0,
            ]);
            flash('Personnel ajoute.');
        }

        if ($action === 'update_personnel') {
            $stmt = $pdo->prepare('UPDATE personnel SET nom = :nom, prenom = :prenom, poste_id = :poste_id, actif = :actif WHERE id = :id');
            $stmt->execute([
                ':id' => (int)$_POST['id'],
                ':nom' => trim((string)$_POST['nom']),
                ':prenom' => trim((string)$_POST['prenom']),
                ':poste_id' => (int)$_POST['poste_id'],
                ':actif' => isset($_POST['actif']) ? 1 : 0,
            ]);
            flash('Personnel modifie.');
        }

        if ($action === 'delete_personnel') {
            $stmt = $pdo->prepare('DELETE FROM personnel WHERE id = :id');
            $stmt->execute([':id' => (int)$_POST['id']]);
            flash('Personnel supprime.');
        }

    } catch (Throwable $e) {
        flash('Erreur: ' . $e->getMessage());
    }

    header('Location: personnel.php');
    exit;
}

$flash = flash();
$postes = $pdo->query('SELECT id, libelle FROM postes ORDER BY libelle')->fetchAll();
$personnels = $pdo->query('SELECT pe.id, pe.nom, pe.prenom, pe.poste_id, po.libelle AS poste, pe.actif FROM personnel pe JOIN postes po ON po.id = pe.poste_id ORDER BY pe.nom, pe.prenom')->fetchAll();

$map = [];
foreach ($personnels as $p) {
    $map[(string)$p['id']] = [
        'nom' => (string)$p['nom'],
        'prenom' => (string)$p['prenom'],
        'poste_id' => (int)$p['poste_id'],
        'actif' => (int)$p['actif'],
    ];
}
$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

adminRenderHeader('Service Personnel', 'personnel', $flash);
?>
<section class="card">
    <h2>Service Personnel</h2>
    <div class="block">
        <form method="post" id="personnel-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" id="personnel-action" value="create_personnel">
            <input type="hidden" name="id" id="personnel-id" value="">
            <input name="nom" id="personnel-nom" placeholder="Nom" required>
            <input name="prenom" id="personnel-prenom" placeholder="Prenom" required>
            <select name="poste_id" id="personnel-poste-id" required>
                <option value="">-- Poste --</option>
                <?php foreach ($postes as $poste): ?>
                    <option value="<?= (int)$poste['id'] ?>"><?= e($poste['libelle']) ?></option>
                <?php endforeach; ?>
            </select>
            <label class="small checkline"><input type="checkbox" name="actif" id="personnel-actif" checked>Actif</label>
            <div class="form-actions">
                <button type="submit" id="personnel-submit">Ajouter</button>
                <button type="button" id="personnel-reset" style="display:none">Abandon</button>
            </div>
        </form>

        <table>
            <tr><th>Actions</th><th>Nom</th><th>Poste</th><th>Actif</th></tr>
            <?php foreach ($personnels as $p): ?>
                <tr>
                    <td class="row-actions">
                        <a href="#" onclick="return editPersonnel('<?= (int)$p['id'] ?>')"><span class="icon icon-edit"></span>Editer</a>
                        <form class="inline" method="post" onsubmit="return confirm('Supprimer ce personnel ?')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_personnel">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button class="linklike" type="submit"><span class="icon icon-del"></span>Supprimer</button>
                        </form>
                    </td>
                    <td><?= e($p['nom'] . ' ' . $p['prenom']) ?></td>
                    <td><?= e($p['poste']) ?></td>
                    <td><?= (int)$p['actif'] === 1 ? 'Oui' : 'Non' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<?php
$script = '<script>
(() => {
  const data = ' . json_encode($map, $flags) . ';
    const action = document.getElementById("personnel-action");
    const id = document.getElementById("personnel-id");
    const nom = document.getElementById("personnel-nom");
    const prenom = document.getElementById("personnel-prenom");
    const posteId = document.getElementById("personnel-poste-id");
    const actif = document.getElementById("personnel-actif");
    const submit = document.getElementById("personnel-submit");
    const reset = document.getElementById("personnel-reset");
    const form = document.getElementById("personnel-form");
    if (!action || !id || !nom || !prenom || !posteId || !actif || !submit || !reset || !form) return;

    function setAddMode() {
        action.value = "create_personnel";
        id.value = "";
        nom.value = "";
        prenom.value = "";
        posteId.value = "";
        actif.checked = true;
        submit.textContent = "Ajouter";
        reset.style.display = "none";
    }

    window.editPersonnel = (personnelId) => {
        const item = data[String(personnelId)];
        if (!item) return false;
        action.value = "update_personnel";
        id.value = String(personnelId);
        nom.value = item.nom || "";
        prenom.value = item.prenom || "";
        posteId.value = String(item.poste_id || "");
        actif.checked = Number(item.actif) === 1;
        submit.textContent = "Modifier";
        reset.style.display = "inline-block";
        form.scrollIntoView({behavior:"smooth", block:"center"});
        return false;
    };

    reset.addEventListener("click", setAddMode);
})();
</script>';

adminRenderFooter($script);
