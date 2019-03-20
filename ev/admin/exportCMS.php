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
	
	// The locations are in the database, so get a list of the locations
	$locRes = $db->query("SELECT * FROM locations");
	$locations = array();
	while($locRow = $locRes->fetch_assoc()) {
		$locations[] = $locRow;
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
	$prStmt = $db->prepare("SELECT `ID`,`First Name`,`Last Name`,`Email`,`Affiliation Name`,`Title` FROM `presenters` WHERE 1");
	$prStmt->execute();
	$prStmt->bind_result($prID,$prFN,$prLN,$prEmail,$prAN,$prTitle);
	$presenters = array();
	while($prStmt->fetch()) {
		$presenters[] = array(
			"id" => $prID,
			"first_name" => $prFN,
			"last_name" => $prLN,
			"email" => $prEmail,
			"affiliation" => $prAN,
			"job_title" => $prTitle
		);
	}
	
	//Next, get the classics presenters
	$cprStmt = $db->prepare("SELECT `ID`,`First Name`,`Last Name`,`Email`,`Affiliation Name`,`Title` FROM `classics_presenters` WHERE 1");
	$cprStmt->execute();
	$cprStmt->bind_result($cprID,$cprFN,$cprLN,$cprEmail,$cprAN,$cprTitle);
	$classics_presenters = array();
	while($cprStmt->fetch()) {
		$classics_presenters[] = array(
			"id" => $cprID,
			"first_name" => $cprFN,
			"last_name" => $cprLN,
			"email" => $cprEmail,
			"affiliation" => $cprAN,
			"job_title" => $cprTitle
		);
	}
	
	//Next, get the other presenters
	$oprStmt = $db->prepare("SELECT `ID`,`First Name`,`Last Name`,`Email`,`Affiliation Name`,`Title` FROM `other_presenters` WHERE 1");
	$oprStmt->execute();
	$oprStmt->bind_result($oprID,$oprFN,$oprLN,$oprEmail,$oprAN,$oprTitle);
	$other_presenters = array();
	while($oprStmt->fetch()) {
		$other_presenters[] = array(
			"id" => $oprID,
			"first_name" => $oprFN,
			"last_name" => $oprLN,
			"email" => $oprEmail,
			"affiliation" => $oprAN,
			"job_title" => $oprTitle
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
	$sStmt = $db->prepare("SELECT s.*, e.event AS eventName, e.propTable AS propTable FROM sessions AS s, events AS e WHERE s.event = e.id");
	$sStmt->execute();
	$sStmt->bind_result($sID,$sLocation,$sDate,$sTime,$sEvent,$sTitle,$sPresentations,$evtName,$evtPropTable);
	$sessions = array();
	while($sStmt->fetch()) {
		if($sPresentations != "") { //only get sessions with presentations assigned to them
			$sessions[] = array(
				"id" => $sID,
				"location" => $sLocation,
				"date" => $sDate,
				"time" => $sTime,
				"eventID" => $sEvent,
				"eventName" => $evtName,
				"title" => $sTitle,
				"propTable" => $evtPropTable,
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
			
			if($sessions[$i]["propTable"] == "proposals") {
				for($k = 0; $k < count($proposals); $k++) {
					if($proposals[$k]["id"] == $pID) {
						$thisPres[$pCount]["id"] = 'P-'.str_pad($proposals[$k]["id"], 6, '0', STR_PAD_LEFT);
						$thisPres[$pCount]["title"] = $proposals[$k]["title"];
						$thisPres[$pCount]["summary"] = $proposals[$k]["summary"];
						$thisPres[$pCount]["presenters"] = $proposals[$k]["presenters"];
						break;
					}
				}
			} else if($sessions[$i]["propTable"] == "classics_proposals") {
					for($k = 0; $k < count($classics_proposals); $k++) {
						if($classics_proposals[$k]["id"] == $pID) {
							$thisPres[$pCount]["id"] = 'C-'.str_pad($classics_proposals[$k]["id"], 6, '0', STR_PAD_LEFT);
							$thisPres[$pCount]["title"] = $classics_proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $classics_proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $classics_proposals[$k]["presenters"];
							break;
						}
					}				
			} else if($sessions[$i]["propTable"] == "other_proposals") {
				for($k = 0; $k < count($other_proposals); $k++) {
					if($other_proposals[$k]["id"] == $pID) {
						$thisPres[$pCount]["id"] = 'O-'.str_pad($other_proposals[$k]["id"], 6, '0', STR_PAD_LEFT);
						$thisPres[$pCount]["title"] = $other_proposals[$k]["title"];
						$thisPres[$pCount]["summary"] = $other_proposals[$k]["summary"];
						$thisPres[$pCount]["presenters"] = $other_proposals[$k]["presenters"];
						break;
					}
				}				
			}
			
			if(count($thisPres[$pCount]) == 0) array_splice($thisPres, $pCount, 1);
			else $pCount++;
		}
		
		$sessions[$i]["presentations"] = $thisPres;		
	}
	
	//Now, export to arrays
	$cmsSpeakers = array();
	$cmsSessions = array();
	$cmsEmails = array();
	for($s = 0; $s < count($sessions); $s++) {
		$tmpPres = $sessions[$s]["presentations"];
		for($p = 0; $p < count($tmpPres); $p++) {
			foreach($locations AS $loc) {
				if($sessions[$s]["location"] == $loc['id']) {
					$sL = $loc['room'];
					break;
				}
			}
			
			$tmpDate = explode("-",$sessions[$s]["date"]);
			$sD = intval($tmpDate[1])."/".intval($tmpDate[2])."/".substr($tmpDate[0],2,2);
			
			$tmpTime = explode("-",$sessions[$s]["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			if(intval($tmpStart[0]) < 12) $sST = intval($tmpStart[0]).":".$tmpStart[1]." AM";
			else if(intval($tmpStart[0]) == 12) $sST = intval($tmpStart[0]).":".$tmpStart[1]." PM";
			else if(intval($tmpStart[0]) > 12) $sST = (intval($tmpStart[0]) - 12).":".$tmpStart[1]." PM";
			
			$tmpEnd = explode(":",$tmpTime[1]);
			if(intval($tmpEnd[0]) < 12) $sET = intval($tmpEnd[0]).":".$tmpEnd[1]." AM";
			else if(intval($tmpEnd[0]) == 12) $sET = intval($tmpEnd[0]).":".$tmpEnd[1]." PM";
			else if(intval($tmpEnd[0]) > 12) $sET = (intval($tmpEnd[0]) - 12).":".$tmpEnd[1]." PM";
			
			$sPres = "";
			$tmpP = $tmpPres[$p]["presenters"];
			for($i = 0; $i < count($tmpP); $i++) {
				$sPres .= $tmpP[$i]["email"]." ";
				if(!in_array($tmpP[$i]["email"], $cmsEmails)) {
					$cmsEmails[] = $tmpP[$i]["email"];
					$cmsSpeakers[] = array(
						"externalID" => "",
						"firstName" => $tmpP[$i]["first_name"],
						"lastName" => $tmpP[$i]["last_name"],
						"credentials" => "",
						"email" => $tmpP[$i]["email"],
						"organizationName" => $tmpP[$i]["affiliation"],
						"title" => "",
						"bio" => "",
						"linkList" => "",
						"photoURL" => "",
						"tags" => "",
						"ribbonName" => "",
						"phoneNumber" => "",
						"phoneCountryCode" => ""
					);
				}
			}
			
			$sPres = rtrim($sPres); //remove the last space
			
			$cmsSessions[] = array(
				"id" => "EV-".str_pad($tmpPres[$p]["id"], "0", STR_PAD_LEFT),
				"name" => $tmpPres[$p]["title"],
				"startTime" => $sD." ".$sST,
				"endTime" => $sD." ".$sET,
				"speakerEmails" => $sPres,
				"locationName" => $sL,
				"strand" => "Electronic Village",
				"typeOfSession" => $sessions[$s]["eventName"],
				"keywords" => "",
				"sessionSummary" => str_replace("\r\n"," ",$tmpPres[$p]["summary"]),
				"documentList" => "",
				"linkList" => "",
				"mandatory" => "",
				"unscheduable" => "",
				"acceptedAttendeesEmails" => ""
			);
		}
	}
	
	array_unique($cmsSpeakers, SORT_REGULAR);
	
	//Now, export the required information to the CSV string
	if(isset($_GET["p"]) && $_GET["p"] == "1") { //export the speakers
		$csvStr[] = "External ID";
		$csvStr[] = "First Name";
		$csvStr[] = "Last Name";
		$csvStr[] = "Credentials";
		$csvStr[] = "Email";
		$csvStr[] = "Organization Name";
		$csvStr[] = "Title";
		$csvStr[] = "Bio";
		$csvStr[] = "Link List";
		$csvStr[] = "Photo URL";
		$csvStr[] = "Tags";
		$csvStr[] = "Ribbon Name";
		$csvStr[] = "Phone Number";
		$csvStr[] = "Phone Country Code";
		
		header("Content-Type: text/plain");
		header('Content-Disposition: attachment; filename="speakers.txt"');
		
		$fp = fopen('php://output','w');
		fputcsv($fp,$csvStr,"\t");
		
		for($i = 0; $i < count($cmsSpeakers); $i++) {
			$thisStr = array();
			$thisStr[] = $cmsSpeakers[$i]["externalID"];
			$thisStr[] = stripslashes(convertChars(trim($cmsSpeakers[$i]["firstName"])));
			$thisStr[] = stripslashes(convertChars(trim($cmsSpeakers[$i]["lastName"])));
			$thisStr[] = $cmsSpeakers[$i]["credentials"];
			$thisStr[] = $cmsSpeakers[$i]["email"];
			$thisStr[] = stripslashes(convertChars(trim($cmsSpeakers[$i]["organizationName"])));
			$thisStr[] = stripslashes(convertChars(trim($cmsSpeakers[$i]["title"])));
			$thisStr[] = $cmsSpeakers[$i]["bio"];
			$thisStr[] = $cmsSpeakers[$i]["linkList"];
			$thisStr[] = $cmsSpeakers[$i]["photoURL"];
			$thisStr[] = $cmsSpeakers[$i]["tags"];
			$thisStr[] = $cmsSpeakers[$i]["ribbonName"];
			$thisStr[] = $cmsSpeakers[$i]["phoneNumber"];
			$thisStr[] = $cmsSpeakers[$i]["phoneCountryCode"];
			
			fputcsv($fp,$thisStr,"\t");
		}
		
		fclose($fp);
		exit();
	} else if(isset($_GET["p"]) && $_GET["p"] == "2") { //export the seminars
		$csvStr[] = "reference ID";
		$csvStr[] = "name";
		$csvStr[] = "start time";
		$csvStr[] = "end time";
		$csvStr[] = "speaker_emails";
		$csvStr[] = "location_name";
		$csvStr[] = "Strand";
		$csvStr[] = "type of session";
		$csvStr[] = "keywords";
		$csvStr[] = "session summary";
		$csvStr[] = "document_list";
		$csvStr[] = "link_list";
		$csvStr[] = "mandatory";
		$csvStr[] = "unscheduable";
		$csvStr[] = "accepted_attendees_emails";
		
		header("Content-Type: text/plain");
		header('Content-Disposition: attachment; filename="sessions.txt"');
		
		$fp = fopen('php://output','w');
		fputcsv($fp,$csvStr,"\t");
		
		for($i = 0; $i < count($cmsSessions); $i++) {
			$thisStr = array();
			$thisStr[] = $cmsSessions[$i]["id"];
			$thisStr[] = stripslashes(convertChars(trim($cmsSessions[$i]["name"])));
			$thisStr[] = $cmsSessions[$i]["startTime"];
			$thisStr[] = $cmsSessions[$i]["endTime"];
			$thisStr[] = $cmsSessions[$i]["speakerEmails"];
			$thisStr[] = $cmsSessions[$i]["locationName"];
			$thisStr[] = $cmsSessions[$i]["strand"];
			$thisStr[] = $cmsSessions[$i]["typeOfSession"];
			$thisStr[] = $cmsSessions[$i]["keywords"];
			$thisStr[] = stripslashes(convertChars(trim($cmsSessions[$i]["sessionSummary"])));
			$thisStr[] = $cmsSessions[$i]["documentList"];
			$thisStr[] = $cmsSessions[$i]["linkList"];
			$thisStr[] = $cmsSessions[$i]["mandatory"];
			$thisStr[] = $cmsSessions[$i]["unscheduable"];
			$thisStr[] = $cmsSessions[$i]["acceptedAttendeesEmails"];
			
			fputcsv($fp,$thisStr,"\t");
		}
		
		fclose($fp);
		exit();
	}
				
	$topTitle = "Export CMS Data for TESOL";
	include "adminTop.php";
?>
	<h3 align="center">Please choose the data type to export</h3>
	<p align="center"><a href="exportCMS.php?p=1">Speakers</a></p>
	<p align="center"><a href="exportCMS.php?p=2">Sessions</a></p>
<?php
	include "adminBottom.php";
	
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