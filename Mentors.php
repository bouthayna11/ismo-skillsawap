<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$themeClass = 'theme-stagiaire';
if (in_array($userRole, ['admin', 'administrateur'])) $themeClass = 'theme-admin';
elseif ($userRole === 'formateur')                    $themeClass = 'theme-formateur';

$statCompPend    = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend = $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();

$queryUser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$queryUser->execute([$userId]);
$currentUser = $queryUser->fetch();
$initials    = $currentUser ? strtoupper(substr($currentUser['prenom_uti'], 0, 1) . substr($currentUser['nom_uti'], 0, 1)) : "ST";

// statut mentor du user courant
$queryStatus = $pdo->prepare("SELECT statut FROM demande_mentorat WHERE id_stagiaire = ? AND (message_demande IS NULL OR message_demande = '') ORDER BY id_demande_m DESC LIMIT 1");
$queryStatus->execute([$userId]);
$dm = $queryStatus->fetch();
$statut_mentor = $dm ? $dm['statut'] : null;

$msg_postuler = "";

// Postuler mentor
if (isset($_POST['postuler_mentor'])) {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM demande_mentorat WHERE id_stagiaire = ? AND (message_demande IS NULL OR message_demande = '') AND statut = 'en_attente'");
    $chk->execute([$userId]);
    if ($chk->fetchColumn() > 0) {
        $msg_postuler = "Vous avez déjà une demande en attente.";
    } else {
        $pdo->prepare("INSERT INTO demande_mentorat (id_stagiaire, statut, message_demande) VALUES (?, 'en_attente', '')")->execute([$userId]);
        $msg_postuler  = "Demande envoyée avec succès !";
        $statut_mentor = 'en_attente';
    }
}

// Envoyer message contact
if (isset($_POST['send_message_help'])) {
    $msg_text = trim($_POST['message_text'] ?? '');
    if (!empty($msg_text)) {
        $pdo->prepare("INSERT INTO demande_mentorat (id_stagiaire, statut, message_demande) VALUES (?, 'en_attente', ?)")->execute([$userId, $msg_text]);
        $msg_postuler = "Votre message a été envoyé au Mentor !";
    }
}

