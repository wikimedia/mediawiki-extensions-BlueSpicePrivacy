CREATE TABLE IF NOT EXISTS /*$wgDBprefix*/bs_privacy_request (
  pr_id INT(6) NOT NULL PRIMARY KEY AUTO_INCREMENT,
	pr_user INT(6) NOT NULL,
	pr_module VARCHAR( 100 ) NOT NULL,
	pr_timestamp VARCHAR (15) NOT NULL,
	pr_comment VARCHAR(255) NULL,
	pr_admin_comment VARCHAR( 255 ) NULL DEFAULT '',
	pr_status INT(1) NOT NULL DEFAULT 1,
	pr_open INT(1) NOT NULL DEFAULT 1,
	pr_data BLOB NULL
) /*$wgDBTableOptions*/;
