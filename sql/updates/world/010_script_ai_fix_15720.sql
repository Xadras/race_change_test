/*

AI fix for Timbermaw Ancestor


*/

INSERT INTO `creature_ai_scripts` (`id`, `entryOrGUID`, `event_type`, `event_inverse_phase_mask`, `event_chance`, `event_flags`, `event_param1`, `event_param2`, `event_param3`, `event_param4`, `action1_type`, `action1_param1`, `action1_param2`, `action1_param3`, `action2_type`, `action2_param1`, `action2_param2`, `action2_param3`, `action3_type`, `action3_param1`, `action3_param2`, `action3_param3`, `comment`) VALUES ('6669911', '15720', '0', '0', '100', '3', '1000', '1500', '3500', '4500', '11', '20295', '1', '0', '23', '1', '0', '0', '0', '0', '0', '0', 'Cast Blitzschlag');
INSERT INTO `creature_ai_scripts` (`id`, `entryOrGUID`, `event_type`, `event_inverse_phase_mask`, `event_chance`, `event_flags`, `event_param1`, `event_param2`, `event_param3`, `event_param4`, `action1_type`, `action1_param1`, `action1_param2`, `action1_param3`, `action2_type`, `action2_param1`, `action2_param2`, `action2_param3`, `action3_type`, `action3_param1`, `action3_param2`, `action3_param3`, `comment`) VALUES ('6669912', '15720', '0', '0', '100', '3', '7000', '9000', '11000', '12000', '11', '26097', '0', '1', '23', '1', '0', '0', '0', '0', '0', '0', 'Cast Welle der Heilung');


UPDATE `creature_template` SET `AIName`='EventAI' WHERE (`entry`='15720');
