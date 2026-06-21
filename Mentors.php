<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// جلب معلومات المستخدم
$queryUser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = :id");
$queryUser->execute(['id' => $userId]);
$currentUser = $queryUser->fetch();
$initials = $currentUser ? strtoupper(substr($currentUser['prenom_uti'], 0, 1) . substr($currentUser['nom_uti'], 0, 1)) : "ST";

$dashboard_link = "dashboard.php";
if ($userRole === 'formateur')                                   { $dashboard_link = "dashboard_formateur.php"; }
elseif ($userRole === 'admin' || $userRole === 'administrateur') { $dashboard_link = "dashboard_admin.php"; }

// =====================================================================
// جلب حالة آخر طلب انضمام كـ Mentor لهاد الستاجير
// =====================================================================
$queryStatus = $pdo->prepare(
    "SELECT statut FROM demande_mentorat 
     WHERE id_stagiaire = ? AND (message_demande IS NULL OR message_demande = '') 
     ORDER BY id_demande_m DESC LIMIT 1"
);
$queryStatus->execute([$userId]);
$demande_mentor = $queryStatus->fetch();
$statut_mentor  = $demande_mentor ? $demande_mentor['statut'] : null;

// =====================================================================
// معالجة طلب الانضمام كـ Mentor
// =====================================================================
$msg_postuler = "";
if (isset($_POST['postuler_mentor'])) {
    $checkPostul = $pdo->prepare(
        "SELECT statut FROM demande_mentorat 
         WHERE id_stagiaire = :id AND (message_demande IS NULL OR message_demande = '') AND statut = 'en_attente'"
    );
    $checkPostul->execute(['id' => $userId]);
    $existPostul = $checkPostul->fetchColumn();

    if ($existPostul) {
        $msg_postuler = "Vous avez déjà une demande en attente de validation.";
    } else {
        $insPostul = $pdo->prepare(
            "INSERT INTO demande_mentorat (id_stagiaire, statut, message_demande) VALUES (:id, 'en_attente', '')"
        );
        $insPostul->execute(['id' => $userId]);
        $msg_postuler  = "Votre demande pour devenir Mentor a été envoyée avec succès !";
        $statut_mentor = 'en_attente';
    }
}

// =====================================================================
// معالجة إرسال رسالة التواصل (Contacter)
// =====================================================================
if (isset($_POST['send_message_help'])) {
    $msg_text = trim($_POST['message_text']);
    if (!empty($msg_text)) {
        $insMessage = $pdo->prepare(
            "INSERT INTO demande_mentorat (id_stagiaire, statut, message_demande) VALUES (:id_stagiaire, 'en_attente', :msg)"
        );
        $insMessage->execute(['id_stagiaire' => $userId, 'msg' => $msg_text]);
        $msg_postuler = "Votre demande de mentorat a été envoyée avec succès au Mentor !";
    }
}

// =====================================================================
// جلب المينتورز المقبولين (statut = 'valide') + كومبيتانسهم
// =====================================================================
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

