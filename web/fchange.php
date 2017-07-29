<?php

// Arrays to hold string value of Race/Class/Combos and Items
$raceList = [
    "1" => "Human",
    "2" => "Orc",
    "3" => "Dwarf",
    "4" => "Night Elf",
    "5" => "Undead",
    "6" => "Tauren",
    "7" => "Gnome",
    "8" => "Troll",
    "10" => "Blood Elf",
    "11" => "Draenei"
];
$classList = [
    "1" => "Warrior",
    "2" => "Paladin",
    "3" => "Hunter",
    "4" => "Rogue",
    "5" => "Priest",
    "7" => "Shaman",
    "8" => "Mage",
    "9" => "Warlock",
    "11" => "Druid"
];
$classRaceList = [
    "Warrior" => "Human | Dwarf | Gnome | Night Elf | Draenei | Orc | Undead | Tauren | Troll",
    "Paladin" => "Human | Dwarf | Draenei | Blood Elf",
    "Hunter" => "Dwarf | Night Elf | Draenei | Orc | Tauren | Troll | Blood Elf",
    "Rogue" => "Human | Night Elf | Gnome | Dwarf | Orc | Undead | Troll | Blood Elf",
    "Priest" => "Human | Dwarf | Night Elf | Draenei | Undead | Troll | Blood Elf",
    "Shaman" => "Draenei | Orc | Tauren | Troll",
    "Mage" => "Human | Gnome | Draenei | Undead | Troll | Blood Elf",
    "Warlock" => "Human | Gnome | Undead | Blood Elf | Orc",
    "Druid" => "Night Elf | Tauren"
];


// Data from Front End Page
$requestType = $_SERVER['REQUEST_METHOD'];

// Get data from post request
$wowUser = $_POST['user'];
$wowPass = $_POST['pass'];
$wowCharacter = $_POST['charname'];
$wowDestinationRace = $_POST['destrace'];

// Some vars
$authenticated = false;
$validateTransfer = false;
$isAllianceDest = true;

switch($wowDestinationRace) {
        case "Orc":
	case "Blood Elf":
	case "Tauren":
	case "Troll":
	case "Undead":
		$isAllianceDest = false;
		break;
}



// Create connection to Realm and World DB
$dbUser = '*****';
$dbPass = '*****';
$dbRealm = new PDO('mysql:host=127.0.0.1;port=3306;dbname=wow_realm;charset=utf8mb4', $dbUser, $dbPass);
$dbChar = new PDO('mysql:host=127.0.0.1;port=3306;dbname=test_char;charset=utf8mb4', $dbUser, $dbPass);

// Prepare query to authenticate user
$sth = $dbRealm->prepare("select * from account where username = :wowUser");
$sth->bindParam(':wowUser', $wowUser);
$sth->execute();

// Get results to authenticate user
$result = $sth->fetchAll();
$accountGUID = $result[0]['account_id'];
$accountName = $result[0]['username'];
$passHash = $result[0]['pass_hash'];

// Check if passwords match!
if (strtoupper($passHash) == strtoupper(sha1(strtoupper($wowUser) . ":" . strtoupper($wowPass)))) {
    $authenticated = true;
}
else {
    echo "Password does not match";
    exit(1);
}




// Lets see if the character belongs to user and fetch character data!
$sth = $dbChar->prepare("SELECT * from characters where account = :accountGUID");
$sth->bindParam(':accountGUID', $accountGUID);
$sth->execute();
$result = $sth->fetchAll();

// Char name and Username Matches!
for ($i = 0; $i < count($result); $i++) {
    if (strtoupper($result[$i]['name']) === strtoupper($wowCharacter)) {
        // Save GUID, Race and Class of Char
        $charGUID = $result[$i]['guid'];
        $originalRace = $result[$i]['race'];
        $charClass = $result[$i]['class'];
        $charOnline = $result[$i]['online'];
        $charName = $result[$i]['name'];
        $charLevel = $result[$i]['level'];
        $sth = null;
        $i = count($result) + 10;
    }
    else {
        if ($i == count($result)) {
            echo "Error : No character exist, or does not belong to your account";
            exit(1);
        }
    }
}


// Check if Destination Race / Class combination is valid
if (strpos($classRaceList[$classList[$charClass]], $wowDestinationRace) !== false) {
    $validateTransfer = true;
}
else {
    echo "Error : Destination Character Race and Class Combo does not Exist!";
    exit(1);
}




