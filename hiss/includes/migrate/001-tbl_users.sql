CREATE TABLE `tbl_users` (
	`user_id` INT(11) NOT NULL AUTO_INCREMENT,
	`username` VARCHAR(26) NOT NULL,
	`email` VARCHAR(120) NOT NULL,
	`password` TEXT NOT NULL,
	`firstname` VARCHAR(40) NOT NULL,
	`lastname` VARCHAR(40) NOT NULL,
	`date_joined` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`token` TEXT NOT NULL,
	`first_login` INT(1) NOT NULL,
	`access` INT(11) NULL DEFAULT '0',
	PRIMARY KEY (`user_id`)
)
COLLATE='latin1_swedish_ci'
ENGINE=InnoDB
AUTO_INCREMENT=2
;
