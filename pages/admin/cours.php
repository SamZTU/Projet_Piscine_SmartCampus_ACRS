<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('admin');

$message = '';
$erreur  = '';

// AJOUT cours
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $code         = trim($_POST['code']);
    $nom          = trim($_POST['nom']);
    $credits      = (int)$_POST['credits'];
    $coefficient  = (float)$_POST['coefficient'];
    $capacite_max = (int)$_POST['capacite_max'];
    $semestre     = $_POST['semestre'];
    $niveau       = $_POST['niveau'];
    $departement  = trim($_POST['departement']);
    $enseignant_id= (int)$_POST['enseignant_id'] ?: null;

    $check = $pdo->prepare("SELECT id FROM cours WHERE code = ?");
    $check->execute([$code]);
    if ($check->fetch()) {
        $erreur = "Ce code de cours existe déjà.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO cours (code, nom, credits, coefficient, capacite_max, semestre, niveau, departement, enseignant_id) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$code, $nom, $credits, $coefficient, $capacite_max, $semestre, $niveau, $departement, $enseignant_id]);
        $message = "Cours créé avec succès !";
    }
}

// SUPPRESSION
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $pdo->prepare("DELETE FROM cours WHERE id = ?")->execute([$id]);
    $message = "Cours supprimé.";
}

// Liste des enseignants pour le select
$enseignants = $pdo->query("SELECT en.id, u.nom, u.prenom FROM enseignants en JOIN users u ON u.id = en.user_id ORDER BY u.nom")->fetchAll();

// Liste des cours
$cours = $pdo->query("
    SELECT c.*, 
           u.nom as ens_nom, u.prenom as ens_prenom,
           COUNT(i.id) as nb_inscrits
    FROM cours c
    LEFT JOIN enseignants en ON en.id = c.enseignant_id
    LEFT JOIN users u ON u.id = en.user_id
    LEFT JOIN inscriptions i ON i.cours_id = c.id AND i.statut = 'actif'
    GROUP BY c.id
    ORDER BY c.code
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des cours — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Gestion des cours 📚</h1>
                <p><?= count($cours) ?> cours enregistré(s)</p>
            </div>
            <button onclick="document.getElementById('modal-cours').classList.add('open')" class="btn btn-primary">
                + Créer un cours
            </button>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Code</th><th>Nom</th><th>Enseignant</th><th>Semestre</th><th>Niveau</th><th>Inscrits/Max</th><th>Crédits</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($cours as $c): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($c['code']) ?></code></td>
                        <td><strong><?= htmlspecialchars($c['nom']) ?></strong></td>
                        <td><?= $c['ens_nom'] ? htmlspecialchars($c['ens_prenom'].' '.$c['ens_nom']) : '<span style="color:#94a3b8;">Non assigné</span>' ?></td>
                        <td><span class="badge badge-blue"><?= $c['semestre'] ?></span></td>
                        <td><?= $c['niveau'] ?></td>
                        <td>
                            <span class="badge <?= $c['nb_inscrits'] >= $c['capacite_max'] ? 'badge-red' : 'badge-green' ?>">
                                <?= $c['nb_inscrits'] ?>/<?= $c['capacite_max'] ?>
                            </span>
                        </td>
                        <td><?= $c['credits'] ?> crédits</td>
                        <td>
                            <a href="cours.php?supprimer=<?= $c['id'] ?>"
                               onclick="return confirm('Supprimer ce cours ? Toutes les inscriptions seront supprimées.')"
                               class="btn btn-danger btn-sm">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal ajout cours -->
<div class="modal-overlay" id="modal-cours">
    <div class="modal" style="max-width:600px;">
        <div class="modal-header">
            <h3>Créer un cours</h3>
            <button class="modal-close" onclick="document.getElementById('modal-cours').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ajouter">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group">
                    <label>Code du cours</label>
                    <input type="text" name="code" placeholder="ex: INFO201" required>
                </div>
                <div class="form-group">
                    <label>Nom du cours</label>
                    <input type="text" name="nom" placeholder="ex: Algorithmique" required>
                </div>
                <div class="form-group">
                    <label>Enseignant responsable</label>
                    <select name="enseignant_id">
                        <option value="">-- Non assigné --</option>
                        <?php foreach ($enseignants as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Département</label>
                    <select name="departement" required>
                        <option value="Informatique">Informatique</option>
                        <option value="Mathématiques">Mathématiques</option>
                        <option value="Physique">Physique</option>
                        <option value="Économie">Économie</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semestre</label>
                    <select name="semestre" required>
                        <option>S1</option><option>S2</option><option>S3</option>
                        <option>S4</option><option>S5</option><option>S6</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Niveau</label>
                    <select name="niveau" required>
                        <option value="L1">L1</option><option value="L2">L2</option>
                        <option value="L3">L3</option><option value="M1">M1</option><option value="M2">M2</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Crédits</label>
                    <input type="number" name="credits" value="3" min="1" max="10" required>
                </div>
                <div class="form-group">
                    <label>Coefficient</label>
                    <input type="number" name="coefficient" value="1.0" min="0.5" max="5" step="0.5" required>
                </div>
                <div class="form-group">
                    <label>Capacité maximale</label>
                    <input type="number" name="capacite_max" value="30" min="1" max="200" required>
                </div>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-cours').classList.remove('open')">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer le cours</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
