<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
// theme + dashboard link
$themeClass = 'theme-stagiaire';
if (in_array($userRole , ['admin', 'administrateur'])) $themeClass = 'theme-admin';
elseif ($userRole  === 'formateur')                    $themeClass = 'theme-formateur';
$queryUser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$queryUser->execute([$userId]);
$user     = $queryUser->fetch();
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "ST";
$dashboard_link = "dashboard.php";
if ($userRole === 'formateur')                            { $dashboard_link = "dashboard_formateur.php"; }
elseif (in_array($userRole, ['admin', 'administrateur'])) { $dashboard_link = "dashboard_admin.php"; }


$stagiaires = $pdo->query(
    "SELECT u.id_utilisateur, u.prenom_uti, u.nom_uti, s.filiere, s.score_global,
            GROUP_CONCAT(DISTINCT c.nom_comp ORDER BY c.nom_comp SEPARATOR '||') AS competences,
            GROUP_CONCAT(DISTINCT ma.niveau_estime ORDER BY c.nom_comp SEPARATOR '||') AS niveaux,
            GROUP_CONCAT(DISTINCT b.nom_badge ORDER BY b.nom_badge SEPARATOR '||') AS badges,
            GROUP_CONCAT(DISTINCT b.image_badge ORDER BY b.nom_badge SEPARATOR '||') AS badges_icons,
            (SELECT COUNT(*) FROM demande_mentorat dm WHERE dm.id_stagiaire = u.id_utilisateur AND dm.statut = 'valide') AS is_mentor
     FROM utilisateur u
     JOIN stagiaire s ON u.id_utilisateur = s.id_utilisateur
     LEFT JOIN maitriser ma ON u.id_utilisateur = ma.id_utilisateur
     LEFT JOIN competence c  ON ma.id_comp = c.id_comp
     LEFT JOIN obtenir o     ON u.id_utilisateur = o.id_utilisateur
     LEFT JOIN badges b      ON o.id_badge = b.id_badge
     WHERE u.role = 'stagiaire'
     GROUP BY u.id_utilisateur
     ORDER BY s.score_global DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$demandes = $pdo->query(
    "SELECT d.id_demande, d.Titre AS titre, d.description, d.technologie, d.niveau, d.filiere, d.statut,
            u.prenom_uti, u.nom_uti
     FROM demande_aide d
     JOIN utilisateur u ON d.id_stagiaire = u.id_utilisateur
     ORDER BY d.id_demande DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$filieres = $pdo->query("SELECT DISTINCT categorie FROM competence WHERE categorie IS NOT NULL ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);
$competences = $pdo->query("SELECT DISTINCT nom_comp FROM competence ORDER BY nom_comp")->fetchAll(PDO::FETCH_COLUMN);

$stagiairesJSON = json_encode(array_map(function($s) {
    return [
        'id'          => $s['id_utilisateur'],
        'nom'         => $s['prenom_uti'] . ' ' . $s['nom_uti'],
        'initials'    => strtoupper(substr($s['prenom_uti'], 0, 1) . substr($s['nom_uti'], 0, 1)),
        'filiere'     => $s['filiere'] ?? '',
        'xp'          => (int)($s['score_global'] ?? 0),
        'niveau'      => max(1, floor(($s['score_global'] ?? 0) / 300)),
        'is_mentor'   => (int)$s['is_mentor'] > 0,
        'competences' => $s['competences'] ? explode('||', $s['competences']) : [],
        'niveaux'     => $s['niveaux']     ? explode('||', $s['niveaux'])     : [],
        'badges'      => $s['badges']      ? explode('||', $s['badges'])      : [],
        'badges_icons'=> $s['badges_icons']? explode('||', $s['badges_icons']): [],
    ];
}, $stagiaires), JSON_UNESCAPED_UNICODE);

$demandesJSON = json_encode(array_map(function($d) {
    return [
        'id'          => $d['id_demande'],
        'titre'       => $d['titre'] ?? '',
        'description' => $d['description'] ?? '',
        'technologie' => $d['technologie'] ?? '',
        'niveau'      => $d['niveau'] ?? '',
        'filiere'     => $d['filiere'] ?? '',
        'statut'      => $d['statut'] ?? 'ouverte',
        'auteur'      => $d['prenom_uti'] . ' ' . $d['nom_uti'],
    ];
}, $demandes), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Recherche</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .theme-stagiaire { --sidebar-bg:#1e293b; --primary:#6366f1; --primary-hover:#4f46e5; }
        .theme-formateur { --sidebar-bg:#430b54; --primary:#df64d4; --primary-hover:#8447ac; }
        .theme-admin     { --sidebar-bg:#0c540b; --primary:#198218; --primary-hover:#1595b6; }
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
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }

        .main  { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .search-box-top { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box-top input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; }

        .page-title { font-size: 24px; font-weight: 700; }

        .search-container { background: white; padding: 28px; border-radius: 16px; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 20px; }
        .search-wrap { position: relative; }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input-main { width: 100%; padding: 14px 16px 14px 44px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; outline: none; font-family: inherit; transition: 0.2s; }
        .search-input-main:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

        .filters { display: flex; gap: 16px; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { display: flex; flex-direction: column; gap: 6px; }
        .filter-group label { font-size: 13px; color: var(--text-muted); font-weight: 600; }
        .filter-group select { padding: 10px 14px; border-radius: 10px; border: 1px solid var(--border); font-size: 13px; outline: none; background: white; font-family: inherit; min-width: 160px; }
        .filter-group select:focus { border-color: var(--primary); }

        .type-buttons { display: flex; gap: 8px; }
        .type-btn { padding: 10px 20px; border: 1px solid var(--border); border-radius: 10px; background: white; color: var(--text-muted); cursor: pointer; font-size: 14px; font-weight: 600; font-family: inherit; transition: 0.2s; }
        .type-btn:hover  { border-color: var(--primary); color: var(--primary); }
        .type-btn.active { background: var(--primary); color: white; border-color: var(--primary); }

        .result-header { display: flex; align-items: center; justify-content: space-between; }
        .result-title  { font-size: 16px; font-weight: 700; }
        .result-count  { background: #eef2ff; color: var(--primary); padding: 3px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }

        .cards-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

        /* User card */
        .user-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 22px; display: flex; flex-direction: column; gap: 14px; cursor: pointer; transition: 0.2s; text-decoration: none; color: inherit; }
        .user-card:hover { box-shadow: 0 6px 20px rgba(99,102,241,0.12); transform: translateY(-3px); border-color: #c7d2fe; }

        .card-top { display: flex; align-items: center; gap: 14px; }
        .avatar   { width: 50px; height: 50px; border-radius: 50%; background: var(--primary); color: white; display: flex; justify-content: center; align-items: center; font-weight: 700; font-size: 18px; flex-shrink: 0; }
        .user-name { font-size: 15px; font-weight: 700; }
        .user-filiere { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .card-stats { display: flex; gap: 10px; flex-wrap: wrap; }
        .stat-pill  { display: inline-flex; align-items: center; gap: 5px; background: #f8fafc; border: 1px solid var(--border); border-radius: 20px; padding: 4px 12px; font-size: 12px; font-weight: 600; }
        .stat-pill.xp     { color: var(--primary); border-color: #c7d2fe; background: #eef2ff; }
        .stat-pill.niveau { color: #7c3aed; border-color: #ddd6fe; background: #f5f3ff; }
        .stat-pill.mentor { color: #15803d; border-color: #bbf7d0; background: #f0fdf4; }

        .tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag  { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .tag.skill  { background: #eef2ff; color: #4f46e5; }
        .tag.no-comp{ background: #f1f5f9; color: #94a3b8; font-style: italic; }

        .badges-row { display: flex; flex-wrap: wrap; gap: 6px; }
        .badge-pill { display: inline-flex; align-items: center; gap: 5px; background: #fefce8; border: 1px solid #fde047; border-radius: 20px; padding: 3px 10px; font-size: 11px; font-weight: 600; color: #854d0e; }
        .badge-pill i { font-size: 11px; color: var(--yellow); }

        /* Demande card */
        .demande-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 22px; display: flex; flex-direction: column; gap: 10px; transition: 0.2s; }
        .demande-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,.06); }
        .demande-titre { font-size: 15px; font-weight: 700; }
        .demande-desc  { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
        .demande-meta  { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .demande-auteur{ font-size: 12px; color: var(--text-muted); }
        .statut-badge  { font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; white-space: nowrap; }
        .statut-ouverte  { background: #dcfce7; color: #15803d; }
        .statut-en_cours { background: #fefce8; color: #854d0e; }
        .statut-resolue  { background: #dbeafe; color: #1d4ed8; }
        .statut-fermee   { background: #f1f5f9; color: #64748b; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); grid-column: 1/-1; }
        .empty-state i { font-size: 40px; color: #cbd5e1; margin-bottom: 12px; display: block; }

        /* Modal profil public */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center; padding: 20px; }
        .modal-overlay.open { display: flex; }
        .modal-box { background: white; border-radius: 20px; width: 100%; max-width: 540px; max-height: 90vh; overflow-y: auto; padding: 32px; position: relative; display: flex; flex-direction: column; gap: 20px; }
        .modal-close { position: absolute; top: 16px; right: 16px; background: #f1f5f9; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 16px; color: var(--text-muted); display: flex; align-items: center; justify-content: center; }
        .modal-close:hover { background: #e2e8f0; }

        .modal-avatar { width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; justify-content: center; align-items: center; font-size: 28px; font-weight: 700; margin: 0 auto; }
        .modal-name   { text-align: center; font-size: 20px; font-weight: 700; }
        .modal-pills  { display: flex; justify-content: center; gap: 10px; flex-wrap: wrap; }

        .modal-section { border-top: 1px solid var(--border); padding-top: 16px; }
        .modal-section h4 { font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 12px; }

        .comp-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .comp-item { display: flex; align-items: center; gap: 6px; background: #eef2ff; color: #4f46e5; border-radius: 20px; padding: 5px 12px; font-size: 12px; font-weight: 600; }
        .comp-niv  { font-size: 10px; background: rgba(99,102,241,0.15); padding: 1px 6px; border-radius: 10px; }

        .badge-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .badge-item { display: flex; align-items: center; gap: 6px; background: #fefce8; border: 1px solid #fde047; border-radius: 20px; padding: 5px 12px; font-size: 12px; font-weight: 600; color: #854d0e; }

        /* Responsive */
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
            .filters { flex-direction: column; }
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

        <?php if (in_array($userRole , ['stagiaire', 'formateur'])): ?>
        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i>
            <span><?php echo $userRole  === 'formateur' ? "Offres d'aide" : "Demandes d'aide"; ?></span>
        </a>
        <?php if ($userRole  === 'stagiaire'): ?>
            <a href="creer_demande.php" class="menu-item"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
        <?php endif; ?>
        <a href="recherche.php" class="menu-item active"><i class="fas fa-search"></i> <span>Recherche</span></a>
        <?php if ($userRole  === 'stagiaire'): ?>
        <p class="menu-title">COMPÉTENCES</p>
        <a href="competence.php" class="menu-item"><i class="fas fa-award"></i> <span>Compétences</span></a>
        <?php endif; ?>
        <a href="passport.php"   class="menu-item"><i class="fas fa-id-card"></i> <span>Passeport</span></a>
        <a href="mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
<?php endif; ?>
        <?php if (in_array($userRole , ['admin', 'formateur', 'administrateur'])): ?>
        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item "><i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
        </a>
        <a href="validation_mentors.php" class="menu-item"><i class="fas fa-user-shield"></i> <span>Valider Mentors</span>
        </a>
        <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i> <span>Gestion Badges</span></a>
        <?php endif; ?>

        <?php if (in_array($userRole , ['admin', 'administrateur'])): ?>
        <a href="gestion_utilisateurs.php" class="menu-item"><i class="fas fa-users-cog"></i> <span>Gérer Utilisateurs</span></a>
        <a href="gestion_categories.php"   class="menu-item"><i class="fas fa-tags"></i> <span>Gestion Catégories</span></a>
        <?php endif; ?>
        <p class="menu-title" >COMPTE</p>
        <a href="satisfaction.php"  class="menu-item"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
            <a href="logout.php" class="menu-item" style="color: #f87171;"style="margin-top: auto;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
    </nav>
</aside>

<main class="main">
    <header>
        <div class="search-box-top">
            <i class="fas fa-search" style="color:#94a3b8;"></i>
            <input type="text" placeholder="Rechercher...">
        </div>
        <a href="profil.php" class="avatar-top"><?php echo $initials; ?></a>
    </header>

    <h1 class="page-title">Recherche</h1>

    <div class="search-container">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" class="search-input-main" placeholder="Rechercher par nom, compétence, titre...">
        </div>
        <div class="filters">
            <div class="filter-group">
                <label>Type</label>
                <div class="type-buttons">
                    <button class="type-btn active" id="usersBtn"><i class="fas fa-users" style="margin-right:6px;"></i>Utilisateurs</button>
                    <button class="type-btn"         id="demandesBtn"><i class="far fa-question-circle" style="margin-right:6px;"></i>Demandes</button>
                </div>
            </div>
            <div class="filter-group">
                <label>Filière</label>
                <select id="filiereFilter">
                    <option value="">Toutes les filières</option>
                    <?php foreach ($filieres as $f): ?>
                        <option value="<?php echo htmlspecialchars(strtolower($f)); ?>"><?php echo htmlspecialchars($f); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" id="compGroup">
                <label>Compétence</label>
                <select id="compFilter">
                    <option value="">Toutes</option>
                    <?php foreach ($competences as $c): ?>
                        <option value="<?php echo htmlspecialchars(strtolower($c)); ?>"><?php echo htmlspecialchars($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" id="mentorGroup">
                <label>Statut</label>
                <select id="mentorFilter">
                    <option value="">Tous</option>
                    <option value="mentor">Mentors seulement</option>
                </select>
            </div>
        </div>
    </div>

    <div class="result-header">
        <h2 class="result-title">Résultats</h2>
        <span class="result-count" id="resultCount">0 résultat(s)</span>
    </div>

    <div class="cards-grid" id="cardsContainer"></div>
</main>

<!-- Modal Profil Public -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal(event)">
    <div class="modal-box" id="modalBox">
        <button class="modal-close" onclick="closeModalDirect()"><i class="fas fa-times"></i></button>
        <div class="modal-avatar" id="mAvatar"></div>
        <div class="modal-name"   id="mName"></div>
        <div class="modal-pills"  id="mPills"></div>

        <div class="modal-section" id="mCompSection">
            <h4><i class="fas fa-award" style="margin-right:6px; color:#6366f1;"></i>Compétences validées</h4>
            <div class="comp-list" id="mComps"></div>
        </div>

        <div class="modal-section" id="mBadgeSection">
            <h4><i class="fas fa-medal" style="margin-right:6px; color:#f59e0b;"></i>Badges obtenus</h4>
            <div class="badge-list" id="mBadges"></div>
        </div>
    </div>
</div>

<script>
const stagiaires = <?php echo $stagiairesJSON; ?>;
const demandes   = <?php echo $demandesJSON; ?>;

let typeActif = 'utilisateurs';

const container      = document.getElementById('cardsContainer');
const resultCount    = document.getElementById('resultCount');
const searchInput    = document.getElementById('searchInput');
const filiereFilter  = document.getElementById('filiereFilter');
const compFilter     = document.getElementById('compFilter');
const mentorFilter   = document.getElementById('mentorFilter');
const usersBtn       = document.getElementById('usersBtn');
const demandesBtn    = document.getElementById('demandesBtn');
const compGroup      = document.getElementById('compGroup');
const mentorGroup    = document.getElementById('mentorGroup');

// نيفو XP
function niveauLabel(niv) {
    if (niv >= 10) return '🏆 Expert';
    if (niv >= 6)  return '🔥 Avancé';
    if (niv >= 3)  return '⚡ Intermédiaire';
    return '🌱 Débutant';
}

// =====================================================================
// رندر الستاجييرز
// =====================================================================
function renderUsers(liste) {
    container.innerHTML = '';
    resultCount.textContent = liste.length + ' résultat(s)';
    if (!liste.length) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-search"></i><p>Aucun utilisateur trouvé.</p></div>';
        return;
    }
    liste.forEach(u => {
        const compsHTML = u.competences.length
            ? u.competences.slice(0, 4).map(c => `<span class="tag skill">${c}</span>`).join('')
              + (u.competences.length > 4 ? `<span class="tag no-comp">+${u.competences.length - 4}</span>` : '')
            : '<span class="tag no-comp">Aucune compétence</span>';

        const badgesHTML = u.badges.length
            ? u.badges.slice(0, 3).map((b, i) => `<span class="badge-pill"><i class="${u.badges_icons[i] || 'fas fa-medal'}"></i>${b}</span>`).join('')
              + (u.badges.length > 3 ? `<span class="badge-pill">+${u.badges.length - 3}</span>` : '')
            : '';

        const mentorBadge = u.is_mentor
            ? '<span class="stat-pill mentor"><i class="fas fa-chalkboard-teacher"></i> Mentor</span>' : '';

        const card = document.createElement('div');
        card.className = 'user-card';
        card.innerHTML = `
            <div class="card-top">
                <div class="avatar">${u.initials}</div>
                <div style="flex:1;">
                    <div class="user-name">${u.nom}</div>
                    <div class="user-filiere"><i class="fas fa-graduation-cap" style="margin-right:4px;"></i>${u.filiere || '—'}</div>
                </div>
            </div>
            <div class="card-stats">
                <span class="stat-pill xp"><i class="fas fa-star"></i>${u.xp} XP</span>
                <span class="stat-pill niveau"><i class="fas fa-layer-group"></i>Nv.${u.niveau} — ${niveauLabel(u.niveau)}</span>
                ${mentorBadge}
            </div>
            <div class="tags">${compsHTML}</div>
            ${badgesHTML ? `<div class="badges-row">${badgesHTML}</div>` : ''}
        `;
        card.addEventListener('click', () => openModal(u));
        container.appendChild(card);
    });
}

// =====================================================================
// رندر الديماندات
// =====================================================================
function renderDemandes(liste) {
    container.innerHTML = '';
    resultCount.textContent = liste.length + ' résultat(s)';
    if (!liste.length) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-search"></i><p>Aucune demande trouvée.</p></div>';
        return;
    }
    liste.forEach(d => {
        const statutLabel = {ouverte:'🟢 Ouverte', en_cours:'🟡 En cours', resolue:'🔵 Résolue', fermee:'⚫ Fermée'}[d.statut] || d.statut;
        container.innerHTML += `
        <div class="demande-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:10px;">
                <div class="demande-titre">${d.titre}</div>
                <span class="statut-badge statut-${d.statut || 'ouverte'}">${statutLabel}</span>
            </div>
            ${d.description ? `<div class="demande-desc">${d.description.substring(0, 120)}${d.description.length > 120 ? '...' : ''}</div>` : ''}
            <div class="demande-meta tags">
                ${d.technologie ? `<span class="tag skill">${d.technologie}</span>` : ''}
                ${d.niveau      ? `<span class="tag" style="background:#f1f5f9;color:#475569;">${d.niveau}</span>` : ''}
                ${d.filiere     ? `<span class="tag" style="background:#f1f5f9;color:#475569;">${d.filiere}</span>` : ''}
            </div>
            <div class="demande-auteur"><i class="fas fa-user" style="margin-right:4px;"></i>${d.auteur}</div>
        </div>`;
    });
}

// =====================================================================
// فلتر
// =====================================================================
function filtrer() {
    const q       = searchInput.value.toLowerCase().trim();
    const filiere = filiereFilter.value.toLowerCase();
    const comp    = compFilter.value.toLowerCase();
    const mentor  = mentorFilter.value;

    if (typeActif === 'utilisateurs') {
        const res = stagiaires.filter(u => {
            const matchQ      = !q      || u.nom.toLowerCase().includes(q) || u.competences.join(' ').toLowerCase().includes(q) || u.filiere.toLowerCase().includes(q);
            const matchFil    = !filiere|| u.filiere.toLowerCase().includes(filiere);
            const matchComp   = !comp   || u.competences.some(c => c.toLowerCase().includes(comp));
            const matchMentor = !mentor || (mentor === 'mentor' && u.is_mentor);
            return matchQ && matchFil && matchComp && matchMentor;
        });
        renderUsers(res);
    } else {
        const res = demandes.filter(d => {
            const matchQ   = !q      || d.titre.toLowerCase().includes(q) || d.description.toLowerCase().includes(q) || d.technologie.toLowerCase().includes(q) || d.auteur.toLowerCase().includes(q);
            const matchFil = !filiere|| (d.filiere && d.filiere.toLowerCase().includes(filiere));
            return matchQ && matchFil;
        });
        renderDemandes(res);
    }
}

// =====================================================================
// Modal Profil Public
// =====================================================================
function openModal(u) {
    document.getElementById('mAvatar').textContent = u.initials;
    document.getElementById('mName').textContent   = u.nom;

    // Pills
    const mentorPill = u.is_mentor ? '<span class="stat-pill mentor"><i class="fas fa-chalkboard-teacher"></i> Mentor</span>' : '';
    document.getElementById('mPills').innerHTML = `
        <span class="stat-pill xp"><i class="fas fa-star"></i>${u.xp} XP</span>
        <span class="stat-pill niveau"><i class="fas fa-layer-group"></i>Nv.${u.niveau} — ${niveauLabel(u.niveau)}</span>
        <span class="stat-pill" style="background:#f1f5f9; color:#475569;"><i class="fas fa-graduation-cap"></i>${u.filiere || '—'}</span>
        ${mentorPill}
    `;

    // Compétences
    const compsEl = document.getElementById('mComps');
    if (u.competences.length) {
        compsEl.innerHTML = u.competences.map((c, i) => {
            const niv = u.niveaux[i] ? `<span class="comp-niv">${u.niveaux[i]}</span>` : '';
            return `<div class="comp-item"><i class="fas fa-code"></i>${c}${niv}</div>`;
        }).join('');
        document.getElementById('mCompSection').style.display = '';
    } else {
        document.getElementById('mCompSection').style.display = 'none';
    }

    // Badges
    const badgesEl = document.getElementById('mBadges');
    if (u.badges.length) {
        badgesEl.innerHTML = u.badges.map((b, i) =>
            `<div class="badge-item"><i class="${u.badges_icons[i] || 'fas fa-medal'}"></i>${b}</div>`
        ).join('');
        document.getElementById('mBadgeSection').style.display = '';
    } else {
        document.getElementById('mBadgeSection').style.display = 'none';
    }

    document.getElementById('modalOverlay').classList.add('open');
}

function closeModal(e) {
    if (e.target === document.getElementById('modalOverlay')) closeModalDirect();
}
function closeModalDirect() {
    document.getElementById('modalOverlay').classList.remove('open');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModalDirect(); });

// =====================================================================
// Events
// =====================================================================
usersBtn.addEventListener('click', () => {
    typeActif = 'utilisateurs';
    usersBtn.classList.add('active'); demandesBtn.classList.remove('active');
    compGroup.style.display = ''; mentorGroup.style.display = '';
    filtrer();
});
demandesBtn.addEventListener('click', () => {
    typeActif = 'demandes';
    demandesBtn.classList.add('active'); usersBtn.classList.remove('active');
    compGroup.style.display = 'none'; mentorGroup.style.display = 'none';
    filtrer();
});

searchInput.addEventListener('keyup', filtrer);
filiereFilter.addEventListener('change', filtrer);
compFilter.addEventListener('change', filtrer);
mentorFilter.addEventListener('change', filtrer);

filtrer();
</script>
</body>
</html>