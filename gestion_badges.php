<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'administrateur', 'formateur'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId    = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$themeClass = 'theme-stagiaire';
if (in_array($user_role, ['admin', 'administrateur'])) $themeClass = 'theme-admin';
elseif ($user_role === 'formateur')                    $themeClass = 'theme-formateur';

$infosuser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$infosuser->execute([$userId]);
$user     = $infosuser->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "AD";

$success_msg = '';

if (isset($_GET['action']) && $_GET['action'] === 'supprimer') {
    $pdo->prepare("DELETE FROM obtenir WHERE id_utilisateur = ? AND id_badge = ?")->execute([(int)$_GET['uid'], (int)$_GET['bid']]);
    header("Location: gestion_badges.php?msg=supprime");
    exit();
}

if (isset($_POST['attribuer_badge'])) {
    $uid = (int) $_POST['id_utilisateur'];
    $bid = (int) $_POST['id_badge'];
    $chk = $pdo->prepare("SELECT COUNT(*) FROM obtenir WHERE id_utilisateur = ? AND id_badge = ?");
    $chk->execute([$uid, $bid]);
    if ($chk->fetchColumn() == 0) {
        $pdo->prepare("INSERT INTO obtenir (id_utilisateur, id_badge, date_obtention, statut) VALUES (?, ?, NOW(), 'Attribué')")->execute([$uid, $bid]);
    }
    header("Location: gestion_badges.php?msg=attribue");
    exit();
}

$msg = $_GET['msg'] ?? '';

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

$totalBadgesAttrib = $pdo->query("SELECT COUNT(*) FROM obtenir")->fetchColumn();
$totalStagiaires   = count($stagiaires);
$avgBadges         = $totalStagiaires > 0 ? round($totalBadgesAttrib / $totalStagiaires, 1) : 0;

