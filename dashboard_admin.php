<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'administrateur'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';
$userId = $_SESSION['user_id'];

$infosuser = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
$infosuser->execute([$userId]);
$user = $infosuser->fetch();
$userNomComplet = $user ? htmlspecialchars($user['prenom_uti'] . ' ' . $user['nom_uti']) : "Administrateur";
$initials       = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "AD";

// =====================================================================
// Stats générales
// =====================================================================
$statUsers      = $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
$statStagiaires = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role = 'stagiaire'")->fetchColumn();
$statFormateurs = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role = 'formateur'")->fetchColumn();
$statDemandes   = $pdo->query("SELECT COUNT(*) FROM demande_aide")->fetchColumn();
$statReponses   = $pdo->query("SELECT COUNT(*) FROM reponses_aide")->fetchColumn();
$statAccepte    = $pdo->query("SELECT COUNT(*) FROM reponses_aide WHERE statut = 'accepte'")->fetchColumn();
$statCompValid  = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'valide'")->fetchColumn();
$statCompPend   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentors    = $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'valide'")->fetchColumn();
$statMentorsPend= $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();

// Top 5 compétences les plus demandées
$topComps = $pdo->query(
    "SELECT c.nom_comp, COUNT(v.id_validation) as nb
     FROM validation v
     JOIN competence c ON v.id_comp = c.id_comp
     GROUP BY c.nom_comp"
)->fetchAll(PDO::FETCH_ASSOC);

// Top 5 stagiaires par score
$topStagiaires = $pdo->query(
    "SELECT u.prenom_uti, u.nom_uti, s.score_global, s.filiere
     FROM stagiaire s
     JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur
     ORDER BY s.score_global DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);

