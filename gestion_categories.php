<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'administrateur'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId    = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$infosuser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$infosuser->execute([$userId]);
$user     = $infosuser->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "AD";

$statCompPend   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend= $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();

$success_msg = '';
$error_msg   = '';

// إضافة كومبيتانس
if (isset($_POST['add_competence'])) {
    $nom_comp         = trim($_POST['nom_comp'] ?? '');
    $categorie_new    = trim($_POST['categorie_new'] ?? '');
    $categorie_select = trim($_POST['categorie'] ?? '');
    $categorie        = !empty($categorie_new) ? $categorie_new : $categorie_select;

    if (!empty($nom_comp) && !empty($categorie)) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM competence WHERE nom_comp = ? AND categorie = ?");
        $chk->execute([$nom_comp, $categorie]);
        if ($chk->fetchColumn() > 0) {
            $error_msg = "Cette compétence existe déjà dans cette catégorie.";
        } else {
            $pdo->prepare("INSERT INTO competence (nom_comp, categorie) VALUES (?, ?)")->execute([$nom_comp, $categorie]);
            $success_msg = "Compétence ajoutée avec succès !";
        }
    } else {
        $error_msg = "Veuillez remplir tous les champs.";
    }
}

// حذف كومبيتانس
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id   = (int) $_GET['delete'];
    $used = $pdo->prepare("SELECT COUNT(*) FROM validation WHERE id_comp = ?");
    $used->execute([$id]);
    if ($used->fetchColumn() > 0) {
        $error_msg = "Impossible de supprimer : compétence utilisée dans des validations.";
    } else {
        $pdo->prepare("DELETE FROM competence WHERE id_comp = ?")->execute([$id]);
        $success_msg = "Compétence supprimée.";
    }
}

// جلب الكومبيتانس
$competences = $pdo->query(
    "SELECT id_comp, nom_comp, categorie,
            (SELECT COUNT(*) FROM validation WHERE id_comp = competence.id_comp) AS nb_demandes,
            (SELECT COUNT(*) FROM maitriser WHERE id_comp = competence.id_comp) AS nb_maitrise
     FROM competence ORDER BY categorie, nom_comp"
)->fetchAll(PDO::FETCH_ASSOC);

$by_cat     = [];
foreach ($competences as $c) { $by_cat[$c['categorie'] ?? 'Général'][] = $c; }
$categories = array_unique(array_filter(array_column($competences, 'categorie')));
sort($categories);

