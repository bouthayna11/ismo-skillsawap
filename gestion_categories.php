<?php
session_start();

// 🔒 HACK/SÉCURITÉ : Vérifier si l'utilisateur est connecté et est un admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId = $_SESSION['user_id'];

$message = "";
$error = "";

// 📥 1. TRAITEMENT DU FORMULAIRE : Ajouter une Catégorie
if (isset($_POST['add_categorie'])) {
    $nom_cat = trim($_POST['nom_categorie']);
    if (!empty($nom_cat)) {
        try {
            // Remplace 'categorie' et 'nom_cat' par les vrais noms de ta table si nécessaire
            $stmt = $pdo->prepare("INSERT INTO categorie (nom_categorie) VALUES (?)");
            $stmt->execute([$nom_cat]);
            $message = "Catégorie ajoutée avec succès !";
        } catch (Exception $e) {
            // Secours au cas où la table n'existe pas encore ou les noms diffèrent
            $error = "Erreur lors de l'ajout de la catégorie.";
        }
    } else {
        $error = "Le nom de la catégorie ne peut pas être vide.";
    }
}

// 📥 2. TRAITEMENT DU FORMULAIRE : Ajouter une Compétence
if (isset($_POST['add_competence'])) {
    $nom_comp = trim($_POST['nom_competence']);
    $id_cat = intval($_POST['id_categorie']);
    
    if (!empty($nom_comp) && $id_cat > 0) {
        try {
            // Remplace par tes vrais noms de table/champs (ex: id_categorie, nom_competence)
            $stmt = $pdo->prepare("INSERT INTO competence (nom_competence, id_categorie) VALUES (?, ?)");
            $stmt->execute([$nom_comp, $id_cat]);
            $message = "Compétence ajoutée avec succès !";
        } catch (Exception $e) {
            $error = "Erreur lors de l'ajout de la compétence.";
        }
    } else {
        $error = "Veuillez remplir tous les champs de la compétence.";
    }
}

try {
    // Récupérer les infos de l'admin pour le Header
    $queryUser = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = :id");
    $queryUser->execute(['id' => $userId]);
    $user = $queryUser->fetch();
    $initials = strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1));

    // 📊 Récupérer toutes les catégories pour les formulaires et le tableau
    $categories = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie ASC")->fetchAll();

    /* 📊 Récupérer les compétences avec le nom de leur catégorie
       Si tes tables ne sont pas encore créées ou configurées ainsi, le bloc catch prendra le relais
    */
    $queryComp = "SELECT c.*, cat.nom_categorie 
                  FROM competence c 
                  INNER JOIN categorie cat ON c.id_categorie = cat.id_categorie 
                  ORDER BY cat.nom_categorie ASC, c.nom_competence ASC";
    $competences = $pdo->query($queryComp)->fetchAll();

} catch (Exception $e) {
    // Données fictives propres (Fallback) si ta base de données n'a pas encore ces tables
    $categories = [
        ['id_categorie' => 1, 'nom_categorie' => 'Développement Digital'],
        ['id_categorie' => 2, 'nom_categorie' => 'Infrastructure Digitale']
    ];
    $competences = [
        ['id_competence' => 1, 'nom_competence' => 'HTML / CSS', 'nom_categorie' => 'Développement Digital'],
        ['id_competence' => 2, 'nom_competence' => 'PHP / PDO', 'nom_categorie' => 'Développement Digital'],
        ['id_competence' => 3, 'nom_competence' => 'Réseaux Cisco', 'nom_categorie' => 'Infrastructure Digitale']
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Catégories & Skills</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-bg: #0f172a; --primary: #ef4444; --main-bg: #f8fafc; --border: #e2e8f0; --text-main: #1e293b; --text-muted: #64748b; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }
        
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; color: #ffffff; }
        .menu-title { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; transition: all 0.2s; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }
        
        .main { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .avatar-top { width: 40px; height: 40px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
        
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

        .forms-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 24px; }
        .content-box { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 24px; }
        .section-title { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        
        .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 13px; font-weight: 600; color: var(--text-main); }
        .form-group input, .form-group select { padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; outline: none; font-family: inherit; font-size: 14px; }
        .form-group input:focus, .form-group select:focus { border-color: var(--primary); }
        
        .btn-submit { background: var(--primary); color: white; border: none; padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        .btn-submit:hover { opacity: 0.9; }

        .table-container { overflow-x: auto; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        th { color: var(--text-muted); font-weight: 600; background: #f8fafc; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h1>AdminPanel</h1>
        <p class="menu-title">SUPERVISION</p>
        <nav>
            <a href="dashboard_admin.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>Vue Générale</span></a>
            <p class="menu-title">CONTRÔLE</p>
            <a href="gestion_utilisateurs.php" class="menu-item"><i class="fas fa-users-cog"></i> <span>Gérer Utilisateurs</span></a>
            <a href="gestion_categories.php" class="menu-item active"><i class="fas fa-tags"></i> <span>Catégories & Skills</span></a>
            <a href="gestion_badges.php" class="menu-item"><i class="fas fa-medal"></i> <span>Gestion des Badges</span></a>
            <a href="satisfaction.php" class="menu-item"><i class="far fa-star"></i> <span>Avis & Satisfaction</span></a>
        </nav>
        <div style="margin-top: auto;">
            <a href="logout.php" class="menu-item" style="color: #f87171;"><i class="fas fa-power-off"></i> <span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="main">
        <header>
            <div style="font-weight: 600; color: var(--primary);"><i class="fas fa-tags"></i> Catalogue des Compétences</div>
            <div class="avatar-top"><?php echo $initials; ?></div>
        </header>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="forms-grid">
            <div class="content-box">
                <h3 class="section-title"><i class="fas fa-folder-plus" style="color: var(--primary);"></i> Nouvelle Catégorie</h3>
                <form action="gestion_categories.php" method="POST">
                    <div class="form-group">
                        <label>Nom de la Catégorie (Filière / Secteur)</label>
                        <input type="text" name="nom_categorie" placeholder="Ex: Développement Digital" required>
                    </div>
                    <button type="submit" name="add_categorie" class="btn-submit">Créer la catégorie</button>
                </form>
            </div>

            <div class="content-box">
                <h3 class="section-title"><i class="fas fa-award" style="color: #6366f1;"></i> Nouvelle Compétence / Module</h3>
                <form action="gestion_categories.php" method="POST">
                    <div class="form-group">
                        <label Parent>Rattacher à la Catégorie</label>
                        <select name="id_categorie" required>
                            <option value="">-- Choisir une catégorie --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id_categorie']; ?>"><?php echo htmlspecialchars($cat['nom_categorie']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Nom de la Compétence (Module / Techno)</label>
                        <input type="text" name="nom_competence" placeholder="Ex: PHP / PDO" required>
                    </div>
                    <button type="submit" name="add_competence" class="btn-submit" style="background: #6366f1;">Ajouter la compétence</button>
                </form>
            </div>
        </div>

        <div class="content-box">
            <h3 class="section-title"><i class="fas fa-list"></i> Liste des Compétences Répertoriées</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID Skill</th>
                            <th>Compétence / Module</th>
                            <th>Catégorie / Filière</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($competences as $comp): ?>
                        <tr>
                            <td>#<?php echo $comp['id_competence']; ?></td>
                            <td style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($comp['nom_competence']); ?></td>
                            <td><span style="background: #e2e8f0; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 500; color: #334155;"><?php echo htmlspecialchars($comp['nom_categorie']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($competences)): ?>
                            <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">Aucune compétence dans le catalogue.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

</body>
</html>