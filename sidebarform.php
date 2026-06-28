<?php
$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$dash= "dashboard.php";
if ($user_role  === 'formateur')                                   { $dash= "dashboard_formateur.php"; }
elseif ($user_role  === 'admin' || $user_role  === 'administrateur') { $dash= "dashboard_admin.php"; }
?>
<aside class="sidebar">
    <h1>ISMO SkillSwap</h1>
    <nav>
        <p class="menu-title">GENERAL</p>
<a href="<?php echo $dash; ?>" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
        <a href="profil.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'profil.php') ? 'active' : ''; ?>">
            <i class="far fa-user"></i> <span>Profil</span>
        </a>

        <p class="menu-title">ACTIVITÉ</p>
        <a href="demandes_aide.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'demandes_aide.php') ? 'active' : ''; ?>">
            <i class="far fa-question-circle"></i> <span>Offres d'aide</span>
        </a>
        <a href="recherche.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'recherche.php') ? 'active' : ''; ?>">
            <i class="fas fa-search"></i> <span>Recherche</span>
        </a>
        <a href="passport.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'passport.php') ? 'active' : ''; ?>">
            <i class="fas fa-id-card"></i> <span>Passeport</span>
        </a>

        <p class="menu-title">CONTRÔLE</p>
        <a href="validation_competence.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'validation_competence.php') ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i> <span>Valider Compétences</span>
        </a>
        <a href="validation_mentors.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'validation_mentors.php') ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> <span>Valider Mentors</span>
        </a>
        <a href="gestion_badges.php" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) == 'gestion_badges.php') ? 'active' : ''; ?>">
            <i class="fas fa-medal"></i> <span>Gestion Badges</span>
        </a>

        <p class="menu-title">GÉNÉRAL</p>
        <a href="logout.php" class="menu-item" style="color: #f87171; margin-top: auto;">
            <i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span>
        </a>
    </nav>
</aside>