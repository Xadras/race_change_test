/*

Questfix for Uniting the Shattered Amulet

*/

UPDATE `spell_script_target` SET `targetEntry`='7665' WHERE (`entry`='12938') AND (`type`='1') AND (`targetEntry`='7664');
UPDATE `spell_script_target` SET `targetEntry`='7667' WHERE (`entry`='12938') AND (`type`='1') AND (`targetEntry`='7665');
INSERT INTO `spell_script_target` (`entry`, `type`, `targetEntry`) VALUES ('12928', '1', '7666');