// Mentors
$all_mentors = $pdo->query(
    "SELECT u.id_utilisateur, u.nom_uti, u.prenom_uti, s.score_global, s.filiere
     FROM utilisateur u
     INNER JOIN stagiaire s ON u.id_utilisateur = s.id_utilisateur
     INNER JOIN demande_mentorat d ON s.id_utilisateur = d.id_stagiaire
     WHERE d.statut = 'valide'
     GROUP BY u.id_utilisateur
     ORDER BY s.score_global DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$mentor_skills = [];
foreach ($all_mentors as $m) {
    $st = $pdo->prepare("SELECT c.nom_comp FROM maitriser ma JOIN competence c ON ma.id_comp = c.id_comp WHERE ma.id_utilisateur = ? LIMIT 4");
    $st->execute([$m['id_utilisateur']]);
    $mentor_skills[$m['id_utilisateur']] = $st->fetchAll(PDO::FETCH_COLUMN);
}

// Notes moyennes par mentor
$notesStmt = $pdo->query("SELECT id_receveur, ROUND(AVG(score),1) AS moy, COUNT(*) AS nb FROM notation GROUP BY id_receveur");
$notesMap  = [];
foreach ($notesStmt->fetchAll(PDO::FETCH_ASSOC) as $n) {
    $notesMap[$n['id_receveur']] = $n;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Mentors</title>
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

        .main  { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .page-title { font-size: 24px; font-weight: 700; }
        .page-sub   { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        .alert-info { background: #e0f2fe; color: #0369a1; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; }

        .filters { display: flex; gap: 16px; flex-wrap: wrap; }
        .filter-group label { font-size: 13px; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 6px; }
        .filter-group select { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); width: 200px; font-size: 13px; outline: none; background: white; font-family: inherit; }

        .content-layout { display: grid; grid-template-columns: 1fr 300px; gap: 24px; align-items: start; }

        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 18px; }
        .card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 22px; display: flex; flex-direction: column; gap: 12px; transition: 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.07); border-color: #c7d2fe; }

        .card-top { display: flex; gap: 14px; align-items: center; }
        .avatar   { width: 50px; height: 50px; border-radius: 50%; background: var(--primary); color: white; display: flex; justify-content: center; align-items: center; font-size: 18px; font-weight: 700; flex-shrink: 0; }
        .name-row { display: flex; justify-content: space-between; align-items: center; gap: 6px; }
        .name-row h2 { font-size: 15px; font-weight: 700; }
        .level-badge { background: #eef2ff; color: #4f46e5; padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 600; }
        .xp-text { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .stars-row { display: flex; align-items: center; gap: 5px; font-size: 12px; }
        .stars     { color: #fbbf24; font-size: 12px; display: flex; gap: 1px; }
        .no-note   { font-size: 11px; color: #94a3b8; font-style: italic; }

        .tags    { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag     { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; background: #eef2ff; color: #4f46e5; }
        .tag-empty{ font-size: 11px; color: #94a3b8; font-style: italic; }

        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 10px; border-top: 1px solid var(--border); }
        .status-dot  { width: 7px; height: 7px; border-radius: 50%; background: var(--green); display: inline-block; margin-right: 5px; }
        .contact-btn { padding: 7px 14px; border: none; border-radius: 8px; background: var(--primary); color: white; cursor: pointer; font-size: 12px; font-weight: 600; }
        .contact-btn:hover { background: var(--primary-hover); }
        .noter-btn   { padding: 7px 12px; border: 1px solid #fde047; border-radius: 8px; background: #fefce8; color: #854d0e; cursor: pointer; font-size: 12px; font-weight: 600; text-decoration: none; }
        .noter-btn:hover { background: #fde047; }

        /* Side */
        .side-content { display: flex; flex-direction: column; gap: 20px; }
        .side-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 22px; }
        .side-card h3 { font-size: 14px; font-weight: 700; margin-bottom: 14px; }

        .ranking-item { display: flex; align-items: center; gap: 10px; padding: 8px 10px; background: #f8fafc; border-radius: 10px; margin-bottom: 8px; }
        .ranking-item:last-child { margin-bottom: 0; }
        .rank-num { width: 24px; height: 24px; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 11px; font-weight: 700; flex-shrink: 0; }
        .gold { background: #fbbf24; } .silver { background: #94a3b8; } .bronze { background: #b45309; }
        .rank-info h4 { font-size: 13px; font-weight: 600; }
        .rank-info p  { font-size: 11px; color: var(--text-muted); }

        .become-mentor p { font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 12px; }
        .apply-btn { width: 100%; padding: 10px; border: none; border-radius: 10px; background: var(--primary); color: white; font-size: 13px; font-weight: 600; cursor: pointer; }
        .apply-btn:hover { background: var(--primary-hover); }
        .status-box { padding: 10px 14px; border-radius: 10px; font-size: 12px; font-weight: 500; display: flex; align-items: center; gap: 8px; }
        .status-pending  { background: #fefce8; color: #854d0e; border: 1px solid #fde047; }
        .status-rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .status-approved { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.45); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 28px; border-radius: 16px; width: 100%; max-width: 420px; display: flex; flex-direction: column; gap: 14px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 16px; }
        .modal-close  { cursor: pointer; color: var(--text-muted); font-size: 20px; }
        .modal textarea { width: 100%; height: 90px; padding: 12px; border-radius: 10px; border: 1px solid var(--border); font-family: inherit; font-size: 13px; resize: none; outline: none; }
        .modal textarea:focus { border-color: var(--primary); }
        .btn-modal { width: 100%; padding: 11px; border: none; border-radius: 10px; background: var(--primary); color: white; font-size: 14px; font-weight: 600; cursor: pointer; }

        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
        .empty-state i { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; display: block; }

        @media (max-width: 1100px) { .content-layout { grid-template-columns: 1fr; } }
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
        <a href="profil.php" class="menu-item"><i class="far fa-user"></i> <span>Profil</span></a>

        <?php if (in_array($userRole, ['stagiaire', 'formateur'])): ?>
        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i>
            <span><?php echo $userRole === 'formateur' ? "Offres d'aide" : "Demandes d'aide"; ?></span>
        </a>
        <?php if ($userRole === 'stagiaire'): ?>
        <a href="creer_demande.php" class="menu-item"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
        <?php endif; ?>
        <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>

        <p class="menu-title">COMPÉTENCES</p>
        <a href="competence.php" class="menu-item"><i class="fas fa-award"></i>           <span>Compétences</span></a>
        <a href="mentors.php"    class="menu-item active"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
        <a href="passport.php"   class="menu-item"><i class="fas fa-id-card"></i>         <span>Passeport</span></a>
        <?php endif; ?>

        <?php if (in_array($userRole, ['formateur', 'admin', 'administrateur'])): ?>
        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item"><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
        <a href="validation_mentors.php" class="menu-item"><i class="fas fa-user-shield"></i> <span>Valider Mentors</span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a>
        <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i> <span>Gestion Badges</span></a>
        <?php endif; ?>

        <?php if (in_array($userRole, ['admin', 'administrateur'])): ?>
        <a href="gestion_utilisateurs.php" class="menu-item"><i class="fas fa-users-cog"></i> <span>Gérer Utilisateurs</span></a>
        <a href="mentors.php" class="menu-item active"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
        <a href="satisfaction.php" class="menu-item"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
        <?php endif; ?>
    </nav>
    <div style="margin-top:auto;">
        <a href="logout.php" class="menu-item" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </div>
</aside>

<main class="main">
    <header>
        <div class="search-box">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" id="searchInput" placeholder="Rechercher un mentor...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <?php if (!empty($msg_postuler)): ?>
        <div class="alert-info"><i class="fas fa-info-circle" style="margin-right:8px;"></i><?php echo htmlspecialchars($msg_postuler); ?></div>
    <?php endif; ?>

    <div>
        <h1 class="page-title">Mentors</h1>
        <p class="page-sub">Trouvez l'expertise dont vous avez besoin au sein de l'ISMO</p>
    </div>

    <div class="filters">
        <div class="filter-group">
            <label>Filtrer par compétence</label>
            <select id="skillFilter">
                <option value="">Toutes les compétences</option>
            </select>
        </div>
    </div>

    <div class="content-layout">
        <!-- Cards -->
        <div class="cards-grid" id="mentorsContainer">
            <?php if (empty($all_mentors)): ?>
                <div class="empty-state"><i class="fas fa-user-graduate"></i><p>Aucun mentor disponible pour le moment.</p></div>
            <?php else: ?>
                <?php foreach ($all_mentors as $m):
                    $mInit  = strtoupper(substr($m['prenom_uti'], 0, 1) . substr($m['nom_uti'], 0, 1));
                    $level  = max(1, floor(($m['score_global'] ?? 0) / 300));
                    $skills = $mentor_skills[$m['id_utilisateur']] ?? [];
                    $note   = $notesMap[$m['id_utilisateur']] ?? null;
                ?>
                <div class="card mentor-card"
                     data-name="<?php echo strtolower($m['prenom_uti'] . ' ' . $m['nom_uti']); ?>"
                     data-skills="<?php echo strtolower(implode(',', $skills)); ?>">
                    <div class="card-top">
                        <div class="avatar"><?php echo $mInit; ?></div>
                        <div style="flex:1;">
                            <div class="name-row">
                                <h2><?php echo htmlspecialchars($m['prenom_uti'] . ' ' . $m['nom_uti']); ?></h2>
                                <span class="level-badge">Nv.<?php echo $level; ?></span>
                            </div>
                            <p class="xp-text"><i class="fas fa-star" style="color:#fbbf24; margin-right:3px;"></i><?php echo $m['score_global'] ?? 0; ?> XP</p>
                        </div>
                    </div>

                    <!-- Note -->
                    <div class="stars-row">
                        <?php if ($note): ?>
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++) echo $i <= round($note['moy']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                            </div>
                            <span style="font-size:12px; font-weight:700;"><?php echo $note['moy']; ?></span>
                            <span style="font-size:11px; color:var(--text-muted);">(<?php echo $note['nb']; ?> avis)</span>
                        <?php else: ?>
                            <span class="no-note">Pas encore noté</span>
                        <?php endif; ?>
                    </div>

                    <div class="tags">
                        <?php if (!empty($skills)): ?>
                            <?php foreach ($skills as $sk): ?><span class="tag"><?php echo htmlspecialchars($sk); ?></span><?php endforeach; ?>
                        <?php else: ?>
                            <span class="tag-empty">Aucune compétence renseignée</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <span style="font-size:12px; color:var(--text-muted);"><span class="status-dot"></span>Disponible</span>
                        <div style="display:flex; gap:6px;">
                            <?php if ($m['id_utilisateur'] != $userId): ?>
                                <button class="contact-btn"
                                    onclick="openModal(<?php echo $m['id_utilisateur']; ?>, '<?php echo addslashes($m['prenom_uti'] . ' ' . $m['nom_uti']); ?>')">
                                    <i class="fas fa-paper-plane"></i> Contacter
                                </button>
                                <?php if ($userRole === 'stagiaire'): ?>
                                <a href="notation.php?id_mentor=<?php echo $m['id_utilisateur']; ?>" class="noter-btn">
                                    <i class="fas fa-star"></i> Noter
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size:12px; color:var(--text-muted); font-style:italic;">C'est vous</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Side -->
        <div class="side-content">
            <div class="side-card">
                <h3><i class="fas fa-trophy" style="color:#fbbf24; margin-right:6px;"></i>Top Mentors XP</h3>
                <?php $top = array_slice($all_mentors, 0, 3); $rc = ['gold','silver','bronze']; ?>
                <?php foreach ($top as $i => $tm): ?>
                <div class="ranking-item">
                    <div class="rank-num <?php echo $rc[$i]; ?>"><?php echo $i+1; ?></div>
                    <div class="rank-info">
                        <h4><?php echo htmlspecialchars($tm['prenom_uti'] . ' ' . $tm['nom_uti']); ?></h4>
                        <p><?php echo $tm['score_global'] ?? 0; ?> XP</p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($top)): ?>
                    <p style="font-size:12px; color:var(--text-muted); text-align:center;">Aucun mentor.</p>
                <?php endif; ?>
            </div>

            <?php if ($userRole === 'stagiaire'): ?>
            <div class="side-card become-mentor">
                <h3><i class="fas fa-user-graduate" style="color:var(--primary); margin-right:6px;"></i>Devenez Mentor</h3>
                <?php if ($statut_mentor === 'en_attente'): ?>
                    <div class="status-box status-pending"><i class="fas fa-hourglass-half"></i><span>Demande en cours d'examen.</span></div>
                <?php elseif ($statut_mentor === 'refuse'): ?>
                    <div class="status-box status-rejected" style="margin-bottom:10px;"><i class="fas fa-times-circle"></i><span>Demande refusée.</span></div>
                    <p>Vous pouvez re-postuler :</p>
                    <form method="POST"><button type="submit" name="postuler_mentor" class="apply-btn">Postuler à nouveau</button></form>
                <?php elseif ($statut_mentor === 'valide'): ?>
                    <div class="status-box status-approved"><i class="fas fa-check-circle"></i><span>Vous êtes Mentor validé 🎉</span></div>
                <?php else: ?>
                    <p>Partagez vos compétences et gagnez des XP en aidant vos collègues.</p>
                    <form method="POST"><button type="submit" name="postuler_mentor" class="apply-btn"><i class="fas fa-paper-plane" style="margin-right:5px;"></i>Postuler</button></form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Modal Contact -->
<div class="modal" id="contactModal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Contacter <span id="modalMentorName" style="color:var(--primary);"></span></span>
            <span class="modal-close" onclick="document.getElementById('contactModal').style.display='none'">&times;</span>
        </div>
        <p style="font-size:13px; color:var(--text-muted);">Expliquez votre besoin d'aide :</p>
        <form method="POST" action="mentors.php">
            <input type="hidden" name="id_mentor" id="modalMentorId">
            <textarea name="message_text" placeholder="Votre message..." required></textarea>
            <button type="submit" name="send_message_help" class="btn-modal" style="margin-top:8px;">
                <i class="fas fa-paper-plane" style="margin-right:5px;"></i>Envoyer
            </button>
        </form>
    </div>
</div>

<script>
function openModal(id, name) {
    document.getElementById('modalMentorId').value = id;
    document.getElementById('modalMentorName').innerText = name;
    document.getElementById('contactModal').style.display = 'flex';
}

const searchInput = document.getElementById("searchInput");
const skillFilter = document.getElementById("skillFilter");
const cards       = document.querySelectorAll(".mentor-card");

const allSkills = new Set();
cards.forEach(c => c.getAttribute("data-skills").split(',').filter(Boolean).forEach(s => allSkills.add(s.trim())));
allSkills.forEach(s => {
    const opt = document.createElement('option');
    opt.value = s; opt.textContent = s.charAt(0).toUpperCase() + s.slice(1);
    skillFilter.appendChild(opt);
});

function filter() {
    const q = searchInput.value.toLowerCase();
    const s = skillFilter.value.toLowerCase();
    cards.forEach(c => {
        const match = (!q || c.getAttribute('data-name').includes(q)) &&
                      (!s || c.getAttribute('data-skills').includes(s));
        c.style.display = match ? '' : 'none';
    });
}
searchInput.addEventListener('keyup', filter);
skillFilter.addEventListener('change', filter);
</script>
</body>
</html>