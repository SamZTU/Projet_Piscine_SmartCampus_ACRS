<?php
error_reporting(0);
ini_set('display_errors', 0);
require_once '../../includes/auth.php';
require_once '../../config/database.php';
requireRole('etudiant');

$etudiant_id = $_SESSION['etudiant_id'];

$emploi = $pdo->prepare("
    SELECT e.jour, e.heure_debut, e.heure_fin, e.salle,
           c.nom as cours_nom, c.code, c.departement,
           u.nom as ens_nom, u.prenom as ens_prenom
    FROM emploi_du_temps e
    JOIN cours c ON c.id = e.cours_id
    JOIN inscriptions i ON i.cours_id = c.id
    LEFT JOIN enseignants en ON en.id = c.enseignant_id
    LEFT JOIN users u ON u.id = en.user_id
    WHERE i.etudiant_id = ?
    ORDER BY FIELD(e.jour,'Lundi','Mardi','Mercredi','Jeudi','Vendredi'), e.heure_debut
");
$emploi->execute([$etudiant_id]);
$seances = $emploi->fetchAll(PDO::FETCH_ASSOC);

// Passe les données PHP à React via JSON
$seances_json = json_encode($seances);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Emploi du temps — SmartCampus</title>
    <link rel="stylesheet" href="/smartcampus/assets/css/style.css">
    <!-- React via CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.2/babel.min.js"></script>
    <style>
        .edt-container { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 4px rgba(0,0,0,.06); border: 1px solid #e2e8f0; }
        .edt-header { display: grid; grid-template-columns: 80px repeat(5, 1fr); background: #1e293b; }
        .edt-header-cell { padding: 14px; text-align: center; color: white; font-weight: 700; font-size: 14px; }
        .edt-body { display: grid; grid-template-columns: 80px repeat(5, 1fr); }
        .edt-time { padding: 8px; text-align: center; font-size: 12px; color: #94a3b8; border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: center; min-height: 60px; }
        .edt-cell { border-right: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; position: relative; min-height: 60px; padding: 4px; }
        .edt-seance { border-radius: 8px; padding: 8px 10px; height: 100%; box-sizing: border-box; cursor: pointer; transition: transform .15s, box-shadow .15s; }
        .edt-seance:hover { transform: scale(1.02); box-shadow: 0 4px 12px rgba(0,0,0,.15); }
        .edt-seance-code { font-size: 11px; font-weight: 700; opacity: 0.8; }
        .edt-seance-nom { font-size: 13px; font-weight: 700; margin: 2px 0; }
        .edt-seance-info { font-size: 11px; opacity: 0.75; }
        .modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; display: flex; align-items: center; justify-content: center; }
        .modal-box { background: white; border-radius: 16px; padding: 28px; max-width: 400px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
        .legend { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
        .legend-item { display: flex; align-items: center; gap: 6px; font-size: 13px; }
        .legend-dot { width: 12px; height: 12px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="app-layout">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="main-content">
        <div class="topbar">
            <div>
                <h1>Emploi du temps 📅</h1>
                <p>Cliquez sur un cours pour plus de détails</p>
            </div>
        </div>
        <!-- Conteneur React -->
        <div id="react-edt"></div>
    </main>
</div>

<script type="text/babel">
const seances = <?= $seances_json ?>;

const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
const HEURES = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00'];

const COULEURS = [
    { bg: '#dbeafe', text: '#1e40af', border: '#93c5fd' },
    { bg: '#d1fae5', text: '#065f46', border: '#6ee7b7' },
    { bg: '#fef3c7', text: '#92400e', border: '#fcd34d' },
    { bg: '#fee2e2', text: '#991b1b', border: '#fca5a5' },
    { bg: '#ede9fe', text: '#5b21b6', border: '#c4b5fd' },
    { bg: '#fce7f3', text: '#9d174d', border: '#f9a8d4' },
];

// Associe une couleur par cours
const coursColors = {};
let colorIdx = 0;
seances.forEach(s => {
    if (!coursColors[s.code]) {
        coursColors[s.code] = COULEURS[colorIdx % COULEURS.length];
        colorIdx++;
    }
});

function timeToMinutes(t) {
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
}

function SeanceCard({ seance, onClick }) {
    const col = coursColors[seance.code];
    return (
        <div className="edt-seance"
             style={{ background: col.bg, borderLeft: `4px solid ${col.border}` }}
             onClick={() => onClick(seance)}>
            <div className="edt-seance-code" style={{ color: col.text }}>{seance.code}</div>
            <div className="edt-seance-nom" style={{ color: col.text }}>{seance.cours_nom}</div>
            <div className="edt-seance-info" style={{ color: col.text }}>
                📍 {seance.salle} · {seance.heure_debut.slice(0,5)}–{seance.heure_fin.slice(0,5)}
            </div>
        </div>
    );
}

function Modal({ seance, onClose }) {
    if (!seance) return null;
    const col = coursColors[seance.code];
    return (
        <div className="modal-backdrop" onClick={onClose}>
            <div className="modal-box" onClick={e => e.stopPropagation()}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16 }}>
                    <span style={{ background: col.bg, color: col.text, padding: '4px 12px', borderRadius: 99, fontWeight: 700 }}>{seance.code}</span>
                    <button onClick={onClose} style={{ border: 'none', background: 'none', fontSize: 22, cursor: 'pointer', color: '#94a3b8' }}>✕</button>
                </div>
                <h2 style={{ marginBottom: 16 }}>{seance.cours_nom}</h2>
                <div style={{ display: 'grid', gap: 10 }}>
                    <div>📅 <strong>{seance.jour}</strong></div>
                    <div>⏰ <strong>{seance.heure_debut.slice(0,5)} – {seance.heure_fin.slice(0,5)}</strong></div>
                    <div>📍 Salle <strong>{seance.salle}</strong></div>
                    <div>👨‍🏫 <strong>{seance.ens_prenom} {seance.ens_nom}</strong></div>
                    <div>🏛️ <strong>{seance.departement}</strong></div>
                </div>
            </div>
        </div>
    );
}

function EmploiDuTemps() {
    const [selected, setSelected] = React.useState(null);

    // Index séances par jour
    const parJour = {};
    JOURS.forEach(j => parJour[j] = []);
    seances.forEach(s => { if (parJour[s.jour]) parJour[s.jour].push(s); });

    // Légende
    const uniqueCours = [...new Map(seances.map(s => [s.code, s])).values()];

    return (
        <div>
            {/* Légende */}
            <div className="legend" style={{ marginBottom: 16 }}>
                {uniqueCours.map(c => (
                    <div key={c.code} className="legend-item">
                        <div className="legend-dot" style={{ background: coursColors[c.code].bg, border: `2px solid ${coursColors[c.code].border}` }}></div>
                        <span>{c.code} — {c.cours_nom}</span>
                    </div>
                ))}
            </div>

            <div className="edt-container">
                {/* Header jours */}
                <div className="edt-header">
                    <div className="edt-header-cell" style={{ fontSize: 11, opacity: 0.5 }}>Heure</div>
                    {JOURS.map(j => (
                        <div key={j} className="edt-header-cell">{j}</div>
                    ))}
                </div>

                {/* Grille heures */}
                <div className="edt-body">
                    {HEURES.map(heure => (
                        <React.Fragment key={heure}>
                            <div className="edt-time">{heure}</div>
                            {JOURS.map(jour => {
                                const seance = parJour[jour].find(s =>
                                    s.heure_debut.slice(0,5) === heure
                                );
                                return (
                                    <div key={jour} className="edt-cell">
                                        {seance && (
                                            <SeanceCard seance={seance} onClick={setSelected} />
                                        )}
                                    </div>
                                );
                            })}
                        </React.Fragment>
                    ))}
                </div>
            </div>

            {selected && <Modal seance={selected} onClose={() => setSelected(null)} />}
        </div>
    );
}

const root = ReactDOM.createRoot(document.getElementById('react-edt'));
root.render(<EmploiDuTemps />);
</script>
</body>
</html>
