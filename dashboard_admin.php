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
     GROUP BY v.id_comp ORDER BY nb DESC LIMIT 5"
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
            --sidebar-bg:    #0f172a;
            --primary:       #ef4444;
            --primary-dark:  #dc2626;
            --main-bg:       #f8fafc;
            --border:        #e2e8f0;
            --text-main:     #1e293b;
            --text-muted:    #64748b;
            --green:         #22c55e;
            --blue:          #6366f1;
            --yellow:        #f59e0b;
            --purple:        #a855f7;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 18px; font-weight: 700; margin-bottom: 6px; }
        .sidebar .admin-tag { font-size: 10px; background: #ef4444; color: white; padding: 2px 8px; border-radius: 20px; font-weight: 600; display: inline-block; margin-bottom: 28px; }
        .menu-title { font-size: 11px; color: #475569; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #94a3b8; padding: 11px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; transition: 0.2s; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }
        .notif-dot { background: var(--yellow); color: #1e293b; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: auto; }

        /* Main */
        .main  { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 28px; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .admin-badge { background: #fee2e2; color: var(--primary); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .avatar-top  { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; }

        .page-title { font-size: 24px; font-weight: 700; }
        .page-sub   { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* Stats grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
        .stat-card  { background: white; border-radius: 14px; border: 1px solid var(--border); padding: 20px 22px; display: flex; align-items: center; gap: 16px; }
        .stat-icon  { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .stat-icon.red    { background: #fef2f2; color: var(--primary); }
        .stat-icon.blue   { background: #eef2ff; color: var(--blue); }
        .stat-icon.green  { background: #f0fdf4; color: var(--green); }
        .stat-icon.yellow { background: #fefce8; color: var(--yellow); }
        .stat-icon.purple { background: #faf5ff; color: var(--purple); }
        .stat-num   { font-size: 26px; font-weight: 700; line-height: 1; }
        .stat-label { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

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
        .comp-rank { width: 24px; height: 24px; border-radius: 50%; background: #eef2ff; color: var(--blue); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .comp-name { font-size: 14px; font-weight: 600; flex: 1; }
        .comp-bar-wrap { flex: 2; background: #f1f5f9; border-radius: 20px; height: 6px; overflow: hidden; }
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
    <span class="admin-tag">Admin Panel</span>
    <p class="menu-title">SUPERVISION</p>
    <nav>
        <a href="dashboard_admin.php"      class="menu-item active"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
        <a href="profil.php" class="menu-item"><i class="far fa-user"></i> <span>Profil</span></a>
        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item"><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
        <a href="validation_mentors.php"    class="menu-item"><i class="fas fa-user-shield"></i>     <span>Valider Mentors</span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a>
        <a href="gestion_utilisateurs.php"  class="menu-item"><i class="fas fa-users-cog"></i>       <span>Gérer Utilisateurs</span></a>
        <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i>            <span>Gestion Badges</span></a>
        <a href="demandes_aide.php"  class="menu-item"><i class="far fa-question-circle"></i>  <span>Demandes d'aide</span></a>
        <a href="mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i>    <span>Mentors</span></a>
    </nav>
    <div style="margin-top:auto;">
        <a href="logout.php" class="menu-item" style="color:#f87171;"><i class="fas fa-power-off"></i> <span>Déconnexion</span></a>
    </div>
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

    <!-- Stats principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-users"></i></div>
            <div>
                <div class="stat-num"><?php echo $statUsers; ?></div>
                <div class="stat-label">Membres inscrits</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
            <div>
                <div class="stat-num"><?php echo $statStagiaires; ?></div>
                <div class="stat-label">Stagiaires</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="far fa-question-circle"></i></div>
            <div>
                <div class="stat-num"><?php echo $statDemandes; ?></div>
                <div class="stat-label">Demandes d'aide</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-handshake"></i></div>
            <div>
                <div class="stat-num"><?php echo $statAccepte; ?></div>
                <div class="stat-label">Aides acceptées</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-award"></i></div>
            <div>
                <div class="stat-num"><?php echo $statCompValid; ?></div>
                <div class="stat-label">Compétences validées</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-chalkboard-teacher"></i></div>
            <div>
                <div class="stat-num"><?php echo $statMentors; ?></div>
                <div class="stat-label">Mentors actifs</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-comments"></i></div>
            <div>
                <div class="stat-num"><?php echo $statReponses; ?></div>
                <div class="stat-label">Réponses données</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-chalkboard"></i></div>
            <div>
                <div class="stat-num"><?php echo $statFormateurs; ?></div>
                <div class="stat-label">Formateurs</div>
            </div>
        </div>
    </div>

    <!-- Alertes actions requises -->
    <div class="alert-row">
        <div class="alert-box pending">
            <i class="fas fa-clipboard-check"></i>
            <div>
                <div class="alert-num"><?php echo $statCompPend; ?></div>
                <div class="alert-label">Compétences en attente</div>
                <a href="validation_competence.php" class="alert-link">Valider maintenant →</a>
            </div>
        </div>
        <div class="alert-box danger">
            <i class="fas fa-user-shield"></i>
            <div>
                <div class="alert-num"><?php echo $statMentorsPend; ?></div>
                <div class="alert-label">Demandes Mentor en attente</div>
                <a href="validation_mentors.php" class="alert-link">Traiter →</a>
            </div>
        </div>
        <div class="alert-box success">
            <i class="fas fa-check-circle"></i>
            <div>
                <div class="alert-num"><?php echo $statCompValid; ?></div>
                <div class="alert-label">Compétences validées au total</div>
                <a href="validation_competence.php?filter=valide" class="alert-link">Voir tout →</a>
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

        <!-- Dernières demandes -->
        <div class="section-card">
            <div class="section-title">
                <span><i class="far fa-clock" style="color:var(--blue); margin-right:8px;"></i>Dernières demandes d'aide</span>
                <a href="demandes_aide.php">Voir tout</a>
            </div>
            <?php foreach ($lastDemandes as $ld):
                $statut = $ld['statut'] ?? 'ouverte';
            ?>
            <div class="demande-item">
                <div class="status-dot <?php echo $statut; ?>"></div>
                <div style="flex:1;">
                    <div class="demande-title"><?php echo htmlspecialchars($ld['titre'] ?? ''); ?></div>
                    <div class="demande-by"><?php echo htmlspecialchars($ld['prenom_uti'] . ' ' . $ld['nom_uti']); ?> • <?php echo $ld['date_publication'] ?? ''; ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($lastDemandes)): ?>
                <p style="color:var(--text-muted); font-size:13px;">Aucune demande.</p>
            <?php endif; ?>
        </div>

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