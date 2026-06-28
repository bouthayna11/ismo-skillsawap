-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Jun 27, 2026 at 02:38 PM
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
(19, NULL);

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
(8, 'Programmation Orientée Objet (POO)', 'Digital Development'),
(11, 'Configuration Routeurs/Switchs', 'Infrastructure Digitale'),
(12, 'Administration Windows Server', 'Infrastructure Digitale'),
(13, 'Sécurité Réseaux', 'Infrastructure Digitale'),
(14, 'Virtualisation (VMware/Hyper-V)', 'Infrastructure Digitale'),
(15, 'Adobe Photoshop', 'Infographie'),
(16, 'Adobe Illustrator', 'Infographie'),
(17, 'UI/UX Design', 'Infographie'),
(18, 'Montage Vidéo (Premiere Pro)', 'Infographie'),
(19, 'Python pour IA', 'Intelligence Artificielle'),
(20, 'Machine Learning', 'Intelligence Artificielle'),
(21, 'Deep Learning', 'Intelligence Artificielle'),
(22, 'Analyse de données (Data Science)', 'Intelligence Artificielle'),
(23, 'Comptabilité Générale', 'Gestion des Entreprises'),
(24, 'Gestion de la paie', 'Gestion des Entreprises'),
(25, 'Marketing Digital', 'Gestion des Entreprises');

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
(14, 'Aide en React', 'React', 'aider moi en React', 'Débutant', 'Digital Development', NULL, 'ouverte', 14),
(15, 'POO', 'Programmation Orientée Objet (POO)', 'aide en POO', 'Débutant', 'Intelligence Artificielle', NULL, 'ouverte', 17),
(16, 'UI/UX', 'UI/UX Design', 'Aide moi en UI/UX', 'Intermédiaire', 'Infographie', NULL, 'ouverte', 18);

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
(8, 14, 'refuse', '2026-06-21 16:14:48', ''),
(9, 17, 'valide', '2026-06-21 16:59:03', ''),
(10, 18, 'valide', '2026-06-21 17:02:57', '');

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
(15, '1245'),
(21, '213456789');

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
(14, 1, 'debutant'),
(17, 1, 'debutant'),
(17, 21, 'debutant'),
(17, 22, 'debutant'),
(18, 15, 'debutant');

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
(14, 1, '2026-06-23', 'Attribué'),
(16, 1, '2026-06-23', 'Attribué'),
(17, 5, '2026-06-22', 'Attribué'),
(18, 1, '2026-06-21', 'Attribué');

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
(7, 15, 18, 'visite la page python.learning', 'en_attente', '2026-06-21 17:02:36');

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
(14, '11032008', 'Digital Development', 0, 1, NULL),
(16, '12345', 'Infrastructure Digitale', 0, 1, NULL),
(17, '1234', 'Intelligence Artificielle', 3900, 1, NULL),
(18, '098876', 'Infographie', 1500, 1, NULL);

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
(14, 'El khadir', 'Bouthayna', 'boutaynaelkhadir@gmail.com', '$2y$10$5zC9wtq43Td4NcMP0JXk7elyTOD85KLREDQzRJ2UBkVI8z0ta5w46', 'stagiaire', 'user_14.jpeg', 1),
(15, 'ISMO', 'formateur', 'formateur@gmail.com', '$2y$10$C0wc5UKRpoX2UmpB6lLfe.WZ3Z7j/tzw0Y49ZlSf/dqbcgjDUSIVy', 'formateur', 'user_15.jpeg', 1),
(16, 'BR', 'Kawtar', 'kawtarbr@gmail.com', '$2y$10$eRfna/tPqu98eQXsO7VfKOiknjock2zf/.FbuuYSTUC0ghv9/mTdm', 'stagiaire', 'default.png', 1),
(17, 'Elkhadir', 'nada', 'nadaelkhadir@gmail.com', '$2y$10$gGVcEWtu3wBqYI9TqdcSleieSbWSPohBN95ACx6fHD/xnVWguo.WG', 'stagiaire', 'user_17_1782076907.jpeg', 1),
(18, 'benhammou', 'marwa', 'marwa@gmail.com', '$2y$10$vx/ZKyabEl0bQootAeMow.19iP7U.EgHiuoKiRxDf2zwvObBkhBy2', 'stagiaire', 'user_18_1782064574.png', 1),
(19, 'admin', 'boutayna', 'boutaynaadmin@gmail.com', '$2y$10$.ARBAONc.Mnclse1wg4AEuPyXT0TTiPNjhzGHsqWak/mcI7ZhiK4G', 'admin', 'user_19.jpeg', 1),
(21, 'Br', 'kawtar', 'formateurkawtar@gmail.com', '$2y$10$f.9v7iAFzTyLlAyofSQHjOspx/jJekT/P3C4.qktfmOYyP637wBCO', 'formateur', 'default.png', 1);

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
(47, '2026-06-21', 'valide', 1, 14, 15, NULL),
(48, '2026-06-21', 'valide', 21, 17, NULL, NULL),
(49, '2026-06-21', 'valide', 22, 17, NULL, NULL),
(50, '2026-06-21', 'valide', 15, 18, NULL, NULL),
(51, '2026-06-22', 'valide', 1, 17, NULL, NULL);

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
  MODIFY `id_comp` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `demande_aide`
--
ALTER TABLE `demande_aide`
  MODIFY `id_demande` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `demande_mentorat`
--
ALTER TABLE `demande_mentorat`
  MODIFY `id_demande_m` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `id_reponse` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `validation`
--
ALTER TABLE `validation`
  MODIFY `id_validation` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

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
