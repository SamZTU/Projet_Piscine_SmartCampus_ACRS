<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('etudiant');

$etudiant_id = $_SESSION['etudiant_id'];
$user_id     = $_SESSION['user_id'];
$message = '';
$erreur  = '';

// Dossier upload
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/smartcampus/assets/photos/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Upload photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['photo']['name'])) {
    $file    = $_FILES['photo'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($file['type'], $allowed)) {
        $erreur = "Format non autorisé. Utilisez JPG, PNG ou GIF.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $erreur = "Image trop lourde (max 2 Mo).";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $erreur = "Erreur lors de l'upload (code ".$file['error'].").";
    } else {
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = 'photo_' . $user_id . '.' . $ext;
        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            $pdo->prepare("UPDATE users SET photo = ? WHERE id = ?")->execute([$filename, $user_id]);
            $_SESSION['photo'] = $filename;
            $message = "Photo mise à jour !";
        } else {
            $erreur = "Erreur lors de l'enregistrement. Vérifiez les permissions du dossier.";
        }
    }
}

// Modification infos personnelles
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $telephone     = trim($_POST['telephone']);
    $adresse       = trim($_POST['adresse']);
    $date_naissance= trim($_POST['date_naissance']);

    $pdo->prepare("UPDATE etudiants SET telephone=?, adresse=?, date_naissance=? WHERE id=?")
        ->execute([$telephone, $adresse, $date_naissance ?: null, $etudiant_id]);
    $message = "Informations mises à jour !";
}

// Changement mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    $ancien  = $_POST['ancien_mdp'];
    $nouveau = $_POST['nouveau_mdp'];
    $confirm = $_POST['confirm_mdp'];

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!password_verify($ancien, $user['password'])) {
        $erreur = "Ancien mot de passe incorrect.";
    } elseif (strlen($nouveau) < 6) {
        $erreur = "Le nouveau mot de passe doit faire au moins 6 caractères.";
    } elseif ($nouveau !== $confirm) {
        $erreur = "Les mots de passe ne correspondent pas.";
    } else {
        $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")
            ->execute([password_hash($nouveau, PASSWORD_DEFAULT), $user_id]);
        $message = "Mot de passe modifié !";
    }
}

