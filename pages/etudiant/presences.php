<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('etudiant');

$etudiant_id = $_SESSION['etudiant_id'];

// Présences par cours
$stats = $pdo->prepare("
    SELECT c.nom as cours_nom, c.code,
           COUNT(p.id) as total_seances,
           SUM(CASE WHEN p.statut = 'present' THEN 1 ELSE 0 END) as nb_present,
           SUM(CASE WHEN p.statut = 'absent' THEN 1 ELSE 0 END) as nb_absent,
           SUM(CASE WHEN p.statut = 'retard' THEN 1 ELSE 0 END) as nb_retard,
           SUM(CASE WHEN p.statut = 'justifie' THEN 1 ELSE 0 END) as nb_justifie
    FROM inscriptions i
    JOIN cours c ON c.id = i.cours_id
    LEFT JOIN presences p ON p.etudiant_id = i.etudiant_id AND p.cours_id = i.cours_id
    WHERE i.etudiant_id = ?
    GROUP BY c.id
    ORDER BY c.code
");
$stats->execute([$etudiant_id]);
$stats = $stats->fetchAll();

// Détail des absences
$absences = $pdo->prepare("
    SELECT p.date_seance, p.statut, p.remarque, c.nom as cours_nom, c.code
    FROM presences p
    JOIN cours c ON c.id = p.cours_id
    WHERE p.etudiant_id = ? AND p.statut IN ('absent', 'retard')
    ORDER BY p.date_seance DESC
");
$absences->execute([$etudiant_id]);
$absences = $absences->fetchAll();

$total_absences = array_sum(array_column($stats, 'nb_absent'));
$data_json = json_encode($stats);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes présences — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <style>
        .presence-bar { height: 12px; border-radius: 99px; background: #f1f5f9; overflow: hidden; display: flex; }
        .presence-bar-fill { height: 100%; transition: width 1s ease; }
        .seuil-alert { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 10px; padding: 12px 16px; margin-bottom: 8px; }
        .seuil-ok { background: #d1fae5; border: 1px solid #6ee7b7; border-radius: 10px; padding: 12px 16px; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Mes présences ✅</h1>
                <p>Suivi de vos absences par cours</p>
            </div>
            <?php if ($total_absences >= 3): ?>
            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:10px;padding:12px 20px;color:#991b1b;font-weight:600;">
                ⚠️ <?= $total_absences ?> absences — Attention au seuil !
            </div>
            <?php else: ?>
            <div style="background:#d1fae5;border:1px solid #6ee7b7;border-radius:10px;padding:12px 20px;color:#065f46;font-weight:600;">
                ✅ <?= $total_absences ?> absence(s) — Situation correcte
            </div>
            <?php endif; ?>
        </div>

        <!-- Composant React -->
        <div id="react-presences"></div>

        <!-- Détail absences (PHP classique) -->
        <?php if (!empty($absences)): ?>
        <div class="table-card" style="margin-top:24px;">
            <div class="table-card-header">
                <h3>📋 Historique des absences</h3>
            </div>
            <table>
                <thead><tr><th>Date</th><th>Cours</th><th>Statut</th><th>Remarque</th></tr></thead>
                <tbody>
                    <?php foreach ($absences as $a): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($a['date_seance'])) ?></td>
                        <td><code><?= $a['code'] ?></code> <?= htmlspecialchars($a['cours_nom']) ?></td>
                        <td>
                            <span class="badge <?= $a['statut'] === 'absent' ? 'badge-red' : 'badge-orange' ?>">
                                <?= $a['statut'] === 'absent' ? '❌ Absent' : '⏰ Retard' ?>
                            </span>
                        </td>
                        <td style="color:#64748b;font-size:13px;"><?= htmlspecialchars($a['remarque'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>

<script type="text/babel">
const stats = <?= $data_json ?>;
const SEUIL = 3; // seuil d'alerte absences

function CoursPresence({ cours }) {
    const total = cours.total_seances || 0;
    const present = cours.nb_present || 0;
    const absent  = cours.nb_absent  || 0;
    const retard  = cours.nb_retard  || 0;
    const justifie= cours.nb_justifie|| 0;

    const tauxPresence = total > 0 ? Math.round((present / total) * 100) : 0;
    const alerte = absent >= SEUIL;

    return (
        <div className={alerte ? 'seuil-alert' : 'seuil-ok'} style={{ marginBottom: 12 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                <div>
                    <strong style={{ fontSize: 15 }}>{cours.cours_nom}</strong>
                    <span style={{ marginLeft: 8, fontSize: 12, background: 'rgba(0,0,0,.08)', padding: '2px 8px', borderRadius: 99 }}>
                        {cours.code}
                    </span>
                </div>
                <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
                    {alerte && <span style={{ fontSize: 12, color: '#991b1b', fontWeight: 700 }}>⚠️ Seuil atteint</span>}
                    <strong style={{ fontSize: 18 }}>{tauxPresence}%</strong>
                </div>
            </div>

            {/* Barre de présence */}
            {total > 0 && (
                <div className="presence-bar" style={{ marginBottom: 8 }}>
                    <div className="presence-bar-fill" style={{ width: (present/total*100)+'%', background: '#10b981' }}></div>
                    <div className="presence-bar-fill" style={{ width: (retard/total*100)+'%', background: '#f59e0b' }}></div>
                    <div className="presence-bar-fill" style={{ width: (justifie/total*100)+'%', background: '#3b82f6' }}></div>
                    <div className="presence-bar-fill" style={{ width: (absent/total*100)+'%', background: '#ef4444' }}></div>
                </div>
            )}

            <div style={{ display: 'flex', gap: 16, fontSize: 13 }}>
                <span style={{ color: '#10b981' }}>✅ {present} présent(s)</span>
                <span style={{ color: '#ef4444' }}>❌ {absent} absent(s)</span>
                <span style={{ color: '#f59e0b' }}>⏰ {retard} retard(s)</span>
                <span style={{ color: '#3b82f6' }}>📄 {justifie} justifié(s)</span>
                <span style={{ color: '#94a3b8' }}>Total : {total} séance(s)</span>
            </div>
        </div>
    );
}

function Presences() {
    return (
        <div>
            <div style={{ marginBottom: 8, fontSize: 13, color: '#64748b', display: 'flex', gap: 16 }}>
                <span><span style={{ display: 'inline-block', width: 12, height: 12, background: '#10b981', borderRadius: 2, marginRight: 4 }}></span>Présent</span>
                <span><span style={{ display: 'inline-block', width: 12, height: 12, background: '#f59e0b', borderRadius: 2, marginRight: 4 }}></span>Retard</span>
                <span><span style={{ display: 'inline-block', width: 12, height: 12, background: '#3b82f6', borderRadius: 2, marginRight: 4 }}></span>Justifié</span>
                <span><span style={{ display: 'inline-block', width: 12, height: 12, background: '#ef4444', borderRadius: 2, marginRight: 4 }}></span>Absent</span>
            </div>
            {stats.map((c, i) => <CoursPresence key={i} cours={c} />)}
            {stats.length === 0 && (
                <div style={{ textAlign: 'center', padding: 40, color: '#94a3b8' }}>
                    Aucune donnée de présence disponible
                </div>
            )}
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('react-presences'));
root.render(<Presences />);
</script>
</body>
</html>
