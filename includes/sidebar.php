<?php
$nom  = ($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? '');
$role = $_SESSION['role'] ?? '';
$menus = [
    'etudiant' => [
        ['url' => '/smartcampus/pages/etudiant/dashboard.php',       'icon' => 'layout-dashboard', 'label' => 'Tableau de bord'],
        ['url' => '/smartcampus/pages/etudiant/cours.php',           'icon' => 'book-open',        'label' => 'Mes cours'],
        ['url' => '/smartcampus/pages/etudiant/notes.php',           'icon' => 'bar-chart-2',      'label' => 'Mes notes'],
        ['url' => '/smartcampus/pages/etudiant/emploi_du_temps.php', 'icon' => 'calendar',         'label' => 'Emploi du temps'],
        ['url' => '/smartcampus/pages/etudiant/inscriptions.php',    'icon' => 'pen-line',         'label' => 'Inscriptions'],
        ['url' => '/smartcampus/pages/etudiant/presences.php',       'icon' => 'check-square',     'label' => 'Mes présences'],
        ['url' => '/smartcampus/pages/etudiant/profil.php',          'icon' => 'user',             'label' => 'Mon profil'],
        ['url' => '/smartcampus/pages/messages.php',                 'icon' => 'message-circle',   'label' => 'Messagerie'],
    ],
    'enseignant' => [
        ['url' => '/smartcampus/pages/enseignant/dashboard.php', 'icon' => 'layout-dashboard', 'label' => 'Tableau de bord'],
        ['url' => '/smartcampus/pages/enseignant/cours.php',     'icon' => 'book-open',        'label' => 'Mes cours'],
        ['url' => '/smartcampus/pages/enseignant/notes.php',     'icon' => 'file-pen',         'label' => 'Saisir les notes'],
        ['url' => '/smartcampus/pages/enseignant/etudiants.php', 'icon' => 'users',            'label' => 'Mes étudiants'],
        ['url' => '/smartcampus/pages/enseignant/presences.php', 'icon' => 'check-square',     'label' => 'Présences'],
        ['url' => '/smartcampus/pages/messages.php',             'icon' => 'message-circle',   'label' => 'Messagerie'],
    ],
    'admin' => [
        ['url' => '/smartcampus/pages/admin/dashboard.php',       'icon' => 'layout-dashboard', 'label' => 'Tableau de bord'],
        ['url' => '/smartcampus/pages/admin/etudiants.php',       'icon' => 'graduation-cap',   'label' => 'Étudiants'],
        ['url' => '/smartcampus/pages/admin/enseignants.php',     'icon' => 'user-check',       'label' => 'Enseignants'],
        ['url' => '/smartcampus/pages/admin/cours.php',           'icon' => 'book-open',        'label' => 'Cours'],
        ['url' => '/smartcampus/pages/admin/inscriptions.php',    'icon' => 'pen-line',         'label' => 'Inscriptions'],
        ['url' => '/smartcampus/pages/admin/emploi_du_temps.php', 'icon' => 'calendar',         'label' => 'Emploi du temps'],
        ['url' => '/smartcampus/pages/messages.php',              'icon' => 'message-circle',   'label' => 'Messagerie'],
    ],
];
$currentUrl = $_SERVER['REQUEST_URI'];
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="background:#2563eb;border-radius:8px;padding:6px;display:flex;">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <div>
                <h2 style="font-size:16px;line-height:1;color:white;">SmartCampus</h2>
                <p style="font-size:11px;color:#94a3b8;margin:0;">Gérer · Apprendre · Réussir</p>
            </div>
        </div>
    </div>

    <div class="sidebar-user">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;border-radius:50%;background:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </div>
            <div>
                <strong style="display:block;font-size:14px;color:white;"><?= htmlspecialchars($nom) ?></strong>
                <span style="font-size:12px;color:#94a3b8;"><?= ucfirst($role) ?></span>
            </div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php
        $svgs = [
            'layout-dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>',
            'book-open'        => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
            'bar-chart-2'      => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" x2="18" y1="20" y2="10"/><line x1="12" x2="12" y1="20" y2="4"/><line x1="6" x2="6" y1="20" y2="14"/></svg>',
            'calendar'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/></svg>',
            'pen-line'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>',
            'check-square'     => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>',
            'user'             => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
            'message-circle'   => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
            'file-pen'         => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h7l5 5v3"/><path d="M14 19c0-1.1.9-2 2-2h.5"/><path d="m19.5 17-.5 4.5 3-1.5-2.5-3z"/></svg>',
            'users'            => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'graduation-cap'   => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
            'user-check'       => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><polyline points="16 11 18 13 22 9"/></svg>',
        ];
        ?>
        <?php foreach ($menus[$role] as $item): ?>
            <?php $active = strpos($currentUrl, basename($item['url'], '.php')) !== false; ?>
            <a href="<?= $item['url'] ?>" class="<?= $active ? 'active' : '' ?>">
                <?= $svgs[$item['icon']] ?? '' ?>
                <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="/smartcampus/pages/logout.php"
           style="display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:8px;color:#ef4444;border:1px solid rgba(239,68,68,.4);font-size:14px;font-weight:600;transition:background .15s;text-decoration:none;"
           onmouseover="this.style.background='rgba(239,68,68,.1)'"
           onmouseout="this.style.background='transparent'">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
            Déconnexion
        </a>
    </div>
</aside>
