<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

$dashboard_link = "dashboard.php";
if ($userRole === 'formateur')                                   { $dashboard_link = "dashboard_formateur.php"; }
elseif (in_array($userRole, ['admin', 'administrateur']))        { $dashboard_link = "dashboard_admin.php"; }

$success_msg = '';
$error_msg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $email  = trim($_POST['email']  ?? '');
    $pwd    = trim($_POST['password'] ?? '');
    $pwd2   = trim($_POST['password2'] ?? '');

    if (empty($prenom) || empty($nom) || empty($email)) {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Email invalide.";
    } elseif (!empty($pwd) && $pwd !== $pwd2) {
        $error_msg = "Les mots de passe ne correspondent pas.";
    } else {
        $chkEmail = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE email_uti = ? AND id_utilisateur != ?");
        $chkEmail->execute([$email, $userId]);
        if ($chkEmail->fetchColumn() > 0) {
            $error_msg = "Cet email est déjà utilisé par un autre compte.";
        } else {
            $photo = null;
            if (!empty($_FILES['photo']['name'])) {
                $ext     = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (!in_array($ext, $allowed)) {
                    $error_msg = "Format de photo non supporté (JPG, PNG, GIF, WEBP uniquement).";
                } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                    $error_msg = "La photo ne doit pas dépasser 2MB.";
                } else {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                        $photo = $filename;
                    } else {
                        $error_msg = "Erreur lors de l'upload de la photo.";
                    }
                }
            }

            if (empty($error_msg)) {
                if ($photo) {
                    $upd = $pdo->prepare("UPDATE utilisateur SET prenom_uti = ?, nom_uti = ?, email_uti = ?, photo = ? WHERE id_utilisateur = ?");
                    $upd->execute([$prenom, $nom, $email, $photo, $userId]);
                } else {
                    $upd = $pdo->prepare("UPDATE utilisateur SET prenom_uti = ?, nom_uti = ?, email_uti = ? WHERE id_utilisateur = ?");
                    $upd->execute([$prenom, $nom, $email, $userId]);
                }

                if (!empty($pwd)) {
                    $hash = password_hash($pwd, PASSWORD_DEFAULT);
                    $pdo->prepare("UPDATE utilisateur SET pwd_uti = ? WHERE id_utilisateur = ?")->execute([$hash, $userId]);
                }

                $success_msg = "Profil mis à jour avec succès !";
            }
        }
    }
}

