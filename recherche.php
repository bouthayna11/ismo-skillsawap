<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISMO SkillSwap - Recherche</title>
        <link rel="stylesheet" href="style.css">
<script src="theme.js"></script>
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

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            background: var(--main-bg);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            color: var(--text-main);
        }

        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            color: white;
            padding: 24px;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            transition: 0.3s;
        }

        .sidebar h1 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 32px;
        }

        .menu-title {
            font-size: 11px;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 20px 0 8px 8px;
            font-weight: 600;
        }

        .menu-item {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #cbd5e1;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .menu-item i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }

        .menu-item:hover,
        .menu-item.active {
            background: var(--primary);
            color: white;
        }

        .main {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: 100%;
            overflow-y: auto;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .search-box-top {
            background: white;
            padding: 10px 16px;
            border-radius: 10px;
            border: 1px solid var(--border);
            flex: 1;
            max-width: 400px;
            display: flex;
            align-items: center;
        }

        .search-box-top input {
            border: none;
            outline: none;
            margin-left: 12px;
            width: 100%;
            font-size: 14px;
        }

        .avatar-top {
            width: 38px;
            height: 38px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid white;
            box-shadow: 0 0 0 1px var(--border);
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            margin-top: 10px;
        }

        .search-container-box {
            background: white;
            padding: 28px;
            border-radius: 16px;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .search-input-main {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            font-family: inherit;
            transition: 0.2s;
        }

        .search-input-main:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .filters {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-group label {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
        }

        .filter-group select {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1px solid var(--border);
            width: 180px;
            font-size: 14px;
            outline: none;
            background: white;
            color: var(--text-main);
            font-family: inherit;
        }

        .type-buttons {
            display: flex;
            gap: 8px;
        }

        .type-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            background: #f1f5f9;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            transition: 0.2s;
        }

        .type-btn:hover {
            background: #e2e8f0;
        }

        .type-btn.active {
            background: var(--primary);
            color: white;
        }

        .result-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 10px;
        }

        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 24px;
        }

        .card {
            background: white;
            border-radius: 20px;
            border: 1px solid var(--border);
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
            justify-content: space-between;
        }

        .card-top {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #4f46e5;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }

        .user-info h4 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }

        .user-info p {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 2px;
        }

        .tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .tag.skill {
            background: #eef2ff;
            color: #4f46e5;
        }

        .tag.level {
            background: #f1f5f9;
            color: #475569;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
                padding: 20px 10px;
            }

            .sidebar h1,
            .sidebar span,
            .menu-title {
                display: none;
            }

            .menu-item {
                justify-content: center;
                padding: 12px;
            }

            .menu-item i {
                margin-right: 0;
            }
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                flex-direction: row;
                overflow-x: auto;
                padding: 10px;
            }

            .sidebar h1 {
                display: none;
            }

            .sidebar nav {
                display: flex;
                flex-direction: row;
            }

            .main {
                padding: 20px;
            }

            .filters {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-group select,
            .type-buttons {
                width: 100%;
            }

            .type-btn {
                flex: 1;
            }

            .cards-grid {
                grid-template-columns: 1fr;
            }
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
            <a href="demandes_aide.php" class="menu-item"><i class="far fa-question-circle"></i> <span>Demandes
                    d'aide</span></a>
            <a href="creer_demande.php" class="menu-item" id="nav-creer"><i class="far fa-plus-square"></i> <span>Créer
                    une demande</span></a>
            <a href="recherche.php" class="menu-item active"><i class="fas fa-search"></i> <span>Recherche</span></a>

            <p class="menu-title">COMPÉTENCES</p>
            <a href="competence.php" class="menu-item"><i class="fas fa-award"></i> <span>Compétences</span></a>
            <a href="Mentors.php" class="menu-item"><i class="fas fa-user-graduate"></i> <span>Mentors</span></a>
            <a href="passport.php" class="menu-item"><i class="fas fa-id-card"></i> <span>Passeport</span></a>

            <p class="menu-title">ADMIN</p>
            <a href="Admin.php" class="menu-item"><i class="fas fa-user-shield"></i> <span>Admin</span></a>
        </nav>
        <div style="margin-top: auto;">
            <a href="login.php" class="menu-item" onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span></a>
        </div>
    </aside>

    <main class="main">
        <header>
            <div class="search-box-top">
                <i class="fas fa-search" style="color: #94a3b8;"></i>
                <input type="text" placeholder="Rechercher...">
            </div>
            <div class="header-actions">
                <div class="avatar-top"><a href="profil.php" id="user-initials"
                        style="color:white; text-decoration:none;">JD</a></div>
            </div>
        </header>

        <h1 class="page-title">Recherche</h1>

        <div class="search-container-box">
            <input type="text" id="searchInput" class="search-input-main"
                placeholder="Rechercher par compétence ou nom...">

            <div class="filters">
                <div class="filter-group">
                    <label>Type</label>
                    <div class="type-buttons">
                        <button class="type-btn active" id="usersBtn">Utilisateurs</button>
                        <button class="type-btn" id="demandesBtn">Demandes</button>
                    </div>
                </div>

                <div class="filter-group">
                    <label>Niveau</label>
                    <select id="levelFilter" name="levelFilter">
                        <option value="Tous">Tous</option>
                        <option value="Débutant">Débutant</option>
                        <option value="Intermédiaire">Intermédiaire</option>
                        <option value="Avancé">Avancé</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Filière</label>
                    <select id="fieldFilter" name="fieldFilter">
                        <option value="Toutes">Toutes</option>
                        <option value="Développement Web">Développement Web</option>
                        <option value="Data Science">Data Science</option>
                        <option value="Design">Design</option>
                    </select>
                </div>
            </div>
        </div>

        <h2 class="result-title">
            <span id="resultCount">2</span> résultat(s)
        </h2>

        <div class="cards-grid" id="cardsContainer">
            <div class="card">
                <div class="card-top">
                    <div class="avatar">SM</div>
                    <div class="user-info">
                        <h4>Sophie Martin</h4>
                        <p>Développement Web</p>
                    </div>
                </div>
                <div class="tags">
                    <span class="tag skill">React</span>
                    <span class="tag skill">Node.js</span>
                </div>
                <div><span class="tag level">Avancé</span></div>
            </div>

            <div class="card">
                <div class="card-top">
                    <div class="avatar">AB</div>
                    <div class="user-info">
                        <h4>Ahmed Benali</h4>
                        <p>Data Science</p>
                    </div>
                </div>
                <div class="tags">
                    <span class="tag skill">Python</span>
                    <span class="tag skill">SQL</span>
                </div>
                <div><span class="tag level">Avancé</span></div>
            </div>
        </div>
    </main>

    <script>

        let typeActif = "utilisateurs";

        let utilisateurs = [

            {
                nom: "Sophie Martin",
                initials: "SM",
                filiere: "Développement Web",
                niveau: "Avancé",
                competences: ["React", "Node.js", "TypeScript"]
            },

            {
                nom: "Ahmed Benali",
                initials: "AB",
                filiere: "Data Science",
                niveau: "Avancé",
                competences: ["Python", "SQL", "Data Science"]
            }

        ];

        let demandes = [

            {
                titre: "Aide en React Hooks",
                competence: "React",
                niveau: "Intermédiaire",
                filiere: "Développement Web"
            },

            {
                titre: "Python Data Analysis",
                competence: "Python",
                niveau: "Avancé",
                filiere: "Data Science"
            }

        ];

        const niveaux = [
            "Débutant",
            "Intermédiaire",
            "Avancé"
        ];

        const filieres = [
            "Développement Web",
            "Data Science",
            "Design"
        ];

        const cardsContainer = document.getElementById("cardsContainer");
        const resultCount = document.getElementById("resultCount");
        const searchInput = document.getElementById("searchInput");
        const levelFilter = document.getElementById("levelFilter");
        const fieldFilter = document.getElementById("fieldFilter");
        const usersBtn = document.getElementById("usersBtn");
        const demandesBtn = document.getElementById("demandesBtn");

        niveaux.forEach(function (level) {

            levelFilter.innerHTML += `
    <option value="${level}">
        ${level}
    </option>
    `;

        });

        filieres.forEach(function (filiere) {

            fieldFilter.innerHTML += `
    <option value="${filiere}">
        ${filiere}
    </option>
    `;

        });

        function afficherUtilisateurs(liste) {

            cardsContainer.innerHTML = "";
            resultCount.textContent = liste.length;

            if (liste.length === 0) {

                cardsContainer.innerHTML = `
        <div class="empty-message">
            Aucun utilisateur trouvé.
        </div>
        `;

                return;
            }

            liste.forEach(function (user) {

                const card = document.createElement("div");
                card.classList.add("card");

                let competencesHTML = "";

                user.competences.forEach(function (skill) {

                    competencesHTML += `
            <span class="tag skill">
                ${skill}
            </span>
            `;

                });

                card.innerHTML = `

            <div class="card-top">

                <div class="avatar">
                    ${user.initials}
                </div>

                <div class="user-info">

                    <h2>${user.nom}</h2>

                    <p>${user.filiere}</p>

                </div>

            </div>

            <div class="tags">
                ${competencesHTML}
            </div>

            <span class="tag level">
                ${user.niveau}
            </span>

        `;

                cardsContainer.appendChild(card);

            });

        }

        function afficherDemandes(liste) {

            cardsContainer.innerHTML = "";
            resultCount.textContent = liste.length;

            if (liste.length === 0) {

                cardsContainer.innerHTML = `
        <div class="empty-message">
            Aucune demande trouvée.
        </div>
        `;

                return;
            }

            liste.forEach(function (demande) {

                const card = document.createElement("div");
                card.classList.add("card");

                card.innerHTML = `

            <h2 class="demande-title">
                ${demande.titre}
            </h2>

            <div class="tags">

                <span class="tag skill">
                    ${demande.competence}
                </span>

                <span class="tag level">
                    ${demande.niveau}
                </span>

            </div>

            <p class="demande-field">
                ${demande.filiere}
            </p>

        `;

                cardsContainer.appendChild(card);

            });

        }

        function filtrer() {

            const recherche = searchInput.value.toLowerCase();
            const niveau = levelFilter.value;
            const filiere = fieldFilter.value;

            if (typeActif === "utilisateurs") {

                const resultat = utilisateurs.filter(function (user) {

                    const rechercheCorrecte =
                        user.nom.toLowerCase().includes(recherche)
                        ||
                        user.competences.join(" ").toLowerCase().includes(recherche);

                    const niveauCorrect =
                        niveau === "Tous"
                        ||
                        user.niveau === niveau;

                    const filiereCorrect =
                        filiere === "Toutes"
                        ||
                        user.filiere === filiere;

                    return rechercheCorrecte && niveauCorrect && filiereCorrect;

                });

                afficherUtilisateurs(resultat);

            } else {

                const resultat = demandes.filter(function (demande) {

                    const rechercheCorrecte =
                        demande.titre.toLowerCase().includes(recherche)
                        ||
                        demande.competence.toLowerCase().includes(recherche);

                    const niveauCorrect =
                        niveau === "Tous"
                        ||
                        demande.niveau === niveau;

                    const filiereCorrect =
                        filiere === "Toutes"
                        ||
                        demande.filiere === filiere;

                    return rechercheCorrecte && niveauCorrect && filiereCorrect;

                });

                afficherDemandes(resultat);

            }

        }

        usersBtn.addEventListener("click", function () {

            typeActif = "utilisateurs";

            usersBtn.classList.add("active");
            demandesBtn.classList.remove("active");

            filtrer();

        });

        demandesBtn.addEventListener("click", function () {

            typeActif = "demandes";

            demandesBtn.classList.add("active");
            usersBtn.classList.remove("active");

            filtrer();

        });

        searchInput.addEventListener("keyup", filtrer);
        levelFilter.addEventListener("change", filtrer);
        fieldFilter.addEventListener("change", filtrer);

        afficherUtilisateurs(utilisateurs);
        document.addEventListener("DOMContentLoaded", function () {
    const userRole = localStorage.getItem("userRole") || "stagiaire";
    
    // 1. التحكم في الـ Sidebar (إخفاء خانة إنشاء طلب للفورماتور)
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