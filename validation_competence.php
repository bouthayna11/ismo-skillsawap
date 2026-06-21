<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['formateur', 'administrateur', 'admin'])) {
    header("Location: dashboard.php");
    exit();
}
$user_role = $_SESSION['user_role'];
require_once 'config.php';
$statCompPend   = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend= $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$queryUser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = :id");
$queryUser->execute(['id' => $userId]);
$user     = $queryUser->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "FO";

$dashboard_link = $userRole === 'formateur' ? "dashboard_formateur.php" : "dashboard_admin.php";
// Determine la classe dyal le theme b-hssab le rôle
$themeClass = 'theme-stagiaire';
if (in_array($user_role, ['admin', 'administrateur'])) {
    $themeClass = 'theme-admin';
} elseif ($user_role === 'formateur') {
    $themeClass = 'theme-formateur';
}
$success_msg = '';
$error_msg   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id_validation = (int) $_POST['id_validation'];
    $action        = $_POST['action'];

    if (in_array($action, ['valide', 'refuse'])) {

        // تحقق: واش هاد المستخدم كاين فـ table formateur
        $chkF = $pdo->prepare("SELECT COUNT(*) FROM formateur WHERE id_utilisateur = ?");
        $chkF->execute([$userId]);
        $isFormateur = $chkF->fetchColumn() > 0;

        if ($isFormateur) {
            $upd = $pdo->prepare(
                "UPDATE validation SET etat_validation = :etat, id_formateur = :formateur 
                 WHERE id_validation = :id AND etat_validation = 'en_attente'"
            );
            $upd->execute(['etat' => $action, 'formateur' => $userId, 'id' => $id_validation]);
        } else {
            $upd = $pdo->prepare(
                "UPDATE validation SET etat_validation = :etat 
                 WHERE id_validation = :id AND etat_validation = 'en_attente'"
            );
            $upd->execute(['etat' => $action, 'id' => $id_validation]);
        }

        if ($action === 'valide') {
            $getInfo = $pdo->prepare("SELECT id_comp, id_stagiaire FROM validation WHERE id_validation = :id");
            $getInfo->execute(['id' => $id_validation]);
            $info = $getInfo->fetch();

            if ($info) {
                $chk = $pdo->prepare("SELECT COUNT(*) FROM maitriser WHERE id_utilisateur = :u AND id_comp = :c");
                $chk->execute(['u' => $info['id_stagiaire'], 'c' => $info['id_comp']]);
                if ($chk->fetchColumn() == 0) {
                    $ins = $pdo->prepare("INSERT INTO maitriser (id_utilisateur, id_comp, niveau_estime) VALUES (:u, :c, 'debutant')");
                    $ins->execute(['u' => $info['id_stagiaire'], 'c' => $info['id_comp']]);
                }
            }
            $success_msg = "Compétence validée et ajoutée au profil du stagiaire ✅";
        } else {
            $success_msg = "Demande refusée ❌";
        }
    }
}

$filter = $_GET['filter'] ?? 'en_attente';
$allowed_filters = ['en_attente', 'valide', 'refuse', 'tous'];
if (!in_array($filter, $allowed_filters)) $filter = 'en_attente';

$whereClause = $filter !== 'tous' ? "WHERE v.etat_validation = :etat" : "WHERE 1=1";
$params = $filter !== 'tous' ? ['etat' => $filter] : [];

