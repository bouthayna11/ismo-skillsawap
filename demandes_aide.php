<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
require_once 'config.php';

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Theme
$themeClass = 'theme-stagiaire';
if (in_array($userRole, ['admin', 'administrateur'])) $themeClass = 'theme-admin';
elseif ($userRole === 'formateur')                    $themeClass = 'theme-formateur';

// Dashboard link
$dashboard_link = "dashboard.php";
if ($userRole === 'formateur')                                { $dashboard_link = "dashboard_formateur.php"; }
elseif (in_array($userRole, ['admin', 'administrateur']))     { $dashboard_link = "dashboard_admin.php"; }

// Notifications
$statCompPend    = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend = $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();

// Initials
$users = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$users->execute([$userId]);
$user     = $users->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "ST";


// 1. Supprimer demande

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_supprimer'])) {
    $id_demande = (int) $_POST['iddem'];
    $check = $pdo->prepare("SELECT id_stagiaire FROM demande_aide WHERE id_demande = ?");
    $check->execute([$id_demande]);
    $ownerId = $check->fetchColumn();

    if ($ownerId == $userId || in_array($userRole, ['formateur', 'admin', 'administrateur'])) {
        $pdo->prepare("DELETE FROM reponses_aide WHERE id_demande = ?")->execute([$id_demande]);
        $pdo->prepare("DELETE FROM demande_aide WHERE id_demande = ?")->execute([$id_demande]);
    }
    header("Location: demandes_aide.php");
    exit();
}


// 2. Nouvelle réponse

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reponse_new'])) {
    $id_demande = (int) $_POST['iddem'];
    $message    = trim($_POST['message'] ?? '');

    if (!empty($message)) {
        $checkOwner = $pdo->prepare("SELECT id_stagiaire FROM demande_aide WHERE id_demande = ?");
        $checkOwner->execute([$id_demande]);
        $ownerId = $checkOwner->fetchColumn();

        if ($ownerId != $userId) {
            $checkDouble = $pdo->prepare("SELECT COUNT(*) FROM reponses_aide WHERE id_demande = ? AND id_aidant = ?");
            $checkDouble->execute([$id_demande, $userId]);
            if ($checkDouble->fetchColumn() == 0) {
                $pdo->prepare("INSERT INTO reponses_aide (id_demande, id_aidant, message, statut) VALUES (?,?,?,'en_attente')")
                    ->execute([$id_demande, $userId, $message]);
            }
        }
    }
    header("Location: demandes_aide.php#demande-" . $id_demande);
    exit();
}


// 3. Accepter / Refuser réponse

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reponse'])) {
    $id_reponse = (int) $_POST['idrep'];
    $status     = $_POST['status'] ?? '';

    $check = $pdo->prepare(
        "SELECT d.id_stagiaire FROM reponses_aide r
         JOIN demande_aide d ON r.id_demande = d.id_demande
         WHERE r.id_reponse = ?"
    );
    $check->execute([$id_reponse]);
    $owner = $check->fetchColumn();

    if ($owner == $userId && in_array($status, ['accepte', 'refuse'])) {
        $pdo->prepare("UPDATE reponses_aide SET statut = ? WHERE id_reponse = ?")->execute([$status, $id_reponse]);
    }
    header("Location: demandes_aide.php");
    exit();
}


