<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['formateur', 'administrateur', 'admin'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';
$statCompPend   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend= $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();
$userId = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$queryUser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$queryUser->execute([$userId]);
$user     = $queryUser->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "FR";

$msg      = "";
$msg_type = "success";

if (isset($_GET['action'], $_GET['id_demande'])) {
    $action     = $_GET['action'];
    $id_demande = (int) $_GET['id_demande'];

    if ($action === 'accepter') {
        // ✅ 'valide' مشي 'valide' بالفرنسية
        $stmt = $pdo->prepare("UPDATE demande_mentorat SET statut = 'valide' WHERE id_demande_m = ?");
        $stmt->execute([$id_demande]);
        $msg = "Le stagiaire a été accepté comme Mentor avec succès ! ✅";
    } elseif ($action === 'refuser') {
        // ✅ 'refuse' بدون accent — هكا كاين فالـ enum
        $stmt = $pdo->prepare("UPDATE demande_mentorat SET statut = 'refuse' WHERE id_demande_m = ?");
        $stmt->execute([$id_demande]);
        $msg      = "La demande a été refusée.";
        $msg_type = "error";
    }

    header("Location: validation_mentors.php" . (!empty($msg) ? "?msg=" . urlencode($msg) . "&type=" . $msg_type : ""));
    exit();
}

// رسالة من الـ redirect
if (isset($_GET['msg'])) {
    $msg      = $_GET['msg'];
    $msg_type = $_GET['type'] ?? 'success';
}

// =====================================================================
// Stats
// =====================================================================
$stmtStats = $pdo->query("SELECT statut, COUNT(*) as nb FROM demande_mentorat WHERE message_demande IS NULL OR message_demande = '' GROUP BY statut");
$stats_raw = $stmtStats->fetchAll(PDO::FETCH_ASSOC);
$stats = ['en_attente' => 0, 'valide' => 0, 'refuse' => 0];
foreach ($stats_raw as $s) { $stats[$s['statut']] = $s['nb']; }

// =====================================================================
// جلب الطلبات المعلقة
// =====================================================================
$queryRequests = $pdo->query(
    "SELECT 
        d.id_demande_m,
        d.message_demande,
        d.date_demande,
        u.id_utilisateur,
        u.nom_uti,
        u.prenom_uti,
        s.score_global,
        s.filiere,
        (SELECT COUNT(*) FROM reponses_aide r WHERE r.id_aidant = u.id_utilisateur AND r.statut = 'accepte') AS total_aides
     FROM demande_mentorat d
     INNER JOIN utilisateur u ON d.id_stagiaire = u.id_utilisateur
     INNER JOIN stagiaire   s ON u.id_utilisateur = s.id_utilisateur
     WHERE d.statut = 'en_attente' AND (d.message_demande IS NULL OR d.message_demande = '')
     ORDER BY d.date_demande ASC"
);
$mentor_requests = $queryRequests->fetchAll(PDO::FETCH_ASSOC);
$dashboard_link = "dashboard.php";
if ($user_role === 'formateur')                                    { $dashboard_link = "dashboard_formateur.php"; }
elseif (in_array($user_role, ['administrateur', 'admin']))         { $dashboard_link = "dashboard_admin.php"; }
// Determine la classe dyal le theme b-hssab le rôle
$themeClass = 'theme-stagiaire';
if (in_array($user_role, ['admin', 'administrateur'])) {
    $themeClass = 'theme-admin';
} elseif ($user_role === 'formateur') {
    $themeClass = 'theme-formateur';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Validation Mentors</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #430b54; 
            --primary: #df64d4; 
            --primary-hover: #8447ac;
            --main-bg:       #f8fafc;
            --border:        #e2e8f0;
            --text-main:     #1e293b;
            --text-muted:    #64748b;
            --green:         #22c55e;
            --red:           #ef4444;
            --yellow:        #f59e0b;
        }
        .theme-admin{
     --sidebar-bg: #0c540b; 
    --primary: #198218; 
    --primary-hover: #1595b6;
    --main-bg: #f8fafc; 
    --border: #e2e8f0; 
    --text-main: #1e293b; 
    --text-muted: #64748b; 
    --green: #10b981; /* Was success */
    --red: #ef4444;   /* Was danger */
}
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }

        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }

        /* Main */
        .main  { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .page-title { font-size: 24px; font-weight: 700; }
        .page-sub   { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* Alertes */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Stats */
        .stats-bar { display: flex; gap: 16px; flex-wrap: wrap; }
        .stat-box  { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 16px 22px; flex: 1; min-width: 110px; text-align: center; }
        .stat-num  { font-size: 28px; font-weight: 700; }
        .stat-label{ font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .stat-num.pending { color: var(--yellow); }
        .stat-num.valide  { color: var(--green); }
        .stat-num.refuse  { color: var(--red); }

        /* Cards */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px; }

        .request-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 22px; display: flex; flex-direction: column; gap: 16px; transition: box-shadow 0.2s; }
        .request-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }

        .card-top  { display: flex; align-items: center; gap: 14px; }
        .avatar-lg { width: 50px; height: 50px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; flex-shrink: 0; }
        .stag-name    { font-size: 15px; font-weight: 700; }
        .stag-filiere { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .stag-date    { font-size: 11px; color: #94a3b8; margin-top: 3px; }

        .card-stats { display: flex; gap: 10px; flex-wrap: wrap; }
        .mini-stat  { background: #f8fafc; border: 1px solid var(--border); border-radius: 8px; padding: 8px 14px; text-align: center; flex: 1; min-width: 80px; }
        .mini-stat-num   { font-size: 18px; font-weight: 700; color: var(--primary); }
        .mini-stat-label { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .msg-box { background: #f8fafc; border-left: 3px solid var(--primary); border-radius: 0 8px 8px 0; padding: 10px 14px; font-size: 13px; color: var(--text-muted); line-height: 1.5; }

        .card-actions { display: flex; gap: 10px; padding-top: 4px; }
        .btn-accept { flex: 1; background: var(--green); color: white; border: none; padding: 10px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; }
        .btn-refuse { flex: 1; background: var(--red);   color: white; border: none; padding: 10px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; }
        .btn-accept:hover { background: #16a34a; }
        .btn-refuse:hover { background: #dc2626; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); background: white; border-radius: 16px; border: 1px solid var(--border); }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }
        .empty-state p { font-size: 15px; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
            .cards-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">

<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <p class="menu-title">GENERAL</p>
    <nav>
        <a href="<?php echo $dashboard_link; ?>" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
        <a href="profil.php" class="menu-item "><i class="far fa-user"></i> <span>Profil</span></a>

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
        <a href="validation_competence.php" class="menu-item "><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
        <a href="validation_mentors.php"    class="menu-item active"><i class="fas fa-user-shield"></i>     <span>Valider Mentors </span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a>  <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i>            <span>Gestion Badges</span></a>
          <?php if (in_array($user_role, ['admin', 'administrateur'])): ?>
        <a href="gestion_utilisateurs.php" class="menu-item"><i class="fas fa-users-cog"></i> <span>Gérer Utilisateurs</span></a>
        <a href="gestion_categories.php"   class="menu-item"><i class="fas fa-tags"></i> <span>Gestion Catégories</span></a>
        <?php endif; ?>
        <?php endif; ?>
        <p class="menu-title">GENERAL</p>
        <a href="Mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i><span>Mentors</span></a>
         <a href="demandes_aide.php"  class="menu-item"><i class="far fa-question-circle"></i>  <span>Demandes d'aide </span></a>
        <a href="satisfaction.php" class="menu-item"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
                            <p class="menu-title" >COMPTE</p>
            <a href="logout.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </nav>
</aside>

<main class="main">
    <header>
        <div class="search-box">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" id="searchInput" placeholder="Rechercher un stagiaire...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <div>
        <h1 class="page-title">Validation des Mentors</h1>
        <p class="page-sub">Gérez les demandes d'accès au statut de Mentor</p>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert alert-<?php echo $msg_type; ?>">
            <i class="fas fa-<?php echo $msg_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-num pending"><?php echo $stats['en_attente']; ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-box">
            <div class="stat-num valide"><?php echo $stats['valide']; ?></div>
            <div class="stat-label">Acceptés</div>
        </div>
        <div class="stat-box">
            <div class="stat-num refuse"><?php echo $stats['refuse']; ?></div>
            <div class="stat-label">Refusés</div>
        </div>
        <div class="stat-box">
            <div class="stat-num" style="color:var(--primary);"><?php echo array_sum($stats); ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>

    <!-- Cards -->
    <?php if (empty($mentor_requests)): ?>
        <div class="empty-state">
            <i class="fas fa-user-graduate"></i>
            <p>Aucune demande de Mentor en attente pour le moment.</p>
        </div>
    <?php else: ?>
    <div class="cards-grid" id="cardsContainer">
        <?php foreach ($mentor_requests as $req):
            $mInitials = strtoupper(substr($req['prenom_uti'], 0, 1) . substr($req['nom_uti'], 0, 1));
            $level     = max(1, floor(($req['score_global'] ?? 0) / 300));
        ?>
        <div class="request-card" data-name="<?php echo strtolower($req['prenom_uti'] . ' ' . $req['nom_uti']); ?>">

            <!-- Top -->
            <div class="card-top">
                <div class="avatar-lg"><?php echo $mInitials; ?></div>
                <div>
                    <div class="stag-name"><?php echo htmlspecialchars($req['prenom_uti'] . ' ' . $req['nom_uti']); ?></div>
                    <div class="stag-filiere"><i class="fas fa-graduation-cap" style="margin-right:4px;"></i><?php echo htmlspecialchars($req['filiere'] ?? ''); ?></div>
                    <?php if (!empty($req['date_demande'])): ?>
                        <div class="stag-date"><i class="far fa-clock" style="margin-right:3px;"></i><?php echo date('d/m/Y H:i', strtotime($req['date_demande'])); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Mini Stats -->
            <div class="card-stats">
                <div class="mini-stat">
                    <div class="mini-stat-num"><?php echo $req['score_global'] ?? 0; ?></div>
                    <div class="mini-stat-label">XP</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-num">Nv.<?php echo $level; ?></div>
                    <div class="mini-stat-label">Niveau</div>
                </div>
                <div class="mini-stat">
                    <div class="mini-stat-num" style="color:var(--green);"><?php echo $req['total_aides']; ?></div>
                    <div class="mini-stat-label">Aides acceptées</div>
                </div>
            </div>

            <!-- Message si كاين -->
            <?php if (!empty($req['message_demande'])): ?>
            <div class="msg-box">
                <i class="fas fa-comment-alt" style="margin-right:6px; color:var(--primary);"></i>
                <?php echo nl2br(htmlspecialchars($req['message_demande'])); ?>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="card-actions">
                <a href="validation_mentors.php?action=accepter&id_demande=<?php echo $req['id_demande_m']; ?>"
                   class="btn-accept"
                   onclick="return confirm('Accepter <?php echo addslashes($req['prenom_uti']); ?> comme Mentor ?')">
                    <i class="fas fa-check"></i> Accepter
                </a>
                <a href="validation_mentors.php?action=refuser&id_demande=<?php echo $req['id_demande_m']; ?>"
                   class="btn-refuse"
                   onclick="return confirm('Refuser cette demande ?')">
                    <i class="fas fa-times"></i> Refuser
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</main>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('.request-card').forEach(card => {
            card.style.display = card.getAttribute('data-name').includes(val) ? '' : 'none';
        });
    });
</script>

</body>
</html>