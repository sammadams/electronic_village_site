<?php
	//exportData.php -- allows a user to export the proposal information for a specific event
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
		if(isset($_GET["y"]) && in_array($_GET["y"], $prevYears)) {
			$dbLink = ${ 'db_'.$_GET["y"] };
			$propTypes = getTypes($dbLink);
		} else {
			$dbLink = $db;
			$propTypes = getTypes($db);
		}
	
		$eType = $propTypes[$_GET["t"]];
		
		//Get all the proposals for this event
		$propStmt = $dbLink->prepare("SELECT * FROM `proposals` WHERE `type` = ?");
		$propStmt->bind_param('s',$eType);
		$propStmt->execute();
		$propStmt->bind_result($tmpID,$tmpTitle,$tmpContact,$tmpPresenters,$tmpTimes,$tmpTopics,$tmpComputer,$tmpSummary,$tmpAbstract,$tmpPassword,$tmpSalt,$tmpComments,$photoOK,$tmpEmailOK,$tmpType,$tmpStatus,$tmpCertificate);
		
		$props = array();
		while($propStmt->fetch()) {
			$props[] = array(
				"id" => $tmpID,
				"title" => $tmpTitle,
				"contact" => $tmpContact,
				"presenters" => $tmpPresenters,
				"times" => $tmpTimes,
				"topics" => $tmpTopics,
				"computer" => $tmpComputer,
				"summary" => $tmpSummary,
				"abstract" => $tmpAbstract,
				"comments" => $tmpComments,
				"status" => $tmpStatus,
				"certificate" => $tmpCertificate
			);
		}
		
		//Now, get the presenters information
		$presStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`,`Email` FROM `presenters` WHERE 1");
		$presStmt->execute();
		$presStmt->bind_result($tmpPresID, $tmpPresFirstName, $tmpPresLastName,$tmpPresEmail);
		
		$presenters = array();
		while($presStmt->fetch()) {
			$presenters[] = array(
				"id" => $tmpPresID,
				"first_name" => $tmpPresFirstName,
				"last_name" => $tmpPresLastName,
				"email" => $tmpPresEmail
			);
		}
		
		$presCount = 0;
		//Now, find the names of the presenters and put them into the props array
		for($i = 0; $i < count($props); $i++) {
			$tmp = explode("|",$props[$i]["presenters"]);
			$thisPres = array();
			$thisPresCount = 0;
			for($p = 0; $p < count($tmp); $p++) {
				for($j = 0; $j < count($presenters); $j++) {
					if($presenters[$j]["id"] == $tmp[$p]) {
						$thisPres[] = $presenters[$j]["first_name"]." ".$presenters[$j]["last_name"]." (".$presenters[$j]["email"].")";
						$thisPresCount++;
						break;
					}
				}
			}
			
			if($thisPresCount > $presCount) $presCount = $thisPresCount;
			
			$props[$i]["presenters"] = $thisPres;
		}
		
		//Now, get the reviews (if any)
		$revStmt = $db->prepare("SELECT * FROM `reviews` WHERE 1");
		$revStmt->execute();
		$revStmt->bind_result($revID,$revProp,$revReviewer,$revEvent,$revReview,$revComments);
		
		$reviews = array();
		while($revStmt->fetch()) {
			$reviews[] = array(
				"prop_id" => $revProp,
				"reviewer" => $revReviewer,
				"review" => $revReview,
				"comments" => $revComments
			);
		}
		
		$rStmt = $db->prepare("SELECT `username`,`first_name`,`last_name` FROM `users` WHERE 1");
		$rStmt->execute();
		$rStmt->bind_result($rUN,$rFN,$rLN);
		
		$reviewers = array();
		while($rStmt->fetch()) {
			$reviewers[] = array(
				"username" => $rUN,
				"first_name" => $rFN,
				"last_name" => $rLN
			);
		}
		
		//Update the reviews array with the reviewer name
		for($i = 0; $i < count($reviews); $i++) {
			for($j = 0; $j < count($reviewers); $j++) {
				if($reviewers[$j]["username"] == $reviews[$i]["reviewer"]) {
					$reviews[$i]["reviewer"] = $reviewers[$j]["first_name"]." ".$reviewers[$j]["last_name"];
					break;
				}
			}
		}
		
		//Now, update the $props array with the reviews
		$rCount = 0;
		for($i = 0; $i < count($props); $i++) {
			$thisReviews = array();
			$thisRCount = 0;
			for($j = 0; $j < count($reviews); $j++) {
				if($reviews[$j]["prop_id"] == $props[$i]["id"]) {
					$thisReviews[] = $reviews[$j];
					$thisRCount++;
				}
			}
			
			if($thisRCount > $rCount) $rCount = $thisRCount;
			$props[$i]["reviews"] = $thisReviews;
		}
		
		//create the CSV String
		$csvStr = array("ID","Title","Contact");
		for($p = 0; $p < $presCount; $p++) {
			$csvStr[] = "Presenter ".($p + 1);
		}
		
		$csvStr[] = "Times";
		$csvStr[] = "Topics";
		$csvStr[] = "Computer Preference";
		$csvStr[] = "Summary";
		$csvStr[] = "Abstract";
		$csvStr[] = "Comments";
		$csvStr[] = "Status";
		
		for($r = 0; $r < $rCount; $r++) {
			if($_GET["t"] == "1" || $_GET["t"] == "3" || $_GET["t"] == "4") {
				$csvStr[] = "Review ".($r + 1);
				$csvStr[] = "Reviewer Comments ".($r + 1);
				$csvStr[] = "Reviewer ".($r + 1);
			} else if($_GET["t"] == "2") {
				$csvStr[] = "Style & Content Score ".($r + 1);
				$csvStr[] = "Novelty Score ".($r + 1);
				$csvStr[] = "Practicality Score ".($r + 1);
				$csvStr[] = "Feasibility Score ".($r + 1);
				$csvStr[] = "Pedagogical Soundness Score ".($r + 1);
				$csvStr[] = "Total Score ".($r + 1);
				$csvStr[] = "Reviewer Comments ".($r + 1);
				$csvStr[] = "Reviewer ".($r + 1);
			} else if($_GET["t"] == "5") {
				$csvStr[] = "Innovation Score ".($r + 1);
				$csvStr[] = "Style Score ".($r + 1);
				$csvStr[] = "Context Score ".($r + 1);
				$csvStr[] = "Total Score ".($r + 1);
				$csvStr[] = "Reviewr Comments ".($r + 1);
				$csvStr[] = "Reviewer ".($r + 1);
			}
		}

		if($eType == "Technology Fairs") $filename = "ev_fairs.txt";
		else if($eType == "Mini-Workshops") $filename = "ev_mini.txt";
		else if($eType == "Developers Showcase") $filename = "ev_ds.txt";
		else if($eType == "Mobile Apps for Education Showcase") $filename = "ev_mae.txt";
		else if($eType == "Graduate Student Research") $filename = "ev_grad.txt";
		else if($eType == "Hot Topics") $filename = "ev_ht.txt";
		else if($eType == "Classroom of the Future") $filename = "ev_cotf.txt";

		header("Content-Type: text/plain");
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		
		$fp = fopen('php://output','w');
		fputcsv($fp,$csvStr,"\t");
		
		for($i = 0; $i < count($props); $i++) {
			$thisStr = array();
			$thisStr[] = $props[$i]["id"];
			$thisStr[] = $props[$i]["title"];
			$thisStr[] = $props[$i]["contact"];
			for($p = 0; $p < $presCount; $p++) {
				if(isset($props[$i]["presenters"][$p])) $thisStr[] = $props[$i]["presenters"][$p];
				else $thisStr[] = "";
			}
			$thisStr[] = $props[$i]["times"];
			$thisStr[] = $props[$i]["topics"];
			$thisStr[] = $props[$i]["computer"];
			$thisStr[] = str_replace("\r\n"," ",$props[$i]["summary"]);
			$thisStr[] = str_replace("\r\n"," ",$props[$i]["abstract"]);
			$thisStr[] = str_replace("\r\n"," ",$props[$i]["comments"]);
			$thisStr[] = $props[$i]["status"];
			for($r = 0; $r < $rCount; $r++) {
				if($_GET["t"] == "1" || $_GET["t"] == "3" || $_GET["t"] == "4") {
					if(isset($props[$i]["reviews"][$r]["review"])) $thisStr[] = $props[$i]["reviews"][$r]["review"];
					else $thisStr[] = "";
				
					if(isset($props[$i]["reviews"][$r]["comments"])) $thisStr[] = $props[$i]["reviews"][$r]["comments"];
					else $thisStr[] = "";
				
					if(isset($props[$i]["reviews"][$r]["reviewer"])) $thisStr[] = $props[$i]["reviews"][$r]["reviewer"];
					else $thisStr[] = "";
				} else if($_GET["t"] == "2") {
					if(isset($props[$i]["reviews"][$r]["review"])) {
						$tmpS = explode("|",$props[$i]["reviews"][$r]["review"]);
						$thisStr[] = $tmpS[0];
						$thisStr[] = $tmpS[1];
						$thisStr[] = $tmpS[2];
						$thisStr[] = $tmpS[3];
						$thisStr[] = $tmpS[4];
						$thisStr[] = $tmpS[5];
					} else {
						$thisStr[] = "";
						$thisStr[] = "";
						$thisStr[] = "";
						$thisStr[] = "";
						$thisStr[] = "";
						$thisStr[] = "";					
					}
					
					if(isset($props[$i]["reviews"][$r]["comments"])) $thisStr[] = $props[$i]["reviews"][$r]["comments"];
					else $thisStr[] = "";
					
					if(isset($props[$i]["reviews"][$r]["reviewer"])) $thisStr[] = $props[$i]["reviews"][$r]["reviewer"];
					else $thisStr[] = "";
				} else if($_GET["t"] == "5") {
					if(isset($props[$i]["reviews"][$r]["review"])) {
						$tmpS = explode("|",$props[$i]["reviews"][$r]["review"]);
						$thisStr[] = $tmpS[0];
						$thisStr[] = $tmpS[1];
						$thisStr[] = $tmpS[2];
						$thisStr[] = $tmpS[3];
					} else {
						$thisStr[] = "";
						$thisStr[] = "";
						$thisStr[] = "";
						$thisStr[] = "";						
					}
					
					if(isset($props[$i]["reviews"][$r]["comments"])) $thisStr[] = $props[$i]["reviews"][$r]["comments"];
					else $thisStr[] = "";
					
					if(isset($props[$i]["reviews"][$r]["reviewer"])) $thisStr[] = $props[$i]["reviews"][$r]["reviewer"];
					else $thisStr[] = "";
				}
			}
			fputcsv($fp,$thisStr,"\t");
		}
		
		fclose($fp);
		exit();
	}
		
	$topTitle = "Export Proposal Data to a CSV File";
	include "adminTop.php";
?>
	<h3 align="center">Please choose an event</h3>
<?php
	$types = getTypes($db);
	
	foreach($types AS $tI => $tType) {
?>
	<p align="center"><a href="exportData.php?t=<?php echo $tI; ?>"><?php echo $tType; ?></a></p>
<?php
	}
?>
	<h1 align="center">Previous Years</h1>
	<h3 align="center">2017</h3>
<?php
	$t2017 = getTypes($db_2017);
	
	foreach($t2017 AS $tI => $tType) {
?>
	<p align="center"><a href="exportData.php?t=<?php echo $tI; ?>&y=2017"><?php echo $tType; ?></a></p>
<?php	
	}
?>
	<h3 align="center">2016</h3>
<?php
	$t2016 = getTypes($db_2016);
	
	foreach($t2016 AS $tI => $tType) {
?>
	<p align="center"><a href="exportData.php?t=<?php echo $tI; ?>&y=2016"><?php echo $tType; ?></a></p>
<?php	
	}
?>
	<h3 align="center">2015</h3>
<?php
	$t2015 = getTypes($db_2015);
	
	foreach($t2015 AS $tI => $tType) {
?>
	<p align="center"><a href="exportData.php?t=<?php echo $tI; ?>&y=2015"><?php echo $tType; ?></a></p>
<?php	
	}

	include "adminBottom.php";
	
	function getTypes($dbLink) {
		$tStmt = $dbLink->prepare("SELECT DISTINCT(type) FROM proposals WHERE 1 ORDER BY type ASC");
		$tStmt->execute();
		$tStmt->bind_result($t);
		
		$types = array();
		while($tStmt->fetch()) {
			$types[] = $t;
		}
		
		return $types;
	}
?>