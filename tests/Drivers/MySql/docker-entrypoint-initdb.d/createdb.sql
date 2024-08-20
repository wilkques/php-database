-- Additional users
CREATE USER IF NOT EXISTS 'user'@'%' IDENTIFIED WITH mysql_native_password BY 'root';

CREATE DATABASE IF NOT EXISTS `test` COLLATE 'utf8_general_ci';
GRANT ALL ON `test`.* TO 'user'@'%';

CREATE DATABASE IF NOT EXISTS `try` COLLATE 'utf8mb4_unicode_ci';
GRANT ALL ON `try`.* TO 'user'@'%';

FLUSH PRIVILEGES;
