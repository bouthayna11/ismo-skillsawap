<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'config.php';

$userId   = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Theme + dashboard
$themeClass = 'theme-stagiaire';
if (in_array($userRole, ['admin', 'administrateur'])) $themeClass = 'theme-admin';
elseif ($userRole === 'formateur')                    $themeClass = 'theme-formateur';



$statCompPend    = $pdo->query("SELECT COUNT(*) FROM validation WHERE etat_validation = 'en_attente'")->fetchColumn();
$statMentorsPend = $pdo->query("SELECT COUNT(*) FROM demande_mentorat WHERE statut = 'en_attente' AND (message_demande IS NULL OR message_demande = '')")->fetchColumn();

$success_msg = '';
$error_msg   = '';

// =====================================================================
// معالجة الفورم
// =====================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $email  = trim($_POST['email']  ?? '');
    $pwd    = trim($_POST['password']  ?? '');
    $pwd2   = trim($_POST['password2'] ?? '');

    if (empty($prenom) || empty($nom) || empty($email)) {
        $error_msg = "Veuillez remplir tous les champs obligatoires.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Email invalide.";
    } elseif (!empty($pwd) && $pwd !== $pwd2) {
        $error_msg = "Les mots de passe ne correspondent pas.";
    } elseif (!empty($pwd) && strlen($pwd) < 6) {
        $error_msg = "Le mot de passe doit contenir au moins 6 caractères.";
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
                    $error_msg = "Format non supporté (JPG, PNG, GIF, WEBP).";
                } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                    $error_msg = "La photo ne doit pas dépasser 2MB.";
                } else {
                    $uploadDir = 'uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                        $photo = $filename;
                    } else {
                        $error_msg = "Erreur lors de l'upload.";
                    }
                }
            }

            if (empty($error_msg)) {
                if ($photo) {
                    $pdo->prepare("UPDATE utilisateur SET prenom_uti=?, nom_uti=?, email_uti=?, photo=? WHERE id_utilisateur=?")
                        ->execute([$prenom, $nom, $email, $photo, $userId]);
                } else {
                    $pdo->prepare("UPDATE utilisateur SET prenom_uti=?, nom_uti=?, email_uti=? WHERE id_utilisateur=?")
                        ->execute([$prenom, $nom, $email, $userId]);
                }
                if (!empty($pwd)) {
                    $pdo->prepare("UPDATE utilisateur SET pwd_uti=? WHERE id_utilisateur=?")
                        ->execute([password_hash($pwd, PASSWORD_DEFAULT), $userId]);
                }
                $success_msg = "Profil mis à jour avec succès !";
            }
        }
    }
}

// جلب المستخدم (بعد التحديث)
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
        .theme-stagiaire { --sidebar-bg:#1e293b; --primary:#6366f1; --primary-hover:#4f46e5; }
        .theme-formateur { --sidebar-bg:#430b54; --primary:#df64d4; --primary-hover:#8447ac; }
        .theme-admin     { --sidebar-bg:#0c540b; --primary:#198218; --primary-hover:#1595b6; }

        :root {
            --main-bg:   #f8fafc;
            --border:    #e2e8f0;
            --text-main: #1e293b;
            --text-muted:#64748b;
            --green:     #22c55e;
            --red:       #ef4444;
            --yellow:    #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { display: flex; background: var(--main-bg); font-family: 'Inter', sans-serif; min-height: 100vh; color: var(--text-main); }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item  { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }
        .notif-dot { background: var(--yellow); color: #1e293b; font-size: 10px; font-weight: 700; padding: 1px 6px; border-radius: 10px; margin-left: auto; }

        .main  { flex: 1; padding: 40px; display: flex; flex-direction: column; gap: 24px; overflow-y: auto; }
        header { display: flex; justify-content: space-between; align-items: center; }
        .search-box { background: white; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--border); flex: 1; max-width: 400px; display: flex; align-items: center; }
        .search-box input { border: none; outline: none; margin-left: 12px; width: 100%; font-size: 14px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; overflow: hidden; }
        .avatar-top img { width: 100%; height: 100%; object-fit: cover; }

        .page-title { font-size: 24px; font-weight: 700; }

        .alert { padding: 12px 16px; border-radius: 10px; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        .edit-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 32px; max-width: 780px; }

        /* Avatar */
        .avatar-upload  { display: flex; align-items: center; gap: 20px; margin-bottom: 28px; padding-bottom: 28px; border-bottom: 1px solid var(--border); }
        .avatar-preview { width: 80px; height: 80px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; flex-shrink: 0; overflow: hidden; border: 3px solid #e0e7ff; }
        .avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
        .upload-info h4 { font-size: 14px; font-weight: 600; margin-bottom: 6px; }
        .upload-info p  { font-size: 12px; color: var(--text-muted); margin-bottom: 10px; }
        .btn-upload-label { display: inline-flex; align-items: center; gap: 6px; background: #eef2ff; color: var(--primary); padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-upload-label:hover { background: #e0e7ff; }
        input[type="file"] { display: none; }

        .section-sep { font-size: 13px; font-weight: 700; color: var(--text-muted); margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 7px; }
        .form-group input,
        .form-group select { width: 100%; padding: 11px 14px; border: 1px solid var(--border); border-radius: 10px; font-size: 14px; font-family: inherit; outline: none; background: white; transition: 0.2s; }
        .form-group input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        .form-group .hint { font-size: 11px; color: var(--text-muted); margin-top: 5px; }

        .btn-group  { display: flex; gap: 12px; margin-top: 28px; }
        .btn-save   { background: var(--primary); color: white; padding: 12px 28px; border-radius: 10px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-save:hover { background: var(--primary-hover); }
        .btn-cancel { background: white; color: var(--text-muted); padding: 12px 24px; border-radius: 10px; border: 1px solid var(--border); text-decoration: none; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .btn-cancel:hover { background: #f1f5f9; }

        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .notif-dot { display: none; }
            .form-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; padding: 10px; }
            .sidebar nav { display: flex; }
            .main { padding: 20px; }
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body class="<?php echo $themeClass; ?>">
<?php if($userRole==="admin")include 'sidebar.php'; ?>
<?php if($userRole==="formateur")include 'sidebarform.php'; ?>
<?php if($userRole==="stagiaire")include 'sidebarstgpass.php'; ?>

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

            <!-- Avatar -->
            <div class="avatar-upload">
                <div class="avatar-preview" id="avatarPreview">
                    <?php if (!empty($user['photo']) && $user['photo'] !== 'default.png' && file_exists('uploads/' . $user['photo'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($user['photo']); ?>" alt="Avatar">
                    <?php else: ?>
                        <?php echo $initials; ?>
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

            <!-- Infos -->
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

            <div class="btn-group">
                <button type="submit" class="btn-save"><i class="fas fa-save"></i> Enregistrer</button>
                <a href="profil.php" class="btn-cancel"><i class="fas fa-times"></i> Annuler</a>
            </div>

        </form>
    </div>
</main>

<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('avatarPreview').innerHTML =
                `<img src="${e.target.result}" alt="Preview" style="width:100%;height:100%;object-fit:cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>