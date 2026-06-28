<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'stagiaire') {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// جلب id_mentor من URL
$id_mentor = (int)($_GET['id_mentor'] ?? 0);
if (!$id_mentor) {
    header("Location: mentors.php");
    exit();
}

// جلب معلومات المنتور
$stmtM = $pdo->prepare(
    "SELECT u.id_utilisateur, u.prenom_uti, u.nom_uti, s.score_global, s.filiere
     FROM utilisateur u
     JOIN stagiaire s ON u.id_utilisateur = s.id_utilisateur
     WHERE u.id_utilisateur = ? AND u.role = 'stagiaire'"
);
$stmtM->execute([$id_mentor]);
$mentor = $stmtM->fetch();

if (!$mentor) {
    header("Location: mentors.php");
    exit();
}

// واش هو فعلاً منتور مقبول
$chkMentor = $pdo->prepare("SELECT COUNT(*) FROM demande_mentorat WHERE id_stagiaire = ? AND statut = 'valide'");
$chkMentor->execute([$id_mentor]);
if ($chkMentor->fetchColumn() == 0) {
    header("Location: mentors.php");
    exit();
}

// ما يقدرش ينوط روحو
if ($id_mentor == $userId) {
    header("Location: mentors.php");
    exit();
}

// جلب المستخدم الحالي
$stmtU = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$stmtU->execute([$userId]);
$currentUser = $stmtU->fetch();
$initials    = strtoupper(substr($currentUser['prenom_uti'], 0, 1) . substr($currentUser['nom_uti'], 0, 1));
$mentorInit  = strtoupper(substr($mentor['prenom_uti'], 0, 1) . substr($mentor['nom_uti'], 0, 1));
$mentorLevel = max(1, floor(($mentor['score_global'] ?? 0) / 300));

// واش سبق ونوط هاد المنتور
$chkDouble = $pdo->prepare("SELECT id_note FROM notation WHERE id_donneur = ? AND id_receveur = ?");
$chkDouble->execute([$userId, $id_mentor]);
$existingNote = $chkDouble->fetch();

// note moyenne المنتور
$stmtAvg = $pdo->prepare("SELECT ROUND(AVG(score),1) AS moy, COUNT(*) AS nb FROM notation WHERE id_receveur = ?");
$stmtAvg->execute([$id_mentor]);
$avgData = $stmtAvg->fetch();

$success_msg = '';
$error_msg   = '';

// =====================================================================
// معالجة الفورم
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score       = (int)($_POST['score'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');
    $id_demande  = !empty($_POST['id_demande']) ? (int)$_POST['id_demande'] : null;

    if ($score < 1 || $score > 5) {
        $error_msg = "Veuillez sélectionner une note entre 1 et 5 étoiles.";
    } else {
        if ($existingNote) {
            // تحديث النوط الموجودة
            $upd = $pdo->prepare("UPDATE notation SET score = ?, commentaires = ?, date_note = NOW() WHERE id_note = ?");
            $upd->execute([$score, $commentaire, $existingNote['id_note']]);
            $success_msg = "Note mise à jour avec succès !";
        } else {
            // إضافة نوط جديدة
            $ins = $pdo->prepare(
                "INSERT INTO notation (score, commentaires, date_note, id_donneur, id_receveur, id_demande)
                 VALUES (?, ?, NOW(), ?, ?, ?)"
            );
            $ins->execute([$score, $commentaire, $userId, $id_mentor, $id_demande]);
            $success_msg = "Note soumise avec succès ! Merci pour votre retour.";
        }
        // reload pour mettre à jour
        $chkDouble->execute([$userId, $id_mentor]);
        $existingNote = $chkDouble->fetch();
        $stmtAvg->execute([$id_mentor]);
        $avgData = $stmtAvg->fetch();
    }
}

// جلب النوط السابقة من هاد المنتور
$stmtNotes = $pdo->prepare(
    "SELECT n.score, n.commentaires, n.date_note, u.prenom_uti, u.nom_uti
     FROM notation n
     JOIN utilisateur u ON n.id_donneur = u.id_utilisateur
     WHERE n.id_receveur = ?
     ORDER BY n.date_note DESC"
);
$stmtNotes->execute([$id_mentor]);
$allNotes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

// جلب الديماندات اللي جاوب فيها المنتور (باش يربط النوط بديماند)
$stmtDemandes = $pdo->prepare(
    "SELECT d.id_demande, d.Titre AS titre
     FROM reponses_aide r
     JOIN demande_aide d ON r.id_demande = d.id_demande
     WHERE r.id_aidant = ? AND r.statut = 'accepte'
     ORDER BY d.id_demande DESC
     LIMIT 10"
);
$stmtDemandes->execute([$id_mentor]);
$demandes_mentor = $stmtDemandes->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Noter un Mentor</title>
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
            --yellow:        #f59e0b;
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
        .main  { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; max-width: 900px; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 360px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 10px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .page-title { font-size: 24px; font-weight: 700; }
        .back-link  { display: inline-flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 13px; text-decoration: none; margin-bottom: 4px; }
        .back-link:hover { color: var(--primary); }

        /* Alertes */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Mentor profile mini */
        .mentor-profile { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; display: flex; gap: 20px; align-items: center; }
        .m-avatar { width: 70px; height: 70px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; flex-shrink: 0; }
        .m-name   { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .m-meta   { font-size: 13px; color: var(--text-muted); display: flex; gap: 12px; flex-wrap: wrap; }
        .m-meta span { display: flex; align-items: center; gap: 4px; }
        .m-badge  { display: inline-flex; align-items: center; gap: 5px; background: #eef2ff; color: #4f46e5; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .mentor-badge { background: #f0fdf4; color: #15803d; font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 600; border: 1px solid #bbf7d0; }

        /* Moyenne */
        .avg-box { display: flex; align-items: center; gap: 10px; background: #fefce8; border: 1px solid #fde047; border-radius: 12px; padding: 10px 16px; }
        .avg-num { font-size: 26px; font-weight: 700; color: #854d0e; }
        .stars-display { color: #fbbf24; font-size: 16px; display: flex; gap: 2px; }
        .nb-avis  { font-size: 12px; color: var(--text-muted); }

        /* Formulaire notation */
        .notation-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 28px; }
        .notation-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }

        /* Star rating interactif */
        .star-rating { display: flex; gap: 8px; margin-bottom: 20px; }
        .star-rating input[type="radio"] { display: none; }
        .star-rating label {
            font-size: 36px;
            color: #e2e8f0;
            cursor: pointer;
            transition: color 0.15s, transform 0.15s;
        }
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label { color: #fbbf24; }
        /* trick: reverse order pour CSS */
        .star-rating { flex-direction: row-reverse; justify-content: flex-end; }
        .star-rating label:hover,
        .star-rating input:checked ~ label { color: #fbbf24; transform: scale(1.1); }
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label ~ label { color: #fbbf24; transform: scale(1.05); }

        .star-label { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; height: 18px; }
        .star-labels-map { display: none; }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 7px; }
        .form-group textarea,
        .form-group select { width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; font-family: inherit; outline: none; transition: 0.2s; }
        .form-group textarea { resize: vertical; min-height: 90px; line-height: 1.6; }
        .form-group textarea:focus,
        .form-group select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

        .btn-submit { background: var(--primary); color: white; border: none; padding: 12px 28px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; }
        .btn-submit:hover { background: var(--primary-hover); }

        /* Avis existants */
        .avis-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .avis-card h3 { font-size: 16px; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        .avis-item { background: #f8fafc; border-radius: 12px; border: 1px solid var(--border); padding: 14px 16px; margin-bottom: 10px; display: flex; flex-direction: column; gap: 8px; }
        .avis-item:last-child { margin-bottom: 0; }
        .avis-top  { display: flex; align-items: center; gap: 10px; }
        .avis-av   { width: 32px; height: 32px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; flex-shrink: 0; }
        .avis-name { font-size: 13px; font-weight: 600; }
        .avis-date { margin-left: auto; font-size: 11px; color: #94a3b8; }
        .avis-stars{ color: #fbbf24; font-size: 12px; display: flex; gap: 1px; }
        .avis-comment { font-size: 13px; color: #475569; line-height: 1.5; padding: 8px 10px; background: white; border-radius: 8px; border-left: 3px solid #c7d2fe; font-style: italic; }

        .empty-notes { text-align: center; padding: 30px; color: var(--text-muted); }
        .empty-notes i { font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px; }

        /* Mon avis existant */
        .my-note-box { background: #fefce8; border: 2px solid #fde047; border-radius: 12px; padding: 14px 16px; margin-bottom: 16px; }
        .my-note-box p { font-size: 13px; font-weight: 600; color: #854d0e; margin-bottom: 6px; }

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
            .main { padding: 20px; max-width: 100%; }
            .mentor-profile { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <p class="menu-title">GENERAL</p>
    <nav>
        <a href="dashboard.php"     class="menu-item"><i class="fas fa-th-large"></i>        <span>Dashboard</span></a>
        <a href="profil.php"        class="menu-item"><i class="far fa-user"></i>            <span>Profil</span></a>

        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
        <a href="creer_demande.php" class="menu-item"><i class="far fa-plus-square"></i>     <span>Créer une demande</span></a>
        <a href="recherche.php"     class="menu-item"><i class="fas fa-search"></i>           <span>Recherche</span></a>

        <p class="menu-title">COMPÉTENCES</p>
        <a href="competence.php"    class="menu-item"><i class="fas fa-award"></i>            <span>Compétences</span></a>
        <a href="mentors.php"       class="menu-item active"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
        <a href="passport.php"      class="menu-item"><i class="fas fa-id-card"></i>          <span>Passeport</span></a>
    </nav>
    <div style="margin-top:auto;">
        <a href="logout.php" class="menu-item" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </div>
</aside>

<main class="main">
    <header>
        <div class="search-box">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Rechercher...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <div>
        <a href="mentors.php" class="back-link"><i class="fas fa-arrow-left"></i> Retour aux Mentors</a>
        <h1 class="page-title">Noter un Mentor</h1>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- Profil mentor -->
    <div class="mentor-profile">
        <div class="m-avatar"><?php echo $mentorInit; ?></div>
        <div style="flex:1;">
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:6px;">
                <div class="m-name"><?php echo htmlspecialchars($mentor['prenom_uti'] . ' ' . $mentor['nom_uti']); ?></div>
                <span class="m-badge">Nv.<?php echo $mentorLevel; ?></span>
                <span class="mentor-badge"><i class="fas fa-chalkboard-teacher"></i> Mentor validé</span>
            </div>
            <div class="m-meta">
                <span><i class="fas fa-star" style="color:#fbbf24;"></i><?php echo $mentor['score_global'] ?? 0; ?> XP</span>
                <span><i class="fas fa-graduation-cap"></i><?php echo htmlspecialchars($mentor['filiere'] ?? ''); ?></span>
            </div>
        </div>
        <!-- Moyenne -->
        <?php if ($avgData && $avgData['nb'] > 0): ?>
        <div class="avg-box">
            <div>
                <div class="avg-num"><?php echo $avgData['moy']; ?><span style="font-size:14px; color:var(--text-muted);">/5</span></div>
                <div class="nb-avis"><?php echo $avgData['nb']; ?> avis</div>
            </div>
            <div>
                <div class="stars-display">
                    <?php for ($i=1; $i<=5; $i++) echo $i <= round($avgData['moy']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div style="font-size:12px; color:var(--text-muted); font-style:italic; padding: 10px;">Pas encore noté</div>
        <?php endif; ?>
    </div>

    <!-- Formulaire -->
    <div class="notation-card">
        <h3>
            <i class="fas fa-star" style="color:var(--yellow);"></i>
            <?php echo $existingNote ? 'Modifier votre note' : 'Laisser une note'; ?>
        </h3>

        <?php if ($existingNote): ?>
        <div class="my-note-box">
            <p><i class="fas fa-info-circle"></i> Vous avez déjà noté ce mentor. Vous pouvez modifier votre note ci-dessous.</p>
        </div>
        <?php endif; ?>

        <form method="POST" action="notation.php?id_mentor=<?php echo $id_mentor; ?>">

            <!-- Stars interactifs -->
            <div class="form-group">
                <label>Votre note <span style="color:var(--red);">*</span></label>
                <div class="star-rating" id="starRating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="score" id="star<?php echo $i; ?>" value="<?php echo $i; ?>">
                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> étoile(s)">
                        <i class="fas fa-star"></i>
                    </label>
                    <?php endfor; ?>
                </div>
                <div class="star-label" id="starLabel">Cliquez pour noter</div>
            </div>

            <div class="form-group">
                <label>Commentaire <span style="color:var(--text-muted); font-weight:400;">(optionnel)</span></label>
                <textarea name="commentaire" placeholder="Partagez votre expérience avec ce mentor..."><?php echo $existingNote ? '' : ''; ?></textarea>
            </div>

            <?php if (!empty($demandes_mentor)): ?>
            <div class="form-group">
                <label>Lié à une demande <span style="color:var(--text-muted); font-weight:400;">(optionnel)</span></label>
                <select name="id_demande">
                    <option value="">-- Aucune demande spécifique --</option>
                    <?php foreach ($demandes_mentor as $dem): ?>
                        <option value="<?php echo $dem['id_demande']; ?>"><?php echo htmlspecialchars($dem['titre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i>
                <?php echo $existingNote ? 'Mettre à jour la note' : 'Soumettre la note'; ?>
            </button>
        </form>
    </div>

    <!-- Avis des autres -->
    <div class="avis-card">
        <h3>
            <i class="fas fa-comments" style="color:var(--primary);"></i>
            Avis des autres stagiaires
            <?php if (!empty($allNotes)): ?>
                <span style="background:#eef2ff; color:#4f46e5; font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600;"><?php echo count($allNotes); ?></span>
            <?php endif; ?>
        </h3>

        <?php if (empty($allNotes)): ?>
            <div class="empty-notes">
                <i class="far fa-star"></i>
                <p>Aucun avis pour le moment. Soyez le premier à noter !</p>
            </div>
        <?php else: ?>
            <?php foreach ($allNotes as $a):
                $aInit = strtoupper(substr($a['prenom_uti'], 0, 1) . substr($a['nom_uti'], 0, 1));
            ?>
            <div class="avis-item">
                <div class="avis-top">
                    <div class="avis-av"><?php echo $aInit; ?></div>
                    <div>
                        <div class="avis-name"><?php echo htmlspecialchars($a['prenom_uti'] . ' ' . $a['nom_uti']); ?></div>
                        <div class="avis-stars">
                            <?php for ($i=1; $i<=5; $i++) echo $i <= $a['score'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                            <span style="margin-left:4px; font-size:11px; color:var(--text-muted);"><?php echo $a['score']; ?>/5</span>
                        </div>
                    </div>
                    <div class="avis-date"><?php echo date('d/m/Y', strtotime($a['date_note'])); ?></div>
                </div>
                <?php if (!empty($a['commentaires'])): ?>
                <div class="avis-comment"><?php echo htmlspecialchars($a['commentaires']); ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<script>
// Star rating labels
const labels = ['', 'Très mauvais 😕', 'Mauvais 😐', 'Correct 🙂', 'Bien 😊', 'Excellent 🌟'];
const starLabel = document.getElementById('starLabel');
const radios    = document.querySelectorAll('.star-rating input[type="radio"]');

radios.forEach(radio => {
    radio.addEventListener('change', () => {
        starLabel.textContent = labels[radio.value] || '';
        starLabel.style.color = '#854d0e';
        starLabel.style.fontWeight = '600';
    });
});

// Hover effect text
document.querySelectorAll('.star-rating label').forEach(label => {
    const val = label.getAttribute('for').replace('star', '');
    label.addEventListener('mouseenter', () => {
        starLabel.textContent = labels[val];
        starLabel.style.color = '#94a3b8';
        starLabel.style.fontWeight = '400';
    });
    label.addEventListener('mouseleave', () => {
        const checked = document.querySelector('.star-rating input:checked');
        if (checked) {
            starLabel.textContent = labels[checked.value];
            starLabel.style.color = '#854d0e';
            starLabel.style.fontWeight = '600';
        } else {
            starLabel.textContent = 'Cliquez pour noter';
            starLabel.style.color = 'var(--text-muted)';
            starLabel.style.fontWeight = '400';
        }
    });
});
</script>

</body>
</html>