$queryUser = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
$queryUser->execute([$userId]);
$user     = $queryUser->fetch();
$initials = strtoupper(substr($user['prenom_uti'], 0, 1) . substr($user['nom_uti'], 0, 1));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Modifier le Profil</title>
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
        header { display: flex; justify-content: space-between; align-items: center; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; overflow: hidden; }
        .avatar-top img { width: 100%; height: 100%; object-fit: cover; }

        .page-title { font-size: 24px; font-weight: 700; }

        /* Alertes */
        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Form card */
        .edit-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 32px; max-width: 800px; }

        /* Avatar upload */
        .avatar-upload { display: flex; align-items: center; gap: 20px; margin-bottom: 28px; padding-bottom: 28px; border-bottom: 1px solid var(--border); }
        .avatar-preview { width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; flex-shrink: 0; overflow: hidden; border: 3px solid #e0e7ff; }
        .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
        .upload-info h4  { font-size: 14px; font-weight: 600; margin-bottom: 6px; }
        .upload-info p   { font-size: 12px; color: var(--text-muted); margin-bottom: 10px; }
        .btn-upload-label { display: inline-flex; align-items: center; gap: 6px; background: #eef2ff; color: var(--primary); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-upload-label:hover { background: #e0e7ff; }
        input[type="file"] { display: none; }

        /* Form groups */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 7px; color: var(--text-main); }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 10px;
            font-size: 14px; font-family: inherit; outline: none; background: white; transition: 0.2s;
        }
        .form-group input:focus,
        .form-group textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .form-group textarea { resize: vertical; min-height: 80px; line-height: 1.6; }
        .form-group .hint { font-size: 11px; color: var(--text-muted); margin-top: 5px; }

        /* Section divider */
        .section-sep { font-size: 13px; font-weight: 700; color: var(--text-muted); margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }

        /* Buttons */
        .btn-group { display: flex; gap: 12px; margin-top: 28px; }
        .btn-save   { background: var(--primary); color: white; padding: 12px 28px; border-radius: 10px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-save:hover { background: var(--primary-hover); }
        .btn-cancel { background: white; color: var(--text-muted); padding: 12px 24px; border-radius: 10px; border: 1px solid var(--border); text-decoration: none; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-cancel:hover { background: #f1f5f9; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .form-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <p class="menu-title">GENERAL</p>
    <nav>
        <a href="<?php echo $dashboard_link; ?>" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
        <a href="profil.php"        class="menu-item active"><i class="far fa-user"></i>           <span>Profil</span></a>

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
        <a href="competence.php"    class="menu-item"><i class="fas fa-award"></i>           <span>Compétences</span></a>
        <a href="mentors.php"       class="menu-item"><i class="fas fa-user-graduate"></i>   <span>Mentors</span></a>
        <a href="passport.php"      class="menu-item"><i class="fas fa-id-card"></i>         <span>Passeport</span></a>
        <?php endif; ?>
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
        <a href="profil.php" class="avatar-top">
            <?php if (!empty($user['photo']) && $user['photo'] !== 'default.png' && file_exists('uploads/' . $user['photo'])): ?>
                <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" alt="Avatar">
            <?php else: ?>
                <?php echo $initials; ?>
            <?php endif; ?>
        </a>
    </header>

    <h1 class="page-title">Modifier le Profil</h1>

    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <div class="edit-card">
        <form method="POST" action="modifier_profil.php" enctype="multipart/form-data">

            <!-- Avatar Upload -->
            <div class="avatar-upload">
                <div class="avatar-preview" id="avatarPreview">
                    <?php if (!empty($user['photo']) && $user['photo'] !== 'default.png' && file_exists('uploads/' . $user['photo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" alt="Avatar" id="previewImg">
                    <?php else: ?>
                        <span id="previewInitials"><?php echo $initials; ?></span>
                    <?php endif; ?>
                </div>
                <div class="upload-info">
                    <h4>Photo de profil</h4>
                    <p>JPG, PNG, GIF ou WEBP. Max 2MB.</p>
                    <label for="photoInput" class="btn-upload-label">
                        <i class="fas fa-camera"></i> Changer la photo
                    </label>
                    <input type="file" id="photoInput" name="photo" accept="image/*" onchange="previewPhoto(this)">
                </div>
            </div>

            <!-- Infos personnelles -->
            <div class="section-sep"><i class="fas fa-user"></i> Informations personnelles</div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Prénom <span style="color:var(--red);">*</span></label>
                    <input type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom_uti']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Nom <span style="color:var(--red);">*</span></label>
                    <input type="text" name="nom" value="<?php echo htmlspecialchars($user['nom_uti']); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Email <span style="color:var(--red);">*</span></label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email_uti']); ?>" required>
            </div>

            <!-- Sécurité -->
            <div class="section-sep"><i class="fas fa-lock"></i> Sécurité</div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Nouveau mot de passe</label>
                    <input type="password" name="password" placeholder="Laisser vide pour ne pas changer">
                    <p class="hint">Minimum 6 caractères recommandé</p>
                </div>
                <div class="form-group">
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="password2" placeholder="Répéter le nouveau mot de passe">
                </div>
            </div>

            <!-- Boutons -->
            <div class="btn-group">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <a href="profil.php" class="btn-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>

        </form>
    </div>
</main>

<script>
// Preview صورة قبل الرفع
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatarPreview');
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>