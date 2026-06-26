<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'administrateur'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId    = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$themeClass = in_array($user_role, ['admin', 'administrateur']) ? 'theme-admin' : 'theme-stagiaire';

$infosuser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$infosuser->execute([$userId]);
$user     = $infosuser->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "AD";

$statCompPend    = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend = $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();

$success_msg = '';
$error_msg   = '';

// معالجة الأكشن (تغيير رول / حذف / تغيير statut)
if (isset($_GET['action'], $_GET['id_user'])) {
    $action  = $_GET['action'];
    $id_user = (int) $_GET['id_user'];

    if ($id_user === $userId) {
        $error_msg = "Vous ne pouvez pas modifier votre propre compte.";
    } else {
        try {
            if ($action === 'activer') {
                $pdo->prepare("UPDATE utilisateur SET is_verified = 1 WHERE id_utilisateur = ?")->execute([$id_user]);
                $success_msg = "Compte activé.";
            } elseif ($action === 'suspendre') {
                $pdo->prepare("UPDATE utilisateur SET is_verified = 0 WHERE id_utilisateur = ?")->execute([$id_user]);
                $success_msg = "Compte suspendu.";
            } elseif ($action === 'supprimer') {
                $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?")->execute([$id_user]);
                $success_msg = "Utilisateur supprimé.";
            }
        } catch (Exception $e) {
            $error_msg = "Erreur : " . $e->getMessage();
        }
    }
}
// فلتر
$filterRole = $_GET['role'] ?? 'tous';
$search     = trim($_GET['search'] ?? '');

