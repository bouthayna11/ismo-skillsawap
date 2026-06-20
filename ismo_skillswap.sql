SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(11) NOT NULL AUTO_INCREMENT,
  `nom_uti` varchar(50) DEFAULT NULL,
  `prenom_uti` varchar(50) DEFAULT NULL,
  `email_uti` varchar(100) DEFAULT NULL,
  `pwd_uti` varchar(255) DEFAULT NULL,
  `role` enum('stagiaire','formateur','admin') DEFAULT NULL,
  `photo` varchar(255) DEFAULT 'default.png',
  `is_verified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email_uti` (`email_uti`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `passeport` (
  `id_passeport` int(11) NOT NULL AUTO_INCREMENT,
  `score_total` int(11) DEFAULT 0,
  `classement` int(11) DEFAULT NULL,
  `date_gen` date DEFAULT NULL,
  PRIMARY KEY (`id_passeport`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `stagiaire` (
  `id_utilisateur` int(11) NOT NULL,
  `cef` varchar(50) DEFAULT NULL,
  `filiere` varchar(50) DEFAULT NULL,
  `score_global` int(11) DEFAULT 0,
  `disponibilite` tinyint(1) DEFAULT 1,
  `id_passeport` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `cef` (`cef`),
  KEY `id_passeport` (`id_passeport`),
  CONSTRAINT `stagiaire_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `stagiaire_ibfk_2` FOREIGN KEY (`id_passeport`) REFERENCES `passeport` (`id_passeport`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `formateur` (
  `id_utilisateur` int(11) NOT NULL,
  `matricule_rh` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `matricule_rh` (`matricule_rh`),
  CONSTRAINT `formateur_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `administrateur` (
  `id_utilisateur` int(11) NOT NULL,
  `cle_securite` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  CONSTRAINT `administrateur_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `competence` (
  `id_comp` int(11) NOT NULL AUTO_INCREMENT,
  `nom_comp` varchar(100) DEFAULT NULL,
  `categorie` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_comp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `demande_aide` (
  `id_demande` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_publication` date DEFAULT NULL,
  `statut` enum('ouverte','en_cours','resolue','fermee') DEFAULT 'ouverte',
  `id_stagiaire` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_demande`),
  KEY `id_stagiaire` (`id_stagiaire`),
  CONSTRAINT `demande_aide_ibfk_1` FOREIGN KEY (`id_stagiaire`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `entraide` (
  `id_entraide` int(11) NOT NULL AUTO_INCREMENT,
  `id_demande` int(11) NOT NULL, -- الربط مع الطلب الأصلي
  `id_mentor` int(11) NOT NULL,  -- الـ id ديال المتدرب اللي قدم المساعدة
  `date_aide` timestamp DEFAULT CURRENT_TIMESTAMP, -- فوقاش تمت المساعدة
  `statut_aide` varchar(30) DEFAULT 'terminee', -- حالة المساعدة (terminee, en_cours)
  PRIMARY KEY (`id_entraide`),
  CONSTRAINT `fk_entraide_demande` FOREIGN KEY (`id_demande`) REFERENCES `demande_aide` (`id_demande`) ON DELETE CASCADE,
  CONSTRAINT `fk_entraide_mentor` FOREIGN KEY (`id_mentor`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `proposer_aide` (
  `id_proposition` int(11) NOT NULL AUTO_INCREMENT,
  `message_aide` text DEFAULT NULL,
  `date_proposition` date DEFAULT NULL,
  `statut_proposition` enum('proposee','acceptee','refusee') DEFAULT 'proposee',
  `id_stagiaire` int(11) DEFAULT NULL,
  `id_demande` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_proposition`),
  KEY `id_stagiaire` (`id_stagiaire`),
  KEY `id_demande` (`id_demande`),
  CONSTRAINT `proposer_aide_ibfk_1` FOREIGN KEY (`id_stagiaire`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `proposer_aide_ibfk_2` FOREIGN KEY (`id_demande`) REFERENCES `demande_aide` (`id_demande`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `notation` (
  `id_note` int(11) NOT NULL AUTO_INCREMENT,
  `score` int(11) DEFAULT NULL,
  `date_note` date DEFAULT NULL,
  `id_donneur` int(11) DEFAULT NULL,
  `id_receveur` int(11) DEFAULT NULL,
  `id_demande` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_note`),
  KEY `id_donneur` (`id_donneur`),
  KEY `id_receveur` (`id_receveur`),
  KEY `id_demande` (`id_demande`),
  CONSTRAINT `notation_ibfk_1` FOREIGN KEY (`id_donneur`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `notation_ibfk_2` FOREIGN KEY (`id_receveur`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `notation_ibfk_3` FOREIGN KEY (`id_demande`) REFERENCES `demande_aide` (`id_demande`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `badges` (
  `id_badge` int(11) NOT NULL AUTO_INCREMENT,
  `nom_badge` varchar(50) DEFAULT NULL,
  `description_badge` text DEFAULT NULL,
  `image_badge` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_badge`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `maitriser` (
  `id_utilisateur` int(11) NOT NULL,
  `id_comp` int(11) NOT NULL,
  `niveau_estime` enum('debutant','intermediaire','avance','expert') DEFAULT 'debutant',
  PRIMARY KEY (`id_utilisateur`,`id_comp`),
  KEY `id_comp` (`id_comp`),
  CONSTRAINT `maitriser_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `maitriser_ibfk_2` FOREIGN KEY (`id_comp`) REFERENCES `competence` (`id_comp`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `obtenir` (
  `id_utilisateur` int(11) NOT NULL,
  `id_badge` int(11) NOT NULL,
  `date_obtention` date DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`,`id_badge`),
  KEY `id_badge` (`id_badge`),
  CONSTRAINT `obtenir_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `obtenir_ibfk_2` FOREIGN KEY (`id_badge`) REFERENCES `badges` (`id_badge`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `validation` (
  `id_validation` int(11) NOT NULL AUTO_INCREMENT,
  `date_validation` date DEFAULT NULL,
  `etat_validation` enum('en_attente','valide','refuse') DEFAULT 'en_attente',
  `id_comp` int(11) DEFAULT NULL,
  `id_stagiaire` int(11) DEFAULT NULL,
  `id_formateur` int(11) DEFAULT NULL,
  PRIMARY KEY (`id_validation`),
  KEY `id_comp` (`id_comp`),
  KEY `id_stagiaire` (`id_stagiaire`),
  KEY `id_formateur` (`id_formateur`),
  CONSTRAINT `validation_ibfk_1` FOREIGN KEY (`id_comp`) REFERENCES `competence` (`id_comp`) ON DELETE CASCADE,
  CONSTRAINT `validation_ibfk_2` FOREIGN KEY (`id_stagiaire`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  CONSTRAINT `validation_ibfk_3` FOREIGN KEY (`id_formateur`) REFERENCES `formateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;