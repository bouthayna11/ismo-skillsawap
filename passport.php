<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$themeClass = 'theme-stagiaire';
if (in_array($user_role, ['admin', 'administrateur'])) $themeClass = 'theme-admin';
elseif ($user_role === 'formateur')                    $themeClass = 'theme-formateur';


$queryUser = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
$queryUser->execute([$userId]);
$user = $queryUser->fetch();
$nom_complet = htmlspecialchars($user['prenom_uti'] . ' ' . $user['nom_uti']);
$initials    = strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1));


$filiere      = '';
$score_global = 0;
$niveau       = 1;
$competences  = [];
$badges       = [];
$aides_count  = 0;
$classement   = '#—';
$is_mentor    = false;
$validations_faites = 0;
$total_users = $total_demandes = $total_valide = $total_mentors = 0;

if ($user_role === 'stagiaire') {
    $stmtStag = $pdo->prepare("SELECT * FROM stagiaire WHERE id_utilisateur = ?");
    $stmtStag->execute([$userId]);
    $stagiaire    = $stmtStag->fetch();
    $filiere      = $stagiaire['filiere']      ?? 'Développement Digital';
    $score_global = $stagiaire['score_global'] ?? 0;
    $niveau       = max(1, floor($score_global / 300));


    $stmtComps = $pdo->prepare(
        "SELECT c.nom_comp, c.categorie, m.niveau_estime,
                v.date_validation,
                u.prenom_uti AS form_prenom, u.nom_uti AS form_nom
         FROM maitriser m
         JOIN competence c ON m.id_comp = c.id_comp
         LEFT JOIN validation v ON v.id_comp = m.id_comp
                                AND v.id_stagiaire = m.id_utilisateur
                                AND v.etat_validation = 'valide'
         LEFT JOIN utilisateur u ON v.id_formateur = u.id_utilisateur
         WHERE m.id_utilisateur = ?
         ORDER BY c.categorie, c.nom_comp"
    );
    $stmtComps->execute([$userId]);
    $competences = $stmtComps->fetchAll(PDO::FETCH_ASSOC);


    $stmtBadges = $pdo->prepare(
        "SELECT b.nom_badge, b.description_badge, b.image_badge, o.date_obtention
         FROM obtenir o JOIN badges b ON o.id_badge = b.id_badge
         WHERE o.id_utilisateur = ? ORDER BY o.date_obtention DESC"
    );
    $stmtBadges->execute([$userId]);
    $badges = $stmtBadges->fetchAll(PDO::FETCH_ASSOC);

    $nbAides = $pdo->prepare("SELECT COUNT(*) FROM reponses_aide WHERE id_aidant = ? AND statut = 'accepte'");
    $nbAides->execute([$userId]);
    $aides_count = $nbAides->fetchColumn();

    $rankStmt = $pdo->query("SELECT id_utilisateur, RANK() OVER (ORDER BY score_global DESC) AS rang FROM stagiaire");
    foreach ($rankStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['id_utilisateur'] == $userId) { $classement = '#' . $r['rang']; break; }
    }


    $chkMentor = $pdo->prepare("SELECT COUNT(*) FROM demande_mentorat WHERE id_stagiaire = ? AND statut = 'valide'");
    $chkMentor->execute([$userId]);
    $is_mentor = $chkMentor->fetchColumn() > 0;

} elseif ($user_role === 'formateur') {

    $filiere = "Formateur ISMO";
    $stmt_v  = $pdo->prepare("SELECT COUNT(*) FROM validation WHERE id_formateur = ? AND etat_validation = 'valide'");
    $stmt_v->execute([$userId]);
    $validations_faites = $stmt_v->fetchColumn();


    $stmtCompsValid = $pdo->prepare(
        "SELECT v.date_validation, c.nom_comp, c.categorie,
                u.prenom_uti, u.nom_uti
         FROM validation v
         JOIN competence c ON v.id_comp = c.id_comp
         JOIN utilisateur u ON v.id_stagiaire = u.id_utilisateur
         WHERE v.id_formateur = ? AND v.etat_validation = 'valide'
         ORDER BY v.date_validation DESC LIMIT 10"
    );
    $stmtCompsValid->execute([$userId]);
    $comps_valides = $stmtCompsValid->fetchAll(PDO::FETCH_ASSOC);

} elseif (in_array($user_role, ['admin', 'administrateur'])) {

    $filiere        = "Administrateur ISMO";
    $total_users    = $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
    $total_demandes = $pdo->query("SELECT COUNT(*) FROM demande_aide")->fetchColumn();
    $total_valide   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'valide'")->fetchColumn();
    $total_mentors  = $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'valide'")->fetchColumn();
}

