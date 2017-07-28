DELETE FROM `looking4group_string` WHERE `entry` BETWEEN 11016 AND 11019;
INSERT INTO `looking4group_string` (`entry`,`content_default`,`content_loc1`,`content_loc2`,`content_loc3`,`content_loc4`,`content_loc5`,`content_loc6`,`content_loc7`,`content_loc8`) VALUES
(11016,'%s is already in a group!',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(11017,'%s joined %s''s group.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(11018,'%s is not in a group!',NULL, NULL,NULL,NULL,NULL,NULL,NULL,NULL),
(11019,'Group is full!',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL);

DELETE FROM `command` WHERE `name`='group join';
INSERT INTO `command` (`name`,`permission_mask`,`help`) VALUES
('group join',2048,'Syntax: .group join $AnyCharacterNameFromGroup [$CharacterName] \r\nAdds to group of player $AnyCharacterNameFromGroup player $CharacterName (or selected).');