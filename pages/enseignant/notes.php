<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('enseignant');

$enseignant_id = $_SESSION['enseignant_id'];
$message = '';
$erreur  = '';

$cours_liste = $pdo->prepare("SELECT id, code, nom FROM cours WHERE enseignant_id = ? AND actif = 1 ORDER BY code");
$cours_liste->execute([$enseignant_id]);
$cours_liste = $cours_liste->fetchAll();

$cours_id = (int)($_GET['cours_id'] ?? ($_POST['cours_id'] ?? ($cours_liste[0]['id'] ?? 0)));

// VERROUILLAGE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'verrouiller') {
    $cours_id = (int)$_POST['cours_id'];
    $check = $pdo->prepare("SELECT id FROM cours WHERE id = ? AND enseignant_id = ?");
    $check->execute([$cours_id, $enseignant_id]);
    if ($check->fetch()) {
        $pdo->prepare("UPDATE notes SET verrouille = 1 WHERE cours_id = ?")->execute([$cours_id]);
        $message = "🔒 Notes verrouillées définitivement.";
    }
}

// SAUVEGARDE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notes'])) {
    $cours_id = (int)$_POST['cours_id'];
    $check = $pdo->prepare("SELECT id FROM cours WHERE id = ? AND enseignant_id = ?");
    $check->execute([$cours_id, $enseignant_id]);
    if (!$check->fetch()) {
        $erreur = "Accès non autorisé.";
    } else {
        foreach ($_POST['notes'] as $etudiant_id => $note_data) {
            $etudiant_id  = (int)$etudiant_id;
            $cc1          = $note_data['cc1'] !== '' ? (float)$note_data['cc1'] : null;
            $cc2          = $note_data['cc2'] !== '' ? (float)$note_data['cc2'] : null;
            $examen_final = $note_data['examen'] !== '' ? (float)$note_data['examen'] : null;

            $verif = $pdo->prepare("SELECT verrouille FROM notes WHERE etudiant_id = ? AND cours_id = ?");
            $verif->execute([$etudiant_id, $cours_id]);
            $existing = $verif->fetch();
            if ($existing && $existing['verrouille']) continue;

            $moyenne  = null;
            if ($cc1 !== null && $cc2 !== null && $examen_final !== null) {
                $moyenne = round($cc1 * 0.2 + $cc2 * 0.2 + $examen_final * 0.6, 2);
            }
            $resultat = $moyenne !== null ? ($moyenne >= 10 ? 'admis' : 'ajourne') : 'en_attente';

            if ($existing) {
                $pdo->prepare("UPDATE notes SET cc1=?, cc2=?, examen_final=?, moyenne=?, resultat=? WHERE etudiant_id=? AND cours_id=?")
                    ->execute([$cc1, $cc2, $examen_final, $moyenne, $resultat, $etudiant_id, $cours_id]);
            } else {
                $pdo->prepare("INSERT INTO notes (etudiant_id, cours_id, cc1, cc2, examen_final, moyenne, resultat) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$etudiant_id, $cours_id, $cc1, $cc2, $examen_final, $moyenne, $resultat]);
            }
        }
        $message = "Notes sauvegardées !";
    }
}

