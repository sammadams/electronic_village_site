<?php
	/*
		status.php -- allows a user to check the status of their proposal
		
		Users will be asked to type in their email address (the one that was used as the "main contact" on the proposal submission).
		The system will search for proposals with that email address and list them. No status will be displayed on this list. In
		order to see the status, they will have to click on the proposal itself, which will pop up a box containing the notification
		email (as web page) that they should have received.
		
		The page will load ALL the proposal data each time it is called, and we will use javascript to display the data we want.
	*/
	
	include_once "../../ev_config.php"; //holds the settings and defines constants
	include_once "../../ev_library.php"; //contains functions and connects to the DB
	
	sec_session_start(); //start a secure PHP session
	
	$confirmLink = "https://call-is.org/ev/confirm.php?id=";
	$certificateLink = "https://call-is.org/ev/certificate.php?id=";
	$y = $confYear;
	$cLocation = "Atlanta, Georgia, USA";
	$cDates = "March 12 - 15, ".$y;
	$cURL = "http://www.tesol.org/convention-".$y;
	
	$confirmDate = "December 10, 2018";
	$evFairsLeadName = "Jose Antonio da Silva";
	$evMiniLeadName = "Sandy Wagner";
	$evDSLeadName = "Andy Bowman";
	$evMAELeadName = "Audra Anjum";
	$evClassicsLeadName = "Maria Tomeho-Palermino";
	$evHTLeadName = "Christine Sabieh";
	$evGradLeadName = "Stephanie Korslund";

	//$allowOK = (isset($_GET["db"]) && $_GET["db"] == "1") ? true : false;
	$allowOK = true;
	if($allowOK === false) {
?>
<html>
	<head>
		<title>Proposal Status -- Electronic Village</title>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}

			span.label {
				color: green;
				font-size: 9pt;
				font-style: italic;
			}	
						
			a {
				text-decoration: none;
				border-bottom: dashed 1px #CCCCCC;
				color: #0066CC;
			}
			
			a:hover {
				border-bottom: solid 1px #0066CC;
			}

			th.sList {
				background-color: #333333;
				color: #FFFFFF;
				font-size: .85em;
				text-align: left;
				padding: 5px;
			}
		
			td.sList_rowEven {
				background-color: #FFFFFF;
				color: #000000;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;
			}

			td.sList_rowOdd {
				background-color: #CCCCCC;
				color: #000000;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;
			}
				
			td.sList_highlighted {
				background-color: #333333;
				color: #FFFFFF;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;
			}
			
			div#notificationDiv {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: auto;
				background-color: #FFFFFF;
				padding: 50px;
			}
		</style>
	</head>
	
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $y; ?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Proposal Status</span></td>
			</tr>
			<tr>
				<td>
					<p>You cannot check the status of your proposal at this time. Please contact <a href="mailto:ev@call-is.org">ev@call-is.org</a> with any questions.</p>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php
		exit();
	}
	
	//echo "<pre>";
	//print_r($_POST);
	//secho "</pre>";
	
	if(isset($_POST["contact"])) $contactEmail = filter_var($_POST["contact"], FILTER_VALIDATE_EMAIL);
	if(isset($contactEmail) && $contactEmail == "") unset($contactEmail);
	
	//get all the proposals
	$pStmt = $db->prepare("SELECT `id`,`title`,`contact`,`presenters`,`abstract`,`summary`,`emailOK`,`type`,`status` FROM `proposals` WHERE 1 ORDER BY `id`");
	$pStmt->execute();
	$pStmt->bind_result($pID,$pTitle,$pContact,$pPresenters,$pAbstract,$pSummary,$pEmailOK,$pType,$pStatus);
	
	$proposals = array();
	while($pStmt->fetch()) {
		$proposals[] = array(
			"id" => $pID,
			"title" => $pTitle,
			"contact" => $pContact,
			"presenters" => $pPresenters,
			"abstract" => $pAbstract,
			"summary" => $pSummary,
			"emailOK" => $pEmailOK,
			"type" => $pType,
			"status" => $pStatus,
			"session" => 0
		);
	}
	
	$pStmt->close();
	
	//get all the classics proposals
	$cStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary`,`emailOK`,`contact` FROM `classics_proposals` WHERE 1 ORDER BY `id`");
	$cStmt->execute();
	$cStmt->bind_result($cID,$cTitle,$cPresenters,$cSummary,$cEmailOK,$cContact);
	
	$classics_proposals = array();
	while($cStmt->fetch()) {
		$classics_proposals[] = array(
			"id" => $cID,
			"title" => $cTitle,
			"presenters" => $cPresenters,
			"summary" => $cSummary,
			"emailOK" => $cEmailOK,
			"contact" => $cContact,
			"type" => 'Technology Fair Classics',
			"status" => 'accepted',
			"session" => 0
		);
	}
	
	$cStmt->close();
	
	
	//get all the other proposals
	$oStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `other_proposals` WHERE 1 ORDER BY `id`");
	$oStmt->execute();
	$oStmt->bind_result($oID,$oTitle,$oPresenters,$oSummary);
	
	$other_proposals = array();
	while($oStmt->fetch()) {
		$other_proposals[] = array(
			"id" => $oID,
			"title" => $oTitle,
			"presenters" => $oPresenters,
			"summary" => $oSummary,
			"type" => 'Other',
			"status" => 'accepted',
			"session" => 0
		);
	}
	
	$oStmt->close();
	
	
	//get all the presenters
	$prStmt = $db->prepare("SELECT `id`,`First Name`,`Last Name`,`Email` FROM `presenters` WHERE 1");
	$prStmt->execute();
	$prStmt->bind_result($prID,$prFirstName,$prLastName,$prEmail);
	
	$presenters = array();
	while($prStmt->fetch()) {
		$presenters[] = array(
			"id" => $prID,
			"first_name" => $prFirstName,
			"last_name" => $prLastName,
			"email" => $prEmail,
			"role" => ""
		);
	}
	
	$prStmt->close();
		
	//get all the classics presenters
	$cprStmt = $db->prepare("SELECT `id`,`First Name`,`Last Name`,`Email` FROM `classics_presenters` WHERE 1");
	$cprStmt->execute();
	$cprStmt->bind_result($cprID,$cprFirstName,$cprLastName,$cprEmail);
	
	$classics_presenters = array();
	while($cprStmt->fetch()) {
		$classics_presenters[] = array(
			"id" => $cprID,
			"first_name" => $cprFirstName,
			"last_name" => $cprLastName,
			"email" => $cprEmail
		);
	}
	
	$cprStmt->close();
		
	//get all the other presenters
	$oprStmt = $db->prepare("SELECT `id`,`First Name`,`Last Name`,`Email` FROM `other_presenters` WHERE 1");
	$oprStmt->execute();
	$oprStmt->bind_result($oprID,$oprFirstName,$oprLastName,$oprEmail);
	
	$other_presenters = array();
	while($oprStmt->fetch()) {
		$other_presenters[] = array(
			"id" => $oprID,
			"first_name" => $oprFirstName,
			"last_name" => $oprLastName,
			"email" => $oprEmail
		);
	}
	
	$oprStmt->close();
		
	//get the schedule
	$sStmt = $db->prepare("SELECT `id`,`location`,`date`,`time`,`event`,`title`,`presentations` FROM `sessions` WHERE 1");
	$sStmt->execute();
	$sStmt->bind_result($sID,$sLocation,$sDate,$sTime,$sEvent,$sTitle,$sPresentations);
	
	$sessions = array();
	while($sStmt->fetch()) {
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
	
	//update the proposals array with the presenters information
	for($p = 0; $p < count($proposals); $p++) {
		$tmpPres = explode("|", $proposals[$p]["presenters"]);
		$thisPresenters = array();
		for($i = 0; $i < count($tmpPres); $i++) {
			for($j = 0; $j < count($presenters); $j++) {
				if($tmpPres[$i] == $presenters[$j]["id"]) {
					$thisPresenterStr = $presenters[$j]["id"]."|".$presenters[$j]["first_name"]."|".$presenters[$j]["last_name"]."|".$presenters[$j]["email"]."|";
					if($presenters[$j]["email"] == $proposals[$p]["contact"]) $thisPresenterStr .= "main";
					$thisPresenters[] = $thisPresenterStr;
					break; //the presenters loop
				}
			}
		}
		
		$proposals[$p]["presenters"] = $thisPresenters;
	}
	
	//update the classics proposals array with the presenters information
	for($cp = 0; $cp < count($classics_proposals); $cp++) {
		$tmpPres = explode("|", $classics_proposals[$cp]["presenters"]);
		$thisPresenters = array();
		for($i = 0; $i < count($tmpPres); $i++) {
			for($j = 0; $j < count($classics_presenters); $j++) {
				if($tmpPres[$i] == $classics_presenters[$j]["id"]) {
					$thisPresenterStr = $classics_presenters[$j]["id"]."|".$classics_presenters[$j]["first_name"]."|".$classics_presenters[$j]["last_name"]."|".$classics_presenters[$j]["email"]."|";
					if($classics_presenters[$j]["email"] == $classics_proposals[$cp]["contact"]) $thisPresenterStr .= "main";
					$thisPresenters[] = $thisPresenterStr;
					break; //the presenters loop
				}
			}
		}
		
		$classics_proposals[$cp]["presenters"] = $thisPresenters;
	}
	
	//update the other proposals array with the presenters information
	for($op = 0; $op < count($other_proposals); $op++) {
		$tmpPres = explode("|", $other_proposals[$op]["presenters"]);
		$thisPresenters = array();
		for($i = 0; $i < count($tmpPres); $i++) {
			for($j = 0; $j < count($other_presenters); $j++) {
				if($tmpPres[$i] == $other_presenters[$j]["id"]) {
					$thisPresenterStr = $other_presenters[$j]["id"]."|".$other_presenters[$j]["first_name"]."|".$other_presenters[$j]["last_name"]."|".$other_presenters[$j]["email"]."|";
					//if($other_presenters[$j]["email"] == $other_proposals[$op]["contact"]) $thisPresenterStr .= "main";
					$thisPresenters[] = $thisPresenterStr;
					break; //the presenters loop
				}
			}
		}
		
		$other_proposals[$op]["presenters"] = $thisPresenters;
	}
	
	//get the station names
	$stStmt = $db->prepare("SELECT `id`,`name` FROM `stations` WHERE 1");
	$stStmt->execute();
	$stStmt->bind_result($stID,$stName);
	$stations = array();
	while($stStmt->fetch()) {
		$stations[] = array(
			"id" => $stID,
			"name" => $stName
		);
	}
	
	//update the proposals array with the schedule information
	for($p = 0; $p < count($proposals); $p++) {
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] == "Technology Fairs (Classics)" || $sessions[$i]["event"] == "Other") continue; //skip the classics and the other sessions for now
			$tmpSes = explode("||",$sessions[$i]["presentations"]);
			for($j = 0; $j < count($tmpSes); $j++) {
				if(strpos($tmpSes[$j],"|") !== false) { //there is a station (ev fairs or mini-workshops)
					$tmpP = explode("|",$tmpSes[$j]);
					if($tmpP[1] == $proposals[$p]["id"]) { //this proposal is in the current session
						$proposals[$p]["session"] = $sessions[$i]["date"]."|".$sessions[$i]["time"];
						if($proposals[$p]["type"] == "Technology Fairs") {
							for($k = 0; $k < count($stations); $k++) {
								if($stations[$k]["id"] == $tmpP[0]) { //this is the right station
									$proposals[$p]["session"] .= "|".$stations[$k]["name"];
									break; //stations loop
								}
							}
						}
						$proposals[$p]["status"] = "scheduled";
						
						break; //presentations loop
						break; //sessions loop
					}
				} else { //no station information
					if($tmpSes[$j] == $proposals[$p]["id"]) { //this proposal is in the current session
						$proposals[$p]["session"] = $sessions[$i]["date"]."|".$sessions[$i]["time"];
						$proposals[$p]["status"] = "scheduled";
						
						break; //presentations loop
						break; //sessions loop
					}
				}
			}
		}
	}
	
	//update the classics_proposals array with the schedule information
	for($cp = 0; $cp < count($classics_proposals); $cp++) {
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] != "Technology Fairs (Classics)") continue; //skip any non-Classics sessions
			$tmpSes = explode("||",$sessions[$i]["presentations"]);
			for($j = 0; $j < count($tmpSes); $j++) {
				$tmpP = explode("|",$tmpSes[$j]);
				if($tmpP[1] == $classics_proposals[$cp]["id"]) { //this proposal is in the current session
					$classics_proposals[$cp]["session"] = $sessions[$i]["date"]."|".$sessions[$i]["time"];
					for($k = 0; $k < count($stations); $k++) {
						if($stations[$k]["id"] == $tmpP[0]) { //this is the right station
							$classics_proposals[$cp]["session"] .= "|".$stations[$k]["name"];
							break; //stations loop
						}
					}
					$classics_proposals[$cp]["status"] = "scheduled";
						
					break; //presentations loop
					break; //sessions loop
				}
			}
		}
	}
	
	//update the other_proposals array with the schedule information
	for($op = 0; $op < count($other_proposals); $op++) {
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] != "Other") continue; //skip any non-Other sessions
			$tmpSes = explode("||",$sessions[$i]["presentations"]);
			for($j = 0; $j < count($tmpSes); $j++) {
				if($tmpSes[$j] == $other_proposals[$op]["id"]) { //this proposal is in the current session
					$other_proposals[$p]["session"] = $sessions[$i]["date"]."|".$sessions[$i]["time"];
					$other_proposals[$p]["status"] = "scheduled";
						
					break; //presentations loop
					break; //sessions loop
				}
			}
		}
	}	

	//Now, separate the proposals into the right groups
	//Groups are by event type, then accepted and scheduled, accepted but not scheduled, and rejected

	$propList = array(); //one array to hold everything
	for($p = 0; $p < count($proposals); $p++) {
		$tE = $proposals[$p]["type"];
		
		//check to see if this event exists already
		if(!array_key_exists($tE,$propList)) $propList[$tE] = array(); //create the subarray for this event
		
		if($proposals[$p]["status"] == "accepted") {
			if($proposals[$p]["session"] == 0) $tS = "accepted";
			else $tS = "scheduled";
		} else $tS = $proposals[$p]["status"];

		//check to see if this status exists already
		if(!array_key_exists($tS,$propList[$tE])) $propList[$tE][$tS] = array();
			
		$propList[$tE][$tS][] = $proposals[$p];
	}
	
	if(count($classics_proposals) > 0) {
		$tE = 'Technology Fair Classics';
		$propList[$tE] = array();
		for($cp = 0; $cp < count($classics_proposals); $cp++) {
			$tS = $classics_proposals[$cp]['status'];
			if(!array_key_exists($tS, $propList[$tE])) $propList[$tE][$tS] = array();
			$propList[$tE][$tS][] = $classics_proposals[$cp];
		}
	}
	
	//echo "<pre>";
	//print_r($propList);
	//echo "</pre>";
	//exit();
	
?>
<html>
	<head>
		<title>Proposal Status -- Electronic Village</title>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}

			span.label {
				color: green;
				font-size: 9pt;
				font-style: italic;
			}	
						
			a {
				text-decoration: none;
				border-bottom: dashed 1px #CCCCCC;
				color: #0066CC;
			}
			
			a:hover {
				border-bottom: solid 1px #0066CC;
			}

			th.sList {
				background-color: #333333;
				color: #FFFFFF;
				font-size: .85em;
				text-align: left;
				padding: 5px;
			}
		
			td.sList_rowEven {
				background-color: #FFFFFF;
				color: #000000;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;
			}

			td.sList_rowOdd {
				background-color: #CCCCCC;
				color: #000000;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;
			}
				
			td.sList_highlighted {
				background-color: #333333;
				color: #FFFFFF;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;
			}
			
			div#notificationDiv {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: auto;
				background-color: #FFFFFF;
				padding: 50px;
			}
		</style>
		<script type="text/javascript">
			var year = '<?php echo $y; ?>';
			var cLocation = '<?php echo $cLocation; ?>';
			var cDates = '<?php echo $cDates; ?>';
			var cURL = '<?php echo $cURL; ?>';
			var evFairLeads = '<?php echo $evFairsLeadName; ?>';
			var evMiniLeads = '<?php echo $evMiniLeadName; ?>';
			var evDSLeads = '<?php echo $evDSLeadName; ?>';
			var evMAELeads = '<?php echo $evMAELeadName; ?>';
			var evHTLeads = '<?php echo $evHTLeadName; ?>';
			var evGradLeads = '<?php echo $evGradLeadName; ?>';
			var evClassicsLeads = '<?php echo $evClassicsLeadName; ?>';

			var selectedProps = new Array();
			var proposals = new Array();
			var events = new Array();
<?php
	$eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Hot Topics","Graduate Student Research","Technology Fair Classics","Other");
	for($e = 0; $e < count($eTypes); $e++) {
?>
		events[<?php echo $e?>] = '<?php echo $eTypes[$e]; ?>';
<?php
	}
?>
		
<?php
	$pC = 0;
	for($e = 0; $e < count($eTypes); $e++) {
		$tE = $eTypes[$e];
		if(isset($propList[$tE])) {
			if($tE == "Technology Fair Classics") $thisPresenters = $classics_presenters;
			$pStatus = array("scheduled","accepted","rejected");
			for($s = 0; $s < count($pStatus); $s++) {
				if(!array_key_exists($pStatus[$s],$propList[$tE])) continue;
				$tS = $pStatus[$s];
				for($i = 0; $i < count($propList[$tE][$tS]); $i++) {
					if(isset($contactEmail) && $propList[$tE][$tS][$i]["contact"] == $contactEmail) {
?>
			selectedProps[<?php echo $pC; ?>] = new Array();
			selectedProps[<?php echo $pC; ?>]['id'] = '<?php echo $propList[$tE][$tS][$i]["id"]; ?>';
			selectedProps[<?php echo $pC; ?>]['title'] = '<?php echo addslashes(stripslashes(trim($propList[$tE][$tS][$i]["title"]))); ?>';
			selectedProps[<?php echo $pC; ?>]['contact'] = '<?php echo $propList[$tE][$tS][$i]["contact"]; ?>';
			selectedProps[<?php echo $pC; ?>]['presenters'] = new Array();
<?php
						for($j = 0; $j < count($propList[$tE][$tS][$i]["presenters"]); $j++) {
							$thisP = explode("|",$propList[$tE][$tS][$i]["presenters"][$j]);
?>
			selectedProps[<?php echo $pC; ?>]['presenters'][<?php echo $j; ?>] = new Array();
			selectedProps[<?php echo $pC; ?>]['presenters'][<?php echo $j; ?>]['id'] = '<?php echo $thisP[0]; ?>';
			selectedProps[<?php echo $pC; ?>]['presenters'][<?php echo $j; ?>]['first_name'] = '<?php echo addslashes(stripslashes(trim($thisP[1]))); ?>';
			selectedProps[<?php echo $pC; ?>]['presenters'][<?php echo $j; ?>]['last_name'] = '<?php echo addslashes(stripslashes(trim($thisP[2]))); ?>';
			selectedProps[<?php echo $pC; ?>]['presenters'][<?php echo $j; ?>]['email'] = '<?php echo trim($thisP[3]); ?>';
			selectedProps[<?php echo $pC; ?>]['presenters'][<?php echo $j; ?>]['role'] = '<?php echo $thisP[4]; ?>';
<?php
						}
					
						if(isset($propList[$tE][$tS][$i]["abstract"])) {
?>
			selectedProps[<?php echo $pC; ?>]['abstract'] = '<?php echo addslashes(stripslashes(trim(preg_replace("/\\n|\\r\\n|\\r/","<br>",$propList[$tE][$tS][$i]["abstract"])))); ?>';
<?php
						}
?>
			selectedProps[<?php echo $pC; ?>]['summary'] = '<?php echo addslashes(stripslashes(trim(preg_replace("/\\n|\\r\\n|\\r/","<br>",$propList[$tE][$tS][$i]["summary"])))); ?>';
			selectedProps[<?php echo $pC; ?>]['type'] = '<?php echo trim($propList[$tE][$tS][$i]["type"]); ?>';
			selectedProps[<?php echo $pC; ?>]['status'] = '<?php echo trim($propList[$tE][$tS][$i]["status"]); ?>';
			selectedProps[<?php echo $pC; ?>]['session'] = '<?php echo trim($propList[$tE][$tS][$i]["session"]); ?>';
			
<?php
						$pC++;
					}
				}
			}
		}
	}
?>
			
			//This function searches the proposals array for the given email and adds the proposals it finds to the selectedProps array
			function searchProps() {
				//First, clear out the selectedProps array
				selectedProps = new Array(0);
				
				//Now, get the email address to search for
				var email = document.getElementById('contactInput').value;
				//alert(email);
				
				if(!validateEmail(email)) {
					alert('Please enter a valid email address!');
					document.getElementById('contactInput').value = '';
					document.getElementById('contactInput').focus();
					return false;
				}
				
				//Now, search the proposals array
				var sI = 0;
				for(var i = 0; i < proposals.length; i++) {
					if(proposals[i]['contact'].toLowerCase() == email.toLowerCase()) {
						if(proposals[i]['type'] != 'Classroom of the Future') { //Classroom of the Future proposals are not decided by us
							selectedProps[sI] = proposals[i];
							sI++;
						}
					}
				}
				
				//Now, display the selected proposals
				showProps();				
			}
			
			//This function displays the data in the selectedProps array for the user
			function showProps() {
				//Clear out any existing prop data
				document.getElementById('propDataDiv').innerHTML = '';
				
				//Add the new prop data
				if(selectedProps.length > 0) {
					var pdStr = '<table border="0" align="center" cellpadding="5" width="800"><tr><th class="sList">Title</th><th class="sList">Presenters</th></tr>';
					
					var rN = 0;
					for(var i = 0; i < selectedProps.length; i++) {
						if(rN % 2 == 0) var tdClass = 'sList_rowOdd';
						else var tdClass = 'sList_rowEven';
						
						pdStr += '<tr id="row' + rN + '"><td class="' + tdClass + '" onMouseOver="highlightRow(\'row' + rN + '\',1)" onMouseOut="highlightRow(\'row' + rN + '\',0)" onClick="showStatus(\'' + selectedProps[i]['id'] + '\',\'' + selectedProps[i]['type'] + '\')">' + selectedProps[i]['title'] + '</td><td class="' + tdClass + '" onMouseOver="highlightRow(\'row' + rN + '\',1)" onMouseOut="highlightRow(\'row' + rN + '\',0)">';
						if(selectedProps[i]['presenters'].length > 1) {
							pdStr += '<ol style="margin: 0; padding-left: 15px">';
							for(var j = 0; j < selectedProps[i]['presenters'].length; j++) {
								pdStr += '<li style="padding-left: 0px">' + selectedProps[i]['presenters'][j]['first_name'] + ' ' + selectedProps[i]['presenters'][j]['last_name'] + '</li>';
							}
							pdStr += '</ol>';
						} else pdStr += selectedProps[i]['presenters'][0]['first_name'] + ' ' + selectedProps[i]['presenters'][0]['last_name'];
						
						pdStr += '</td></tr>';
						rN++;
					}
					
					pdStr += '</table>';
				} else var pdStr = '<p style="color:red; font-weight: bold; text-align: center">No proposals found with that email address!</p>';
				
				pdStr += '<p style="text-align: center"><input type="button" value="Search Again" onclick="showSearch()" style="font-size: 12pt; font-weight: bold; height: 30px; width: 200px; background-color: #CCCCCC; border: solid 1px #000000; border-radius: 5px;" /></p>';
				
				document.getElementById('propDataDiv').innerHTML = pdStr;
				document.getElementById('enterEmailDiv').style.display = 'none';
				document.getElementById('propDiv').style.display = '';
			}
			
			function showSearch() {
				document.getElementById('propDiv').style.display = 'none';
				document.getElementById('enterEmailDiv').style.display = '';
				document.getElementById('contactInput').value = '';
				document.getElementById('contactInput').focus();
			}

			function validateEmail(email) { 
				var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
				return re.test(email);
			} 

			function highlightRow(elStr,n) {
				var rEl = document.getElementById(elStr);
				var nS = parseInt(rEl.id.indexOf('row') + parseInt(3));
				var r = rEl.id.substring(nS,rEl.id.length);
				for(i = 0; i < rEl.cells.length; i++) {
					var cEl = rEl.cells[i];
					if(n == 1) cEl.className = 'sList_highlighted';
					else if(n == 0) {
						if(parseInt(r) % 2 == 0) cEl.className = 'sList_rowEven';
						else cEl.className = 'sList_rowOdd';
					}
				}
			}
			
			function showStatus(n,t) {
				//First, find the proposal in the array
				for(var i = 0; i < selectedProps.length; i++) {
					if(selectedProps[i]['id'] == n && selectedProps[i]['type'] == t) { //found the right one, so get the data
						if(selectedProps[i]['type'] == 'Technology Fairs' && selectedProps[i]['session'] != 0) {
							var nStr = document.getElementById('evFairsScheduleDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-fair@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Acceptance Notice for Electronic Village [INSERT YEAR] Technology Fairs Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evFairLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Technology Fairs' && selectedProps[i]['status'] == 'rejected') {
							var nStr = document.getElementById('evFairsRejectDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-fair@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Decision Notice for Electronic Village [INSERT YEAR] Technology Fairs Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evFairLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Technology Fairs' && selectedProps[i]['status'] == 'accepted') {
							var nStr = document.getElementById('evFairsAcceptDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-fair@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Waiting List Notice for Electronic Village [INSERT YEAR] Technology Fairs Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evFairLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Mini-Workshops' && selectedProps[i]['session'] != 0) {
							var nStr = document.getElementById('evMiniScheduleDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-mini@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Acceptance Notice for Electronic Village [INSERT YEAR] Mini-Workshops Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evMiniLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Mini-Workshops' && selectedProps[i]['status'] == 'rejected') {
							var nStr = document.getElementById('evMiniRejectDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-mini@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Decision Notice for Electronic Village [INSERT YEAR] Mini-Workshops Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evMiniLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Mini-Workshops' && selectedProps[i]['status'] == 'accepted') {
							var nStr = document.getElementById('evMiniAcceptDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-mini@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Waiting List Notice for Electronic Village [INSERT YEAR] Mini-Workshops Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evMiniLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Developers Showcase' && selectedProps[i]['session'] != 0) {
							var nStr = document.getElementById('evDSScheduleDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-ds@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Acceptance Notice for Electronic Village [INSERT YEAR] Developers Showcase Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evDSLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Developers Showcase' && selectedProps[i]['status'] == 'rejected') {
							var nStr = document.getElementById('evDSRejectDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-ds@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Decision Notice for Electronic Village [INSERT YEAR] Developers Showcase Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evDSLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Developers Showcase' && selectedProps[i]['status'] == 'accepted') {
							var nStr = document.getElementById('evDSAcceptDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-ds@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Waiting List Notice for Electronic Village [INSERT YEAR] Developers Showcase Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evDSLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Mobile Apps for Education Showcase' && selectedProps[i]['session'] != 0) {
							var nStr = document.getElementById('evMAEScheduleDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-mae@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Acceptance Notice for Electronic Village [INSERT YEAR] Mobile Apps for Education Showcase Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evMAELeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Mobile Apps for Education Showcase' && selectedProps[i]['status'] == 'rejected') {
							var nStr = document.getElementById('evMAERejectDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-mae@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Decision Notice for Electronic Village [INSERT YEAR] Mobile Apps for Education Showcase Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evMAELeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Mobile Apps for Education Showcase' && selectedProps[i]['status'] == 'accepted') {
							var nStr = document.getElementById('evMAEAcceptDiv').innerHTML;
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-mae@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Waiting List Notice for Electronic Village [INSERT YEAR] Mobile Apps for Education Showcase Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evMAELeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Hot Topics' && selectedProps[i]['session'] != 0) {
							var nStr = document.getElementById('evHTScheduleDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-ht@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Acceptance Notice for Electronic Village [INSERT YEAR] Hot Topics Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evHTLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Hot Topics' && selectedProps[i]['status'] == 'rejected') {
							var nStr = document.getElementById('evHTRejectDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-ht@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Decision Notice for Electronic Village [INSERT YEAR] Hot Topics Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evHTLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Hot Topics' && selectedProps[i]['status'] == 'accepted') {
							var nStr = document.getElementById('evHTAcceptDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-ht@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Waiting List Notice for Electronic Village [INSERT YEAR] Hot Topics Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evHTLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Graduate Student Research' && selectedProps[i]['session'] != 0) {
							var nStr = document.getElementById('evGradScheduleDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-grad@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Acceptance Notice for Electronic Village [INSERT YEAR] Graduate Student Research Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evGradLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Graduate Student Research' && selectedProps[i]['status'] == 'rejected') {
							var nStr = document.getElementById('evGradRejectDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-grad@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Decision Notice for Electronic Village [INSERT YEAR] Graduate Student Research Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evGradLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Graduate Student Research' && selectedProps[i]['status'] == 'accepted') {
							var nStr = document.getElementById('evGradAcceptDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-grad@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Waiting List Notice for Electronic Village [INSERT YEAR] Graduate Student Research Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evGradLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id']);
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id']);
						} else if(selectedProps[i]['type'] == 'Technology Fair Classics' && selectedProps[i]['session'] != 0) {
							var nStr = document.getElementById('evClassicsScheduleDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-classics@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Schedule Notice for Electronic Village [INSERT YEAR] Technology Fairs (Classics) Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evClassicsLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id'] + '&classics=1');
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id'] + '&classics=1');
						} else if(selectedProps[i]['type'] == 'Technology Fair Classics' && selectedProps[i]['status'] == 'rejected') {
							var nStr = document.getElementById('evClassicsRejectDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-classics@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Decision Notice for Electronic Village [INSERT YEAR] Technology Fairs (Classics) Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evClassicsLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id'] + '&classics=1');
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id'] + '&classics=1');
						} else if(selectedProps[i]['type'] == 'Technology Fair Classics' && selectedProps[i]['status'] == 'accepted') {
							var nStr = document.getElementById('evClassicsAcceptDiv').innerHTML; //scheduled proposal
							nStr = nStr.replace(/\[INSERT FROM\]/g,'ev-classics@call-is.org');
							nStr = nStr.replace(/\[INSERT SUBJECT\]/g,'Waiting List Notice for Electronic Village [INSERT YEAR] Graduate Student Research Submission');
							nStr = nStr.replace(/\[INSERT LEAD NAME\]/g,evClassicsLeads);
							nStr = nStr.replace(/\[INSERT CONFIRM LINK\]/g,'<?php echo $confirmLink; ?>' + selectedProps[i]['id'] + '&classics=1');
							nStr = nStr.replace(/\[INSERT CERTIFICATE LINK\]/g, '<?php echo $certificateLink; ?>' + selectedProps[i]['id'] + '&classics=1');
						}

						
						nStr = nStr.replace(/\[INSERT YEAR\]/g,year);
						nStr = nStr.replace(/\[INSERT DATES\]/g, cDates);
						nStr = nStr.replace(/\[INSERT LOCATION\]/g, cLocation);
						nStr = nStr.replace(/\[INSERT TITLE\]/g, selectedProps[i]['title']);
						nStr = nStr.replace(/\[INSERT CONTACT\]/g, selectedProps[i]['contact']);
						nStr = nStr.replace(/\[INSERT ABSTRACT\]/g, selectedProps[i]['abstract']);
						nStr = nStr.replace(/\[INSERT SUMMARY\]/g, selectedProps[i]['summary']);
						nStr = nStr.replace(/\[INSERT CONFIRM DEADLINE\]/g, '<?php echo $confirmDate; ?>');
												
						//Now get the presenters information
						var presStr = '';
						for(var j = 0; j < selectedProps[i]['presenters'].length; j++) {
							presStr += selectedProps[i]['presenters'][j]['first_name'] + ' ' + selectedProps[i]['presenters'][j]['last_name'] + ' (' + selectedProps[i]['presenters'][j]['email'] + ')';
							
							if(j < (selectedProps[i]['presenters'].length - 1)) presStr += '<br>';
						}
						
						nStr = nStr.replace(/\[INSERT PRESENTERS HTML\]/g,presStr);
						
						if(selectedProps[i]['session'] != 0) {
							//Now, get the schedule information
							var tmpS = selectedProps[i]['session'].split('|');
							var tmpD = tmpS[0].split('-');
						
							var months = new Array('','January','February','March','April','May','June','July','August','September','October','November','December');
							var m = parseInt(tmpD[1]);
							var d = parseInt(tmpD[2]);
							var y = parseInt(tmpD[0]);
						
							nStr = nStr.replace(/\[INSERT SESSION DATE\]/g,months[m] + ' ' + d + ', ' + y);
						
							var tmpT = tmpS[1].split('-');
							var sT = tmpT[0].split(':');
							var sH = parseInt(sT[0]);
							if(sH < 12) var sAMPM = 'AM';
							else {
								var sAMPM = 'PM';
								if(sH > 12) sH = sH - 12;
							}
						
							var sM = sT[1];
						
							var eT = tmpT[1].split(':');
							var eH = parseInt(eT[0]);
							if(eH < 12) var eAMPM = 'AM';
							else {
								var eAMPM = 'PM';
								if(eH > 12) eH = eH - 12;
							}
						
							var eM = eT[1];
						
							nStr = nStr.replace(/\[INSERT SESSION TIME\]/g,sH + ':' + sM + ' ' + sAMPM + ' to ' + eH + ':' + eM + ' ' + eAMPM);
						
							if(tmpS[2] != '') var stationStr = tmpS[2]; //there is a station to include
							else var stationStr = '';
						
							nStr = nStr.replace(/\[INSERT STATION\]/g,stationStr);
						}
						
						document.getElementById('notificationDiv').innerHTML = nStr;
						document.getElementById('notificationDiv').style.display = '';
						break;
					}
				}
			}
			
			function hideStatus() {
				document.getElementById('notificationDiv').style.display = 'none';
			}
			
			function checkEnter(e) {
				e.preventDefault();
				e.stopPropagation();
				
				if(!e) e = window.event;
				var keyCode = e.keyCode || e.which;
				if(keyCode === 13) {
					document.getElementById('loginForm').submit();
				}
			}
		</script>
	</head>
	
	<body onload="<?php if(isset($contactEmail)) { ?>showProps()<?php } else { ?>document.getElementById('contactInput').focus()<?php } ?>">
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $y; ?>)<br /><br /><span style="font-size: 18pt; font-weight: bold">Proposal Status</span></td>
			</tr>
			<tr>
				<td>
					<div id="enterEmailDiv">
						<p>Please enter the email address used to submit your proposal. On your submission confirmation email, this is listed as the "Main Contact". If you are not sure which email address you used to submit your proposal, please contact <a href="mailto:ev@call-is.org">ev@call-is.org</a> and provide your name and the title of your proposal.</p>
						<form name="loginForm" id="loginForm" method="post" action="">
							<table border="0" align="center">
								<tr>
									<td style="font-weight :bold; font-size: 12pt">Main contact email:</td>
									<td><input type="text" name="contact" id="contactInput" style="height: 30px; font-size: 12pt; padding-left: 10px; paddingright: 10px;" onkeyup="checkEnter(event)"/></td>
								</tr>
								<tr>
									<td colspan="2" align="center"><br /><input type="button" value="Check Status" onclick="document.getElementById('loginForm').submit()" style="font-size: 12pt; font-weight: bold; height: 30px; width: 200px; background-color: #CCCCCC; border: solid 1px #000000; border-radius: 5px;" />
								</tr>
							</table>
						</form>
					</div>
					<div id="propDiv" style="display: none">
						<p>Below is a list of the proposals with the email you entered as the <b>main contact</b>. Click on a specific proposal to see the notification email that was sent for this proposal.</p>
						<div id="propDataDiv">
						
						</div>
					</div>
				</td>
			</tr>
		</table>
		<div id="notificationDiv" style="display: none"></div>
<?php
	foreach($events AS $e) {
		$event = $e['event'];
		$eAbbr = str_replace("_", "", $e['adminSuffix']);
		if(strlen($eAbbr) <= 3) $eAbbr = strtoupper($eAbbr);
		else $eAbbr = ucwords($eAbbr);
?>
		<div id="ev<?php echo $eAbbr; ?>ScheduleDiv" style="display: none">
			<table border="0" align="center" cellspacing="0" cellpadding="5" width="100%" style="border-bottom: solid 1px #CCCCCC; margin-bottom: 50px;">
				<tr>
					<td style="font-weight: bold">To:</td>
					<td>[INSERT CONTACT]</td>
				</tr>
				<tr>
					<td style="font-weight: bold">From:</td>
					<td>[INSERT FROM]</td>
				</tr>
				<tr>
					<td style="font-weight: bold">Subject:</td>
					<td>[INSERT SUBJECT]</td>
				</tr>
			</table>
<?php
		$msgStr = file_get_contents("ev".$eAbbr."ScheduleHTML.txt");
		$editableStr = file_get_contents("admin/ev".$eAbbr."ScheduleEmail_editable.txt");
		$editableStr = str_replace("\n","<br>",$editableStr);
		
		echo str_replace("[INSERT EDITABLE HTML HERE]",$editableStr,$msgStr);
?>
			<p align="center"><input type="button" value="Back to Proposal List" onClick="hideStatus()" style="background-color: #CCCCCC; border: solid 1px #000000; border-radius: 5px; height: 30px; width: 300px; font-size: 12pt"></p>
		</div>
		<div id="ev<?php echo $eAbbr; ?>AcceptDiv" style="display: none">
			<table border="0" align="center" cellspacing="0" cellpadding="5" width="100%" style="border-bottom: solid 1px #CCCCCC; margin-bottom: 50px;">
				<tr>
					<td style="font-weight: bold">To:</td>
					<td>[INSERT CONTACT]</td>
				</tr>
				<tr>
					<td style="font-weight: bold">From:</td>
					<td>[INSERT FROM]</td>
				</tr>
				<tr>
					<td style="font-weight: bold">Subject:</td>
					<td>[INSERT SUBJECT]</td>
				</tr>
			</table>
<?php
		$msgStr = file_get_contents("ev".$eAbbr."AcceptHTML.txt");
		$editableStr = file_get_contents("admin/ev".$eAbbr."AcceptEmail_editable.txt");
		$editableStr = str_replace("\n","<br>",$editableStr);
		
		echo str_replace("[INSERT EDITABLE HTML HERE]",$editableStr,$msgStr);
?>
			<p align="center"><input type="button" value="Back to Proposal List" onClick="hideStatus()" style="background-color: #CCCCCC; border: solid 1px #000000; border-radius: 5px; height: 30px; width: 300px; font-size: 12pt"></p>
		</div>
		<div id="ev<?php echo $eAbbr; ?>RejectDiv" style="display: none">
			<table border="0" align="center" cellspacing="0" cellpadding="5" width="100%" style="border-bottom: solid 1px #CCCCCC; margin-bottom: 50px;">
				<tr>
					<td style="font-weight: bold">To:</td>
					<td>[INSERT CONTACT]</td>
				</tr>
				<tr>
					<td style="font-weight: bold">From:</td>
					<td>[INSERT FROM]</td>
				</tr>
				<tr>
					<td style="font-weight: bold">Subject:</td>
					<td>[INSERT SUBJECT]</td>
				</tr>
			</table>
<?php
		$msgStr = file_get_contents("ev".$eAbbr."RejectHTML.txt");
		$editableStr = file_get_contents("admin/ev".$eAbbr."RejectEmail_editable.txt");
		$editableStr = str_replace("\n","<br>",$editableStr);
		
		echo str_replace("[INSERT EDITABLE HTML HERE]",$editableStr,$msgStr);
?>
			<p align="center"><input type="button" value="Back to Proposal List" onClick="hideStatus()" style="background-color: #CCCCCC; border: solid 1px #000000; border-radius: 5px; height: 30px; width: 300px; font-size: 12pt"></p>
		</div>
<?php
	}
?>
	</body>
</html>
