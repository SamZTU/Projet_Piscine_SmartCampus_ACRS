<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('enseignant');

$enseignant_id = $_SESSION['enseignant_id'];

$mes_cours = $pdo->prepare("
    SELECT c.*, 
           COUNT(DISTINCT i.etudiant_id) as nb_inscrits,
           COUNT(DISTINCT n.id) as nb_notes
    FROM cours c
    LEFT JOIN inscriptions i ON i.cours_id = c.id AND i.statut = 'actif'
    LEFT JOIN notes n ON n.cours_id = c.id
    WHERE c.enseignant_id = ?
    GROUP BY c.id
    ORDER BY c.semestre, c.code
");
$mes_cours->execute([$enseignant_id]);
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
                <p><?= count($mes_cours) ?> cours assigné(s)</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px;">
            <?php foreach ($mes_cours as $c): ?>
            <div class="card">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:12px;">
                    <div>
                        <code style="background:#dbeafe;color:#1e40af;padding:3px 8px;border-radius:6px;font-size:13px;"><?= $c['code'] ?></code>
                        <span class="badge badge-blue" style="margin-left:6px;"><?= $c['semestre'] ?></span>
                    </div>
                    <span class="badge badge-gray"><?= $c['niveau'] ?></span>
                </div>

                <h3 style="margin-bottom:8px;"><?= htmlspecialchars($c['nom']) ?></h3>
                <p style="color:#64748b;font-size:13px;margin-bottom:16px;"><?= $c['departement'] ?> · <?= $c['credits'] ?> crédits · coeff <?= $c['coefficient'] ?></p>

                <!-- Barre de remplissage -->
                <div style="margin-bottom:16px;">
                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px;">
                        <span>Étudiants inscrits</span>
                        <strong><?= $c['nb_inscrits'] ?>/<?= $c['capacite_max'] ?></strong>
                    </div>
                    <div style="background:#f1f5f9;border-radius:99px;height:8px;">
                        <?php $pct = $c['capacite_max'] > 0 ? min(100, round($c['nb_inscrits']/$c['capacite_max']*100)) : 0; ?>
                        <div style="width:<?= $pct ?>%;background:<?= $pct >= 100 ? '#ef4444' : '#2563eb' ?>;height:8px;border-radius:99px;transition:width .3s;"></div>
                    </div>
                </div>

                <div style="display:flex;gap:8px;">
                    <a href="notes.php?cours_id=<?= $c['id'] ?>" class="btn btn-primary btn-sm" style="flex:1;text-align:center;">📝 Notes</a>
                    <a href="etudiants.php?cours_id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" style="flex:1;text-align:center;">👥 Étudiants</a>
                    <a href="presences.php?cours_id=<?= $c['id'] ?>" class="btn btn-outline btn-sm" style="flex:1;text-align:center;">✅ Présences</a>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($mes_cours)): ?>
            <div class="card" style="text-align:center;padding:40px;color:#94a3b8;grid-column:1/-1;">
                Aucun cours assigné. Contactez l'administrateur.
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