// جلب الكومبيتانس ديال كل منتور من maitriser + competence
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
        :root {
            --sidebar-bg:    #1e293b;
            --primary:       #6366f1;
            --primary-hover: #4f46e5;
            --main-bg:       #f8fafc;
            --border:        #e2e8f0;
            --text-main:     #1e293b;
            --text-muted:    #64748b;
            --green:         #22c55e;
            --red:           #ef4444;
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

        /* Main */
        .main  { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .search-box-top { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box-top input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .page-header h1 { font-size: 24px; font-weight: 700; }
        .page-header p  { color: var(--text-muted); font-size: 14px; margin-top: 4px; }

        .alert-info { background: #e0f2fe; color: #0369a1; padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; }

        /* Filters */
        .filters { display: flex; gap: 20px; flex-wrap: wrap; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 13px; color: var(--text-muted); font-weight: 600; }
        .filter-group select { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); width: 200px; font-size: 14px; outline: none; background: white; font-family: inherit; }

        /* Layout */
        .content-layout { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }

        /* Cards */
        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 22px; display: flex; flex-direction: column; gap: 14px; transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }

        .card-top  { display: flex; gap: 14px; align-items: center; }
        .avatar    { width: 52px; height: 52px; border-radius: 50%; background: var(--primary); color: white; display: flex; justify-content: center; align-items: center; font-size: 18px; font-weight: 700; flex-shrink: 0; }
        .info      { flex: 1; }
        .name-row  { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .name-row h2 { font-size: 15px; font-weight: 700; }
        .level-badge { background: #eef2ff; color: #4f46e5; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; white-space: nowrap; }
        .xp-text   { font-size: 12px; color: var(--text-muted); margin-top: 3px; }

        .tags { display: flex; gap: 6px; flex-wrap: wrap; }
        .tag  { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; background: #eef2ff; color: #4f46e5; }
        .tag-empty { font-size: 12px; color: #94a3b8; font-style: italic; }

        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--border); }
        .status      { display: flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 13px; }
        .status-dot  { width: 8px; height: 8px; border-radius: 50%; background: var(--green); }
        .contact-btn { padding: 8px 16px; border: none; border-radius: 8px; background: var(--primary); color: white; cursor: pointer; font-size: 13px; font-weight: 600; }
        .contact-btn:hover { background: var(--primary-hover); }

        /* Side */
        .side-content { display: flex; flex-direction: column; gap: 20px; }
        .side-card    { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .side-card h3 { font-size: 15px; font-weight: 700; margin-bottom: 16px; }

        .ranking-list { display: flex; flex-direction: column; gap: 10px; }
        .ranking-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; background: #f8fafc; border-radius: 10px; }
        .rank-number  { width: 26px; height: 26px; border-radius: 50%; display: flex; justify-content: center; align-items: center; color: white; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .rank-number.gold   { background: #fbbf24; }
        .rank-number.silver { background: #94a3b8; }
        .rank-number.bronze { background: #b45309; }
        .rank-info h4 { font-size: 13px; font-weight: 600; }
        .rank-info p  { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        /* Become mentor */
        .become-mentor p { font-size: 13px; color: #475569; line-height: 1.6; margin-bottom: 14px; }
        .apply-btn { width: 100%; padding: 11px; border: none; border-radius: 10px; background: var(--primary); color: white; font-size: 14px; font-weight: 600; cursor: pointer; }
        .apply-btn:hover { background: var(--primary-hover); }

        .status-box { padding: 12px; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; line-height: 1.5; }
        .status-pending  { background: #fefce8; color: #854d0e; border: 1px solid #fde047; }
        .status-rejected { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .status-approved { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }

        /* Empty */
        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
        .empty-state i { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; display: block; }

        /* Modal */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); justify-content: center; align-items: center; z-index: 1000; }
        .modal-content { background: white; padding: 28px; border-radius: 16px; width: 100%; max-width: 440px; display: flex; flex-direction: column; gap: 16px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; font-weight: 700; font-size: 16px; }
        .modal-close  { cursor: pointer; color: var(--text-muted); font-size: 20px; }
        .modal textarea { width: 100%; height: 100px; padding: 12px; border-radius: 10px; border: 1px solid var(--border); font-family: inherit; font-size: 13px; resize: none; outline: none; }
        .modal textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .btn-submit-modal { width: 100%; padding: 11px; border: none; border-radius: 10px; background: var(--primary); color: white; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn-submit-modal:hover { background: var(--primary-hover); }

        /* Responsive */
        @media (max-width: 1100px) { .content-layout { grid-template-columns: 1fr; } }
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <p class="menu-title">GENERAL</p>
    <nav>
        <a href="<?php echo $dashboard_link; ?>" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
        <a href="profil.php"        class="menu-item"><i class="far fa-user"></i>           <span>Profil</span></a>

        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
        <a href="creer_demande.php" class="menu-item"><i class="far fa-plus-square"></i>    <span>Créer une demande</span></a>
        <a href="recherche.php"     class="menu-item"><i class="fas fa-search"></i>          <span>Recherche</span></a>

        <p class="menu-title">COMPÉTENCES</p>
        <a href="competence.php"    class="menu-item"><i class="fas fa-award"></i>           <span>Compétences</span></a>
        <a href="Mentors.php"       class="menu-item active"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
        <a href="passport.php"      class="menu-item"><i class="fas fa-id-card"></i>         <span>Passeport</span></a>

        <?php if ($userRole === 'admin' || $userRole === 'administrateur'): ?>
            <p class="menu-title">ADMIN</p>
            <a href="admin.php" class="menu-item"><i class="fas fa-user-shield"></i> <span>Admin</span></a>
        <?php endif; ?>
                    <p class="menu-title" >COMPTE</p>
            <a href="logout.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </nav>
</aside>

<main class="main">

    <header>
        <div class="search-box-top">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" id="searchInput" placeholder="Rechercher un mentor...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <?php if (!empty($msg_postuler)): ?>
        <div class="alert-info"><i class="fas fa-info-circle" style="margin-right:8px;"></i><?php echo htmlspecialchars($msg_postuler); ?></div>
    <?php endif; ?>

    <div class="page-header">
        <h1>Mentors</h1>
        <p>Trouvez l'expertise dont vous avez besoin au sein de l'ISMO</p>
    </div>

    <!-- Filters -->
    <div class="filters">
        <div class="filter-group">
            <label>Recherche par compétence</label>
            <select id="skillFilter">
                <option value="">Toutes les compétences</option>
            </select>
        </div>
    </div>

    <div class="content-layout">

        <!-- ══ Cards Mentors ══ -->
        <div class="cards-grid" id="mentorsContainer">
            <?php if (empty($all_mentors)): ?>
                <div class="empty-state">
                    <i class="fas fa-user-graduate"></i>
                    <p>Aucun mentor disponible pour le moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($all_mentors as $m):
                    $mInitials = strtoupper(substr($m['prenom_uti'], 0, 1) . substr($m['nom_uti'], 0, 1));
                    $level     = max(1, floor(($m['score_global'] ?? 0) / 300));
                    $skills    = $mentor_skills[$m['id_utilisateur']] ?? [];
                ?>
                <div class="card mentor-card"
                     data-name="<?php echo strtolower(htmlspecialchars($m['prenom_uti'] . ' ' . $m['nom_uti'])); ?>"
                     data-skills="<?php echo strtolower(implode(',', $skills)); ?>">

                    <div class="card-top">
                        <div class="avatar"><?php echo $mInitials; ?></div>
                        <div class="info">
                            <div class="name-row">
                                <h2><?php echo htmlspecialchars($m['prenom_uti'] . ' ' . $m['nom_uti']); ?></h2>
                                <span class="level-badge">Nv.<?php echo $level; ?></span>
                            </div>
                            <p class="xp-text"><i class="fas fa-star" style="color:#fbbf24; margin-right:4px;"></i><?php echo $m['score_global'] ?? 0; ?> XP</p>
                        </div>
                    </div>

                    <!-- Compétences réelles -->
                    <div class="tags">
                        <?php if (!empty($skills)): ?>
                            <?php foreach ($skills as $sk): ?>
                                <span class="tag"><?php echo htmlspecialchars($sk); ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="tag-empty">Aucune compétence renseignée</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <div class="status">
                            <span class="status-dot"></span>
                            <span>Disponible</span>
                        </div>
                        <?php if ($m['id_utilisateur'] != $userId): ?>
                            <button class="contact-btn"
                                onclick="openContactModal(<?php echo $m['id_utilisateur']; ?>, '<?php echo addslashes(htmlspecialchars($m['prenom_uti'] . ' ' . $m['nom_uti'])); ?>')">
                                <i class="fas fa-paper-plane" style="margin-right:5px;"></i>Contacter
                            </button>
                        <?php else: ?>
                            <span style="font-size:12px; color:var(--text-muted); font-style:italic;">C'est vous</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- ══ Side ══ -->
        <div class="side-content">

            <!-- Classement -->
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

            <!-- Devenir Mentor -->
            <?php if ($userRole === 'stagiaire'): ?>
            <div class="side-card become-mentor">
                <h3><i class="fas fa-user-graduate" style="color:var(--primary); margin-right:8px;"></i>Devenez Mentor</h3>

                <?php if ($statut_mentor === 'en_attente'): ?>
                    <div class="status-box status-pending">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Votre demande est en cours d'examen par les formateurs.</span>
                    </div>

                <?php elseif ($statut_mentor === 'refuse'): ?>
                    <div class="status-box status-rejected" style="margin-bottom:12px;">
                        <i class="fas fa-times-circle"></i>
                        <span>Votre demande précédente a été refusée.</span>
                    </div>
                    <p>Vous pouvez soumettre une nouvelle demande :</p>
                    <form method="POST" action="mentors.php">
                        <button type="submit" name="postuler_mentor" class="apply-btn">Postuler à nouveau</button>
                    </form>

                <?php elseif ($statut_mentor === 'valide'): ?>
                    <div class="status-box status-approved">
                        <i class="fas fa-check-circle"></i>
                        <span>Félicitations ! Vous êtes déjà un Mentor validé. 🎉</span>
                    </div>

                <?php else: ?>
                    <p>Partagez vos compétences avec vos collègues et gagnez des points XP en aidant les autres.</p>
                    <form method="POST" action="mentors.php">
                        <button type="submit" name="postuler_mentor" class="apply-btn">
                            <i class="fas fa-paper-plane" style="margin-right:6px;"></i>Postuler
                        </button>
                    </form>
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
            <span class="modal-close" onclick="closeContactModal()">&times;</span>
        </div>
        <p style="font-size:13px; color:var(--text-muted);">Expliquez votre besoin d'aide ou votre question :</p>
        <form method="POST" action="mentors.php">
            <input type="hidden" name="id_mentor" id="modalMentorId">
            <textarea name="message_text" placeholder="Écrivez votre message ici..." required></textarea>
            <button type="submit" name="send_message_help" class="btn-submit-modal">
                <i class="fas fa-paper-plane" style="margin-right:6px;"></i>Envoyer
            </button>
        </form>
    </div>
</div>

<script>
    function openContactModal(id, name) {
        document.getElementById('modalMentorId').value = id;
        document.getElementById('modalMentorName').innerText = name;
        document.getElementById('contactModal').style.display = 'flex';
    }
    function closeContactModal() {
        document.getElementById('contactModal').style.display = 'none';
    }

    // فلتر البحث
    const searchInput  = document.getElementById("searchInput");
    const skillFilter  = document.getElementById("skillFilter");
    const cards        = document.querySelectorAll(".mentor-card");

    // بناء قائمة الكومبيتانس من الكاردز
    const allSkills = new Set();
    cards.forEach(c => c.getAttribute("data-skills").split(',').filter(Boolean).forEach(s => allSkills.add(s.trim())));
    allSkills.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s; opt.textContent = s.charAt(0).toUpperCase() + s.slice(1);
        skillFilter.appendChild(opt);
    });

    function filterMentors() {
        const searchVal = searchInput.value.toLowerCase();
        const skillVal  = skillFilter.value.toLowerCase();
        cards.forEach(card => {
            const name   = card.getAttribute("data-name");
            const skills = card.getAttribute("data-skills");
            const matchName  = name.includes(searchVal);
            const matchSkill = !skillVal || skills.includes(skillVal);
            card.style.display = (matchName && matchSkill) ? "flex" : "none";
        });
    
                }
    searchInput.addEventListener("keyup", filterMentors);
    skillFilter.addEventListener("change", filterMentors);
</script>

</body>
</html>