$etudiants = [];
$notes_verrouillees = false;
if ($cours_id) {
    $stmt = $pdo->prepare("
        SELECT e.id as etudiant_id, u.nom, u.prenom, e.numero_etudiant,
               n.cc1, n.cc2, n.examen_final, n.moyenne, n.resultat, n.verrouille
        FROM inscriptions i
        JOIN etudiants e ON e.id = i.etudiant_id
        JOIN users u ON u.id = e.user_id
        LEFT JOIN notes n ON n.etudiant_id = e.id AND n.cours_id = ?
        WHERE i.cours_id = ? AND i.statut = 'actif'
        ORDER BY u.nom
    ");
    $stmt->execute([$cours_id, $cours_id]);
    $etudiants = $stmt->fetchAll();
    foreach ($etudiants as $e) {
        if ($e['verrouille']) { $notes_verrouillees = true; break; }
    }
}

$cours_actuel = null;
foreach ($cours_liste as $c) {
    if ($c['id'] == $cours_id) { $cours_actuel = $c; break; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisie des notes — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Saisie des notes 📝</h1>
                <p>CC1 (20%) · CC2 (20%) · Examen (60%) — Moyenne calculée en temps réel</p>
            </div>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

        <div class="card" style="margin-bottom:20px;">
            <form method="GET" style="display:flex;align-items:center;gap:12px;">
                <label style="font-weight:600;">Cours :</label>
                <select name="cours_id" onchange="this.form.submit()" style="max-width:300px;">
                    <?php foreach ($cours_liste as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $cours_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['code'].' - '.$c['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if ($cours_id && !empty($etudiants)): ?>
        <form method="POST">
            <input type="hidden" name="cours_id" value="<?= $cours_id ?>">
            <div class="table-card">
                <div class="table-card-header">
                    <h3>📋 <?= htmlspecialchars($cours_actuel['nom'] ?? '') ?></h3>
                    <div style="display:flex;gap:8px;">
                        <?php if (!$notes_verrouillees): ?>
                        <button type="submit" class="btn btn-primary">💾 Sauvegarder</button>
                        <button type="submit" name="action" value="verrouiller" class="btn btn-danger"
                                onclick="return confirm('⚠️ Verrouiller définitivement les notes ? Cette action est irréversible.')">
                            🔒 Verrouiller
                        </button>
                        <?php else: ?>
                        <span class="badge badge-orange" style="padding:8px 16px;font-size:14px;">🔒 Notes verrouillées</span>
                        <?php endif; ?>
                    </div>
                </div>
                <table>
                    <thead>
                        <tr><th>Étudiant</th><th>CC1 /20</th><th>CC2 /20</th><th>Examen /20</th><th>Moyenne</th><th>Résultat</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($etudiants as $e): ?>
                        <tr class="note-row">
                            <td>
                                <strong><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></strong><br>
                                <small style="color:#94a3b8;"><?= $e['numero_etudiant'] ?></small>
                            </td>
                            <?php if ($e['verrouille']): ?>
                                <td><?= $e['cc1'] ?? '—' ?></td>
                                <td><?= $e['cc2'] ?? '—' ?></td>
                                <td><?= $e['examen_final'] ?? '—' ?></td>
                            <?php else: ?>
                                <td><input type="number" class="input-cc1" name="notes[<?= $e['etudiant_id'] ?>][cc1]" value="<?= $e['cc1'] ?>" min="0" max="20" step="0.5" style="width:70px;"></td>
                                <td><input type="number" class="input-cc2" name="notes[<?= $e['etudiant_id'] ?>][cc2]" value="<?= $e['cc2'] ?>" min="0" max="20" step="0.5" style="width:70px;"></td>
                                <td><input type="number" class="input-exam" name="notes[<?= $e['etudiant_id'] ?>][examen]" value="<?= $e['examen_final'] ?>" min="0" max="20" step="0.5" style="width:70px;"></td>
                            <?php endif; ?>
                            <td><strong class="moyenne-live"><?= $e['moyenne'] ? $e['moyenne'].'/20' : '—' ?></strong></td>
                            <td>
                                <span class="resultat-live badge <?= $e['resultat'] === 'admis' ? 'badge-green' : ($e['resultat'] === 'ajourne' ? 'badge-red' : 'badge-gray') ?>">
                                    <?= $e['resultat'] === 'admis' ? 'Admis' : ($e['resultat'] === 'ajourne' ? 'Ajourné' : 'En attente') ?>
                                </span>
                                <?php if ($e['verrouille']): ?><span class="badge badge-orange">🔒</span><?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
        <?php endif; ?>
    </main>
</div>
<script src="/smartcampus/assets/js/notes.js"></script>
</body>
</html>
