<?php
	//exportCMS.php -- allows a user to export the proposal and presenter information information for the CMS (TESOL's online portal)
	//accessible only to admin users
	
	include_once "login.php";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION["user_role"],"chair") === false) { //don't have permission to view this page
	
		$topTitle = "Access Denied!";
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(isset($_GET["t"])) {
		$t = strip_tags($_GET["t"]);
		if($t == 1) $eType = "Technology Fairs";
		else if($t == 2) $eType = "Mini-Workshops";
		else if($t == 3) $eType = "Developers Showcase";
		else if($t == 4) $eType = "Mobile Apps for Education Showcase";
		else if($t == 5) $eType = "Classics";
		else if($t == 6) $eType = "Other";
	}

	//First, get the main event speakers (the solicited events)
	$prStmt = $db->prepare("SELECT `ID`,`Prefix`,`First Name`,`Last Name`,`Affiliation Name`,`Title`,`Email` FROM `presenters` WHERE 1");
	$prStmt->execute();
	$prStmt->bind_result($prID,$prPrefix,$prFN,$prLN,$prAN,$prTitle,$prEmail);
	$presenters = array();
	while($prStmt->fetch()) {
		$presenters[] = array(
			"id" => $prID,
			"prefix" => $prPrefix,
			"first_name" => $prFN,
			"last_name" => $prLN,
			"affiliation" => $prAN,
			"job_title" => $prTitle,
			"email" => $prEmail
		);
	}
	
	//Next, get the classics presenters
	$cprStmt = $db->prepare("SELECT `ID`,`Prefix`,`First Name`,`Last Name`,`Affiliation Name`,`Title`,`Email` FROM `classics_presenters` WHERE 1");
	$cprStmt->execute();
	$cprStmt->bind_result($cprID,$cprPrefix,$cprFN,$cprLN,$cprAN,$cprTitle,$cprEmail);
	$classics_presenters = array();
	while($cprStmt->fetch()) {
		$classics_presenters[] = array(
			"id" => $cprID,
			"prefix" => $cprPrefix,
			"first_name" => $cprFN,
			"last_name" => $cprLN,
			"affiliation" => $cprAN,
			"job_title" => $cprTitle,
			"email" => $cprEmail
		);
	}
	
	//Next, get the other presenters
	$oprStmt = $db->prepare("SELECT `ID`,`Prefix`,`First Name`,`Last Name`,`Affiliation Name`,`Title`,`Email` FROM `other_presenters` WHERE 1");
	$oprStmt->execute();
	$oprStmt->bind_result($oprID,$oprPrefix,$oprFN,$oprLN,$oprAN,$oprTitle,$oprEmail);
	$other_presenters = array();
	while($oprStmt->fetch()) {
		$other_presenters[] = array(
			"id" => $oprID,
			"prefix" => $oprPrefix,
			"first_name" => $oprFN,
			"last_name" => $oprLN,
			"affiliation" => $oprAN,
			"job_title" => $oprTitle,
			"email" => $oprEmail
		);
	}
	
	//Next, get the proposals from the solicited events
	$pStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `proposals` WHERE 1");
	$pStmt->execute();
	$pStmt->bind_result($pID,$pTitle,$pPres,$pSummary);
	$proposals = array();
	while($pStmt->fetch()) {
		$proposals[] = array(
			"id" => $pID,
			"title" => $pTitle,
			"presenters" => $pPres,
			"summary" =>$pSummary
		);
	}
	
	//Next, get the proposals from the classics
	$cpStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `classics_proposals` WHERE 1");
	$cpStmt->execute();
	$cpStmt->bind_result($cpID,$cpTitle,$cpPres,$cpSummary);
	$classics_proposals = array();
	while($cpStmt->fetch()) {
		$classics_proposals[] = array(
			"id" => $cpID,
			"title" => $cpTitle,
			"presenters" => $cpPres,
			"summary" =>$cpSummary
		);
	}
	
	//Next, get the proposals from the other events
	$opStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `other_proposals` WHERE 1");
	$opStmt->execute();
	$opStmt->bind_result($opID,$opTitle,$opPres,$opSummary);
	$other_proposals = array();
	while($opStmt->fetch()) {
		$other_proposals[] = array(
			"id" => $opID,
			"title" => $opTitle,
			"presenters" => $opPres,
			"summary" =>$opSummary
		);
	}
	
	//Finally, get all the sessions
	$sStmt = $db->prepare("SELECT * FROM `sessions` WHERE 1");
	$sStmt->execute();
	$sStmt->bind_result($sID,$sLocation,$sDate,$sTime,$sEvent,$sTitle,$sPresentations);
	$sessions = array();
	while($sStmt->fetch()) {
		if($sPresentations != "") { //only get sessions with presentations assigned to them
			$sessions[] = array(
				"id" => $sID,
				"location" => $sLocation,
				"date" => $sDate,
				"time" => $sTime,
				"event" => $sEvent,
				"title" => $sTitle,
				"presentations" => $sPresentations
			);
		}
	}
	
	//Now, update the proposals arrays with the presenters information
	for($i = 0; $i < count($proposals); $i++) {
		$tmp = explode("|",$proposals[$i]["presenters"]);
		$thisPres = array();
		for($j = 0; $j < count($tmp); $j++) {
			for($p = 0; $p < count($presenters); $p++) {
				if($tmp[$j] == $presenters[$p]["id"]) {
					$thisPres[] = $presenters[$p];
					break;
				}
			}
		}
		
		$proposals[$i]["presenters"] = $thisPres;
	}
	
	for($i = 0; $i < count($classics_proposals); $i++) {
		$tmp = explode("|",$classics_proposals[$i]["presenters"]);
		$thisPres = array();
		for($j = 0; $j < count($tmp); $j++) {
			for($p = 0; $p < count($classics_presenters); $p++) {
				if($tmp[$j] == $classics_presenters[$p]["id"]) {
					$thisPres[] = $classics_presenters[$p];
					break;
				}
			}
		}
		
		$classics_proposals[$i]["presenters"] = $thisPres;
	}
	
	for($i = 0; $i < count($other_proposals); $i++) {
		$tmp = explode("|",$other_proposals[$i]["presenters"]);
		$thisPres = array();
		for($j = 0; $j < count($tmp); $j++) {
			for($p = 0; $p < count($other_presenters); $p++) {
				if($tmp[$j] == $other_presenters[$p]["id"]) {
					$thisPres[] = $other_presenters[$p];
					break;
				}
			}
		}
		
		$other_proposals[$i]["presenters"] = $thisPres;
	}
	
	//now, update the sessions array with the proposals information
	for($i = 0; $i < count($sessions); $i++) {
		$tmp = explode("||",$sessions[$i]["presentations"]);
		$thisPres = array();
		$pCount = 0;
		for($j = 0; $j < count($tmp); $j++) {
			$tmpP = explode("|",$tmp[$j]);
			$thisPres[$pCount] = array();
			if(count($tmpP) > 1) $pID = $tmpP[1];
			else $pID = $tmpP[0];
			
			if($sessions[$i]["event"] != "Other") {
				for($k = 0; $k < count($proposals); $k++) {
					if($proposals[$k]["id"] == $pID) {
						$thisPres[$pCount]["title"] = $proposals[$k]["title"];
						$thisPres[$pCount]["summary"] = $proposals[$k]["summary"];
						$thisPres[$pCount]["presenters"] = $proposals[$k]["presenters"];
						break;
					}
				}
			} else {
				if($sessions[$i]["title"] == "Technology Fair: Classics") {
					for($k = 0; $k < count($classics_proposals); $k++) {
						if($classics_proposals[$k]["id"] == $pID) {
							$thisPres[$pCount]["title"] = $classics_proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $classics_proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $classics_proposals[$k]["presenters"];
							break;
						}
					}				
				} else {
					for($k = 0; $k < count($other_proposals); $k++) {
						if($other_proposals[$k]["id"] == $pID) {
							$thisPres[$pCount]["title"] = $other_proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $other_proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $other_proposals[$k]["presenters"];
							break;
						}
					}				
				}
			}
			
			if(count($thisPres[$pCount]) == 0) array_splice($thisPres, $pCount, 1);
			else $pCount++;
		}
		
		$sessions[$i]["presentations"] = $thisPres;		
	}
	
	//Now, export to arrays
	$seminars = array();
	$speakerID = 9000; //the SYNC_ID for speakers will start at 3000
	$sessionID = 2000; //the SYNC_ID for sessions will start at 2000
	$sessionCI = 5000; //the SESSION_CODE is in the format of "0592-00" + the sessionCI
	$maxSpeakerCount = 0;
	for($s = 0; $s < count($sessions); $s++) {
		$tmpPres = $sessions[$s]["presentations"];
		for($p = 0; $p < count($tmpPres); $p++) {
			if($sessions[$s]["location"] == "ev") $sL = "Holiday Ballroom 5";
			else if($sessions[$s]["location"] == "ts") $sL = "Holiday Ballroom 4";
			
			$tmpDate = explode("-",$sessions[$s]["date"]);
			$sD = date("m/d/Y",mktime(0,0,0,intval($tmpDate[1]),intval($tmpDate[2]),intval($tmpDate[0])));
			
			$tmpTime = explode("-",$sessions[$s]["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			if(intval($tmpStart[0]) < 12) $sStAMPM = "AM";
			else $sStAMPM = "PM";
			
			if(intval($tmpStart[0]) > 12) $sStHour = intval($tmpStart[0]) - 12;
			else $sStHour = intval($tmpStart[0]);
			
			if($sStHour < 10) $sStHour = "0".$sStHour; //add a leading 0 if needed
			
			$sST = $sStHour.":".$tmpStart[1]." ".$sStAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			if(intval($tmpStart[0]) < 12) $sEndAMPM = "AM";
			else $sEndAMPM = "PM";
			
			if(intval($tmpEnd[0]) > 12) $sEndHour = intval($tmpEnd[0]) - 12;
			else $sEndHour = intval($tmpEnd[0]);
			
			if($sEndHour < 10) $sEndHour = "0".$sEndHour; //add a leading 0 if needed
			
			$sET = $sEndHour.":".$tmpEnd[1]." ".$sEndAMPM;
			
			$tmpP = $tmpPres[$p]["presenters"];
			$speakers = array();
			for($i = 0; $i < count($tmpP); $i++) {
				$speakers[] = array(
					"SPEAKER_SYNC_ID" => $speakerID,
					"SPEAKER_MEMBER_NO" => "",
					"SPEAKER_ACTIVE" => "",
					"SPEAKER_FIRST_NAME" => $tmpP[$i]["first_name"],
					"SPEAKER_LAST_NAME" => $tmpP[$i]["last_name"],
					"SPEAKER_TITLE" => "",
					"SPEAKER_EMAIL" => $tmpP[$i]["email"],
					"SPEAKER_SUFFIX" => "",
					"SPEAKER_ROLE" => "",
					"SPEAKER_CREDENTIALS" => "",
					"SPEAKER_PHONE" => "",
					"SPEAKER_CELL_PHONE" => "",
					"SPEAKER_FAX" => "",
					"SPEAKER_PASSWORD" => "",
					"SPEAKER_COMPANY" => "",
					"SPEAKER_COMPANY_DIVISION" => "",
					"SPEAKER_COMPANY_SHORTNAME" => "",
					"SPEAKER_ADDRESS1" => "",
					"SPEAKER_ADDRESS2" => "",
					"SPEAKER_CITY" => "",
					"SPEAKER_STATE" => "",
					"SPEAKER_ZIP_CODE" => "",
					"SPEAKER_COUNTRY" => "",
					"SPEAKER_PHOTO" => "",
					"SPEAKER_BIO" => ""
				);
				$speakerID++;
				if($maxSpeakerCount < ($i + 1)) $maxSpeakerCount = ($i + 1);
			}
			
			$seminars[] = array(
				"SESSION_SYNC_ID" => $sessionID,
				"PARENT_SESSION_SYNC_ID" => "",
				"SESSION_CODE" => "00592-00".$sessionCI,
				"SESSION_CODE2" => "",
				"SESSION_TITLE" => $tmpPres[$p]["title"],
				"SESSION_DISPLAY_ORDER" => "",
				"TRACK" => "Electronic Village",
				"TYPE" => $sessions[$s]["event"],
				"VENUE" => "Hilton",
				"ROOM" => $sL,
				"ROOM_SETUP" => "",
				"CME_CREDIT" => "",
				"NUMBER_OF_SEATS" => "",
				"NOTES" => "",
				"SESSION_DESCRIPTION" => str_replace("\r\n"," ",$tmpPres[$p]["summary"]),
				"START_DATE" => $sD,
				"START_TIME" => $sST,
				"END_TIME" => $sET,
				"ACTIVE" => "",
				"MANAGER_SYNC_ID" => "",
				"MANAGER_MEMBER_NO" => "",
				"MANAGER_ACTIVE" => "",
				"MANAGER_FIRST_NAME" => "",
				"MANAGER_LAST_NAME" => "",
				"MANAGER_TITLE" => "",
				"MANAGER_EMAIL" => "",
				"MANAGER_SUFFIX" => "",
				"MANAGER_CREDENTIALS" => "",
				"MANAGER_PHONE" => "",
				"MANAGER_CELL_PHONE" => "",
				"MANAGER_FAX" => "",
				"MANAGER_PASSWORD" => "",
				"MANAGER_COMPANY" => "",
				"MANAGER_COMPANY_DIVISION" => "",
				"MANAGER_COMPANY_SHORTNAME" => "",
				"MANAGER_ADDRESS1" => "",
				"MANAGER_ADDRESS2" => "",
				"MANAGER_CITY" => "",
				"MANAGER_STATE" => "",
				"MANAGER_ZIP_CODE" => "",
				"MANAGER_COUNTRY" => "",
				"SPEAKERS" => $speakers
			);
			
			$sessionID++;
			$sessionCI++;
		}
	}
	
	//Now, export the required information to the CSV string
	$csvStr = array("SESSION_SYNC_ID","PARENT_SESSION_SYNC_ID","SESSION_CODE","SESSION_CODE2","SESSION_TITLE","SESSION_DISPLAY_ORDER","TRACK","TYPE","VENUE","ROOM","ROOM_SETUP","CME_CREDIT","NUMBER_OF_SEATS","NOTES","SESSION_DESCRIPTION","START_DATE","START_TIME","END_TIME","ACTIVE","MANAGER_SYNC_ID","MANAGER_MEMBER_NO","MANAGER_ACTIVE","MANAGER_FIRST_NAME","MANAGER_LAST_NAME","MANAGER_TITLE","MANAGER_EMAIL","MANAGER_SUFFIX","MANAGER_CREDENTIALS","MANAGER_PHONE","MANAGER_CELL_PHONE","MANAGER_FAX","MANAGER_PASSWORD","MANAGER_COMPANY","MANAGER_COMPANY_DIVISION","MANAGER_COMPANY_SHORTNAME","MANAGER_ADDRESS1","MANAGER_ADDRESS2","MANAGER_CITY","MANAGER_STATE","MANAGER_ZIP_CODE","MANAGER_COUNTRY");
	for($sN = 1; $sN <= $maxSpeakerCount; $sN++) {
		$csvStr[] = "SPEAKER_SYNC_ID_".$sN;
		$csvStr[] = "SPEAKER_MEMBER_NO_".$sN;
		$csvStr[] = "SPEAKER_ACTIVE_".$sN;
		$csvStr[] = "SPEAKER_FIRST_NAME_".$sN;
		$csvStr[] = "SPEAKER_LAST_NAME_".$sN;
		$csvStr[] = "SPEAKER_TITLE_".$sN;
		$csvStr[] = "SPEAKER_EMAIL_".$sN;
		$csvStr[] = "SPEAKER_SUFFIX_".$sN;
		$csvStr[] = "SPEAKER_ROLE_".$sN;
		$csvStr[] = "SPEAKER_CREDENTIALS_".$sN;
		$csvStr[] = "SPEAKER_PHONE_".$sN;
		$csvStr[] = "SPEAKER_CELL_PHONE_".$sN;
		$csvStr[] = "SPEAKER_FAX_".$sN;
		$csvStr[] = "SPEAKER_PASSWORD_".$sN;
		$csvStr[] = "SPEAKER_COMPANY_".$sN;
		$csvStr[] = "SPEAKER_COMPANY_DIVISION_".$sN;
		$csvStr[] = "SPEAKER_COMPANY_SHORTNAME_".$sN;
		$csvStr[] = "SPEAKER_ADDRESS1_".$sN;
		$csvStr[] = "SPEAKER_ADDRESS2_".$sN;
		$csvStr[] = "SPEAKER_CITY_".$sN;
		$csvStr[] = "SPEAKER_STATE_".$sN;
		$csvStr[] = "SPEAKER_ZIP_CODE_".$sN;
		$csvStr[] = "SPEAKER_COUNTRY_".$sN;
		$csvStr[] = "SPEAKER_PHOTO_".$sN;
		$csvStr[] = "SPEAKER_BIO_".$sN;
	}
	
	header("Content-Type: text/plain");
	header('Content-Disposition: attachment; filename="sessions.txt"');
		
	$fp = fopen('php://output','w');
	fputcsv($fp,$csvStr,"\t");
	
	for($i = 0; $i < count($seminars); $i++) {
		$thisStr = array();
		$thisStr[] = $seminars[$i]["SESSION_SYNC_ID"];
		$thisStr[] = $seminars[$i]["PARENT_SESSION_SYNC_ID"];
		$thisStr[] = $seminars[$i]["SESSION_CODE"];
		$thisStr[] = $seminars[$i]["SESSION_CODE2"];
		$thisStr[] = stripslashes(convertChars(trim($seminars[$i]["SESSION_TITLE"])));
		$thisStr[] = $seminars[$i]["SESSION_DISPLAY_ORDER"];
		$thisStr[] = $seminars[$i]["TRACK"];
		$thisStr[] = $seminars[$i]["TYPE"];
		$thisStr[] = $seminars[$i]["VENUE"];
		$thisStr[] = $seminars[$i]["ROOM"];
		$thisStr[] = $seminars[$i]["ROOM_SETUP"];
		$thisStr[] = $seminars[$i]["CME_CREDIT"];
		$thisStr[] = $seminars[$i]["NUMBER_OF_SEATS"];
		$thisStr[] = $seminars[$i]["NOTES"];
		$thisStr[] = stripslashes(convertChars(trim($seminars[$i]["SESSION_DESCRIPTION"])));
		$thisStr[] = $seminars[$i]["START_DATE"];
		$thisStr[] = $seminars[$i]["START_TIME"];
		$thisStr[] = $seminars[$i]["END_TIME"];
		$thisStr[] = $seminars[$i]["ACTIVE"];
		$thisStr[] = $seminars[$i]["MANAGER_SYNC_ID"];
		$thisStr[] = $seminars[$i]["MANAGER_MEMBER_NO"];
		$thisStr[] = $seminars[$i]["MANAGER_ACTIVE"];
		$thisStr[] = $seminars[$i]["MANAGER_FIRST_NAME"];
		$thisStr[] = $seminars[$i]["MANAGER_LAST_NAME"];
		$thisStr[] = $seminars[$i]["MANAGER_TITLE"];
		$thisStr[] = $seminars[$i]["MANAGER_EMAIL"];
		$thisStr[] = $seminars[$i]["MANAGER_SUFFIX"];
		$thisStr[] = $seminars[$i]["MANAGER_CREDENTIALS"];
		$thisStr[] = $seminars[$i]["MANAGER_PHONE"];
		$thisStr[] = $seminars[$i]["MANAGER_CELL_PHONE"];
		$thisStr[] = $seminars[$i]["MANAGER_FAX"];
		$thisStr[] = $seminars[$i]["MANAGER_PASSWORD"];
		$thisStr[] = $seminars[$i]["MANAGER_COMPANY"];
		$thisStr[] = $seminars[$i]["MANAGER_COMPANY_DIVISION"];
		$thisStr[] = $seminars[$i]["MANAGER_COMPANY_SHORTNAME"];
		$thisStr[] = $seminars[$i]["MANAGER_ADDRESS1"];
		$thisStr[] = $seminars[$i]["MANAGER_ADDRESS2"];
		$thisStr[] = $seminars[$i]["MANAGER_CITY"];
		$thisStr[] = $seminars[$i]["MANAGER_STATE"];
		$thisStr[] = $seminars[$i]["MANAGER_ZIP_CODE"];
		$thisStr[] = $seminars[$i]["MANAGER_COUNTRY"];
		
		for($sI = 0; $sI < $maxSpeakerCount; $sI++) {
			if($sI < count($seminars[$i]["SPEAKERS"])) {
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_SYNC_ID"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_MEMBER_NO"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_ACTIVE"];
				$thisStr[] = stripslashes(convertChars(trim($seminars[$i]["SPEAKERS"][$sI]["SPEAKER_FIRST_NAME"])));
				$thisStr[] = stripslashes(convertChars(trim($seminars[$i]["SPEAKERS"][$sI]["SPEAKER_LAST_NAME"])));
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_TITLE"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_EMAIL"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_SUFFIX"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_ROLE"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_CREDENTIALS"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_PHONE"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_CELL_PHONE"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_FAX"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_PASSWORD"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_COMPANY"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_COMPANY_DIVISION"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_COMPANY_SHORTNAME"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_ADDRESS1"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_ADDRESS2"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_CITY"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_STATE"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_ZIP_CODE"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_COUNTRY"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_PHOTO"];
				$thisStr[] = $seminars[$i]["SPEAKERS"][$sI]["SPEAKER_BIO"];
			} else { //insert a blank one to fill the rest of the line
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
				$thisStr[] = "";
			}
		}
					
		fputcsv($fp,$thisStr,"\t");
	}
		
	fclose($fp);
	exit();
	
	function convertChars($text) {
		// First, replace UTF-8 characters.
		$text = str_replace(
			array("\xe2\x80\x98", "\xe2\x80\x99", "\xe2\x80\x9c", "\xe2\x80\x9d", "\xe2\x80\x93", "\xe2\x80\x94", "\xe2\x80\xa6"),
			array("'", "'", '"', '"', '-', '--', '...'),
			$text);

		// Next, replace their Windows-1252 equivalents.
		$text = str_replace(
			array(chr(145), chr(146), chr(147), chr(148), chr(150), chr(151), chr(133)),
			array("'", "'", '"', '"', '-', '--', '...'),
			$text);	
			
		return $text;
	}
?>