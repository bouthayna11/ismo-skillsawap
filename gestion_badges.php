<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'administrateur', 'formateur'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$user_id    = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
// theme + dashboard link
$themeClass = 'theme-stagiaire';
if (in_array($user_role, ['admin', 'administrateur'])) $themeClass = 'theme-admin';
elseif ($user_role === 'formateur')                    $themeClass = 'theme-formateur';
$statCompPend   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend= $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();
// جلب معلومات المستخدم
$stmtAdmin = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$stmtAdmin->execute([$user_id]);
$admin    = $stmtAdmin->fetch();
$initials = $admin ? strtoupper(substr($admin['prenom_uti'], 0, 1) . substr($admin['nom_uti'], 0, 1)) : "AD";

$dashboard_link = in_array($user_role, ['admin', 'administrateur']) ? "dashboard_admin.php" : "dashboard_formateur.php";

$success_msg = '';

// =====================================================================
// معالجة حذف badge
// =====================================================================
if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    $uid = (int) $_GET['uid'];
    $bid = (int) $_GET['bid'];
    $pdo->prepare("DELETE FROM obtenir WHERE id_utilisateur = ? AND id_badge = ?")->execute([$uid, $bid]);
    header("Location: gestion_badges.php?msg=supprime");
    exit();
}

// =====================================================================
// معالجة إضافة badge
// =====================================================================
if (isset($_POST['attribuer_badge'])) {
    $uid = (int) $_POST['id_utilisateur'];
    $bid = (int) $_POST['id_badge'];

    // تحقق: ماعندوش بالفعل
    $chk = $pdo->prepare("SELECT COUNT(*) FROM obtenir WHERE id_utilisateur = ? AND id_badge = ?");
    $chk->execute([$uid, $bid]);
    if ($chk->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO obtenir (id_utilisateur, id_badge, date_obtention, statut) VALUES (?, ?, NOW(), 'Attribué')")->execute([$uid, $bid]);
    }
    header("Location: gestion_badges.php?msg=attribue");
    exit();
}

$msg = $_GET['msg'] ?? '';

