<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'stagiaire') {
    header("Location: login.php");
    exit();
}
require_once 'config.php';
$user_id= $_SESSION['user_id'];
//declare d'une variable qui compte le nombre des demandes d'user_id
$nbdemandes= 0;
//declare d'une variable qui compte le nombre des skilss (competence) d'user_id
$nbcomp = 0;
//declare d'une variable qui compte le nombre d'aides d'user_id
$nbaides = 0;
$xp = 0;
$niveau = 1;
$progress_percentage = 0;
$initials = "";
$nom_complet = "";
$activities = [];
$xp_dans_le_niveau = 0;
try {
    //henia kanjibo ism filliere d stagairee
    $Users= $pdo->prepare("SELECT u.prenom_uti, u.nom_uti, s.filiere 
        FROM utilisateur u 
        LEFT JOIN stagiaire s ON u.id_utilisateur = s.id_utilisateur 
        WHERE u.id_utilisateur = ?");
    $Users->execute([$user_id]);
    $user = $Users->fetch();
    //ila kan kayn had user kanemlolo initial dyalo (Awel harf mn nom awel harf mn prenom)
    if ($user) {
        $nom_complet = htmlspecialchars($user['prenom_uti'] . ' ' . $user['nom_uti']);
        $initials = strtoupper(substr($user['prenom_uti'] , 0, 1) . substr($user['nom_uti'] , 0, 1));
        $_SESSION['user_filiere'] = $user['filiere'] ?? 'Digital Development';
    } else {
        $nom_complet = "Stagiaire";
        $initials = "ST";
    }  
    //henia kanjibo leadad d demandes li emloum had stagire b count   
    $demandes = $pdo->prepare("SELECT COUNT(*) FROM demande_aide WHERE id_stagiaire = ?");
    $demandes->execute([$user_id]);
    $nbdemandes= $demandes->fetchColumn();
    //henia kanjibo leadad d competence mvalidyin li emloum had stagire b count  
    $competences = $pdo->prepare("SELECT COUNT(*) FROM validation WHERE id_stagiaire = ? AND etat_validation = 'valide'");
    $competences->execute([$user_id]);
    $nbcomp = $competences->fetchColumn();
    //henia kanjibo leadad d aide li emloum had stagire b count  
    $Aides = $pdo->prepare("SELECT COUNT(*) FROM reponses_aide WHERE id_aidant = ?");
    $Aides->execute([$user_id]);
    $nbaides = $Aides->fetchColumn(); 
    //henia kanhesbo xp dyalo ela hasab nbcompetnce dyalo o chehal mn aide emel 
    $xp = ($nbcomp * 1200) + ($nbdemandes* 300);
        $niveau = 1 + floor($xp / 2000); 
    $xp_dans_le_niveau = $xp % 2000;
    $progress_percentage = ($xp_dans_le_niveau / 2000) * 100;
    //henia kanemlo update l score_global dyalo b xp li hesbna
$newxp = $pdo->prepare("UPDATE stagiaire SET score_global =? WHERE id_utilisateur =?");
$newxp->execute([$xp,$user_id]);
//henia kanjibo agherr haja emla bhal chi demandes emla 
$lastrecent = $pdo->prepare("SELECT titre, technologie FROM demande_aide WHERE id_stagiaire = ? ORDER BY id_demande DESC LIMIT 3");
$lastrecent->execute([$user_id]);
$activities = $lastrecent->fetchAll();
//henia kanjibo lbadgat li eandoo li tetawlo mn eand admin wla formateur
$badges = $pdo->prepare("SELECT b.nom_badge, b.description_badge, b.image_badge 
    FROM badges b
    JOIN obtenir o ON b.id_badge = o.id_badge
    WHERE o.id_utilisateur = ?");
$badges->execute([$user_id]);
$mes_badges = $badges->fetchAll(PDO::FETCH_ASSOC);
$queryMentors = $pdo->query(
    "SELECT u.id_utilisateur, u.nom_uti, u.prenom_uti, s.score_global
     FROM utilisateur u
     INNER JOIN stagiaire s ON u.id_utilisateur = s.id_utilisateur
     INNER JOIN demande_mentorat d ON s.id_utilisateur = d.id_stagiaire
     WHERE d.statut = 'valide'
     GROUP BY u.id_utilisateur
     ORDER BY s.score_global DESC"
);
$all_mentors = $queryMentors->fetchAll(PDO::FETCH_ASSOC);

//competence dyal mentors
$mentor_skills = [];
foreach ($all_mentors as $m) {
    $stmtSkills = $pdo->prepare(
        "SELECT c.nom_comp FROM maitriser ma
         JOIN competence c ON ma.id_comp = c.id_comp
         WHERE ma.id_utilisateur = :id LIMIT 4"
    );
    $stmtSkills->execute(['id' => $m['id_utilisateur']]);
    $mentor_skills[$m['id_utilisateur']] = $stmtSkills->fetchAll(PDO::FETCH_COLUMN);
}
} catch (Exception $e) {
    echo "<div style='color:red;'>Erreur en connexion avec les donnes de base donne: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Dashboard Stagiaire</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1e293b;
            --primary: #6366f1;
            --main-bg: #f8fafc;
            --border: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; transition: 0.3s; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }

        .main { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; width: 100%; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; border: 2px solid white; box-shadow: 0 0 0 1px var(--border); }
        .page-title { font-size: 24px; font-weight: 700; margin-top: 10px; }
        .welcome-sub { color: var(--text-muted); font-size: 14px; margin-bottom: 10px; }

        .level-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; display: flex; justify-content: space-between; align-items: center; }
        .level-info h3 { font-size: 20px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; gap: 10px; }
        .xp-badge { background: #eef2ff; color: var(--primary); font-size: 12px; padding: 4px 8px; border-radius: 6px; font-weight: 600; }
        .stars { color: #fbbf24; font-size: 14px; margin-bottom: 20px; display: flex; gap: 4px; }
        .progress-text { font-size: 13px; color: var(--text-muted); margin-bottom: 8px; }
        .bar-bg { width: 100%; height: 8px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
        .bar-fill { height: 100%; background: var(--primary); border-radius: 10px; }
        .level-avatar { width: 64px; height: 64px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .card-stat { background: white; padding: 24px; border-radius: 16px; border: 1px solid var(--border); }
        .card-stat .val { font-size: 32px; font-weight: 700; color: var(--primary); display: block; margin-bottom: 6px; }
        .card-stat .label { color: var(--text-muted); font-size: 14px; font-weight: 500; }

        .middle-row { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; height: 100%; }
        .section-card h3 { font-size: 18px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
                .side-content { display: flex; flex-direction: column; gap: 20px; }
        .side-card    { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .side-card h3 { font-size: 15px; font-weight: 700; margin-bottom: 16px; }
        .act-item { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .act-item:last-child { border-bottom: none; }
        .act-dot { color: var(--primary); margin-right: 10px; font-size: 8px; }
        .act-time { color: var(--text-muted); font-size: 13px; }
        
        .ranking-list { display: flex; flex-direction: column; gap: 10px; }
        .ranking-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: #f8fafc; border-radius: 10px; }
        .rank-number  { width: 26px; height: 26px; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .rank-number.gold   { background: #fbbf24; }
        .rank-number.silver { background: #94a3b8; }
        .rank-number.bronze { background: #b45309; }
        .rank-info h4 { font-size: 13px; font-weight: 600; }
        .rank-info p  { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .mentor-item { display: flex; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid #f1f5f9; }
        .mentor-item:last-child { border-bottom: none; }
        .rank { width: 24px; height: 24px; background: #1e293b; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
        .m-avatar { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; }
        .m-info h4 { font-size: 14px; font-weight: 600; color: #1e293b; }
        .m-info p { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .badges-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .badge-box { display: flex; align-items: center; gap: 16px; padding: 20px; border-radius: 16px; border: 1px solid var(--border); background: #ffffff; position: relative; }
        .badge-box.locked { background: #fafafa; opacity: 0.7; }
        .b-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .b-icon.blue { background: #eff6ff; color: #2563eb; }
        .b-icon.indigo { background: #e0e7ff; color: #4f46e5; }
        .b-icon.gray { background: #f1f5f9; color: #64748b; }
        .b-details h4 { font-size: 15px; font-weight: 700; margin-bottom: 6px; }
        .b-details p { font-size: 13px; color: var(--text-muted); }
        .lock-icon { position: absolute; right: 16px; top: 16px; color: #94a3b8; font-size: 14px; }
        
        .btn-action-swap { display: inline-flex; align-items: center; gap: 8px; background: var(--primary); color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; margin-top: 10px; border: none; cursor: pointer; align-self: flex-start; transition: background 0.3s; }
        .btn-action-swap:hover { background: #4f46e5; }

        @media (max-width: 1024px) { .sidebar { width: 80px; padding: 20px 10px; } .sidebar h1, .sidebar span, .menu-title { display: none; } .menu-item { justify-content: center; padding: 12px; } .menu-item i { margin-right: 0; } .middle-row { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { body { flex-direction: column; } .sidebar { width: 100%; height: auto; flex-direction: row; overflow-x: auto; padding: 10px; } .sidebar h1 { display: none; } .sidebar nav { display: flex; flex-direction: row; } .main { padding: 20px; } .stats-grid { grid-template-columns: 1fr; } }
    </style>
</head>

<body>

     <aside class="sidebar">
        <h1>ISMO SkillSwap</h1>
        <p class="menu-title">GENERAL</p>
        <nav>
            <a href="dashboard.php" class="menu-item active "><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            <a href="profil.php" class="menu-item"><i class="far fa-user"></i> <span>Profil</span></a>
            <p class="menu-title">ACTIVITÉ</p>
            <a href="demandes_aide.php" class="menu-item" id="nav-demandes"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
            <a href="creer_demande.php" class="menu-item" id="nav-creer"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
            <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>
        <p class="menu-title">COMPÉTENCES</p>
        <a href="competence.php" class="menu-item"><i class="fas fa-award"></i> <span>Compétences</span></a>
        <a href="passport.php"   class="menu-item"><i class="fas fa-id-card"></i> <span>Passeport</span></a>
        <a href="Mentors.php"   class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
            <p class="menu-title" >COMPTE</p>
            <a href="logout.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </nav>
    </aside>
    <main class="main">
        <header>
            <div class="search-box">
                <i class="fas fa-search" style="color: #94a3b8;"></i>
                <input type="text" placeholder="Rechercher des compétences, mentors...">
            </div>
            <div class="avatar-top">
                <a href="profil.php" style="color:white; text-decoration:none;"><?php echo $initials; ?></a>
            </div>
        </header>

        <div>
            <h2 class="page-title">Dashboard</h2>
            <p class="welcome-sub">Bienvenue, <?php echo $nom_complet; ?> !</p>
        </div>

        <div class="level-card">
            <div class="level-info" style="flex: 1; margin-right: 20px;">
                <h3>Niveau <?php echo $niveau; ?> <span class="xp-badge"><?php echo $xp; ?> XP</span></h3>
                <div class="stars">
                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>

                    <i class="<?php echo ($nbcomp >= 3) ? 'fas fa-star' : 'fas fa-star-half-alt'; ?>"></i>
                    <span style="color: var(--text-muted); font-size: 13px; margin-left: 6px; font-weight: 500;">
                        <?php echo number_format(min(4.0 + ($nbcomp * 0.2), 5.0), 1); ?> (<?php echo $nbdemandes+ 3; ?> avis)
                    </span>
                </div>
                <p class="progress-text">Progression vers niveau <?php echo ($niveau + 1); ?> <span style="float: right; font-weight: 600;"><?php echo $xp_dans_le_niveau; ?> / 2000 XP</span></p>
                <div class="bar-bg">
                    <div class="bar-fill" style="width: <?php echo $progress_percentage; ?>%;"></div>
                </div>
            </div>
            <div class="level-avatar"><?php echo $initials; ?></div>
        </div>

        <div class="stats-grid">
            <div class="card-stat">
                <span class="val"><?php echo $nbdemandes; ?></span>
                <span class="label">Demandes créées</span>
            </div>
            <div class="card-stat">
                <span class="val"><?php echo $nbaides; ?></span>
                <span class="label">Aides réalisées</span>
            </div>
            <div class="card-stat">
                <span class="val"><?php echo $nbcomp; ?></span>
                <span class="label">Compétences validées</span>
            </div>
        </div>

        <div class="middle-row">
            <div class="section-card">
                <h3>Activité récente</h3>
                <?php if (count($activities) > 0): ?>
                    <?php foreach ($activities as $act): ?>
                        <div class="act-item">
                            <span><span class="act-dot">•</span>Vous avez créé la demande "<?php echo htmlspecialchars($act['titre']); ?>" en <?php echo htmlspecialchars($act['technologie']); ?></span>
                            <span class="act-time">Récemment</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="act-item">
                        <span><span class="act-dot">•</span>Aucune activité récente. Créez votre première demande d'aide !</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="side-card">
                <h3><i class="fas fa-trophy" style="color:#fbbf24; margin-right:8px;"></i>Top Mentors XP</h3>
                <div class="ranking-list">
                    <?php
                    $top = array_slice($all_mentors, 0, 3);
                    $rankClasses = ['gold', 'silver', 'bronze'];
                    foreach ($top as $i => $tm):
                    ?>
                    <div class="ranking-item">
                        <div class="rank-number <?php echo $rankClasses[$i]; ?>"><?php echo $i + 1; ?></div>
                        <div class="rank-info">
                            <h4><?php echo htmlspecialchars($tm['prenom_uti'] . ' ' . $tm['nom_uti']); ?></h4>
                            <p><?php echo $tm['score_global'] ?? 0; ?> XP</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($top)): ?>
                        <p style="font-size:13px; color:var(--text-muted); text-align:center;">Aucun mentor pour le moment.</p>
                    <?php endif; ?>
                </div>
            </div>

        <div class="section-card">
    <h3><i class="fas fa-medal" style="color: #fbbf24;"></i> Mes Badges</h3>
    <div class="badges-grid" style="margin-top: 20px;">
        
        <?php if (empty($mes_badges)): ?>
            <p style="color: var(--text-muted); font-size: 14px;">Aucun badge pour le moment. Continuez vos efforts !</p>
        <?php else: ?>
            <?php foreach ($mes_badges as $badge): ?>
                <div class="badge-box">
                    <div class="b-icon indigo"><i class="<?php echo htmlspecialchars($badge['image_badge']); ?>"></i></div>
                    <div class="b-details">
                        <h4><?php echo htmlspecialchars($badge['nom_badge']); ?></h4>
                        <p><?php echo htmlspecialchars($badge['description_badge']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <a href="creer_demande.php" class="btn-action-swap" style="margin-top: 20px;">
        <i class="far fa-plus-square"></i> Créer une demande d'aide
    </a>
</div>

</body>
</html>