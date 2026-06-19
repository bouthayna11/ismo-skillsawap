<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Passeport de Compétences</title>
        <link rel="stylesheet" href="style.css">
<script src="theme.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #1e293b;
            --primary: #6366f1;
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

        .title-row { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; }
        .page-title { font-size: 24px; font-weight: 700; }
        .btn-pdf { background: var(--primary); color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }

        .profile-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 32px; display: flex; gap: 32px; align-items: center; }
        .avatar-large { width: 110px; height: 110px; background: #4f46e5; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 38px; font-weight: 700; flex-shrink: 0; }
        .profile-details { flex: 1; }
        .profile-details h2 { font-size: 24px; font-weight: 700; margin-bottom: 4px; display: flex; align-items: center; gap: 10px; }
        .xp-badge { background: #eef2ff; color: var(--primary); font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 600; }
        .job-title { color: var(--text-muted); font-size: 15px; margin-bottom: 8px; font-weight: 500; }
        .stars-row { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--text-muted); }
        .stars { color: #fbbf24; display: flex; gap: 3px; }

        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 10px; }
        .card-stat { background: white; padding: 20px; border-radius: 16px; border: 1px solid var(--border); text-align: center; }
        .card-stat .val { font-size: 28px; font-weight: 700; display: block; margin-bottom: 4px; }
        .card-stat .label { color: var(--text-muted); font-size: 13px; font-weight: 500; }
        
        .card-stat.xp-box { background: #f5f3ff; border-color: #ddd6fe; color: #4f46e5; }
        .card-stat.comp-box { background: #fffbeb; border-color: #fef3c7; color: #b45309; }
        .card-stat.badge-box { background: #fff5f5; border-color: #fed7d7; color: #db2777; }
        .card-stat.class-box { background: #f0fdf4; border-color: #dcfce7; color: #16a34a; }

        .section-card { background: white; border-radius: 16px; border: 1px solid var(--border); padding: 28px; display: flex; flex-direction: column; gap: 20px; }
        .section-card h3 { font-size: 18px; font-weight: 700; color: #1e293b; }
        .comp-row { display: flex; flex-direction: column; gap: 20px; }
        .comp-item { background: #ffffff; border-radius: 12px; border: 1px solid var(--border); padding: 20px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .comp-left { display: flex; align-items: center; gap: 16px; min-width: 150px; }
        .check-icon { color: #10b981; font-size: 20px; }
        .comp-name { font-size: 15px; font-weight: 700; color: #1e293b; }
        .comp-level { font-size: 12px; color: var(--text-muted); margin-top: 2px; display: block; }
        
        .comp-center { flex: 1; display: flex; align-items: center; gap: 16px; }
        .bar-bg { flex: 1; height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; }
        .bar-fill { height: 100%; background: var(--primary); border-radius: 10px; }
        .percentage { font-size: 14px; font-weight: 700; color: #1e293b; min-width: 40px; text-align: right; }

        .comp-right { text-align: right; font-size: 12px; color: var(--text-muted); min-width: 140px; }
        .validator { font-weight: 600; color: #475569; }
        .validator span { color: var(--primary); }

        /* Badges et Certifications (Grid ديال 2 أعمدة) */
        .badges-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .badge-card { display: flex; align-items: center; gap: 16px; padding: 18px; border-radius: 12px; border: 1px solid var(--border); background: #ffffff; }
        .b-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .b-icon.blue { background: #eff6ff; color: #2563eb; }
        .b-icon.indigo { background: #e0e7ff; color: #4f46e5; }
        .b-icon.purple { background: #f3e8ff; color: #a855f7; }
        .b-details h4 { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
        .b-details p { font-size: 12px; color: var(--text-muted); }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar { width: 80px; padding: 20px 10px; }
            .sidebar h1, .sidebar span, .menu-title { display: none; }
            .menu-item { justify-content: center; padding: 12px; }
            .menu-item i { margin-right: 0; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .badges-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .sidebar { width: 100%; height: auto; flex-direction: row; overflow-x: auto; padding: 10px; }
            .sidebar h1 { display: none; }
            .sidebar nav { display: flex; flex-direction: row; }
            .main { padding: 20px; }
            .profile-card { flex-direction: column; text-align: center; }
            .stars-row { justify-content: center; }
            .stats-grid { grid-template-columns: 1fr; }
            .comp-item { flex-direction: column; align-items: flex-start; gap: 12px; }
            .comp-center { width: 100%; }
            .comp-right { text-align: left; }
        }
    </style>
</head>

<body>

    <aside class="sidebar">
        <h1>ISMO SkillSwap</h1>
        <p class="menu-title">GENERAL</p>
        <nav>
            <a href="dashboard.php" class="menu-item"><i class="fas fa-th-large"></i> <span>Dashboard</span></a>
            <a href="profil.php" class="menu-item"><i class="far fa-user"></i> <span>Profil</span></a>

            <p class="menu-title">ACTIVITÉ</p>
            <a href="demandesd'aide.php" class="menu-item" id="nav-demandes"><i class="far fa-question-circle"></i> <span>Demandes d'aide</span></a>
            <a href="créerunedemande.php" class="menu-item" id="nav-creer"><i class="far fa-plus-square"></i> <span>Créer une demande</span></a>
            <a href="recherche.php" class="menu-item"><i class="fas fa-search"></i> <span>Recherche</span></a>

            <p class="menu-title">COMPÉTENCES</p>
            <a href="competence.php" class="menu-item"><i class="fas fa-award"></i> <span>Compétences</span></a>
            <a href="Mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
            <a href="passport.php" class="menu-item active"><i class="fas fa-id-card"></i> <span>Passeport</span></a>

            <p class="menu-title">ADMIN</p>
            <a href="Admin.php" class="menu-item"><i class="fas fa-user-shield"></i> <span>Admin</span></a>
        </nav>
        <div>
            <a href="login.php" class="menu-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i> <span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="main">
        <header>
            <div class="search-box">
                <i class="fas fa-search" style="color: #94a3b8;"></i>
                <input type="text" placeholder="Rechercher...">
            </div>
            <div class="header-actions">
                <div class="avatar-top"><a href="profil.php" id="user-initials" style="color:white; text-decoration:none;">JD</a></div>
            </div>
        </header>

        <div class="title-row">
            <h2 class="page-title">Passeport de Compétences</h2>
            <button class="btn-pdf"><i class="fas fa-file-pdf"></i> Export PDF</button>
        </div>

        <div class="profile-card">
            <div class="avatar-large" id="user-avatar-large">TD</div>
            <div class="profile-details">
                <h2><span id="user-fullname">Thomas Durand</span> <span class="xp-badge">Niveau 12</span></h2>
                <div class="job-title" id="user-role-title">Développement Web Full Stack</div>
                <div class="stars-row">
                    <div class="stars">
                        <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i>
                    </div>
                    <span style="margin-left: 6px; font-weight: 500; color: #1e293b;">4.7</span>
                    <span style="margin: 0 4px;">•</span>
                    <span>Membre depuis Mars 2025</span>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="card-stat xp-box">
                <span class="val">3420</span>
                <span class="label">Points XP</span>
            </div>
            <div class="card-stat comp-box">
                <span class="val">6</span>
                <span class="label">Compétences</span>
            </div>
            <div class="card-stat badge-box">
                <span class="val">5</span>
                <span class="label">Badges</span>
            </div>
            <div class="card-stat class-box">
                <span class="val">#24</span>
                <span class="label">Classement</span>
            </div>
        </div>

        <div class="section-card">
            <h3>Compétences Validées</h3>
            <div class="comp-row">
                
                <div class="comp-item">
                    <div class="comp-left">
                        <i class="fas fa-circle-check check-icon"></i>
                        <div>
                            <span class="comp-name">React.js</span>
                            <span class="comp-level">Niveau: Avancé</span>
                        </div>
                    </div>
                    <div class="comp-center">
                        <div class="bar-bg"><div class="bar-fill" style="width: 85%;"></div></div>
                        <span class="percentage">85%</span>
                    </div>
                    <div class="comp-right">
                        <p class="validator">Validé par <span>Sophie Martin</span></p>
                        <p style="margin-top: 2px;">15/03/2026</p>
                    </div>
                </div>

                <div class="comp-item">
                    <div class="comp-left">
                        <i class="fas fa-circle-check check-icon"></i>
                        <div>
                            <span class="comp-name">TypeScript</span>
                            <span class="comp-level">Niveau: Intermédiaire</span>
                        </div>
                    </div>
                    <div class="comp-center">
                        <div class="bar-bg"><div class="bar-fill" style="width: 70%;"></div></div>
                        <span class="percentage">70%</span>
                    </div>
                    <div class="comp-right">
                        <p class="validator">Validé par <span>Ahmed Benali</span></p>
                        <p style="margin-top: 2px;">12/03/2026</p>
                    </div>
                </div>

                <div class="comp-item">
                    <div class="comp-left">
                        <i class="fas fa-circle-check check-icon"></i>
                        <div>
                            <span class="comp-name">Node.js</span>
                            <span class="comp-level">Niveau: Avancé</span>
                        </div>
                    </div>
                    <div class="comp-center">
                        <div class="bar-bg"><div class="bar-fill" style="width: 90%;"></div></div>
                        <span class="percentage">90%</span>
                    </div>
                    <div class="comp-right">
                        <p class="validator">Validé par <span>Sophie Martin</span></p>
                        <p style="margin-top: 2px;">10/03/2026</p>
                    </div>
                </div>

                <div class="comp-item">
                    <div class="comp-left">
                        <i class="fas fa-circle-check check-icon"></i>
                        <div>
                            <span class="comp-name">SQL</span>
                            <span class="comp-level">Niveau: Avancé</span>
                        </div>
                    </div>
                    <div class="comp-center">
                        <div class="bar-bg"><div class="bar-fill" style="width: 80%;"></div></div>
                        <span class="percentage">80%</span>
                    </div>
                    <div class="comp-right">
                        <p class="validator">Validé par <span>Thomas Leroy</span></p>
                        <p style="margin-top: 2px;">08/03/2026</p>
                    </div>
                </div>

            </div>
        </div>

        <div class="section-card">
            <h3>Badges et Certifications</h3>
            <div class="badges-grid">
                
                <div class="badge-card">
                    <div class="b-icon gray"><i class="fas fa-users"></i></div>
                    <div class="b-details">
                        <h4>Mentor Débutant</h4>
                        <p>Aidez 5 étudiants avec succès</p>
                        <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px;">Obtenu le 15/05/2026</span>
                    </div>
                </div>

                <div class="badge-card">
                    <div class="b-icon blue"><i class="fas fa-code"></i></div>
                    <div class="b-details">
                        <h4>Expert React</h4>
                        <p>Résolvez 10 demandes React</p>
                        <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px;">Obtenu le 12/05/2026</span>
                    </div>
                </div>

                <div class="badge-card">
                    <div class="b-icon blue"><i class="fab fa-php"></i></div>
                    <div class="b-details">
                        <h4>Expert PHP</h4>
                        <p>Maîtrisez PHP à un niveau avancé</p>
                        <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px;">Obtenu le 08/05/2026</span>
                    </div>
                </div>

                <div class="badge-card">
                    <div class="b-icon purple"><i class="fas fa-bullseye"></i></div>
                    <div class="b-details">
                        <h4>Bug Hunter</h4>
                        <p>Résolvez 20 problèmes techniques</p>
                        <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px;">Obtenu le 05/05/2026</span>
                    </div>
                </div>

                <div class="badge-card">
                    <div class="b-icon blue"><i class="fas fa-user-friends"></i></div>
                    <div class="b-details">
                        <h4>Team Player</h4>
                        <p>Collaborez avec 15 personnes</p>
                        <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 2px;">Obtenu le 01/05/2026</span>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const userNom = localStorage.getItem("userNom") || "Jean Dupont";
            const userRole = localStorage.getItem("userRole") || "stagiaire";

            document.getElementById("user-fullname").innerText = userNom;

            const roleTitle = document.getElementById("user-role-title");
            if (userRole === "formateur") {
                roleTitle.innerText = "Formateur ISMO";
                roleTitle.style.color = "#a855f7";
            } else {
                roleTitle.innerText = "Stagiaire en Développement";
                roleTitle.style.color = "#6366f1";
            }

            const initials = userNom.split(" ").map(n => n[0]).join("").toUpperCase();
            document.getElementById("user-initials").innerText = initials.substring(0, 2);
            document.getElementById("user-avatar-large").innerText = initials.substring(0, 2);
            
            const navCreer = document.getElementById("nav-creer");
            if (userRole === "formateur") {
                if(navCreer) navCreer.style.display = "none";
            }
        });

        function logout() {
            if(confirm("Voulez-vous vous déconnecter ?")) {
                window.location.href = 'login.php';
            }
        }
        document.addEventListener("DOMContentLoaded", function () {
    const userRole = localStorage.getItem("userRole") || "stagiaire";
        const navCreer = document.getElementById("nav-creer");
    const navDemandes = document.getElementById("nav-demandes");
    
    if (userRole === "formateur") {
        if(navCreer) navCreer.style.display = "none";
        if(navDemandes) navDemandes.querySelector("span").innerText = "Offres d'aide";
    } else {
        if(navCreer) navCreer.style.display = "flex";
        if(navDemandes) navDemandes.querySelector("span").innerText = "Demandes d'aide";
    }
    });
    </script>
</body>

</html>