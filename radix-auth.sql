CREATE TABLE IF NOT EXISTS users(
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_key CHAR(8) NOT NULL,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY username (username),
    UNIQUE KEY email (email)
) ENGINE=InnoDB;

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
