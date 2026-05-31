<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('admin');

$message = '';
$erreur  = '';

// AJOUT étudiant
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom      = trim($_POST['nom']);
    $prenom   = trim($_POST['prenom']);
    $email    = trim($_POST['email']);
    $filiere  = trim($_POST['filiere']);
    $niveau   = $_POST['niveau'];
    $password = password_hash('password', PASSWORD_DEFAULT);

    // Vérifie si email déjà utilisé
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        $erreur = "Cet email est déjà utilisé.";
    } else {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?,?,?,?,'etudiant')");
            $stmt->execute([$nom, $prenom, $email, $password]);
            $user_id = $pdo->lastInsertId();

            $numero = 'E' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            $stmt2  = $pdo->prepare("INSERT INTO etudiants (user_id, numero_etudiant, filiere, niveau, annee_academique) VALUES (?,?,?,?,'2025-2026')");
            $stmt2->execute([$user_id, $numero, $filiere, $niveau]);

            $pdo->commit();
            $message = "Étudiant ajouté avec succès ! Mot de passe par défaut : password";
        } catch (Exception $e) {
            $pdo->rollBack();
            $erreur = "Erreur lors de l'ajout.";
        }
    }
}

// SUPPRESSION
if (isset($_GET['supprimer'])) {
    $id = (int)$_GET['supprimer'];
    $stmt = $pdo->prepare("SELECT user_id FROM etudiants WHERE id = ?");
    $stmt->execute([$id]);
    $e = $stmt->fetch();
    if ($e) {
        $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$e['user_id']]);
        $message = "Étudiant supprimé.";
    }
}

// Liste étudiants
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("
        SELECT e.id, e.numero_etudiant, e.filiere, e.niveau, e.statut,
               u.nom, u.prenom, u.email
        FROM etudiants e JOIN users u ON u.id = e.user_id
        WHERE u.nom LIKE ? OR u.prenom LIKE ? OR e.numero_etudiant LIKE ?
        ORDER BY u.nom
    ");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("
        SELECT e.id, e.numero_etudiant, e.filiere, e.niveau, e.statut,
               u.nom, u.prenom, u.email
        FROM etudiants e JOIN users u ON u.id = e.user_id
        ORDER BY u.nom
    ");
}
$etudiants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Étudiants — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Gestion des étudiants 👨‍🎓</h1>
                <p><?= count($etudiants) ?> étudiant(s) enregistré(s)</p>
            </div>
            <button onclick="document.getElementById('modal-ajout').classList.add('open')" class="btn btn-primary">
                + Ajouter un étudiant
            </button>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

        <!-- Recherche -->
        <form method="GET" style="margin-bottom:20px;display:flex;gap:10px;">
            <input type="text" name="search" placeholder="Rechercher un étudiant..." value="<?= htmlspecialchars($search) ?>" style="max-width:300px;">
            <button type="submit" class="btn btn-outline">🔍 Rechercher</button>
            <?php if ($search): ?><a href="etudiants.php" class="btn btn-outline">✕ Effacer</a><?php endif; ?>
        </form>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>N° Étudiant</th>
                        <th>Nom complet</th>
                        <th>Email</th>
                        <th>Filière</th>
                        <th>Niveau</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($etudiants as $e): ?>
                    <tr>
                        <td><code><?= $e['numero_etudiant'] ?></code></td>
                        <td><strong><?= htmlspecialchars($e['prenom'].' '.$e['nom']) ?></strong></td>
                        <td><?= htmlspecialchars($e['email']) ?></td>
                        <td><?= htmlspecialchars($e['filiere']) ?></td>
                        <td><span class="badge badge-blue"><?= $e['niveau'] ?></span></td>
                        <td>
                            <span class="badge <?= $e['statut'] === 'actif' ? 'badge-green' : 'badge-red' ?>">
                                <?= ucfirst($e['statut']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="etudiants.php?supprimer=<?= $e['id'] ?>"
                               onclick="return confirm('Supprimer cet étudiant ?')"
                               class="btn btn-danger btn-sm">Supprimer</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($etudiants)): ?>
                    <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:32px;">Aucun étudiant trouvé</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal ajout -->
<div class="modal-overlay" id="modal-ajout">
    <div class="modal">
        <div class="modal-header">
            <h3>Ajouter un étudiant</h3>
            <button class="modal-close" onclick="document.getElementById('modal-ajout').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ajouter">
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" required>
            </div>
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Filière</label>
                <select name="filiere" required>
                    <option value="Informatique">Informatique</option>
                    <option value="Mathématiques">Mathématiques</option>
                    <option value="Physique">Physique</option>
                    <option value="Économie">Économie</option>
                </select>
            </div>
            <div class="form-group">
                <label>Niveau</label>
                <select name="niveau" required>
                    <option value="L1">L1</option>
                    <option value="L2">L2</option>
                    <option value="L3">L3</option>
                    <option value="M1">M1</option>
                    <option value="M2">M2</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-ajout').classList.remove('open')">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