$niveauPct   = ['debutant' => 40, 'intermediaire' => 65, 'avance' => 85, 'expert' => 100];
$niveauLabel = ['debutant' => '🌱 Débutant', 'intermediaire' => '⚡ Intermédiaire', 'avance' => '🔥 Avancé', 'expert' => '🏆 Expert'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Passeport</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .theme-stagiaire { --sidebar-bg:#1e293b; --primary:#6366f1; --primary-hover:#4f46e5; }
        .theme-formateur { --sidebar-bg:#430b54; --primary:#df64d4; --primary-hover:#8447ac; }
        .theme-admin     { --sidebar-bg:#0c540b; --primary:#198218; --primary-hover:#1595b6; }

        :root {
            --main-bg:   #f8fafc;
            --border:    #e2e8f0;
            --text-main: #1e293b;
            --text-muted:#64748b;
            --green:     #22c55e;
            --red:       #ef4444;
            --yellow:    #f59e0b;
            --purple:    #a855f7;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }

        .main  { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .title-row  { display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 24px; font-weight: 700; }
        .btn-pdf { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .btn-pdf:hover { opacity: 0.9; }

        .profile-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 32px; display: flex; gap: 28px; align-items: center; }
        .avatar-large { width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 34px; font-weight: 700; flex-shrink: 0; position: relative; }
        .av-stagiaire { background: #4f46e5; color: white; }
        .av-formateur { background: var(--purple); color: white; }
        .av-admin     { background: var(--red); color: white; }
        .mentor-crown { position: absolute; top: -8px; right: -4px; font-size: 18px; }
        .role-badge   { font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .rb-stag  { background: #eef2ff; color: #4f46e5; }
        .rb-form  { background: #faf5ff; color: var(--purple); }
        .rb-admin { background: #fef2f2; color: var(--red); }
        .mentor-badge { background: #f0fdf4; color: #15803d; font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 600; border: 1px solid #bbf7d0; }
        .profile-details h2 { font-size: 22px; font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .job-title   { font-size: 14px; font-weight: 500; margin-bottom: 8px; color: var(--primary); }
        .profile-meta{ display: flex; gap: 16px; font-size: 13px; color: var(--text-muted); flex-wrap: wrap; }
        .profile-meta span { display: flex; align-items: center; gap: 5px; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .card-stat  { background: white; padding: 20px; border-radius: 14px; border: 1px solid var(--border); text-align: center; }
        .card-stat .val   { font-size: 28px; font-weight: 700; display: block; margin-bottom: 4px; }
        .card-stat .label { color: var(--text-muted); font-size: 12px; font-weight: 500; }

        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 28px; }
        .section-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .section-card h3 i { color: var(--primary); }

        .comp-item { background: #f8fafc; border-radius: 12px; border: 1px solid var(--border); padding: 16px 20px; display: flex; align-items: center; gap: 16px; margin-bottom: 10px; transition: 0.2s; }
        .comp-item:hover { border-color: #c7d2fe; background: white; }
        .comp-item:last-child { margin-bottom: 0; }
        .check-icon  { color: var(--green); font-size: 18px; flex-shrink: 0; }
        .comp-name   { font-size: 14px; font-weight: 700; }
        .comp-level  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .comp-center { flex: 1; display: flex; align-items: center; gap: 12px; }
        .bar-bg  { flex: 1; height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
        .bar-fill{ height: 100%; background: var(--primary); border-radius: 10px; }
        .percentage { font-size: 13px; font-weight: 700; min-width: 38px; text-align: right; }
        .comp-right { text-align: right; font-size: 11px; color: var(--text-muted); min-width: 130px; }
        .comp-right .validator { font-weight: 600; color: #475569; }
        .comp-right .validator span { color: var(--primary); }
        .comp-cat { display: inline-block; background: #ede9fe; color: #7c3aed; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: 600; margin-bottom: 4px; }

        .badges-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .badge-card  { display: flex; align-items: center; gap: 14px; padding: 14px; border-radius: 12px; border: 1px solid var(--border); background: #fefce8; }
        .badge-card:hover { border-color: #fde047; }
        .b-icon  { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; background: #fef9c3; color: var(--yellow); flex-shrink: 0; }
        .b-details h4 { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
        .b-details p  { font-size: 11px; color: var(--text-muted); }
        .b-date       { font-size: 10px; color: #94a3b8; margin-top: 3px; display: block; }

        .val-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px; background: #f8fafc; border: 1px solid var(--border); margin-bottom: 8px; }
        .val-item:last-child { margin-bottom: 0; }
        .val-comp { font-size: 14px; font-weight: 600; flex: 1; }
        .val-stag { font-size: 12px; color: var(--text-muted); }
        .val-date { font-size: 11px; color: #94a3b8; }

        .admin-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .action-btn { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 18px; text-align: center; text-decoration: none; color: var(--text-main); display: flex; flex-direction: column; align-items: center; gap: 8px; transition: 0.2s; }
        .action-btn:hover { border-color: var(--primary); background: #f0fdf4; }
        .action-btn i   { font-size: 22px; color: var(--primary); }
        .action-btn span{ font-size: 12px; font-weight: 600; }

        .empty-state { text-align: center; padding: 32px; color: var(--text-muted); }
        .empty-state i { font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block; }

        @media print {
            .sidebar, header, .btn-pdf { display: none !important; }
            body { background: white; }
            .main { padding: 20px; }
        }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .badges-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
            .profile-card { flex-direction: column; text-align: center; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .comp-item { flex-direction: column; align-items: flex-start; }
            .comp-center { width: 100%; }
            .comp-right { text-align: left; }
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">

<?php if($user_role==="admin")include 'sidebar.php'; ?>
<?php if($user_role==="formateur")include 'sidebarform.php'; ?>
<?php if($user_role==="stagiaire")include 'sidebarstgpass.php'; ?>
<main class="main">
    <header>
        <div class="search-box">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Rechercher...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <div class="title-row">
        <h2 class="page-title">Passeport de Compétences</h2>
        <button class="btn-pdf" onclick="window.print()"><i class="fas fa-file-pdf"></i> Export PDF</button>
    </div>

    <div class="profile-card">
        <?php
        $avClass = 'av-stagiaire';
        if ($user_role === 'formateur') $avClass = 'av-formateur';
        elseif (in_array($user_role, ['admin','administrateur'])) $avClass = 'av-admin';
        ?>
        <div class="avatar-large <?php echo $avClass; ?>">
            <?php echo $initials; ?>
            <?php if ($is_mentor): ?><span class="mentor-crown">👑</span><?php endif; ?>
        </div>
        <div class="profile-details">
            <h2>
                <?php echo $nom_complet; ?>
                <?php if ($user_role === 'stagiaire'): ?>
                    <span class="role-badge rb-stag">Niveau <?php echo $niveau; ?></span>
                    <?php if ($is_mentor): ?><span class="mentor-badge"><i class="fas fa-chalkboard-teacher"></i> Mentor</span><?php endif; ?>
                <?php elseif ($user_role === 'formateur'): ?>
                    <span class="role-badge rb-form">Formateur</span>
                <?php else: ?>
                    <span class="role-badge rb-admin">Administrateur</span>
                <?php endif; ?>
            </h2>
            <div class="job-title"><?php echo htmlspecialchars($filiere ?: ucfirst($user_role) . ' ISMO'); ?></div>
            <div class="profile-meta">
                <span><i class="far fa-envelope"></i><?php echo htmlspecialchars($user['email_uti']); ?></span>
                <span><i class="fas fa-map-marker-alt"></i>Tétouan, Maroc</span>
                <span><i class="fas fa-user-tag"></i><?php echo ucfirst($user_role); ?></span>
            </div>
        </div>
    </div>

    <?php if ($user_role === 'stagiaire'): ?>
    <div class="stats-grid">
        <div class="card-stat" style="background:#f5f3ff; border-color:#ddd6fe;">
            <span class="val" style="color:#4f46e5;"><?php echo $score_global; ?></span>
            <span class="label">Points XP</span>
        </div>
        <div class="card-stat" style="background:#fffbeb; border-color:#fef3c7;">
            <span class="val" style="color:#b45309;"><?php echo count($competences); ?></span>
            <span class="label">Compétences validées</span>
        </div>
        <div class="card-stat" style="background:#fff5f5; border-color:#fed7d7;">
            <span class="val" style="color:#db2777;"><?php echo count($badges); ?></span>
            <span class="label">Badges obtenus</span>
        </div>
        <div class="card-stat" style="background:#f0fdf4; border-color:#dcfce7;">
            <span class="val" style="color:#16a34a;"><?php echo $classement; ?></span>
            <span class="label">Classement</span>
        </div>
    </div>

    <div class="section-card">
        <h3><i class="fas fa-award"></i> Compétences Validées</h3>
        <?php if (empty($competences)): ?>
            <div class="empty-state"><i class="fas fa-award"></i><p>Aucune compétence validée. Soumettez une demande !</p></div>
        <?php else: ?>
            <?php foreach ($competences as $comp):
                $niv   = $comp['niveau_estime'] ?? 'debutant';
                $pct   = $niveauPct[$niv] ?? 50;
                $label = $niveauLabel[$niv] ?? $niv;
                $form  = !empty($comp['form_prenom']) ? htmlspecialchars($comp['form_prenom'] . ' ' . $comp['form_nom']) : 'Formateur';
                $date  = !empty($comp['date_validation']) ? date('d/m/Y', strtotime($comp['date_validation'])) : '—';
            ?>
            <div class="comp-item">
                <i class="fas fa-circle-check check-icon"></i>
                <div style="min-width:160px;">
                    <?php if (!empty($comp['categorie'])): ?><span class="comp-cat"><?php echo htmlspecialchars($comp['categorie']); ?></span><?php endif; ?>
                    <div class="comp-name"><?php echo htmlspecialchars($comp['nom_comp']); ?></div>
                    <div class="comp-level"><?php echo $label; ?></div>
                </div>
                <div class="comp-center">
                    <div class="bar-bg"><div class="bar-fill" style="width:<?php echo $pct; ?>%;"></div></div>
                    <span class="percentage"><?php echo $pct; ?>%</span>
                </div>
                <div class="comp-right">
                    <p class="validator">Validé par <span><?php echo $form; ?></span></p>
                    <p style="margin-top:2px;"><?php echo $date; ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section-card">
        <h3><i class="fas fa-medal"></i> Badges et Distinctions</h3>
        <?php if (empty($badges)): ?>
            <div class="empty-state"><i class="fas fa-medal"></i><p>Aucun badge. Continuez à aider vos collègues !</p></div>
        <?php else: ?>
        <div class="badges-grid">
            <?php foreach ($badges as $b): ?>
            <div class="badge-card">
                <div class="b-icon"><i class="<?php echo htmlspecialchars($b['image_badge'] ?? 'fas fa-medal'); ?>"></i></div>
                <div class="b-details">
                    <h4><?php echo htmlspecialchars($b['nom_badge']); ?></h4>
                    <p><?php echo htmlspecialchars($b['description_badge'] ?? ''); ?></p>
                    <?php if (!empty($b['date_obtention'])): ?>
                        <span class="b-date"><i class="far fa-calendar" style="margin-right:3px;"></i>Obtenu le <?php echo date('d/m/Y', strtotime($b['date_obtention'])); ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php elseif ($user_role === 'formateur'): ?>
    <div class="stats-grid" style="grid-template-columns: repeat(2,1fr);">
        <div class="card-stat" style="background:#faf5ff; border-color:#e9d5ff;">
            <span class="val" style="color:var(--purple);"><?php echo $validations_faites; ?></span>
            <span class="label">Compétences validées</span>
        </div>
        <div class="card-stat" style="background:#f0fdf4; border-color:#bbf7d0;">
            <span class="val" style="color:var(--green);"><?php echo isset($comps_valides) ? count($comps_valides) : 0; ?></span>
            <span class="label">Dernières validations</span>
        </div>
    </div>

    <div class="section-card">
        <h3><i class="fas fa-clipboard-check"></i> Dernières Compétences Validées</h3>
        <?php if (empty($comps_valides)): ?>
            <div class="empty-state"><i class="fas fa-clipboard-check"></i><p>Aucune validation effectuée pour le moment.</p></div>
        <?php else: ?>
            <?php foreach ($comps_valides as $cv): ?>
            <div class="val-item">
                <i class="fas fa-check-circle" style="color:var(--green); font-size:16px; flex-shrink:0;"></i>
                <div style="flex:1;">
                    <div class="val-comp"><?php echo htmlspecialchars($cv['nom_comp']); ?></div>
                    <div class="val-stag">pour <?php echo htmlspecialchars($cv['prenom_uti'] . ' ' . $cv['nom_uti']); ?></div>
                </div>
                <?php if (!empty($comp['categorie'])): ?>
                    <span style="background:#ede9fe; color:#7c3aed; font-size:11px; padding:2px 8px; border-radius:10px;"><?php echo htmlspecialchars($cv['categorie']); ?></span>
                <?php endif; ?>
                <div class="val-date"><?php echo !empty($cv['date_validation']) ? date('d/m/Y', strtotime($cv['date_validation'])) : '—'; ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="section-card">
        <h3><i class="fas fa-bolt"></i> Actions Rapides</h3>
        <div class="admin-actions">
            <a href="validation_competence.php" class="action-btn"><i class="fas fa-clipboard-check"></i><span>Valider Compétences</span></a>
            <a href="validation_mentors.php"    class="action-btn"><i class="fas fa-user-shield"></i><span>Valider Mentors</span></a>
            <a href="demandes_aide.php"         class="action-btn"><i class="far fa-question-circle"></i><span>Demandes d'aide</span></a>
        </div>
    </div>

    <?php elseif (in_array($user_role, ['admin', 'administrateur'])): ?>
    <div class="stats-grid">
        <div class="card-stat" style="background:#fef2f2; border-color:#fecaca;">
            <span class="val" style="color:var(--red);"><?php echo $total_users; ?></span>
            <span class="label">Membres inscrits</span>
        </div>
        <div class="card-stat" style="background:#eef2ff; border-color:#c7d2fe;">
            <span class="val" style="color:#4f46e5;"><?php echo $total_demandes; ?></span>
            <span class="label">Demandes d'aide</span>
        </div>
        <div class="card-stat" style="background:#f0fdf4; border-color:#bbf7d0;">
            <span class="val" style="color:var(--green);"><?php echo $total_valide; ?></span>
            <span class="label">Compétences validées</span>
        </div>
        <div class="card-stat" style="background:#fefce8; border-color:#fde047;">
            <span class="val" style="color:#854d0e;"><?php echo $total_mentors; ?></span>
            <span class="label">Mentors actifs</span>
        </div>
    </div>

    <div class="section-card">
        <h3><i class="fas fa-bolt"></i> Actions Rapides</h3>
        <div class="admin-actions">
            <a href="validation_competence.php" class="action-btn"><i class="fas fa-clipboard-check"></i><span>Valider Compétences</span></a>
            <a href="validation_mentors.php"    class="action-btn"><i class="fas fa-user-shield"></i><span>Valider Mentors</span></a>
            <a href="gestion_utilisateurs.php"  class="action-btn"><i class="fas fa-users-cog"></i><span>Gérer Utilisateurs</span></a>
            <a href="gestion_badges.php"        class="action-btn"><i class="fas fa-medal"></i><span>Badges</span></a>
            <a href="demandes_aide.php"         class="action-btn"><i class="far fa-question-circle"></i><span>Demandes d'aide</span></a>
            <a href="dashboard_admin.php"       class="action-btn"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
        </div>
    </div>
    <?php endif; ?>

</main>
</body>
</html>