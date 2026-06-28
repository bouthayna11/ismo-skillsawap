<?php
include ('config.php');
session_start();
$err = []; 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saved_password = $_POST['password'] ?? '';
    $_POST = array_map('trim', $_POST);
    $_POST['password'] = $saved_password; 
    extract($_POST);
    if (!isset($nom)|| empty($nom)) $err['nom'] = "Le nom est obligatoire.";
    if (!isset($prenom)||empty($prenom)) $err['prenom'] = "Le prénom est obligatoire.";
    if (empty($email)) $err['email'] = "L'email est obligatoire."; 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err['email'] = "Le format de l'email est invalide.";
    if (!isset($nom)||empty($password))$err['password'] = "Le mot de passe est obligatoire."; 
    elseif (strlen($password) < 8)$err['password'] = "Le mot de passe doit contenir au moins 8 caractères.";
    if (($role === 'stagiaire' || $role === 'formateur') && empty($id)) {
        $err['id'] = ($role === 'stagiaire') ? "Le N° CEF est obligatoire." : "Le Matricule RH est obligatoire.";
    }
    $img_n = 'default.png';
    //kanchofo photo wash kayna
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(end(explode('.', $_FILES['photo']['name'])));
        $allowed = ['jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allowed)) $err['photo'] = "Seuls les formats JPG, JPEG et PNG sont autorisés.";
    elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024)$err['photo'] = "La taille de l'image ne doit pas dépasser 5 Mo.";
    if (empty($err)) {
        try {
            //henia kanjibo id d utulisateur bash nqedro nchfo beh wash kayn chi wahd dakhel beh
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email_uti = ?");
            $stmt->execute([$email]);
            //kanchofo wash kayn mn qbel had email
            if ($stmt->rowCount() > 0) {
                $err['email'] = "Cet email existe déjà !";
            } else {
                $mdp_hash = password_hash($password, PASSWORD_DEFAULT);
                //hena kanzido infos ila makanshi email meawed
                $stmt = $pdo->prepare("INSERT INTO utilisateur (nom_uti, prenom_uti, email_uti, pwd_uti, role, photo) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $prenom, $email, $mdp_hash, $role, $img_n]);
                $id_utu = $pdo->lastInsertId();//lastInserId katetik agherr id teadel mili eadeli had insert d dbbbb
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $img_n = "user_" . $id_utu . "." . $ext;
                    //hrna kanemlo ism lhaqiqi dyal imj
                    if (move_uploaded_file($_FILES['photo']['tmp_name'], 'uploads/' . $img_n)) {
                        $photo_up = $pdo->prepare("UPDATE utilisateur SET photo = ? WHERE id_utilisateur = ?");
                        $photo_up->execute([$img_n, $id_utu]);
                    }
                }
                if ($role === 'stagiaire') {
                    $filiere_res = 'Digital Development';
                    if (!empty($filiere)) $filiere_res = $filiere;
                    $pdo->prepare("INSERT INTO stagiaire (id_utilisateur, cef, filiere) VALUES (?, ?, ?)")->execute([$id_ut, $id, $filiere_res]);
                } elseif ($role === 'formateur') {
                    $pdo->prepare("INSERT INTO formateur (id_utilisateur, matricule_rh) VALUES (?, ?)")->execute([$id_utilisateur, $id]);
                } elseif ($role === 'admin') {
                    $pdo->prepare("INSERT INTO administrateur (id_utilisateur) VALUES (?)")->execute([$id_utilisateur]);
                }
                header('Location: login.php');
                exit();
                $query_filiere = $pdo->query("SELECT DISTINCT categorie FROM competence"); 
                $listes_filieres = $query_filiere->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $err['database'] = "Erreur d'inscription : " . $e->getMessage();
        }
    }
}}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>S'inscrire - ISMO SkillSwap</title>
    <link rel="stylesheet" href="style_auth.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Créer un compte</h2>
            <p>Rejoignez SkillSwap</p>

            <form id="regForm" action="inscription.php" method="POST" enctype="multipart/form-data">
                <div class="input-group">
                    <input type="text" id="nom" name="nom" placeholder="Nom"
                        value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required
                        style="margin-bottom: 5px;">
                    <?php if(isset($err['nom'])): ?>
                    <span style="color: #ef4444; font-size: 12px; display: block; margin-bottom: 10px;">
                        <?php echo $err['nom']; ?>
                    </span>
                    <?php endif; ?>

                    <input type="text" id="prenom" name="prenom" placeholder="Prénom"
                        value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>"
                        required style="margin-bottom: 5px;">
                    <?php if(isset($err['prenom'])): ?>
                    <span style="color: #ef4444; font-size: 12px; display: block; margin-bottom: 10px;">
                        <?php echo $err['prenom']; ?>
                    </span>
                    <?php endif; ?>

                    <input type="email" id="email" name="email" placeholder="Email (ex: exemple@gmail.com)"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required
                        style="margin-bottom: 5px;">
                    <?php if(isset($err['email'])): ?>
                    <span style="color: #ef4444; font-size: 12px; display: block; margin-bottom: 10px;">
                        <?php echo $err['email']; ?>
                    </span>
                    <?php endif; ?>

                    <input type="password" id="password" name="password" placeholder="Mot de passe" required
                        style="margin-bottom: 5px;">
                    <?php if(isset($err['password'])): ?>
                    <span style="color: #ef4444; font-size: 12px; display: block; margin-bottom: 10px;">
                        <?php echo $err['password']; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="input-group" style="text-align: left; margin-bottom: 15px;">
                    <label for="photo"
                        style="display: block; margin-bottom: 6px; font-weight: 500; color: var(--text-main); font-size: 14px;">Photo
                        de profil (Optionnelle)</label>
                    <input type="file" id="photo" name="photo" accept="image/png, image/jpeg, image/jpg"
                        style="width: 100%; padding: 8px; border: 1px dashed var(--border); border-radius: 8px; background: #fafafa; font-size: 13px;">
                    <?php if(isset($err['photo'])): ?>
                    <span style="color: #ef4444; font-size: 12px; display: block; margin-top: 5px;">
                        <?php echo $err['photo']; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="input-group">
                    <label>Vous êtes :</label>
                    <select id="role" name="role" onchange="toggleFields()"
                        style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: #f8fafc; font-size: 14px;">
                        <option value="stagiaire" <?php echo (isset($_POST['role']) && $_POST['role']==='stagiaire' )
                            ? 'selected' : '' ; ?>>Stagiaire</option>
                        <option value="formateur" <?php echo (isset($_POST['role']) && $_POST['role']==='formateur' )
                            ? 'selected' : '' ; ?>>Formateur</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role']==='admin' )
                            ? 'selected' : '' ; ?>>Administrateur</option>
                    </select>
                </div>

                <div id="filiere-group" class="input-group" style="text-align: left; margin-bottom: 15px;">
                    <label
                        style="display: block; margin-bottom: 6px; font-weight: 500; color: var(--text-main); font-size: 14px;">Filière</label>
                    <select name="filiere"
                        style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; background: #f8fafc; font-size: 14px;">
                        <option value="Digital Development">Digital Development</option>
                        <option value="Infrastructure Digitale">Infrastructure Digitale</option>
                        <option value="Gestion des Entreprises">Gestion des Entreprises</option>
                        <option value="Infographie">Infographie</option>
                        <option value="Intelligence Artificielle">Intelligence Artificielle - IA</option>
                    </select>
                </div>

                <div id="extra-field" class="input-group">
                    <input type="text" id="id" name="id" placeholder="N° CEF"
                        value="<?php echo isset($_POST['id']) ? htmlspecialchars($_POST['id']) : ''; ?>"
                        style="margin-bottom: 5px;">
                    <?php if(isset($err['id'])): ?>
                    <span style="color: #ef4444; font-size: 12px; display: block; margin-bottom: 10px;">
                        <?php echo $err['id']; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if(isset($err['database'])): ?>
                <div
                    style="color: #ef4444; background: #fee2e2; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; text-align: center;">
                    <?php echo $err['database']; ?>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-auth">S'inscrire</button>
            </form>

            <div class="footer-link">
                Déjà un compte ? <a href="login.php">Se connecter</a>
            </div>
        </div>
    </div>

    <script>
        function toggleFields() {
            const role = document.getElementById('role').value;
            const extraField = document.getElementById('extra-field');
            const filiereGroup = document.getElementById('filiere-group');
            const input = document.getElementById('id');

            if (role === 'stagiaire') {
                extraField.style.display = "block";
                filiereGroup.style.display = "block"; // إظهار الشعبة
                input.placeholder = 'N° CEF';
                input.required = true;
            } else if (role === 'formateur') {
                extraField.style.display = "block";
                filiereGroup.style.display = "none";  // إخفاء الشعبة
                input.placeholder = 'Matricule RH';
                input.required = true;
            } else if (role === 'admin') {
                extraField.style.display = "none";
                filiereGroup.style.display = "none";  // إخفاء الشعبة
                input.required = false;
                input.value = "";
            }
        }
        toggleFields();
    </script>
</body>

</html>