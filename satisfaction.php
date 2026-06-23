<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'administrateur'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId    = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$queryUser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$queryUser->execute([$userId]);
$user     = $queryUser->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "AD";

$statCompPend    = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend = $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();

// =====================================================================
// جلب التقييمات من table notation
// =====================================================================
try {
    $avisList = $pdo->query(
        "SELECT n.score, n.commentaires, n.date_note,
                u_donneur.prenom_uti  AS donneur_prenom,  u_donneur.nom_uti  AS donneur_nom,
                u_receveur.prenom_uti AS receveur_prenom, u_receveur.nom_uti AS receveur_nom,
                d.Titre AS titre_demande
         FROM notation n
         JOIN utilisateur u_donneur  ON n.id_donneur  = u_donneur.id_utilisateur
         JOIN utilisateur u_receveur ON n.id_receveur = u_receveur.id_utilisateur
         LEFT JOIN demande_aide d    ON n.id_demande  = d.id_demande
         ORDER BY n.date_note DESC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $avisList = [];
}

// Stats
$totalAvis   = count($avisList);
$moyenneNote = $totalAvis > 0 ? round(array_sum(array_column($avisList, 'score')) / $totalAvis, 1) : 0;
$nbCinqEt    = count(array_filter($avisList, fn($a) => $a['score'] == 5));
$nbUnEt      = count(array_filter($avisList, fn($a) => $a['score'] <= 2));

function genererEtoiles($note) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $note ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>';
    }
    return $html;
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
        :root {
            --sidebar-bg:    #1e293b;
            --primary:       #4f46e5;
            --primary-hover: #3730a3;
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
        .admin-badge { background: #e0e7ff; color: var(--primary); padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
        .avatar-top  { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; }

        .page-title { font-size: 24px; font-weight: 700; }
        .page-sub   { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        /* Stats */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-card  { background: white; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,.06); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.10); }
        .stat-num   { font-size: 24px; font-weight: 700; }
        .stat-label { font-size: 13px; color: var(--text-muted); }

        /* Section card */
        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .section-title { font-size: 15px; font-weight: 700; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; }
        .count-badge { background: #e0e7ff; color: var(--primary); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        /* Moyenne globale */
        .moyenne-box { background: linear-gradient(135deg, #4f46e5, #7c3aed); border-radius: 16px; padding: 28px; color: white; text-align: center; }
        .moyenne-num { font-size: 56px; font-weight: 700; line-height: 1; }
        .moyenne-stars { font-size: 24px; color: #fbbf24; margin: 10px 0 6px; display: flex; justify-content: center; gap: 4px; }
        .moyenne-label { font-size: 14px; opacity: 0.85; }

        /* Review cards */
        .reviews-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; }
        .review-card  { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 10px; transition: 0.2s; }
        .review-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.06); border-color: #c7d2fe; }

        .review-top   { display: flex; align-items: center; gap: 12px; }
        .review-av    { width: 40px; height: 40px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; flex-shrink: 0; }
        .review-name  { font-size: 14px; font-weight: 700; }
        .review-meta  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .review-date  { margin-left: auto; font-size: 11px; color: #94a3b8; white-space: nowrap; }

        .stars        { color: #fbbf24; display: flex; gap: 2px; font-size: 13px; }
        .score-badge  { font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 20px; margin-left: 8px; }
        .score-5 { background: #dcfce7; color: #15803d; }
        .score-4 { background: #dbeafe; color: #1d4ed8; }
        .score-3 { background: #fefce8; color: #854d0e; }
        .score-2, .score-1 { background: #fef2f2; color: #dc2626; }

        .review-comment { font-size: 13px; color: #475569; line-height: 1.6; font-style: italic; padding: 10px 14px; background: white; border-radius: 8px; border-left: 3px solid #c7d2fe; }
        .review-demande { font-size: 11px; color: var(--text-muted); display: flex; align-items: center; gap: 5px; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }

        /* Filtre */
        .filter-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
        .filter-btn { padding: 6px 14px; border-radius: 20px; border: 1px solid var(--border); background: white; font-size: 12px; font-weight: 600; cursor: pointer; color: var(--text-muted); transition: 0.2s; }
        .filter-btn:hover, .filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

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
            .reviews-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

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
        <a href="gestion_utilisateurs.php"  class="menu-item"><i class="fas fa-users-cog"></i>       <span>Gérer Utilisateurs</span></a>
        <a href="gestion_badges.php"        class="menu-item"><i class="fas fa-medal"></i>            <span>Gestion Badges</span></a>
        <a href="gestion_categories.php"    class="menu-item"><i class="fas fa-tags"></i>             <span>Gestion catégories</span></a>

        <p class="menu-title">GENERAL</p>
        <a href="mentors.php"              class="menu-item"><i class="fas fa-user-graduate"></i>   <span>Mentors</span></a>
        <a href="demandes_aide.php"        class="menu-item"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
        <a href="satisfaction.php"         class="menu-item active"><i class="far fa-star"></i>     <span>Avis & Satisfaction</span></a>
                            <p class="menu-title" >COMPTE</p>
            <a href="logout.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </nav>
</aside>

<main class="main">
    <header>
        <div class="admin-badge"><i class="far fa-star"></i> Avis & Satisfaction</div>
        <div class="avatar-top"><?php echo $initials; ?></div>
    </header>

    <div>
        <h1 class="page-title">Retours & Satisfaction</h1>
        <p class="page-sub">Consultez les avis des stagiaires sur les mentors et les échanges</p>
    </div>

    <!-- Stats -->
    <div class="section-card">
        <div class="section-title">
            <span><i class="fas fa-chart-pie" style="color:var(--primary); margin-right:8px;"></i>Vue d'ensemble</span>
        </div>
        <div style="display:grid; grid-template-columns: 200px 1fr; gap:20px; align-items:center;">
            <div class="moyenne-box">
                <div class="moyenne-num"><?php echo $moyenneNote; ?></div>
                <div class="moyenne-stars"><?php echo genererEtoiles(round($moyenneNote)); ?></div>
                <div class="moyenne-label">Note moyenne / 5</div>
            </div>
            <div class="stats-grid">
                <div class="stat-card" style="border-left:4px solid #4f46e5;">
                    <i class="fas fa-comments" style="font-size:28px; color:#4f46e5;"></i>
                    <div>
                        <div class="stat-num"><?php echo $totalAvis; ?></div>
                        <div class="stat-label">Avis publiés</div>
                    </div>
                </div>
                <div class="stat-card" style="border-left:4px solid #22c55e;">
                    <i class="fas fa-star" style="font-size:28px; color:#fbbf24;"></i>
                    <div>
                        <div class="stat-num"><?php echo $nbCinqEt; ?></div>
                        <div class="stat-label">Notes parfaites (5★)</div>
                    </div>
                </div>
                <div class="stat-card" style="border-left:4px solid #ef4444;">
                    <i class="fas fa-thumbs-down" style="font-size:28px; color:#ef4444;"></i>
                    <div>
                        <div class="stat-num"><?php echo $nbUnEt; ?></div>
                        <div class="stat-label">Notes basses (≤2★)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste avis -->
    <div class="section-card">
        <div class="section-title">
            <span><i class="fas fa-list" style="color:var(--primary); margin-right:8px;"></i>Tous les avis</span>
            <span class="count-badge"><?php echo $totalAvis; ?> avis</span>
        </div>

        <!-- Filtres -->
        <div class="filter-row">
            <button class="filter-btn active" onclick="filterAvis('tous', this)">Tous</button>
            <button class="filter-btn" onclick="filterAvis('5', this)">⭐ 5 étoiles</button>
            <button class="filter-btn" onclick="filterAvis('4', this)">⭐ 4 étoiles</button>
            <button class="filter-btn" onclick="filterAvis('3', this)">⭐ 3 étoiles</button>
            <button class="filter-btn" onclick="filterAvis('2', this)">⭐ ≤2 étoiles</button>
        </div>

        <?php if (empty($avisList)): ?>
            <div class="empty-state">
                <i class="far fa-star"></i>
                <p>Aucun avis publié pour le moment.</p>
            </div>
        <?php else: ?>
        <div class="reviews-grid" id="reviewsGrid">
            <?php foreach ($avisList as $a):
                $ini = strtoupper(substr($a['donneur_prenom'], 0, 1) . substr($a['donneur_nom'], 0, 1));
                $scoreClass = 'score-' . min(5, max(1, (int)$a['score']));
            ?>
            <div class="review-card" data-score="<?php echo $a['score']; ?>">
                <div class="review-top">
                    <div class="review-av"><?php echo $ini; ?></div>
                    <div>
                        <div class="review-name"><?php echo htmlspecialchars($a['donneur_prenom'] . ' ' . $a['donneur_nom']); ?></div>
                        <div class="review-meta">
                            → <?php echo htmlspecialchars($a['receveur_prenom'] . ' ' . $a['receveur_nom']); ?>
                        </div>
                    </div>
                    <div class="review-date"><?php echo date('d/m/Y', strtotime($a['date_note'])); ?></div>
                </div>

                <div style="display:flex; align-items:center; gap:6px;">
                    <div class="stars"><?php echo genererEtoiles($a['score']); ?></div>
                    <span class="score-badge <?php echo $scoreClass; ?>"><?php echo $a['score']; ?>/5</span>
                </div>

                <?php if (!empty($a['commentaires'])): ?>
                <div class="review-comment"><?php echo htmlspecialchars($a['commentaires']); ?></div>
                <?php endif; ?>

                <?php if (!empty($a['titre_demande'])): ?>
                <div class="review-demande">
                    <i class="far fa-question-circle"></i>
                    <?php echo htmlspecialchars($a['titre_demande']); ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function filterAvis(score, btn) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.review-card').forEach(card => {
        if (score === 'tous') {
            card.style.display = '';
        } else if (score === '2') {
            card.style.display = parseInt(card.getAttribute('data-score')) <= 2 ? '' : 'none';
        } else {
            card.style.display = card.getAttribute('data-score') === score ? '' : 'none';
        }
    });
}
</script>

</body>
</html>