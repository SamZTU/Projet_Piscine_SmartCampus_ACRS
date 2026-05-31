<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('admin');

$message = '';
$erreur  = '';

// AJOUT inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'inscrire') {
    $etudiant_id = (int)$_POST['etudiant_id'];
    $cours_id    = (int)$_POST['cours_id'];

    // Règle métier 1 : pas de double inscription
    $check = $pdo->prepare("SELECT id FROM inscriptions WHERE etudiant_id = ? AND cours_id = ?");
    $check->execute([$etudiant_id, $cours_id]);
    if ($check->fetch()) {
        $erreur = "Cet étudiant est déjà inscrit à ce cours.";
    } else {
        // Règle métier 2 : capacité maximale
        $cap = $pdo->prepare("SELECT c.capacite_max, COUNT(i.id) as nb FROM cours c LEFT JOIN inscriptions i ON i.cours_id = c.id AND i.statut='actif' WHERE c.id = ? GROUP BY c.id");
        $cap->execute([$cours_id]);
        $data = $cap->fetch();
        if ($data && $data['nb'] >= $data['capacite_max']) {
            $erreur = "Ce cours est complet (capacité maximale atteinte).";
        } else {
            $pdo->prepare("INSERT INTO inscriptions (etudiant_id, cours_id) VALUES (?,?)")->execute([$etudiant_id, $cours_id]);
            $message = "Inscription effectuée avec succès !";
        }
    }
}

// SUPPRESSION inscription
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $pdo->prepare("DELETE FROM inscriptions WHERE id = ?")->execute([$id]);
    $message = "Inscription supprimée.";
}

$etudiants   = $pdo->query("SELECT e.id, u.nom, u.prenom FROM etudiants e JOIN users u ON u.id = e.user_id ORDER BY u.nom")->fetchAll();
$cours_liste = $pdo->query("SELECT id, code, nom, capacite_max FROM cours WHERE actif = 1 ORDER BY code")->fetchAll();

$inscriptions = $pdo->query("
    SELECT i.id, i.date_inscription, i.statut,
           u.nom, u.prenom, c.nom as cours_nom, c.code as cours_code
    FROM inscriptions i
    JOIN etudiants e ON e.id = i.etudiant_id
    JOIN users u ON u.id = e.user_id
    JOIN cours c ON c.id = i.cours_id
    ORDER BY i.date_inscription DESC
")->fetchAll();
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
                <h1>Gestion des inscriptions ✍️</h1>
                <p><?= count($inscriptions) ?> inscription(s)</p>
            </div>
            <button onclick="document.getElementById('modal-insc').classList.add('open')" class="btn btn-primary">
                + Nouvelle inscription
            </button>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

        <div class="table-card">
            <table>
                <thead><tr><th>Étudiant</th><th>Cours</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($inscriptions as $i): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($i['prenom'].' '.$i['nom']) ?></strong></td>
                        <td><code><?= $i['cours_code'] ?></code> — <?= htmlspecialchars($i['cours_nom']) ?></td>
                        <td><?= date('d/m/Y', strtotime($i['date_inscription'])) ?></td>
                        <td><span class="badge <?= $i['statut'] === 'actif' ? 'badge-green' : 'badge-gray' ?>"><?= ucfirst($i['statut']) ?></span></td>
                        <td>
                            <a href="inscriptions.php?supprimer=<?= $i['id'] ?>"
                               onclick="return confirm('Supprimer cette inscription ?')"
                               class="btn btn-danger btn-sm">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div class="modal-overlay" id="modal-insc">
    <div class="modal">
        <div class="modal-header">
            <h3>Nouvelle inscription</h3>
            <button class="modal-close" onclick="document.getElementById('modal-insc').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="inscrire">
            <div class="form-group">
                <label>Étudiant</label>
                <select name="etudiant_id" required>
                    <option value="">-- Choisir un étudiant --</option>
                    <?php foreach ($etudiants as $e): ?>
                    <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Cours</label>
                <select name="cours_id" required>
                    <option value="">-- Choisir un cours --</option>
                    <?php foreach ($cours_liste as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['code'].' - '.$c['nom']) ?> (max <?= $c['capacite_max'] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-insc').classList.remove('open')">Annuler</button>
                <button type="submit" class="btn btn-primary">Inscrire</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