// Dernières demandes d'aide
$lastDemandes = $pdo->query(
    "SELECT d.Titre AS titre, d.statut, u.prenom_uti, u.nom_uti, d.date_publication
     FROM demande_aide d
     JOIN utilisateur u ON d.id_stagiaire = u.id_utilisateur
     ORDER BY d.id_demande DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Dashboard Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
         :root { 
           --sidebar-bg: #0c540b;  
           --primary: #198218; 
            --primary-hover: #1595b6;
            --main-bg: #f8fafc; 
            --border: #e2e8f0; 
            --text-main: #1e293b; 
            --text-muted: #64748b; 
            --success: #10b981;
            --danger: #ef4444;
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
        .main  { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 28px; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .admin-badge { background: #fee2e2; color: var(--primary); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .avatar-top  { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; }

        .page-title { font-size: 24px; font-weight: 700; }
        .page-sub   { color: var(--text-muted); font-size: 14px; margin-top: 4px; }
        .stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:15px;
}

.stat-card{
    background:#fff;
    border-radius:14px;
    padding:18px;
    display:flex;
    align-items:center;
    gap:15px;
    box-shadow:0 2px 10px rgba(0,0,0,.06);
    transition:0.3s;
}

.stat-card:hover{
    transform:translateY(-5px);
    box-shadow:0 8px 20px rgba(0,0,0,.10);
}

.stat-num{
    font-size:24px;
    font-weight:700;
}

.stat-label{
    font-size:13px;
    color:#64748b;
}

        /* Section cards */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }

        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .section-title { font-size: 15px; font-weight: 700; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; }
        .section-title a { font-size: 12px; color: var(--blue); font-weight: 500; text-decoration: none; }
        .section-title a:hover { text-decoration: underline; }

        /* Alert boxes */
        .alert-row { display: flex; gap: 16px; flex-wrap: wrap; }
        .alert-box { flex: 1; min-width: 180px; border-radius: 12px; padding: 16px 18px; display: flex; align-items: center; gap: 12px; }
        .alert-box.pending  { background: #fefce8; border: 1px solid #fde047; }
        .alert-box.danger   { background: #fef2f2; border: 1px solid #fca5a5; }
        .alert-box.success  { background: #f0fdf4; border: 1px solid #86efac; }
        .alert-box i   { font-size: 22px; }
        .alert-box.pending i  { color: var(--yellow); }
        .alert-box.danger  i  { color: var(--primary); }
        .alert-box.success i  { color: var(--green); }
        .alert-num   { font-size: 22px; font-weight: 700; }
        .alert-label { font-size: 12px; color: var(--text-muted); }
        .alert-link  { font-size: 12px; font-weight: 600; text-decoration: none; margin-top: 4px; display: inline-block; }
        .alert-box.pending .alert-link  { color: #92400e; }
        .alert-box.danger  .alert-link  { color: var(--primary); }
        .alert-box.success .alert-link  { color: #15803d; }

        /* Top compétences */
        .comp-item { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .comp-rank { width: 24px; height: 24px; border-radius: 50%; background: #eceef1; color: var(--blue); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .comp-name { font-size: 14px; font-weight: 600; flex: 1; }
        .comp-bar-wrap { flex: 2; background: #4c7094; border-radius: 20px; height: 6px; overflow: hidden; }
        .comp-bar { height: 100%; border-radius: 20px; background: var(--blue); }
        .comp-nb  { font-size: 12px; color: var(--text-muted); min-width: 28px; text-align: right; }

        /* Top stagiaires */
        .stag-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .stag-item:last-child { border-bottom: none; }
        .stag-av   { width: 34px; height: 34px; border-radius: 50%; background: var(--blue); color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .stag-name { font-size: 14px; font-weight: 600; }
        .stag-fil  { font-size: 11px; color: var(--text-muted); }
        .stag-xp   { margin-left: auto; font-size: 13px; font-weight: 700; color: var(--blue); }
        .medal-1 { background: #fbbf24; }
        .medal-2 { background: #94a3b8; }
        .medal-3 { background: #b45309; }

        /* Dernières demandes */
        .demande-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .demande-item:last-child { border-bottom: none; }
        .demande-title { font-size: 13px; font-weight: 600; flex: 1; }
        .demande-by    { font-size: 11px; color: var(--text-muted); }
        .status-dot    { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .status-dot.ouverte  { background: var(--green); }
        .status-dot.en_cours { background: var(--yellow); }
        .status-dot.resolue  { background: var(--blue); }
        .status-dot.fermee   { background: #94a3b8; }

        /* Quick actions */
        .quick-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .qa-btn { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 16px; text-align: center; text-decoration: none; color: var(--text-main); transition: 0.2s; display: flex; flex-direction: column; align-items: center; gap: 8px; }
        .qa-btn:hover { border-color: var(--primary); background: #fef2f2; }
        .qa-btn i { font-size: 22px; color: var(--primary); }
        .qa-btn span { font-size: 12px; font-weight: 600; }

        /* Responsive */
        @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar .admin-tag, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .notif-dot { display: none; }
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .quick-actions { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <p class="menu-title">GENERAL</p>
    <nav>
        <a href="dashboard_admin.php"      class="menu-item active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
        <a href="profil.php" class="menu-item"><i class="far fa-user"></i> <span>Profil</span></a>
        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item"><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
         <a href="validation_mentors.php"    class="menu-item"><i class="fas fa-user-shield"></i>     <span>Valider Mentors</span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a>
        <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i>            <span>Gestion Badges</span></a>
         <a href="gestion_utilisateurs.php"  class="menu-item"><i class="fas fa-users-cog"></i>       <span>Gérer Utilisateurs</span></a>
        <a href="gestion_categories.php"  class="menu-item"><i class="fas fa-tags"></i> <span>Gestion categories</span></a>
        <p class="menu-title">GENERAL</p>
        <a href="Mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i><span>Mentors</span></a>
         <a href="demandes_aide.php"  class="menu-item"><i class="far fa-question-circle"></i>  <span>Demandes d'aide </span></a>
        <a href="satisfaction.php" class="menu-item"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
         <p class="menu-title" >COMPTE</p>
        <a href="passport.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Passport</span></a>      
        <a href="logout.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </nav>
</aside>

<main class="main">
    <header>
        <div class="admin-badge"><i class="fas fa-user-shield"></i> Mode Administrateur</div>
        <div class="avatar-top"><?php echo $initials; ?></div>
    </header>

    <div>
        <h1 class="page-title">Panneau de Contrôle Global</h1>
        <p class="page-sub">Bienvenue, <strong><?php echo $userNomComplet; ?></strong> — Surveillance de la plateforme ISMO SkillSwap</p>
    </div>
    <!-- Statistiques Générales -->
<div class="section-card">
    <div class="section-title">
        <span>
            <i class="fas fa-chart-pie" style="color:#4f46e5;margin-right:8px;"></i>
            Statistiques Générales
        </span>
    </div>

    <div class="stats-grid">

        <div class="stat-card" style="border-left:4px solid #4f46e5;">
            <i class="fas fa-users" style="font-size:28px;color:#4f46e5;"></i>
            <div>
                <div class="stat-num"><?php echo $statUsers; ?></div>
                <div class="stat-label">Total Membres</div>
            </div>
        </div>

        <div class="stat-card" style="border-left:4px solid #06b6d4;">
            <i class="fas fa-user-graduate" style="font-size:28px;color:#06b6d4;"></i>
            <div>
                <div class="stat-num"><?php echo $statStagiaires; ?></div>
                <div class="stat-label">Stagiaires</div>
            </div>
        </div>

        <div class="stat-card" style="border-left:4px solid #10b981;">
            <i class="fas fa-chalkboard-teacher" style="font-size:28px;color:#10b981;"></i>
            <div>
                <div class="stat-num"><?php echo $statFormateurs; ?></div>
                <div class="stat-label">Formateurs</div>
            </div>
        </div>

        <div class="stat-card" style="border-left:4px solid #f59e0b;">
            <i class="fas fa-question-circle" style="font-size:28px;color:#f59e0b;"></i>
            <div>
                <div class="stat-num"><?php echo $statDemandes; ?></div>
                <div class="stat-label">Demandes d'aide</div>
            </div>
        </div>

        <div class="stat-card" style="border-left:4px solid #8b5cf6;">
            <i class="fas fa-comments" style="font-size:28px;color:#8b5cf6;"></i>
            <div>
                <div class="stat-num"><?php echo $statReponses; ?></div>
                <div class="stat-label">Réponses</div>
            </div>
        </div>

        <div class="stat-card" style="border-left:4px solid #22c55e;">
            <i class="fas fa-award" style="font-size:28px;color:#22c55e;"></i>
            <div>
                <div class="stat-num"><?php echo $statCompValid; ?></div>
                <div class="stat-label">Compétences Validées</div>
            </div>
        </div>

        <div class="stat-card" style="border-left:4px solid #ef4444;">
            <i class="fas fa-user-shield" style="font-size:28px;color:#ef4444;"></i>
            <div>
                <div class="stat-num"><?php echo $statMentors; ?></div>
                <div class="stat-label">Mentors Validés</div>
            </div>
        </div>

    </div>
</div>
    <!-- Grid contenu -->
    <div class="grid-2">

        <!-- Top compétences -->
        <div class="section-card">
            <div class="section-title">
                <span><i class="fas fa-fire" style="color:var(--primary); margin-right:8px;"></i>Top Compétences demandées</span>
            </div>
            <?php
            $maxNb = !empty($topComps) ? $topComps[0]['nb'] : 1;
            foreach ($topComps as $i => $tc):
                $pct = $maxNb > 0 ? round($tc['nb'] / $maxNb * 100) : 0;
            ?>
            <div class="comp-item">
                <div class="comp-rank"><?php echo $i + 1; ?></div>
                <span class="comp-name"><?php echo htmlspecialchars($tc['nom_comp']); ?></span>
                <div class="comp-bar-wrap"><div class="comp-bar" style="width:<?php echo $pct; ?>%;"></div></div>
                <span class="comp-nb"><?php echo $tc['nb']; ?></span>
            </div>
            <?php endforeach; ?>
            <?php if (empty($topComps)): ?>
                <p style="color:var(--text-muted); font-size:13px;">Aucune donnée disponible.</p>
            <?php endif; ?>
        </div>

        <!-- Top stagiaires XP -->
        <div class="section-card">
            <div class="section-title">
                <span><i class="fas fa-trophy" style="color:#fbbf24; margin-right:8px;"></i>Top Stagiaires XP</span>
            </div>
            <?php foreach ($topStagiaires as $i => $ts):
                $avIni = strtoupper(substr($ts['prenom_uti'], 0, 1) . substr($ts['nom_uti'], 0, 1));
                $medalClass = $i === 0 ? 'medal-1' : ($i === 1 ? 'medal-2' : ($i === 2 ? 'medal-3' : ''));
            ?>
            <div class="stag-item">
                <div class="stag-av <?php echo $medalClass; ?>"><?php echo $avIni; ?></div>
                <div>
                    <div class="stag-name"><?php echo htmlspecialchars($ts['prenom_uti'] . ' ' . $ts['nom_uti']); ?></div>
                    <div class="stag-fil"><?php echo htmlspecialchars($ts['filiere'] ?? ''); ?></div>
                </div>
                <div class="stag-xp"><?php echo $ts['score_global'] ?? 0; ?> XP</div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($topStagiaires)): ?>
                <p style="color:var(--text-muted); font-size:13px;">Aucun stagiaire trouvé.</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- Dernières demandes + Quick Actions -->
    <div class="grid-2">
        <!-- Quick Actions -->
        <div class="section-card">
            <div class="section-title"><span><i class="fas fa-bolt" style="color:var(--yellow); margin-right:8px;"></i>Actions Rapides</span></div>
            <div class="quick-actions">
                <a href="validation_competence.php" class="qa-btn">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Valider<br>Compétences</span>
                </a>
                <a href="validation_mentors.php" class="qa-btn">
                    <i class="fas fa-user-shield"></i>
                    <span>Valider<br>Mentors</span>
                </a>
                <a href="gestion_utilisateurs.php" class="qa-btn">
                    <i class="fas fa-users-cog"></i>
                    <span>Gérer<br>Utilisateurs</span>
                </a>
                <a href="gestion_badges.php" class="qa-btn">
                    <i class="fas fa-medal"></i>
                    <span>Badges</span>
                </a>
                <a href="mentors.php" class="qa-btn">
                    <i class="fas fa-user-graduate"></i>
                    <span>Mentors</span>
                </a>
                <a href="demandes_aide.php" class="qa-btn">
                    <i class="far fa-question-circle"></i>
                    <span>Demandes<br>d'aide</span>
                </a>
            </div>
        </div>

    </div>
</main>

</body>
</html>