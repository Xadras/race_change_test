/*
Fix the Quest The Summoning Chamber

Horde (10602)
Ally (10585)

*/

UPDATE `quest_template` SET `ReqSpellCast1`='37285' WHERE (`entry`='10585');
UPDATE `quest_template` SET `ReqSpellCast1`='37285' WHERE (`entry`='10602');
INSERT INTO `spell_script_target` (`entry`, `type`, `targetEntry`) VALUES ('37285', '1', '21735');