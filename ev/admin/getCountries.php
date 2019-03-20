<?php
	include "login.php";
	
	$pStmt = $db->prepare("SELECT `country` FROM `presenters` WHERE 1");
	$pStmt->execute();
	$pStmt->bind_result($country);
	$tmp = array();
	while($pStmt->fetch()) {
		$tmp[] = $country;
	}
	
	$opStmt = $db->prepare("SELECT `country` FROM `other_presenters` WHERE 1");
	$opStmt->execute();
	$opStmt->bind_result($country);
	while($opStmt->fetch()) {
		$tmp[] = $country;
	}
	
	$cpStmt = $db->prepare("SELECT `country` FROM `classics_presenters` WHERE 1");
	$cpStmt->execute();
	$cpStmt->bind_result($country);
	while($cpStmt->fetch()) {
		$tmp[] = $country;
	}
	
	sort($tmp);
	
	$oC = '';
	$countries = array();
	foreach($tmp AS $c) {
		if($c != $oC) {
			$countries[] = $c;
			$oC = $c;
		}
	}
	
	echo "<pre>";
	print_r($countries);
	echo "</pre>";
?>