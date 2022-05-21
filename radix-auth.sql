CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_key CHAR(8) NOT NULL,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    password_reset VARCHAR(64) NULL,
    password_reset_expires_at DATETIME NULL,
    activation VARCHAR(64) NULL,
    role VARCHAR(10) NOT NULL,
    status TINYINT NOT NULL,
    visible CHAR(3) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id, user_key),
    UNIQUE KEY username (username),
    UNIQUE KEY email (email)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(40) NOT NULL PRIMARY KEY,
    expiry INT(10) unsigned NOT NULL,
    data TEXT NOT NULL
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS autologin (
    user_key CHAR(8) NOT NULL,
    token CHAR(32) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data TEXT,
    used TINYINT(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (user_key, token)
) ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS failed_logins (
    id int unique default null,
    login varchar(255) DEFAULT NULL,
    count tinyint(2) NOT NULL DEFAULT 0,
    last_time varchar(10) DEFAULT NULL,
    blocked tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO users (id, user_key, username, email, password, password_reset, password_reset_expires_at, activation, role, status, visible, created_at, updated_at)
VALUES (1, 'ee8894b0', 'admin', 'admin@akebrands.se', '$2y$10$o56cQLLffSvvi93l5Z6S2.nfs7izkUm1Brr.9ZaKCYKUhE0cBZi5S', NULL, NULL, '5a56a7bfcc90a6f22917d090520f35950625ac1328017a8a90bf341d79d183aa', 'admin', 1, 'off', '2022-03-16 17:30:30', NULL);

ALTER TABLE users AUTO_INCREMENT = 1;