// We have now authenticated user
// Made sure character belongs to him
// And that destination race supports char class
// Now we have valiated the transfer we can go ahead with the faction change

// Begin a transaction turn off auto commit
$dbChar->beginTransaction();

// Lets start the transfer process
// Deal with Racials, and items

    // Check if character is online
    if ($charOnline) {
        echo "Error : Character is currently online, please log out.";
        exit(1);
    }

    // Check if character has any auctions
    $query = "SELECT * FROM auctionhouse WHERE item_owner = :charGUID";
    $sth = $dbChar->prepare($query);
    $sth->bindParam(':charGUID', $charGUID);
    $sth->execute();
    $result = $sth->fetchAll();
    // If any auctions, quit
    if ($result != null) {
        echo "Error : Character has active auctions, please cancel them.";
        exit(1);
    } 

    // Check if character has any mail in mailbox
    $query = "SELECT * FROM mail WHERE receiver = :charGUID";
    $sth = $dbChar->prepare($query);
    $sth->bindParam(':charGUID', $charGUID);
    $sth->execute();
    $result = $sth->fetchAll();
    // If any mail, quit
    if ($result != null) {
        echo "Error : Character has mail, please retreive it.";
        exit(1);
    }
 
    // Change the characters race
    $query = "UPDATE `characters` SET `changeRaceTo` = :destinationRace WHERE `guid` = :charGUID";
    $sth = $dbChar->prepare($query);
    $sth->bindParam(':charGUID', $charGUID);
    $raceID = array_search($wowDestinationRace, $raceList);
    $sth->bindParam(':destinationRace', $raceID);
    $sth->execute();

    // Move the character to Shattrath
    $query = "UPDATE `characters` SET `position_x` = '-1838', `position_y` = '5301', `position_z` = '-12', `map` = '530' where `guid` = :charGUID";
    $sth = $dbChar->prepare($query);
    $sth->bindParam(':charGUID', $charGUID);
    $sth->execute();

    // Replace quests
	if ($isAllianceDest){
		$query =   
		   "UPDATE `character_queststatus` SET `quest`='10937' WHERE `quest` = '10876' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10084' WHERE `quest` = '10092' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10288' WHERE `quest` = '10120' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10138' WHERE `quest` = '10156' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10482' WHERE `quest` = '10450' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10395' WHERE `quest` = '10393' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10079' WHERE `quest` = '10088' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9833' WHERE `quest` = '9447' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10115' WHERE `quest` = '9441' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9835' WHERE `quest` = '9400' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10116' WHERE `quest` = '9442' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10443' WHERE `quest` = '10442' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10137' WHERE `quest` = '10155' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10483' WHERE `quest` = '10242' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10935' WHERE `quest` = '10838' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10139' WHERE `quest` = '10157' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10141' WHERE `quest` = '10121' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10122' WHERE `quest` = '10150' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9399' WHERE `quest` = '9387' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9355' WHERE `quest` = '10236' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10397' WHERE `quest` = '10392' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9423' WHERE `quest` = '9376' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9426' WHERE `quest` = '9366' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10144' WHERE `quest` = '10208' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9398' WHERE `quest` = '9340' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10130' WHERE `quest` = '10152' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10054' WHERE `quest` = '10060' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10119' WHERE `quest` = '9407' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9587' WHERE `quest` = '9588' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10058' WHERE `quest` = '10229' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9383' WHERE `quest` = '10278' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10131' WHERE `quest` = '10154' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9490' WHERE `quest` = '9466' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10400' WHERE `quest` = '10136' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10762' WHERE `quest` = '10756' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10764' WHERE `quest` = '10758' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10106' WHERE `quest` = '10110' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10909' WHERE `quest` = '10864' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10055' WHERE `quest` = '10086' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9420' WHERE `quest` = '10161' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10396' WHERE `quest` = '10391' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10484' WHERE `quest` = '10538' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10394' WHERE `quest` = '10390' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10126' WHERE `quest` = '10151' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10485' WHERE `quest` = '10809' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11002' WHERE `quest` = '11003' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9563' WHERE `quest` = '9483' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10149' WHERE `quest` = '10401' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10145' WHERE `quest` = '10127' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10163' WHERE `quest` = '10162' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10144' WHERE `quest` = '10125' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10148' WHERE `quest` = '10135' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10146' WHERE `quest` = '10129' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10147' WHERE `quest` = '10133' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10140' WHERE `quest` = '10289' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9545' WHERE `quest` = '9410' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10763' WHERE `quest` = '10757' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10382' WHERE `quest` = '10388' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10346' WHERE `quest` = '10347' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10057' WHERE `quest` = '10062' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10142' WHERE `quest` = '10123' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9385' WHERE `quest` = '9361' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10099' WHERE `quest` = '10100' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9558' WHERE `quest` = '10213' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10056' WHERE `quest` = '10158' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10053' WHERE `quest` = '10059' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10895' WHERE `quest` = '10792' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10630' WHERE `quest` = '10100' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10754' WHERE `quest` = '10755' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10078' WHERE `quest` = '10087' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9791' WHERE `quest` = '9770' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9830' WHERE `quest` = '9814' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9834' WHERE `quest` = '9845' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9905' WHERE `quest` = '9903' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9776' WHERE `quest` = '9775' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9833' WHERE `quest` = '9842' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10116' WHERE `quest` = '10117' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9902' WHERE `quest` = '9904' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9780' WHERE `quest` = '9773' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9790' WHERE `quest` = '9769' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9896' WHERE `quest` = '9898' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9793' WHERE `quest` = '9796' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10115' WHERE `quest` = '10118' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9839' WHERE `quest` = '9823' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9827' WHERE `quest` = '9828' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10104' WHERE `quest` = '10105' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9901' WHERE `quest` = '9899' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9792' WHERE `quest` = '9797' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9781' WHERE `quest` = '9774' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9787' WHERE `quest` = '9846' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9801' WHERE `quest` = '9847' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9835' WHERE `quest` = '9822' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9777' WHERE `quest` = '9820' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9782' WHERE `quest` = '9771' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9803' WHERE `quest` = '9816' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9848' WHERE `quest` = '9823' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9783' WHERE `quest` = '9772' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10355' WHERE `quest` = '9841' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9786' WHERE `quest` = '9787' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9996' WHERE `quest` = '9997' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9952' WHERE `quest` = '9953' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10033' WHERE `quest` = '10034' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9949' WHERE `quest` = '9950' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10051' WHERE `quest` = '10052' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10014' WHERE `quest` = '10015' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9984' WHERE `quest` = '9985' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10869' WHERE `quest` = '10868' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10028' WHERE `quest` = '10201' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10446' WHERE `quest` = '10447' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9930' WHERE `quest` = '9929' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9986' WHERE `quest` = '9987' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9941' WHERE `quest` = '9942' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9958' WHERE `quest` = '9959' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9969' WHERE `quest` = '9974' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10048' WHERE `quest` = '10049' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10012' WHERE `quest` = '10013' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10005' WHERE `quest` = '10006' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9920' WHERE `quest` = '9890' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9988' WHERE `quest` = '9989' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10863' WHERE `quest` = '10862' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11505' WHERE `quest` = '11506' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10016' WHERE `quest` = '10018' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9965' WHERE `quest` = '9966' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9963' WHERE `quest` = '9964' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10026' WHERE `quest` = '10027' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10195' WHERE `quest` = '10196' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10444' WHERE `quest` = '10448' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9917' WHERE `quest` = '9889' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9992' WHERE `quest` = '9993' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10022' WHERE `quest` = '10023' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9980' WHERE `quest` = '9981' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9943' WHERE `quest` = '9947' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10038' WHERE `quest` = '10039' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10035' WHERE `quest` = '10036' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10042' WHERE `quest` = '10043' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9998' WHERE `quest` = '10000' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9975' WHERE `quest` = '9976' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10002' WHERE `quest` = '10003' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10007' WHERE `quest` = '10008' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9994' WHERE `quest` = '9995' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9961' WHERE `quest` = '9960' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10040' WHERE `quest` = '10041' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10022' WHERE `quest` = '10791' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10806' WHERE `quest` = '10742' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10805' WHERE `quest` = '10724' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10801' WHERE `quest` = '10723' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10801' WHERE `quest` = '10785' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10800' WHERE `quest` = '10721' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10799' WHERE `quest` = '10720' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10799' WHERE `quest` = '10749' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10799' WHERE `quest` = '10715' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10798' WHERE `quest` = '10783' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10797' WHERE `quest` = '10714' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10795' WHERE `quest` = '10709' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11040' WHERE `quest` = '11036' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10457' WHERE `quest` = '10488' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10506' WHERE `quest` = '10488' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10927' WHERE `quest` = '10928' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10502' WHERE `quest` = '10503' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10504' WHERE `quest` = '10505' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10584' WHERE `quest` = '10851' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10511' WHERE `quest` = '10542' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10455' WHERE `quest` = '10486' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10456' WHERE `quest` = '10487' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10675' WHERE `quest` = '10867' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11043' WHERE `quest` = '11047' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10690' WHERE `quest` = '10489' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10657' WHERE `quest` = '10853' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10803' WHERE `quest` = '10786' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10674' WHERE `quest` = '10865' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9794' WHERE `quest` = '9795' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10674' WHERE `quest` = '10859' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10512' WHERE `quest` = '10545' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10796' WHERE `quest` = '10784' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10516' WHERE `quest` = '10846' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10517' WHERE `quest` = '10843' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10518' WHERE `quest` = '10845' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10620' WHERE `quest` = '10617' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10671' WHERE `quest` = '10618' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10510' WHERE `quest` = '10860' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10608' WHERE `quest` = '10718' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10594' WHERE `quest` = '10614' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10580' WHERE `quest` = '10524' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10581' WHERE `quest` = '10525' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10632' WHERE `quest` = '10526' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10555' WHERE `quest` = '10543' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10556' WHERE `quest` = '10544' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10557' WHERE `quest` = '10565' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10712' WHERE `quest` = '10566' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10609' WHERE `quest` = '10615' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10608' WHERE `quest` = '10860' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10594' WHERE `quest` = '10618' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9874' WHERE `quest` = '9863' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9873' WHERE `quest` = '9867' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9955' WHERE `quest` = '9946' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9878' WHERE `quest` = '9865' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9879' WHERE `quest` = '9868' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9869' WHERE `quest` = '9870' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10108' WHERE `quest` = '10107' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9922' WHERE `quest` = '9907' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9871' WHERE `quest` = '9872' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11042' WHERE `quest` = '11037' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9982' WHERE `quest` = '9983' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11502' WHERE `quest` = '11503' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9956' WHERE `quest` = '9948' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9938' WHERE `quest` = '9937' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9936' WHERE `quest` = '9935' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9940' WHERE `quest` = '9939' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9954' WHERE `quest` = '9945' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10076' WHERE `quest` = '10074' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10077' WHERE `quest` = '10075' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11044' WHERE `quest` = '11048' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10477' WHERE `quest` = '10478' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9933' WHERE `quest` = '9934' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10113' WHERE `quest` = '10114' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9921' WHERE `quest` = '9906' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9923' WHERE `quest` = '9910' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10476' WHERE `quest` = '10479' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11044' WHERE `quest` = '11048' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9924' WHERE `quest` = '9916' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9920' WHERE `quest` = '9891' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9878' WHERE `quest` = '9866' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9917' WHERE `quest` = '9888' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10562' WHERE `quest` = '10595' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10626' WHERE `quest` = '10627' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10569' WHERE `quest` = '10760' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10677' WHERE `quest` = '10672' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10773' WHERE `quest` = '10751' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10662' WHERE `quest` = '10663' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10585' WHERE `quest` = '10602' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10572' WHERE `quest` = '10597' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10680' WHERE `quest` = '10681' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10606' WHERE `quest` = '10611' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10573' WHERE `quest` = '10599' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10582' WHERE `quest` = '10600' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10642' WHERE `quest` = '10624' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10759' WHERE `quest` = '10761' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11497' WHERE `quest` = '11498' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10643' WHERE `quest` = '10625' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10648' WHERE `quest` = '10647' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10645' WHERE `quest` = '10639' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10766' WHERE `quest` = '10767' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10564' WHERE `quest` = '10598' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10583' WHERE `quest` = '10601' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10744' WHERE `quest` = '10745' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10586' WHERE `quest` = '10603' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10703' WHERE `quest` = '10702' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10621' WHERE `quest` = '10623' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10644' WHERE `quest` = '10633' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10678' WHERE `quest` = '10673' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10612' WHERE `quest` = '10613' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10775' WHERE `quest` = '10768' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10661' WHERE `quest` = '10660' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10774' WHERE `quest` = '10765' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10589' WHERE `quest` = '10604' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10563' WHERE `quest` = '10596' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10776' WHERE `quest` = '10769' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9589' WHERE `quest` = '9590' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9493' WHERE `quest` = '9496' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9492' WHERE `quest` = '9495' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9575' WHERE `quest` = '9572' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9524' WHERE `quest` = '9525' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9607' WHERE `quest` = '9608' and `guid`=:charGUID;";
	}
	else
	{
		$query =   
		   "UPDATE `character_queststatus` SET `quest`='10876' WHERE `quest` = '10937' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10092' WHERE `quest` = '10084' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10120' WHERE `quest` = '10288' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10156' WHERE `quest` = '10138' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10450' WHERE `quest` = '10482' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10393' WHERE `quest` = '10395' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10088' WHERE `quest` = '10079' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9447' WHERE `quest` = '9833' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9441' WHERE `quest` = '10115' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9400' WHERE `quest` = '9835' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9442' WHERE `quest` = '10116' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10442' WHERE `quest` = '10443' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10155' WHERE `quest` = '10137' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10242' WHERE `quest` = '10483' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10838' WHERE `quest` = '10935' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10157' WHERE `quest` = '10139' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10121' WHERE `quest` = '10141' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10150' WHERE `quest` = '10122' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9387' WHERE `quest` = '9399' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10236' WHERE `quest` = '9355' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10392' WHERE `quest` = '10397' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9376' WHERE `quest` = '9423' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9366' WHERE `quest` = '9426' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10208' WHERE `quest` = '10144' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9340' WHERE `quest` = '9398' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10152' WHERE `quest` = '10130' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10060' WHERE `quest` = '10054' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9407' WHERE `quest` = '10119' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9588' WHERE `quest` = '9587' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10229' WHERE `quest` = '10058' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10278' WHERE `quest` = '9383' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10154' WHERE `quest` = '10131' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9466' WHERE `quest` = '9490' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10136' WHERE `quest` = '10400' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10756' WHERE `quest` = '10762' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10758' WHERE `quest` = '10764' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10110' WHERE `quest` = '10106' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10864' WHERE `quest` = '10909' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10086' WHERE `quest` = '10055' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10161' WHERE `quest` = '9420' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10391' WHERE `quest` = '10396' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10538' WHERE `quest` = '10484' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10390' WHERE `quest` = '10394' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10151' WHERE `quest` = '10126' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10809' WHERE `quest` = '10485' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11003' WHERE `quest` = '11002' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9483' WHERE `quest` = '9563' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10401' WHERE `quest` = '10149' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10127' WHERE `quest` = '10145' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10162' WHERE `quest` = '10163' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10125' WHERE `quest` = '10144' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10135' WHERE `quest` = '10148' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10129' WHERE `quest` = '10146' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10133' WHERE `quest` = '10147' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10289' WHERE `quest` = '10140' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9410' WHERE `quest` = '9545' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10757' WHERE `quest` = '10763' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10388' WHERE `quest` = '10382' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10347' WHERE `quest` = '10346' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10062' WHERE `quest` = '10057' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10123' WHERE `quest` = '10142' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9361' WHERE `quest` = '9385' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10100' WHERE `quest` = '10099' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10213' WHERE `quest` = '9558' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10158' WHERE `quest` = '10056' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10059' WHERE `quest` = '10053' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10792' WHERE `quest` = '10895' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10100' WHERE `quest` = '10630' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10755' WHERE `quest` = '10754' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10087' WHERE `quest` = '10078' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9773' WHERE `quest` = '9780' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9770' WHERE `quest` = '9791' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9814' WHERE `quest` = '9830' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9904' WHERE `quest` = '9902' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9775' WHERE `quest` = '9776' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10118' WHERE `quest` = '10115' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10117' WHERE `quest` = '10116' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9769' WHERE `quest` = '9790' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9903' WHERE `quest` = '9905' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9796' WHERE `quest` = '9793' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9845' WHERE `quest` = '9834' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9823' WHERE `quest` = '9839' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9899' WHERE `quest` = '9901' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9898' WHERE `quest` = '9896' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9828' WHERE `quest` = '9827' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10105' WHERE `quest` = '10104' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9842' WHERE `quest` = '9833' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9797' WHERE `quest` = '9792' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9846' WHERE `quest` = '9787' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9774' WHERE `quest` = '9781' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9847' WHERE `quest` = '9801' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9822' WHERE `quest` = '9835' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9820' WHERE `quest` = '9777' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9771' WHERE `quest` = '9782' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9816' WHERE `quest` = '9803' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9823' WHERE `quest` = '9848' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9772' WHERE `quest` = '9783' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9841' WHERE `quest` = '10355' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9786' WHERE `quest` = '9787' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9997' WHERE `quest` = '9996' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10034' WHERE `quest` = '10033' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9950' WHERE `quest` = '9949' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10052' WHERE `quest` = '10051' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9953' WHERE `quest` = '9952' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10015' WHERE `quest` = '10014' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9985' WHERE `quest` = '9984' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9989' WHERE `quest` = '9988' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10447' WHERE `quest` = '10446' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10023' WHERE `quest` = '10022' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9888' WHERE `quest` = '9917' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9929' WHERE `quest` = '9930' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9987' WHERE `quest` = '9986' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10868' WHERE `quest` = '10869' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10008' WHERE `quest` = '10007' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9942' WHERE `quest` = '9941' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9959' WHERE `quest` = '9958' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9974' WHERE `quest` = '9969' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10049' WHERE `quest` = '10048' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10006' WHERE `quest` = '10005' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9890' WHERE `quest` = '9920' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10201' WHERE `quest` = '10028' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10862' WHERE `quest` = '10863' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11506' WHERE `quest` = '11505' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9966' WHERE `quest` = '9965' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9964' WHERE `quest` = '9963' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10013' WHERE `quest` = '10012' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10027' WHERE `quest` = '10026' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10448' WHERE `quest` = '10444' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9993' WHERE `quest` = '9992' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9981' WHERE `quest` = '9980' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9947' WHERE `quest` = '9943' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10039' WHERE `quest` = '10038' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10196' WHERE `quest` = '10195' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10036' WHERE `quest` = '10035' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10043' WHERE `quest` = '10042' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9976' WHERE `quest` = '9975' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10003' WHERE `quest` = '10002' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10018' WHERE `quest` = '10016' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9995' WHERE `quest` = '9994' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9960' WHERE `quest` = '9961' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10041' WHERE `quest` = '10040' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10000' WHERE `quest` = '9998' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10791' WHERE `quest` = '10022' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10742' WHERE `quest` = '10806' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10724' WHERE `quest` = '10805' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10723' WHERE `quest` = '10801' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10785' WHERE `quest` = '10801' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10721' WHERE `quest` = '10800' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10720' WHERE `quest` = '10799' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10749' WHERE `quest` = '10799' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10715' WHERE `quest` = '10799' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10783' WHERE `quest` = '10798' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10714' WHERE `quest` = '10797' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10709' WHERE `quest` = '10795' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10859' WHERE `quest` = '10674' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10488' WHERE `quest` = '10457' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10488' WHERE `quest` = '10506' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10928' WHERE `quest` = '10927' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10503' WHERE `quest` = '10502' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10505' WHERE `quest` = '10504' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10545' WHERE `quest` = '10512' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10486' WHERE `quest` = '10455' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10867' WHERE `quest` = '10675' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11047' WHERE `quest` = '11043' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10851' WHERE `quest` = '10584' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10489' WHERE `quest` = '10690' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11036' WHERE `quest` = '11040' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10786' WHERE `quest` = '10803' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10865' WHERE `quest` = '10674' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9795' WHERE `quest` = '9794' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10853' WHERE `quest` = '10657' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10487' WHERE `quest` = '10456' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10542' WHERE `quest` = '10511' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10784' WHERE `quest` = '10796' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10846' WHERE `quest` = '10516' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10843' WHERE `quest` = '10517' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10845' WHERE `quest` = '10518' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10617' WHERE `quest` = '10620' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10618' WHERE `quest` = '10671' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10860' WHERE `quest` = '10510' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10718' WHERE `quest` = '10608' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10614' WHERE `quest` = '10594' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10524' WHERE `quest` = '10580' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10525' WHERE `quest` = '10581' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10526' WHERE `quest` = '10632' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10565' WHERE `quest` = '10557' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10566' WHERE `quest` = '10712' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10615' WHERE `quest` = '10609' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10860' WHERE `quest` = '10608' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10618' WHERE `quest` = '10594' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9946' WHERE `quest` = '9955' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9916' WHERE `quest` = '9924' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9868' WHERE `quest` = '9879' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9870' WHERE `quest` = '9869' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9863' WHERE `quest` = '9874' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9865' WHERE `quest` = '9878' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9906' WHERE `quest` = '9921' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9907' WHERE `quest` = '9922' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9948' WHERE `quest` = '9956' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10107' WHERE `quest` = '10108' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9872' WHERE `quest` = '9871' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11037' WHERE `quest` = '11042' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9983' WHERE `quest` = '9982' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9937' WHERE `quest` = '9938' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9935' WHERE `quest` = '9936' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9939' WHERE `quest` = '9940' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10479' WHERE `quest` = '10476' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9945' WHERE `quest` = '9954' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10074' WHERE `quest` = '10076' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10075' WHERE `quest` = '10077' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10478' WHERE `quest` = '10477' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9867' WHERE `quest` = '9873' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9891' WHERE `quest` = '9920' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9934' WHERE `quest` = '9933' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10114' WHERE `quest` = '10113' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9889' WHERE `quest` = '9918' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9910' WHERE `quest` = '9923' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11048' WHERE `quest` = '11044' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9866' WHERE `quest` = '9878' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11503' WHERE `quest` = '11502' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9889' WHERE `quest` = '9917' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9944' WHERE `quest` = '9955' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9864' WHERE `quest` = '9878' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10595' WHERE `quest` = '10562' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10627' WHERE `quest` = '10626' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10765' WHERE `quest` = '10774' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10673' WHERE `quest` = '10678' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10760' WHERE `quest` = '10569' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10660' WHERE `quest` = '10661' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10751' WHERE `quest` = '10773' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10663' WHERE `quest` = '10662' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10672' WHERE `quest` = '10677' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10602' WHERE `quest` = '10585' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10597' WHERE `quest` = '10572' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10681' WHERE `quest` = '10680' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10611' WHERE `quest` = '10606' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10599' WHERE `quest` = '10573' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10600' WHERE `quest` = '10582' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10761' WHERE `quest` = '10759' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10601' WHERE `quest` = '10583' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='11498' WHERE `quest` = '11497' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10647' WHERE `quest` = '10648' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10625' WHERE `quest` = '10643' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10639' WHERE `quest` = '10645' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10767' WHERE `quest` = '10766' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10598' WHERE `quest` = '10564' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10745' WHERE `quest` = '10744' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10603' WHERE `quest` = '10586' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10624' WHERE `quest` = '10642' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10623' WHERE `quest` = '10621' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10633' WHERE `quest` = '10644' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10613' WHERE `quest` = '10612' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10768' WHERE `quest` = '10775' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10702' WHERE `quest` = '10703' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10604' WHERE `quest` = '10589' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10596' WHERE `quest` = '10563' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='10769' WHERE `quest` = '10776' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9590' WHERE `quest` = '9589' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9495' WHERE `quest` = '9492' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9496' WHERE `quest` = '9493' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9572' WHERE `quest` = '9575' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9525' WHERE `quest` = '9524' and `guid`=:charGUID;
			UPDATE `character_queststatus` SET `quest`='9608' WHERE `quest` = '9607' and `guid`=:charGUID;";
	}

    $sth = $dbChar->prepare($query);
    $sth->bindParam(':charGUID', $charGUID);
    $sth->execute();


    // We have now done all of the required changes
    // Lets confirm character is still offline and then commit the changes!
    $query = "SELECT * from characters where name = :charName";
    $sth = $dbChar->prepare($query);
    $sth->bindParam(':charName', $charName);
    $sth->execute();
    $result = $sth->fetchAll();
    $charOnline = $result[0]['online'];

    // Char is online
    if ($charOnline == 1) {
        echo "Error : Character is currently online, please log out.";
        $dbChar->rollback();
        exit(1);
    }

    // Commit all previous changes
    $dbChar->commit();

    // We are done!!!
    echo "Faction Change Successful. Please clear your cache and wait 10 minutes before logging in. Otherwise you will face many visual bugs.";
    $myFile = "factionchangeList12314.txt";
	$fh = fopen($myFile, 'a') or die("can't open file");
	$stringData = $charName . " ";
	fwrite($fh, $stringData);
	fclose($fh);
?>