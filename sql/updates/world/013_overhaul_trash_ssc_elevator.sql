-- SSC: Link trash after first elevator to Hydross
INSERT INTO `creature_linked_respawn` (`guid`, `linkedGuid`) VALUES ('37951', '93846');
INSERT INTO `creature_linked_respawn` (`guid`, `linkedGuid`) VALUES ('37991', '93846');
INSERT INTO `creature_linked_respawn` (`guid`, `linkedGuid`) VALUES ('37987', '93846');
INSERT INTO `creature_linked_respawn` (`guid`, `linkedGuid`) VALUES ('37989', '93846');

-- Pathing for Underbog Colossus Entry: 21251
SET @NPC := 37951;
SET @PATH := @NPC * 10;
UPDATE `creature` SET `spawndist`=0,`MovementType`=2 WHERE `guid`=@NPC;
DELETE FROM `creature_addon` WHERE `guid`=@NPC;
INSERT INTO `creature_addon` (`guid`,`path_id`,`bytes2`,`mount`,`auras`) VALUES (@NPC,@PATH,1,0, '');
DELETE FROM `waypoint_data` WHERE `id`=@PATH;
INSERT INTO `waypoint_data` (`id`,`point`,`position_x`,`position_y`,`position_z`,`delay`,`move_type`,`action`,`action_chance`,`wpguid`) VALUES
(@PATH,1,-8.324710,-65.784798,-71.257500,0,0,0,100,0),
(@PATH,2,1.189690,-63.721802,-71.653297,0,0,0,100,0),
(@PATH,3,-47.674782,-64.738129,-69.313438,0,0,0,100,0);