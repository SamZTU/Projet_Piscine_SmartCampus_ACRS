<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('admin');

$message = '';
$erreur  = '';

// AJOUT créneau
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajouter') {
    $cours_id    = (int)$_POST['cours_id'];
    $jour        = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin   = $_POST['heure_fin'];
    $salle       = trim($_POST['salle']);

    // Règle métier : détection conflit (même salle, même jour, même horaire)
    $conflit = $pdo->prepare("
        SELECT id FROM emploi_du_temps 
        WHERE salle = ? AND jour = ? 
        AND ((heure_debut <= ? AND heure_fin > ?) OR (heure_debut < ? AND heure_fin >= ?))
    ");
    $conflit->execute([$salle, $jour, $heure_debut, $heure_debut, $heure_fin, $heure_fin]);
    if ($conflit->fetch()) {
        $erreur = "Conflit détecté : cette salle est déjà occupée sur ce créneau.";
    } else {
        $pdo->prepare("INSERT INTO emploi_du_temps (cours_id, jour, heure_debut, heure_fin, salle) VALUES (?,?,?,?,?)")
            ->execute([$cours_id, $jour, $heure_debut, $heure_fin, $salle]);
        $message = "Créneau ajouté !";
    }
}

// SUPPRESSION
if (isset($_GET['supprimer'])) {
    $pdo->prepare("DELETE FROM emploi_du_temps WHERE id = ?")->execute([(int)$_GET['supprimer']]);
    $message = "Créneau supprimé.";
}

$cours_liste = $pdo->query("SELECT id, code, nom FROM cours WHERE actif = 1 ORDER BY code")->fetchAll();

$emploi = $pdo->query("
    SELECT e.*, c.nom as cours_nom, c.code as cours_code,
           u.nom as ens_nom, u.prenom as ens_prenom
    FROM emploi_du_temps e
    JOIN cours c ON c.id = e.cours_id
    LEFT JOIN enseignants en ON en.id = c.enseignant_id
    LEFT JOIN users u ON u.id = en.user_id
    ORDER BY FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'), e.heure_debut
")->fetchAll();

$jours = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi'];
$par_jour = [];
foreach ($emploi as $e) $par_jour[$e['jour']][] = $e;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
    <style>
        .edt-grid { display:grid; grid-template-columns: repeat(5,1fr); gap:12px; margin-bottom:24px; }
        .edt-jour { background:#fff; border-radius:10px; border:1px solid #e2e8f0; overflow:hidden; }
        .edt-jour-header { background:#1e293b; color:#fff; padding:10px; text-align:center; font-weight:700; font-size:14px; }
        .edt-seance { padding:10px 12px; border-bottom:1px solid #f1f5f9; font-size:13px; }
        .edt-seance:last-child { border-bottom:none; }
        .edt-heure { color:#64748b; font-size:11px; }
        .edt-cours { font-weight:700; }
        .edt-vide { padding:16px; text-align:center; color:#cbd5e1; font-size:13px; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div><h1>Emploi du temps 📅</h1><p>Gestion des créneaux</p></div>
            <button onclick="document.getElementById('modal-edt').classList.add('open')" class="btn btn-primary">+ Ajouter un créneau</button>
        </div>

        <?php if ($message): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($erreur):  ?><div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>

        <div class="edt-grid">
            <?php foreach ($jours as $jour): ?>
            <div class="edt-jour">
                <div class="edt-jour-header"><?= $jour ?></div>
                <?php if (!empty($par_jour[$jour])): ?>
                    <?php foreach ($par_jour[$jour] as $s): ?>
                    <div class="edt-seance">
                        <div class="edt-heure"><?= substr($s['heure_debut'],0,5) ?>–<?= substr($s['heure_fin'],0,5) ?> · <?= $s['salle'] ?></div>
                        <div class="edt-cours"><?= htmlspecialchars($s['cours_nom']) ?></div>
                        <a href="emploi_du_temps.php?supprimer=<?= $s['id'] ?>" onclick="return confirm('Supprimer ?')" style="font-size:11px;color:#ef4444;">Supprimer</a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="edt-vide">Libre</div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>
</div>

<div class="modal-overlay" id="modal-edt">
    <div class="modal">
        <div class="modal-header">
            <h3>Ajouter un créneau</h3>
            <button class="modal-close" onclick="document.getElementById('modal-edt').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="ajouter">
            <div class="form-group">
                <label>Cours</label>
                <select name="cours_id" required>
                    <?php foreach ($cours_liste as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['code'].' - '.$c['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jour</label>
                <select name="jour" required>
                    <?php foreach ($jours as $j): ?><option><?= $j ?></option><?php endforeach; ?>
                </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group"><label>Heure début</label><input type="time" name="heure_debut" required></div>
                <div class="form-group"><label>Heure fin</label><input type="time" name="heure_fin" required></div>
            </div>
            <div class="form-group"><label>Salle</label><input type="text" name="salle" placeholder="ex: B105" required></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-edt').classList.remove('open')">Annuler</button>
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
