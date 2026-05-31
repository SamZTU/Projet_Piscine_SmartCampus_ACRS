<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('etudiant');

$etudiant_id = $_SESSION['etudiant_id'];

$notes = $pdo->prepare("
    SELECT n.*, c.nom as cours_nom, c.code as cours_code, c.credits, c.coefficient
    FROM notes n
    JOIN cours c ON c.id = n.cours_id
    WHERE n.etudiant_id = ?
    ORDER BY c.code
");
$notes->execute([$etudiant_id]);
$notes = $notes->fetchAll();

$moyenne_generale = 0;
$total_coeff = 0;
foreach ($notes as $n) {
    if ($n['moyenne'] !== null) {
        $moyenne_generale += $n['moyenne'] * $n['coefficient'];
        $total_coeff += $n['coefficient'];
    }
}
$moyenne_generale = $total_coeff > 0 ? round($moyenne_generale / $total_coeff, 2) : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes notes — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Mes notes 📊</h1>
                <p>Résultats académiques</p>
            </div>
            <?php if ($moyenne_generale !== null): ?>
            <div class="card" style="padding:12px 24px;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:<?= $moyenne_generale >= 10 ? '#10b981' : '#ef4444' ?>">
                    <?= $moyenne_generale ?>/20
                </div>
                <div style="font-size:13px;color:#64748b;">Moyenne générale</div>
            </div>
            <?php endif; ?>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Cours</th><th>CC1</th><th>CC2</th><th>Examen</th><th>Moyenne</th><th>Résultat</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($notes as $n): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($n['cours_nom']) ?></strong><br>
                            <small style="color:#94a3b8;"><?= $n['cours_code'] ?> · <?= $n['credits'] ?> crédits · coeff <?= $n['coefficient'] ?></small>
                        </td>
                        <td><?= $n['cc1'] !== null ? $n['cc1'].'/20' : '—' ?></td>
                        <td><?= $n['cc2'] !== null ? $n['cc2'].'/20' : '—' ?></td>
                        <td><?= $n['examen_final'] !== null ? $n['examen_final'].'/20' : '—' ?></td>
                        <td><strong><?= $n['moyenne'] !== null ? $n['moyenne'].'/20' : '—' ?></strong></td>
                        <td>
                            <span class="badge <?= $n['resultat'] === 'admis' ? 'badge-green' : ($n['resultat'] === 'ajourne' ? 'badge-red' : 'badge-gray') ?>">
                                <?= $n['resultat'] === 'admis' ? 'Admis' : ($n['resultat'] === 'ajourne' ? 'Ajourné' : 'En attente') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($notes)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px;">Aucune note disponible</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
