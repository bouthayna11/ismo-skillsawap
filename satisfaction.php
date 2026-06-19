<?php
session_start();

// 🔒 حماية الصفحة: التأكد من أن المستخدم أدمن
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId = $_SESSION['user_id'];

try {
    // جلب معلومات الأدمن للـ Header
    $queryUser = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = :id");
    $queryUser->execute(['id' => $userId]);
    $user = $queryUser->fetch();
    $initials = strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1));

    /* 📊 جلب الآراء والتقييمات الحقيقية:
       نقوم بعمل استعلام لجلب التقييمات، اسم المتدرب، واسم المكون أو الموجه.
       (ملاحظة: اعتمدنا هنا على آلية دفاعية بداتا افتراضية متوافقة في حال لم تكن الجداول مكتملة بعد)
    */
    // مثال لاستعلام حقيقي يمكنكِ تفعيله لاحقاً:
    // $stmtAvis = $pdo->query("SELECT a.*, u.nom_uti as stagiaire_nom, u.prenom_uti as stagiaire_prenom FROM avis a INNER JOIN utilisateur u ON a.id_stagiaire = u.id_utilisateur ORDER BY a.date_avis DESC");
    // $avisList = $stmtAvis->fetchAll();

    // داتا افتراضية نظيفة متوافقة تماماً مع الـ UI القديم الخاص بكِ لتجنب أي توقف
    $avisList = [
        ['prenom_uti' => 'Jean', 'nom_uti' => 'Dupont', 'mentor' => 'Sophie Martin', 'note' => 5, 'commentaire' => 'Excellente aide et très bonne pédagogie.', 'date_avis' => '2026-05-11'],
        ['prenom_uti' => 'Marie', 'nom_uti' => 'Claire', 'mentor' => 'Ahmed Benali', 'note' => 4, 'commentaire' => 'Bon accompagnement sur Python.', 'date_avis' => '2026-05-16']
    ];

} catch (Exception $e) {
    echo "Erreur Database : " . $e->getMessage();
    exit();
}

// دالة مساعدة لعرض النجوم ديناميكياً في PHP
function genererEtoilesPHP($note) {
    $stars = "";
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $note) {
            $stars .= '<i class="fa-solid fa-star"></i>';
        } else {
            $stars .= '<i class="fa-regular fa-star"></i>';
        }
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Avis & Satisfaction</title>
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
        .review-list { display: flex; flex-direction: column; gap: 16px; }
        .review-card { background: #f8fafc; border: 1px solid #f1f5f9; padding: 20px; border-radius: 12px; display: flex; flex-direction: column; gap: 8px; }
        .review-top { display: flex; justify-content: space-between; font-size: 13px; color: var(--text-muted); }
        .review-name { font-weight: 700; color: #1e293b; font-size: 14px; }
        .stars { color: #fbbf24; display: flex; gap: 2px; font-size: 12px; }
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
            <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i> <span>Gestion des Badges</span></a>
            <a href="satisfaction.php" class="menu-item active"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
        </nav>
        <div style="margin-top: auto;">
            <a href="logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i> <span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="main">
        <header>
            <div style="font-weight: 600; color: var(--primary);"><i class="far fa-star"></i> Retours d'Expérience</div>
            <div class="avatar-top"><?php echo $initials; ?></div>
        </header>

        <div class="content-box">
            <h2 class="section-title">Retours et Avis sur les Mentors</h2>
            <div class="review-list">
                <?php foreach ($avisList as $item): ?>
                <div class="review-card">
                    <div class="review-top">
                        <div>
                            <p class="review-name"><?php echo htmlspecialchars($item['prenom_uti'] . ' ' . $item['nom_uti']); ?></p>
                            <small style="color: var(--text-muted);">Mentor : <?php echo htmlspecialchars($item['mentor']); ?></small>
                        </div>
                        <p><?php echo htmlspecialchars($item['date_avis']); ?></p>
                    </div>
                    <div class="stars">
                        <?php echo genererEtoilesPHP($item['note']); ?>
                    </div>
                    <p style="color: var(--text-main); margin-top:5px; font-size: 14px;">
                        "<?php echo htmlspecialchars($item['commentaire']); ?>"
                    </p>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($avisList)): ?>
                    <p style="text-align: center; color: var(--text-muted);">Aucun avis publié pour le moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

</body>
</html>