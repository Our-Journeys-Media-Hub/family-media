-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 04. Jan 2026 um 13:53
-- Server-Version: 8.0.37
-- PHP-Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `demo_auth`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `families`
--

CREATE TABLE `families` (
  `id` int NOT NULL,
  `family_name` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `families`
--

INSERT INTO `families` (`id`, `family_name`, `created_at`) VALUES
(1, 'E-Tech Group', '2025-12-17 19:42:35'),
(2, 'Bayram', '2025-12-17 19:42:43');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `family_invites`
--

CREATE TABLE `family_invites` (
  `id` int NOT NULL,
  `family_id` int NOT NULL,
  `created_by` int NOT NULL,
  `token` char(64) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `family_invites`
--

INSERT INTO `family_invites` (`id`, `family_id`, `created_by`, `token`, `email`, `expires_at`, `used_at`) VALUES
(1, 1, 1, '314d16c53eba9cbd184d2dbf8f15c8cb7f76a8b74008f93640f12479b98c178e', 'Karim@gmail.com', '2025-12-24 19:50:04', '2025-12-17 19:50:42'),
(2, 1, 1, '4d875410405f0693e13c9ea1f8aec33fff5859364a227b14e8d72960539cf9dc', 'Jules@gmail.com', '2025-12-24 19:50:11', NULL);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `family_memberships`
--

CREATE TABLE `family_memberships` (
  `user_id` int NOT NULL,
  `family_id` int NOT NULL,
  `family_role` enum('owner','admin','member') NOT NULL DEFAULT 'member',
  `relation_label` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `family_memberships`
--

INSERT INTO `family_memberships` (`user_id`, `family_id`, `family_role`, `relation_label`) VALUES
(1, 1, 'owner', 'Eyüphan'),
(1, 2, 'owner', 'Eyüphan'),
(2, 1, 'member', 'Karim');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `images`
--

CREATE TABLE `images` (
  `id` int NOT NULL,
  `family_id` int NOT NULL,
  `uploaded_by` int NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `visibility` enum('family','private','custom') DEFAULT 'family',
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `country_code` char(2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `images`
--

INSERT INTO `images` (`id`, `family_id`, `uploaded_by`, `title`, `file_path`, `visibility`, `uploaded_at`, `country_code`) VALUES
(1, 1, 1, 'E Tech ', 'images/LT/WhatsApp Image 2025-12-17 at 19.47.14.jpeg', 'family', '2025-12-17 19:48:14', 'LT'),
(2, 1, 1, 'E Tech ', 'images/LT/WhatsApp Video 2025-12-17 at 19.47.12.mp4', 'family', '2025-12-17 19:48:14', 'LT'),
(3, 1, 2, 'Private upload', 'images/LT/karim.jpeg', 'private', '2025-12-17 19:53:10', 'LT'),
(4, 1, 2, 'tech', 'images/LT/karim2.jpeg', 'family', '2025-12-17 19:53:25', 'LT'),
(6, 1, 2, '123', 'images/DE/karim.jpeg', 'family', '2025-12-18 16:27:24', 'DE'),
(9, 1, 1, 'vacation', 'images/FR/WhatsApp Image 2025-12-19 at 11.49.00.jpeg', 'family', '2025-12-19 12:43:47', 'FR');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `image_permissions`
--

CREATE TABLE `image_permissions` (
  `image_id` int NOT NULL,
  `user_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `image_shares`
--

CREATE TABLE `image_shares` (
  `image_id` int NOT NULL,
  `user_id` int NOT NULL,
  `can_view` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Daten für Tabelle `users`
--

INSERT INTO `users` (`id`, `email`, `display_name`, `password_hash`, `created_at`) VALUES
(1, 'brooo2002y@gmail.com', 'Eyüphan Bayram', '$2y$10$LIiZaOQyY2s45rkTHWyLtuUBElUC5mzERIwuPy3.vCdugBtu2l8HW', '2025-12-17 19:41:53'),
(2, 'Karim@gmail.com', 'Karim', '$2y$10$QI49WAljiWQyvoYc3GNhMuXD8/dfdKsieUzNBYAKXFVC3Y.whlx5C', '2025-12-17 19:49:11'),
(3, 'Jules@gmail.com', 'Jules', '$2y$10$BB0mZPSq9Uw7ORQK5jPOiuRhR8IZ8I4kp3zIkFJfb1NKzKmfsx2ym', '2025-12-17 19:49:47');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `families`
--
ALTER TABLE `families`
  ADD PRIMARY KEY (`id`);

--
-- Indizes für die Tabelle `family_invites`
--
ALTER TABLE `family_invites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_inv_token` (`token`),
  ADD KEY `fk_inv_family` (`family_id`),
  ADD KEY `fk_inv_creator` (`created_by`);

--
-- Indizes für die Tabelle `family_memberships`
--
ALTER TABLE `family_memberships`
  ADD PRIMARY KEY (`user_id`,`family_id`),
  ADD KEY `idx_fm_family` (`family_id`);

--
-- Indizes für die Tabelle `images`
--
ALTER TABLE `images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_img_family` (`family_id`),
  ADD KEY `fk_img_user` (`uploaded_by`);

--
-- Indizes für die Tabelle `image_permissions`
--
ALTER TABLE `image_permissions`
  ADD PRIMARY KEY (`image_id`,`user_id`),
  ADD KEY `fk_perm_user` (`user_id`);

--
-- Indizes für die Tabelle `image_shares`
--
ALTER TABLE `image_shares`
  ADD PRIMARY KEY (`image_id`,`user_id`),
  ADD KEY `fk_shares_user` (`user_id`);

--
-- Indizes für die Tabelle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_email` (`email`);

--
-- AUTO_INCREMENT für exportierte Tabellen
--

--
-- AUTO_INCREMENT für Tabelle `families`
--
ALTER TABLE `families`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `family_invites`
--
ALTER TABLE `family_invites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT für Tabelle `images`
--
ALTER TABLE `images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT für Tabelle `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints der exportierten Tabellen
--

--
-- Constraints der Tabelle `family_invites`
--
ALTER TABLE `family_invites`
  ADD CONSTRAINT `fk_inv_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `family_memberships`
--
ALTER TABLE `family_memberships`
  ADD CONSTRAINT `fk_fm_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `images`
--
ALTER TABLE `images`
  ADD CONSTRAINT `fk_img_family` FOREIGN KEY (`family_id`) REFERENCES `families` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_img_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `image_permissions`
--
ALTER TABLE `image_permissions`
  ADD CONSTRAINT `fk_perm_image` FOREIGN KEY (`image_id`) REFERENCES `images` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_perm_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints der Tabelle `image_shares`
--
ALTER TABLE `image_shares`
  ADD CONSTRAINT `fk_shares_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