// Récupère toutes les infos
$stmt = $pdo->prepare("
    SELECT u.nom, u.prenom, u.email, u.photo,
           e.numero_etudiant, e.filiere, e.niveau,
           e.date_naissance, e.telephone, e.adresse, e.annee_academique, e.statut
    FROM users u JOIN etudiants e ON e.user_id = u.id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$profil = $stmt->fetch();

// Stats rapides
$nb_cours = $pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE etudiant_id = ? AND statut='actif'");
$nb_cours->execute([$etudiant_id]);
$nb_cours = $nb_cours->fetchColumn();

$moyenne = $pdo->prepare("SELECT AVG(moyenne) FROM notes WHERE etudiant_id = ? AND moyenne IS NOT NULL");
$moyenne->execute([$etudiant_id]);
$moyenne = round((float)$moyenne->fetchColumn(), 2);

$photoUrl = !empty($profil['photo'])
    ? '/smartcampus/assets/photos/' . htmlspecialchars($profil['photo'])
    : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
    <style>
        .profil-header {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            border-radius: 16px;
            padding: 32px;
            color: white;
            display: flex;
            align-items: center;
            gap: 28px;
            margin-bottom: 24px;
        }
        .avatar-wrapper { position: relative; cursor: pointer; }
        .avatar {
            width: 110px; height: 110px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.4);
            object-fit: cover;
            display: flex; align-items: center; justify-content: center;
            font-size: 44px;
            background: rgba(255,255,255,0.15);
            overflow: hidden;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .avatar-overlay {
            position: absolute; inset: 0;
            background: rgba(0,0,0,.45);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity .2s;
            font-size: 22px;
        }
        .avatar-wrapper:hover .avatar-overlay { opacity: 1; }
        .profil-name { font-size: 26px; font-weight: 700; margin-bottom: 4px; }
        .profil-sub  { opacity: .8; font-size: 14px; margin-bottom: 6px; }
        .profil-stats { display: flex; gap: 20px; margin-top: 12px; }
        .profil-stat  { text-align: center; }
        .profil-stat strong { display: block; font-size: 22px; }
        .profil-stat span   { font-size: 12px; opacity: .75; }
        .section-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 20px; }
        .section-card h3 { margin-bottom: 20px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-item label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; display: block; margin-bottom: 4px; }
        .info-item p { font-size: 15px; color: #1e293b; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

        <!-- Header -->
        <div class="profil-header">
            <!-- Avatar cliquable -->
            <form method="POST" enctype="multipart/form-data" id="form-photo">
                <input type="file" name="photo" id="input-photo" accept="image/jpeg,image/png,image/gif,image/webp"
                       style="display:none;" onchange="document.getElementById('form-photo').submit()">
                <div class="avatar-wrapper" onclick="document.getElementById('input-photo').click()">
                    <div class="avatar">
                        <?php if ($photoUrl): ?>
                            <img src="<?= $photoUrl ?>?v=<?= time() ?>" alt="Photo">
                        <?php else: ?>
                            👤
                        <?php endif; ?>
                    </div>
                    <div class="avatar-overlay">📷</div>
                </div>
            </form>

            <div style="flex:1;">
                <div class="profil-name"><?= htmlspecialchars($profil['prenom'].' '.$profil['nom']) ?></div>
                <div class="profil-sub">📧 <?= htmlspecialchars($profil['email']) ?></div>
                <div class="profil-sub">
                    🎓 <?= htmlspecialchars($profil['filiere']) ?> · <?= $profil['niveau'] ?> · <?= $profil['annee_academique'] ?>
                </div>
                <div class="profil-sub">🪪 N° <?= $profil['numero_etudiant'] ?></div>

                <div class="profil-stats">
                    <div class="profil-stat">
                        <strong><?= $nb_cours ?></strong>
                        <span>Cours</span>
                    </div>
                    <div class="profil-stat">
                        <strong><?= $moyenne ?: '—' ?></strong>
                        <span>Moyenne</span>
                    </div>
                    <div class="profil-stat">
                        <strong>
                            <span class="badge <?= $profil['statut'] === 'actif' ? 'badge-green' : 'badge-red' ?>">
                                <?= ucfirst($profil['statut']) ?>
                            </span>
                        </strong>
                        <span>Statut</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

            <!-- Infos personnelles -->
            <div class="section-card">
                <h3>👤 Informations personnelles</h3>
                <div class="info-grid" style="margin-bottom:20px;">
                    <div class="info-item">
                        <label>Nom complet</label>
                        <p><?= htmlspecialchars($profil['prenom'].' '.$profil['nom']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <p><?= htmlspecialchars($profil['email']) ?></p>
                    </div>
                    <div class="info-item">
                        <label>Date de naissance</label>
                        <p><?= $profil['date_naissance'] ? date('d/m/Y', strtotime($profil['date_naissance'])) : '—' ?></p>
                    </div>
                    <div class="info-item">
                        <label>Téléphone</label>
                        <p><?= htmlspecialchars($profil['telephone'] ?: '—') ?></p>
                    </div>
                    <div class="info-item" style="grid-column:1/-1;">
                        <label>Adresse</label>
                        <p><?= htmlspecialchars($profil['adresse'] ?: '—') ?></p>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <div class="form-group">
                        <label>Téléphone</label>
                        <input type="text" name="telephone" value="<?= htmlspecialchars($profil['telephone'] ?? '') ?>" placeholder="06 00 00 00 00">
                    </div>
                    <div class="form-group">
                        <label>Date de naissance</label>
                        <input type="date" name="date_naissance" value="<?= $profil['date_naissance'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label>Adresse</label>
                        <input type="text" name="adresse" value="<?= htmlspecialchars($profil['adresse'] ?? '') ?>" placeholder="15 rue des Universités, 75007 Paris">
                    </div>
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                </form>
            </div>

            <div style="display:flex;flex-direction:column;gap:20px;">
                <!-- Infos académiques -->
                <div class="section-card">
                    <h3>🎓 Informations académiques</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>N° Étudiant</label>
                            <p><code><?= $profil['numero_etudiant'] ?></code></p>
                        </div>
                        <div class="info-item">
                            <label>Statut</label>
                            <p><span class="badge <?= $profil['statut'] === 'actif' ? 'badge-green' : 'badge-red' ?>"><?= ucfirst($profil['statut']) ?></span></p>
                        </div>
                        <div class="info-item">
                            <label>Filière</label>
                            <p><?= htmlspecialchars($profil['filiere']) ?></p>
                        </div>
                        <div class="info-item">
                            <label>Niveau</label>
                            <p><span class="badge badge-blue"><?= $profil['niveau'] ?></span></p>
                        </div>
                        <div class="info-item">
                            <label>Année académique</label>
                            <p><?= $profil['annee_academique'] ?></p>
                        </div>
                        <div class="info-item">
                            <label>Moyenne générale</label>
                            <p style="font-size:18px;font-weight:700;color:<?= $moyenne >= 10 ? '#10b981' : '#ef4444' ?>">
                                <?= $moyenne ? $moyenne.'/20' : '—' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Changer mot de passe -->
                <div class="section-card">
                    <h3>🔒 Changer le mot de passe</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="password">
                        <div class="form-group">
                            <label>Ancien mot de passe</label>
                            <input type="password" name="ancien_mdp" required>
                        </div>
                        <div class="form-group">
                            <label>Nouveau mot de passe</label>
                            <input type="password" name="nouveau_mdp" placeholder="Min. 6 caractères" required>
                        </div>
                        <div class="form-group">
                            <label>Confirmer</label>
                            <input type="password" name="confirm_mdp" required>
                        </div>
                        <button type="submit" class="btn btn-primary">🔒 Modifier</button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>
</body>
</html>
