<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'administrateur')) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId = $_SESSION['user_id'];
if (isset($_GET['action']) && $_GET['action'] === 'attribuer' && isset($_GET['uid']) && isset($_GET['bid'])) {
    $uid = intval($_GET['uid']);
    $bid = intval($_GET['bid']);
    $stmt = $pdo->prepare("UPDATE obtenir SET statut = 'Attribué' WHERE id_utilisateur = ? AND id_badge = ?");
    $stmt->execute([$uid, $bid]);
    header("Location: gestion_badges.php");
    exit();
}
$queryBadges = "SELECT u.id_utilisateur, u.nom_uti, u.prenom_uti, b.id_badge, b.nom_badge, o.date_obtention, o.statut 
                FROM obtenir o
                JOIN utilisateur u ON o.id_utilisateur = u.id_utilisateur
                JOIN badges b ON o.id_badge = b.id_badge
                ORDER BY o.date_obtention DESC";

$stmtBadges = $pdo->query($queryBadges);
$badges = $stmtBadges->fetchAll(PDO::FETCH_ASSOC);
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
        :root { --sidebar-bg: #0f172a; --primary: #ef4444; --main-bg: #f8fafc; --border: #e2e8f0; --text-main: #1e293b; --text-muted: #64748b; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; color: #ffffff; }
        .menu-title { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }
        .main { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .avatar-top { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        .content-box { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 28px; }
        .section-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 20px; }
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        th { color: var(--text-muted); font-weight: 600; background: #f8fafc; }
        .btn-action { background: #6366f1; padding: 6px 12px; border: none; border-radius: 6px; color: white; text-decoration: none; font-size: 12px; font-weight: 600; transition: 0.2s; }
        .btn-action:hover { background: #4f46e5; }
        .badge-status { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-non { background: #fee2e2; color: #ef4444; }
        .status-oui { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h1>AdminPanel</h1>
        <p class="menu-title">SUPERVISION</p>
        <nav>
            <a href="dashboard_admin.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Vue Générale</span></a>
            <p class="menu-title">CONTRÔLE</p>
            <a href="gestion_utilisateurs.php" class="menu-item"><i class="fas fa-users-cog"></i> <span>Gérer Utilisateurs</span></a>
            <a href="gestion_badges.php" class="menu-item active"><i class="fas fa-medal"></i> <span>Gestion des Badges</span></a>
            <a href="satisfaction.php" class="menu-item"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
        </nav>
        <div style="margin-top: auto;">
            <a href="logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i> <span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="main">
        <header>
            <div style="font-weight: 600; color: var(--primary);"><i class="fas fa-medal"></i> Distinctions & Badges</div>
            <div class="avatar-top"><?php echo $initials; ?></div>
        </header>

        <div class="content-box">
            <h2 class="section-title">Attribution des Badges et Distinctions</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nom Complet</th>
                            <th>Badge Recommandé</th>
                            <th>Date de Demande</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($badges as $b): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($b['prenom_uti'] . ' ' . $b['nom_uti']); ?></td>
                            <td><i class="fas fa-medal" style="color: #fbbf24; margin-right:5px;"></i> <?php echo htmlspecialchars($b['badge_nom']); ?></td>
                            <td><?php echo htmlspecialchars($b['date_validation'] ?? $b['date']); ?></td>
                          <td>
    <?php if ($b['statut'] === "Non attribué"): ?>
        <a href="gestion_badges.php?action=attribuer&uid=<?php echo $b['id_utilisateur']; ?>&bid=<?php echo $b['id_badge']; ?>" class="btn-action">Attribuer</a>
    <?php else: ?>
        <span style="color: #065f46; font-weight:600;"><i class="fas fa-check"></i> Fait</span>
    <?php endif; ?>
</td>
                            <td>
                                <?php if ($statut === "Non attribué"): ?>
                                    <a href="gestion_badges.php?action=attribuer&id=<?php echo $b['id_validation']; ?>" class="btn-action">Attribuer</a>
                                <?php else: ?>
                                    <span style="color: #065f46; font-weight:600;"><i class="fas fa-check"></i> Fait</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>