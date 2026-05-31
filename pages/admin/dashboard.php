<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('admin');

$nb_etudiants    = $pdo->query("SELECT COUNT(*) FROM etudiants")->fetchColumn();
$nb_enseignants  = $pdo->query("SELECT COUNT(*) FROM enseignants")->fetchColumn();
$nb_cours        = $pdo->query("SELECT COUNT(*) FROM cours WHERE actif = 1")->fetchColumn();
$nb_inscriptions = $pdo->query("SELECT COUNT(*) FROM inscriptions WHERE statut = 'actif'")->fetchColumn();

// Stats par filière pour graphique
$filieres = $pdo->query("SELECT filiere, COUNT(*) as nb FROM etudiants GROUP BY filiere ORDER BY nb DESC")->fetchAll();

// Résultats globaux pour graphique
$resultats = $pdo->query("SELECT resultat, COUNT(*) as nb FROM notes WHERE resultat != 'en_attente' GROUP BY resultat")->fetchAll();

// Cours les plus remplis
$cours_stats = $pdo->query("
    SELECT c.nom, c.capacite_max, COUNT(i.id) as nb_inscrits
    FROM cours c LEFT JOIN inscriptions i ON i.cours_id = c.id AND i.statut='actif'
    GROUP BY c.id ORDER BY nb_inscrits DESC LIMIT 5
")->fetchAll();

$derniers_etudiants = $pdo->query("
    SELECT u.nom, u.prenom, e.filiere, e.niveau
    FROM etudiants e JOIN users u ON u.id = e.user_id
    ORDER BY e.id DESC LIMIT 5
")->fetchAll();

// Pour Chart.js
$filiere_labels = json_encode(array_column($filieres, 'filiere'));
$filiere_data   = json_encode(array_column($filieres, 'nb'));

$resultat_labels = json_encode(array_map(fn($r) => ucfirst($r['resultat']), $resultats));
$resultat_data   = json_encode(array_column($resultats, 'nb'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Tableau de bord Admin 🛠️</h1>
                <p>Vue d'ensemble de la plateforme SmartCampus</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="cards-grid">
            <div class="card stat-card">
                <div class="stat-icon blue">👨‍🎓</div>
                <div><div class="stat-value"><?= $nb_etudiants ?></div><div class="stat-label">Étudiants</div></div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon green">👨‍🏫</div>
                <div><div class="stat-value"><?= $nb_enseignants ?></div><div class="stat-label">Enseignants</div></div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon orange">📚</div>
                <div><div class="stat-value"><?= $nb_cours ?></div><div class="stat-label">Cours actifs</div></div>
            </div>
            <div class="card stat-card">
                <div class="stat-icon red">✍️</div>
                <div><div class="stat-value"><?= $nb_inscriptions ?></div><div class="stat-label">Inscriptions</div></div>
            </div>
        </div>

        <!-- Graphiques -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
            <div class="card">
                <h3 style="margin-bottom:16px;">📊 Étudiants par filière</h3>
                <canvas id="chartFilieres" height="200"></canvas>
            </div>
            <div class="card">
                <h3 style="margin-bottom:16px;">🎯 Résultats globaux</h3>
                <canvas id="chartResultats" height="200"></canvas>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="table-card">
                <div class="table-card-header">
                    <h3>👨‍🎓 Derniers étudiants</h3>
                    <a href="etudiants.php" class="btn btn-primary btn-sm">Gérer</a>
                </div>
                <table>
                    <thead><tr><th>Nom</th><th>Filière</th><th>Niveau</th></tr></thead>
                    <tbody>
                        <?php foreach ($derniers_etudiants as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></td>
                            <td><?= htmlspecialchars($e['filiere']) ?></td>
                            <td><span class="badge badge-blue"><?= $e['niveau'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-card">
                <div class="table-card-header">
                    <h3>📚 Remplissage des cours</h3>
                    <a href="cours.php" class="btn btn-primary btn-sm">Gérer</a>
                </div>
                <table>
                    <thead><tr><th>Cours</th><th>Inscrits/Max</th></tr></thead>
                    <tbody>
                        <?php foreach ($cours_stats as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nom']) ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;background:#f1f5f9;border-radius:99px;height:8px;">
                                        <div style="width:<?= min(100, round($c['nb_inscrits']/$c['capacite_max']*100)) ?>%;background:<?= $c['nb_inscrits'] >= $c['capacite_max'] ? '#ef4444' : '#10b981' ?>;height:8px;border-radius:99px;"></div>
                                    </div>
                                    <span style="font-size:13px;"><?= $c['nb_inscrits'] ?>/<?= $c['capacite_max'] ?></span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top:20px;">
            <h3 style="margin-bottom:16px;">⚡ Actions rapides</h3>
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <a href="etudiants.php" class="btn btn-primary">+ Ajouter un étudiant</a>
                <a href="enseignants.php" class="btn btn-primary">+ Ajouter un enseignant</a>
                <a href="cours.php" class="btn btn-primary">+ Créer un cours</a>
                <a href="inscriptions.php" class="btn btn-outline">Gérer les inscriptions</a>
            </div>
        </div>
    </main>
</div>

<script>
// Graphique filières
new Chart(document.getElementById('chartFilieres'), {
    type: 'doughnut',
    data: {
        labels: <?= $filiere_labels ?>,
        datasets: [{
            data: <?= $filiere_data ?>,
            backgroundColor: ['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6'],
            borderWidth: 0
        }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});

// Graphique résultats
new Chart(document.getElementById('chartResultats'), {
    type: 'bar',
    data: {
        labels: <?= $resultat_labels ?>,
        datasets: [{
            label: 'Nombre d\'étudiants',
            data: <?= $resultat_data ?>,
            backgroundColor: ['#10b981','#ef4444','#f59e0b'],
            borderRadius: 8
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});
</script>
</body>
</html>
