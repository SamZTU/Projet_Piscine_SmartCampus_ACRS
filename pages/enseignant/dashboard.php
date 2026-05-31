<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('enseignant');

$enseignant_id = $_SESSION['enseignant_id'];

$nb_cours = $pdo->prepare("SELECT COUNT(*) FROM cours WHERE enseignant_id = ? AND actif = 1");
$nb_cours->execute([$enseignant_id]);
$nb_cours = $nb_cours->fetchColumn();

$nb_etudiants = $pdo->prepare("SELECT COUNT(DISTINCT i.etudiant_id) FROM inscriptions i JOIN cours c ON c.id = i.cours_id WHERE c.enseignant_id = ? AND i.statut = 'actif'");
$nb_etudiants->execute([$enseignant_id]);
$nb_etudiants = $nb_etudiants->fetchColumn();

$nb_notes_manquantes = $pdo->prepare("SELECT COUNT(*) FROM inscriptions i JOIN cours c ON c.id = i.cours_id LEFT JOIN notes n ON n.etudiant_id = i.etudiant_id AND n.cours_id = i.cours_id WHERE c.enseignant_id = ? AND i.statut = 'actif' AND n.id IS NULL");
$nb_notes_manquantes->execute([$enseignant_id]);
$nb_notes_manquantes = $nb_notes_manquantes->fetchColumn();

$mes_cours = $pdo->prepare("
    SELECT c.*, COUNT(i.id) as nb_inscrits
    FROM cours c
    LEFT JOIN inscriptions i ON i.cours_id = c.id AND i.statut = 'actif'
    WHERE c.enseignant_id = ?
    GROUP BY c.id ORDER BY c.code
");
$mes_cours->execute([$enseignant_id]);
$mes_cours = $mes_cours->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Enseignant — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Bonjour, <?= htmlspecialchars($_SESSION['prenom']) ?> 👋</h1>
                <p>Espace enseignant</p>
            </div>
        </div>

        <div class="cards-grid">
            <div class="card stat-card">
                <div class="stat-icon blue">📚</div>
                <div><div class="stat-value"><?= $nb_cours ?></div><div class="stat-label">Mes cours</div></div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon green">👥</div>
                <div><div class="stat-value"><?= $nb_etudiants ?></div><div class="stat-label">Étudiants</div></div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon orange">📝</div>
                <div><div class="stat-value"><?= $nb_notes_manquantes ?></div><div class="stat-label">Notes à saisir</div></div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <h3>📚 Mes cours</h3>
                <a href="notes.php" class="btn btn-primary btn-sm">Saisir les notes</a>
            </div>
            <table>
                <thead><tr><th>Code</th><th>Cours</th><th>Semestre</th><th>Inscrits</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($mes_cours as $c): ?>
                    <tr>
                        <td><code><?= $c['code'] ?></code></td>
                        <td><strong><?= htmlspecialchars($c['nom']) ?></strong></td>
                        <td><span class="badge badge-blue"><?= $c['semestre'] ?></span></td>
                        <td><?= $c['nb_inscrits'] ?>/<?= $c['capacite_max'] ?></td>
                        <td>
                            <a href="notes.php?cours_id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">Notes</a>
                            <a href="presences.php?cours_id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">Présences</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($mes_cours)): ?>
                    <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:32px;">Aucun cours assigné</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>