$query = $pdo->query(
    "SELECT d.id_demande, d.Titre AS titre, d.description, d.technologie,
            d.niveau, d.filiere, d.id_stagiaire, d.statut,
            u.nom_uti, u.prenom_uti
     FROM demande_aide d
     JOIN utilisateur u ON d.id_stagiaire = u.id_utilisateur
     ORDER BY d.id_demande DESC"
);
$db_demandes = $query->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Demandes d'aide</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Themes ── */
        .theme-stagiaire { --sidebar-bg:#1e293b; --primary:#6366f1; --primary-hover:#4f46e5; }
        .theme-formateur { --sidebar-bg:#430b54; --primary:#df64d4; --primary-hover:#8447ac; }
        .theme-admin     { --sidebar-bg:#0c540b; --primary:#198218; --primary-hover:#1595b6; }

        :root {
            --main-bg:       #f8fafc;
            --border:        #e2e8f0;
            --text-main:     #1e293b;
            --text-muted:    #64748b;
            --green:         #22c55e;
            --red:           #ef4444;
            --yellow:        #f59e0b;
            --yellow-bg:     #fefce8;
            --yellow-border: #fde047;
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
        .main  { flex: 1; padding: 40px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .page-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
        .page-title  { font-size: 24px; font-weight: 700; }
        .btn-create  { background: var(--primary); color: white; padding: 10px 20px; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .btn-create:hover { background: var(--primary-hover); }

        /* Cards */
        .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 24px; }
        .card  { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; display: flex; flex-direction: column; gap: 14px; }

        .card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; }
        .card-title  { font-size: 16px; font-weight: 700; }
        .badge       { font-size: 11px; padding: 4px 10px; border-radius: 20px; font-weight: 600; white-space: nowrap; }
        .badge-tech  { background: #ede9fe; color: #7c3aed; }
        .badge-level { background: #e0f2fe; color: #0369a1; }

        .card-meta { font-size: 12px; color: var(--text-muted); display: flex; gap: 12px; flex-wrap: wrap; align-items: center; }
        .card-desc { font-size: 14px; color: var(--text-muted); line-height: 1.6; }

        /* Réponses */
        .reponses-title { font-size: 13px; font-weight: 600; color: var(--text-muted); border-top: 1px solid var(--border); padding-top: 14px; margin-bottom: 10px; }

        .reponse-box            { border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 6px; margin-bottom: 8px; border: 1px solid var(--border); background: #f8fafc; }
        .reponse-box.accepte    { border-left: 4px solid var(--green);         background: #f0fdf4; }
        .reponse-box.refuse     { border-left: 4px solid var(--red);           background: #fef2f2; }
        .reponse-box.en_attente { border-left: 4px solid var(--yellow-border); background: var(--yellow-bg); }

        .rep-author  { font-size: 13px; font-weight: 600; }
        .rep-message { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
        .rep-date    { font-size: 11px; color: #94a3b8; }
        .rep-status  { font-size: 11px; font-weight: 600; }
        .rep-status.accepte    { color: var(--green); }
        .rep-status.refuse     { color: var(--red); }
        .rep-status.en_attente { color: #92400e; }

        .rep-actions      { display: flex; gap: 8px; margin-top: 6px; }
        .rep-actions form { margin: 0; }
        .btn-accept { background: var(--green); color: white; border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-refuse { background: var(--red);   color: white; border: none; padding: 5px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .btn-accept:hover { background: #16a34a; }
        .btn-refuse:hover { background: #dc2626; }

        /* Reply form */
        .reply-form-section { border-top: 1px solid var(--border); padding-top: 14px; margin-top: 4px; }
        .reply-form-title   { font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; }
        .reply-form-section textarea { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; font-family: inherit; resize: vertical; min-height: 80px; outline: none; line-height: 1.5; transition: 0.2s; background: #f8fafc; }
        .reply-form-section textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); background: white; }
        .btn-reply { margin-top: 8px; background: var(--primary); color: white; border: none; padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .btn-reply:hover { background: var(--primary-hover); }
        .msg-already { font-size: 12px; color: #94a3b8; font-style: italic; padding: 8px 0; }

        /* Formateur info box */
        .formateur-note { background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #7c3aed; display: flex; align-items: center; gap: 6px; }

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
            .cards { grid-template-columns: 1fr; }
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

        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item active"><i class="far fa-question-circle"></i>
            <span><?php echo $userRole === 'formateur' ? "Offres d'aide" : "Demandes d'aide"; ?></span>
        </a>
        <?php if ($userRole === 'stagiaire'): ?>
        <a href="creer_demande.php" class="menu-item"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
                        <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>
        <?php endif; ?>
        <?php if ($userRole === 'stagiaire'): ?>
        <p class="menu-title">COMPÉTENCES</p>
        <a href="competence.php" class="menu-item"><i class="fas fa-award"></i>         <span>Compétences</span></a>
        <a href="mentors.php"    class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
        <a href="passport.php"   class="menu-item"><i class="fas fa-id-card"></i>       <span>Passeport</span></a>
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
        <a href="gestion_categories.php"   class="menu-item"><i class="fas fa-tags"></i>       <span>Gestion Catégories</span></a>
        <?php endif; ?>
    </nav>
    <div>
        <p class="menu-title">COMPTE</p>
        <a href="logout.php" class="menu-item" style="color:#f87171;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </div>
</aside>

<main class="main">
    <header>
        <div class="search-box">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" id="searchInput" placeholder="Rechercher...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <div class="page-header">
        <h1 class="page-title">
            <?php echo in_array($userRole, ['formateur']) ? "Offres d'aide" : "Demandes d'aide"; ?>
        </h1>
        <?php if ($userRole === 'stagiaire'): ?>
            <a href="creer_demande.php" class="btn-create"><i class="fas fa-plus"></i> Nouvelle demande</a>
        <?php endif; ?>
    </div>

    <?php if ($userRole === 'formateur'): ?>
    <div class="formateur-note">
        <i class="fas fa-eye"></i>
        En tant que formateur, vous pouvez consulter toutes les demandes et supprimer les publications inappropriées.
    </div>
    <?php endif; ?>

    <div class="cards" id="cardsContainer">
        <?php if (empty($db_demandes)): ?>
        <div class="empty-state">
            <i class="far fa-question-circle"></i>
            <p>Aucune demande pour le moment.</p>
        </div>
        <?php endif; ?>

        <?php foreach ($db_demandes as $d):
            $isOwner = ($d['id_stagiaire'] == $userId);

            $stmt = $pdo->prepare(
                "SELECT r.*, u.prenom_uti, u.nom_uti
                 FROM reponses_aide r
                 JOIN utilisateur u ON r.id_aidant = u.id_utilisateur
                 WHERE r.id_demande = ?
                 ORDER BY r.id_reponse ASC"
            );
            $stmt->execute([$d['id_demande']]);
            $reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $nb_accepte  = count(array_filter($reponses, fn($r) => ($r['statut'] ?? '') === 'accepte'));
            $dejaRepondu = false;
            foreach ($reponses as $r) {
                if ($r['id_aidant'] == $userId) { $dejaRepondu = true; break; }
            }
        ?>
        <div class="card" id="demande-<?php echo $d['id_demande']; ?>"
             data-title="<?php echo strtolower($d['titre'] ?? ''); ?>"
             data-tech="<?php echo strtolower($d['technologie'] ?? ''); ?>">

            <div class="card-header">
                <span class="card-title"><?php echo htmlspecialchars($d['titre'] ?? ''); ?></span>
                <div style="display:flex; align-items:center; gap:10px;">
                    <!-- Bouton supprimer: owner OU formateur OU admin -->
                    <?php if ($isOwner || in_array($userRole, ['formateur', 'admin', 'administrateur'])): ?>
                    <form method="POST" action="demandes_aide.php" style="margin:0;">
                        <input type="hidden" name="action_supprimer" value="1">
                        <input type="hidden" name="iddem" value="<?php echo $d['id_demande']; ?>">
                        <button type="submit"
                            onclick="return confirm('Supprimer cette demande ?')"
                            style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:14px;"
                            title="Supprimer">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                    <span class="badge badge-tech"><?php echo htmlspecialchars($d['technologie'] ?? ''); ?></span>
                </div>
            </div>

            <div class="card-meta">
                <span><i class="fas fa-user" style="margin-right:4px;"></i><?php echo htmlspecialchars($d['prenom_uti'] . ' ' . $d['nom_uti']); ?></span>
                <?php if (!empty($d['niveau'])): ?>
                    <span class="badge badge-level"><?php echo htmlspecialchars($d['niveau']); ?></span>
                <?php endif; ?>
                <?php if (!empty($d['filiere'])): ?>
                    <span><i class="fas fa-graduation-cap" style="margin-right:4px;"></i><?php echo htmlspecialchars($d['filiere']); ?></span>
                <?php endif; ?>
                <?php if ($nb_accepte > 0): ?>
                    <span style="color:var(--green);"><i class="fas fa-check-circle" style="margin-right:4px;"></i><?php echo $nb_accepte; ?> réponse(s) acceptée(s)</span>
                <?php endif; ?>
            </div>

            <div class="card-desc"><?php echo nl2br(htmlspecialchars($d['description'] ?? '')); ?></div>

            <!-- Réponses -->
            <div class="reponses-section">
                <p class="reponses-title">
                    <i class="fas fa-comments" style="margin-right:6px;"></i>
                    Réponses (<?php echo count($reponses); ?>)
                    <?php if ($isOwner && empty($reponses)): ?>
                        — <span style="color:#94a3b8; font-weight:400; font-style:italic;">aucune réponse pour l'instant</span>
                    <?php endif; ?>
                </p>

                <?php foreach ($reponses as $rep):
                    $statut = $rep['statut'] ?? 'en_attente';
                    $statusLabel = match($statut) {
                        'accepte' => '✅ Acceptée',
                        'refuse'  => '❌ Refusée',
                        default   => '⏳ En attente',
                    };
                ?>
                <div class="reponse-box <?php echo htmlspecialchars($statut); ?>">
                    <span class="rep-author">
                        <i class="fas fa-user-circle" style="margin-right:4px;"></i>
                        <?php echo htmlspecialchars($rep['prenom_uti'] . ' ' . $rep['nom_uti']); ?>
                    </span>
                    <span class="rep-message"><?php echo nl2br(htmlspecialchars($rep['message'] ?? '')); ?></span>
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px;">
                        <span class="rep-status <?php echo $statut; ?>"><?php echo $statusLabel; ?></span>
                        <?php if (!empty($rep['date_reponse'])): ?>
                            <span class="rep-date"><i class="far fa-clock" style="margin-right:3px;"></i><?php echo date('d/m/Y H:i', strtotime($rep['date_reponse'])); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($isOwner && $statut === 'en_attente'): ?>
                    <div class="rep-actions">
                        <form method="POST" action="demandes_aide.php">
                            <input type="hidden" name="action_reponse" value="1">
                            <input type="hidden" name="idrep"   value="<?php echo (int)$rep['id_reponse']; ?>">
                            <input type="hidden" name="status"   value="accepte">
                            <button type="submit" class="btn-accept" onclick="return confirm('Accepter cette réponse ?')"><i class="fas fa-check"></i> Accepter</button>
                        </form>
                        <form method="POST" action="demandes_aide.php">
                            <input type="hidden" name="action_reponse" value="1">
                            <input type="hidden" name="idrep"   value="<?php echo (int)$rep['id_reponse']; ?>">
                            <input type="hidden" name="status"   value="refuse">
                            <button type="submit" class="btn-refuse" onclick="return confirm('Refuser cette réponse ?')"><i class="fas fa-times"></i> Refuser</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- فورم الرد — غير للستاجير (مشي صاحب الطلب) والفورماتور ما يجاوبش -->
            <?php if (!$isOwner && $userRole === 'stagiaire'): ?>
            <div class="reply-form-section">
                <p class="reply-form-title"><i class="fas fa-reply" style="margin-right:6px;"></i>Apporter votre aide</p>
                <?php if ($dejaRepondu): ?>
                    <p class="msg-already"><i class="fas fa-info-circle" style="margin-right:4px;"></i>Vous avez déjà répondu à cette demande.</p>
                <?php else: ?>
                    <form method="POST" action="demandes_aide.php">
                        <input type="hidden" name="action_reponse_new" value="1">
                        <input type="hidden" name="iddem" value="<?php echo (int)$d['id_demande']; ?>">
                        <textarea name="message" placeholder="Écrivez votre réponse ici..." required></textarea>
                        <button type="submit" class="btn-reply"><i class="fas fa-paper-plane"></i> Envoyer</button>
                    </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
        <?php endforeach; ?>
    </div>
</main>

<script>
// بحث live
document.getElementById('searchInput').addEventListener('keyup', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('.card').forEach(card => {
        const title = card.getAttribute('data-title') || '';
        const tech  = card.getAttribute('data-tech')  || '';
        card.style.display = (title.includes(val) || tech.includes(val)) ? '' : 'none';
    });
});
</script>

</body>
</html>