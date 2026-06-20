<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
if (isset($_GET['action']) && isset($_GET['id_user'])) {
    $action = $_GET['action'];
    $id_user = intval($_GET['id_user']);
    
    try {
        if ($action === 'activer') {
            $stmt = $pdo->prepare("UPDATE utilisateur SET statut = 'actif' WHERE id_utilisateur = ?");
            $stmt->execute([$id_user]);
        } elseif ($action === 'suspendre') {
            $stmt = $pdo->prepare("UPDATE utilisateur SET statut = 'suspendu' WHERE id_utilisateur = ?");
            $stmt->execute([$id_user]);
        }
        header("Location: gestion_utilisateurs.php?success=1");
        exit();
    } catch (Exception $e) {
    }
}

try {
    // جلب معلومات الأدمن للـ Header
    $queryUser = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = :id");
    $queryUser->execute(['id' => $userId]);
    $user = $queryUser->fetch();
    $initials = strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1));

    // 📊 جلب كاع المستخدمين من قاعدة البيانات حقيقيين
    $stmtUsers = $pdo->query("SELECT * FROM utilisateur ORDER BY id_utilisateur DESC");
    $utilisateurs = $stmtUsers->fetchAll();
} catch (Exception $e) {
    echo "Erreur Database : " . $e->getMessage();
    exit();
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
    <title>ISMO SkillSwap - Gérer Utilisateurs</title>
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
        .btn-action-active { background: #10b981; padding: 6px 12px; border: none; border-radius: 6px; color: white; text-decoration: none; font-size: 12px; font-weight: 600; margin-right: 5px; }
        .btn-action-suspend { background: #f97316; padding: 6px 12px; border: none; border-radius: 6px; color: white; text-decoration: none; font-size: 12px; font-weight: 600; }
        .role-badge { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; background: #e2e8f0; color: #334155; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h1>AdminPanel</h1>
        <p class="menu-title">SUPERVISION</p>
        <nav>
            <a href="dashboard_admin.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Vue Générale</span></a>
            <p class="menu-title">CONTRÔLE</p>
            <a href="gestion_utilisateurs.php" class="menu-item active"><i class="fas fa-users-cog"></i> <span>Gérer Utilisateurs</span></a>
            <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i> <span>Gestion des Badges</span></a>
            <a href="satisfaction.php" class="menu-item"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
        </nav>
        <div style="margin-top: auto;">
            <a href="logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i> <span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="main">
        <header>
            <div style="font-weight: 600; color: var(--primary);"><i class="fas fa-user-shield"></i> Gestion des Comptes</div>
            <div class="avatar-top"><?php echo $initials; ?></div>
        </header>

        <div class="content-box">
            <h2 class="section-title">Liste des Utilisateurs de la Plateforme</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Nom Complet</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u['prenom_uti'] . ' ' . $u['nom_uti']); ?></td>
                            <td><?php echo htmlspecialchars($u['email_uti']); ?></td>
                            <td><span class="role-badge"><?php echo htmlspecialchars($u['role']); ?></span></td>
                            <td>
                                <a href="gestion_utilisateurs.php?action=activer&id_user=<?php echo $u['id_utilisateur']; ?>" class="btn-action-active">Activer</a>
                                <a href="gestion_utilisateurs.php?action=suspendre&id_user=<?php echo $u['id_utilisateur']; ?>" class="btn-action-suspend">Suspendre</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($utilisateurs)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted);">Aucun utilisateur trouvé.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>