$totalComps = count($competences);
$totalCats  = count($by_cat);
$totalValid = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation='valide'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Catalogue Compétences</title>
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

        /* Sidebar — نفس dashboard_admin */
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

        /* Alertes */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Stats — نفس ستايل dashboard */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .stat-card  { background: white; border-radius: 14px; padding: 18px; display: flex; align-items: center; gap: 15px; box-shadow: 0 2px 10px rgba(0,0,0,.06); transition: 0.3s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,.10); }
        .stat-num   { font-size: 24px; font-weight: 700; }
        .stat-label { font-size: 13px; color: var(--text-muted); }

        /* Layout */
        .content-grid { display: grid; grid-template-columns: 320px 1fr; gap: 24px; align-items: start; }

        /* Section cards — نفس dashboard */
        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .section-title { font-size: 15px; font-weight: 700; margin-bottom: 18px; display: flex; align-items: center; justify-content: space-between; }

        /* فورم */
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 7px; color: var(--text-main); }
        .form-group input,
        .form-group select { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; font-family: inherit; outline: none; background: white; transition: 0.2s; }
        .form-group input:focus,
        .form-group select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79,70,229,0.1); }

        .or-divider { text-align: center; font-size: 12px; color: var(--text-muted); margin: 12px 0; position: relative; }
        .or-divider::before, .or-divider::after { content: ''; position: absolute; top: 50%; width: 40%; height: 1px; background: var(--border); }
        .or-divider::before { left: 0; }
        .or-divider::after  { right: 0; }

        .btn-submit { width: 100%; background: var(--primary); color: white; border: none; padding: 11px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; }
        .btn-submit:hover { background: var(--primary-hover); }

        /* Table compétences */
        .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .count-badge  { background: #e0e7ff; color: var(--primary); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        .cat-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; padding: 12px 0 8px; border-bottom: 1px solid var(--border); margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }

        .comp-row  { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 10px; margin-bottom: 6px; transition: 0.15s; }
        .comp-row:hover { background: #f8fafc; }
        .comp-icon { width: 32px; height: 32px; background: #ede9fe; color: #7c3aed; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
        .comp-name { font-size: 14px; font-weight: 600; flex: 1; }
        .comp-stats{ font-size: 11px; color: var(--text-muted); display: flex; gap: 10px; }

        .btn-del { background: none; border: none; color: #cbd5e1; cursor: pointer; padding: 6px 8px; border-radius: 6px; font-size: 13px; }
        .btn-del:hover { background: #fef2f2; color: var(--red); }

        .empty-state { text-align: center; padding: 40px; color: var(--text-muted); }
        .empty-state i { font-size: 36px; color: #cbd5e1; margin-bottom: 10px; display: block; }

        /* Responsive */
        @media (max-width: 1100px) { .content-grid { grid-template-columns: 1fr; } }
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
            .stats-grid { grid-template-columns: 1fr 1fr; }
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
        <a href="gestion_categories.php"    class="menu-item active"><i class="fas fa-tags"></i>     <span>Gestion des catégories</span></a>

        <p class="menu-title">GENERAL</p>
        <a href="mentors.php"              class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
        <a href="demandes_aide.php"        class="menu-item"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
        <a href="satisfaction.php"         class="menu-item"><i class="far fa-star"></i>           <span>Avis & Satisfaction</span></a>
    </nav>
    <div style="margin-top:auto;">
        <a href="logout.php" class="menu-item" style="color:#f87171;"><i class="fas fa-power-off"></i> <span>Déconnexion</span></a>
    </div>
</aside>

<main class="main">
    <header>
        <div class="admin-badge"><i class="fas fa-tags"></i> Catalogue des Compétences</div>
        <div class="avatar-top"><?php echo $initials; ?></div>
    </header>

    <div>
        <h1 class="page-title">Gestion des Catégories & Compétences</h1>
        <p class="page-sub">Gérez le catalogue des compétences disponibles sur la plateforme</p>
    </div>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- Stats — نفس ستايل dashboard -->
    <div class="section-card">
        <div class="section-title">
            <span><i class="fas fa-chart-pie" style="color:var(--primary); margin-right:8px;"></i>Statistiques du Catalogue</span>
        </div>
        <div class="stats-grid">
            <div class="stat-card" style="border-left:4px solid #4f46e5;">
                <i class="fas fa-award" style="font-size:28px; color:#4f46e5;"></i>
                <div>
                    <div class="stat-num"><?php echo $totalComps; ?></div>
                    <div class="stat-label">Compétences</div>
                </div>
            </div>
            <div class="stat-card" style="border-left:4px solid #06b6d4;">
                <i class="fas fa-folder" style="font-size:28px; color:#06b6d4;"></i>
                <div>
                    <div class="stat-num"><?php echo $totalCats; ?></div>
                    <div class="stat-label">Catégories</div>
                </div>
            </div>
            <div class="stat-card" style="border-left:4px solid #22c55e;">
                <i class="fas fa-clipboard-check" style="font-size:28px; color:#22c55e;"></i>
                <div>
                    <div class="stat-num"><?php echo $totalValid; ?></div>
                    <div class="stat-label">Validations totales</div>
                </div>
            </div>
            <div class="stat-card" style="border-left:4px solid #f59e0b;">
                <i class="fas fa-hourglass-half" style="font-size:28px; color:#f59e0b;"></i>
                <div>
                    <div class="stat-num"><?php echo $statCompPend; ?></div>
                    <div class="stat-label">En attente</div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-grid">

        <!-- فورم إضافة -->
        <div class="section-card">
            <div class="section-title">
                <span><i class="fas fa-plus-circle" style="color:var(--primary); margin-right:8px;"></i>Ajouter une compétence</span>
            </div>
            <form method="POST" action="gestion_categories.php">
                <input type="hidden" name="add_competence" value="1">

                <div class="form-group">
                    <label>Nom de la compétence</label>
                    <input type="text" name="nom_comp" placeholder="Ex: React, Laravel, SQL..." required>
                </div>

                <div class="form-group">
                    <label>Catégorie existante</label>
                    <select name="categorie" id="selectCat">
                        <option value="">-- Sélectionner --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="or-divider">ou nouvelle catégorie</div>

                <div class="form-group">
                    <input type="text" name="categorie_new" id="newCat" placeholder="Ex: Infrastructure Digitale...">
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus"></i> Ajouter la compétence
                </button>
            </form>
        </div>

        <!-- Liste compétences -->
        <div class="section-card">
            <div class="table-header">
                <div class="section-title" style="margin-bottom:0;">
                    <span><i class="fas fa-list" style="color:var(--primary); margin-right:8px;"></i>Compétences disponibles</span>
                </div>
                <div style="display:flex; gap:10px; align-items:center;">
                    <input type="text" id="searchInput" placeholder="Rechercher..." style="padding:8px 12px; border:1px solid var(--border); border-radius:8px; font-size:13px; outline:none; width:180px;">
                    <span class="count-badge"><?php echo $totalComps; ?> au total</span>
                </div>
            </div>

            <?php if (empty($competences)): ?>
                <div class="empty-state">
                    <i class="fas fa-award"></i>
                    <p>Aucune compétence dans le catalogue.</p>
                </div>
            <?php else: ?>
                <?php foreach ($by_cat as $cat => $comps): ?>
                <p class="cat-label"><i class="fas fa-folder"></i><?php echo htmlspecialchars($cat); ?> <span style="font-weight:400;">(<?php echo count($comps); ?>)</span></p>
                <?php foreach ($comps as $comp): ?>
                <div class="comp-row" data-name="<?php echo strtolower($comp['nom_comp']); ?>">
                    <div class="comp-icon"><i class="fas fa-code"></i></div>
                    <span class="comp-name"><?php echo htmlspecialchars($comp['nom_comp']); ?></span>
                    <div class="comp-stats">
                        <span title="Demandes"><i class="fas fa-clipboard-check" style="color:var(--primary);"></i> <?php echo $comp['nb_demandes']; ?></span>
                        <span title="Maîtrisent"><i class="fas fa-users" style="color:var(--green);"></i> <?php echo $comp['nb_maitrise']; ?></span>
                    </div>
                    <?php if ($comp['nb_demandes'] == 0 && $comp['nb_maitrise'] == 0): ?>
                    <a href="gestion_categories.php?delete=<?php echo $comp['id_comp']; ?>"
                       class="btn-del"
                       onclick="return confirm('Supprimer <?php echo addslashes($comp['nom_comp']); ?> ?')"
                       title="Supprimer">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    <?php else: ?>
                    <button class="btn-del" disabled title="Utilisée — non supprimable" style="opacity:0.3; cursor:not-allowed;">
                        <i class="fas fa-lock"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const val = this.value.toLowerCase();
    document.querySelectorAll('.comp-row').forEach(row => {
        row.style.display = row.getAttribute('data-name').includes(val) ? '' : 'none';
    });
});
document.getElementById('newCat').addEventListener('input', function() {
    if (this.value.trim() !== '') document.getElementById('selectCat').value = '';
});
</script>

</body>
</html>