<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'formateur') {
    header("Location: login.php");
    exit();
}
require_once 'config.php';
$user_role = $_SESSION['user_role'];
$statCompPend   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend= $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();
$userId = $_SESSION['user_id'];
$queryUser = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
$queryUser->execute([$userId]);
$user = $queryUser->fetch();
$userNomComplet = $user['prenom_uti'] . ' ' . $user['nom_uti'];
$statClasses = 0; 
$initials = strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1));
if (isset($_GET['action']) && isset($_GET['id_demande'])) {
    $action = $_GET['action'];
    $id_demande = (int)$_GET['id_demande'];
    
    if ($action === 'accepter') {
        $stmt = $pdo->prepare("UPDATE demande_mentorat SET statut = 'valide' WHERE id_demande_m = ?");
        $stmt->execute([$id_demande]);
    } elseif ($action === 'refuser') {
        $stmt = $pdo->prepare("UPDATE demande_mentorat SET statut = 'refuse' WHERE id_demande_m = ?");
        $stmt->execute([$id_demande]);
    }
    header("Location: dashboard_formateur.php");
    exit();
}
$queryRequests = $pdo->query(
    "SELECT 
        d.id_demande_m, 
        u.nom_uti, 
        u.prenom_uti, 
        s.score_global,
        (SELECT COUNT(*) FROM demande_aide WHERE id_stagiaire = u.id_utilisateur) as total_aides
     FROM demande_mentorat d
     INNER JOIN utilisateur u ON d.id_stagiaire = u.id_utilisateur
     INNER JOIN stagiaire s ON u.id_utilisateur = s.id_utilisateur
     WHERE d.statut = 'en_attente' AND d.message_demande IS NULL"
);
$mentor_requests = $queryRequests->fetchAll(PDO::FETCH_ASSOC);
$mentor_requests = $queryRequests->fetchAll(PDO::FETCH_ASSOC);
$statGlobalValidated = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'valide'")->fetchColumn();
$stmtValid = $pdo->prepare("SELECT COUNT(*) FROM validation WHERE id_formateur = ? AND etat_validation = 'valide'");
$stmtValid->execute([$userId]);
$statMyValidations = $stmtValid->fetchColumn();
$dashboard_link = "dashboard.php";
if ($user_role === 'formateur')                                    { $dashboard_link = "dashboard_formateur.php"; }
elseif (in_array($user_role, ['administrateur', 'admin']))         { $dashboard_link = "dashboard_admin.php"; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Dashboard Formateur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { 
            --sidebar-bg: #430b54; 
            --primary: #df64d4; 
            --primary-hover: #8447ac;
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
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }
        
        /* Main */
        .main { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; width: 100%; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }
        
        .page-title { font-size: 24px; font-weight: 700; margin-top: 10px; }
        .welcome-sub { color: var(--text-muted); font-size: 14px; margin-bottom: 10px; }
        
        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .card-stat { background: white; padding: 24px; border-radius: 16px; border: 1px solid var(--border); transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-2px); }
        .card-stat .val { font-size: 32px; font-weight: 700; color: var(--primary); display: block; margin-bottom: 6px; }
        .card-stat .label { color: var(--text-muted); font-size: 14px; font-weight: 500; }
        
        /* Layout Panels */
        .middle-row { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; margin-bottom: 1px; }
        .section-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        
        /* Tables & Lists */
        .mentor-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        .mentor-table th { color: var(--text-muted); font-weight: 600; padding: 12px; border-bottom: 2px solid var(--border); }
        .mentor-table td { padding: 16px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        
        /* Badges & Buttons */
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .badge-nv { background: #eef2ff; color: #4f46e5; }
        .badge-help { background: #f0fdf4; color: #166534; }
        
        .btn-status { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-block; margin-right: 5px; }
        .btn-accept { background: var(--success); color: white; }
        .btn-accept:hover { background: #059669; }
        .btn-refuse { background: var(--danger); color: white; }
        .btn-refuse:hover { background: #dc2626; }
        
        .msg-locked { color: var(--text-muted); font-style: italic; font-size: 12px; background: #f1f5f9; padding: 6px 10px; border-radius: 6px; display: inline-block; }
        .empty-msg { color: var(--text-muted); font-size: 14px; text-align: center; padding: 20px 0; }

        .act-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .act-item:last-child { border-bottom: none; }
        .act-dot { color: var(--primary); margin-right: 10px; font-size: 8px; }
        .act-time { color: var(--text-muted); font-size: 13px; }
        
        .btn-action-swap { display: inline-flex; align-items: center; gap: 8px; background: var(--primary); color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; margin-top: 10px; border: none; cursor: pointer; transition: 0.2s; }
        .btn-action-swap:hover { background: var(--primary-hover); }
        @media (max-width: 1024px) { .sidebar { width: 80px; padding: 20px 10px; } .sidebar h1, .sidebar span, .menu-title { display: none; } .menu-item { justify-content: center; padding: 12px; } .menu-item i { margin-right: 0; } .middle-row { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { body { flex-direction: column; } .sidebar { width: 100%; height: auto; flex-direction: row; overflow-x: auto; padding: 10px; } .sidebar h1 { display: none; } .sidebar nav { display: flex; flex-direction: row; } .main { padding: 20px; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">

    <aside class="sidebar">
        <h1>ISMO SkillSwap</h1>
        <p class="menu-title">GENERAL</p>
<nav>
    <a href="dashboard_formateur.php" class="menu-item active"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
    <a href="profil.php" class="menu-item"><i class="far fa-user"></i> <span>Profil</span></a>

    <p class="menu-title">ACTIVITÉ</p>
    <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i> <span>Offres d'aide</span></a>
    <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>
    <a href="passport.php" class="menu-item"><i class="fas fa-id-card"></i> <span>Passeport</span></a>
    <a href="mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
    <?php if (in_array($user_role, ['admin', 'formateur'])): ?>
        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item"><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
        <a href="validation_mentors.php"    class="menu-item"><i class="fas fa-user-shield"></i>     <span>Valider Mentors </span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a>
        <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i> <span>Gestion Badges</span></a>
         <p class="menu-title">GÉNÉRAL</p>
        <a href="satisfaction.php"  class="menu-item"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
        <?php endif; ?>
            <a href="logout.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
</nav>
    </aside>
    <main class="main">
        <header>
            <div class="search-box">
                <i class="fas fa-search" style="color: #94a3b8;"></i>
                <input type="text" placeholder="Rechercher des stagiaires, compétences...">
            </div>
            <div class="avatar-top"><?php echo $initials; ?></div>
        </header>

        <div>
            <h2 class="page-title">Espace Formateur</h2>
            <p class="welcome-sub">Bonjour M./Mme. <?php echo htmlspecialchars($userNomComplet); ?></p>
        </div>

        <div class="stats-grid">
            <div class="card-stat"><span class="val"><?php echo $statClasses; ?></span><span class="label">Classes gérées</span></div>
            <div class="card-stat"><span class="val"><?php echo $statGlobalValidated; ?></span><span class="label">Total compétences validées</span></div>
            <div class="card-stat"><span class="val"><?php echo $statMyValidations; ?></span><span class="label">Compétences validées par vous</span></div>
        </div>
        <div class="section-card">
            <h3><i class="fas fa-user-shield" style="color: var(--primary);"></i> Gestion des Demandes Mentors</h3>
            <?php if (empty($mentor_requests)): ?>
                <p class="empty-msg">Aucune demande d'inscription en attente.</p>
            <?php else: ?>
                <table class="mentor-table">
                    <thead>
                        <tr>
                            <th>Stagiaire</th>
                            <th>Score XP</th>
                            <th>Niveau</th>
                            <th>Aides Données</th>
                            <th>Actions / Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mentor_requests as $req): 
                            $level = max(1, floor($req['score_global'] / 300));
                            $total_aides = $req['total_aides'];
                            $eligible = ($level >= 3 && $total_aides > 5);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($req['prenom_uti'] . ' ' . $req['nom_uti']); ?></strong></td>
                            <td><?php echo $req['score_global']; ?> XP</td>
                            <td><span class="badge badge-nv">Nv. <?php echo $level; ?></span></td>
                            <td><span class="badge badge-help"><?php echo $total_aides; ?> aides</span></td>
                            <td>
                                <?php if ($eligible): ?>
                                    <a href="dashboard_formateur.php?action=accepter&id_demande=<?php echo $req['id_demande_m']; ?>" class="btn-status btn-accept" onclick="return confirm('Accepter ce stagiaire comme Mentor ?')">Accepter</a>
                                    <a href="dashboard_formateur.php?action=refuser&id_demande=<?php echo $req['id_demande_m']; ?>" class="btn-status btn-refuse" onclick="return confirm('Refuser cette demande ?')">Refuse</a>
                                <?php else: ?>
                                    <span class="msg-locked"><i class="fas fa-lock"></i> Critères non atteints (Niveau 3 + 5 aides requis)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="middle-row">
            <div class="section-card">
                <h3>Suivi des validations</h3>
                <div class="act-item">
                    <span><span class="act-dot">•</span> 3 nouvelles demandes de validation de compétences en attente.</span>
                    <span class="act-time">il y a 10 min</span>
                </div>
            </div>
            
            <div class="section-card">
                <h3>Actions rapides</h3>
                <button class="btn-action-swap" onclick="window.location.href='validation_competence.php'">
                    <i class="fas fa-check-circle"></i> Valider des compétences
                </button>
                 <button class="btn-action-swap" onclick="window.location.href='gestion_badges.php'">
                    <i class="fas fa-check-circle"></i> Attribuer des badges
                </button>
            </div>
        </div>
    </main>

</body>
</html>