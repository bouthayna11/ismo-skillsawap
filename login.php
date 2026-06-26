<?php
include ('config.php'); 
session_start();
$errors = []; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'stagiaire';
    if (empty($email)) {
    $errors['email'] = "L'email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { 
    $errors['email'] = "Le format de l'email est invalide.";
    }
    if (empty($password)) {
        $errors['password'] = "Le mot de passe est obligatoire.";
    }
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE email_uti = ? AND role = ? AND is_verified = 1");
            $stmt->execute([$email, $role]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['pwd_uti']) || $password === $user['pwd_uti']) {
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['user_nom'] = $user['nom_uti'];
                    $_SESSION['user_prenom'] = $user['prenom_uti'];
                    $_SESSION['user_email'] = $user['email_uti'];
                    $_SESSION['user_role'] = $user['role'];
                    if ($_SESSION['user_role'] === 'stagiaire') {
                        header('Location: dashboard.php');
                         exit();
                    }
                    elseif ($_SESSION['user_role'] === 'formateur') { 
                        header('Location: dashboard_formateur.php');
                         exit();
                    }
                    elseif ($_SESSION['user_role'] === 'admin') {
                        header('Location: dashboard_admin.php');
                        exit();
                    } 
                    else {
                        $errors['email'] = "Rôle utilisateur non reconnu.";
                    }
                } else {
                    $errors['password'] = "Mot de passe incorrect.";
            }
            } else {
                $errors['email'] = "Aucun compte trouvé avec cet email pour ce rôle.";
            }

        } catch (Exception $e) {
            $errors['database'] = "Erreur de connexion : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ISMO SkillSwap</title>
    <link rel="stylesheet" href="style_auth.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Bon retour !</h2>
            <p>Connectez-vous à votre espace</p>

            <form id="loginForm" action="login.php" method="POST">

                <div class="input-group" style="text-align: left; margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: var(--text-main);">Vous
                        êtes ?</label>
                    <select id="login-role" name="role" required
                        style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: #f8fafc; font-family: inherit; font-size: 14px;">
                        <option value="stagiaire" <?php echo (isset($_POST['role']) && $_POST['role']==='stagiaire' )
                            ? 'selected' : '' ; ?>>Stagiaire</option>
                        <option value="formateur" <?php echo (isset($_POST['role']) && $_POST['role']==='formateur' )
                            ? 'selected' : '' ; ?>>Formateur</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role']==='admin' )
                            ? 'selected' : '' ; ?>>Administrateur</option>
                    </select>
                </div>

                <div class="input-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" placeholder="example@email.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required
                        style="margin-bottom: 5px;">
                    <?php if(isset($errors['email'])): ?>
                    <span
                        style="color: #ef4444; font-size: 12px; display: block; margin-bottom: 10px; text-align: left;">
                        <?php echo $errors['email']; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <label>Mot de passe</label>
                    <input type="password" name="password" placeholder="••••••••" required style="margin-bottom: 5px;">
                    <?php if(isset($errors['password'])): ?>
                    <span
                        style="color: #ef4444; font-size: 12px; display: block; margin-bottom: 10px; text-align: left;">
                        <?php echo $errors['password']; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if(isset($errors['database'])): ?>
                <div
                    style="color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; text-align: center;">
                    <?php echo $errors['database']; ?>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-auth">Se connecter</button>
            </form>

            <div class="footer-link">
                Nouveau ici ? <a href="inscription.php">Créer un compte</a>
            </div>
        </div>
    </div>
</body>

</html>