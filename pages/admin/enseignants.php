<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('admin');

$message = '';
$erreur  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $nom        = trim($_POST['nom']);
    $prenom     = trim($_POST['prenom']);
    $email      = trim($_POST['email']);
    $departement= trim($_POST['departement']);
    $grade      = trim($_POST['grade']);
    $password   = password_hash('password', PASSWORD_DEFAULT);

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $erreur = "Cet email est déjà utilisé.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?,?,?,?,'enseignant')");
            $stmt->execute([$nom, $prenom, $email, $password]);
            $user_id = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO enseignants (user_id, departement, grade) VALUES (?,?,?)")->execute([$user_id, $departement, $grade]);
            $pdo->commit();
            $message = "Enseignant ajouté ! Mot de passe par défaut : password";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erreur = "Erreur lors de l'ajout.";
        }
    }
}

if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $row = $pdo->prepare("SELECT user_id FROM enseignants WHERE id = ?");
    $row->execute([$id]);
    $en = $row->fetch();
    if ($en) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$en['user_id']]);
        $message = "Enseignant supprimé.";
    }
}

$enseignants = $pdo->query("
    SELECT en.id, en.departement, en.grade, u.nom, u.prenom, u.email,
           COUNT(c.id) as nb_cours
    FROM enseignants en
    JOIN users u ON u.id = en.user_id
    LEFT JOIN cours c ON c.enseignant_id = en.id
    GROUP BY en.id ORDER BY u.nom
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Enseignants — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Gestion des enseignants 👨‍🏫</h1>
                <p><?= count($enseignants) ?> enseignant(s)</p>
            </div>
            <button onclick="document.getElementById('modal-ens').classList.add('open')" class="btn btn-primary">
                + Ajouter un enseignant
            </button>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

        <div class="table-card">
            <table>
                <thead><tr><th>Nom</th><th>Email</th><th>Département</th><th>Grade</th><th>Cours</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($enseignants as $e): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($e['email']) ?></td>
                        <td><?= htmlspecialchars($e['departement']) ?></td>
                        <td><span class="badge badge-blue"><?= htmlspecialchars($e['grade']) ?></span></td>
                        <td><?= $e['nb_cours'] ?> cours</td>
                        <td>
                            <a href="enseignants.php?supprimer=<?= $e['id'] ?>"
                               onclick="return confirm('Supprimer cet enseignant ?')"
                               class="btn btn-danger btn-sm">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<div class="modal-overlay" id="modal-ens">
    <div class="modal">
        <div class="modal-header">
            <h3>Ajouter un enseignant</h3>
            <button class="modal-close" onclick="document.getElementById('modal-ens').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ajouter">
            <div class="form-group"><label>Prénom</label><input type="text" name="prenom" required></div>
            <div class="form-group"><label>Nom</label><input type="text" name="nom" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
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
                <label>Grade</label>
                <select name="grade" required>
                    <option value="Professeur">Professeur</option>
                    <option value="Maître de conférences">Maître de conférences</option>
                    <option value="Maître assistant">Maître assistant</option>
                    <option value="Vacataire">Vacataire</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-ens').classList.remove('open')">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
