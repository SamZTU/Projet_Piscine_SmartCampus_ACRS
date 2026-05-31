<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('etudiant');

$etudiant_id = $_SESSION['etudiant_id'];
$message = '';
$erreur  = '';

// Désinscription
if (isset($_GET['desinscrire'])) {
    $cours_id = (int)$_GET['desinscrire'];
    $pdo->prepare("DELETE FROM inscriptions WHERE etudiant_id = ? AND cours_id = ?")->execute([$etudiant_id, $cours_id]);
    $message = "Désinscription effectuée.";
}

// Mes cours inscrits
$mes_cours = $pdo->prepare("
    SELECT c.*, i.date_inscription, i.statut as insc_statut,
           u.nom as ens_nom, u.prenom as ens_prenom
    FROM inscriptions i
    JOIN cours c ON c.id = i.cours_id
    LEFT JOIN enseignants en ON en.id = c.enseignant_id
    LEFT JOIN users u ON u.id = en.user_id
    WHERE i.etudiant_id = ?
    ORDER BY c.code
");
$mes_cours->execute([$etudiant_id]);
$mes_cours = $mes_cours->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes cours — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Mes cours 📚</h1>
                <p><?= count($mes_cours) ?> cours suivis</p>
            </div>
            <a href="inscriptions.php" class="btn btn-primary">+ S'inscrire à un cours</a>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

        <div class="table-card">
            <table>
                <thead><tr><th>Code</th><th>Cours</th><th>Enseignant</th><th>Semestre</th><th>Crédits</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($mes_cours as $c): ?>
                    <tr>
                        <td><code><?= $c['code'] ?></code></td>
                        <td><strong><?= htmlspecialchars($c['nom']) ?></strong></td>
                        <td><?= $c['ens_nom'] ? htmlspecialchars($c['ens_prenom'].' '.$c['ens_nom']) : '—' ?></td>
                        <td><span class="badge badge-blue"><?= $c['semestre'] ?></span></td>
                        <td><?= $c['credits'] ?></td>
                        <td>
                            <a href="cours.php?desinscrire=<?= $c['id'] ?>"
                               onclick="return confirm('Se désinscrire de ce cours ?')"
                               class="btn btn-danger btn-sm">Se désinscrire</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($mes_cours)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px;">Aucun cours</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
