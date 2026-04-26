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
        if ($action === 'create_motif') {
            $stmt = $pdo->prepare('INSERT INTO motifs (libelle) VALUES (:libelle)');
            $stmt->execute([':libelle' => trim((string)$_POST['libelle'])]);
            flash('Motif ajoute.');
        }

        if ($action === 'update_motif') {
            $stmt = $pdo->prepare('UPDATE motifs SET libelle = :libelle WHERE id = :id');
            $stmt->execute([
                ':id' => (int)$_POST['id'],
                ':libelle' => trim((string)$_POST['libelle']),
            ]);
            flash('Motif modifie.');
        }

        if ($action === 'delete_motif') {
            $stmt = $pdo->prepare('DELETE FROM motifs WHERE id = :id');
            $stmt->execute([':id' => (int)$_POST['id']]);
            flash('Motif supprime.');
        }

    } catch (Throwable $e) {
        flash('Erreur: ' . $e->getMessage());
    }

    header('Location: motifs.php');
    exit;
}

$flash = flash();
$motifs = $pdo->query('SELECT id, libelle FROM motifs ORDER BY libelle')->fetchAll();

$map = [];
foreach ($motifs as $m) {
    $map[(string)$m['id']] = ['libelle' => (string)$m['libelle']];
}
$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

adminRenderHeader('Service Motifs', 'motifs', $flash);
?>
<section class="card">
    <h2><span class="icon icon-side-motifs"></span>Service Motifs</h2>
    <div class="block">
        <form method="post" id="motif-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" id="motif-action" value="create_motif">
            <input type="hidden" name="id" id="motif-id" value="">
            <input name="libelle" id="motif-libelle" placeholder="Libelle motif" required>
            <div class="form-actions">
                <button type="submit" id="motif-submit">Ajouter</button>
                <button type="button" id="motif-reset" style="display:none">Abandon</button>
            </div>
        </form>

        <table>
            <tr><th>Actions</th><th>Libelle</th></tr>
            <?php foreach ($motifs as $m): ?>
                <tr>
                    <td class="row-actions">
                        <a href="#" onclick="return editMotif('<?= (int)$m['id'] ?>')"><span class="icon icon-edit"></span>Editer</a>
                        <form class="inline" method="post" onsubmit="return confirm('Supprimer ce motif ?')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_motif">
                            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                            <button class="linklike" type="submit"><span class="icon icon-del"></span>Supprimer</button>
                        </form>
                    </td>
                    <td><?= e($m['libelle']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<?php
$script = '<script>
(() => {
  const data = ' . json_encode($map, $flags) . ';
    const action = document.getElementById("motif-action");
    const id = document.getElementById("motif-id");
    const libelle = document.getElementById("motif-libelle");
    const submit = document.getElementById("motif-submit");
    const reset = document.getElementById("motif-reset");
    const form = document.getElementById("motif-form");
    if (!action || !id || !libelle || !submit || !reset || !form) return;

    function setAddMode() {
        action.value = "create_motif";
        id.value = "";
        libelle.value = "";
        submit.textContent = "Ajouter";
        reset.style.display = "none";
    }

    window.editMotif = (motifId) => {
        const item = data[String(motifId)];
        if (!item) return false;
        action.value = "update_motif";
        id.value = String(motifId);
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
