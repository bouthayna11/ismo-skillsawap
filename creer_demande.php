<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'stagiaire') {
    header("Location: dashboard.php");
    exit();
}
require_once 'config.php'; 
$filiere = $_SESSION['user_filiere'] ?? "Développement Digital"; 
$queryUser = $pdo->prepare("SELECT prenom_uti, nom_uti FROM utilisateur WHERE id_utilisateur = ?");
$queryUser->execute([$_SESSION['user_id']]);
$user = $queryUser->fetch();
$nom_complet = $user ? htmlspecialchars($user['prenom_uti'] . ' ' . $user['nom_uti']) : "Stagiaire";
$initials = $user ? strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1)) : "ST";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $technologie = trim($_POST['competence']);
    $niveau = trim($_POST['niveau']);
    if (!empty($titre) && !empty($description) && !empty($technologie) && !empty($niveau)) {
        $sql = "INSERT INTO demande_aide (titre, description, technologie, niveau, filiere, id_stagiaire) 
                VALUES (?,?,?,?,?,?)";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([ $titre, $description,$technologie, $niveau,$filiere, $_SESSION['user_id']]);
        if ($result) {
            echo "<script>alert('✅ Demande publiée avec succès !'); window.location.href='demandes_aide.php';</script>";
            exit();
        } else {
            echo "<script>alert('❌ Erreur lors de la publication.');</script>";
        }
    } else {
        echo "<script>alert('❌ Veuillez remplir tous les champs.');</script>";
    }
}
$dashboard_link = "dashboard.php"; 
if ($_SESSION['user_role']=== 'formateur') { $dashboard_link = "dashboard_formateur.php"; }
elseif ($_SESSION['user_role']=== 'admin') { $dashboard_link = "dashboard_admin.php"; }
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Créer une demande</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1e293b;
            --primary: #6366f1;
            --primary-hover: #4f46e5;
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

        .form-container { background: white; max-width: 800px; padding: 32px; border-radius: 16px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        
        .form-group { display: flex; flex-direction: column; margin-bottom: 24px; }
        .form-group label { margin-bottom: 8px; font-size: 14px; font-weight: 600; color: var(--text-main); }
        
        .form-group input, .form-group textarea, .form-group select { padding: 12px 16px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; outline: none; font-family: inherit; background: white; color: var(--text-main); transition: 0.2s; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .form-group textarea { resize: vertical; min-height: 140px; line-height: 1.6; }

        .row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        .buttons-group { display: flex; gap: 12px; margin-top: 32px; }
        .publish-btn { flex: 1; background: var(--primary); color: white; padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .publish-btn:hover { background: var(--primary-hover); }
        .cancel-btn { flex: 1; background: #f1f5f9; color: var(--text-muted); padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; transition: 0.2s; }
        .cancel-btn:hover { background: #e2e8f0; }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; flex-direction: row; overflow-x: auto; padding: 10px; }
            .sidebar h1 { display: none; }
            .sidebar nav { display: flex; flex-direction: row; }
            .main { padding: 20px; }
            .row-grid, .buttons-group { grid-template-columns: 1fr; display: flex; flex-direction: column; }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <h1>ISMO SkillSwap</h1>
        <p class="menu-title">GENERAL</p>
        <nav>
            <a href="<?php echo $dashboard_link; ?>" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            <a href="profil.php" class="menu-item"><i class="far fa-user"></i> <span>Profil</span></a>

            <p class="menu-title">ACTIVITÉ</p>
            <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
            <a href="creer_demande.php" class="menu-item active"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
            <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>

            <p class="menu-title">COMPÉTENCES</p>
            <a href="competence.php" class="menu-item"><i class="fas fa-award"></i> <span>Compétences</span></a>
            <a href="Mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
            <a href="passport.php" class="menu-item"><i class="fas fa-id-card"></i> <span>Passeport</span></a>
        </nav>
        <div style="margin-top: auto;">
            <a href="logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="main">
        <header>
            <div class="search-box">
                <i class="fas fa-search" style="color: #94a3b8;"></i>
                <input type="text" placeholder="Rechercher...">
            </div>
            <div class="header-actions">
                <a href="profil.php" class="avatar-top" style="color:white; text-decoration:none;"><?php echo $initials; ?></a>
            </div>
        </header>

        <h1 class="page-title">Créer une demande d'aide</h1>

        <div class="form-container">
            <form id="demandeForm" method="POST" action="creer_demande.php">
                <div class="form-group">
                    <label>Titre de la demande</label>
                    <input type="text" id="titre" name="titre" placeholder="Ex: Aide en React Hooks" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea id="description" name="description" placeholder="Décrivez votre problem ou votre besoin..." required></textarea>
                </div>

                <div class="row-grid">
                    <div class="form-group">
                        <label>Compétence</label>
                        <select id="competence" name="competence" required>
                            <option value="">Sélectionner...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Niveau</label>
                        <select id="niveau" name="niveau" required>
                            <option value="">Sélectionner...</option>
                        </select>
                    </div>
                </div>

                <div class="buttons-group">
                    <button type="submit" class="publish-btn">Publier la demande</button>
                    <a href="demandes_aide.php" class="cancel-btn">Annuler</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        const competences = ["HTML", "CSS", "JavaScript", "PHP", "MySQL", "React"];
        const niveaux = ["Débutant", "Intermédiaire", "Avancé"];

        const competenceSelect = document.getElementById("competence");
        const niveauSelect = document.getElementById("niveau");

        competences.forEach(function (skill) {
            competenceSelect.innerHTML += `<option value="${skill}">${skill}</option>`;
        });

        niveaux.forEach(function (level) {
            niveauSelect.innerHTML += `<option value="${level}">${level}</option>`;
        });
    </script>
</body>
</html>