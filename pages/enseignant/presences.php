<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('enseignant');

$enseignant_id = $_SESSION['enseignant_id'];
$message = '';

// Enregistrement présences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['presences'])) {
    $cours_id    = (int)$_POST['cours_id'];
    $date_seance = $_POST['date_seance'];
    foreach ($_POST['presences'] as $etudiant_id => $statut) {
        $etudiant_id = (int)$etudiant_id;
        // Vérifie si déjà enregistré
        $check = $pdo->prepare("SELECT id FROM presences WHERE etudiant_id=? AND cours_id=? AND date_seance=?");
        $check->execute([$etudiant_id, $cours_id, $date_seance]);
        if ($check->fetch()) {
            $pdo->prepare("UPDATE presences SET statut=? WHERE etudiant_id=? AND cours_id=? AND date_seance=?")
                ->execute([$statut, $etudiant_id, $cours_id, $date_seance]);
        } else {
            $pdo->prepare("INSERT INTO presences (etudiant_id, cours_id, date_seance, statut) VALUES (?,?,?,?)")
                ->execute([$etudiant_id, $cours_id, $date_seance, $statut]);
        }
    }
    $message = "Présences enregistrées !";
}

$cours_liste = $pdo->prepare("SELECT id, code, nom FROM cours WHERE enseignant_id = ? ORDER BY code");
$cours_liste->execute([$enseignant_id]);
$cours_liste = $cours_liste->fetchAll();

$cours_id    = (int)($_GET['cours_id'] ?? ($_POST['cours_id'] ?? ($cours_liste[0]['id'] ?? 0)));
$date_seance = $_GET['date'] ?? date('Y-m-d');

$etudiants = [];
if ($cours_id) {
    $stmt = $pdo->prepare("
        SELECT e.id as etudiant_id, u.nom, u.prenom, e.numero_etudiant,
               p.statut as presence_statut
        FROM inscriptions i
        JOIN etudiants e ON e.id = i.etudiant_id
        JOIN users u ON u.id = e.user_id
        LEFT JOIN presences p ON p.etudiant_id = e.id AND p.cours_id = ? AND p.date_seance = ?
        WHERE i.cours_id = ? AND i.statut = 'actif'
        ORDER BY u.nom
    ");
    $stmt->execute([$cours_id, $date_seance, $cours_id]);
    $etudiants = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Présences — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div><h1>Suivi des présences ✅</h1></div>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="card" style="margin-bottom:20px;">
            <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <div>
                    <label style="font-weight:600;margin-right:8px;">Cours :</label>
                    <select name="cours_id" onchange="this.form.submit()">
                        <?php foreach ($cours_liste as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $cours_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['code'].' - '.$c['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label style="font-weight:600;margin-right:8px;">Date :</label>
                    <input type="date" name="date" value="<?= $date_seance ?>" onchange="this.form.submit()">
                </div>
            </form>
        </div>

        <?php if (!empty($etudiants)): ?>
        <form method="POST">
            <input type="hidden" name="cours_id" value="<?= $cours_id ?>">
            <input type="hidden" name="date_seance" value="<?= $date_seance ?>">
            <div class="table-card">
                <div class="table-card-header">
                    <h3>Présences du <?= date('d/m/Y', strtotime($date_seance)) ?></h3>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </div>
                <table>
                    <thead><tr><th>Étudiant</th><th>N° Étudiant</th><th>Présence</th></tr></thead>
                    <tbody>
                        <?php foreach ($etudiants as $e): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></strong></td>
                            <td><?= $e['numero_etudiant'] ?></td>
                            <td>
                                <select name="presences[<?= $e['etudiant_id'] ?>]" style="width:150px;">
                                    <option value="present"  <?= ($e['presence_statut'] ?? '') === 'present'  ? 'selected' : '' ?>>✅ Présent</option>
                                    <option value="absent"   <?= ($e['presence_statut'] ?? 'absent') === 'absent'   ? 'selected' : '' ?>>❌ Absent</option>
                                    <option value="retard"   <?= ($e['presence_statut'] ?? '') === 'retard'   ? 'selected' : '' ?>>⏰ Retard</option>
                                    <option value="justifie" <?= ($e['presence_statut'] ?? '') === 'justifie' ? 'selected' : '' ?>>📄 Justifié</option>
                                </select>
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
</body>
</html>
