<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('enseignant');

$enseignant_id = $_SESSION['enseignant_id'];

// Cours de l'enseignant
$cours_liste = $pdo->prepare("SELECT id, code, nom FROM cours WHERE enseignant_id = ? ORDER BY code");
$cours_liste->execute([$enseignant_id]);
$cours_liste = $cours_liste->fetchAll();

$cours_id = (int)($_GET['cours_id'] ?? ($cours_liste[0]['id'] ?? 0));

$etudiants = [];
if ($cours_id) {
    $stmt = $pdo->prepare("
        SELECT e.id, e.numero_etudiant, e.filiere, e.niveau,
               u.nom, u.prenom, u.email,
               n.moyenne, n.resultat,
               COUNT(p.id) as nb_absences
        FROM inscriptions i
        JOIN etudiants e ON e.id = i.etudiant_id
        JOIN users u ON u.id = e.user_id
        LEFT JOIN notes n ON n.etudiant_id = e.id AND n.cours_id = ?
        LEFT JOIN presences p ON p.etudiant_id = e.id AND p.cours_id = ? AND p.statut = 'absent'
        WHERE i.cours_id = ? AND i.statut = 'actif'
        GROUP BY e.id
        ORDER BY u.nom
    ");
    $stmt->execute([$cours_id, $cours_id, $cours_id]);
    $etudiants = $stmt->fetchAll();
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
    <title>Mes étudiants — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Mes étudiants 👥</h1>
                <p><?= count($etudiants) ?> étudiant(s) inscrit(s)</p>
            </div>
        </div>

        <!-- Sélection cours -->
        <div class="card" style="margin-bottom:20px;">
            <form method="GET" style="display:flex;align-items:center;gap:12px;">
                <label style="font-weight:600;">Cours :</label>
                <select name="cours_id" onchange="this.form.submit()" style="max-width:350px;">
                    <?php foreach ($cours_liste as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $cours_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['code'].' - '.$c['nom']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div class="table-card">
            <?php if ($cours_actuel): ?>
            <div class="table-card-header">
                <h3><?= htmlspecialchars($cours_actuel['nom']) ?></h3>
                <a href="notes.php?cours_id=<?= $cours_id ?>" class="btn btn-primary btn-sm">Saisir les notes</a>
            </div>
            <?php endif; ?>
            <table>
                <thead>
                    <tr><th>Étudiant</th><th>N° Étudiant</th><th>Filière</th><th>Niveau</th><th>Moyenne</th><th>Absences</th><th>Résultat</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($etudiants as $e): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></strong><br>
                            <small style="color:#94a3b8;"><?= htmlspecialchars($e['email']) ?></small>
                        </td>
                        <td><code><?= $e['numero_etudiant'] ?></code></td>
                        <td><?= htmlspecialchars($e['filiere']) ?></td>
                        <td><span class="badge badge-blue"><?= $e['niveau'] ?></span></td>
                        <td><strong><?= $e['moyenne'] !== null ? $e['moyenne'].'/20' : '—' ?></strong></td>
                        <td>
                            <span class="badge <?= $e['nb_absences'] >= 3 ? 'badge-red' : 'badge-green' ?>">
                                <?= $e['nb_absences'] ?> absence(s)
                            </span>
                        </td>
                        <td>
                            <?php if ($e['resultat']): ?>
                            <span class="badge <?= $e['resultat'] === 'admis' ? 'badge-green' : ($e['resultat'] === 'ajourne' ? 'badge-red' : 'badge-gray') ?>">
                                <?= $e['resultat'] === 'admis' ? 'Admis' : ($e['resultat'] === 'ajourne' ? 'Ajourné' : 'En attente') ?>
                            </span>
                            <?php else: ?>
                            <span class="badge badge-gray">En attente</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($etudiants)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:32px;">Aucun étudiant inscrit</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
