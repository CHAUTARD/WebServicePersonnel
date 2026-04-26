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
        if ($action === 'create_conge') {
            $typeConge = (string)($_POST['type_conge'] ?? 'annuel');
            $motifId = !empty($_POST['motif_id']) ? (int)$_POST['motif_id'] : null;
            $dateDebut = (string)$_POST['date_debut'];
            $dateFin = (string)$_POST['date_fin'];
            $pourTous = isset($_POST['pour_tous']) && $typeConge === 'annuel';

            if ($pourTous) {
                $personnelsActifs = $pdo->query('SELECT id FROM personnel WHERE actif = 1')->fetchAll();
                $stmt = $pdo->prepare('INSERT INTO conges (personnel_id, motif_id, type_conge, date_debut, date_fin) VALUES (:pid, :mid, :type, :dd, :df)');
                foreach ($personnelsActifs as $p) {
                    $stmt->execute([
                        ':pid' => (int)$p['id'],
                        ':mid' => $motifId,
                        ':type' => 'annuel',
                        ':dd' => $dateDebut,
                        ':df' => $dateFin,
                    ]);
                }
                flash('Conge annuel cree pour tout le personnel actif.');
            } else {
                $stmt = $pdo->prepare('INSERT INTO conges (personnel_id, motif_id, type_conge, date_debut, date_fin) VALUES (:pid, :mid, :type, :dd, :df)');
                $stmt->execute([
                    ':pid' => (int)($_POST['personnel_id'] ?? 0),
                    ':mid' => $motifId,
                    ':type' => $typeConge,
                    ':dd' => $dateDebut,
                    ':df' => $dateFin,
                ]);
                flash('Conge ajoute.');
            }
        }

        if ($action === 'update_conge') {
            $motifId = !empty($_POST['motif_id']) ? (int)$_POST['motif_id'] : null;
            $stmt = $pdo->prepare('UPDATE conges SET personnel_id = :pid, motif_id = :mid, type_conge = :type, date_debut = :dd, date_fin = :df WHERE id = :id');
            $stmt->execute([
                ':id' => (int)$_POST['id'],
                ':pid' => (int)$_POST['personnel_id'],
                ':mid' => $motifId,
                ':type' => (string)$_POST['type_conge'],
                ':dd' => (string)$_POST['date_debut'],
                ':df' => (string)$_POST['date_fin'],
            ]);
            flash('Conge modifie.');
        }

        if ($action === 'delete_conge') {
            $stmt = $pdo->prepare('DELETE FROM conges WHERE id = :id');
            $stmt->execute([':id' => (int)$_POST['id']]);
            flash('Conge supprime.');
        }

    } catch (Throwable $e) {
        flash('Erreur: ' . $e->getMessage());
    }

    header('Location: conges.php');
    exit;
}

