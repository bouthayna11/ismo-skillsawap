<?php
include ('config.php');
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$statCompPend   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend= $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();

try {
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    $nom_complet = htmlspecialchars($user['nom_uti'] . ' ' . $user['prenom_uti']);
    $initials    = strtoupper(substr($user['nom_uti'], 0, 1) . substr($user['prenom_uti'], 0, 1));

    $filiere             = "Développement Digital";
    $xp                  = 0;
    $aides_completees    = 0;
    $skills              = [];
    $niveau              = 1;
    $validations_faites  = 0;

    if ($user_role === 'stagiaire') {
        $stmt_s = $pdo->prepare("SELECT * FROM stagiaire WHERE id_utilisateur = ?");
        $stmt_s->execute([$user_id]);
        $stagiaire = $stmt_s->fetch();
        if ($stagiaire) {
            $filiere = !empty($stagiaire['filiere']) ? $stagiaire['filiere'] : "Développement Digital";
            $xp      = $stagiaire['score_global'] ?? 0;
        }
        $stmt_a = $pdo->prepare("SELECT COUNT(*) FROM entraide WHERE id_mentor = ? AND statut_aide = 'terminee'");
        $stmt_a->execute([$user_id]);
        $aides_completees = $stmt_a->fetchColumn();

        $stmt_sk = $pdo->prepare(
            "SELECT c.nom_comp FROM validation v
             JOIN competence c ON v.id_comp = c.id_comp
             WHERE v.id_stagiaire = ? AND v.etat_validation = 'valide'"
        );
        $stmt_sk->execute([$user_id]);
        $skills = $stmt_sk->fetchAll();
        $niveau = floor($xp / 1000) + 1;

    } elseif ($user_role === 'formateur') {
        $filiere = "Formateur ISMO";
        $stmt_v = $pdo->prepare("SELECT COUNT(*) FROM validation WHERE id_formateur = ? AND etat_validation = 'valide'");
        $stmt_v->execute([$user_id]);
        $validations_faites = $stmt_v->fetchColumn();

    } elseif (in_array($user_role, ['admin', 'administrateur'])) {
        $filiere = "Administrateur ISMO";
        // Stats admin
        $total_users    = $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
        $total_demandes = $pdo->query("SELECT COUNT(*) FROM demande_aide")->fetchColumn();
        $total_valide   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'valide'")->fetchColumn();
        $total_mentors  = $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'valide'")->fetchColumn();
    }

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

$dashboard_link = "dashboard.php";
if ($user_role === 'formateur')                                    { $dashboard_link = "dashboard_formateur.php"; }
elseif (in_array($user_role, ['administrateur', 'admin']))         { $dashboard_link = "dashboard_admin.php"; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Mon Profil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1e293b;
            --primary:    #6366f1;
            --main-bg:    #f8fafc;
            --border:     #e2e8f0;
            --text-main:  #1e293b;
            --text-muted: #64748b;
            --green:      #22c55e;
            --red:        #ef4444;
            --purple:     #a855f7;
            --yellow:     #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white;}
        .notif-dot { background: var(--yellow); color: #d6b810; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: auto;}

        /* Main */
        .main  { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; overflow: hidden; }
        .avatar-top img { width: 100%; height: 100%; object-fit: cover; }

        .title-row { display: flex; justify-content: space-between; align-items: center; }
        .page-title { font-size: 24px; font-weight: 700; }
        .btn-edit { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }

        /* Profile card */
        .profile-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 32px; display: flex; gap: 28px; align-items: center; }
        .avatar-large { width: 100px; height: 100px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 34px; font-weight: 700; flex-shrink: 0; overflow: hidden; }
        .avatar-large img { width: 100%; height: 100%; object-fit: cover; }
        .av-stagiaire { background: #4f46e5; color: white; }
        .av-formateur { background: var(--purple); color: white; }
        .av-admin     { background: #ef4444; color: white; }

        .profile-details h2 { font-size: 22px; font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .role-badge { font-size: 11px; padding: 3px 10px; border-radius: 20px; font-weight: 600; }
        .rb-stagiaire { background: #eef2ff; color: var(--primary); }
        .rb-formateur { background: #faf5ff; color: var(--purple); }
        .rb-admin     { background: #fef2f2; color: #ef4444; }
        .job-title  { font-size: 14px; font-weight: 500; margin-bottom: 8px; }
        .bio        { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 14px; max-width: 700px; }
        .profile-meta { display: flex; gap: 16px; flex-wrap: wrap; font-size: 13px; color: var(--text-muted); }
        .profile-meta span { display: flex; align-items: center; gap: 6px; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stats-grid.cols-4 { grid-template-columns: repeat(4, 1fr); }
        .card-stat  { background: white; padding: 20px; border-radius: 14px; border: 1px solid var(--border); text-align: center; }
        .card-stat .val   { font-size: 28px; font-weight: 700; display: block; margin-bottom: 4px; }
        .card-stat .label { color: var(--text-muted); font-size: 12px; font-weight: 500; }

        /* Section */
        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 28px; display: flex; flex-direction: column; gap: 16px; }
        .section-card h3 { font-size: 16px; font-weight: 700; }

        /* Skills */
        .skills-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .skill-item  { display: flex; flex-direction: column; gap: 6px; }
        .skill-info  { display: flex; justify-content: space-between; font-size: 14px; font-weight: 500; }
        .bar-bg      { height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
        .bar-fill    { height: 100%; background: var(--primary); border-radius: 10px; }

        /* Badges */
        .badges-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .badge-box   { display: flex; align-items: center; gap: 14px; padding: 16px; border-radius: 12px; border: 1px solid var(--border); }
        .badge-box.locked { opacity: 0.5; }
        .b-icon  { width: 42px; height: 42px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .b-icon.blue   { background: #eff6ff; color: #2563eb; }
        .b-icon.indigo { background: #e0e7ff; color: #4f46e5; }
        .b-details h4  { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
        .b-details p   { font-size: 11px; color: var(--text-muted); }

        /* Admin actions */
        .admin-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .action-btn { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 18px; text-align: center; text-decoration: none; color: var(--text-main); display: flex; flex-direction: column; align-items: center; gap: 8px; transition: 0.2s; }
        .action-btn:hover { border-color: #ef4444; background: #fef2f2; }
        .action-btn i { font-size: 22px; color: #ef4444; }
        .action-btn span { font-size: 12px; font-weight: 600; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .skills-grid, .badges-grid { grid-template-columns: 1fr; }
            .stats-grid, .stats-grid.cols-4 { grid-template-columns: 1fr 1fr; }
            .notif-dot { display: none; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
            .profile-card { flex-direction: column; text-align: center; }
            .profile-meta { justify-content: center; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <p class="menu-title">GENERAL</p>
    <nav>
        <a href="<?php echo $dashboard_link; ?>" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
        <a href="profil.php" class="menu-item active"><i class="far fa-user"></i> <span>Profil</span></a>

        <?php if (in_array($user_role, ['stagiaire', 'formateur'])): ?>
        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i>
            <span><?php echo $user_role === 'formateur' ? "Offres d'aide" : "Demandes d'aide"; ?></span>
        </a>
        <?php if ($user_role === 'stagiaire'): ?>
            <a href="creer_demande.php" class="menu-item"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
        <?php endif; ?>
        <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>
        <?php if ($user_role === 'stagiaire'): ?>
        <?php endif; ?>
        <p class="menu-title">COMPÉTENCES</p>
        <a href="passport.php"   class="menu-item"><i class="fas fa-id-card"></i> <span>Passeport</span></a>
        <?php endif; ?>
        <?php if (in_array($user_role, ['admin', 'formateur'])): ?>
        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item"><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
        <a href="validation_mentors.php"    class="menu-item"><i class="fas fa-user-shield"></i>     <span>Valider Mentors </span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a><a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i>            <span>Gestion Badges</span></a>
        <a href="demandes_aide.php"  class="menu-item"><i class="far fa-question-circle"></i>  <span>Demandes d'aide</span></a>
        <a href="Mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i>    <span>Mentors</span></a>
        <?php endif; ?>
        <?php if (in_array($user_role, ['admin', 'adminstrateur'])): ?>
            <a href="gestion_categories.php"  class="menu-item"><i class="fas fa-tags"></i> <span>Gestion des categories</span></a>
            <a href="gestion_utilisateurs.php"  class="menu-item"><i class="fas fa-users-cog"></i>       <span>Gérer Utilisateurs</span></a>
            <?php endif; ?>
        </nav>
    <div style="margin-top:auto;">
        <a href="logout.php" class="menu-item" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </div>
</aside>

<main class="main">
    <header>
        <div class="search-box">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Rechercher...">
        </div>
        <div class="avatar-top">
            <?php if (!empty($user['photo']) && $user['photo'] !== 'default.png' && file_exists('uploads/' . $user['photo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" alt="Avatar">
            <?php else: ?>
                <?php echo $initials; ?>
            <?php endif; ?>
        </div>
    </header>

    <div class="title-row">
        <h2 class="page-title">Mon Profil</h2>
        <a href="modifier_profil.php" class="btn-edit"><i class="fas fa-edit"></i> Modifier</a>
    </div>

    <!-- Profile Card -->
    <div class="profile-card">
        <?php
        $avClass = 'av-stagiaire';
        if ($user_role === 'formateur') $avClass = 'av-formateur';
        elseif (in_array($user_role, ['admin', 'administrateur'])) $avClass = 'av-admin';
        ?>
        <div class="avatar-large <?php echo $avClass; ?>">
            <?php if (!empty($user['photo']) && $user['photo'] !== 'default.png' && file_exists('uploads/' . $user['photo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" alt="Avatar">
            <?php else: ?>
                <?php echo $initials; ?>
            <?php endif; ?>
        </div>
        <div class="profile-details">
            <h2>
                <?php echo $nom_complet; ?>
                <?php if ($user_role === 'stagiaire'): ?>
                    <span class="role-badge rb-stagiaire">Niveau <?php echo $niveau; ?></span>
                <?php elseif ($user_role === 'formateur'): ?>
                    <span class="role-badge rb-formateur">Formateur</span>
                <?php else: ?>
                    <span class="role-badge rb-admin">Administrateur</span>
                <?php endif; ?>
            </h2>
            <div class="job-title" style="color:<?php echo in_array($user_role, ['admin','administrateur']) ? '#ef4444' : ($user_role === 'formateur' ? 'var(--purple)' : 'var(--primary)'); ?>">
                <?php echo htmlspecialchars($filiere); ?>
            </div>
            <p class="bio">
                <?php if (in_array($user_role, ['admin', 'administrateur'])): ?>
                    Administrateur principal de la plateforme ISMO SkillSwap. Responsable de la supervision globale, de la gestion des utilisateurs, et de la configuration du système.
                <?php elseif ($user_role === 'formateur'): ?>
                    Formateur officiel au sein de l'ISMO Tétouan. Responsable du suivi pédagogique et de la validation des compétences des stagiaires.
                <?php else: ?>
                    Membre actif de la communauté ISMO SkillSwap. Prêt à partager mes compétences et à collaborer sur des projets technologiques.
                <?php endif; ?>
            </p>
            <div class="profile-meta">
                <span><i class="far fa-envelope"></i><?php echo htmlspecialchars($user['email_uti']); ?></span>
                <span><i class="fas fa-map-marker-alt"></i>Tétouan, Maroc</span>
                <span><i class="fas fa-user-tag"></i><?php echo ucfirst($user_role); ?></span>
            </div>
        </div>
    </div>

    <!-- ══ STAGIAIRE ══ -->
    <?php if ($user_role === 'stagiaire'): ?>
        <div class="stats-grid">
            <div class="card-stat">
                <span class="val" style="color:var(--primary);"><?php echo $xp; ?></span>
                <span class="label">Points XP</span>
            </div>
            <div class="card-stat">
                <span class="val" style="color:var(--yellow);"><?php echo count($skills); ?></span>
                <span class="label">Compétences validées</span>
            </div>
            <div class="card-stat">
                <span class="val" style="color:var(--green);"><?php echo $aides_completees; ?></span>
                <span class="label">Aides complétées</span>
            </div>
        </div>

        <div class="section-card">
            <h3>Compétences Validées</h3>
            <div class="skills-grid">
                <?php if (!empty($skills)): ?>
                    <?php foreach ($skills as $sk): ?>
                    <div class="skill-item">
                        <div class="skill-info"><span><?php echo htmlspecialchars($sk['nom_comp']); ?></span><span style="color:var(--primary);">✓</span></div>
                        <div class="bar-bg"><div class="bar-fill" style="width:100%;"></div></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:var(--text-muted); font-size:14px;">Aucune compétence validée pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-card">
            <h3>Badges et réalisations</h3>
            <div class="badges-grid">
                <div class="badge-box <?php echo $aides_completees >= 1 ? '' : 'locked'; ?>">
                    <div class="b-icon blue"><i class="fas fa-users"></i></div>
                    <div class="b-details"><h4>Premier Pas</h4><p>Réalisez votre première aide</p></div>
                    <?php if ($aides_completees < 1): ?><i class="fas fa-lock" style="margin-left:auto; color:#cbd5e1;"></i><?php endif; ?>
                </div>
                <div class="badge-box <?php echo $aides_completees >= 5 ? '' : 'locked'; ?>">
                    <div class="b-icon indigo"><i class="fas fa-award"></i></div>
                    <div class="b-details"><h4>Mentor Actif</h4><p>Aidez 5 étudiants</p></div>
                    <?php if ($aides_completees < 5): ?><i class="fas fa-lock" style="margin-left:auto; color:#cbd5e1;"></i><?php endif; ?>
                </div>
            </div>
        </div>

    <!-- ══ FORMATEUR ══ -->
    <?php elseif ($user_role === 'formateur'): ?>
        <div class="stats-grid" style="grid-template-columns:1fr;">
            <div class="card-stat">
                <span class="val" style="color:var(--purple);"><?php echo $validations_faites; ?></span>
                <span class="label">Compétences validées pour les stagiaires</span>
            </div>
        </div>
        <div class="section-card">
            <h3>Actions de Formateur</h3>
            <p style="font-size:14px; color:var(--text-muted);">En tant que formateur, vous pouvez consulter et valider les compétences soumises par les stagiaires.</p>
            <div style="display:flex; gap:12px; flex-wrap:wrap;">
                <a href="validation_competence.php" class="btn-edit"><i class="fas fa-clipboard-check"></i> Valider Compétences</a>
                <a href="validation_mentors.php"    class="btn-edit" style="background:var(--purple);"><i class="fas fa-user-shield"></i> Valider Mentors</a>
            </div>
        </div>

    <!-- ══ ADMIN ══ -->
    <?php elseif (in_array($user_role, ['admin', 'administrateur'])): ?>
        <div class="stats-grid cols-4">
            <div class="card-stat">
                <span class="val" style="color:#ef4444;"><?php echo $total_users; ?></span>
                <span class="label">Membres inscrits</span>
            </div>
            <div class="card-stat">
                <span class="val" style="color:var(--primary);"><?php echo $total_demandes; ?></span>
                <span class="label">Demandes d'aide</span>
            </div>
            <div class="card-stat">
                <span class="val" style="color:var(--green);"><?php echo $total_valide; ?></span>
                <span class="label">Compétences validées</span>
            </div>
            <div class="card-stat">
                <span class="val" style="color:var(--yellow);"><?php echo $total_mentors; ?></span>
                <span class="label">Mentors actifs</span>
            </div>
        </div>

        <div class="section-card">
            <h3>Actions Rapides</h3>
            <div class="admin-actions">
                <a href="validation_competence.php" class="action-btn"><i class="fas fa-clipboard-check"></i><span>Valider Compétences</span></a>
                <a href="validation_mentors.php"    class="action-btn"><i class="fas fa-user-shield"></i><span>Valider Mentors</span></a>
                <a href="gestion_utilisateurs.php"  class="action-btn"><i class="fas fa-users-cog"></i><span>Gérer Utilisateurs</span></a>
                <a href="gestion_badges.php"        class="action-btn"><i class="fas fa-medal"></i><span>Gestion Badges</span></a>
                <a href="demandes_aide.php"         class="action-btn"><i class="far fa-question-circle"></i><span>Demandes d'aide</span></a>
                <a href="dashboard_admin.php"       class="action-btn"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
            </div>
        </div>
    <?php endif; ?>

</main>
</body>
</html>