// =====================================================================
// جلب البيانات
// =====================================================================
$stagiaires = $pdo->query(
    "SELECT u.id_utilisateur, u.nom_uti, u.prenom_uti,
            COALESCE(s.score_global, 0) AS score_global,
            (SELECT COUNT(*) FROM validation WHERE id_stagiaire = u.id_utilisateur AND etat_validation = 'valide') AS count_skills,
            (SELECT COUNT(*) FROM reponses_aide WHERE id_aidant = u.id_utilisateur AND statut = 'accepte') AS count_aides,
            (SELECT COUNT(*) FROM obtenir WHERE id_utilisateur = u.id_utilisateur) AS count_badges
     FROM utilisateur u
     LEFT JOIN stagiaire s ON u.id_utilisateur = s.id_utilisateur
     WHERE u.role = 'stagiaire'
     ORDER BY score_global DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$allBadges = $pdo->query("SELECT id_badge, nom_badge, description_badge, image_badge FROM badges ORDER BY id_badge")->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalBadgesAttrib = $pdo->query("SELECT COUNT(*) FROM obtenir")->fetchColumn();
$totalStagiaires   = count($stagiaires);
$avgBadges         = $totalStagiaires > 0 ? round($totalBadgesAttrib / $totalStagiaires, 1) : 0;

function getStagiaireBadges($pdo, $uid) {
    $stmt = $pdo->prepare("SELECT o.id_badge, b.nom_badge, b.image_badge, b.description_badge FROM obtenir o JOIN badges b ON o.id_badge = b.id_badge WHERE o.id_utilisateur = ?");
    $stmt->execute([$uid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <title>ISMO SkillSwap - Gestion des Badges</title>
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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }
                .theme-stagiaire { --sidebar-bg:#1e293b; --primary:#6366f1; --primary-hover:#4f46e5; }
        .theme-formateur { --sidebar-bg:#430b54; --primary:#df64d4; --primary-hover:#8447ac; }
        .theme-admin     { --sidebar-bg:#0c540b; --primary:#198218; --primary-hover:#1595b6; }
        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }

        /* Main */
        .main  { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }

        .page-title { font-size: 24px; font-weight: 700; }
        .page-sub   { font-size: 14px; color: var(--text-muted); margin-top: 4px; }

        /* Alertes */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-info    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

        /* Stats */
        .stats-bar { display: flex; gap: 16px; flex-wrap: wrap; }
        .stat-box  { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 16px 22px; flex: 1; min-width: 120px; text-align: center; }
        .stat-num  { font-size: 26px; font-weight: 700; color: var(--primary); }
        .stat-label{ font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        /* Catalogue badges */
        .badges-catalogue { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; margin-bottom: 8px; }
        .badge-card { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 16px; text-align: center; }
        .badge-icon { font-size: 28px; color: var(--yellow); margin-bottom: 8px; }
        .badge-name { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
        .badge-desc { font-size: 11px; color: var(--text-muted); line-height: 1.4; }

        /* Table */
        .table-card { background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; }
        .table-header { padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 15px; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; }
        th    { padding: 12px 18px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; border-bottom: 1px solid var(--border); }
        td    { padding: 14px 18px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #fafafa; }

        .stag-info { display: flex; align-items: center; gap: 10px; }
        .av-sm     { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .stag-name { font-size: 14px; font-weight: 600; }
        .stag-xp   { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .mini-badges { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .mini-badge  { display: inline-flex; align-items: center; gap: 5px; background: #fefce8; border: 1px solid #fde047; border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 600; color: #854d0e; }
        .mini-badge i { font-size: 12px; color: var(--yellow); }
        .del-badge   { background: none; border: none; color: #cbd5e1; cursor: pointer; font-size: 11px; margin-left: 2px; padding: 0; }
        .del-badge:hover { color: var(--red); }
        .no-badge    { font-size: 12px; color: #94a3b8; font-style: italic; }

        .attrib-form { display: flex; gap: 8px; align-items: center; }
        .attrib-form select { padding: 7px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; outline: none; background: white; }
        .attrib-form select:focus { border-color: var(--primary); }
        .btn-attrib { background: var(--primary); color: white; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .btn-attrib:hover { background: var(--primary-hover); }

        .count-badge { background: #eef2ff; color: var(--primary); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .table-card { overflow-x: auto; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
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
        <a href="validation_competence.php" class="menu-item"><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
        <a href="validation_mentors.php"    class="menu-item "><i class="fas fa-user-shield"></i>     <span>Valider Mentors </span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a>        <a href="gestion_badges.php" class="menu-item active"><i class="fas fa-medal"></i>            <span>Gestion Badges</span></a>
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
        <div class="avatar-top"><?php echo $initials; ?></div>
    </header>

    <div>
        <h1 class="page-title">Gestion des Badges</h1>
        <p class="page-sub">Attribuez et gérez les distinctions des stagiaires</p>
    </div>

    <?php if ($msg === 'attribue'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Badge attribué avec succès !</div>
    <?php elseif ($msg === 'supprime'): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle"></i> Badge supprimé.</div>
    <?php endif; ?>
       <!-- Stats -->
    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-num"><?php echo $totalStagiaires; ?></div>
            <div class="stat-label">Stagiaires</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo count($allBadges); ?></div>
            <div class="stat-label">Types de badges</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo $totalBadgesAttrib; ?></div>
            <div class="stat-label">Badges attribués</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo $avgBadges; ?></div>
            <div class="stat-label">Moyenne par stagiaire</div>
        </div>
    </div>
 <div class="table-card">
        <div class="table-header">
            <h3><i class="fas fa-users" style="margin-right:8px; color:var(--primary);"></i>Attribution par Stagiaire</h3>
            <span class="count-badge"><?php echo $totalStagiaires; ?> stagiaire(s)</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Stagiaire</th>
                    <th>Stats</th>
                    <th>Badges actuels</th>
                    <th>Attribuer un badge</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stagiaires as $s):
                    $stagBadges = getStagiaireBadges($pdo, $s['id_utilisateur']);
                    $stagInit   = strtoupper(substr($s['prenom_uti'], 0, 1) . substr($s['nom_uti'], 0, 1));
                    $level      = max(1, floor($s['score_global'] / 300));

                    // الكومبيتانس المتاحة (اللي ماعندوش بعد)
                    $ownedIds  = array_column($stagBadges, 'id_badge');
                    $available = array_filter($allBadges, fn($b) => !in_array($b['id_badge'], $ownedIds));
                ?>
                <tr class="stag-row" data-name="<?php echo strtolower($s['prenom_uti'] . ' ' . $s['nom_uti']); ?>">
                    <td>
                        <div class="stag-info">
                            <div class="av-sm"><?php echo $stagInit; ?></div>
                            <div>
                                <div class="stag-name"><?php echo htmlspecialchars($s['prenom_uti'] . ' ' . $s['nom_uti']); ?></div>
                                <div class="stag-xp"><?php echo $s['score_global']; ?> XP • Nv.<?php echo $level; ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:13px; color:var(--text-muted);">
                        <span title="Compétences validées"><i class="fas fa-award" style="color:var(--primary); margin-right:3px;"></i><?php echo $s['count_skills']; ?> comp.</span><br>
                        <span title="Aides acceptées"><i class="fas fa-hands-helping" style="color:var(--green); margin-right:3px;"></i><?php echo $s['count_aides']; ?> aides</span>
                    </td>
                    <td>
                        <?php if (!empty($stagBadges)): ?>
                            <div class="mini-badges">
                                <?php foreach ($stagBadges as $b): ?>
                                <span class="mini-badge">
                                    <i class="<?php echo htmlspecialchars($b['image_badge']); ?>"></i>
                                    <?php echo htmlspecialchars($b['nom_badge']); ?>
                                    <a href="gestion_badges.php?action=supprimer&uid=<?php echo $s['id_utilisateur']; ?>&bid=<?php echo $b['id_badge']; ?>"
                                       onclick="return confirm('Supprimer ce badge ?')"
                                       style="color:#94a3b8; margin-left:3px; text-decoration:none; font-size:11px;">✕</a>
                                </span>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <span class="no-badge">Aucun badge</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($available)): ?>
                        <form method="POST" action="gestion_badges.php" class="attrib-form">
                            <input type="hidden" name="id_utilisateur" value="<?php echo $s['id_utilisateur']; ?>">
                            <select name="id_badge">
                                <?php foreach ($available as $b): ?>
                                    <option value="<?php echo $b['id_badge']; ?>"><?php echo htmlspecialchars($b['nom_badge']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="attribuer_badge" class="btn-attrib">
                                <i class="fas fa-plus"></i> Attribuer
                            </button>
                        </form>
                        <?php else: ?>
                            <span style="font-size:12px; color:var(--green); font-weight:600;"><i class="fas fa-check-circle"></i> Tous les badges obtenus</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Catalogue badges -->
    <div class="table-card" style="padding:24px;">
        <h3 style="font-size:15px; font-weight:700; margin-bottom:16px;">
            <i class="fas fa-th-large" style="margin-right:8px; color:var(--yellow);"></i>Catalogue des Badges
        </h3>
        <div class="badges-catalogue">
            <?php foreach ($allBadges as $b): ?>
            <div class="badge-card">
                <div class="badge-icon"><i class="<?php echo htmlspecialchars($b['image_badge']); ?>"></i></div>
                <div class="badge-name"><?php echo htmlspecialchars($b['nom_badge']); ?></div>
                <div class="badge-desc"><?php echo htmlspecialchars($b['description_badge']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</main>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('.stag-row').forEach(row => {
            row.style.display = row.getAttribute('data-name').includes(val) ? '' : 'none';
        });
    });
</script>

</body>
</html>