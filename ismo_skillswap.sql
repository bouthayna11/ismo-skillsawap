-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jun 20, 2026 at 03:01 PM
-- Server version: 8.0.44
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ismo_skillswap`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrateur`
--

CREATE TABLE `administrateur` (
  `id_utilisateur` int NOT NULL,
  `cle_securite` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `administrateur`
--

INSERT INTO `administrateur` (`id_utilisateur`, `cle_securite`) VALUES
(13, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id_badge` int NOT NULL,
  `nom_badge` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description_badge` text COLLATE utf8mb4_general_ci,
  `image_badge` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'default_badge.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `badges`
--

INSERT INTO `badges` (`id_badge`, `nom_badge`, `description_badge`, `image_badge`) VALUES
(1, 'Développeur Junior', 'Premiers pas dans le monde du code.', 'fa-solid fa-seedling'),
(2, 'Expert React', 'Maîtrise avancée des composants et hooks.', 'fa-brands fa-react'),
(3, 'Maître du SQL', 'Expert en requêtes complexes et base de données.', 'fa-solid fa-database'),
(4, 'Codeur PHP', 'Spécialiste du développement backend.', 'fa-brands fa-php'),
(5, 'Top Mentor', 'Reconnu pour la qualité de son aide.', 'fa-solid fa-trophy'),
(6, 'Bug Hunter', 'Exceptionnel pour résoudre les problèmes techniques.', 'fa-solid fa-bug');

-- --------------------------------------------------------

--
-- Table structure for table `competence`
--

CREATE TABLE `competence` (
  `id_comp` int NOT NULL,
  `nom_comp` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `categorie` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `competence`
--

INSERT INTO `competence` (`id_comp`, `nom_comp`, `categorie`) VALUES
(1, 'HTML & CSS', 'Digital Development'),
(2, 'JavaScript (Vanilla)', 'Digital Development'),
(3, 'jQuery & AJAX', 'Digital Development'),
(4, 'PHP & MySQL', 'Digital Development'),
(5, 'Conception Bases de Données (SQL)', 'Digital Development'),
(6, 'Bootstrap & Tailwind CSS', 'Digital Development'),
(7, 'Gestion de versions avec Git', 'Digital Development'),
(8, 'Programmation Orientée Objet (POO)', 'Digital Development');

-- --------------------------------------------------------

--
-- Table structure for table `demande_aide`
--

CREATE TABLE `demande_aide` (
  `id_demande` int NOT NULL,
  `titre` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `technologie` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `niveau` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `filiere` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_publication` date DEFAULT NULL,
  `statut` enum('ouverte','en_cours','resolue','fermee') COLLATE utf8mb4_general_ci DEFAULT 'ouverte',
  `id_stagiaire` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `demande_aide`
--

INSERT INTO `demande_aide` (`id_demande`, `titre`, `technologie`, `description`, `niveau`, `filiere`, `date_publication`, `statut`, `id_stagiaire`) VALUES
(6, 'problème on langue de français', 'React', 'I can\'t read and I can\'t understand', 'Débutant', 'Infrastructure Digitale', NULL, 'ouverte', NULL),
(7, 'probleme en python', 'MySQL', 'I can hjbrfdsjkncx', 'Débutant', 'Infrastructure Digitale', NULL, 'ouverte', NULL),
(9, 'aide en php', 'PHP', 'j\'ai besoin quelqu\'un pour reviser avec moi', 'Débutant', 'Digital Development', NULL, 'ouverte', 9),
(11, 'eawenoni', 'PHP', 'ejkfvbsxc,', 'Intermédiaire', 'Digital Development', NULL, 'ouverte', 12),
(12, 'wqdejfkbrhsvd', 'CSS', 'sakjncz,mx', 'Débutant', 'Digital Development', NULL, 'ouverte', 12);

-- --------------------------------------------------------

--
-- Table structure for table `demande_mentorat`
--

CREATE TABLE `demande_mentorat` (
  `id_demande_m` int NOT NULL,
  `id_stagiaire` int NOT NULL,
  `statut` enum('en_attente','valide','refuse') NOT NULL DEFAULT 'en_attente',
  `date_demande` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `message_demande` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `demande_mentorat`
--

INSERT INTO `demande_mentorat` (`id_demande_m`, `id_stagiaire`, `statut`, `date_demande`, `message_demande`) VALUES
(1, 4, 'refuse', '2026-06-16 21:51:50', ''),
(2, 4, 'valide', '2026-06-16 22:15:00', ''),
(3, 3, 'valide', '2026-06-16 22:20:55', ''),
(4, 3, 'refuse', '2026-06-17 10:09:08', 'l;jkhvngb'),
(5, 9, 'en_attente', '2026-06-18 15:36:04', ''),
(6, 12, 'en_attente', '2026-06-19 11:45:35', 'can you help me'),
(7, 12, 'en_attente', '2026-06-19 13:47:32', 'uikhlk.');

-- --------------------------------------------------------

--
-- Table structure for table `entraide`
--

CREATE TABLE `entraide` (
  `id_entraide` int NOT NULL,
  `id_demande` int NOT NULL,
  `id_mentor` int NOT NULL,
  `date_aide` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `statut_aide` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'terminee'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `formateur`
--

CREATE TABLE `formateur` (
  `id_utilisateur` int NOT NULL,
  `matricule_rh` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `formateur`
--

INSERT INTO `formateur` (`id_utilisateur`, `matricule_rh`) VALUES
(5, '12345678'),
(11, '321');

-- --------------------------------------------------------

--
-- Table structure for table `maitriser`
--

CREATE TABLE `maitriser` (
  `id_utilisateur` int NOT NULL,
  `id_comp` int NOT NULL,
  `niveau_estime` enum('debutant','intermediaire','avance','expert') COLLATE utf8mb4_general_ci DEFAULT 'debutant'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maitriser`
--

INSERT INTO `maitriser` (`id_utilisateur`, `id_comp`, `niveau_estime`) VALUES
(3, 1, 'debutant'),
(4, 4, 'debutant'),
(4, 6, 'debutant'),
(4, 8, 'debutant'),
(9, 5, 'debutant'),
(12, 1, 'debutant'),
(12, 3, 'debutant');

-- --------------------------------------------------------

--
-- Table structure for table `notation`
--

CREATE TABLE `notation` (
  `id_note` int NOT NULL,
  `score` int DEFAULT NULL,
  `commentaires` text COLLATE utf8mb4_general_ci,
  `date_note` date DEFAULT NULL,
  `id_donneur` int DEFAULT NULL,
  `id_receveur` int DEFAULT NULL,
  `id_demande` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `obtenir`
--

CREATE TABLE `obtenir` (
  `id_utilisateur` int NOT NULL,
  `id_badge` int NOT NULL,
  `date_obtention` date DEFAULT NULL,
  `statut` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Non attribué'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `obtenir`
--

INSERT INTO `obtenir` (`id_utilisateur`, `id_badge`, `date_obtention`, `statut`) VALUES
(3, 4, '2026-06-19', 'Attribué'),
(6, 1, '2026-06-19', 'Attribué'),
(12, 5, '2026-06-19', 'Attribué');

-- --------------------------------------------------------

--
-- Table structure for table `passeport`
--

CREATE TABLE `passeport` (
  `id_passeport` int NOT NULL,
  `score_total` int DEFAULT '0',
  `classement` int DEFAULT NULL,
  `date_gen` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reponses_aide`
--

CREATE TABLE `reponses_aide` (
  `id_reponse` int NOT NULL,
  `id_demande` int NOT NULL,
  `id_aidant` int NOT NULL,
  `message` text,
  `statut` enum('en_attente','accepte','refuse') DEFAULT 'en_attente',
  `date_reponse` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `reponses_aide`
--

INSERT INTO `reponses_aide` (`id_reponse`, `id_demande`, `id_aidant`, `message`, `statut`, `date_reponse`) VALUES
(6, 9, 12, 'okkk', 'accepte', '2026-06-19 11:57:31');

-- --------------------------------------------------------

--
-- Table structure for table `stagiaire`
--

CREATE TABLE `stagiaire` (
  `id_utilisateur` int NOT NULL,
  `cef` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `filiere` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `score_global` int DEFAULT '0',
  `disponibilite` tinyint(1) DEFAULT '1',
  `id_passeport` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stagiaire`
--

INSERT INTO `stagiaire` (`id_utilisateur`, `cef`, `filiere`, `score_global`, `disponibilite`, `id_passeport`) VALUES
(3, '123456', NULL, 3600, 1, NULL),
(4, '11234567', 'Infrastructure Digitale', 0, 1, NULL),
(6, '12356', 'Gestion des Entreprises', 0, 1, NULL),
(9, '123', 'Digital Development', 9900, 1, NULL),
(12, '1234567890', 'Digital Development', 4200, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int NOT NULL,
  `nom_uti` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenom_uti` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_uti` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pwd_uti` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('stagiaire','formateur','admin') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT 'default.png',
  `is_verified` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom_uti`, `prenom_uti`, `email_uti`, `pwd_uti`, `role`, `photo`, `is_verified`) VALUES
(3, 'boutayna', 'elkhadir', 'boutaynaelkhadir@gmail.com', '$2y$10$CciyqcQXYbkL9gRo.rvnnOMhXG6nBl.FD1iPEoL0faWNujiOWZK4i', 'stagiaire', 'user_3.jpeg', 0),
(4, 'nada', 'elkhadir', 'nadaelkhadir@gmail.com', '$2y$10$DumaSuqZmzj5yHZQ/nDtB.Ad/N6pa.rGM7NOvr10lrrTYjpyv8LZy', 'stagiaire', 'user_4.jpeg', 0),
(5, 'for', 'mateur', 'formateur@gmail.com', '$2y$10$SussbkqNvRyCpUewf2BcnePn.IZohDnGjV1p8dVNf1buhqVDpnvOi', 'formateur', 'user_5.jpeg', 0),
(6, 'benhammmou', 'marwa', 'marwabenhammou@gmail.com', '$2y$10$H8t12G8TsjxzSYFXdXktQeo0oomvOMvDkpk5GjCfv7sYtjLDMoLNO', 'stagiaire', 'user_6.jpeg', 0),
(9, 'bt', 'kawthar', 'kawtharbt@gmail.com', '$2y$10$zhEUVA6nCjS/Iv4fB3aIf.ivjgfK7y9eMjAwCRufhyog2YwBagbK.', 'stagiaire', 'default.png', 0),
(11, 'KIKA', 'KIKA', 'KIKA1@gmail.com', '$2y$10$6.7/cfsjZAwX4Ol1O/bN/unVQvMklR25T0LkNkrTcd8cUXMstVg6y', 'formateur', 'default.png', 0),
(12, 'el mouden', 'lamyae', 'lamyaeelmouden@gmail.com', '$2y$10$D5ace7eFgyg1bSj7Zfcu2eI9Yyx0kWOtVKOsxyo/jcHrCbL9DDOie', 'stagiaire', 'user_12.jpeg', 0),
(13, 'Admin', 'boutyan', 'adminboutayna@gmail.com', '$2y$10$3ns7WH/V8SAry25AIg7ugOc8I0QjxTHfXmwaNQjlOQqhde2u7iUzS', 'admin', 'user_13.jpeg', 0);

-- --------------------------------------------------------

--
-- Table structure for table `validation`
--

CREATE TABLE `validation` (
  `id_validation` int NOT NULL,
  `date_validation` date DEFAULT NULL,
  `etat_validation` enum('en_attente','valide','refuse') COLLATE utf8mb4_general_ci DEFAULT 'en_attente',
  `id_comp` int DEFAULT NULL,
  `id_stagiaire` int DEFAULT NULL,
  `id_formateur` int DEFAULT NULL,
  `commentaire_refus` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `validation`
--

INSERT INTO `validation` (`id_validation`, `date_validation`, `etat_validation`, `id_comp`, `id_stagiaire`, `id_formateur`, `commentaire_refus`) VALUES
(1, '2026-06-16', 'valide', 1, 3, 5, NULL),
(4, '2026-06-16', 'valide', 4, 3, 5, NULL),
(5, '2026-06-16', 'valide', 1, 6, 5, NULL),
(6, '2026-06-16', 'valide', 7, 6, 5, NULL),
(7, '2026-06-16', 'valide', 5, 6, 5, NULL),
(15, '2026-06-16', 'valide', 4, 4, 5, NULL),
(16, '2026-06-16', 'valide', 6, 4, 5, NULL),
(18, '2026-06-16', 'valide', 8, 4, 5, NULL),
(20, '2026-06-17', 'valide', 1, 3, 5, NULL),
(23, '2026-06-18', 'valide', 1, 12, 5, NULL),
(26, '2026-06-19', 'valide', 3, 12, 5, NULL),
(30, '2026-06-19', 'valide', 5, 12, 5, NULL),
(34, '2026-06-19', 'valide', 2, 9, 5, NULL),
(36, '2026-06-19', 'valide', 2, 9, 5, NULL),
(37, '2026-06-19', 'valide', 6, 9, 5, NULL),
(38, '2026-06-19', 'refuse', 7, 9, 5, 'Compétence non maîtrisée'),
(39, '2026-06-19', 'valide', 1, 9, 5, NULL),
(40, '2026-06-19', 'refuse', 4, 9, 5, 'Veuillez refaire le projet'),
(41, '2026-06-19', 'valide', 7, 9, 5, NULL),
(42, '2026-06-19', 'valide', 2, 9, 5, NULL),
(43, '2026-06-19', 'valide', 2, 9, 5, NULL),
(44, '2026-06-19', 'valide', 5, 9, 5, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrateur`
--
ALTER TABLE `administrateur`
  ADD PRIMARY KEY (`id_utilisateur`);

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id_badge`);

--
-- Indexes for table `competence`
--
ALTER TABLE `competence`
  ADD PRIMARY KEY (`id_comp`);

--
-- Indexes for table `demande_aide`
--
ALTER TABLE `demande_aide`
  ADD PRIMARY KEY (`id_demande`),
  ADD KEY `id_stagiaire` (`id_stagiaire`);

--
-- Indexes for table `demande_mentorat`
--
ALTER TABLE `demande_mentorat`
  ADD PRIMARY KEY (`id_demande_m`),
  ADD KEY `id_stagiaire` (`id_stagiaire`);

--
-- Indexes for table `entraide`
--
ALTER TABLE `entraide`
  ADD PRIMARY KEY (`id_entraide`),
  ADD KEY `fk_entraide_demande` (`id_demande`),
  ADD KEY `fk_entraide_mentor` (`id_mentor`);

--
-- Indexes for table `formateur`
--
ALTER TABLE `formateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `matricule_rh` (`matricule_rh`);

--
-- Indexes for table `maitriser`
--
ALTER TABLE `maitriser`
  ADD PRIMARY KEY (`id_utilisateur`,`id_comp`),
  ADD KEY `id_comp` (`id_comp`);

--
-- Indexes for table `notation`
--
ALTER TABLE `notation`
  ADD PRIMARY KEY (`id_note`),
  ADD KEY `id_donneur` (`id_donneur`),
  ADD KEY `id_receveur` (`id_receveur`),
  ADD KEY `id_demande` (`id_demande`);

--
-- Indexes for table `obtenir`
--
ALTER TABLE `obtenir`
  ADD PRIMARY KEY (`id_utilisateur`,`id_badge`),
  ADD KEY `id_badge` (`id_badge`);

--
-- Indexes for table `passeport`
--
ALTER TABLE `passeport`
  ADD PRIMARY KEY (`id_passeport`);

--
-- Indexes for table `reponses_aide`
--
ALTER TABLE `reponses_aide`
  ADD PRIMARY KEY (`id_reponse`),
  ADD KEY `fk_demande` (`id_demande`),
  ADD KEY `fk_aidant` (`id_aidant`);

--
-- Indexes for table `stagiaire`
--
ALTER TABLE `stagiaire`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `cef` (`cef`),
  ADD KEY `id_passeport` (`id_passeport`);

--
-- Indexes for table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`),
  ADD UNIQUE KEY `email_uti` (`email_uti`);

--
-- Indexes for table `validation`
--
ALTER TABLE `validation`
  ADD PRIMARY KEY (`id_validation`),
  ADD KEY `id_comp` (`id_comp`),
  ADD KEY `id_stagiaire` (`id_stagiaire`),
  ADD KEY `id_formateur` (`id_formateur`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id_badge` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `competence`
--
ALTER TABLE `competence`
  MODIFY `id_comp` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `demande_aide`
--
ALTER TABLE `demande_aide`
  MODIFY `id_demande` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `demande_mentorat`
--
ALTER TABLE `demande_mentorat`
  MODIFY `id_demande_m` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `entraide`
--
ALTER TABLE `entraide`
  MODIFY `id_entraide` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notation`
--
ALTER TABLE `notation`
  MODIFY `id_note` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `passeport`
--
ALTER TABLE `passeport`
  MODIFY `id_passeport` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reponses_aide`
--
ALTER TABLE `reponses_aide`
  MODIFY `id_reponse` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `validation`
--
ALTER TABLE `validation`
  MODIFY `id_validation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `administrateur`
--
ALTER TABLE `administrateur`
  ADD CONSTRAINT `administrateur_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `demande_aide`
--
ALTER TABLE `demande_aide`
  ADD CONSTRAINT `demande_aide_ibfk_1` FOREIGN KEY (`id_stagiaire`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `demande_mentorat`
--
ALTER TABLE `demande_mentorat`
  ADD CONSTRAINT `demande_mentorat_ibfk_1` FOREIGN KEY (`id_stagiaire`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `entraide`
--
ALTER TABLE `entraide`
  ADD CONSTRAINT `fk_entraide_demande` FOREIGN KEY (`id_demande`) REFERENCES `demande_aide` (`id_demande`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_entraide_mentor` FOREIGN KEY (`id_mentor`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `formateur`
--
ALTER TABLE `formateur`
  ADD CONSTRAINT `formateur_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Constraints for table `maitriser`
--
ALTER TABLE `maitriser`
  ADD CONSTRAINT `maitriser_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `maitriser_ibfk_2` FOREIGN KEY (`id_comp`) REFERENCES `competence` (`id_comp`) ON DELETE CASCADE;

--
-- Constraints for table `notation`
--
ALTER TABLE `notation`
  ADD CONSTRAINT `notation_ibfk_1` FOREIGN KEY (`id_donneur`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `notation_ibfk_2` FOREIGN KEY (`id_receveur`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `notation_ibfk_3` FOREIGN KEY (`id_demande`) REFERENCES `demande_aide` (`id_demande`) ON DELETE CASCADE;

--
-- Constraints for table `obtenir`
--
ALTER TABLE `obtenir`
  ADD CONSTRAINT `obtenir_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `obtenir_ibfk_2` FOREIGN KEY (`id_badge`) REFERENCES `badges` (`id_badge`) ON DELETE CASCADE;

--
-- Constraints for table `reponses_aide`
--
ALTER TABLE `reponses_aide`
  ADD CONSTRAINT `fk_aidant` FOREIGN KEY (`id_aidant`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_demande` FOREIGN KEY (`id_demande`) REFERENCES `demande_aide` (`id_demande`) ON DELETE CASCADE;

--
-- Constraints for table `stagiaire`
--
ALTER TABLE `stagiaire`
  ADD CONSTRAINT `stagiaire_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `stagiaire_ibfk_2` FOREIGN KEY (`id_passeport`) REFERENCES `passeport` (`id_passeport`) ON DELETE SET NULL;

--
-- Constraints for table `validation`
--
ALTER TABLE `validation`
  ADD CONSTRAINT `validation_ibfk_1` FOREIGN KEY (`id_comp`) REFERENCES `competence` (`id_comp`) ON DELETE CASCADE,
  ADD CONSTRAINT `validation_ibfk_2` FOREIGN KEY (`id_stagiaire`) REFERENCES `stagiaire` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `validation_ibfk_3` FOREIGN KEY (`id_formateur`) REFERENCES `formateur` (`id_utilisateur`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
