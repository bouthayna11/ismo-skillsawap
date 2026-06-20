<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Profil - ISMO SkillSwap</title>
        <link rel="stylesheet" href="style.css">
<script src="theme.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        /* Sidebar الثابتة والمطابقة للبروجي */
        .sidebar { width: 260px; background: var(--sidebar-bg); color: white; padding: 24px; display: flex; flex-direction: column; flex-shrink: 0; transition: 0.3s; }
        .sidebar h1 { font-size: 20px; font-weight: 700; margin-bottom: 32px; }
        .menu-title { font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin: 20px 0 8px 8px; font-weight: 600; }
        .menu-item { display: flex; align-items: center; text-decoration: none; color: #cbd5e1; padding: 12px 16px; border-radius: 8px; margin-bottom: 4px; font-size: 14px; font-weight: 500; }
        .menu-item i { margin-right: 12px; width: 20px; text-align: center; }
        .menu-item:hover, .menu-item.active { background: var(--primary); color: white; }

        .main { flex: 1; padding: 40px; width: 100%; display: flex; flex-direction: column; }
        
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .avatar-top { width: 38px; height: 38px; background: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; text-decoration: none; border: 2px solid white; box-shadow: 0 0 0 1px var(--border); }

        /* Form Card */
        .edit-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 32px; max-width: 800px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .form-group { margin-bottom: 24px; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 8px; color: var(--text-main); }
        
        input[type="text"], input[type="email"], textarea, select {
            width: 100%; padding: 12px 16px; border-radius: 10px; border: 1px solid var(--border); font-family: inherit; font-size: 14px; transition: 0.2s; outline: none;
        }
        input:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }

        .avatar-upload { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .avatar-preview { width: 80px; height: 80px; background: #f1f5f9; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; color: var(--text-muted); border: 2px dashed var(--border); }
        .btn-upload { font-size: 13px; color: var(--primary); font-weight: 600; cursor: pointer; }

        .btn-group { display: flex; gap: 12px; margin-top: 32px; }
        .btn-save { background: var(--primary); color: white; padding: 12px 24px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background: var(--primary-hover); }
        .btn-cancel { background: white; color: var(--text-muted); padding: 12px 24px; border-radius: 10px; border: 1px solid var(--border); text-decoration: none; font-weight: 600; font-size: 14px; }
        
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
        }
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; flex-direction: row; overflow-x: auto; padding: 10px; }
            .main { padding: 20px; }
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <h1>ISMO SkillSwap</h1>
        <p class="menu-title">GENERAL</p>
        <nav>
            <a href="dashboard.php" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            <a href="profil.php" class="menu-item active"><i class="far fa-user"></i> <span>Profil</span></a>

            <p class="menu-title">ACTIVITÉ</p>
            <a href="demandes_aide" class="menu-item" id="nav-demandes"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
            <a href="creer_demande.php" class="menu-item" id="nav-creer"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
            <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>

            <p class="menu-title">COMPÉTENCES</p>
            <a href="competence.php" class="menu-item"><i class="fas fa-award"></i> <span>Compétences</span></a>
            <a href="Mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
            <a href="passport.php" class="menu-item"><i class="fas fa-id-card"></i> <span>Passeport</span></a>

            <p class="menu-title">ADMIN</p>
            <a href="Admin.php" class="menu-item"><i class="fas fa-user-shield"></i> <span>Admin</span></a>
        </nav>
        <div style="margin-top: auto;">
            <a href="login.php" class="menu-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="main">
        <header>
            <h2 style="font-size: 24px; font-weight: 700;">Modifier le profil</h2>
            <div class="avatar-top"><a href="profil.php" id="user-initials-top" style="color:white; text-decoration:none;">JD</a></div>
        </header>

        <div class="edit-card">
            <form id="editForm">
                <div class="avatar-upload">
                    <div class="avatar-preview"><i class="fas fa-camera"></i></div>
                    <div>
                        <label class="btn-upload">Changer la photo de profil</label>
                        <p style="font-size: 12px; color: var(--text-muted);">JPG, GIF ou PNG. Max 2MB.</p>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Nom complet</label>
                        <input type="text" id="edit-fullname" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="edit-email" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Titre professionnel</label>
                    <input type="text" id="edit-title">
                </div>

                <div class="form-group">
                    <label>Bio</label>
                    <textarea id="edit-bio" rows="4"></textarea>
                </div>

                <div class="form-group">
                    <label>Localisation</label>
                    <input type="text" id="edit-ville">
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn-save">Enregistrer les modifications</button>
                    <a href="profil.php" class="btn-cancel">Annuler</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const userNom = localStorage.getItem("userNom") || "Jean Dupont";
            const userEmail = localStorage.getItem("userEmail") || "jean.dupont@email.com";
            const userRole = localStorage.getItem("userRole") || "stagiaire";
            
            const userTitre = localStorage.getItem("userTitre") || "Développement Web Full Stack";
            const userBio = localStorage.getItem("userBio") || "Développeur passionné avec 3 ans d'expérience en développement web. Spécialisé en React et Node.js. Toujours prêt à apprendre et à partager mes connaissances avec la communauté.";
            const userVille = localStorage.getItem("userVille") || "Paris, France";

            document.getElementById("edit-fullname").value = userNom;
            document.getElementById("edit-email").value = userEmail;
            document.getElementById("edit-title").value = userTitre;
            document.getElementById("edit-bio").value = userBio;
            document.getElementById("edit-ville").value = userVille;

            const initials = userNom.split(" ").map(n => n[0]).join("").toUpperCase();
            document.getElementById("user-initials-top").innerText = initials.substring(0, 2);

            const navCreer = document.getElementById("nav-creer");
            if (userRole === "formateur") {
                if(navCreer) navCreer.style.display = "none";
            }
        });

        document.getElementById("editForm").addEventListener("submit", function (e) {
            e.preventDefault();

            const newNom = document.getElementById("edit-fullname").value;
            const newEmail = document.getElementById("edit-email").value;
            const newTitre = document.getElementById("edit-title").value;
            const newBio = document.getElementById("edit-bio").value;
            const newVille = document.getElementById("edit-ville").value;

            localStorage.setItem("userNom", newNom);
            localStorage.setItem("userEmail", newEmail);
            localStorage.setItem("userTitre", newTitre);
            localStorage.setItem("userBio", newBio);
            localStorage.setItem("userVille", newVille);

            window.location.href = "profil.php";
        });

        function logout() {
            if(confirm("Voulez-vous vous déconnecter ?")) {
                window.location.href = 'login.php';
            }
        }
        document.addEventListener("DOMContentLoaded", function () {
    const userRole = localStorage.getItem("userRole") || "stagiaire";
    
    // 1. التحكم في الـ Sidebar (إخفاء خانة إنشاء طلب للفورماتور)
    const navCreer = document.getElementById("nav-creer");
    const navDemandes = document.getElementById("nav-demandes");
    
    if (userRole === "formateur") {
        if(navCreer) navCreer.style.display = "none"; // الفورماتور مكيكرييش طلب
        if(navDemandes) navDemandes.querySelector("span").innerText = "Offres d'aide"; // تبديل الإسم
    } else {
        if(navCreer) navCreer.style.display = "flex";
        if(navDemandes) navDemandes.querySelector("span").innerText = "Demandes d'aide";
    }
    
    // هنا كيكمل الكود القديم ديال الحروف الأولى والإسم...
});
    </script>
</body>
</html>