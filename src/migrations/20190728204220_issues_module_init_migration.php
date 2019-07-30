<?php

use Phinx\Migration\AbstractMigration;

class IssuesModuleInitMigration extends AbstractMigration
{
    public function up()
    {
        $sql = <<<SQL
SET NAMES utf8mb4;
SET time_zone = '+00:00';


CREATE TABLE IF NOT EXISTS `magazines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier` (`identifier`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `issues` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `issued_at` datetime NOT NULL,
  `magazine_id` int(11) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT '1',
  `cover` varchar(255) DEFAULT NULL,
  `state` varchar(255) NOT NULL DEFAULT 'new',
  `name` varchar(255) NOT NULL,
  `error_message` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  `checksum` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier` (`identifier`),
  KEY `issued_at` (`issued_at`),
  KEY `magazine_id` (`magazine_id`),
  CONSTRAINT `issues_ibfk_1` FOREIGN KEY (`magazine_id`) REFERENCES `magazines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `issue_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `page` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `size` int(11) unsigned NOT NULL DEFAULT '0',
  `mime` varchar(255) NOT NULL,
  `quality` enum('small','large') NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `orientation` enum('portrait','landscape') NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `issue_id` (`issue_id`,`page`,`quality`),
  UNIQUE KEY `identifier` (`identifier`),
  CONSTRAINT `issue_pages_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `issue_source_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `identifier` varchar(255) NOT NULL,
  `issue_id` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `size` int(11) unsigned NOT NULL DEFAULT '0',
  `mime` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `identifier` (`identifier`),
  KEY `issue_id` (`issue_id`),
  CONSTRAINT `issue_source_files_ibfk_1` FOREIGN KEY (`issue_id`) REFERENCES `issues` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `subscription_type_magazines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_type_id` int(11) NOT NULL,
  `magazine_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_type_id` (`subscription_type_id`,`magazine_id`),
  KEY `magazine_id` (`magazine_id`),
  CONSTRAINT `subscription_type_magazines_ibfk_1` FOREIGN KEY (`subscription_type_id`) REFERENCES `subscription_types` (`id`),
  CONSTRAINT `subscription_type_magazines_ibfk_2` FOREIGN KEY (`magazine_id`) REFERENCES `magazines` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

        $this->execute($sql);
    }

    public function down()
    {
        // TODO: [refactoring] add down migrations for module init migrations (needs confirmation dialog)
        $this->output->writeln('Down migration is not available.');
    }
}
