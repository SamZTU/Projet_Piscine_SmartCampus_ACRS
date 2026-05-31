<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('etudiant');

$etudiant_id = $_SESSION['etudiant_id'];
$message = '';
$erreur  = '';

// Inscription
if (isset($_GET['inscrire'])) {
    $cours_id = (int)$_GET['inscrire'];

    // Règle métier 1 : pas de double inscription
    $check = $pdo->prepare("SELECT id FROM inscriptions WHERE etudiant_id = ? AND cours_id = ?");
    $check->execute([$etudiant_id, $cours_id]);
    if ($check->fetch()) {
        $erreur = "Vous êtes déjà inscrit à ce cours.";
    } else {
        // Règle métier 2 : capacité maximale
        $cap = $pdo->prepare("SELECT c.capacite_max, COUNT(i.id) as nb FROM cours c LEFT JOIN inscriptions i ON i.cours_id = c.id AND i.statut='actif' WHERE c.id = ? GROUP BY c.id");
        $cap->execute([$cours_id]);
        $data = $cap->fetch();
        if ($data && $data['nb'] >= $data['capacite_max']) {
            $erreur = "Ce cours est complet.";
        } else {
            $pdo->prepare("INSERT INTO inscriptions (etudiant_id, cours_id) VALUES (?,?)")->execute([$etudiant_id, $cours_id]);
            $message = "Inscription réussie !";
        }
    }
}

// Cours disponibles filtrés par niveau de l'étudiant
$cours_dispo = $pdo->prepare("
    SELECT c.*, u.nom as ens_nom, u.prenom as ens_prenom,
           COUNT(i.id) as nb_inscrits
    FROM cours c
    LEFT JOIN enseignants en ON en.id = c.enseignant_id
    LEFT JOIN users u ON u.id = en.user_id
    LEFT JOIN inscriptions i ON i.cours_id = c.id AND i.statut = 'actif'
    WHERE c.actif = 1
    AND c.id NOT IN (
        SELECT cours_id FROM inscriptions WHERE etudiant_id = ?
    )
    AND c.niveau = (SELECT niveau FROM etudiants WHERE id = ?)
    GROUP BY c.id ORDER BY c.code
");
$cours_dispo->execute([$etudiant_id, $etudiant_id]);
$cours_dispo = $cours_dispo->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscriptions — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>S'inscrire à un cours ✍️</h1>
                <p>Cours disponibles pour votre niveau</p>
            </div>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

        <div class="table-card">
            <table>
                <thead><tr><th>Code</th><th>Cours</th><th>Enseignant</th><th>Semestre</th><th>Places</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($cours_dispo as $c): ?>
                    <?php $complet = $c['nb_inscrits'] >= $c['capacite_max']; ?>
                    <tr>
                        <td><code><?= $c['code'] ?></code></td>
                        <td><strong><?= htmlspecialchars($c['nom']) ?></strong></td>
                        <td><?= $c['ens_nom'] ? htmlspecialchars($c['ens_prenom'].' '.$c['ens_nom']) : '—' ?></td>
                        <td><span class="badge badge-blue"><?= $c['semestre'] ?></span></td>
                        <td>
                            <span class="badge <?= $complet ? 'badge-red' : 'badge-green' ?>">
                                <?= $c['nb_inscrits'] ?>/<?= $c['capacite_max'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($complet): ?>
                            <span style="color:#94a3b8;font-size:13px;">Complet</span>
                            <?php else: ?>
                            <a href="inscriptions.php?inscrire=<?= $c['id'] ?>" class="btn btn-primary btn-sm">S'inscrire</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cours_dispo)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px;">Aucun cours disponible pour votre niveau</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>