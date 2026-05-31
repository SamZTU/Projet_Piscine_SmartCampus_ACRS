<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('etudiant');

$etudiant_id = $_SESSION['etudiant_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE etudiant_id = ? AND statut = 'actif'");
$stmt->execute([$etudiant_id]);
$nb_cours = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(moyenne) FROM notes WHERE etudiant_id = ? AND moyenne IS NOT NULL");
$stmt->execute([$etudiant_id]);
$moyenne = round((float)$stmt->fetchColumn(), 2);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM presences WHERE etudiant_id = ? AND statut = 'absent'");
$stmt->execute([$etudiant_id]);
$nb_absences = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND lu = 0");
$stmt->execute([$_SESSION['user_id']]);
$nb_notifs = (int)$stmt->fetchColumn();

$notes = $pdo->prepare("
    SELECT c.nom as cours_nom, c.code, n.moyenne, n.resultat, c.coefficient
    FROM notes n JOIN cours c ON c.id = n.cours_id
    WHERE n.etudiant_id = ? AND n.moyenne IS NOT NULL
    ORDER BY n.updated_at DESC
");
$notes->execute([$etudiant_id]);
$notes = $notes->fetchAll(PDO::FETCH_ASSOC);

$prochains = $pdo->prepare("
    SELECT c.nom, e.jour, e.heure_debut, e.salle
    FROM emploi_du_temps e
    JOIN cours c ON c.id = e.cours_id
    JOIN inscriptions i ON i.cours_id = c.id
    WHERE i.etudiant_id = ?
    ORDER BY FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi'), e.heure_debut
    LIMIT 4
");
$prochains->execute([$etudiant_id]);
$prochains = $prochains->fetchAll(PDO::FETCH_ASSOC);

$data = json_encode([
    'prenom'      => $_SESSION['prenom'],
    'nb_cours'    => $nb_cours,
    'moyenne'     => $moyenne,
    'nb_absences' => $nb_absences,
    'nb_notifs'   => $nb_notifs,
    'notes'       => $notes,
    'prochains'   => $prochains,
]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <style>
        .stat-animated { transition: all .3s ease; }
        .stat-animated:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,.1); }
        .note-bar { height: 8px; border-radius: 99px; background: #f1f5f9; overflow: hidden; margin-top: 6px; }
        .note-bar-fill { height: 100%; border-radius: 99px; transition: width 1s ease; }
        .cours-badge { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div id="react-dashboard"></div>
    </main>
</div>

<script type="text/babel">
const data = <?= $data ?>;

function AnimatedNumber({ value, suffix = '' }) {
    const [display, setDisplay] = React.useState(0);
    React.useEffect(() => {
        let start = 0;
        const end = parseFloat(value) || 0;
        if (end === 0) { setDisplay(0); return; }
        const duration = 800;
        const step = end / (duration / 16);
        const timer = setInterval(() => {
            start += step;
            if (start >= end) { setDisplay(end); clearInterval(timer); }
            else setDisplay(Math.floor(start * 10) / 10);
        }, 16);
        return () => clearInterval(timer);
    }, [value]);
    return <span>{display}{suffix}</span>;
}

function StatCard({ icon, value, suffix, label, color }) {
    const colors = {
        blue:   { bg: '#dbeafe', icon: '#2563eb' },
        green:  { bg: '#d1fae5', icon: '#10b981' },
        orange: { bg: '#fef3c7', icon: '#f59e0b' },
        red:    { bg: '#fee2e2', icon: '#ef4444' },
    };
    const c = colors[color];
    return (
        <div className="card stat-card stat-animated" style={{ cursor: 'default' }}>
            <div className="stat-icon" style={{ background: c.bg, fontSize: 22, color: c.icon }}>{icon}</div>
            <div>
                <div className="stat-value"><AnimatedNumber value={value} suffix={suffix} /></div>
                <div className="stat-label">{label}</div>
            </div>
        </div>
    );
}

function NoteRow({ note }) {
    const pct = Math.min(100, (note.moyenne / 20) * 100);
    const color = note.moyenne >= 14 ? '#10b981' : note.moyenne >= 10 ? '#2563eb' : '#ef4444';
    return (
        <div style={{ padding: '12px 0', borderBottom: '1px solid #f1f5f9' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                    <strong style={{ fontSize: 14 }}>{note.cours_nom}</strong>
                    <span style={{ marginLeft: 8, fontSize: 12, background: '#f1f5f9', padding: '2px 8px', borderRadius: 99 }}>{note.code}</span>
                </div>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                    <strong style={{ color, fontSize: 16 }}>{note.moyenne}/20</strong>
                    <span className="cours-badge" style={{
                        background: note.resultat === 'admis' ? '#d1fae5' : '#fee2e2',
                        color: note.resultat === 'admis' ? '#065f46' : '#991b1b'
                    }}>
                        {note.resultat === 'admis' ? 'Admis' : 'Ajourné'}
                    </span>
                </div>
            </div>
            <div className="note-bar">
                <div className="note-bar-fill" style={{ width: pct + '%', background: color }}></div>
            </div>
        </div>
    );
}

function Dashboard() {
    return (
        <div>
            <div className="topbar">
                <div>
                    <h1>Bonjour, {data.prenom} 👋</h1>
                    <p>Voici un résumé de votre activité académique</p>
                </div>
            </div>

            {/* Stats */}
            <div className="cards-grid">
                <StatCard icon="📚" value={data.nb_cours}    label="Cours suivis"      color="blue" />
                <StatCard icon="📊" value={data.moyenne}     label="Moyenne générale"  color="green"  suffix="/20" />
                <StatCard icon="⚠️" value={data.nb_absences} label="Absences"          color="orange" />
                <StatCard icon="🔔" value={data.nb_notifs}   label="Notifications"     color="red" />
            </div>

            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 20 }}>
                {/* Notes */}
                <div className="card">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                        <h3>📊 Mes notes</h3>
                        <a href="/smartcampus/pages/etudiant/notes.php" style={{ fontSize: 13, color: '#2563eb' }}>Voir tout →</a>
                    </div>
                    {data.notes.length === 0
                        ? <p style={{ color: '#94a3b8', textAlign: 'center', padding: 20 }}>Aucune note</p>
                        : data.notes.map((n, i) => <NoteRow key={i} note={n} />)
                    }
                </div>

                {/* Prochains cours */}
                <div className="card">
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                        <h3>📅 Prochains cours</h3>
                        <a href="/smartcampus/pages/etudiant/emploi_du_temps.php" style={{ fontSize: 13, color: '#2563eb' }}>Voir tout →</a>
                    </div>
                    {data.prochains.length === 0
                        ? <p style={{ color: '#94a3b8', textAlign: 'center', padding: 20 }}>Aucun cours programmé</p>
                        : data.prochains.map((c, i) => (
                            <div key={i} style={{ padding: '10px 0', borderBottom: '1px solid #f1f5f9', display: 'flex', justifyContent: 'space-between' }}>
                                <div>
                                    <strong style={{ fontSize: 14 }}>{c.nom}</strong><br/>
                                    <span style={{ fontSize: 12, color: '#64748b' }}>📍 {c.salle}</span>
                                </div>
                                <div style={{ textAlign: 'right' }}>
                                    <strong style={{ fontSize: 13 }}>{c.jour}</strong><br/>
                                    <span style={{ fontSize: 12, color: '#64748b' }}>{c.heure_debut.slice(0,5)}</span>
                                </div>
                            </div>
                        ))
                    }
                </div>
            </div>
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('react-dashboard'));
root.render(<Dashboard />);
</script>
</body>
</html>
