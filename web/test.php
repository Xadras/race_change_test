<?php
	$dbUser = 'webexternal';
	$dbPass = 'mkI0g64BiwFafjs';
	$dbRealm = new PDO('mysql:host=144.76.68.174;port=8899;dbname=progress_realm;charset=utf8mb4', $dbUser, $dbPass);
	$dbChar = new PDO('mysql:host=144.76.68.174;port=8899;dbname=progress_chars;charset=utf8mb4', $dbUser, $dbPass);
	echo "Connected";
?>