<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once 'includes/auth.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    header('Location: /smartcampus/pages/' . $_SESSION['role'] . '/dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom']);
    $prenom   = trim($_POST['prenom']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];
    $filiere  = $_POST['filiere'];
    $niveau   = $_POST['niveau'];

    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $error = "Tous les champs sont obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit faire au moins 6 caractères.";
    } elseif ($password !== $confirm) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $pdo->beginTransaction();
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, password, role) VALUES (?,?,?,?,'etudiant')");
                $stmt->execute([$nom, $prenom, $email, $hash]);
                $user_id = $pdo->lastInsertId();

                $numero = 'E' . date('Y') . str_pad($user_id, 4, '0', STR_PAD_LEFT);
                $pdo->prepare("INSERT INTO etudiants (user_id, numero_etudiant, filiere, niveau, annee_academique) VALUES (?,?,?,?,'2025-2026')")
                    ->execute([$user_id, $numero, $filiere, $niveau]);

                $pdo->commit();
                $success = "Compte créé ! Vous pouvez vous connecter.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Erreur lors de la création du compte.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCampus — Créer un compte</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body class="login-page">
<div class="login-container" style="max-width:480px;">
    <div class="login-logo">
        <h1>🎓 SmartCampus</h1>
        <p>Créer un compte étudiant</p>
    </div>

    <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="/smartcampus/index.php">Se connecter</a></div><?php endif; ?>

    <?php if (!$success): ?>
    <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div class="form-group">
                <label>Filière</label>
                <select name="filiere" required>
                    <option value="">-- Choisir --</option>
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
        </div>
        <div class="form-group">
            <label>Mot de passe</label>
            <input type="password" name="password" placeholder="Min. 6 caractères" required>
        </div>
        <div class="form-group">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="confirm" required>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Créer mon compte</button>
    </form>
    <?php endif; ?>

    <p style="text-align:center;margin-top:16px;font-size:14px;color:#64748b;">
        Déjà un compte ? <a href="/smartcampus/index.php">Se connecter</a>
    </p>
</div>
</body>
</html>  