$flash = flash();
$personnels = $pdo->query('SELECT id, nom, prenom FROM personnel ORDER BY nom, prenom')->fetchAll();
$motifs = $pdo->query('SELECT id, libelle FROM motifs ORDER BY libelle')->fetchAll();
$conges = $pdo->query("SELECT c.id, c.personnel_id, c.motif_id, c.type_conge, c.date_debut, c.date_fin, c.nb_jours,
 p.nom, p.prenom, m.libelle AS motif
FROM conges c
JOIN personnel p ON p.id = c.personnel_id
LEFT JOIN motifs m ON m.id = c.motif_id
ORDER BY c.date_debut DESC")->fetchAll();

$map = [];
foreach ($conges as $c) {
    $map[(string)$c['id']] = [
        'personnel_id' => (int)$c['personnel_id'],
        'motif_id' => $c['motif_id'] !== null ? (int)$c['motif_id'] : null,
        'type_conge' => (string)$c['type_conge'],
        'date_debut' => (string)$c['date_debut'],
        'date_fin' => (string)$c['date_fin'],
    ];
}
$flags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

adminRenderHeader('Service Conges', 'conges', $flash);
?>
<section class="card">
    <h2><span class="icon icon-side-conges"></span>Service Conges</h2>
    <div class="block">
        <form method="post" id="conge-form" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;align-items:end">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="action" id="conge-action" value="create_conge">
            <input type="hidden" name="id" id="conge-id" value="">

            <div>
                <label class="small">Personnel (laisser vide si annuel pour tous)</label>
                <select name="personnel_id" id="conge-personnel">
                    <option value="">-- Selectionner --</option>
                    <?php foreach ($personnels as $p): ?>
                        <option value="<?= (int)$p['id'] ?>"><?= e($p['nom'] . ' ' . $p['prenom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="small">Motif</label>
                <select name="motif_id" id="conge-motif">
                    <option value="">-- Aucun --</option>
                    <?php foreach ($motifs as $m): ?>
                        <option value="<?= (int)$m['id'] ?>"><?= e($m['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="small">Type conge</label>
                <select name="type_conge" id="conge-type" required>
                    <option value="annuel">Annuel</option>
                    <option value="maladie">Maladie</option>
                    <option value="maternite">Maternite</option>
                    <option value="paternite">Paternite</option>
                    <option value="sans_solde">Sans solde</option>
                    <option value="autre">Autre</option>
                </select>
            </div>

            <div>
                <label class="small">Date debut</label>
                <input type="date" name="date_debut" id="conge-date-debut" required>
            </div>

            <div>
                <label class="small">Date fin</label>
                <input type="date" name="date_fin" id="conge-date-fin" required>
            </div>

            <div>
                <label class="small"><input type="checkbox" name="pour_tous" id="conge-pour-tous"> Annuel pour tout le personnel actif</label>
            </div>

            <div class="form-actions" style="grid-column:1 / -1;margin-top:6px">
                <button type="submit" id="conge-submit">Ajouter</button>
                <button type="button" id="conge-reset" style="display:none">Abandon</button>
            </div>
        </form>

        <table>
            <tr><th>Actions</th><th>Personnel</th><th>Type</th><th>Motif</th><th>Debut</th><th>Fin</th><th>Jours</th></tr>
            <?php foreach ($conges as $c): ?>
                <tr>
                    <td class="row-actions">
                        <a href="#" onclick="return editConge('<?= (int)$c['id'] ?>')"><span class="icon icon-edit"></span>Editer</a>
                        <form class="inline" method="post" onsubmit="return confirm('Supprimer ce conge ?')">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="action" value="delete_conge">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="linklike" type="submit"><span class="icon icon-del"></span>Supprimer</button>
                        </form>
                    </td>
                    <td><?= e($c['nom'] . ' ' . $c['prenom']) ?></td>
                    <td><?= e($c['type_conge']) ?></td>
                    <td><?= e((string)($c['motif'] ?? '')) ?></td>
                    <td><?= e($c['date_debut']) ?></td>
                    <td><?= e($c['date_fin']) ?></td>
                    <td><?= e((string)$c['nb_jours']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<?php
$script = '<script>
(() => {
  const data = ' . json_encode($map, $flags) . ';
    const action = document.getElementById("conge-action");
    const id = document.getElementById("conge-id");
    const personnel = document.getElementById("conge-personnel");
    const motif = document.getElementById("conge-motif");
    const type = document.getElementById("conge-type");
    const dateDebut = document.getElementById("conge-date-debut");
    const dateFin = document.getElementById("conge-date-fin");
    const pourTous = document.getElementById("conge-pour-tous");
    const submit = document.getElementById("conge-submit");
    const reset = document.getElementById("conge-reset");
    const form = document.getElementById("conge-form");
    if (!action || !id || !personnel || !motif || !type || !dateDebut || !dateFin || !pourTous || !submit || !reset || !form) return;

    function setAddMode() {
        action.value = "create_conge";
        id.value = "";
        personnel.value = "";
        motif.value = "";
        type.value = "annuel";
        dateDebut.value = "";
        dateFin.value = "";
        pourTous.checked = false;
        submit.textContent = "Ajouter";
        reset.style.display = "none";
    }

    window.editConge = (congeId) => {
        const item = data[String(congeId)];
        if (!item) return false;
        action.value = "update_conge";
        id.value = String(congeId);
        personnel.value = String(item.personnel_id || "");
        motif.value = item.motif_id === null ? "" : String(item.motif_id);
        type.value = item.type_conge || "annuel";
        dateDebut.value = item.date_debut || "";
        dateFin.value = item.date_fin || "";
        pourTous.checked = false;
        submit.textContent = "Modifier";
        reset.style.display = "inline-block";
        form.scrollIntoView({behavior:"smooth", block:"center"});
        return false;
    };

    reset.addEventListener("click", setAddMode);
})();
</script>';

adminRenderFooter($script);
