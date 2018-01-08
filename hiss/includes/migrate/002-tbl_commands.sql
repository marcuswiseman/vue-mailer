CREATE TABLE IF NOT EXISTS `tbl_commands` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`client` VARCHAR(255) NOT NULL,
	`command` VARCHAR(255) NOT NULL,
	`del` INT(11) NOT NULL DEFAULT '0',
	`date_run` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB
AUTO_INCREMENT=2
;