function getStagiaireBadges($pdo, $uid) {
    $stmt = $pdo->prepare("SELECT o.id_badge, b.nom_badge, b.image_badge FROM obtenir o JOIN badges b ON o.id_badge = b.id_badge WHERE o.id_utilisateur = ?");
    $stmt->execute([$uid]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$dashboard_link = "dashboard.php";
if ($user_role  === 'formateur')                                   { $dashboard_link = "dashboard_formateur.php"; }
elseif ($user_role  === 'admin' || $user_role  === 'administrateur') { $dashboard_link = "dashboard_admin.php"; }

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
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }
        .notif-dot { background: var(--yellow); color: #1e293b; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: auto; }

        .main  { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .admin-badge { background: #d1fae5; color: var(--primary); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .avatar-top  { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; }

        .page-title { font-size: 24px; font-weight: 700; }
        .page-sub   { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-info    { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-card  { background: white; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,.06); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.10); }
        .stat-num   { font-size: 24px; font-weight: 700; }
        .stat-label { font-size: 13px; color: var(--text-muted); }

        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .section-title { font-size: 15px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }

        .badges-catalogue { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; }
        .badge-card { background: #fefce8; border: 1px solid #fde047; border-radius: 12px; padding: 16px; text-align: center; }
        .badge-icon { font-size: 28px; color: var(--yellow); margin-bottom: 8px; }
        .badge-name { font-size: 13px; font-weight: 700; margin-bottom: 4px; }
        .badge-desc { font-size: 11px; color: var(--text-muted); line-height: 1.4; }

        .table-card { background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; }
        .table-header { padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 15px; font-weight: 700; }
        .count-badge { background: #d1fae5; color: var(--primary); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; }
        th    { padding: 12px 18px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; border-bottom: 1px solid var(--border); }
        td    { padding: 14px 18px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .stag-info { display: flex; align-items: center; gap: 10px; }
        .av-sm     { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .stag-name { font-size: 14px; font-weight: 600; }
        .stag-xp   { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .mini-badge { display: inline-flex; align-items: center; gap: 5px; background: #fefce8; border: 1px solid #fde047; border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 600; color: #854d0e; margin: 2px; }
        .no-badge   { font-size: 12px; color: #94a3b8; font-style: italic; }

        .attrib-form { display: flex; gap: 8px; align-items: center; }
        .attrib-form select { padding: 7px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; font-family: inherit; outline: none; background: white; }
        .attrib-form select:focus { border-color: var(--primary); }
        .btn-attrib { background: var(--primary); color: white; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; white-space: nowrap; }
        .btn-attrib:hover { background: var(--primary-hover); }

        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
        .empty-state i { font-size: 36px; color: #cbd5e1; margin-bottom: 10px; display: block; }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .notif-dot { display: none; }
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
<?php if($user_role==="admin")include 'sidebar.php'; ?>
<?php if($user_role==="formateur")include 'sidebarform.php'; ?>
<?php if($user_role==="stagiaire")include 'sidebarstgpass.php'; ?>
<main class="main">
    <header>
        <div class="admin-badge"><i class="fas fa-medal"></i> Gestion des Badges</div>
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

    <div class="section-card">
        <div class="section-title">
            <span><i class="fas fa-chart-pie" style="color:var(--primary); margin-right:8px;"></i>Statistiques</span>
        </div>
        <div class="stats-grid">
            <div class="stat-card" style="border-left:4px solid var(--primary);">
                <i class="fas fa-users" style="font-size:28px; color:var(--primary);"></i>
                <div>
                    <div class="stat-num"><?php echo $totalStagiaires; ?></div>
                    <div class="stat-label">Stagiaires</div>
                </div>
            </div>
            <div class="stat-card" style="border-left:4px solid #f59e0b;">
                <i class="fas fa-medal" style="font-size:28px; color:#f59e0b;"></i>
                <div>
                    <div class="stat-num"><?php echo count($allBadges); ?></div>
                    <div class="stat-label">Types de badges</div>
                </div>
            </div>
            <div class="stat-card" style="border-left:4px solid #22c55e;">
                <i class="fas fa-award" style="font-size:28px; color:#22c55e;"></i>
                <div>
                    <div class="stat-num"><?php echo $totalBadgesAttrib; ?></div>
                    <div class="stat-label">Badges attribués</div>
                </div>
            </div>
            <div class="stat-card" style="border-left:4px solid #8b5cf6;">
                <i class="fas fa-chart-bar" style="font-size:28px; color:#8b5cf6;"></i>
                <div>
                    <div class="stat-num"><?php echo $avgBadges; ?></div>
                    <div class="stat-label">Moyenne / stagiaire</div>
                </div>
            </div>
        </div>
    </div>

    <div class="section-card">
        <div class="section-title">
            <span><i class="fas fa-th-large" style="color:#f59e0b; margin-right:8px;"></i>Catalogue des Badges</span>
        </div>
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

    <div class="table-card">
        <div class="table-header">
            <h3><i class="fas fa-users" style="margin-right:8px; color:var(--primary);"></i>Attribution par Stagiaire</h3>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="text" id="searchInput" placeholder="Rechercher..." style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; outline:none; width:180px;">
                <span class="count-badge"><?php echo $totalStagiaires; ?> stagiaire(s)</span>
            </div>
        </div>

        <?php if (empty($stagiaires)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>Aucun stagiaire trouvé.</p>
            </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Stagiaire</th>
                    <th>Stats</th>
                    <th>Badges actuels</th>
                    <th>Attribuer</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stagiaires as $s):
                    $stagBadges = getStagiaireBadges($pdo, $s['id_utilisateur']);
                    $stagInit   = strtoupper(substr($s['prenom_uti'], 0, 1) . substr($s['nom_uti'], 0, 1));
                    $level      = max(1, floor($s['score_global'] / 300));
                    $ownedIds   = array_column($stagBadges, 'id_badge');
                    $available  = array_filter($allBadges, fn($b) => !in_array($b['id_badge'], $ownedIds));
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
                        <span><i class="fas fa-award" style="color:var(--primary); margin-right:3px;"></i><?php echo $s['count_skills']; ?> comp.</span><br>
                        <span><i class="fas fa-hands-helping" style="color:var(--green); margin-right:3px;"></i><?php echo $s['count_aides']; ?> aides</span>
                    </td>
                    <td>
                        <?php if (!empty($stagBadges)): ?>
                            <?php foreach ($stagBadges as $b): ?>
                            <span class="mini-badge">
                                <i class="<?php echo htmlspecialchars($b['image_badge']); ?>"></i>
                                <?php echo htmlspecialchars($b['nom_badge']); ?>
                                <a href="gestion_badges.php?action=supprimer&uid=<?php echo $s['id_utilisateur']; ?>&bid=<?php echo $b['id_badge']; ?>"
                                   onclick="return confirm('Supprimer ce badge ?')"
                                   style="color:#94a3b8; margin-left:3px; text-decoration:none;">✕</a>
                            </span>
                            <?php endforeach; ?>
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
                            <span style="font-size:12px; color:var(--green); font-weight:600;"><i class="fas fa-check-circle"></i> Tous obtenus</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
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