$where  = "WHERE 1=1";
$params = [];
if ($filterRole !== 'tous') { $where .= " AND role = ?"; $params[] = $filterRole; }
if (!empty($search))        { $where .= " AND (prenom_uti LIKE ? OR nom_uti LIKE ? OR email_uti LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$stmt = $pdo->prepare("SELECT * FROM utilisateur $where ORDER BY id_utilisateur DESC");
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$totalUsers   = $pdo->query("SELECT COUNT(*) FROM utilisateur")->fetchColumn();
$totalStag    = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role = 'stagiaire'")->fetchColumn();
$totalForm    = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role = 'formateur'")->fetchColumn();
$totalAdmin   = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE role IN ('admin','administrateur')")->fetchColumn();
$totalActifs  = $pdo->query("SELECT COUNT(*) FROM utilisateur WHERE is_verified = 1")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Gérer Utilisateurs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .theme-admin { --sidebar-bg:#0c540b; --primary:#198218; --primary-hover:#1595b6; }

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

        /* Sidebar */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }
        .notif-dot { background: var(--yellow); color: #1e293b; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: auto; }

        /* Main */
        .main  { flex: 1; padding: 40px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .admin-badge { background: #d1fae5; color: var(--primary); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .avatar-top  { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; }

        .page-title { font-size: 24px; font-weight: 700; }
        .page-sub   { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* Alertes */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .stat-card  { background: white; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,.06); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.10); }
        .stat-num   { font-size: 24px; font-weight: 700; }
        .stat-label { font-size: 13px; color: var(--text-muted); }

        /* Section */
        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }

        /* Filtres */
        .filter-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 20px; }
        .filter-row form { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; flex: 1; }
        .search-inp { padding: 9px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; font-family: inherit; outline: none; min-width: 220px; }
        .search-inp:focus { border-color: var(--primary); }
        .filter-select { padding: 9px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; font-family: inherit; outline: none; background: white; }
        .filter-select:focus { border-color: var(--primary); }
        .btn-filter { background: var(--primary); color: white; border: none; padding: 9px 18px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .btn-filter:hover { background: var(--primary-hover); }
        .btn-reset  { background: white; color: var(--text-muted); border: 1px solid var(--border); padding: 9px 14px; border-radius: 10px; font-size: 13px; font-weight: 600; text-decoration: none; }

        /* Tabs filtres rôle */
        .role-tabs { display: flex; gap: 6px; flex-wrap: wrap; }
        .role-tab  { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--border); color: var(--text-muted); background: white; transition: 0.2s; }
        .role-tab:hover  { border-color: var(--primary); color: var(--primary); }
        .role-tab.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* Table */
        .table-wrap { overflow-x: auto; }
        .count-badge { background: #d1fae5; color: var(--primary); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; }
        th    { padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; border-bottom: 1px solid var(--border); }
        td    { padding: 13px 16px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .user-info { display: flex; align-items: center; gap: 10px; }
        .av-sm     { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .u-name    { font-size: 14px; font-weight: 600; }
        .u-email   { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

        .role-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .role-stagiaire    { background: #eef2ff; color: #4f46e5; }
        .role-formateur    { background: #faf5ff; color: #a855f7; }
        .role-admin,
        .role-administrateur{ background: #fef2f2; color: #ef4444; }

        .status-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .status-1 { background: #dcfce7; color: #15803d; }
        .status-0 { background: #fef2f2; color: #dc2626; }

        /* Actions */
        .actions { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
        .btn-act  { border: none; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; }
        .btn-activer   { background: #dcfce7; color: #15803d; }
        .btn-suspendre { background: #fef9c3; color: #854d0e; }
        .btn-supprimer { background: #fef2f2; color: #dc2626; }
        .btn-activer:hover   { background: var(--green); color: white; }
        .btn-suspendre:hover { background: var(--yellow); color: white; }
        .btn-supprimer:hover { background: var(--red); color: white; }

        /* Role form inline */
        .role-form { display: flex; gap: 6px; align-items: center; }
        .role-form select { padding: 4px 8px; border: 1px solid var(--border); border-radius: 6px; font-size: 12px; font-family: inherit; outline: none; background: white; }
        .role-form button { background: var(--primary); color: white; border: none; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; display: block; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .notif-dot { display: none; }
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
    <p class="menu-title">SUPERVISION</p>
    <nav>
        <a href="dashboard_admin.php"      class="menu-item"><i class="fas fa-th-large"></i>   <span>Dashboard</span></a>
        <a href="profil.php"               class="menu-item"><i class="far fa-user"></i>        <span>Profil</span></a>

        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item"><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
        <a href="validation_mentors.php"    class="menu-item"><i class="fas fa-user-shield"></i>     <span>Valider Mentors</span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a>
        <a href="gestion_utilisateurs.php"  class="menu-item active"><i class="fas fa-users-cog"></i> <span>Gérer Utilisateurs</span></a>
        <a href="gestion_badges.php"        class="menu-item"><i class="fas fa-medal"></i>            <span>Gestion Badges</span></a>
        <a href="gestion_categories.php"    class="menu-item"><i class="fas fa-tags"></i>             <span>Gestion Catégories</span></a>

        <p class="menu-title">GÉNÉRAL</p>
        <a href="mentors.php"              class="menu-item"><i class="fas fa-user-graduate"></i>   <span>Mentors</span></a>
        <a href="demandes_aide.php"        class="menu-item"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
        <a href="satisfaction.php"         class="menu-item"><i class="far fa-star"></i>            <span>Avis & Satisfaction</span></a>
    </nav>
    <div style="margin-top:auto;">
        <a href="logout.php" class="menu-item" style="color:#f87171;"><i class="fas fa-power-off"></i> <span>Déconnexion</span></a>
    </div>
</aside>

<main class="main">
    <header>
        <div class="admin-badge"><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</div>
        <div class="avatar-top"><?php echo $initials; ?></div>
    </header>

    <div>
        <h1 class="page-title">Gérer les Utilisateurs</h1>
        <p class="page-sub">Consultez, modifiez et gérez tous les comptes de la plateforme</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="section-card">
        <div style="font-size:15px; font-weight:700; margin-bottom:16px;">
            <i class="fas fa-chart-pie" style="color:var(--primary); margin-right:8px;"></i>Vue d'ensemble
        </div>
        <div class="stats-grid">
            <div class="stat-card" style="border-left:4px solid var(--primary);">
                <i class="fas fa-users" style="font-size:28px; color:var(--primary);"></i>
                <div><div class="stat-num"><?php echo $totalUsers; ?></div><div class="stat-label">Total membres</div></div>
            </div>
            <div class="stat-card" style="border-left:4px solid #6366f1;">
                <i class="fas fa-user-graduate" style="font-size:28px; color:#6366f1;"></i>
                <div><div class="stat-num"><?php echo $totalStag; ?></div><div class="stat-label">Stagiaires</div></div>
            </div>
            <div class="stat-card" style="border-left:4px solid #a855f7;">
                <i class="fas fa-chalkboard-teacher" style="font-size:28px; color:#a855f7;"></i>
                <div><div class="stat-num"><?php echo $totalForm; ?></div><div class="stat-label">Formateurs</div></div>
            </div>
            <div class="stat-card" style="border-left:4px solid #ef4444;">
                <i class="fas fa-user-shield" style="font-size:28px; color:#ef4444;"></i>
                <div><div class="stat-num"><?php echo $totalAdmin; ?></div><div class="stat-label">Admins</div></div>
            </div>
            <div class="stat-card" style="border-left:4px solid #22c55e;">
                <i class="fas fa-check-circle" style="font-size:28px; color:#22c55e;"></i>
                <div><div class="stat-num"><?php echo $totalActifs; ?></div><div class="stat-label">Comptes actifs</div></div>
            </div>
        </div>
    </div>

    <!-- Filtres + Table -->
    <div class="section-card">
        <!-- Filtres -->
        <div class="filter-row">
            <form method="GET" action="gestion_utilisateurs.php">
                <input type="text" name="search" class="search-inp" placeholder="Rechercher nom ou email..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="role" class="filter-select">
                    <option value="tous" <?php echo $filterRole === 'tous' ? 'selected' : ''; ?>>Tous les rôles</option>
                    <option value="stagiaire"     <?php echo $filterRole === 'stagiaire'     ? 'selected' : ''; ?>>Stagiaires</option>
                    <option value="formateur"     <?php echo $filterRole === 'formateur'     ? 'selected' : ''; ?>>Formateurs</option>
                    <option value="admin"         <?php echo $filterRole === 'admin'         ? 'selected' : ''; ?>>Admins</option>
                </select>
                <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Filtrer</button>
                <a href="gestion_utilisateurs.php" class="btn-reset">Réinitialiser</a>
            </form>
            <span class="count-badge"><?php echo count($utilisateurs); ?> résultat(s)</span>
        </div>

        <!-- Table -->
        <?php if (empty($utilisateurs)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>Aucun utilisateur trouvé.</p>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Changer rôle</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($utilisateurs as $u):
                        $uInit  = strtoupper(substr($u['prenom_uti'], 0, 1) . substr($u['nom_uti'], 0, 1));
                        $role   = $u['role'] ?? 'stagiaire';
                        $actif  = (int)($u['is_verified'] ?? 0);
                        $isMe   = ($u['id_utilisateur'] == $userId);
                    ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="av-sm"><?php echo $uInit; ?></div>
                                <div>
                                    <div class="u-name"><?php echo htmlspecialchars($u['prenom_uti'] . ' ' . $u['nom_uti']); ?></div>
                                    <div class="u-email"><?php echo htmlspecialchars($u['email_uti']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="role-badge role-<?php echo $role; ?>"><?php echo ucfirst($role); ?></span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $actif; ?>">
                                <?php echo $actif ? '✅ Actif' : '🔴 Inactif'; ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!$isMe): ?>
                            <form method="POST" action="gestion_utilisateurs.php" class="role-form">
                                <input type="hidden" name="id_user" value="<?php echo $u['id_utilisateur']; ?>">
                                <select name="new_role">
                                    <option value="stagiaire"  <?php echo $role === 'stagiaire'  ? 'selected' : ''; ?>>Stagiaire</option>
                                    <option value="formateur"  <?php echo $role === 'formateur'  ? 'selected' : ''; ?>>Formateur</option>
                                    <option value="admin"      <?php echo $role === 'admin'      ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <button type="submit" name="change_role"><i class="fas fa-save"></i></button>
                            </form>
                            <?php else: ?>
                                <span style="font-size:12px; color:var(--text-muted); font-style:italic;">Vous</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$isMe): ?>
                            <div class="actions">
                                <?php if (!$actif): ?>
                                    <a href="gestion_utilisateurs.php?action=activer&id_user=<?php echo $u['id_utilisateur']; ?>"
                                       class="btn-act btn-activer" onclick="return confirm('Activer ce compte ?')">
                                        <i class="fas fa-check"></i> Activer
                                    </a>
                                <?php else: ?>
                                    <a href="gestion_utilisateurs.php?action=suspendre&id_user=<?php echo $u['id_utilisateur']; ?>"
                                       class="btn-act btn-suspendre" onclick="return confirm('Suspendre ce compte ?')">
                                        <i class="fas fa-ban"></i> Suspendre
                                    </a>
                                <?php endif; ?>
                                <a href="gestion_utilisateurs.php?action=supprimer&id_user=<?php echo $u['id_utilisateur']; ?>"
                                   class="btn-act btn-supprimer"
                                   onclick="return confirm('Supprimer définitivement <?php echo addslashes($u['prenom_uti']); ?> ?')">
                                    <i class="fas fa-trash"></i> Supprimer
                                </a>
                            </div>
                            <?php else: ?>
                                <span style="font-size:12px; color:var(--text-muted); font-style:italic;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

</body>
</html>
