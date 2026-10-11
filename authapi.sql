
CREATE DATABASE IF NOT EXISTS `devlab_shop` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `devlab_shop`;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_account_activation`
--

DROP TABLE IF EXISTS `tbl_account_activation`;
CREATE TABLE IF NOT EXISTS `tbl_account_activation` (
  `activation_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `activation_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`activation_id`),
  UNIQUE KEY `activation_hash` (`activation_hash`),
  KEY `fk_activation_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


--
-- Table structure for table `tbl_auth_tokens`
--

DROP TABLE IF EXISTS `tbl_auth_tokens`;
CREATE TABLE IF NOT EXISTS `tbl_auth_tokens` (
  `token_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `revoked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`token_id`),
  UNIQUE KEY `uq_token_hash` (`token_hash`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


--
-- Table structure for table `tbl_susers`
--

DROP TABLE IF EXISTS `tbl_susers`;
CREATE TABLE IF NOT EXISTS `tbl_susers` (
  `user_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `account_status` enum('pending','active','disabled') NOT NULL DEFAULT 'pending',
  `email_verified_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;


-- Constraints for table `tbl_account_activation`
--
ALTER TABLE `tbl_account_activation`
  ADD CONSTRAINT `fk_activation_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_susers` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_auth_tokens`
--
ALTER TABLE `tbl_auth_tokens`
  ADD CONSTRAINT `fk_auth_token_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_susers` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
