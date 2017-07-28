TRUNCATE account_login;
ALTER TABLE `account_login`
MODIFY COLUMN `login_date`  timestamp NOT NULL AFTER `account_id`;