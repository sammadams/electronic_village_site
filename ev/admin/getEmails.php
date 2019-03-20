<?php
	//getEmails.php -- allows the user to see all email addresses of presenters for specific events
	//available to leads, chairs, and admin users
	
	include_once "login.php";
	
	$topTitle = "Get Email Addresses of Proposal Authors";

	//reviewers don't have access to this page
	if(strpos($_SESSION['user_role'],"reviewer_") !== false) {
		include "adminTop.php";
?>
	<h3 align="center">You do not have permission to access this page!</h3>
<?php
		include "adminBottom.php";
	}
	
	$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
	$y = "2016";
	$cLocation = "Baltimore, Maryland, USA";
	$cDates = "April 5 - 8, 2016";
	$cURL = "http://www.tesol.org/convention2016";

	//get all the proposals
	$pStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`type`,`status` FROM `proposals` WHERE 1 ORDER BY `id`");
	$pStmt->execute();
	$pStmt->bind_result($pID,$pTitle,$pPresenters,$pType,$pStatus);
	
	$proposals = array();
	while($pStmt->fetch()) {
		$proposals[] = array(
			"id" => $pID,
			"title" => $pTitle,
			"presenters" => $pPresenters,
			"type" => $pType,
			"status" => $pStatus,
			"session" => 0
		);
	}
	
	$pStmt->close();
	
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
	
	
		
	//get the schedule
	$sStmt = $db->prepare("SELECT * FROM `sessions` WHERE 1");
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
		$tmpPres = explode("|",$proposals[$p]["presenters"]);
		$thisPresenters = array();
		for($tp = 0; $tp < count($tmpPres); $tp++) {
			$tmpPRID = $tmpPres[$tp];
			for($pr = 0; $pr < count($presenters); $pr++) {
				if($presenters[$pr]["id"] == $tmpPRID) {
					$thisPresenters[] = $presenters[$pr];
				}
			}
		}
		
		$proposals[$p]["presenters"] = $thisPresenters;
		
		for($i = 0; $i < count($sessions); $i++) {
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
	
	//Now, do the same for the "other" presentations (non-solicited)
	$opStmt = $db->prepare("SELECT id, title, presenters FROM other_proposals WHERE 1 ORDER BY id");
	$opStmt->execute();
	$opStmt->bind_result($opID, $opTitle, $opPresenters);
	
	$otherProposals = array();
	while($opStmt->fetch()) {
		$otherProposals[] = array(
			"id" => $opID,
			"title" => $opTitle,
			"presenters" => $opPresenters,
			"type" => "other",
			"session" => 0
		);
	}
	
	$opStmt->close();
	
	$oprStmt = $db->prepare("SELECT `id`, `First Name`, `Last Name`, `Email` FROM `other_presenters` WHERE 1");
	$oprStmt->execute();
	$oprStmt->bind_result($oprID, $oprFN, $oprLN, $oprEmail);
	
	$otherPresenters = array();
	while($oprStmt->fetch()) {
		$otherPresenters[] = array(
			"id" => $oprID,
			"first_name" => $oprFN,
			"last_name" => $oprLN,
			"email" => $oprEmail
		);
	}
	
	$oprStmt->close();
	
	//Now, insert the presenter information into the otherProposals array
	for($op = 0; $op < count($otherProposals); $op++) {
		$tmpPres = explode("|",$otherProposals[$op]["presenters"]);
		$thisPresenters = array();
		for($tp = 0; $tp < count($tmpPres); $tp++) {
			$tmpPRID = $tmpPres[$tp];
			for($opr = 0; $opr < count($otherPresenters); $opr++) {
				if($otherPresenters[$opr]["id"] == $tmpPRID) {
					$thisPresenters[] = $otherPresenters[$opr];
					break; //presenters loop
				}
			}
		}
		
		$otherProposals[$op]["presenters"] = $thisPresenters;
	}
	
	//Now, insert the schedule information for the "other" presentations
	for($s = 0; $s < count($sessions); $s++) {
		if($sessions[$s]["event"] != "Other") continue; //skip any non-other presentations
		if($sessions[$s]["presentations"] == "") continue; //skip any sessions without presentations (e.g. ask us)
		
		$tmpPres = explode("||",$sessions[$s]["presentations"]);
		for($tP = 0; $tP < count($tmpPres); $tP++) {
			//There is no station and only one presentation per session, so all presentation strings start with '0|'.
			//We just split by '|' and ignore the first element.
			list($tmpStation,$tmpID) = explode("|",$sessions[$s]["presentations"]);
			for($op = 0; $op < count($otherProposals); $op++) {
				if($tmpID == $otherProposals[$op]["id"]) { // found the right proposal
					$otherProposals[$op]["session"] = $sessions[$s]["date"]."|".$sessions[$s]["time"];
					break; //proposals loop
				}
			}
		}
	}
	
	//Now, do the same for the "classics" presentations
	$cpStmt = $db->prepare("SELECT id, title, presenters FROM classics_proposals WHERE 1 ORDER BY id");
	$cpStmt->execute();
	$cpStmt->bind_result($cpID, $cpTitle, $cpPresenters);
	
	$classicsProposals = array();
	while($cpStmt->fetch()) {
		$classicsProposals[] = array(
			"id" => $cpID,
			"title" => $cpTitle,
			"presenters" => $cpPresenters,
			"type" => "classics"
		);
	}
	
	$cpStmt->close();
	
	$cprStmt = $db->prepare("SELECT `id`, `First Name`, `Last Name`, `Email` FROM `classics_presenters` WHERE 1");
	$cprStmt->execute();
	$cprStmt->bind_result($cprID, $cprFN, $cprLN, $cprEmail);
	
	$classicsPresenters = array();
	while($cprStmt->fetch()) {
		$classicsPresenters[] = array(
			"id" => $cprID,
			"first_name" => $cprFN,
			"last_name" => $cprLN,
			"email" => $cprEmail
		);
	}
	
	$cprStmt->close();
	
	//Now, insert the presenter information into the classicsProposals array
	for($cp = 0; $cp < count($classicsProposals); $cp++) {
		$tmpPres = explode("|",$classicsProposals[$cp]["presenters"]);
		$thisPresenters = array();
		for($tp = 0; $tp < count($tmpPres); $tp++) {
			$tmpPRID = $tmpPres[$tp];
			for($cpr = 0; $cpr < count($classicsPresenters); $cpr++) {
				if($classicsPresenters[$cpr]["id"] == $tmpPRID) {
					$thisPresenters[] = $classicsPresenters[$cpr];
					break; //presenters loop
				}
			}
		}
		
		$classicsProposals[$cp]["presenters"] = $thisPresenters;
	}	
	
	//Now, insert the schedule information for the "classics" presentations
	for($s = 0; $s < count($sessions); $s++) {
		if($sessions[$s]["title"] != "Technology Fair: Classics") continue; //skip any non-other presentations
		if($sessions[$s]["presentations"] == "") continue; //skip any sessions without presentations (e.g. ask us)
		
		$tmpPres = explode("||",$sessions[$s]["presentations"]);
		for($tP = 0; $tP < count($tmpPres); $tP++) {
			//We just split by '|' and ignore the first element.
			list($tmpStation, $tmpID) = explode("|",$tmpPres[$tP]);
			for($cp = 0; $cp < count($classicsProposals); $cp++) {
				if($tmpID == $classicsProposals[$cp]["id"]) { // found the right proposal
					$classicsProposals[$cp]["session"] = $sessions[$s]["date"]."|".$sessions[$s]["time"];
					for($tS = 0; $tS < count($stations); $tS++) {
						if($stations[$tS]["id"] == $tmpStation) {
							$classicsProposals[$cp]["session"] .= "|".$stations[$tS]["name"];
							break; //stations loop
						}
					}
					break; //proposals loop
				}
			}
		}
	}
	
	if($_POST) {
		//First, get the message and sanitize it
		$message = filter_var($_POST["messageTxt"], FILTER_SANITIZE_STRING);
		$subject = filter_var($_POST["subjectTxt"], FILTER_SANITIZE_STRING);
		$selectedProposals = preg_replace("/[^0-9\|]/","",$_POST["selectedProposals"]);
		$selectedOther = preg_replace("/[^0-9\|]/","",$_POST["selectedOtherProposals"]);
		$selectedClassics = preg_replace("/[^0-9\|]/","",$_POST["selectedClassicsProposals"]);
		
		/*
			The user is allowed to include "flags" to include specific information from each proposal or author
			in the email message. The "flags" are enclosed in "[%" and "%]". The following flags are currenlty
			allowed:
			
				- First name ([%FIRST NAME%])
				- Last name ([%LAST NAME%])
				- Proposal title ([%PROPOSAL TITLE%])
				- Proposal abstract - the longer description for reviewers ([%PROPOSAL ABSTRACT%])
				- Proposal summary - the shorter description for the program book ([%PROPOSAL SUMMARY%])
				
				FOR SCHEDULED PROPOSALS ONLY:
				- Schedule date ([%SESSION DATE%])
				- Schedule time ([%SESSION TIME%])
				
			Any other "flags" are ignored. So, after we sanitize the string, we need to remove any unrecognized
			"flags" from the message string. We will do this by getting a list of all the [% %] pairs and
			checking them.
		 */
		 
		 $tmpStr = array();
		 $tmpE = strpos($message, "[%");
		 $tmpS = 0;
		 $allowedFlags = array(
		 	"[%FIRST NAME%]",			//first name
		 	"[%LAST NAME%]",			//last name
		 	"[%PROPOSAL TITLE%]",		//proposal title
		 	"[%SESSION DATE%]",			//session date
		 	"[%SESSION TIME%]"			//session time
		 );
		 
		 while($tmpE !== false) {
		 	$tmpStr[] = substr($message, $tmpS, $tmpE - $tmpS); //grab anything before the start of this flag
		 	$tmpS = $tmpE; //move the start to the beginning of the flag
		 	$tmpE = strpos($message, "%]", $tmpS) + 2; //find the end of the flag (add 2 to include the "%]")
		 	$tmpFlag = substr($message, $tmpS, $tmpE - $tmpS); //grab the flag
		 	if(!in_array($tmpFlag, $allowedFlags)) $tmpFlag = ""; //remove any flags not allowed by blanking them out
		 	$tmpStr[] = $tmpFlag; //include the flag in the new string we are building
		 	$tmpS = $tmpE; //move the start to the end of the last string we grabbed
		 	$tmpE = strpos($message, "[%", $tmpS); //look for another flag start
		 }
		 
		 $tmpStr[] = substr($message, $tmpS, strlen($message) - $tmpS); //grab the end of the string after the last flag
		
		//Now, put the message back together
		$original_message = join($tmpStr);

		$sendSuccess = array();
		$sendFail = array();
		$sendOtherSuccess = array();
		$sendOtherFail = array();
		$sendClassicsSuccess = array();
		$sendClassicsFail = array();
					
		if($selectedProposals != "") {	
			$tmpProps = explode("|",$selectedProposals);
		
			for($i = 0; $i < count($tmpProps); $i++) {
				for($j = 0; $j < count($proposals); $j++) {
					if($proposals[$j]["id"] == $tmpProps[$i]) {
						for($k = 0; $k < count($proposals[$j]["presenters"]); $k++) {
							$to = "justin@jshewell.com";
							//$to = $proposals[$j]["presenters"][$k]["email"];
							if($_SESSION["user_role"] == "lead_fairs") $from = "ev-fair@call-is.org";
							else if($_SESSION["user_role"] == "lead_mini") $from = "ev-mini@call-is.org";
							else if($_SESSION["user_role"] == "lead_ds") $from = "ev-ds@call-is.org";
							else if($_SESSION["user_role"] == "lead_mae") $from = "ev-mae@call-is.org";
							else $from = "ev@call-is.org";
						
							//$cc = $from;
						
							//Add in the proposal specific information
							$message = str_replace("[%FIRST NAME%]",stripslashes($proposals[$j]["presenters"][$k]["first_name"]),$original_message);
							$message = str_replace("[%LAST NAME%]",stripslashes($proposals[$j]["presenters"][$k]["last_name"]),$message);
							$message = str_replace("[%PROPOSAL TITLE%]",stripslashes($proposals[$j]["title"]),$message);
						
							$tmpSes = explode("|",$proposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSes[0]);
							$sesDate = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					
							$tmpTime = explode("-",$tmpSes[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$sesTime = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
				
							$sesTime .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
						
							if($sesDate != "") $message = str_replace("[%SESSION DATE%]",$sesDate,$message);
							else $message = str_replace("[%SESSION_DATE%]","",$message);
						
							if($sesTime != "") $message = str_replace("[%SESSION TIME%]",$sesTime,$message);
							else $message = str_replace("[%SESSION TIME%]","",$message);

							//define the headers we want passed. Note that they are separated with \r\n
							//$headers = "MIME-Version: 1.0\r\nFrom: ".$from."\r\nCC: ".$cc."\r\nReply-To: ".$from."\r\n";
							$headers = "MIME-Version: 1.0\r\nFrom: ".$from."\r\nReply-To: ".$from."\r\n";

							//send the email
							$mail_sent = @mail( $to, $subject, $message, $headers );

							//if the message is sent successfully print out the confirmation page
							if($mail_sent) $sendSuccess[] = $proposals[$j];
							else $sendFail[] = $proposals[$j];
						}
						
						break; //proposals loop
					}
				}
			}
		}
		
		if($selectedOther != "") {	
			$tmpOther = explode("|",$selectedOther);
		
			for($i = 0; $i < count($tmpOther); $i++) {
				for($j = 0; $j < count($otherProposals); $j++) {
					if($otherProposals[$j]["id"] == $tmpOther[$i]) {
						for($k = 0; $k < count($otherProposals[$j]["presenters"]); $k++) {
							$to = "justin@jshewell.com";
							//$to = $otherProposals[$j]["presenters"][$k]["email"];
							$from = "ev@call-is.org";
							$cc = $from;
						
							//Add in the proposal specific information
							$message = str_replace("[%FIRST NAME%]",stripslashes($otherProposals[$j]["presenters"][$k]["first_name"]),$original_message);
							$message = str_replace("[%LAST NAME%]",stripslashes($otherProposals[$j]["presenters"][$k]["last_name"]),$message);
							$message = str_replace("[%PROPOSAL TITLE%]",stripslashes($otherProposals[$j]["title"]),$message);
						
							$tmpSes = explode("|",$otherProposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSes[0]);
							$sesDate = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					
							$tmpTime = explode("-",$tmpSes[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$sesTime = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
				
							$sesTime .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
						
							if($sesDate != "") $message = str_replace("[%SESSION DATE%]",$sesDate,$message);
							else $message = str_replace("[%SESSION_DATE%]","",$message);
						
							if($sesTime != "") $message = str_replace("[%SESSION TIME%]",$sesTime,$message);
							else $message = str_replace("[%SESSION TIME%]","",$message);

							//define the headers we want passed. Note that they are separated with \r\n
							$headers = "MIME-Version: 1.0\r\nFrom: ".$from."\r\nCC: ".$cc."\r\nReply-To: ".$from."\r\n";

							//send the email
							$mail_sent = @mail( $to, $subject, $message, $headers );

							//if the message is sent successfully print out the confirmation page
							if($mail_sent) $sendOtherSuccess[] = $otherProposals[$j];
							else $sendOtherFail[] = $otherProposals[$j];
							break; //presenters loop (for debugging);
						}


						break; //proposals loop
					}
				}
			
				break; //tmpProps loop (for debugging);
			}
		}
		
		if($selectedClassics != "") {	
			$tmpClassics = explode("|",$selectedClassics);
		
			for($i = 0; $i < count($tmpClassics); $i++) {
				for($j = 0; $j < count($classicsProposals); $j++) {
					if($classicsProposals[$j]["id"] == $tmpClassics[$i]) {
						for($k = 0; $k < count($classicsProposals[$j]["presenters"]); $k++) {
							$to = "justin@jshewell.com";
							//$to = $classicsProposals[$j]["presenters"][$k]["email"];
							$from = "ev@call-is.org";
							$cc = $from;
						
							//Add in the proposal specific information
							$message = str_replace("[%FIRST NAME%]",stripslashes($classicsProposals[$j]["presenters"][$k]["first_name"]),$original_message);
							$message = str_replace("[%LAST NAME%]",stripslashes($classicsProposals[$j]["presenters"][$k]["last_name"]),$message);
							$message = str_replace("[%PROPOSAL TITLE%]",stripslashes($classicsProposals[$j]["title"]),$message);
						
							$tmpSes = explode("|",$classicsProposals[$j]["session"]);
							$tmpDate = explode("-",$tmpSes[0]);
							$sesDate = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					
							$tmpTime = explode("-",$tmpSes[1]);
							$tmpStart = explode(":",$tmpTime[0]);
							$tmpSHour = intval($tmpStart[0]);
							if($tmpSHour < 12) $sAMPM = "AM";
							else {
								$sAMPM = "PM";
								if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
							}
							$tmpSMinutes = $tmpStart[1];
					
							$sesTime = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";
					
							$tmpEnd = explode(":",$tmpTime[1]);
							$tmpEHour = intval($tmpEnd[0]);
							if($tmpEHour < 12) $eAMPM = "AM";
							else {
								$eAMPM = "PM";
								if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
							}
							$tmpEMinutes = $tmpEnd[1];
				
							$sesTime .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;
						
							if($sesDate != "") $message = str_replace("[%SESSION DATE%]",$sesDate,$message);
							else $message = str_replace("[%SESSION_DATE%]","",$message);
						
							if($sesTime != "") $message = str_replace("[%SESSION TIME%]",$sesTime,$message);
							else $message = str_replace("[%SESSION TIME%]","",$message);

							//define the headers we want passed. Note that they are separated with \r\n
							$headers = "MIME-Version: 1.0\r\nFrom: ".$from."\r\nCC: ".$cc."\r\nReply-To: ".$from."\r\n";

							//send the email
							$mail_sent = @mail( $to, $subject, $message, $headers );

							//if the message is sent successfully print out the confirmation page
							if($mail_sent) $sendClassicsSuccess[] = $classicsProposals[$j];
							else $sendClassicsFail[] = $classicsProposals[$j];
							break; //presenters loop (for debugging);
						}


						break; //proposals loop
					}
				}
			
				break; //tmpProps loop (for debugging);
			}
		}
		
		//Now, show any proposals where mail couldn't be sent
		include "adminTop.php";
		
		if(count($sendSuccess) > 0) {
?>
	<h3 align="center"><?=count($sendSuccess)?> emails sent successfully!</h3>
<?php
		}
		
		if(count($sendFail) > 0) {
?>
	<p>The following emails could not be sent successfully. Please check the email addresses:</p>
	<p stlye="margin-left: 10">
<?php
			for($fI = 0; $fI < count($sendFail); $fI++) {
				for($fP = 0; $fP < count($sendFail[$fI]["presenters"]); $fP++) {
					echo $sendFail[$fI]["presenters"][$fP]["email"]."<br />";
				}
			}
?>
	</p>
<?php
		}

		if(count($sendOtherSuccess) > 0) {
?>
	<h3 align="center"><?=count($sendOtherSuccess)?> "other" emails sent successfully!</h3>
<?php
		}
		
		if(count($sendOtherFail) > 0) {
?>
	<p>The following "other" emails could not be sent successfully. Please check the email addresses:</p>
	<p stlye="margin-left: 10">
<?php
			for($fI = 0; $fI < count($sendOtherFail); $fI++) {
				for($fP = 0; $fP < count($sendOtherFail[$fI]["presenters"]); $fP++) {
					echo $sendOtherFail[$fI]["presenters"][$fP]["email"]."<br />";
				}
			}
?>
	</p>
<?php
		}

		if(count($sendClassicsSuccess) > 0) {
?>
	<h3 align="center"><?=count($sendClassicsSuccess)?> "classics" emails sent successfully!</h3>
<?php
		}
		
		if(count($sendClassicsFail) > 0) {
?>
	<p>The following "classics" emails could not be sent successfully. Please check the email addresses:</p>
	<p stlye="margin-left: 10">
<?php
			for($fI = 0; $fI < count($sendClassicsFail); $fI++) {
				for($fP = 0; $fP < count($sendClassicsFail[$fI]["presenters"]); $fP++) {
					echo $sendClassicsFail[$fI]["presenters"][$fP]["email"]."<br />";
				}
			}
?>
	</p>
<?php
		}
?>
	<p align="center"><a href="sendEmails.php">Send More Emails</a></p>
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
	
	$propList["Other"] = $otherProposals;
	$propList["Technology Fair: Classics"] = $classicsProposals;
	
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
	} else $eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Other","Technology Fair: Classics");
	
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
	</style>
	<script type="text/javascript">
		var proposals = new Array();
		var otherProposals = new Array();
		var classicsProposals = new Array();
		var selectedProps = new Array();
		var selectedOtherProps = new Array();
		var selectedClassicsProps = new Array();
		
		var events = new Array();
<?php
	for($e = 0; $e < count($eTypes); $e++) {
?>
		events[<?=$e?>] = '<?=$eTypes[$e]?>';
<?php
	}

	$pIndex = 0;
	for($e = 0; $e < count($eTypes); $e++) {
		if($eTypes[$e] != "Other" && $eTypes[$e] != "Technology Fair: Classics") {
			$tE = $eTypes[$e];
			$pStatus = array("scheduled","accepted","rejected");
			for($s = 0; $s < count($pStatus); $s++) {
				if(!array_key_exists($pStatus[$s],$propList[$tE])) continue;
				$tS = $pStatus[$s];
				for($i = 0; $i < count($propList[$tE][$tS]); $i++) {
?>
		
		proposals[<?=$pIndex?>] = new Array();
		proposals[<?=$pIndex?>]['id'] = '<?=$propList[$tE][$tS][$i]["id"]?>';
		proposals[<?=$pIndex?>]['event'] = '<?=$tE?>';
		proposals[<?=$pIndex?>]['status'] = '<?=$tS?>';
		proposals[<?=$pIndex?>]['title'] = '<?=addslashes(stripslashes($propList[$tE][$tS][$i]["title"]))?>';
<?php
					//get the presenters names
					$tmpPres = $propList[$tE][$tS][$i]["presenters"];
?>
		
		proposals[<?=$pIndex?>]['presenters'] = new Array();
<?php
					for($tP = 0; $tP < count($tmpPres); $tP++) {
?>
		proposals[<?=$pIndex?>]['presenters'][<?=$tP?>] = new Array();
		proposals[<?=$pIndex?>]['presenters'][<?=$tP?>]['first_name'] = '<?=addslashes(stripslashes($tmpPres[$tP]["first_name"]))?>';
		proposals[<?=$pIndex?>]['presenters'][<?=$tP?>]['last_name'] = '<?=addslashes(stripslashes($tmpPres[$tP]["last_name"]))?>';
		proposals[<?=$pIndex?>]['presenters'][<?=$tP?>]['email'] = '<?=$tmpPres[$tP]["email"]?>';
<?php		
					}

					//get the session information (if there)
					if($tS == "scheduled") {
						$tmpSes = explode("|",$propList[$tE][$tS][$i]["session"]);
						$tmpDate = explode("-",$tmpSes[0]);
?>
		
		proposals[<?=$pIndex?>]['date'] = '<?=$months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0]?>';
<?php
						$tmpTime = explode("-",$tmpSes[1]);
						$tmpStart = explode(":",$tmpTime[0]);
						$tmpSHour = intval($tmpStart[0]);
						if($tmpSHour < 12) $sAMPM = "AM";
						else {
							$sAMPM = "PM";
							if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
						}
						$tmpSMinutes = $tmpStart[1];
?>
		proposals[<?=$pIndex?>]['start_time'] = '<?=$tmpSHour.":".$tmpSMinutes." ".$sAMPM?>';
<?php					
						$tmpEnd = explode(":",$tmpTime[1]);
						$tmpEHour = intval($tmpEnd[0]);
						if($tmpEHour < 12) $eAMPM = "AM";
						else {
							$eAMPM = "PM";
							if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
						}
						$tmpEMinutes = $tmpEnd[1];
?>
		proposals[<?=$pIndex?>]['end_time'] = '<?=$tmpEHour.":".$tmpEMinutes." ".$eAMPM?>';
<?php
						if($tE == "Technology Fairs" && count($tmpSes) > 2) {
?>
		proposals[<?=$pIndex?>]['station'] = '<?=$tmpSes[2]?>';		
<?php
						}
					}
					
					$pIndex++;
				}
			}
		} else {
			$tE = $eTypes[$e];
			for($i = 0; $i < count($propList[$tE]); $i++) {
				if($tE == "Other") {
?>
		
		otherProposals[<?=$i?>] = new Array();
		otherProposals[<?=$i?>]['id'] = '<?=$propList[$tE][$i]["id"]?>';
		otherProposals[<?=$i?>]['title'] = '<?=addslashes(stripslashes($propList[$tE][$i]["title"]))?>';
<?php
				} else if($tE == "Technology Fair: Classics") {
?>
		
		classicsProposals[<?=$i?>] = new Array();
		classicsProposals[<?=$i?>]['id'] = '<?=$propList[$tE][$i]["id"]?>';
		classicsProposals[<?=$i?>]['title'] = '<?=addslashes(stripslashes($propList[$tE][$i]["title"]))?>';
<?php
				}
				
				//get the presenters names
				$tmpPres = $propList[$tE][$i]["presenters"];
				if($tE == "Other") {
?>
		
		otherProposals[<?=$i?>]['presenters'] = new Array();
<?php
				} else if($tE == "Technology Fair: Classics") {
?>
		
		classicsProposals[<?=$i?>]['presenters'] = new Array();
<?php
				}

				for($tP = 0; $tP < count($tmpPres); $tP++) {
					if($tE == "Other") {
?>
		otherProposals[<?=$i?>]['presenters'][<?=$tP?>] = new Array();
		otherProposals[<?=$i?>]['presenters'][<?=$tP?>]['first_name'] = '<?=addslashes(stripslashes($tmpPres[$tP]["first_name"]))?>';
		otherProposals[<?=$i?>]['presenters'][<?=$tP?>]['last_name'] = '<?=addslashes(stripslashes($tmpPres[$tP]["last_name"]))?>';
		otherProposals[<?=$i?>]['presenters'][<?=$tP?>]['email'] = '<?=addslashes(stripslashes($tmpPres[$tP]["email"]))?>';
<?php
					} else if($tE == "Technology Fair: Classics") {
?>
		classicsProposals[<?=$i?>]['presenters'][<?=$tP?>] = new Array();
		classicsProposals[<?=$i?>]['presenters'][<?=$tP?>]['first_name'] = '<?=addslashes(stripslashes($tmpPres[$tP]["first_name"]))?>';
		classicsProposals[<?=$i?>]['presenters'][<?=$tP?>]['last_name'] = '<?=addslashes(stripslashes($tmpPres[$tP]["last_name"]))?>';
		classicsProposals[<?=$i?>]['presenters'][<?=$tP?>]['email'] = '<?=addslashes(stripslashes($tmpPres[$tP]["email"]))?>';
<?php
					}
				}

				$tmpSes = explode("|",$propList[$tE][$i]["session"]);
				$tmpDate = explode("-",$tmpSes[0]);
				if($tE == "Other") {
?>

		otherProposals[<?=$i?>]['date'] = '<?=$months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0]?>';
<?php
				} else if($tE == "Technology Fair: Classics") {
?>

		classicsProposals[<?=$i?>]['date'] = '<?=$months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0]?>';
<?php
				}
	
				$tmpTime = explode("-",$tmpSes[1]);
				$tmpStart = explode(":",$tmpTime[0]);
				$tmpSHour = intval($tmpStart[0]);
				if($tmpSHour < 12) $sAMPM = "AM";
				else {
					$sAMPM = "PM";
					if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
				}
				$tmpSMinutes = $tmpStart[1];
				if($tE == "Other") {
?>
		otherProposals[<?=$i?>]['start_time'] = '<?=$tmpSHour.":".$tmpSMinutes." ".$sAMPM?>';
<?php
				} else if($tE == "Technology Fair: Classics") {
?>
		classicsProposals[<?=$i?>]['start_time'] = '<?=$tmpSHour.":".$tmpSMinutes." ".$sAMPM?>';
<?php
				}

				$tmpEnd = explode(":",$tmpTime[1]);
				$tmpEHour = intval($tmpEnd[0]);
				if($tmpEHour < 12) $eAMPM = "AM";
				else {
				$eAMPM = "PM";
					if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
				}
				$tmpEMinutes = $tmpEnd[1];
				if($tE == "Other") {
?>
		otherProposals[<?=$i?>]['end_time'] = '<?=$tmpEHour.":".$tmpEMinutes." ".$eAMPM?>';
<?php
				} else if($tE == "Technology Fair: Classics") {
?>
		classicsProposals[<?=$i?>]['end_time'] = '<?=$tmpEHour.":".$tmpEMinutes." ".$eAMPM?>';
<?php
				}
	
				if(count($tmpSes) > 2) {
					if($tE == "Other") {
?>
		otherProposals[<?=$i?>]['station'] = '<?=$tmpSes[2]?>';
<?php
					} else if($tE == "Technology Fair: Classics") {
?>
		classicsProposals[<?=$i?>]['station'] = '<?=$tmpSes[2]?>';
<?php
					}
				}
			}
		}
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
				//get the event type from the element id
				var eS = el.id.indexOf('event');
				if(eS != -1) eS = parseInt(eS) + parseInt(5);
				var eE = el.id.indexOf('_');
				if(eE != -1) {
					var eI = parseInt(el.id.substring(eS,eE));
					if(events[eI] == 'Other') var t = 'other';
					else if(events[eI] == 'Technology Fair: Classics') var t = 'classics';
					else var t = '';
				} else var t = '';
				
				if(el.id.indexOf('_all') != -1 || el.id.indexOf('_sendAll') != -1) { //check all the proposals in this section
					if(el.id.indexOf('_all') != -1) {
						var elIDStr = el.id.replace('_all','');
						document.getElementById(elIDStr + '_sendAll').checked = el.checked;
					} else if(el.id.indexOf('_sendAll') != -1) {
						var elIDStr = el.id.replace('_sendAll','');
						document.getElementById(elIDStr + '_all').checked = el.checked;
					}
					
					var tStr = elIDStr + '_propTable';
					var tRows = document.getElementById(tStr).rows;
					var selCount = 0;
					for(r = 1; r < tRows.length; r++) { //skip the header row
						var rN = r - 1;
						var rStr = elIDStr + '_row' + rN;
						var pR = document.getElementById(rStr);
						var cbStr = rStr.replace('row','chk');
						var cbEl = document.getElementById(cbStr);

						if(el.checked) { //the box is checked, so save this as one of the proposals to notify
							if(t == '') {
								var isAlready = false;
								for(p = 0; p < selectedProps.length; p++) {
									if(selectedProps[p] == cbEl.value) isAlready = true; //proposal already selected
								}
							
								cbEl.checked = true;
								if(!isAlready) { //not already in the proposals array, so add it
									var pI = selectedProps.length;
									selectedProps[pI] = cbEl.value;
								}
								
								for(c = 0; c < pR.cells.length; c++) {
									if(el.id.indexOf('accepted') != -1 || el.id.indexOf('scheduled') != -1) pR.cells[c].className = 'pList_accepted';
									else if(el.id.indexOf('rejected') != -1) pR.cells[c].className = 'pList_rejected';
								}
							} else if(t == 'other') {
								var isAlready = false;
								for(p = 0; p < selectedOtherProps.length; p++) {
									if(selectedOtherProps[p] == cbEl.value) isAlready = true; //proposal already selected
								}
							
								cbEl.checked = true;
								if(!isAlready) { //not already in the proposals array, so add it
									var pI = selectedOtherProps.length;
									selectedOtherProps[pI] = cbEl.value;
								}
								
								for(c = 0; c < pR.cells.length; c++) {
									pR.cells[c].className = 'pList_accepted';
								}							
							} else if(t == 'classics') { 
								var isAlready = false;
								for(p = 0; p < selectedClassicsProps.length; p++) {
									if(selectedClassicsProps[p] == cbEl.value) isAlready = true; //proposal already selected
								}
							
								cbEl.checked = true;
								if(!isAlready) { //not already in the proposals array, so add it
									var pI = selectedClassicsProps.length;
									selectedClassicsProps[pI] = cbEl.value;
								}
								
								for(c = 0; c < pR.cells.length; c++) {
									pR.cells[c].className = 'pList_accepted';
								}
							}
							
							selCount++;
						} else { //el is unchecked
							if(t == '') {
								for(p = 0; p < selectedProps.length; p++) {
									if(selectedProps[p] == cbEl.value) selectedProps.splice(p,1); //remove the proposal from the array
									break;
								}
					
								cbEl.checked = false;
								for(c = 0; c < pR.cells.length; c++) {
									if(rN % 2 == 0) pR.cells[c].className = 'pList_rowEven';
									else pR.cells[c].className = 'pList_rowOdd';
								}
							} else if(t == 'other') {
								for(p = 0; p < selectedOtherProps.length; p++) {
									if(selectedOtherProps[p] == cbEl.value) selectedOtherProps.splice(p,1); //remove the proposal from the array
									break;
								}
					
								cbEl.checked = false;
								for(c = 0; c < pR.cells.length; c++) {
									if(rN % 2 == 0) pR.cells[c].className = 'pList_rowEven';
									else pR.cells[c].className = 'pList_rowOdd';
								}
							} else if(t == 'classics') {
								for(p = 0; p < selectedClassicsProps.length; p++) {
									if(selectedClassicsProps[p] == cbEl.value) selectedClassicsProps.splice(p,1); //remove the proposal from the array
									break;
								}
					
								cbEl.checked = false;
								for(c = 0; c < pR.cells.length; c++) {
									if(rN % 2 == 0) pR.cells[c].className = 'pList_rowEven';
									else pR.cells[c].className = 'pList_rowOdd';
								}
							}
							
							selCount--;
							if(selCount < 0) selCount = 0;
						}
						
						document.getElementById(elIDStr + '_selectedNum').innerHTML = selCount;
					}
				} else { //selected a single proposal
					var rStr = el.id.replace('chk','row');
					var rS = rStr.indexOf('row');
					if(rS != -1) rS = parseInt(rS) + parseInt(3);
					var rN = rStr.substring(rS);
				
					var pR = document.getElementById(rStr);			
					if(el.checked) { //the box is checked, so save this as one of the proposals to notify
						if(t == '') {
							for(p = 0; p < selectedProps.length; p++) {
								if(selectedProps[p] == el.value) return false; //proposal already selected
							}
					
							var pI = selectedProps.length;
							selectedProps[pI] = el.value;
							for(c = 0; c < pR.cells.length; c++) {
								if(el.id.indexOf('accepted') != -1 || el.id.indexOf('scheduled') != -1)
									pR.cells[c].className = 'pList_accepted_highlighted';
								else if(el.id.indexOf('rejected') != -1) pR.cells[c].className = 'pList_rejected_highlighted';
							}
						} else if(t == 'other') {
							for(p = 0; p < selectedOtherProps.length; p++) {
								if(selectedOtherProps[p] == el.value) return false; //proposal already selected
							}
					
							var pI = selectedOtherProps.length;
							selectedOtherProps[pI] = el.value;
							for(c = 0; c < pR.cells.length; c++) {
								pR.cells[c].className = 'pList_accepted_highlighted';
							}
						} else if(t == 'classics') {
							for(p = 0; p < selectedClassicsProps.length; p++) {
								if(selectedClassicsProps[p] == el.value) return false; //proposal already selected
							}
					
							var pI = selectedClassicsProps.length;
							selectedClassicsProps[pI] = el.value;
							for(c = 0; c < pR.cells.length; c++) {
								pR.cells[c].className = 'pList_accepted_highlighted';
							}
						}
						
						var snS = el.id.indexOf('chk');
						var tmpSN = el.id.substring(0, snS);
						var snID = tmpSN + 'selectedNum';
						var sn = parseInt(document.getElementById(snID).innerHTML);
						sn++;
						document.getElementById(snID).innerHTML = sn;
					} else { //el is unchecked
						if(t == '') {
							for(p = 0; p < selectedProps.length; p++) {
								if(selectedProps[p] == el.value) {
									selectedProps.splice(p,1); //remove the proposal from the array
									break;
								}
							}
					
							for(c = 0; c < pR.cells.length; c++) {
								if(rN % 2 == 0) pR.cells[c].className = 'pList_rowEven';
								else pR.cells[c].className = 'pList_rowOdd';
							}
						} else if(t == 'other') {
							for(p = 0; p < selectedOtherProps.length; p++) {
								if(selectedOtherProps[p] == el.value) {
									selectedOtherProps.splice(p,1); //remove the proposal from the array
									break;
								}
							}
					
							for(c = 0; c < pR.cells.length; c++) {
								if(rN % 2 == 0) pR.cells[c].className = 'pList_rowEven';
								else pR.cells[c].className = 'pList_rowOdd';
							}
						} else if(t == 'classics') {
							for(p = 0; p < selectedClassicsProps.length; p++) {
								if(selectedClassicsProps[p] == el.value) {
									selectedClassicsProps.splice(p,1); //remove the proposal from the array
									break;
								}
							}
					
							for(c = 0; c < pR.cells.length; c++) {
								if(rN % 2 == 0) pR.cells[c].className = 'pList_rowEven';
								else pR.cells[c].className = 'pList_rowOdd';
							}
						}
						
						var snS = el.id.indexOf('chk');
						var tmpSN = el.id.substring(0, snS);
						var snID = tmpSN + 'selectedNum';
						var sn = parseInt(document.getElementById(snID).innerHTML);
						sn--;
						if(sn < 0) sn = 0;
						document.getElementById(snID).innerHTML = sn;
					}
				}				
			}

			getEmails();
		}
			
		function getEmails() {
			var emailCount = 0;
			var emailStr = '';
			for(p = 0; p < selectedProps.length; p++) {
				for(pI = 0; pI < proposals.length; pI++) {
					if(proposals[pI]['id'] == selectedProps[p]) { //found the right proposal
						for(ppI = 0; ppI < proposals[pI]['presenters'].length; ppI++) {
							//check to see if we've already got this email in the string
							if(emailStr.indexOf(proposals[pI]['presenters'][ppI]['email']) != -1) continue; //skip if already in the string
							
							if(emailStr != '') emailStr += ', ';
							emailStr += proposals[pI]['presenters'][ppI]['email'];
							emailCount++;
						}
						
						break; //proposals loop
					}
				}
			}
			
			for(p = 0; p < selectedOtherProps.length; p++) {
				for(pI = 0; pI < otherProposals.length; pI++) {
					if(otherProposals[pI]['id'] == selectedOtherProps[p]) { //found the right proposal
						for(ppI = 0; ppI < otherProposals[pI]['presenters'].length; ppI++) {
							//check to see if we've already got this email in the string
							if(emailStr.indexOf(otherProposals[pI]['presenters'][ppI]['email']) != -1) continue; //skip if already in the string
							
							if(emailStr != '') emailStr += ', ';
							emailStr += otherProposals[pI]['presenters'][ppI]['email'];
							emailCount++;
						}
						
						break; //otherProposals loop
					}
				}
			}

			for(p = 0; p < selectedClassicsProps.length; p++) {
				for(pI = 0; pI < classicsProposals.length; pI++) {
					if(classicsProposals[pI]['id'] == selectedClassicsProps[p]) { //found the right proposal
						for(ppI = 0; ppI < classicsProposals[pI]['presenters'].length; ppI++) {
							//check to see if we've already got this email in the string
							if(emailStr.indexOf(classicsProposals[pI]['presenters'][ppI]['email']) != -1) continue; //skip if already in the string
							
							if(emailStr != '') emailStr += ', ';
							emailStr += classicsProposals[pI]['presenters'][ppI]['email'];
							emailCount++;
						}
						
						break; //classicsProposals loop
					}
				}
			}
			
			//Now we have all the emails from each proposal in one string, so populate the textbox
			document.getElementById('selectedEmails').value = emailStr;
			document.getElementById('selectedEmailNum').innerHTML = emailCount;
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
		
		//window.onscroll = function() {
		//	checkHeader();
		//};
		
		function viewProposals(str, el) {
			if(el.value.indexOf('Hide') == -1) { //not currently showing proposals
				document.getElementById(str).style.display = '';
				el.value = 'Hide Individual Proposals';
			} else { //showing, so hide
				document.getElementById(str).style.display = 'none';
				el.value = 'View Individual Proposals';
			}
		}
		
		function showInstructions(el) {
			if(el.value.indexOf('Hide') != -1) { //already showing
				document.getElementById('instructions').style.display = 'none';
				document.getElementById('instructionsBtn').value = 'Show Instructions';
			} else { //not showing
				document.getElementById('instructions').style.display = '';
				document.getElementById('instructionsBtn').value = 'Hide Instructions';
			}
		}
	</script>
		<table id="msgTable" border="0" align="center" cellpadding="5" width="800" cellspacing="0">
			<tr>
				<td style="padding-top: 25px">Copy and paste the email addresses below into the <span style="font-weight: bold">BCC field</span> of your email client.</td>
			</tr>
			<tr>
				<td style="text-align: left; font-weight: bold">Selected Emails (<span id="selectedEmailNum">0</span>):<br>
					<textarea id="selectedEmails" rows="10" style="font-size: 10pt; font-family: Courier; width: 100%"></textarea>
				</td>
			</tr>
		</table><br /><br />
<?php
	for($e = 0; $e < count($eTypes); $e++) {
		if($eTypes[$e] != "Other" && $eTypes[$e] != "Technology Fair: Classics") {
			$tE = $eTypes[$e];
			$pStatus = array("scheduled","accepted","rejected");
			for($s = 0; $s < count($pStatus); $s++) {
				if(!array_key_exists($pStatus[$s],$propList[$tE])) continue;
					$tS = $pStatus[$s];
					ob_start();
					$rN = 0;
					for($i = 0; $i < count($propList[$tE][$tS]); $i++) {
						if($rN % 2 == 0) $rowClass = 'pList_rowEven';
						else $rowClass = 'pList_rowOdd';
				
						//get the presenters names
						$tmpPres = $propList[$tE][$tS][$i]["presenters"];
						if(count($tmpPres) > 1) $presStr = "<ol>";
						else $presStr = "";
						for($tP = 0; $tP < count($tmpPres); $tP++) {
							if(count($tmpPres) > 1) $presStr .= "<li>".$tmpPres[$tP]["first_name"]." ".$tmpPres[$tP]["last_name"]."</li>";
							else $presStr .= $tmpPres[$tP]["first_name"]." ".$tmpPres[$tP]["last_name"];
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
		<tr id="event<?=$e?>_<?=$tS?>_row<?=$rN?>">
			<td class="<?=$rowClass?>" width="50" valign="center" style="text-align: center" onMouseOver="highlightRow('event<?=$e?>_<?=$tS?>_row<?=$rN?>',1)" onMouseOut="highlightRow('event<?=$e?>_<?=$tS?>_row<?=$rN?>',0)"><input type="checkbox" name="event<?=$e?>_<?=$tS?>_chk<?=$rN?>" id="event<?=$e?>_<?=$tS?>_chk<?=$rN?>" onClick="selectProposal(this)" value="<?=$propList[$tE][$tS][$i]["id"]?>" /></td>
			<td class="<?=$rowClass?>" onMouseOver="highlightRow('event<?=$e?>_<?=$tS?>_row<?=$rN?>',1)" onMouseOut="highlightRow('event<?=$e?>_<?=$tS?>_row<?=$rN?>',0)" onClick="checkBox('event<?=$e?>_<?=$tS?>_chk<?=$rN?>')"><?=stripslashes($propList[$tE][$tS][$i]['title'])?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('event<?=$e?>_<?=$tS?>_row<?=$rN?>',1)" onMouseOut="highlightRow('event<?=$e?>_<?=$tS?>_row<?=$rN?>',0)" onClick="checkBox('event<?=$e?>_<?=$tS?>_chk<?=$rN?>')"><?=$presStr?></td>
<?php
					if($tS == "scheduled") {
?>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('event<?=$e?>_<?=$tS?>_row<?=$rN?>',1)" onMouseOut="highlightRow('event<?=$e?>_<?=$tS?>_row<?=$rN?>',0)" onClick="checkBox('event<?=$e?>_<?=$tS?>_chk<?=$rN?>')"><?=$sesStr?></td>
<?php
					}
?>
		</tr>
<?php
					$rN++;
				}

				$rows = ob_get_contents();
				ob_end_clean();
				
				if($e % 2 == 0) $divBGColor = "#CCCCCC";
				else $divBGColor = "#FFFFFF";
?>
	<div id="event<?=$e?>_<?=$tS?>_header" style="padding-top: 25px; padding-bottom: 25px; background-color: <?=$divBGColor?>;">
		<table border="0" width="800" style="border-botom: solid 1px #AAAAAA" cellpadding="5">
			<tr>
				<td align="center" style="font-weight: bold"><?=$eTypes[$e]." (".ucwords($tS).")"?> &#151; <span id="event<?=$e?>_<?=$tS?>_selectedNum">0</span> selected</td>
			</tr>
<?php
				if($tS == "scheduled") {
?>
			<tr>
				<td>Scheduled proposals are proposals that are accepted and have been assigned to a session.</td>
			</tr>
<?php
				} else if($tS == "accepted") {
?>
			<tr>
				<td>Accepted proposals are proposals that are accepted, but have <b>NOT</b> been assigned to a session. These presentations are essentially on a "wait-list" and can be added to the program if a previously scheduled presenter cancels.</td>
			</tr>
<?php
				} else if($tS == "rejected") {
?>
			<tr>
				<td>Rejected proposals are proposals that are marked rejected.</td>
			</tr>
<?php
				}
?>
			<tr>
				<td><input type="checkbox" id="event<?=$e?>_<?=$tS?>_sendAll" onclick="selectProposal(this)" /> <span style="font-weight: bold; cursor: default" onclick="checkBox('event<?=$e?>_<?=$ts?>_sendAll')">Send email to all proposals in this group</span></td>
			</tr>
			<tr>
				<td style="text-align: center"><input type="button" value="View Individual Proposals" onclick="viewProposals('event<?=$e?>_<?=$tS?>_props', this)" style="border: solid 1px #000000; border-radius: 5px; background-color: #CCCCCC; color: #000000; font-size: 12pt; font-weight: bold"></td>
			</tr>
		</table>
	</div>
	<div id="event<?=$e?>_<?=$tS?>_props" class="propTableDiv" style="display: none">
		<table id="event<?=$e?>_<?=$tS?>_propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList" style="text-align: center"><input type="checkbox" id="event<?=$e?>_<?=$tS?>_all" onClick="selectProposal(this)" /></th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
<?php
				if($tS == "scheduled") {
?>
				<th class="pList">Schedule</th>
<?php
				}
?>
			</tr>
<?php			
				echo $rows;
?>
		</table><br /><br />
	</div>
<?php
			}
		} else {
			$tE = $eTypes[$e];
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($propList[$tE]); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
						
				//get the presenters names
				$tmpPres = $propList[$tE][$i]["presenters"];
				if(count($tmpPres) > 1) $presStr = "<ol>";
				else $presStr = "";
				for($tP = 0; $tP < count($tmpPres); $tP++) {
					if(count($tmpPres) > 1) $presStr .= "<li>".$tmpPres[$tP]["first_name"]." ".$tmpPres[$tP]["last_name"]."</li>";
					else $presStr .= $tmpPres[$tP]["first_name"]." ".$tmpPres[$tP]["last_name"];
				}

				if(count($tmpPres) > 1) $presStr .= "</ol>";			

				$sesStr = '';
				$tmpSes = explode("|",$propList[$tE][$i]["session"]);
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
	
				if(count($tmpSes) > 2) $sesStr .= "<br />".$tmpSes[2];
?>
		<tr id="event<?=$e?>_row<?=$rN?>">
			<td class="<?=$rowClass?>" width="50" valign="center" style="text-align: center" onMouseOver="highlightRow('event<?=$e?>_row<?=$rN?>',1)" onMouseOut="highlightRow('event<?=$e?>_row<?=$rN?>',0)"><input type="checkbox" name="event<?=$e?>_chk<?=$rN?>" id="event<?=$e?>_chk<?=$rN?>" onClick="selectProposal(this)" value="<?=$propList[$tE][$i]["id"]?>" /></td>
			<td class="<?=$rowClass?>" onMouseOver="highlightRow('event<?=$e?>_row<?=$rN?>',1)" onMouseOut="highlightRow('event<?=$e?>_row<?=$rN?>',0)" onClick="checkBox('event<?=$e?>_chk<?=$rN?>')"><?=stripslashes($propList[$tE][$i]['title'])?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('event<?=$e?>_row<?=$rN?>',1)" onMouseOut="highlightRow('event<?=$e?>_row<?=$rN?>',0)" onClick="checkBox('event<?=$e?>_chk<?=$rN?>')"><?=$presStr?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('event<?=$e?>_row<?=$rN?>',1)" onMouseOut="highlightRow('event<?=$e?>_row<?=$rN?>',0)" onClick="checkBox('event<?=$e?>>_chk<?=$rN?>')"><?=$sesStr?></td>
		</tr>
<?php
				$rN++;
			}

			$rows = ob_get_contents();
			ob_end_clean();
				
			if($e % 2 == 0) $divBGColor = "#CCCCCC";
			else $divBGColor = "#FFFFFF";
?>
	<div id="event<?=$e?>_header" style="padding-top: 25px; padding-bottom: 25px; background-color: <?=$divBGColor?>;">
		<table border="0" width="800" style="border-botom: solid 1px #AAAAAA" cellpadding="5">
			<tr>
				<td align="center" style="font-weight: bold"><?=$eTypes[$e]?> &#151; <span id="event<?=$e?>_selectedNum">0</span> selected</td>
			</tr>
			<tr>
				<td><input type="checkbox" id="event<?=$e?>_sendAll" onclick="selectProposal(this)" /> <span style="font-weight: bold; cursor: default" onclick="checkBox('event<?=$e?>_sendAll')">Send email to all proposals in this group</span></td>
			</tr>
			<tr>
				<td style="text-align: center"><input type="button" value="View Individual Proposals" onclick="viewProposals('event<?=$e?>_props', this)" style="border: solid 1px #000000; border-radius: 5px; background-color: #CCCCCC; color: #000000; font-size: 12pt; font-weight: bold"></td>
			</tr>
		</table>
	</div>
	<div id="event<?=$e?>_props" class="propTableDiv" style="display: none">
		<table id="event<?=$e?>_propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList" style="text-align: center"><input type="checkbox" id="event<?=$e?>_all" onClick="selectProposal(this)" /></th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
				<th class="pList">Schedule</th>
			</tr>
<?php			
			echo $rows;
?>
		</table><br /><br />
	</div>
<?php
		}
	}

	include "adminBottom.php";
?>