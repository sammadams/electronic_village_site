<?php
	//sendNotifications.php -- allows the user to send out mail notifications to proposal authors (acceptance, rejection, etc.)
	//available to leads, chairs, and admin users
	
	include_once "login.php";
	
	$topTitle = "Send Notifications";
	
	/*
		CONFIG SECTION
	 */
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
	
	//$testTo = 'justin.shewell@gmail.com';
	
	//reviewers don't have access to this page
	if(strpos($_SESSION['user_role'],"reviewer_") !== false) {
		include "adminTop.php";
?>
	<h3 align="center">You do not have permission to access this page!</h3>
<?php
		include "adminBottom.php";
	}
	
	$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
	
	//get all the proposals
	$pStmt = $db->prepare("SELECT `id`,`title`,`contact`,`presenters`,`abstract`,`summary`,`emailOK`,`type`,`status`,`confirmed` FROM `proposals` WHERE 1 ORDER BY `id`");
	$pStmt->execute();
	$pStmt->bind_result($pID,$pTitle,$pContact,$pPresenters,$pAbstract,$pSummary,$pEmailOK,$pType,$pStatus,$pConfirmed);
	
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
			"session" => 0,
			"confirmed" => $pConfirmed
		);
	}
	
	$pStmt->close();
	
	//get all the classics proposals
	$cStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary`,`emailOK`,`contact`, `confirmed` FROM `classics_proposals` WHERE 1 ORDER BY `id`");
	$cStmt->execute();
	$cStmt->bind_result($cID,$cTitle,$cPresenters,$cSummary,$cEmailOK,$cContact, $cConfirmed);
	
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
			"session" => 0,
			"confirmed" => $cConfirmed
		);
	}
	
	$cStmt->close();
	
	
	//get all the other proposals
	$oStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary`,`confirmed` FROM `other_proposals` WHERE 1 ORDER BY `id`");
	$oStmt->execute();
	$oStmt->bind_result($oID,$oTitle,$oPresenters,$oSummary,$oConfirmed);
	
	$other_proposals = array();
	while($oStmt->fetch()) {
		$other_proposals[] = array(
			"id" => $oID,
			"title" => $oTitle,
			"presenters" => $oPresenters,
			"summary" => $oSummary,
			"type" => 'Other',
			"status" => 'accepted',
			"session" => 0,
			"confirmed" => $oConfirmed
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
	
	if(isset($_POST["selectedProposals"])) {
		//echo "<pre>";
		//print_r($_POST);
		//echo "</pre>";
		//exit();
		
		//First, get the notification message text and save it to a file
		if(isset($_POST["evFairsAcceptEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evFairsAcceptEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evFairsAcceptEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evFairsAcceptPTMsg = stripslashes($_POST["evFairsAcceptEmailTxt"]);
			$evFairsAcceptHTMLMsg = str_replace("\n","<br />",$evFairsAcceptPTMsg);
		}

		if(isset($_POST["evFairsScheduleEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evFairsScheduleEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evFairsScheduleEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evFairsSchedulePTMsg = stripslashes($_POST["evFairsScheduleEmailTxt"]);
			$evFairsScheduleHTMLMsg = str_replace("\n","<br />",$evFairsSchedulePTMsg);
		}
		
		if(isset($_POST["evFairsRejectEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evFairsRejectEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evFairsRejectEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evFairsRejectPTMsg = stripslashes($_POST["evFairsRejectEmailTxt"]);
			$evFairsRejectHTMLMsg = str_replace("\n","<br />",$evFairsRejectPTMsg);
		}

		if(isset($_POST["evMiniAcceptEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evMiniAcceptEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evMiniAcceptEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evMiniAcceptPTMsg = stripslashes($_POST["evMiniAcceptEmailTxt"]);
			$evMiniAcceptHTMLMsg = str_replace("\n","<br />",$evMiniAcceptPTMsg);
		}

		if(isset($_POST["evMiniScheduleEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evMiniScheduleEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evMiniScheduleEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evMiniSchedulePTMsg = stripslashes($_POST["evMiniScheduleEmailTxt"]);
			$evMiniScheduleHTMLMsg = str_replace("\n","<br />",$evMiniSchedulePTMsg);
		}

		if(isset($_POST["evMiniRejectEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evMiniRejectEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evMiniRejectEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evMiniRejectPTMsg = stripslashes($_POST["evMiniRejectEmailTxt"]);
			$evMiniRejectHTMLMsg = str_replace("\n","<br />",$evMiniRejectPTMsg);
		}

		if(isset($_POST["evDSAcceptEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evDSAcceptEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evDSAcceptEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evDSAcceptPTMsg = stripslashes($_POST["evDSAcceptEmailTxt"]);
			$evDSAcceptHTMLMsg = str_replace("\n","<br />",$evDSAcceptPTMsg);
		}

		if(isset($_POST["evDSScheduleEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evDSScheduleEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evDSScheduleEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evDSSchedulePTMsg = stripslashes($_POST["evDSScheduleEmailTxt"]);
			$evDSScheduleHTMLMsg = str_replace("\n","<br />",$evDSSchedulePTMsg);
		}

		if(isset($_POST["evDSRejectEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evDSRejectEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evDSRejectEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evDSRejectPTMsg = stripslashes($_POST["evDSRejectEmailTxt"]);
			$evDSRejectHTMLMsg = str_replace("\n","<br />",$evDSRejectPTMsg);
		}

		if(isset($_POST["evMAEAcceptEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evMAEAcceptEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evMAEAcceptEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evMAEAcceptPTMsg = stripslashes($_POST["evMAEAcceptEmailTxt"]);
			$evMAEAcceptHTMLMsg = str_replace("\n","<br />",$evMAEAcceptPTMsg);
		}

		if(isset($_POST["evMAEScheduleEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evMAEScheduleEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evMAEScheduleEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evMAESchedulePTMsg = stripslashes($_POST["evMAEScheduleEmailTxt"]);
			$evMAEScheduleHTMLMsg = str_replace("\n","<br />",$evMAESchedulePTMsg);
		}

		if(isset($_POST["evMAERejectEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evMAERejectEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evMAERejectEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evMAERejectPTMsg = stripslashes($_POST["evMAERejectEmailTxt"]);
			$evMAERejectHTMLMsg = str_replace("\n","<br />",$evMAERejectPTMsg);
		}
		
		if(isset($_POST["evHTAcceptEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evHTAcceptEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evHTAcceptEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evHTAcceptPTMsg = stripslashes($_POST["evHTAcceptEmailTxt"]);
			$evHTAcceptHTMLMsg = str_replace("\n","<br />",$evHTAcceptPTMsg);
		}

		if(isset($_POST["evHTScheduleEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evHTScheduleEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evHTScheduleEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evHTSchedulePTMsg = stripslashes($_POST["evHTScheduleEmailTxt"]);
			$evHTScheduleHTMLMsg = str_replace("\n","<br />",$evHTSchedulePTMsg);
		}

		if(isset($_POST["evHTRejectEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evHTRejectEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evHTRejectEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evHTRejectPTMsg = stripslashes($_POST["evHTRejectEmailTxt"]);
			$evHTRejectHTMLMsg = str_replace("\n","<br />",$evHTRejectPTMsg);
		}
		
		if(isset($_POST["evGradAcceptEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evGradAcceptEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evGradAcceptEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evGradAcceptPTMsg = stripslashes($_POST["evGradAcceptEmailTxt"]);
			$evGradAcceptHTMLMsg = str_replace("\n","<br />",$evGradAcceptPTMsg);
		}

		if(isset($_POST["evGradScheduleEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evGradScheduleEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evGradScheduleEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evGradSchedulePTMsg = stripslashes($_POST["evGradScheduleEmailTxt"]);
			$evGradScheduleHTMLMsg = str_replace("\n","<br />",$evGradSchedulePTMsg);
		}

		if(isset($_POST["evGradRejectEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evGradRejectEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evGradRejectEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evGradRejectPTMsg = stripslashes($_POST["evGradRejectEmailTxt"]);
			$evGradRejectHTMLMsg = str_replace("\n","<br />",$evGradRejectPTMsg);
		}
		
		if(isset($_POST["evClassicsAcceptEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evClassicsAcceptEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evClassicsAcceptEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evClassicsAcceptPTMsg = stripslashes($_POST["evClassicsAcceptEmailTxt"]);
			$evClassicsAcceptHTMLMsg = str_replace("\n","<br />",$evClassicsAcceptPTMsg);
		}

		if(isset($_POST["evClassicsScheduleEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evClassicsScheduleEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evClassicsScheduleEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evClassicsSchedulePTMsg = stripslashes($_POST["evClassicsScheduleEmailTxt"]);
			$evClassicsScheduleHTMLMsg = str_replace("\n","<br />",$evClassicsSchedulePTMsg);
		}

		if(isset($_POST["evClassicsRejectEmailTxt"])) {
			//First, save it to a file
			$fout = fopen("evClassicsRejectEmail_editable.txt","w");
			fwrite($fout,stripslashes($_POST["evClassicsRejectEmailTxt"]));
			fclose($fout);
			
			//Now, get the plain text and html versions
			$evClassicsRejectPTMsg = stripslashes($_POST["evClassicsRejectEmailTxt"]);
			$evClassicsRejectHTMLMsg = str_replace("\n","<br />",$evClassicsRejectPTMsg);
		}
		
		$tmpProps = explode("||",strip_tags($_POST["selectedProposals"]));
		$sendSuccess = array();
		$sendFail = array();
		$random_hash = md5(date('r', time()));
		
		for($i = 0; $i < count($tmpProps); $i++) {
			$tmpP = explode("|",$tmpProps[$i]);
			if($tmpP[3] == "Technology Fair Classics") {
				$tmpProposals = $classics_proposals;
				$tmpPresenters = $classics_presenters;
			} else if($tmpP[3] == "Other") {
				$tmpProposals = $other_proposals;
				$tmpPresenters = $other_presenters;
			} else {
				$tmpProposals = $proposals;
				$tmpPresenters = $presenters;
			}
			
			for($j = 0; $j < count($tmpProposals); $j++) {
				if($tmpProposals[$j]["id"] == $tmpP[0]) {
					if(isset($testTo)) $to = $testTo;
					else $to = $tmpProposals[$j]["contact"];
					if($tmpProposals[$j]["type"] == "Technology Fairs") {
						$cc = "ev-fair@call-is.org";
						$from = "ev-fair@call-is.org";
						if($tmpProposals[$j]["session"] != 0) { //this proposal is scheduled
							$subject = "Acceptance Notice for Electronic Village Technology Fairs Submission (".$y.")";
							$tmpMsg = file_get_contents("evFairsScheduleEmail.txt");
							
							$tmpSession = explode("|",$tmpProposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSession[0]);
							$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];
							
							$tmpTime = explode("-",$tmpSession[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
					
							$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
							
							$tmpMsg = str_replace("[INSERT SESSION DATE]",$dateStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT SESSION TIME]",$timeStr,$tmpMsg);
							
							if(isset($tmpSession[2])) {
								$stationStr = "Station: ".$tmpSession[2];
								$stHTMLStr = "				<tr>\n					<td width=\"150\" valign=\"top\" style=\"font-weight: bold\">Station:</td>\n					<td width=\"610\">".$tmpSession[2]."</td>\n				</tr>\n";
							} else {
								$stationStr = "";
								$stHTMLStr = "";
							}
							$tmpMsg = str_replace("[INSERT STATION]",$stationStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT STATION HTML]",$stHTMLStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evFairsSchedulePTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evFairsScheduleHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "rejected") {
							$subject = "Decision Notice for Electronic Village Technology Fairs Submission (".$y.")";
							$tmpMsg = file_get_contents("evFairsRejectEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evFairsRejectPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evFairsRejectHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "accepted") { //accepted but not scheduled (wait-list)
							$subject = "Waiting List Notice for Electronic Village Technology Fairs Submission (".$y.")";
							$tmpMsg = file_get_contents("evFairsAcceptEmail.txt");							
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evFairsAcceptPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evFairsAcceptHTMLMsg,$tmpMsg);
						}
							
						$tmpMsg = str_replace("[INSERT CONFIRM DEADLINE]",$confirmDate,$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONFIRM LINK]",$confirmLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT CERTIFICATE LINK]", $certificateLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT LEAD NAME]",$evFairsLeadName,$tmpMsg);
						$tmpMsg = str_replace("[INSERT TITLE]",stripslashes($tmpProposals[$j]["title"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONTACT]",$tmpProposals[$j]["contact"],$tmpMsg);
						
						$tPres = explode("|",$tmpProposals[$j]["presenters"]);
						$tPStr = "";
						$tPHTML = "";
						for($tPr = 0; $tPr < count($tPres); $tPr++) {
							for($pI = 0; $pI < count($tmpPresenters); $pI++) {
								if($tmpPresenters[$pI]["id"] == $tPres[$tPr]) {
									$tPStr .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									$tPHTML .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									if($tPr < (count($tPres) - 1)) {
										$tPStr .= "\n";
										$tPHTML .= "<br />";
									}
									
									break;
								}
							}
						}
						
						$tmpMsg = str_replace("[INSERT PRESENTERS]",$tPStr,$tmpMsg);
						$tmpMsg = str_replace("[INSERT PRESENTERS HTML]",$tPHTML,$tmpMsg);
						
						$tmpMsg = str_replace("[INSERT ABSTRACT]",stripslashes($tmpProposals[$j]["abstract"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT SUMMARY]",stripslashes($tmpProposals[$j]["summary"]),$tmpMsg);
					} else if($tmpProposals[$j]["type"] == "Mini-Workshops") {
						$cc = "ev-mini@call-is.org";
						$from = "ev-mini@call-is.org";
						if($tmpProposals[$j]["status"] != "rejected" && $tmpProposals[$j]["session"] != 0) { //this proposal is scheduled
							$subject = "Acceptance Notice for Electronic Village Mini-Workshops Submission (".$y.")";
							$tmpMsg = file_get_contents("evMiniScheduleEmail.txt");
							
							$tmpSession = explode("|",$tmpProposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSession[0]);
							$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];
							
							$tmpTime = explode("-",$tmpSession[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
					
							$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
							
							$tmpMsg = str_replace("[INSERT SESSION DATE]",$dateStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT SESSION TIME]",$timeStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evMiniSchedulePTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evMiniScheduleHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "rejected") {
							$subject = "Decision Notice for Electronic Village Mini-Workshops Submission (".$y.")";
							$tmpMsg = file_get_contents("evMiniRejectEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evMiniRejectPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evMiniRejectHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "accepted") { //accepted but not scheduled (wait-list)
							$subject = "Waiting List Notice for Electronic Village Mini-Workshops Submission (".$y.")";
							$tmpMsg = file_get_contents("evMiniAcceptEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evMiniAcceptPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evMiniAcceptHTMLMsg,$tmpMsg);
						}
						
						$tmpMsg = str_replace("[INSERT LEAD NAME]",$evMiniLeadName,$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONFIRM DEADLINE]",$confirmDate,$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONFIRM LINK]",$confirmLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT CERTIFICATE LINK]",$certificateLink.$tmpProposals[$j]["id"],$tmpMsg);	
						$tmpMsg = str_replace("[INSERT TITLE]",stripslashes($tmpProposals[$j]["title"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONTACT]",$tmpProposals[$j]["contact"],$tmpMsg);
						
						$tPres = explode("|",$tmpProposals[$j]["presenters"]);
						$tPStr = "";
						$tPHTML = "";
						for($tPr = 0; $tPr < count($tPres); $tPr++) {
							for($pI = 0; $pI < count($tmpPresenters); $pI++) {
								if($tmpPresenters[$pI]["id"] == $tPres[$tPr]) {
									$tPStr .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									$tPHTML .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									if($tPr < (count($tPres) - 1)) {
										$tPStr .= "\n";
										$tPHTML .= "<br />";
									}
									
									break;
								}
							}
						}
						
						$tmpMsg = str_replace("[INSERT PRESENTERS]",$tPStr,$tmpMsg);
						$tmpMsg = str_replace("[INSERT PRESENTERS HTML]",$tPHTML,$tmpMsg);
						
						$tmpMsg = str_replace("[INSERT ABSTRACT]",stripslashes($tmpProposals[$j]["abstract"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT SUMMARY]",stripslashes($tmpProposals[$j]["summary"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT URL]",$cURL,$tmpMsg);
					} else if($tmpProposals[$j]["type"] == "Mobile Apps for Education Showcase") {
						$cc = "ev-mae@call-is.org";
						$from = "ev-mae@call-is.org";
						if($tmpProposals[$j]["status"] != "rejected" && $tmpProposals[$j]["session"] != 0) { //this proposal is scheduled
							$subject = "Acceptance Notice for Electronic Village Mobile Apps for Education Showcase Submission (".$y.")";
							$tmpMsg = file_get_contents("evMAEScheduleEmail.txt");
							
							$tmpSession = explode("|",$tmpProposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSession[0]);
							$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];
							
							$tmpTime = explode("-",$tmpSession[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
					
							$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
							
							$tmpMsg = str_replace("[INSERT SESSION DATE]",$dateStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT SESSION TIME]",$timeStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evMAESchedulePTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evMAEScheduleHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "rejected") {
							$subject = "Decision Notice for Electronic Village Mobile Apps for Education Showcase Submission (".$y.")";
							$tmpMsg = file_get_contents("evMAERejectEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evMAERejectPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evMAERejectHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "accepted") { //accepted but not scheduled (wait-list)
							$subject = "Waiting List Notice for Electronic Village Mobile Apps for Education Showcase Submission (".$y.")";
							$tmpMsg = file_get_contents("evMAEAcceptEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evMAEAcceptPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evMAEAcceptHTMLMsg,$tmpMsg);
						}
							
						$tmpMsg = str_replace("[INSERT CONFIRM DEADLINE]",$confirmDate,$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONFIRM LINK]",$confirmLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT CERTIFICATE LINK]",$certificateLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT LEAD NAME]",$evMAELeadName,$tmpMsg);
						$tmpMsg = str_replace("[INSERT TITLE]",stripslashes($tmpProposals[$j]["title"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONTACT]",$tmpProposals[$j]["contact"],$tmpMsg);
						
						$tPres = explode("|",$tmpProposals[$j]["presenters"]);
						$tPStr = "";
						$tPHTML = "";
						for($tPr = 0; $tPr < count($tPres); $tPr++) {
							for($pI = 0; $pI < count($tmpPresenters); $pI++) {
								if($tmpPresenters[$pI]["id"] == $tPres[$tPr]) {
									$tPStr .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									$tPHTML .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									if($tPr < (count($tPres) - 1)) {
										$tPStr .= "\n";
										$tPHTML .= "<br />";
									}
									
									break;
								}
							}
						}
						
						$tmpMsg = str_replace("[INSERT PRESENTERS]",$tPStr,$tmpMsg);
						$tmpMsg = str_replace("[INSERT PRESENTERS HTML]",$tPHTML,$tmpMsg);
						
						$tmpMsg = str_replace("[INSERT ABSTRACT]",stripslashes($tmpProposals[$j]["abstract"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT SUMMARY]",stripslashes($tmpProposals[$j]["summary"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT URL]",$cURL,$tmpMsg);
					} else if($tmpProposals[$j]["type"] == "Developers Showcase") {
						$cc = "ev-ds@call-is.org";
						$from = "ev-ds@call-is.org";
						if($tmpProposals[$j]["status"] != "rejected" && $tmpProposals[$j]["session"] != 0) { //this proposal is scheduled
							$subject = "Acceptance Notice for Electronic Village Developers Showcase Submission (".$y.")";
							$tmpMsg = file_get_contents("evDSScheduleEmail.txt");
							
							$tmpSession = explode("|",$tmpProposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSession[0]);
							$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];
							
							$tmpTime = explode("-",$tmpSession[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
					
							$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
							
							$tmpMsg = str_replace("[INSERT SESSION DATE]",$dateStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT SESSION TIME]",$timeStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evDSSchedulePTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evDSScheduleHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "rejected") {
							$subject = "Decision Notice for Electronic Village Developers Showcase Submission (".$y.")";
							$tmpMsg = file_get_contents("evDSRejectEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evDSRejectPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evDSRejectHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "accepted") { //accepted but not scheduled (wait-list)
							$subject = "Waiting List Notice for Electronic Village Developers Showcase Submission (".$y.")";
							$tmpMsg = file_get_contents("evDSAcceptEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evDSAcceptPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evDSAcceptHTMLMsg,$tmpMsg);
						}
						
						$tmpMsg = str_replace("[INSERT CONFIRM DEADLINE]",$confirmDate,$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONFIRM LINK]",$confirmLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT CERTIFICATE LINK]",$certificateLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT LEAD NAME]",$evDSLeadName,$tmpMsg);
						$tmpMsg = str_replace("[INSERT TITLE]",stripslashes($tmpProposals[$j]["title"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONTACT]",$tmpProposals[$j]["contact"],$tmpMsg);
						
						$tPres = explode("|",$tmpProposals[$j]["presenters"]);
						$tPStr = "";
						$tPHTML = "";
						for($tPr = 0; $tPr < count($tPres); $tPr++) {
							for($pI = 0; $pI < count($tmpPresenters); $pI++) {
								if($tmpPresenters[$pI]["id"] == $tPres[$tPr]) {
									$tPStr .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									$tPHTML .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									if($tPr < (count($tPres) - 1)) {
										$tPStr .= "\n";
										$tPHTML .= "<br />";
									}
									
									break;
								}
							}
						}
						
						$tmpMsg = str_replace("[INSERT PRESENTERS]",$tPStr,$tmpMsg);
						$tmpMsg = str_replace("[INSERT PRESENTERS HTML]",$tPHTML,$tmpMsg);
						
						$tmpMsg = str_replace("[INSERT ABSTRACT]",stripslashes($tmpProposals[$j]["abstract"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT SUMMARY]",stripslashes($tmpProposals[$j]["summary"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT URL]",$cURL,$tmpMsg);
					} else if($tmpProposals[$j]["type"] == "Hot Topics") {
						$cc = "ev-ht@call-is.org";
						$from = "ev-ht@call-is.org";
						if($tmpProposals[$j]["status"] != "rejected" && $tmpProposals[$j]["session"] != 0) { //this proposal is scheduled
							$subject = "Acceptance Notice for Electronic Village Hot Topics Submission (".$y.")";
							$tmpMsg = file_get_contents("evHTScheduleEmail.txt");
							
							$tmpSession = explode("|",$tmpProposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSession[0]);
							$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];
							
							$tmpTime = explode("-",$tmpSession[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
					
							$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
							
							$tmpMsg = str_replace("[INSERT SESSION DATE]",$dateStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT SESSION TIME]",$timeStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evHTSchedulePTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evHTScheduleHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "rejected") {
							$subject = "Decision Notice for Electronic Village Hot Topics Submission (".$y.")";
							$tmpMsg = file_get_contents("evHTRejectEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evHTRejectPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evHTRejectHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "accepted") { //accepted but not scheduled (wait-list)
							$subject = "Waiting List Notice for Electronic Village Hot Topics Submission (".$y.")";
							$tmpMsg = file_get_contents("evHTAcceptEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evHTAcceptPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evHTAcceptHTMLMsg,$tmpMsg);
						}
							
						$tmpMsg = str_replace("[INSERT CONFIRM DEADLINE]",$confirmDate,$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONFIRM LINK]",$confirmLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT CERTIFICATE LINK]",$certificateLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT LEAD NAME]",$evHTLeadName,$tmpMsg);
						$tmpMsg = str_replace("[INSERT TITLE]",stripslashes($tmpProposals[$j]["title"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONTACT]",$tmpProposals[$j]["contact"],$tmpMsg);
						
						$tPres = explode("|",$tmpProposals[$j]["presenters"]);
						$tPStr = "";
						$tPHTML = "";
						for($tPr = 0; $tPr < count($tPres); $tPr++) {
							for($pI = 0; $pI < count($tmpPresenters); $pI++) {
								if($tmpPresenters[$pI]["id"] == $tPres[$tPr]) {
									$tPStr .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									$tPHTML .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									if($tPr < (count($tPres) - 1)) {
										$tPStr .= "\n";
										$tPHTML .= "<br />";
									}
									
									break;
								}
							}
						}
						
						$tmpMsg = str_replace("[INSERT PRESENTERS]",$tPStr,$tmpMsg);
						$tmpMsg = str_replace("[INSERT PRESENTERS HTML]",$tPHTML,$tmpMsg);
						
						$tmpMsg = str_replace("[INSERT ABSTRACT]",stripslashes($tmpProposals[$j]["abstract"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT SUMMARY]",stripslashes($tmpProposals[$j]["summary"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT URL]",$cURL,$tmpMsg);
					} else if($tmpProposals[$j]["type"] == "Graduate Student Research") {
						$cc = "ev-grad@call-is.org";
						$from = "ev-grad@call-is.org";
						if($tmpProposals[$j]["status"] != "rejected" && $tmpProposals[$j]["session"] != 0) { //this proposal is scheduled
							$subject = "Acceptance Notice for Electronic Village Graduate Student Research Submission (".$y.")";
							$tmpMsg = file_get_contents("evGradScheduleEmail.txt");
							
							$tmpSession = explode("|",$tmpProposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSession[0]);
							$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];
							
							$tmpTime = explode("-",$tmpSession[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
					
							$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
							
							$tmpMsg = str_replace("[INSERT SESSION DATE]",$dateStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT SESSION TIME]",$timeStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evGradSchedulePTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evGradScheduleHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "rejected") {
							$subject = "Decision Notice for Electronic Village Graduate Student Research Submission (".$y.")";
							$tmpMsg = file_get_contents("evGradRejectEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evGradRejectPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evGradRejectHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "accepted") { //accepted but not scheduled (wait-list)
							$subject = "Waiting List Notice for Electronic Village Graduate Student Research Submission (".$y.")";
							$tmpMsg = file_get_contents("evGradAcceptEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evGradAcceptPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evGradAcceptHTMLMsg,$tmpMsg);
						}
							
						$tmpMsg = str_replace("[INSERT CONFIRM DEADLINE]",$confirmDate,$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONFIRM LINK]",$confirmLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT CERTIFICATE LINK]",$certificateLink.$tmpProposals[$j]["id"],$tmpMsg);
						$tmpMsg = str_replace("[INSERT LEAD NAME]",$evGradLeadName,$tmpMsg);
						$tmpMsg = str_replace("[INSERT TITLE]",stripslashes($tmpProposals[$j]["title"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONTACT]",$tmpProposals[$j]["contact"],$tmpMsg);
						
						$tPres = explode("|",$tmpProposals[$j]["presenters"]);
						$tPStr = "";
						$tPHTML = "";
						for($tPr = 0; $tPr < count($tPres); $tPr++) {
							for($pI = 0; $pI < count($tmpPresenters); $pI++) {
								if($tmpPresenters[$pI]["id"] == $tPres[$tPr]) {
									$tPStr .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									$tPHTML .= $tmpPresenters[$pI]["first_name"]." ".$tmpPresenters[$pI]["last_name"]." (".$tmpPresenters[$pI]["email"].")";
									if($tPr < (count($tPres) - 1)) {
										$tPStr .= "\n";
										$tPHTML .= "<br />";
									}
									
									break;
								}
							}
						}
						
						$tmpMsg = str_replace("[INSERT PRESENTERS]",$tPStr,$tmpMsg);
						$tmpMsg = str_replace("[INSERT PRESENTERS HTML]",$tPHTML,$tmpMsg);
						
						$tmpMsg = str_replace("[INSERT ABSTRACT]",stripslashes($tmpProposals[$j]["abstract"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT SUMMARY]",stripslashes($tmpProposals[$j]["summary"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT URL]",$cURL,$tmpMsg);
					} else if($tmpProposals[$j]["type"] == "Technology Fair Classics") {
						$cc = "ev-classics@call-is.org";
						$from = "ev-classics@call-is.org";
						if($classics_proposals[$j]["session"] != 0) { //this proposal is scheduled
							$subject = "Schedule Information for Electronic Village Technology Fairs Classics Presentation (".$y.")";
							$tmpMsg = file_get_contents("evClassicsScheduleEmail.txt");
							
							$tmpSession = explode("|",$classics_proposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSession[0]);
							$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];
							
							$tmpTime = explode("-",$tmpSession[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
					
							$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
							
							$tmpMsg = str_replace("[INSERT SESSION DATE]",$dateStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT SESSION TIME]",$timeStr,$tmpMsg);
							
							if(isset($tmpSession[2])) {
								$stationStr = "Station: ".$tmpSession[2];
								$stHTMLStr = "				<tr>\n					<td width=\"150\" valign=\"top\" style=\"font-weight: bold\">Station:</td>\n					<td width=\"610\">".$tmpSession[2]."</td>\n				</tr>\n";
							} else {
								$stationStr = "";
								$stHTMLStr = "";
							}
							$tmpMsg = str_replace("[INSERT STATION]",$stationStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT STATION HTML]",$stHTMLStr,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evClassicsSchedulePTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evClassicsScheduleHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "rejected") {
							$subject = "Decision Notice for Electronic Village Technology Fairs Classics Submission (".$y.")";
							$tmpMsg = file_get_contents("evClassicsRejectEmail.txt");
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evClassicsRejectPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evClassicsRejectHTMLMsg,$tmpMsg);
						} else if($tmpProposals[$j]["status"] == "accepted") { //accepted but not scheduled (wait-list)
							$subject = "Waiting List Notice for Electronic Village Technology Fair Classcis Submission (".$y.")";
							$tmpMsg = file_get_contents("evClassicsAcceptEmail.txt");
							
							$tmpMsg = str_replace("[INSERT EDITABLE TEXT HERE]",$evClassicsAcceptPTMsg,$tmpMsg);
							$tmpMsg = str_replace("[INSERT EDITABLE HTML HERE]",$evClassicsAcceptHTMLMsg,$tmpMsg);
						}
							
						$tmpMsg = str_replace("[INSERT CONFIRM DEADLINE]",$confirmDate,$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONFIRM LINK]",$confirmLink.$classics_proposals[$j]["id"]."&classics=1",$tmpMsg);
						$tmpMsg = str_replace("[INSERT CERTIFICATE LINK]",$certificateLink.$classics_proposals[$j]["id"]."&classics=1",$tmpMsg);
						$tmpMsg = str_replace("[INSERT LEAD NAME]",$evClassicsLeadName,$tmpMsg);
						$tmpMsg = str_replace("[INSERT TITLE]",stripslashes($classics_proposals[$j]["title"]),$tmpMsg);
						$tmpMsg = str_replace("[INSERT CONTACT]",$tmpProposals[$j]["contact"],$tmpMsg);
						
						$tPres = explode("|",$classics_proposals[$j]["presenters"]);
						$tPStr = "";
						$tPHTML = "";
						for($tPr = 0; $tPr < count($tPres); $tPr++) {
							for($cpI = 0; $cpI < count($classics_presenters); $cpI++) {
								if($classics_presenters[$cpI]["id"] == $tPres[$tPr]) {
									$tPStr .= $classics_presenters[$cpI]["first_name"]." ".$classics_presenters[$cpI]["last_name"]." (".$classics_presenters[$cpI]["email"].")";
									$tPHTML .= $classics_presenters[$cpI]["first_name"]." ".$classics_presenters[$cpI]["last_name"]." (".$classics_presenters[$cpI]["email"].")";
									if($tPr < (count($tPres) - 1)) {
										$tPStr .= "\n";
										$tPHTML .= "<br />";
									}
									
									break;
								}
							}
						}
						
						$tmpMsg = str_replace("[INSERT PRESENTERS]",$tPStr,$tmpMsg);
						$tmpMsg = str_replace("[INSERT PRESENTERS HTML]",$tPHTML,$tmpMsg);
						
						$tmpMsg = str_replace("[INSERT SUMMARY]",stripslashes($classics_proposals[$j]["summary"]),$tmpMsg);
					}					
																									
					$tmpMsg = str_replace("[INSERT YEAR]",$y,$tmpMsg); //replace the year
					$tmpMsg = str_replace("[INSERT LOCATION]",$cLocation,$tmpMsg); //replace the conference location
					$tmpMsg = str_replace("[INSERT DATES]", $cDates,$tmpMsg); //replace the conference dates
					$tmpMsg = str_replace("[INSERT RANDOM HASH]",$random_hash,$tmpMsg);
					$message = $tmpMsg;

					//define the headers we want passed. Note that they are separated with \r\n
					$headers = "MIME-Version: 1.0\r\nFrom: ".$from."\r\nCC: ".$cc."\r\nReply-To: ".$from."\r\n";

					//add boundary string and mime type specification
					$headers .= "Content-Type: multipart/alternative; boundary=\"CALL-EV-".$random_hash."\""; 

					//send the email
					$mail_sent = @mail( $to, $subject, $message, $headers );

					//if the message is sent successfully print out the confirmation page
					if($mail_sent) $sendSuccess[] = $tmpProposals[$j];
					else $sendFail[] = $tmpProposals[$j];

					break;
				}
			}
		}
		
		if(count($sendSuccess) > 0) {
			$successProps = array();
			$successClassics = array();
			$successOther = array();
			
			//Now update the database for the ones that were sent successfully
			$sucQuery = "UPDATE `proposals` SET `emailOK` = '1' WHERE `id` IN (";
			$sucClassicsQuery = "UPDATE `classics_proposals` SET `emailOK` = '1' WHERE `id` IN (";
			$sucOtherQuery = "UPDATE `other_proposals` SET `emailOK` = '1' WHERE `id` IN (";
			$sucCount = 0;
			$sucClassicsCount = 0;
			$sucOtherCount = 0;
			
			for($sI = 0; $sI < count($sendSuccess); $sI++) {
				if($sendSuccess[$sI]["type"] == "Technology Fair Classics") {
					if($sucClassicsCount > 0) $sucClassicsQuery .= ", ";
					$sucClassicsQuery .= $sendSuccess[$sI]["id"];
					$sucClassicsCount++;
				} else if($sendSuccess[$sI]["type"] == "Other") {
					if($sucOtherCount > 0) $sucOtherQuery .= ", ";
					$sucOtherQuery .= $sendSuccess[$sI]["id"];
					$sucOtherCount++;
				} else {
					if($sucCount > 0) $sucQuery .= ", ";
					$sucQuery .= $sendSuccess[$sI]["id"];
					$sucCount++;
				}
			}
			
			
			$sucQuery .= ")";
			$sucClassicsQuery .= ")";
			$sucOtherQuery .= ")";
		
			if($sucCount > 0) {
				$sucStmt = $db->prepare($sucQuery);
				if(!$sucStmt->execute()) {
					echo $sucStmt->error;
					exit();
				}
				$sucStmt->close();
			}
			
			if($sucClassicsCount > 0) {
				$sucClassicsStmt = $db->prepare($sucClassicsQuery);
				if(!$sucClassicsStmt->execute()) {
					echo $sucClassicsStmt->error;
					exit();
				}
				$sucClassicsStmt->close();
			}
			
			if($sucOtherCount > 0) {
				$sucOtherStmt = $db->prepare($sucOtherQuery);
				if(!$sucOtherStmt->execute()) {
					echo $sucOtherStmt->error;
					exit();
				}
				$sucOtherStmt->close();
			}
		}
		
		//Now, show any proposals where mail couldn't be sent
		include "adminTop.php";
		
		if(count($sendSuccess) > 0) {
?>
	<h3 align="center"><?php echo  count($sendSuccess); ?> notifications sent successfully!</h3>
<?php
		}
		
		if(count($sendFail) > 0) {
?>
	<p>The following notifications could not be sent successfully. Please check the email addresses:</p>
	<p stlye="margin-left: 10">
<?php
			for($fI = 0; $fI < count($sendFail); $fI++) {
				echo $sendFail[$fI]["contact"]."<br />";
			}
?>
	</p>
<?php
		}
?>
	<p align="center"><a href="sendNotifications.php">Send More Notifications</a></p>
<?php	
		include "adminBottom.php";
		exit();
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
	
//	echo "<pre>";
//	print_r($propList);
//	echo "</pre>";
//	exit();
	

	//get the event
	if(strpos($_SESSION['user_role'],"lead_") !== false) {
		if(strpos($_SESSION['user_role'],"_fairs") !== false) $eTypes = array("Technology Fairs");
		else if(strpos($_SESSION['user_role'],"_mini") !== false) $eTypes = array("Mini-Workshops");
		else if(strpos($_SESSION['user_role'],"_ds") !== false) $eTypes = array("Developers Showcase");
		else if(strpos($_SESSION['user_role'],"_mae") !== false) $eTypes = array("Mobile Apps for Education Showcase");
		else if(strpos($_SESSION['user_role'],"_ht") !== false) $eTypes = array("Hot Topics");
		else if(strpos($_SESSION['user_role'],"_grad") !== false) $eTypes = array("Graduate Student Research");
		else if(strpos($_SESSION['user_role'],"_classics") !== false) $eTypes = array("Technology Fair Classics");
	} else $eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Hot Topics","Graduate Student Research","Technology Fair Classics");
	
	include "adminTop.php";
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_rowEven {
			background-color: #FFFFFF;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.pList_rowOdd {
			background-color: #CCCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
				
		td.pList_highlighted {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_accepted {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_accepted_highlighted {
			background-color: #006600;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_rejected {
			background-color: #FFCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_rejected_highlighted {
			background-color: #660000;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		ol {
			padding-left: 18;
		}
		
		div.header {
			position: fixed;
			top: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
		}

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		div.propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
		
		td.msgTab {
			text-align: center;
			font-weight: bold;
			color: #000000;
			border: solid 1px #000000;
			width: 50%;
			cursor: default;
		}
		
		td.msgTab_selected, td.msgTab:hover {
			text-align: center;
			font-weight: bold;
			color: #FFFFFF;
			background-color: #333333;
			border: solid 1px #000000;
			width: 50%;	
			cursor: default;	
		}
		
		img.qMark {
			width: 30px;
			height: 30px;
		}
		
		img.greenCheck, img.redX {
			width: 20px;
			height: 20px;
		}
	</style>
	<script type="text/javascript">
		var proposals = new Array();
		var events = new Array();
<?php
	for($e = 0; $e < count($eTypes); $e++) {
?>
		events[<?php echo $e; ?>] = '<?php echo $eTypes[$e]; ?>';
<?php
	}
?>
		
		function highlightRow(elStr,n) {
			var rEl = document.getElementById(elStr);
			var nS = parseInt(rEl.id.indexOf('row') + parseInt(3));
			var r = rEl.id.substring(nS,rEl.id.length);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_accepted') cEl.className = 'pList_accepted_highlighted';
					else if(cEl.className == 'pList_rejected') cEl.className = 'pList_rejected_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_accepted_highlighted') cEl.className = 'pList_accepted';
					else if(cEl.className == 'pList_rejected_highlighted') cEl.className = 'pList_rejected';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function checkBox(elStr) {
			var chkEl = document.getElementById(elStr);
			if(chkEl.checked) chkEl.checked = false;
			else chkEl.checked = true;
			selectProposal(chkEl);
		}

		function selectProposal(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				if(el.id.indexOf('_all') != -1) { //check all the proposals in this section
					var tStr = el.id.replace('_all','_propTable');
					var tRows = document.getElementById(tStr).rows;
					for(r = 1; r < tRows.length; r++) { //skip the header row
						var rN = r - 1;
						var rStr = el.id.replace('_all','_row') + rN;
						var pR = document.getElementById(rStr);
						var cbStr = rStr.replace('row','chk');
						var cbEl = document.getElementById(cbStr);

						if(el.checked) { //the box is checked, so save this as one of the proposals to notify				
							for(p = 0; p < proposals.length; p++) {
								if(proposals[p] == cbEl.value) continue; //proposal already selected
							}
							
							cbEl.checked = true;					
							var pI = proposals.length;
							proposals[pI] = cbEl.value;
							for(c = 0; c < pR.cells.length; c++) {
								if(el.id.indexOf('accepted') != -1 || el.id.indexOf('scheduled') != -1) pR.cells[c].className = 'pList_accepted';
								else if(el.id.indexOf('rejected') != -1) pR.cells[c].className = 'pList_rejected';
							}
						} else { //el is unchecked
							for(p = 0; p < proposals.length; p++) {
								if(proposals[p] == cbEl.value) proposals.splice(p,1); //remove the proposal from the array
								break;
							}
					
							cbEl.checked = false;
							for(c = 0; c < pR.cells.length; c++) {
								if(rN % 2 == 0) pR.cells[c].className = 'pList_rowEven';
								else pR.cells[c].className = 'pList_rowOdd';
							}
						}
					}
				} else {
					var rStr = el.id.replace('chk','row');
					var rS = rStr.indexOf('row');
					if(rS != -1) rS = parseInt(rS) + parseInt(3);
					var rN = rStr.substring(rS);
				
					var pR = document.getElementById(rStr);			
					if(el.checked) { //the box is checked, so save this as one of the proposals to notify				
						for(p = 0; p < proposals.length; p++) {
							if(proposals[p] == el.value) return false; //proposal already selected
						}
					
						var pI = proposals.length;
						proposals[pI] = el.value;
						for(c = 0; c < pR.cells.length; c++) {
							if(el.value.indexOf('accepted') != -1 || el.value.indexOf('scheduled') != -1) pR.cells[c].className = 'pList_accepted_highlighted';
							else if(el.value.indexOf('rejected') != -1) pR.cells[c].className = 'pList_rejected_highlighted';
						}
					} else { //el is unchecked
						for(p = 0; p < proposals.length; p++) {
							if(proposals[p] == el.value) proposals.splice(p,1); //remove the proposal from the array
							break;
						}
					
						for(c = 0; c < pR.cells.length; c++) {
							if(rN % 2 == 0) pR.cells[c].className = 'pList_rowEven';
							else pR.cells[c].className = 'pList_rowOdd';
						}
					}
				}
				
				var sM = document.getElementById('saveMsg');
				if(proposals.length > 0) sM.style.visibility = '';
				else sM.style.visibility = 'hidden';
			}
		}

		function saveChanges() {
			//First, get the list of all the proposals selected
			var pStr = '';
			for(p = 0; p < proposals.length; p++) {
				pStr += proposals[p];
				if(p < (proposals.length - 1)) pStr += '||';
			}
			
			document.getElementById('selectedProposals').value = pStr;

			//First, check to make sure the notification messages have been saved
			var iEls = document.getElementsByTagName('input');
			for(i = 0; i < iEls.length; i++) {
				if(iEls[i].type == 'checkbox' && iEls[i].id.indexOf('msgChk') != -1) {
					var ev = iEls[i].id.substr(0,iEls[i].id.indexOf('_'));
					ev = parseInt(ev.substr(5,ev.length));
					evnt = events[ev];
					
					var pt = iEls[i].id.substr(iEls[i].id.lastIndexOf('_'),iEls[i].id.length);
					pt = pt.substr(1,pt.length);
					
					if(iEls[i].checked == false) { //they did not review the message (box was unchecked)
						if(pStr.indexOf('|' + pt) != -1 && pStr.indexOf(evnt) != -1) { //they selected a proposal from this group
							if(events.length > 1) { //more than one event, so include the event in the message
								alert('You did not review the ' + pt + ' email notification for ' + evnt + '!\n\n' + pStr);
							} else { //only one event, so only mention the type
								alert('You did not review the ' + pt + ' email notification text!');
							}
						
							return false;
						}
					} else { //the reviewed box was checked, so add the text to the form
						var sForm = document.getElementById('sendForm');
						if(evnt == 'Technology Fairs') { //create the elements for the fairs
							if(pt == 'accepted') {
								if(document.getElementById('evFairsAcceptEmailTxt')) {
									document.getElementById('evFairsAcceptEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evFairsAcceptEmailTxt');
									txtEl.setAttribute('id','evFairsAcceptEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'scheduled') {
								if(document.getElementById('evFairsScheduleEmailTxt')) {
									document.getElementById('evFairsScheduleEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evFairsScheduleEmailTxt');
									txtEl.setAttribute('id','evFairsScheduleEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'rejected') {
								if(document.getElementById('evFairsRejectEmailTxt')) {
									document.getElementById('evFairsRejectEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evFairsRejectEmailTxt');
									txtEl.setAttribute('id','evFairsRejectEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
									sForm.appendChild(txtEl);
								}
							}
						} else if(evnt == 'Mini-Workshops') { //create the elements for the mini-workshops
							if(pt == 'accepted') {
								if(document.getElementById('evMiniAcceptEmailTxt')) {
									document.getElementById('evMiniAcceptEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evMiniAcceptEmailTxt');
									txtEl.setAttribute('id','evMiniAcceptEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'scheduled') {
								if(document.getElementById('evMiniScheduleEmailTxt')) {
									document.getElementById('evMiniScheduleEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evMiniScheduleEmailTxt');
									txtEl.setAttribute('id','evMiniScheduleEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'rejected') {
								if(document.getElementById('evMiniRejectEmailTxt')) {
									document.getElementById('evMiniRejectEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evMiniRejectEmailTxt');
									txtEl.setAttribute('id','evMiniRejectEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
									sForm.appendChild(txtEl);
								}
							}
						} else if(evnt == 'Developers Showcase') { //create the elements for the DS
							if(pt == 'accepted') {
								if(document.getElementById('evDSAcceptEmailTxt')) {
									document.getElementById('evDSAcceptEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evDSAcceptEmailTxt');
									txtEl.setAttribute('id','evDSAcceptEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'scheduled') {
								if(document.getElementById('evDSScheduleEmailTxt')) {
									document.getElementById('evDSScheduleEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evDSScheduleEmailTxt');
									txtEl.setAttribute('id','evDSScheduleEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'rejected') {
								if(document.getElementById('evDSRejectEmailTxt')) {
									document.getElementById('evDSRejectEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evDSRejectEmailTxt');
									txtEl.setAttribute('id','evDSRejectEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
									sForm.appendChild(txtEl);
								}
							}
						} else if(evnt == 'Mobile Apps for Education Showcase') { //create the elements for the MAE
							if(pt == 'accepted') {
								if(document.getElementById('evMAEAcceptEmailTxt')) {
									document.getElementById('evMAEAcceptEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evMAEAcceptEmailTxt');
									txtEl.setAttribute('id','evMAEAcceptEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'scheduled') {
								if(document.getElementById('evMAEScheduleEmailTxt')) {
									document.getElementById('evMAEScheduleEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evMAEScheduleEmailTxt');
									txtEl.setAttribute('id','evMAEScheduleEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'rejected') {
								if(document.getElementById('evMAERejectEmailTxt')) {
									document.getElementById('evMAERejectEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evMAERejectEmailTxt');
									txtEl.setAttribute('id','evMAERejectEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
									sForm.appendChild(txtEl);
								}
							}
						} else if(evnt == 'Hot Topics') { //create the elements for the MAE
							if(pt == 'accepted') {
								if(document.getElementById('evHTAcceptEmailTxt')) {
									document.getElementById('evHTAcceptEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evHTAcceptEmailTxt');
									txtEl.setAttribute('id','evHTAcceptEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'scheduled') {
								if(document.getElementById('evHTScheduleEmailTxt')) {
									document.getElementById('evHTScheduleEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evHTScheduleEmailTxt');
									txtEl.setAttribute('id','evHTScheduleEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'rejected') {
								if(document.getElementById('evHTRejectEmailTxt')) {
									document.getElementById('evHTRejectEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evHTRejectEmailTxt');
									txtEl.setAttribute('id','evHTRejectEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
									sForm.appendChild(txtEl);
								}
							}
						} else if(evnt == 'Graduate Student Research') { //create the elements for the MAE
							if(pt == 'accepted') {
								if(document.getElementById('evGradAcceptEmailTxt')) {
									document.getElementById('evGradAcceptEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evGradAcceptEmailTxt');
									txtEl.setAttribute('id','evGradAcceptEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'scheduled') {
								if(document.getElementById('evGradScheduleEmailTxt')) {
									document.getElementById('evGradScheduleEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evGradScheduleEmailTxt');
									txtEl.setAttribute('id','evGradScheduleEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'rejected') {
								if(document.getElementById('evGradRejectEmailTxt')) {
									document.getElementById('evGradRejectEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evGradRejectEmailTxt');
									txtEl.setAttribute('id','evGradRejectEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
									sForm.appendChild(txtEl);
								}
							}
						} else if(evnt == 'Technology Fair Classics') { //create the elements for the MAE
							if(pt == 'accepted') {
								if(document.getElementById('evClassicsAcceptEmailTxt')) {
									document.getElementById('evClassicsAcceptEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evClassicsAcceptEmailTxt');
									txtEl.setAttribute('id','evClassicsAcceptEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_accepted').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'scheduled') {
								if(document.getElementById('evClassicsScheduleEmailTxt')) {
									document.getElementById('evClassicsScheduleEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evClassicsScheduleEmailTxt');
									txtEl.setAttribute('id','evClassicsScheduleEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_scheduled').value;
									sForm.appendChild(txtEl);
								}
							} else if(pt == 'rejected') {
								if(document.getElementById('evClassicsRejectEmailTxt')) {
									document.getElementById('evClassicsRejectEmailTxt').value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
								} else {
									var txtEl = document.createElement('input');
									txtEl.setAttribute('type','hidden');
									txtEl.setAttribute('name','evClassicsRejectEmailTxt');
									txtEl.setAttribute('id','evClassicsRejectEmailTxt');
									txtEl.value = document.getElementById('event' + ev + '_msgTxt_rejected').value;
									sForm.appendChild(txtEl);
								}
							}
						}
					}
				}
			}
			
			document.getElementById('sendForm').submit();
		}

		function checkHeader() {
			var hArray = new Array();
			var pArray = new Array();
			
			var tmp = document.getElementsByTagName('DIV');
			for(i = 0; i < tmp.length; i++) {
				if(tmp[i].id.indexOf('_header') != -1) {
					var tH = new Array();
					tH[0] = tmp[i].id;
					
					var tHR = tmp[i].getBoundingClientRect();
					tH[1] = tHR.top;
					tH[2] = tHR.bottom;
					
					hArray.push(tH);
				}
				
				if(tmp[i].id.indexOf('_props') != -1) {
					var tP = new Array();
					tP[0] = tmp[i].id;
					
					var tPR = tmp[i].getBoundingClientRect();
					tP[1] = tPR.top;
					tP[2] = tPR.bottom;
					
					pArray.push(tP);
				}
			}
			
			alert('TH: ' + hArray + '\n\nTP: ' + pArray);
			
			return;
			
			for(h = 0; h < hArray.length; h++) {
				var hDiv = document.getElementById(hArray[h]);
				var pDiv = document.getElementById(pArray[h]);
				var hRect = hDiv.getBoundingClientRect();
				var pRect = pDiv.getBoundingClientRect();
				
				var prevI = h - 1;
				var nextI = parseInt(h) + parseInt(1);
				
				if(prevI >= 0) {
					var prevHDiv = document.getElementById(hArray[prevI]);
					var prevPDiv = document.getElementById(pArray[prevI]);
					var prevHRect = prevHDiv.getBoundingClientRect();
					var prevPRect = prevPDiv.getBoundingClientRect();
				}
				
				if(nextI < hArray.length) {
					var nextHDiv = document.getElementById(hArray[nextI]);
					var nextPDiv = document.getElementById(pArray[nextI]);
					var nextHRect = nextHDiv.getBoundingClientRect();
					var nextPRect = nextPDiv.getBoundingClientRect();
				}
			
				var h = hDiv.offsetHeight;
			
				if(prevI < 0) { //no previous divs or no next divs
					if(hRect.top < 0) { //scrolling down
						hDiv.className = 'header';
						pDiv.style.paddingTop = h + 'px';
					} else if(pRect.top > 0) { //scrolling up
						hDiv.className = '';
						pDiv.style.paddingTop = '0px';
					}
				} else {
					if(prevPRect.bottom < 0) { //the previous div is off screen
						prevHDiv.className = '';
						prevPDiv.style.paddingTop = '0px';
						
						hDiv.className = 'header';
						pDiv.style.paddingTop = h + 'px';
					} else if(prevPRect.bottom > 0) {
						hDiv.className = '';
						pDiv.style.paddingTop = '0px';
					}
				}
			}
		}
		
		function displayProps(id) {
			if(document.getElementById(id).style.display != 'none') document.getElementById(id).style.display = 'none';
			else document.getElementById(id).style.display = 'block';
		}
		
		//window.onscroll = function() {
		//	checkHeader();
		//};
	</script>
<?php
	$confirmedProps = array();
	for($e = 0; $e < count($eTypes); $e++) {
		$tE = $eTypes[$e];
		if(isset($propList[$tE])) {
			$confirmedProps[$tE] = array();
			if($tE == "Technology Fair Classics") $thisPresenters = $classics_presenters;
			else if($tE == "Other") $thisPresenters = $other_presenters;
			else $thisPresenters = $presenters;
			$pStatus = array("scheduled","accepted","rejected");
			for($s = 0; $s < count($pStatus); $s++) {
				if(!array_key_exists($pStatus[$s],$propList[$tE])) continue;
				$tS = $pStatus[$s];
				$confirmedProps[$tE][$tS] = array("Y" => 0, "N" => 0, "?" => 0, "Total" => 0);
				ob_start();
				$rN = 0;
				for($i = 0; $i < count($propList[$tE][$tS]); $i++) {
					if($propList[$tE][$tS][$i]["emailOK"] == 1) {
						if($propList[$tE][$tS][$i]["status"] == "accepted" || $propList[$tE][$tS][$i]["status"] == "scheduled") $rowClass = 'pList_accepted';
						else if($propList[$tE][$tS][$i]["status"] == "rejected") $rowClass = 'pList_rejected';
					} else {
						if($rN % 2 == 0) $rowClass = 'pList_rowEven';
						else $rowClass = 'pList_rowOdd';
					}
				
					//get the presenters names
					$tmpPres = explode("|",$propList[$tE][$tS][$i]["presenters"]);
					if(count($tmpPres) > 1) $presStr = "<ol>";
					else $presStr = "";
					for($tP = 0; $tP < count($tmpPres); $tP++) {
						for($j = 0; $j < count($thisPresenters); $j++) {
							if($thisPresenters[$j]["id"] == $tmpPres[$tP]) {
								if(count($tmpPres) > 1) $presStr .= "<li>".$thisPresenters[$j]["first_name"]." ".$thisPresenters[$j]["last_name"]."</li>";
								else $presStr .= $thisPresenters[$j]["first_name"]." ".$thisPresenters[$j]["last_name"];
								break; 
							}
						}
					}
					if(count($tmpPres) > 1) $presStr .= "</ol>";
			
					//get the session information (if there)
					if($tS == "scheduled") {
						$sesStr = '';
						$tmpSes = explode("|",$propList[$tE][$tS][$i]["session"]);
						$sesStr .= '<span style="font-size: .7em">';

						$tmpDate = explode("-",$tmpSes[0]);
						$sesStr .= $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0]."<br />";
						
						$tmpTime = explode("-",$tmpSes[1]);
						$tmpStart = explode(":",$tmpTime[0]);
						$tmpSHour = intval($tmpStart[0]);
						if($tmpSHour < 12) $sAMPM = "AM";
						else {
							$sAMPM = "PM";
							if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
						}
						$tmpSMinutes = $tmpStart[1];
					
						$sesStr .= $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
						$tmpEnd = explode(":",$tmpTime[1]);
						$tmpEHour = intval($tmpEnd[0]);
						if($tmpEHour < 12) $eAMPM = "AM";
						else {
							$eAMPM = "PM";
							if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
						}
						$tmpEMinutes = $tmpEnd[1];
					
						$sesStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
					
						if($tE == "Technology Fairs" && count($tmpSes) > 2) $sesStr .= "<br />".$tmpSes[2];
					}
?>
		<tr id="event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" valign="center" style="text-align: center" onMouseOver="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',1)" onMouseOut="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',0)"><input type="checkbox" name="event<?php echo $e; ?>_<?php echo $tS; ?>_chk<?php echo $rN; ?>" id="event<?php echo $e; ?>_<?php echo $tS; ?>_chk<?php echo $rN; ?>" onClick="selectProposal(this)" value="<?php echo $propList[$tE][$tS][$i]["id"].'|'.$propList[$tE][$tS][$i]["contact"].'|'.$propList[$tE][$tS][$i]["status"].'|'.$eTypes[$e]; ?>" /></td>
			<td class="<?php echo $rowClass; ?>" onMouseOver="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',1)" onMouseOut="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',0)" onClick="checkBox('event<?php echo $e; ?>_<?php echo $tS; ?>_chk<?php echo $rN; ?>')"><?php echo $propList[$tE][$tS][$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',1)" onMouseOut="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',0)" onClick="checkBox('event<?php echo $e; ?>_<?php echo $tS; ?>_chk<?php echo $rN; ?>')"><?php echo $presStr; ?></td>
<?php
					if($tS == "scheduled") {
?>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',1)" onMouseOut="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',0)" onClick="checkBox('event<?php echo $e; ?>_<?php echo $tS; ?>_chk<?php echo $rN; ?>')"><?php echo $sesStr; ?></td>
<?php
					}
					
					if($tS == "scheduled" || $tS == "accepted") { // we don't need the confirmed status for rejected proposals and they wouldn't have received the confirm link anyway
						$tC = $propList[$tE][$tS][$i]["confirmed"];
						$confirmedProps[$tE][$tS][$tC]++;
						$confirmedProps[$tE][$tS]["Total"]++;
?>
			<td class="<?php echo $rowClass; ?>" style="text-align: center;" onMouseOver="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',1)" onMouseOut="highlightRow('event<?php echo $e; ?>_<?php echo $tS; ?>_row<?php echo $rN; ?>',0)" onClick="checkBox('event<?php echo $e; ?>_<?php echo $tS; ?>_chk<?php echo $rN; ?>')"><?php if($tC == "Y") { ?><img src="green_check.png" class="greenCheck" /><?php } else if($tC == "N") { ?><img src="red_x.png" class="redX" /><?php } else if($tC == "?") { ?><img src="q_mark.png" class="qMark" /><?php } ?></td>
		</tr>
<?php
					}
					
					$rN++;
				}

				$rows = ob_get_contents();
				ob_end_clean();
?>
	<div id="event<?php echo $e; ?>_<?php echo $tS; ?>_header" style="margin-top: 50px;">
		<table border="0" width="800" style="border-botom: solid 1px #AAAAAA">
			<tr>
				<td align="center" style="font-weight: bold" onClick="displayProps('event<?php echo $e; ?>_<?php echo $tS; ?>_props')"><?php echo $eTypes[$e]." (".ucwords($tS).")"; ?></td>
			</tr>
<?php
				if($tS == "scheduled") {
?>
			<tr>
				<td style="text-align: center">
					<b>Confirmed (Yes):</b> <?php echo  $confirmedProps[$tE][$tS]["Y"]; ?> (<?php echo  round(($confirmedProps[$tE][$tS]["Y"]/$confirmedProps[$tE][$tS]["Total"]) * 100); ?>%) &nbsp; &nbsp; &nbsp; 
					<b>Confirmed (No):</b> <?php echo  $confirmedProps[$tE][$tS]["N"]; ?>  (<?php echo  round(($confirmedProps[$tE][$tS]["N"]/$confirmedProps[$tE][$tS]["Total"]) * 100); ?>%)&nbsp; &nbsp; &nbsp;
					<b>Unknown:</b> <?php echo  $confirmedProps[$tE][$tS]["?"]; ?> (<?php echo  round(($confirmedProps[$tE][$tS]["?"]/$confirmedProps[$tE][$tS]["Total"]) * 100); ?>%) &nbsp; &nbsp; &nbsp;
					<b>Total:</b> <?php echo  $confirmedProps[$tE][$tS]["Total"]; ?>
				</td>
			</tr>
			<tr>
				<td>Scheduled proposals are proposals that are accepted and have been assigned to a session. Presenters will be notified of their acceptance and asked to confirm.</td>
			</tr>
			<tr>
				<td>Proposals marked with a <span style="background-color: #CCFFCC">&nbsp; green &nbsp;</span> background, but <b>NOT</b> checked have already been sent notifications. To send a duplicate notification to one of these proposals, check the box next to that proposal's title.</td>
			</tr>
<?php
				} else if($tS == "accepted") {
?>
			<tr>
				<td style="text-align: center">
					<b>Confirmed (Yes):</b> <?php echo  $confirmedProps[$tE][$tS]["Y"]; ?> (<?php echo  round(($confirmedProps[$tE][$tS]["Y"]/$confirmedProps[$tE][$tS]["Total"]) * 100); ?>%) &nbsp; &nbsp; &nbsp; 
					<b>Confirmed (No):</b> <?php echo  $confirmedProps[$tE][$tS]["N"]; ?>  (<?php echo  round(($confirmedProps[$tE][$tS]["N"]/$confirmedProps[$tE][$tS]["Total"]) * 100); ?>%)&nbsp; &nbsp; &nbsp;
					<b>Unknown:</b> <?php echo  $confirmedProps[$tE][$tS]["?"]; ?> (<?php echo  round(($confirmedProps[$tE][$tS]["?"]/$confirmedProps[$tE][$tS]["Total"]) * 100); ?>%) &nbsp; &nbsp; &nbsp;
					<b>Total:</b> <?php echo  $confirmedProps[$tE][$tS]["Total"]; ?>
				</td>
			</tr>
			<tr>
				<td>Accepted proposals are proposals that are accepted, but have <b>NOT</b> been assigned to a session. These presentations are essentially on a "wait-list" and can be added to the program if a previously scheduled presenter cancels. Authors will receive an email stating that they have been placed on a "wait-list".</td>
			</tr>
			<tr>
				<td>Proposals marked with a <span style="background-color: #CCFFCC">&nbsp; green &nbsp;</span> background, but <b>NOT</b> checked have already been sent notifications. To send a duplicate notification to one of these proposals, check the box next to that proposal's title.</td>
			</tr>
<?php
				} else if($tS == "rejected") {
?>
			<tr>
				<td>Rejected proposals are proposals that are marked rejected. Presenters will be notified of the decision along with general reasons why some proposals were not accepted.</td>
			</tr>
			<tr>
				<td>Proposals marked with a <span style="background-color: #FFCCCC">&nbsp; red &nbsp;</span> background have already been sent notifications. To send a duplicate notification, click the check box next to that proposal's title.</td>
			</tr>
<?php
				}
?>
		</table>
	</div>
	<div id="event<?php echo $e; ?>_<?php echo $tS; ?>_props" class="propTableDiv" style="display: none;">
		<table id="event<?php echo $e; ?>_<?php echo $tS; ?>_propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList" style="text-align: center"><input type="checkbox" id="event<?php echo $e; ?>_<?php echo $tS; ?>_all" onClick="selectProposal(this)" /></th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
<?php
				if($tS == "scheduled") {
?>
				<th class="pList">Schedule</th>
<?php
				}
				
				if($tS == "scheduled" || $tS == "accepted") {
?>
				<th class="pList">Confirmed</th>
<?php
				}
?>
			</tr>
<?php			
				echo $rows;
?>
		</table><br /><br />
		<table id="msgTable_<?php echo $tS; ?>" border="0" align="center" cellpadding="5" width="800" cellspacing="0">
			<tr>
				<td style="font-weight: bold"><?php echo ucwords($tS); ?> Email Notification</td>
			</tr>
			<tr>
				<td>Below is the content of the <?php echo $tS; ?> email notification. You MUST check the box that you have reviewed the message before you can send any notifications!</td>
			</tr>
			<tr>
				<td>
<?php
				if($eTypes[$e] == "Technology Fairs" && $tS == "scheduled") $msgTxt = file_get_contents("evFairsScheduleEmail_editable.txt");
				else if($eTypes[$e] == "Technology Fairs" && $tS == "accepted") $msgTxt = file_get_contents("evFairsAcceptEmail_editable.txt");
				else if($eTypes[$e] == "Technology Fairs" && $tS == "rejected") $msgTxt = file_get_contents("evFairsRejectEmail_editable.txt");
				else if($eTypes[$e] == "Mini-Workshops" && $tS == "scheduled") $msgTxt = file_get_contents("evMiniScheduleEmail_editable.txt");
				else if($eTypes[$e] == "Mini-Workshops" && $tS == "accepted") $msgTxt = file_get_contents("evMiniAcceptEmail_editable.txt");
				else if($eTypes[$e] == "Mini-Workshops" && $tS == "rejected") $msgTxt = file_get_contents("evMiniRejectEmail_editable.txt");
				else if($eTypes[$e] == "Mobile Apps for Education Showcase" && $tS == "scheduled") $msgTxt = file_get_contents("evMAEScheduleEmail_editable.txt");
				else if($eTypes[$e] == "Mobile Apps for Education Showcase" && $tS == "accepted") $msgTxt = file_get_contents("evMAEAcceptEmail_editable.txt");
				else if($eTypes[$e] == "Mobile Apps for Education Showcase" && $tS == "rejected") $msgTxt = file_get_contents("evMAERejectEmail_editable.txt");
				else if($eTypes[$e] == "Hot Topics" && $tS == "scheduled") $msgTxt = file_get_contents("evHTScheduleEmail_editable.txt");
				else if($eTypes[$e] == "Hot Topics" && $tS == "accepted") $msgTxt = file_get_contents("evHTAcceptEmail_editable.txt");
				else if($eTypes[$e] == "Hot Topics" && $tS == "rejected") $msgTxt = file_get_contents("evHTRejectEmail_editable.txt");
				else if($eTypes[$e] == "Graduate Student Research" && $tS == "scheduled") $msgTxt = file_get_contents("evGradScheduleEmail_editable.txt");
				else if($eTypes[$e] == "Graduate Student Research" && $tS == "accepted") $msgTxt = file_get_contents("evGradAcceptEmail_editable.txt");
				else if($eTypes[$e] == "Graduate Student Research" && $tS == "rejected") $msgTxt = file_get_contents("evGradRejectEmail_editable.txt");
				else if($eTypes[$e] == "Technology Fair Classics" && $tS == "scheduled") $msgTxt = file_get_contents("evClassicsScheduleEmail_editable.txt");
				else if($eTypes[$e] == "Technology Fair Classics" && $tS == "accepted") $msgTxt = file_get_contents("evClassicsAcceptEmail_editable.txt");
				else if($eTypes[$e] == "Technology Fair Classics" && $tS == "rejected") $msgTxt = file_get_contents("evClassicsRejectEmail_editable.txt");
				else if($eTypes[$e] == "Developers Showcase" && $tS == "scheduled") $msgTxt = file_get_contents("evDSScheduleEmail_editable.txt");
				else if($eTypes[$e] == "Developers Showcase" && $tS == "accepted") $msgTxt = file_get_contents("evDSAcceptEmail_editable.txt");
				else if($eTypes[$e] == "Developers Showcase" && $tS == "rejected") $msgTxt = file_get_contents("evDSRejectEmail_editable.txt");
?>
					<textarea id="event<?php echo $e; ?>_msgTxt_<?php echo $tS; ?>" rows="30" cols="98" style="font-size: 10pt; font-family: Courier"><?php echo stripslashes($msgTxt); ?></textarea><br />
					<input type="checkbox" id="event<?php echo $e; ?>_msgChk_<?php echo $tS; ?>" /> I have reviewed the <b><?php echo $tS; ?></b> email notification text.
				</td>
			</tr>
		</table><br /><br />
	</div>
<?php
			}
		}
	}
?>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">NOTIFICATIONS NOT SENT!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input id="saveMsgBtn" type="button" value="Send Notifications" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="sendForm" id="sendForm" method="post" action="">
		<input type="hidden" name="selectedProposals" id="selectedProposals" value="" />
	</form>
<?php
	include "adminBottom.php";
?>