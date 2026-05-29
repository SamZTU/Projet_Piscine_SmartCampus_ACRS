<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$error = '';

// Si déjà connecté, redirige vers le bon dashboard
if (isLoggedIn()) {
    header('Location: /smartcampus/pages/' . $_SESSION['role'] . '/dashboard.php');
    exit();
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['nom']      = $user['nom'];
            $_SESSION['prenom']   = $user['prenom'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];

            // Récupère l'id spécifique selon le rôle
            if ($user['role'] === 'etudiant') {
                $stmt2 = $pdo->prepare("SELECT id FROM etudiants WHERE user_id = ?");
                $stmt2->execute([$user['id']]);
                $etudiant = $stmt2->fetch();
                $_SESSION['etudiant_id'] = $etudiant['id'] ?? null;
            } elseif ($user['role'] === 'enseignant') {
                $stmt2 = $pdo->prepare("SELECT id FROM enseignants WHERE user_id = ?");
                $stmt2->execute([$user['id']]);
                $enseignant = $stmt2->fetch();
                $_SESSION['enseignant_id'] = $enseignant['id'] ?? null;
            }

            header('Location: /smartcampus/pages/' . $user['role'] . '/dashboard.php');
            exit();
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartCampus — Connexion</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
</head>
<body class="login-page">

<div class="login-container">
    <div class="login-logo">
        <h1>🎓 SmartCampus</h1>
        <p>Gérer · Apprendre · Réussir</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email" 
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                   placeholder="votre@email.fr" required>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" 
                   placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
    </form>

    <div class="login-hints">
        <p><strong>Comptes de test :</strong></p>
        <p>Admin : admin@smartcampus.fr</p>
        <p>Enseignant : p.dubois@smartcampus.fr</p>
        <p>Étudiant : emma.martin@etu.smartcampus.fr</p>
        <p>Mot de passe : <strong>password123</strong></p>
        <p style="text-align:center;margin-top:12px;font-size:14px;">
    Pas encore de compte ? <a href="/smartcampus/register.php">S'inscrire</a>
</p>
    </div>
</div>

</body>
</html>

