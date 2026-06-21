<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include('config.php');
$id_user= $_SESSION['user_id'];
$role= $_SESSION['user_role'];
//kanjibo maeloumat dyal utulisateur bash nemlolo initial
$Users= $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur =?");
$Users->execute([$id_user]);
$user= $Users->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "ST";
$actuelle_dashboard = "dashboard.php";
if ($role=== 'formateur'){ $actuelle_dashboard = "dashboard_formateur.php"; }
elseif ($role=== 'admin') { $actuelle_dashboard = "dashboard_admin.php"; }
$success_msg = '';
$error_msg   = '';
// Validation d'une nouvelle compétence:
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_demande'])) {
    extract($_POST);
    $id_comp= (int)$idcom;
    if ($id_comp > 0) {
        // Vérifier qu'il n'existe pas déjà une demande en attente pour cette compétence
        //hena kanchofo wash kayna had talab f tableau d validation yaeni en attente absh mayeawedshi ysarda
        $chekcomp = $pdo->prepare(
            "SELECT COUNT(*) FROM validation WHERE id_stagiaire=? AND id_comp=? AND etat_validation = 'en_attente'");
        $chekcomp->execute([$id_user,$id_comp]);
        // Vérifier que cette compétence n'existe pas déjà dans la table maitriser
        // hena kanchofo eawed wash makaynashi f tableau d maitriser yaeni heya eando maymknshi yeawed ytleb validation
        $maitriser = $pdo->prepare(
            "SELECT COUNT(*) FROM maitriser WHERE id_utilisateur=? AND id_comp =?");
        $maitriser->execute([ $id_user,$id_comp]);
        //ila kan dek chekcomp ketar mn 0 yaeni kayna competence bdek id d stagaire o en attende yaeni khaso ytsena ta tvalida mn eand formateur
        if ($chekcomp->fetchColumn() > 0) {
            $error_msg = "Vous avez déjà une demande en attente pour cette compétence.";
        //nfs chi henia mea maitriser ila kant maitriser rah meando elash yeawed validation anaffichiw had mssj
        } elseif ($maitriser->fetchColumn() > 0) {
            $error_msg = "Vous maîtrisez déjà cette compétence.";
        // ila makant la condition la 2 anzidoha f tableau d validation katzad b satatut d en-attende ta tvalida mn eand formateur wla admin
        } else {
            $ins = $pdo->prepare(
                "INSERT INTO validation (date_validation, etat_validation, id_comp, id_stagiaire) 
                 VALUES (CURDATE(), 'en_attente',?, ?)"
            );
            $ins->execute([ $id_comp,$id_user]);
            $success_msg = "Demande envoyée ! En attente de validation par le formateur.";
        }
    } else {
        $error_msg = "Veuillez sélectionner une compétence.";
    }
}
// =====================================================================

// Récupérer les compétences déjà maîtrisées par ce stagiaire
// henia kanjibo l competnce l mvalidyin yaeni etat_validation="valide"
$compmaitriser = $pdo->prepare(
    "SELECT m.id_comp, m.niveau_estime, c.nom_comp, c.categorie
     FROM maitriser m
     JOIN competence c ON m.id_comp = c.id_comp
     WHERE m.id_utilisateur =?
     ORDER BY c.categorie, c.nom_comp"
);
$compmaitriser->execute([$id_user]);
$mes_competences = $compmaitriser->fetchAll(PDO::FETCH_ASSOC);

// =====================================================================

// Récupération des demandes en attente et refusées
//henia kanjibo maeloumat ela competence li eandoum etat_validation="en-attende" wla "refuse" kaneadlohoum fblassa bohdoum
$stmtPending = $pdo->prepare(
    "SELECT v.id_validation, v.etat_validation, v.date_validation, c.nom_comp, c.categorie, c.id_comp
     FROM validation v
     JOIN competence c ON v.id_comp = c.id_comp
     WHERE v.id_stagiaire = ? AND v.etat_validation IN ('en_attente', 'refuse')
     ORDER BY v.date_validation DESC"
);
$stmtPending->execute([$id_user]);
$demandes = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

// =====================================================================
// Récupération de toutes les compétences disponibles (non demandées et non maîtrisées)
// henia kanjino competence li baqi maemloum demande baqi ma validahoum 
$myCompIds = array_column($mes_competences, 'id_comp');
$pendingIds = array_column($demandes, 'id_comp');
$excludeIds= array_unique(array_merge($myCompIds, $pendingIds));
$allComps = $pdo->query("SELECT id_comp, nom_comp, categorie FROM competence ORDER BY categorie, nom_comp")->fetchAll(PDO::FETCH_ASSOC);
$available = array_filter($allComps, fn($c) => !in_array($c['id_comp'], $excludeIds));