$stmt = $pdo->prepare(
    "SELECT v.id_validation, v.etat_validation, v.date_validation,
            c.nom_comp, c.categorie,
            u.prenom_uti, u.nom_uti, u.id_utilisateur AS id_stagiaire,
            s.filiere,
            f.prenom_uti AS formateur_prenom, f.nom_uti AS formateur_nom
     FROM validation v
     JOIN competence c  ON v.id_comp       = c.id_comp
     JOIN utilisateur u ON v.id_stagiaire  = u.id_utilisateur
     JOIN stagiaire s   ON v.id_stagiaire  = s.id_utilisateur
     LEFT JOIN utilisateur f ON v.id_formateur = f.id_utilisateur
     $whereClause
     ORDER BY v.date_validation DESC"
);
$stmt->execute($params);
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$stmtStats = $pdo->query("SELECT etat_validation, COUNT(*) as nb FROM validation GROUP BY etat_validation");
$stats_raw = $stmtStats->fetchAll(PDO::FETCH_ASSOC);
$stats = ['en_attente' => 0, 'valide' => 0, 'refuse' => 0];
foreach ($stats_raw as $s) { $stats[$s['etat_validation']] = $s['nb']; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Validation Compétences</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
         .theme-formateur {
            --sidebar-bg: #430b54; 
            --primary: #df64d4; 
            --primary-hover: #8447ac;
            --main-bg:       #f8fafc;
            --border:        #e2e8f0;
            --text-main:     #1e293b;
            --text-muted:    #64748b;
            --green:         #22c55e;
            --red:           #ef4444;
            --yellow:        #f59e0b;
        }
        .theme-admin{
     --sidebar-bg: #0c540b; 
    --primary: #198218; 
    --primary-hover: #1595b6;
    --main-bg: #f8fafc; 
    --border: #e2e8f0; 
    --text-main: #1e293b; 
    --text-muted: #64748b; 
    --green: #10b981; /* Was success */
    --red: #ef4444;   /* Was danger */
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
        .notif-dot { background: var(--yellow); color: #d6b810; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: auto;}


        /* Main */
        .main  { flex: 1; padding: 40px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .page-title { font-size: 24px; font-weight: 700; margin-bottom: 6px; }
        .page-sub   { font-size: 14px; color: var(--text-muted); margin-bottom: 28px; }

        /* Alertes */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Stats */
        .stats-bar { display: flex; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
        .stat-box  { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 16px 22px; flex: 1; min-width: 120px; text-align: center; cursor: pointer; text-decoration: none; transition: 0.2s; display: block; }
        .stat-box:hover { border-color: var(--primary); }
        .stat-box.active-filter { border-color: var(--primary); background: #eef2ff; }
        .stat-num   { font-size: 28px; font-weight: 700; }
        .stat-label { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .stat-num.pending { color: var(--yellow); }
        .stat-num.valide  { color: var(--green); }
        .stat-num.refuse  { color: var(--red); }
        .stat-num.tous    { color: var(--primary); }

        /* Tabs filtres */
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab  { padding: 8px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; color: var(--text-muted); background: white; border: 1px solid var(--border); transition: 0.2s; }
        .filter-tab:hover  { border-color: var(--primary); color: var(--primary); }
        .filter-tab.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* Table */
        .table-card { background: white; border-radius: 16px; border: 1px solid var(--border); overflow: hidden; }
        .table-header { padding: 18px 24px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
        .table-header h3 { font-size: 15px; font-weight: 700; }
        .count-badge { background: #eef2ff; color: var(--primary); padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        table { width: 100%; border-collapse: collapse; }
        th    { padding: 12px 20px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .04em; background: #f8fafc; border-bottom: 1px solid var(--border); }
        td    { padding: 14px 20px; font-size: 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .stagiaire-info { display: flex; align-items: center; gap: 10px; }
        .avatar-sm { width: 34px; height: 34px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; }
        .stag-name   { font-size: 14px; font-weight: 600; }
        .stag-filiere{ font-size: 11px; color: var(--text-muted); margin-top: 1px; }

        .comp-badge { display: inline-flex; align-items: center; gap: 6px; background: #ede9fe; color: #7c3aed; padding: 5px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; }
        .cat-text   { font-size: 11px; color: var(--text-muted); margin-top: 3px; }

        .etat-badge { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; padding: 4px 12px; border-radius: 20px; }
        .etat-en_attente { background: #fef9c3; color: #854d0e; }
        .etat-valide     { background: #dcfce7; color: #15803d; }
        .etat-refuse     { background: #fef2f2; color: #dc2626; }

        .actions { display: flex; gap: 8px; }
        .btn-valide  { background: var(--green); color: white; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .btn-refuse  { background: var(--red);   color: white; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .btn-valide:hover { background: #16a34a; }
        .btn-refuse:hover { background: #dc2626; }
        .formateur-text { font-size: 12px; color: var(--text-muted); font-style: italic; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; display: block; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            table { font-size: 13px; }
            th, td { padding: 10px 12px; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
            .table-card { overflow-x: auto; }
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">

<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <p class="menu-title">GENERAL</p>
    <nav>
        <a href="<?php echo $dashboard_link; ?>" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
        <a href="profil.php" class="menu-item "><i class="far fa-user"></i> <span>Profil</span></a>

        <?php if (in_array($user_role, ['stagiaire', 'formateur'])): ?>
        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i>
            <span><?php echo $user_role === 'formateur' ? "Offres d'aide" : "Demandes d'aide"; ?></span>
        </a>
        <?php if ($user_role === 'stagiaire'): ?>
            <a href="creer_demande.php" class="menu-item"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
        <?php endif; ?>
        <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>
        <?php if ($user_role === 'stagiaire'): ?>
        <?php endif; ?>
        <p class="menu-title">COMPÉTENCES</p>
        <a href="passport.php"   class="menu-item"><i class="fas fa-id-card"></i> <span>Passeport</span></a>
        <?php endif; ?>
        <?php if (in_array($user_role, ['admin', 'formateur'])): ?>
        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item active"><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
            <?php if ($statCompPend > 0): ?><span class="notif-dot"><?php echo $statCompPend; ?></span><?php endif; ?>
        </a>
        <a href="validation_mentors.php"    class="menu-item"><i class="fas fa-user-shield"></i>     <span>Valider Mentors </span>
            <?php if ($statMentorsPend > 0): ?><span class="notif-dot"><?php echo $statMentorsPend; ?></span><?php endif; ?>
        </a>        <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i>            <span>Gestion Badges</span></a>
        <a href="demandes_aide.php"  class="menu-item"><i class="far fa-question-circle"></i>  <span>Demandes d'aide</span></a>
        <a href="Mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i>    <span>Mentors</span></a>
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
            <input type="text" id="searchInput" placeholder="Rechercher stagiaire ou compétence...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <h1 class="page-title">Validation des Compétences</h1>
    <p class="page-sub">Examinez et validez les demandes des stagiaires</p>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-bar">
        <a href="?filter=en_attente" class="stat-box <?php echo $filter === 'en_attente' ? 'active-filter' : ''; ?>">
            <div class="stat-num pending"><?php echo $stats['en_attente']; ?></div>
            <div class="stat-label">En attente</div>
        </a>
        <a href="?filter=valide" class="stat-box <?php echo $filter === 'valide' ? 'active-filter' : ''; ?>">
            <div class="stat-num valide"><?php echo $stats['valide']; ?></div>
            <div class="stat-label">Validées</div>
        </a>
        <a href="?filter=refuse" class="stat-box <?php echo $filter === 'refuse' ? 'active-filter' : ''; ?>">
            <div class="stat-num refuse"><?php echo $stats['refuse']; ?></div>
            <div class="stat-label">Refusées</div>
        </a>
        <a href="?filter=tous" class="stat-box <?php echo $filter === 'tous' ? 'active-filter' : ''; ?>">
            <div class="stat-num tous"><?php echo array_sum($stats); ?></div>
            <div class="stat-label">Total</div>
        </a>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <h3><i class="fas fa-clipboard-list" style="margin-right:8px; color:var(--primary);"></i>
                <?php
                $labels = ['en_attente' => 'Demandes en attente', 'valide' => 'Demandes validées', 'refuse' => 'Demandes refusées', 'tous' => 'Toutes les demandes'];
                echo $labels[$filter];
                ?>
            </h3>
            <span class="count-badge"><?php echo count($demandes); ?> demande(s)</span>
        </div>

        <?php if (empty($demandes)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <p>Aucune demande <?php echo $filter !== 'tous' ? '"' . $filter . '"' : ''; ?> pour le moment.</p>
            </div>
        <?php else: ?>
        <table id="demandesTable">
            <thead>
                <tr>
                    <th>Stagiaire</th>
                    <th>Compétence</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($demandes as $d):
                    $etat = $d['etat_validation'];
                    $stagInitials = strtoupper(substr($d['prenom_uti'], 0, 1) . substr($d['nom_uti'], 0, 1));
                    $etatLabels = ['en_attente' => '⏳ En attente', 'valide' => '✅ Validée', 'refuse' => '❌ Refusée'];
                    
                ?>
                <tr class="demande-row"
                    data-name="<?php echo strtolower($d['prenom_uti'] . ' ' . $d['nom_uti']); ?>"
                    data-comp="<?php echo strtolower($d['nom_comp']); ?>">
                    <td>
                        <div class="stagiaire-info">
                            <div class="avatar-sm"><?php echo $stagInitials; ?></div>
                            <div>
                                <div class="stag-name"><?php echo htmlspecialchars($d['prenom_uti'] . ' ' . $d['nom_uti']); ?></div>
                                <div class="stag-filiere"><?php echo htmlspecialchars($d['filiere'] ?? ''); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="comp-badge"><i class="fas fa-code"></i><?php echo htmlspecialchars($d['nom_comp']); ?></span>
                        <div class="cat-text"><?php echo htmlspecialchars($d['categorie'] ?? ''); ?></div>
                    </td>
                    <td style="color:var(--text-muted); font-size:13px;">
                        <i class="far fa-calendar" style="margin-right:4px;"></i><?php echo $d['date_validation']; ?>
                    </td>
                    <td>
                        <span class="etat-badge etat-<?php echo $etat; ?>"><?php echo $etatLabels[$etat] ?? $etat; ?></span>
                        <?php if (!empty($d['formateur_prenom'])): ?>
                            <div class="formateur-text" style="margin-top:4px;">
                                par <?php echo htmlspecialchars($d['formateur_prenom'] . ' ' . $d['formateur_nom']); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($etat === 'en_attente'): ?>
                        <div class="actions">
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="id_validation" value="<?php echo (int)$d['id_validation']; ?>">
                                <input type="hidden" name="action"        value="valide">
                                <button type="submit" class="btn-valide" onclick="return confirm('Valider cette compétence ?')">
                                    <i class="fas fa-check"></i> Valider
                                </button>
                            </form>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="id_validation" value="<?php echo (int)$d['id_validation']; ?>">
                                <input type="hidden" name="action"        value="refuse">
                                <button type="submit" class="btn-refuse" onclick="return confirm('Refuser cette demande ?')">
                                    <i class="fas fa-times"></i> Refuser
                                </button>
                            </form>
                        </div>
                        <?php else: ?>
                            <span style="font-size:12px; color:var(--text-muted); font-style:italic;">Traitée</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</main>

<script>
    // بحث live
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const val = this.value.toLowerCase();
        document.querySelectorAll('.demande-row').forEach(row => {
            const name = row.getAttribute('data-name');
            const comp = row.getAttribute('data-comp');
            row.style.display = (name.includes(val) || comp.includes(val)) ? '' : 'none';
        });
    });
</script>

</body>
</html>