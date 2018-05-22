CREATE TABLE IF NOT EXISTS `config` (
  `setting` varchar(100) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`setting`),
  UNIQUE KEY `setting` (`setting`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `config` (`setting`, `value`) VALUES
('table_fleet_progress', 'fleet_progress'),
('table_fleet_test_progress', 'fleet_test_progress'),
('table_fleet_dvsa_sections', 'fleet_sections'),
('table_fleet_questions', 'fleet_questions');

CREATE TABLE IF NOT EXISTS `fleet_progress` (
  `user_id` int(11) UNSIGNED NOT NULL,
  `progress` longtext NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `fleet_questions` (
  `prim` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `dsacat` tinyint(3) UNSIGNED NOT NULL,
  `dsaqposition` tinyint(3) UNSIGNED DEFAULT NULL,
  `question` varchar(255) DEFAULT NULL,
  `option1` varchar(255) DEFAULT NULL,
  `option2` varchar(255) DEFAULT NULL,
  `option3` varchar(255) DEFAULT NULL,
  `option4` varchar(255) DEFAULT NULL,
  `answer1` varchar(1) DEFAULT NULL,
  `answer2` varchar(1) DEFAULT NULL,
  `answer3` varchar(1) DEFAULT NULL,
  `answer4` varchar(1) DEFAULT NULL,
  `answerletters` varchar(5) DEFAULT NULL,
  `mark` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `tickamount` tinyint(1) UNSIGNED NOT NULL DEFAULT '4',
  `format` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `dsaimageid` int(11) UNSIGNED NOT NULL DEFAULT '0',
  `mockno` tinyint(3) UNSIGNED DEFAULT '0',
  `mockposition` tinyint(3) UNSIGNED DEFAULT '0',
  PRIMARY KEY (`prim`),
  UNIQUE KEY `prim` (`prim`),
  KEY `dsacat` (`dsacat`),
  KEY `mockno` (`mockno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `fleet_sections` (
  `section` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `free` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`section`),
  UNIQUE KEY `dsacat` (`section`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `fleet_test_progress` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(11) UNSIGNED NOT NULL,
  `questions` text NOT NULL,
  `answers` text NOT NULL,
  `results` text,
  `test_id` tinyint(3) UNSIGNED NOT NULL,
  `question_no` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `started` datetime NOT NULL,
  `complete` datetime DEFAULT NULL,
  `time_remaining` varchar(10) DEFAULT NULL,
  `time_taken` varchar(10) DEFAULT NULL,
  `totalscore` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `type` varchar(10) DEFAULT 'fleet',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `test_id` (`test_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `users`
ADD `first_name` VARCHAR(50) NOT NULL AFTER `id`,
ADD `last_name` VARCHAR(50) NOT NULL AFTER `first_name`,
ADD `settings` TEXT NULL DEFAULT NULL AFTER `isactive`;

--
-- Constraints
--

ALTER TABLE `fleet_progress`
  ADD CONSTRAINT `fleet_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `fleet_questions`
  ADD CONSTRAINT `fleet_questions_ibfk_1` FOREIGN KEY (`dsacat`) REFERENCES `fleet_sections` (`section`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `fleet_test_progress`
  ADD CONSTRAINT `fleet_test_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fleet_test_progress_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `fleet_questions` (`mockno`) ON DELETE NO ACTION ON UPDATE NO ACTION;