// Regroupement des compétences par catégorie
// henia kanjibo competnce ela hasab categorie zaema wash developpement wla IT ..
$by_category = [];
foreach ($mes_competences as $comp) {
    $by_category[$comp['categorie'] ?? 'Général'][] = $comp;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Compétences</title>
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

        /* Main */
        .main  { flex: 1; padding: 40px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .page-title { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .page-sub   { font-size: 14px; color: var(--text-muted); margin-bottom: 28px; }

        /* Alertes */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Stats */
        .stats-bar { display: flex; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
        .stat-box  { background: white; border: 1px solid var(--border); border-radius: 12px; padding: 16px 22px; flex: 1; min-width: 120px; text-align: center; }
        .stat-num  { font-size: 28px; font-weight: 700; color: var(--primary); }
        .stat-label{ font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        /* Layout */
        .content-grid { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }

        /* Cards */
        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 26px; }
        .section-title { font-size: 15px; font-weight: 700; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .section-title i { color: var(--primary); }

        .category-label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; margin: 16px 0 8px; padding-bottom: 6px; border-bottom: 1px solid var(--border); }

        .comp-item { display: flex; align-items: center; justify-content: space-between; padding: 11px 14px; border-radius: 10px; border: 1px solid var(--border); margin-bottom: 8px; background: #f8fafc; }
        .comp-left { display: flex; align-items: center; gap: 12px; }
        .comp-icon { width: 34px; height: 34px; background: #ede9fe; color: #7c3aed; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; }
        .comp-name  { font-size: 14px; font-weight: 600; }
        .comp-cat   { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

        .niveau-badge { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
        .niveau-debutant      { background: #fef9c3; color: #854d0e; }
        .niveau-intermediaire { background: #dbeafe; color: #1d4ed8; }
        .niveau-avance        { background: #dcfce7; color: #15803d; }
        .niveau-expert        { background: #ede9fe; color: #6d28d9; }

        /* Demandes en attente / refusées */
        .demande-item { display: flex; align-items: center; justify-content: space-between; padding: 11px 14px; border-radius: 10px; margin-bottom: 8px; border: 1px solid var(--border); }
        .demande-item.en_attente { background: #fefce8; border-left: 4px solid #fde047; }
        .demande-item.refuse     { background: #fef2f2; border-left: 4px solid var(--red); }
        .demande-name  { font-size: 14px; font-weight: 600; }
        .demande-date  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }
        .etat-badge    { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; white-space: nowrap; }
        .etat-en_attente { background: #fef9c3; color: #854d0e; }
        .etat-refuse     { background: #fef2f2; color: #dc2626; }

        .empty-state { text-align: center; padding: 32px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 36px; color: #cbd5e1; margin-bottom: 10px; display: block; }
        .empty-state p { font-size: 13px; }

        /* فورم الطلب */
        .add-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 26px; position: sticky; top: 20px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 8px; }
        .form-group select { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 13px; font-family: inherit; outline: none; background: white; transition: 0.2s; }
        .form-group select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .btn-add { width: 100%; background: var(--primary); color: white; border: none; padding: 11px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-add:hover { background: var(--primary-hover); }

        .info-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 10px; padding: 12px 14px; font-size: 12px; color: #0369a1; line-height: 1.6; margin-top: 14px; }

        /* Responsive */
        @media (max-width: 1100px) { .content-grid { grid-template-columns: 1fr; } .add-card { position: static; } }
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
            .stats-bar { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <p class="menu-title">GENERAL</p>
    <nav>
        <a href="<?php echo $actuelle_dashboard; ?>" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
        <a href="profil.php"        class="menu-item"><i class="far fa-user"></i>           <span>Profil</span></a>

        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
        <a href="creer_demande.php" class="menu-item"><i class="far fa-plus-square"></i>    <span>Créer une demande</span></a>
        <a href="recherche.php"     class="menu-item"><i class="fas fa-search"></i>          <span>Recherche</span></a>

        <p class="menu-title">COMPÉTENCES</p>
        <a href="competence.php"    class="menu-item active"><i class="fas fa-award"></i>    <span>Compétences</span></a>
        <a href="mentors.php"       class="menu-item"><i class="fas fa-user-graduate"></i>   <span>Mentors</span></a>
        <a href="passport.php"      class="menu-item"><i class="fas fa-id-card"></i>         <span>Passeport</span></a>
                    <p class="menu-title" >COMPTE</p>
            <a href="logout.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </nav>
</aside>

<main class="main">
    <header>
        <div class="search-box">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Rechercher...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <h1 class="page-title">Mes Compétences</h1>
    <p class="page-sub">Demandez la validation de vos compétences auprès du formateur</p>
    <!--henia ila kan chi wahd mn dek condition li emlna ela hasab demmandes kanaffichiwah henia-->
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-num"><?php echo count($mes_competences); ?></div>
            <div class="stat-label">Validées</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo count(array_filter($demandes, fn($d) => $d['etat_validation'] === 'en_attente')); ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo count(array_filter($demandes, fn($d) => $d['etat_validation'] === 'refuse')); ?></div>
            <div class="stat-label">Refusées</div>
        </div>
        <div class="stat-box">
            <div class="stat-num"><?php echo count($available); ?></div>
            <div class="stat-label">Disponibles</div>
        </div>
    </div>

    <div class="content-grid">

        <div style="display:flex; flex-direction:column; gap:24px;">

            <!--henia kanaffichiw lcompentce li mvalidyin li jibnahoum foq f php ismoum $mes_competences-->
            <div class="section-card">
                <p class="section-title"><i class="fas fa-check-circle"></i> Compétences validées</p>
                <?php if (empty($mes_competences)): ?>
                    <!--ila kan array khawi yaeni makayna ta comp m validya anketbo hadshi-->
                    <div class="empty-state">
                        <i class="fas fa-award"></i>
                        <p>Aucune compétence validée pour l'instant.<br>Envoyez une demande au formateur !</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($by_category as $cat => $comps): ?>
                        <p class="category-label"><i class="fas fa-folder" style="margin-right:5px;"></i><?php echo htmlspecialchars($cat); ?></p>
                        <?php foreach ($comps as $comp):
                            $niv_class = match($comp['niveau_estime'] ?? '') {
                                'intermediaire' => 'niveau-intermediaire',
                                'avance'        => 'niveau-avance',
                                'expert'        => 'niveau-expert',
                                default         => 'niveau-debutant',
                            };
                            $niv_icons = ['debutant' => '🌱', 'intermediaire' => '⚡', 'avance' => '🔥', 'expert' => '🏆'];
                            $icon = $niv_icons[$comp['niveau_estime'] ?? 'debutant'] ?? '🌱';
                        ?>
                        <div class="comp-item">
                            <div class="comp-left">
                                <div class="comp-icon"><i class="fas fa-code"></i></div>
                                <div>
                                    <div class="comp-name"><?php echo htmlspecialchars($comp['nom_comp']); ?></div>
                                    <div class="comp-cat"><?php echo htmlspecialchars($comp['categorie'] ?? ''); ?></div>
                                </div>
                            </div>
                            <span class="niveau-badge <?php echo $niv_class; ?>"><?php echo $icon . ' ' . ucfirst($comp['niveau_estime'] ?? 'débutant'); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- henia kanemlo l competence en-attente wla li refuse li jibnahoum foq mn BD siminahoum $demandes-->
            <?php if (!empty($demandes)): ?>
            <div class="section-card">
                <p class="section-title"><i class="fas fa-clock"></i> Demandes en cours</p>
                <?php foreach ($demandes as $dem):
                    $etat = $dem['etat_validation'];
                    $etatLabel = $etat === 'en_attente' ? '⏳ En attente' : '❌ Refusée';
                ?>
                <div class="demande-item <?php echo $etat; ?>">
                    <div>
                        <div class="demande-name"><?php echo htmlspecialchars($dem['nom_comp']); ?></div>
                        <div class="demande-date"><i class="far fa-calendar" style="margin-right:3px;"></i><?php echo $dem['date_validation']; ?></div>
                    </div>
                    <span class="etat-badge etat-<?php echo $etat; ?>"><?php echo $etatLabel; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>

        <!-- ══ فورم الطلب ══ -->
        <div class="add-card">
            <p class="section-title"><i class="fas fa-paper-plane"></i> Demander une validation</p>

            <?php if (empty($available)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-double" style="color:var(--green);"></i>
                    <p>Toutes les compétences ont été demandées ou validées !</p>
                </div>
            <?php else: ?>
                <form method="POST" action="competence.php">
                    <input type="hidden" name="action_demande" value="1">
                    <div class="form-group">
                        <label for="idcom">Compétence</label>
                        <select id="idcom" name="idcom" required>
                            <option value="">Sélectionner...</option>
                            <?php
                            $lastCat = '';
                            foreach ($available as $c):
                                $cat = $c['categorie'] ?? 'Général';
                                if ($cat !== $lastCat) {
                                    if ($lastCat !== '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($cat) . '">';
                                    $lastCat = $cat;
                                }
                            ?><!--henia kanaffichiw isxm d competnece -->
                                <option value="<?php echo (int)$c['id_comp']; ?>"><?php echo htmlspecialchars($c['nom_comp']); ?></option>
                            <?php endforeach; if ($lastCat !== '') echo '</optgroup>'; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-add">
                        <i class="fas fa-paper-plane"></i> Envoyer la demande
                    </button>
                </form>
                <div class="info-box">
                    <i class="fas fa-info-circle" style="margin-right:5px;"></i>
                    Votre demande sera examinée par un formateur. Une fois validée, la compétence apparaîtra dans vos compétences validées.
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

</body>
</html>