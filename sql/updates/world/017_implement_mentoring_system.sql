CREATE TABLE `mentoring_program` (
`mentee`  int(10) UNSIGNED NOT NULL ,
`mentor`  int(10) UNSIGNED NULL ,
PRIMARY KEY (`mentee`)
)
;

CREATE TABLE `account_mentor` (
`acc_id`  int(10) UNSIGNED NOT NULL ,
PRIMARY KEY (`acc_id`)
)
;

ALTER TABLE `mentoring_program`
MODIFY COLUMN `mentor`  int(10) UNSIGNED NULL DEFAULT 0 AFTER `mentee`;