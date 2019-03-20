<?php
	/*
		scheduleCheck.php -- checks the schedule for double-bookings across events and within the same event
		
		The page will show all the schedule sessions. By clicking on a session, the user can see all the scheduled presenters. Presenters who are double-booked will have a red background.
		
		The page will load ALL the proposal data each time it is called, and we will use javascript to display the data we want.
	*/
	
	include_once "login.php";
	
	$topTitle = "Check Schedule";

	//reviewers don't have access to this page
	if(strpos($_SESSION['user_role'],"admin") === false) {
		include "adminTop.php";
?>
	<h3 align="center">You do not have permission to access this page!</h3>
<?php
		include "adminBottom.php";
	}
	
	
	$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
	$y = "2017";
	$cLocation = "Seattle, Washington, USA";
	$cDates = "March 21 - 24, 2017";
	$cURL = "http://www.tesol.org/convention2017";

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
			"session" => "",
			"sessionID" => 0
		);
	}
	
	$pStmt->close();
	
	//get the classics proposals
	$cStmt = $db->prepare("SELECT `id`, `title`, `presenters`, `summary` FROM `classics_proposals` WHERE 1 ORDER BY `id`");
	$cStmt->execute();
	$cStmt->bind_result($cID, $cTitle, $cPresenters, $cSummary);
	
	$classics_proposals = array();
	while($cStmt->fetch()) {
		$classics_proposals[] = array(
			"id" => $cID,
			"title" => $cTitle,
			"presenters" => $cPresenters,
			"summary" => $cSummary,
			"status" => "",
			"session" => "",
			"sessionID" => 0
		);
	}
	
	$cStmt->close();
	
	//get the "other" proposals
	$oStmt = $db->prepare("SELECT `id`, `title`, `presenters`, `summary` FROM `other_proposals` WHERE 1 ORDER BY `id`");
	$oStmt->execute();
	$oStmt->bind_result($oID, $oTitle, $oPresenters, $oSummary);
	
	$other_proposals = array();
	while($oStmt->fetch()) {
		$other_proposals[] = array(
			"id" => $oID,
			"title" => $oTitle,
			"presenters" => $oPresenters,
			"summary" => $oSummary,
			"status" => "",
			"session" => "",
			"sessionID" => 0
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
			"email" => $prEmail
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
	$sStmt = $db->prepare("SELECT * FROM `sessions` WHERE 1 ORDER BY DATE ASC, TIME ASC");
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
	
	//get the station names
	$stStmt = $db->prepare("SELECT * FROM `stations` WHERE 1");
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
			if($sessions[$i]["event"] == "Technology Fairs (Classics)" || $sessions[$i]["event"] == "Other") continue; //skip other and classics sessions
			$tmpSes = explode("||",$sessions[$i]["presentations"]);
			for($j = 0; $j < count($tmpSes); $j++) {
				if(strpos($tmpSes[$j],"|") !== false) { //there is a station (ev fairs or mini-workshops)
					$tmpP = explode("|",$tmpSes[$j]);
					if($tmpP[1] == $proposals[$p]["id"]) { //this proposal is in the current session
						$proposals[$p]["session"] = $sessions[$i]["date"]."|".$sessions[$i]["time"];
						$proposals[$p]["sessionID"] = $sessions[$i]["id"];
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
						$proposals[$p]["sessionID"] = $sessions[$i]["id"];
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
			if($sessions[$i]["event"] != "Technology Fairs (Classics)") continue; //skip all but classics sessions
			$tmpSes = explode("||",$sessions[$i]["presentations"]);
			for($j = 0; $j < count($tmpSes); $j++) {
				$tmpP = explode("|",$tmpSes[$j]);
				if($tmpP[1] == $classics_proposals[$cp]["id"]) { //this proposal is in the current session
					$classics_proposals[$cp]["session"] = $sessions[$i]["date"]."|".$sessions[$i]["time"];
					$classics_proposals[$cp]["sessionID"] = $sessions[$i]["id"];
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
			if($sessions[$i]["event"] != "Other") continue; //skip session not for the "other" event type
			$tmpSes = explode("||",$sessions[$i]["presentations"]);
			for($j = 0; $j < count($tmpSes); $j++) {
				if($tmpSes[$j] == $other_proposals[$op]["id"]) { //this proposal is in the current session
					$other_proposals[$op]["session"] = $sessions[$i]["date"]."|".$sessions[$i]["time"];
					$other_proposals[$op]["sessionID"] = $sessions[$i]["id"];
					$other_proposals[$op]["status"] = "scheduled";
						
					break; //presentations loop
					break; //sessions loop
				}
			}
		}
	}
	
	
	//Now, get the presenters information into the proposals array
	for($p = 0; $p < count($proposals); $p++) {
		$tmpPres = explode("|",$proposals[$p]["presenters"]);
		$thisPres = array();
		for($i = 0; $i < count($tmpPres); $i++) {
			for($j = 0; $j < count($presenters); $j++) {
				if($tmpPres[$i] == $presenters[$j]["id"]) {
					$tp = $presenters[$j];
					if($tp['email'] == $proposals[$p]["contact"]) $tpRole = "main";
					else $tpRole = "";
					
					$thisPres[] = $tp['id']."|".$tp['first_name']."|".$tp['last_name']."|".$tp['email']."|".$tpRole;
					break;
				}
			}
		}
		
		$proposals[$p]["presenters"] = $thisPres;
	}
	
	
	//Now, get the classics presenters information into the classics_proposals array
	for($cp = 0; $cp < count($classics_proposals); $cp++) {
		$tmpPres = explode("|",$classics_proposals[$cp]["presenters"]);
		$thisPres = array();
		for($i = 0; $i < count($tmpPres); $i++) {
			for($j = 0; $j < count($classics_presenters); $j++) {
				if($tmpPres[$i] == $classics_presenters[$j]["id"]) {
					$tp = $classics_presenters[$j];
					
					$thisPres[] = $tp['id']."|".$tp['first_name']."|".$tp['last_name']."|".$tp['email'];
					break;
				}
			}
		}
		
		$classics_proposals[$cp]["presenters"] = $thisPres;
	}
	
	
	//Now, get the other presenters information into the other_proposals array
	for($op = 0; $op < count($other_proposals); $op++) {
		$tmpPres = explode("|",$other_proposals[$op]["presenters"]);
		$thisPres = array();
		for($i = 0; $i < count($tmpPres); $i++) {
			for($j = 0; $j < count($other_presenters); $j++) {
				if($tmpPres[$i] == $other_presenters[$j]["id"]) {
					$tp = $other_presenters[$j];
					
					$thisPres[] = $tp['id']."|".$tp['first_name']."|".$tp['last_name']."|".$tp['email'];
					break;
				}
			}
		}
		
		$other_proposals[$op]["presenters"] = $thisPres;
	}

	include "adminTop.php";
?>
		<style type="text/css">
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
			
			td.sList_problem {
				background-color: #FFCCCC;
				color: #000000;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;			
			}
			
			td.sList_problem_highlighted {
				background-color: #660000;
				color: #FFFFFF;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;
			}
			
			td.sList_okay {
				background-color: #CCFFCC;
				color: #000000;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;			
			}
			
			td.sList_okay_highlighted {
				background-color: #006600;
				color: #FFFFFF;
				font-size: .85em;
				text-align: left;
				vertical-align: top;
				cursor: hand;
				cursor: pointer;
			}
			
			div.presDiv {
				padding-left: 25px;
				padding-right: 5px;
				border-left: dashed 1px #000000;
				border-right: dashed 1px #000000;
				border-bottom: dashed 1px #000000;
			}
		</style>
		<script type="text/javascript">
			var year = '<?=$y?>';
			var cLocation = '<?=$cLocation?>';
			var cDates = '<?=$cDates?>';
			var cURL = '<?=$cURL?>';
			
			var scheduledPresenters = new Array();

			var proposals = new Array();
<?php
	$pI = 0;
	for($i = 0; $i < count($proposals); $i++) {
		if($proposals[$i]["status"] != "scheduled") continue; //skip unscheduled presentations
?>
			proposals[<?=$pI?>] = new Array();
			proposals[<?=$pI?>]['id'] = '<?=$proposals[$i]["id"]?>';
			proposals[<?=$pI?>]['title'] = '<?=addslashes(stripslashes(trim($proposals[$i]["title"])))?>';
			proposals[<?=$pI?>]['contact'] = '<?=$proposals[$i]["contact"]?>';
			proposals[<?=$pI?>]['presenters'] = new Array();
<?php
		for($j = 0; $j < count($proposals[$i]["presenters"]); $j++) {
			$thisP = explode("|",$proposals[$i]["presenters"][$j]);
?>
			proposals[<?=$pI?>]['presenters'][<?=$j?>] = new Array();
			proposals[<?=$pI?>]['presenters'][<?=$j?>]['id'] = '<?=$thisP[0]?>';
			proposals[<?=$pI?>]['presenters'][<?=$j?>]['first_name'] = '<?=addslashes(stripslashes(trim($thisP[1])))?>';
			proposals[<?=$pI?>]['presenters'][<?=$j?>]['last_name'] = '<?=addslashes(stripslashes(trim($thisP[2])))?>';
			proposals[<?=$pI?>]['presenters'][<?=$j?>]['email'] = '<?=trim($thisP[3])?>';
			proposals[<?=$pI?>]['presenters'][<?=$j?>]['role'] = '<?=$thisP[4]?>';
<?php
		}
?>
			proposals[<?=$pI?>]['abstract'] = '<?=addslashes(stripslashes(trim(preg_replace("/\\n|\\r\\n|\\r/","<br>",$proposals[$i]["abstract"]))))?>';
			proposals[<?=$pI?>]['summary'] = '<?=addslashes(stripslashes(trim(preg_replace("/\\n|\\r\\n|\\r/","<br>",$proposals[$i]["summary"]))))?>';
			proposals[<?=$pI?>]['type'] = '<?=trim($proposals[$i]["type"])?>';
			proposals[<?=$pI?>]['status'] = '<?=trim($proposals[$i]["status"])?>';
			proposals[<?=$pI?>]['session'] = '<?=trim($proposals[$i]["session"])?>';
			proposals[<?=$pI?>]['sessionID'] = '<?=trim($proposals[$i]["sessionID"])?>';
			
<?php
		$pI++;
	}
?>

			var classics_proposals = new Array();
<?php
	$cpI = 0;
	for($ci = 0; $ci < count($classics_proposals); $ci++) {
		if($classics_proposals[$ci]["status"] != "scheduled") continue;
?>
			classics_proposals[<?=$cpI?>] = new Array();
			classics_proposals[<?=$cpI?>]['id'] = '<?=$classics_proposals[$ci]["id"]?>';
			classics_proposals[<?=$cpI?>]['title'] = '<?=$classics_proposals[$ci]["title"]?>';
			classics_proposals[<?=$cpI?>]['presenters'] = new Array();
<?php
		for($cj = 0; $cj < count($classics_proposals[$ci]["presenters"]); $cj++) {
			$thisP = explode("|", $classics_proposals[$ci]["presenters"][$cj]);
?>
			classics_proposals[<?=$cpI?>]['presenters'][<?=$cj?>] = new Array();
			classics_proposals[<?=$cpI?>]['presenters'][<?=$cj?>]['id'] = '<?=$thisP[0]?>';
			classics_proposals[<?=$cpI?>]['presenters'][<?=$cj?>]['first_name'] = '<?=addslashes(stripslashes(trim($thisP[1])))?>';
			classics_proposals[<?=$cpI?>]['presenters'][<?=$cj?>]['last_name'] = '<?=addslashes(stripslashes(trim($thisP[2])))?>';
			classics_proposals[<?=$cpI?>]['presenters'][<?=$cj?>]['email'] = '<?=trim($thisP[3])?>';
<?php
		}
?>
			classics_proposals[<?=$cpI?>]['summary'] = '<?=addslashes(stripslashes(trim(preg_replace("/\\n|\\r\\n|\\r/","<br>",$classics_proposals[$ci]["summary"]))))?>';
			classics_proposals[<?=$cpI?>]['type'] = 'Technology Fairs (Classics)';
			classics_proposals[<?=$cpI?>]['status'] = '<?=trim($classics_proposals[$ci]["status"])?>';
			classics_proposals[<?=$cpI?>]['session'] = '<?=trim($classics_proposals[$ci]["session"])?>';
			classics_proposals[<?=$cpI?>]['sessionID'] = '<?=trim($classics_proposals[$ci]["sessionID"])?>';
<?php
		$cpI++;
	}
?>
			
			var other_proposals = new Array();
<?php
	$opI = 0;
	for($oi = 0; $oi < count($other_proposals); $oi++) {
		if($other_proposals[$oi]["status"] != "scheduled") continue;
?>
			other_proposals[<?=$opI?>] = new Array();
			other_proposals[<?=$opI?>]['id'] = '<?=$other_proposals[$oi]["id"]?>';
			other_proposals[<?=$opI?>]['title'] = '<?=$other_proposals[$oi]["title"]?>';
			other_proposals[<?=$opI?>]['presenters'] = new Array();
<?php
		for($oj = 0; $oj < count($other_proposals[$oi]["presenters"]); $oj++) {
			$thisP = explode("|", $other_proposals[$oi]["presenters"][$oj]);
?>
			other_proposals[<?=$opI?>]['presenters'][<?=$oj?>] = new Array();
			other_proposals[<?=$opI?>]['presenters'][<?=$oj?>]['id'] = '<?=$thisP[0]?>';
			other_proposals[<?=$opI?>]['presenters'][<?=$oj?>]['first_name'] = '<?=addslashes(stripslashes(trim($thisP[1])))?>';
			other_proposals[<?=$opI?>]['presenters'][<?=$oj?>]['last_name'] = '<?=addslashes(stripslashes(trim($thisP[2])))?>';
			other_proposals[<?=$opI?>]['presenters'][<?=$oj?>]['email'] = '<?=trim($thisP[3])?>';
<?php
		}
?>
			other_proposals[<?=$opI?>]['summary'] = '<?=addslashes(stripslashes(trim(preg_replace("/\\n|\\r\\n|\\r/","<br>",$other_proposals[$oi]["summary"]))))?>';
			other_proposals[<?=$opI?>]['type'] = 'Technology Fairs (Classics)';
			other_proposals[<?=$opI?>]['status'] = '<?=trim($other_proposals[$oi]["status"])?>';
			other_proposals[<?=$opI?>]['session'] = '<?=trim($other_proposals[$oi]["session"])?>';
			other_proposals[<?=$opI?>]['sessionID'] = '<?=trim($other_proposals[$oi]["sessionID"])?>';
<?php
		$opI++;
	}
?>

			var sessions = new Array();
<?php
	
	for($s = 0; $s < count($sessions); $s++) {
?>
			sessions[<?=$s?>] = new Array();
			sessions[<?=$s?>]['id'] = '<?=trim($sessions[$s]["id"])?>';
			sessions[<?=$s?>]['date'] = '<?=trim($sessions[$s]["date"])?>';
			sessions[<?=$s?>]['time'] = '<?=trim($sessions[$s]["time"])?>';
			sessions[<?=$s?>]['location'] = '<?=trim($sessions[$s]["location"])?>';
			sessions[<?=$s?>]['title'] = '<?=trim($sessions[$s]["title"])?>';
			sessions[<?=$s?>]['event'] = '<?=trim($sessions[$s]["event"])?>';
			sessions[<?=$s?>]['presentations'] = '<?=trim($sessions[$s]["presentations"])?>';
			sessions[<?=$s?>]['db'] = false;
			
<?php
	}
?>
			function getPresenters() { //finds double bookings
				for(var i = 0; i < sessions.length; i++) {
					var tmpS = sessions[i]['presentations'].split('||');
					for(var j = 0; j < tmpS.length; j++) {
						if(tmpS[j].indexOf('|') != -1) { //a station is present
							var tmpSs = tmpS[j].split('|');
							var tmpID = tmpSs[1];
						} else var tmpID = tmpS[j];
						
						if(sessions[i]['event'] != 'Technology Fairs (Classics)' && sessions[i]['event'] != 'Other') {
							for(var p = 0; p < proposals.length; p++) {
								if(proposals[p]['id'] == tmpID) { //found the right proposal
									if(proposals[p]['presenters'].length > 1) {
										for(var pr = 0; pr < proposals[p]['presenters'].length; pr++) {
											var thisEmail = proposals[p]['presenters'][pr]['email'];
											var thisName = proposals[p]['presenters'][pr]['first_name'] + ' ' + proposals[p]['presenters'][pr]['last_name'];
											if(scheduledPresenters.hasOwnProperty(thisEmail)) { //array already exists
												scheduledPresenters[thisEmail].push(proposals[p]['session'] + '||' + proposals[p]['title'] + '||' + proposals[p]['type'] + '||' + proposals[p]['id']);
											} else { //array doesn't exist, so create it
												scheduledPresenters[thisEmail] = new Array();
												scheduledPresenters[thisEmail].push(thisName);
												scheduledPresenters[thisEmail].push(proposals[p]['session'] + '||' + proposals[p]['title'] + '||' + proposals[p]['type'] + '||' + proposals[p]['id']);
											}
										}
									} else {
										var thisEmail = proposals[p]['presenters'][0]['email'];
										var thisName = proposals[p]['presenters'][0]['first_name'] + ' ' + proposals[p]['presenters'][0]['last_name'];
										if(scheduledPresenters.hasOwnProperty(thisEmail)) { //already exists
											scheduledPresenters[thisEmail].push(proposals[p]['session'] + '||' + proposals[p]['title'] + '||' + proposals[p]['type'] + '||' + proposals[p]['id']);
										} else { //doesn't exist
											scheduledPresenters[thisEmail] = new Array();
											scheduledPresenters[thisEmail].push(thisName);
											scheduledPresenters[thisEmail].push(proposals[p]['session'] + '||' + proposals[p]['title'] + '||' + proposals[p]['type'] + '||' + proposals[p]['id']);
										}
									}
									break;
								}
							}
						} else if(sessions[i]['event'] == 'Technology Fairs (Classics)') {
							for(var cp = 0; cp < classics_proposals.length; cp++) {
								if(classics_proposals[cp]['id'] == tmpID) { //found the right proposal
									if(classics_proposals[cp]['presenters'].length > 1) {
										for(var cpr = 0; cpr < classics_proposals[cp]['presenters'].length; cpr++) {
											var thisEmail = classics_proposals[cp]['presenters'][cpr]['email'];
											var thisName = classics_proposals[cp]['presenters'][cpr]['first_name'] + ' ' + classics_proposals[cp]['presenters'][cpr]['last_name'];
											if(scheduledPresenters.hasOwnProperty(thisEmail)) { //array already exists
												scheduledPresenters[thisEmail].push(classics_proposals[cp]['session'] + '||' + classics_proposals[cp]['title'] + '||' + classics_proposals[cp]['type'] + '||' + classics_proposals[cp]['id']);
											} else { //array doesn't exist, so create it
												scheduledPresenters[thisEmail] = new Array();
												scheduledPresenters[thisEmail].push(thisName);
												scheduledPresenters[thisEmail].push(classics_proposals[cp]['session'] + '||' + classics_proposals[cp]['title'] + '||' + classics_proposals[cp]['type'] + '||' + classics_proposals[cp]['id']);
											}
										}
									} else {
										var thisEmail = classics_proposals[cp]['presenters'][0]['email'];
										var thisName = classics_proposals[cp]['presenters'][0]['first_name'] + ' ' + classics_proposals[cp]['presenters'][0]['last_name'];
										if(scheduledPresenters.hasOwnProperty(thisEmail)) { //already exists
											scheduledPresenters[thisEmail].push(classics_proposals[cp]['session'] + '||' + classics_proposals[cp]['title'] + '||' + classics_proposals[cp]['type'] + '||' + classics_proposals[cp]['id']);
										} else { //doesn't exist
											scheduledPresenters[thisEmail] = new Array();
											scheduledPresenters[thisEmail].push(thisName);
											scheduledPresenters[thisEmail].push(classics_proposals[cp]['session'] + '||' + classics_proposals[cp]['title'] + '||' + classics_proposals[cp]['type'] + '||' + classics_proposals[cp]['id']);
										}
									}
									break;
								}
							}
						} else if(sessions[i]['event'] == 'Other') {
							for(var op = 0; op < other_proposals.length; op++) {
								if(other_proposals[op]['id'] == tmpID) { //found the right proposal
									if(other_proposals[op]['presenters'].length > 1) {
										for(var opr = 0; opr < other_proposals[op]['presenters'].length; opr++) {
											var thisEmail = other_proposals[op]['presenters'][opr]['email'];
											var thisName = other_proposals[op]['presenters'][opr]['first_name'] + ' ' + other_proposals[op]['presenters'][opr]['last_name'];
											if(scheduledPresenters.hasOwnProperty(thisEmail)) { //array already exists
												scheduledPresenters[thisEmail].push(other_proposals[op]['session'] + '||' + other_proposals[op]['title'] + '||' + other_proposals[op]['type'] + '||' + other_proposals[op]['id']);
											} else { //array doesn't exist, so create it
												scheduledPresenters[thisEmail] = new Array();
												scheduledPresenters[thisEmail].push(thisName);
												scheduledPresenters[thisEmail].push(other_proposals[op]['session'] + '||' + other_proposals[op]['title'] + '||' + other_proposals[op]['type'] + '||' + other_proposals[op]['id']);
											}
										}
									} else {
										var thisEmail = other_proposals[op]['presenters'][0]['email'];
										var thisName = other_proposals[op]['presenters'][0]['first_name'] + ' ' + other_proposals[op]['presenters'][0]['last_name'];
										if(scheduledPresenters.hasOwnProperty(thisEmail)) { //already exists
											scheduledPresenters[thisEmail].push(other_proposals[op]['session'] + '||' + other_proposals[op]['title'] + '||' + other_proposals[op]['type'] + '||' + other_proposals[op]['id']);
										} else { //doesn't exist
											scheduledPresenters[thisEmail] = new Array();
											scheduledPresenters[thisEmail].push(thisName);
											scheduledPresenters[thisEmail].push(other_proposals[op]['session'] + '||' + other_proposals[op]['title'] + '||' + other_proposals[op]['type'] + '||' + other_proposals[op]['id']);
										}
									}
									break;
								}
							}
						}
					}
				}
				
				console.log(scheduledPresenters);
				
				checkPresenters();
			}
			
			function checkPresenters() {
				//Check for any presenters that are double booked
				for(var k in scheduledPresenters) {
					if(scheduledPresenters[k].length < 3) continue; //skip any presenter assigned to only one presentation
					var tmpDates = new Array();
					for(var i = 1; i < scheduledPresenters[k].length; i++) {
						var tmpP = scheduledPresenters[k][i].split('||');

						var tmpS = tmpP[0].split('|');
						var tmpD = tmpS[0].split('-');
						
						var m = parseInt(tmpD[1]);
						var d = parseInt(tmpD[2]);
						var y = parseInt(tmpD[0]);
						
						var tmpT = tmpS[1].split('-');
						var sT = tmpT[0].split(':');
						var sH = parseInt(sT[0]);
						var sM = parseInt(sT[1]);
						
						var eT = tmpT[1].split(':');
						var eH = parseInt(eT[0]);
						var eM = parseInt(eT[1]);
						
						var sDate = new Date();
						sDate.setMonth(m);
						sDate.setHours(sH);
						sDate.setMinutes(sM);
						sDate.setDate(d);
						sDate.setYear(y);
						
						for(var t = 0; t < tmpDates.length; t++) { //check to see if the start time is already in the array
							//[0] will be a start time
							//[1] will be an end time
							//[2] will be a start time
							//[3] will be an end time
							// etc.
							if(t % 2 == 0) { // a start time
								var t1 = parseInt(t) + parseInt(1);
								if(sDate >= tmpDates[t] && sDate <= tmpDates[t1]) { //the start time is between an already existing time
									scheduledPresenters[k][1] = true;
									break;
								}
							}
						}
						
						if(scheduledPresenters[k][1] == false) { //not found a duplicate time yet, so add the dates
							tmpDates.push(sDate);
						
							var eDate = new Date();
							eDate.setMonth(m);
							eDate.setHours(eH);
							eDate.setMinutes(eM);
							eDate.setDate(d);
							eDate.setYear(y);
						
							tmpDates.push(eDate);
						} else break; //found a duplicate time so we don't need to continue checking this presenter
					}
				}
				
				listPresenters();
			}
			
			//Looks for duplicate times and dates for a single presenter and returns the right class for the row
			function checkPresenter(presArray) {
				var dateTimes = new Array(); //an array of date objects indicating the start and end time for each presentation
				for(var i = 1; i < presArray.length; i++) {
					var tmp = presArray[i].split('||');
					var tmpS = tmp[0].split('|');
					var tmpD = tmpS[0].split('-');
					
					var m = parseInt(tmpD[1]);
					var d = parseInt(tmpD[2]);
					var y = parseInt(tmpD[0]);
					
					var tmpT = tmpS[1].split('-');
					var sT = tmpT[0].split(':');
					var sH = parseInt(sT[0]);
					var sM = parseInt(sT[1]);
					
					var eT = tmpT[1].split(':');
					var eH = parseInt(eT[0]);
					var eM = parseInt(eT[1]);
					
					var start = new Date(y, m - 1, d, sH, sM);
					var end = new Date(y, m - 1, d, eH, eM);
					
					for(t = 0; t < dateTimes.length; t++) {
						if(start.getTime() >= dateTimes[t][0].getTime() && start.getTime() <= dateTimes[t][1].getTime()) return 'sList_problem';
						if(end.getTime() >= dateTimes[t][0].getTime() && end.getTime() <= dateTimes[t][1].getTime()) return 'sList_problem';
					}
					
					dateTimes.push(new Array(start, end));
				}
				
				return 'sList_okay'; //no problems detected
			}
			
			//This function displays the data in the selectedProps array for the user
			function listPresenters() {
				//Clear out any existing prop data
				if(document.getElementById('propDataDiv')) document.body.removeChild(document.getElementById('propDataDiv'));
				
				var pdStr = '<table border="0" width="800" cellpadding="5" cellspacing="0" align="center">';
					
				var rN = 0;
				for(var k in scheduledPresenters) {
					if(scheduledPresenters[k].length < 3) continue; //skip any presenter only assigned to one presentation
					
					rN++;
					
					var tdClass = checkPresenter(scheduledPresenters[k]);
					if(tdClass == '') {
						if(rN % 2 == 0) var tdClass = 'sList_rowOdd';
						else var tdClass = 'sList_rowEven';
					}
						
					pdStr += '<tr id="row' + rN + '"><td class="' + tdClass + '" onMouseOver="highlightRow(\'row' + rN + '\',1)" onMouseOut="highlightRow(\'row' + rN + '\',0)"><p style="font-weight: bold">' + scheduledPresenters[k][0] + '</p>';

					for(var i = 1; i < scheduledPresenters[k].length; i++) {
						var tmpP = scheduledPresenters[k][i].split('||');

						pdStr += '<p>' + tmpP[1] + ' (' + tmpP[3] + ')<br><span style="font-style: italic">' + tmpP[2] + '<br>';
						//alert(tmpS);

						//Now, get the schedule information
						var tmpS = tmpP[0].split('|');
						var tmpD = tmpS[0].split('-');
						
						var months = new Array('','January','February','March','April','May','June','July','August','September','October','November','December');
						var m = parseInt(tmpD[1]);
						var d = parseInt(tmpD[2]);
						var y = parseInt(tmpD[0]);
						
						pdStr += months[m] + ' ' + d + ' from ';
						
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
						
						pdStr += sH + ':' + sM + ' ' + sAMPM + ' to ' + eH + ':' + eM + ' ' + eAMPM;
						
						var stationStr = '';
						if(tmpS[2] != undefined) var stationStr = tmpS[2]; //there is a station to include
						//alert(tmpS + '\n' + stationStr);
						
						if(stationStr != '') pdStr += ' (' + stationStr + ')';
						
						pdStr += '</span></p>';
					}
					
					pdStr += '</td></tr>';
				}

				pdStr += '</table>';
				
				var pDiv = document.createElement('DIV');
				pDiv.id = 'propDataDiv';
				pDiv.style.margin = 'auto';
				pDiv.innerHTML = pdStr;
				document.body.appendChild(pDiv);
			}

			function highlightRow(elStr,n) {
				var rEl = document.getElementById(elStr);
				var nS = parseInt(rEl.id.indexOf('row') + parseInt(3));
				var r = rEl.id.substring(nS,rEl.id.length);
				for(i = 0; i < rEl.cells.length; i++) {
					var cEl = rEl.cells[i];
					if(n == 1) {
						if(cEl.className == 'sList_okay') cEl.className = 'sList_okay_highlighted';
						else if(cEl.className == 'sList_problem') cEl.className = 'sList_problem_highlighted';
						else cEl.className = 'sList_highlighted';
					} else if(n == 0) {
						if(cEl.className == 'sList_okay_highlighted') cEl.className = 'sList_okay';
						else if(cEl.className == 'sList_problem_highlighted') cEl.className = 'sList_problem';
						else {
							if(parseInt(r) % 2 == 0) cEl.className = 'sList_rowOdd';
							else cEl.className = 'sList_rowEven';
						}
					}
				}
			}
			
			document.body.onload = getPresenters();
		</script>
		<p style="text-align: left">The presenters below are presenting in more than one session in the Electronic Village. Compare the dates and times for each presentation to make sure that no presenters are double-booked. You can also use this to determine if a single presenter is over-represented in the Electronic Village.</p>
		<p>&nbsp;</p>
<?php
	include "adminBottom.php";
?>
