<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../includes/auth.php';
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$message_ok = '';

// Envoi message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'envoyer') {
    $destinataire_id = (int)$_POST['destinataire_id'];
    $sujet   = trim($_POST['sujet']);
    $contenu = trim($_POST['contenu']);

    if ($destinataire_id && $sujet && $contenu) {
        $pdo->prepare("INSERT INTO messages (expediteur_id, destinataire_id, sujet, contenu) VALUES (?,?,?,?)")
            ->execute([$user_id, $destinataire_id, $sujet, $contenu]);
        $message_ok = "Message envoyé !";
    }
}

// Marquer comme lu
if (isset($_GET['lire'])) {
    $pdo->prepare("UPDATE messages SET lu = 1 WHERE id = ? AND destinataire_id = ?")
        ->execute([(int)$_GET['lire'], $user_id]);
}

// Messages reçus
$recus = $pdo->prepare("
    SELECT m.*, u.nom as exp_nom, u.prenom as exp_prenom, u.role as exp_role
    FROM messages m
    JOIN users u ON u.id = m.expediteur_id
    WHERE m.destinataire_id = ?
    ORDER BY m.created_at DESC
");
$recus->execute([$user_id]);
$recus = $recus->fetchAll();

// Messages envoyés
$envoyes = $pdo->prepare("
    SELECT m.*, u.nom as dest_nom, u.prenom as dest_prenom
    FROM messages m
    JOIN users u ON u.id = m.destinataire_id
    WHERE m.expediteur_id = ?
    ORDER BY m.created_at DESC
");
$envoyes->execute([$user_id]);
$envoyes = $envoyes->fetchAll();

// Destinataires possibles
$destinataires = $pdo->prepare("SELECT id, nom, prenom, role FROM users WHERE id != ? ORDER BY role, nom");
$destinataires->execute([$user_id]);
$destinataires = $destinataires->fetchAll();

$nb_non_lus = count(array_filter($recus, fn($m) => !$m['lu']));

$onglet = $_GET['onglet'] ?? 'recus';
$msg_ouvert = isset($_GET['lire']) ? (int)$_GET['lire'] : null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Messagerie — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
    <style>
        .msg-list { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; background: white; }
        .msg-item { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background .15s; display: flex; justify-content: space-between; align-items: center; }
        .msg-item:hover { background: #f8fafc; }
        .msg-item.non-lu { background: #eff6ff; border-left: 4px solid #2563eb; }
        .msg-item:last-child { border-bottom: none; }
        .msg-detail { background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 28px; margin-top: 16px; }
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: #f1f5f9; padding: 4px; border-radius: 10px; width: fit-content; }
        .tab { padding: 8px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; border: none; background: none; transition: all .15s; }
        .tab.active { background: white; box-shadow: 0 1px 4px rgba(0,0,0,.1); color: #2563eb; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include '../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Messagerie 💬</h1>
                <p><?= $nb_non_lus ?> message(s) non lu(s)</p>
            </div>
            <button onclick="document.getElementById('modal-msg').classList.add('open')" class="btn btn-primary">
                ✉️ Nouveau message
            </button>
        </div>

        <?php if ($message_ok): ?><div class="alert alert-success"><?= $message_ok ?></div><?php endif; ?>

        <!-- Onglets -->
        <div class="tabs">
            <a href="?onglet=recus"><button class="tab <?= $onglet === 'recus' ? 'active' : '' ?>">
                📥 Reçus <?= $nb_non_lus > 0 ? "($nb_non_lus)" : '' ?>
            </button></a>
            <a href="?onglet=envoyes"><button class="tab <?= $onglet === 'envoyes' ? 'active' : '' ?>">
                📤 Envoyés
            </button></a>
        </div>

        <?php if ($onglet === 'recus'): ?>
        <div class="msg-list">
            <?php foreach ($recus as $m): ?>
            <div class="msg-item <?= !$m['lu'] ? 'non-lu' : '' ?>"
                 onclick="window.location='?onglet=recus&lire=<?= $m['id'] ?>'">
                <div>
                    <strong><?= htmlspecialchars($m['exp_prenom'].' '.$m['exp_nom']) ?></strong>
                    <span class="badge badge-<?= $m['exp_role'] === 'admin' ? 'red' : ($m['exp_role'] === 'enseignant' ? 'blue' : 'green') ?>" style="margin-left:8px;">
                        <?= ucfirst($m['exp_role']) ?>
                    </span><br>
                    <span style="font-size:14px;"><?= htmlspecialchars($m['sujet']) ?></span>
                </div>
                <div style="text-align:right;font-size:12px;color:#94a3b8;">
                    <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
                    <?php if (!$m['lu']): ?><br><span style="color:#2563eb;font-weight:700;">●</span><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recus)): ?>
            <div style="padding:40px;text-align:center;color:#94a3b8;">Aucun message reçu</div>
            <?php endif; ?>
        </div>

        <?php if ($msg_ouvert): ?>
        <?php $msg = array_values(array_filter($recus, fn($m) => $m['id'] === $msg_ouvert))[0] ?? null; ?>
        <?php if ($msg): ?>
        <div class="msg-detail">
            <h3 style="margin-bottom:8px;"><?= htmlspecialchars($msg['sujet']) ?></h3>
            <p style="color:#64748b;font-size:13px;margin-bottom:20px;">
                De : <strong><?= htmlspecialchars($msg['exp_prenom'].' '.$msg['exp_nom']) ?></strong> ·
                <?= date('d/m/Y à H:i', strtotime($msg['created_at'])) ?>
            </p>
            <div style="line-height:1.7;white-space:pre-wrap;"><?= htmlspecialchars($msg['contenu']) ?></div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php else: ?>
        <div class="msg-list">
            <?php foreach ($envoyes as $m): ?>
            <div class="msg-item">
                <div>
                    <strong>À : <?= htmlspecialchars($m['dest_prenom'].' '.$m['dest_nom']) ?></strong><br>
                    <span style="font-size:14px;"><?= htmlspecialchars($m['sujet']) ?></span>
                </div>
                <div style="text-align:right;font-size:12px;color:#94a3b8;">
                    <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?><br>
                    <span class="badge <?= $m['lu'] ? 'badge-green' : 'badge-gray' ?>"><?= $m['lu'] ? 'Lu' : 'Non lu' ?></span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($envoyes)): ?>
            <div style="padding:40px;text-align:center;color:#94a3b8;">Aucun message envoyé</div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- Modal nouveau message -->
<div class="modal-overlay" id="modal-msg">
    <div class="modal" style="max-width:540px;">
        <div class="modal-header">
            <h3>Nouveau message</h3>
            <button class="modal-close" onclick="document.getElementById('modal-msg').classList.remove('open')">✕</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="envoyer">
            <div class="form-group">
                <label>Destinataire</label>
                <select name="destinataire_id" required>
                    <option value="">-- Choisir --</option>
                    <?php foreach ($destinataires as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['prenom'].' '.$d['nom']) ?> (<?= ucfirst($d['role']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Sujet</label>
                <input type="text" name="sujet" placeholder="Objet du message" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="contenu" rows="5" placeholder="Votre message..." required style="resize:vertical;"></textarea>
            </div>
            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('modal-msg').classList.remove('open')">Annuler</button>
                <button type="submit" class="btn btn-primary">✉️ Envoyer</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>

