<?php
	// program.php -- prints out the schedule in the format needed for the printed EV Program Book
	
	include_once "login.php";
	
	$topTitle = "Print Signs for Bulletin Boards";
	
	$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');

	// TODO: update confdates to pull from 'configs' table 
	$confDate1 = "2019-03-13";
	$confDate2 = "2019-03-14";
	$confDate3 = "2019-03-15";
	
	$evLocationStr = "Exhibition Hall - Booth 917";
	$tsLocationStr = "Exhibition Hall - Booth 1111";
	
	$cfnSummary = "Learn CALL basics from experts and enhance your teaching with digital resources. This event includes hands-on guided practice in the Electronic Village on a variety of introductory CALL techniques and tools.";

	if(strpos($_SESSION['user_role'],"admin") === false) {
		include "adminTop.php";
?>
	<h2 align="center" style="color: red">Access Denied!</h2>
	<h3 align="center">You do not have permission to access this page!</h3>
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
		else if($t == 5) $eType = "Technology Fairs Classics";
		else if($t == 6) $eType = "Other";
		else if($t == 7) $eType = "Hot Topics";
		else if($t == 8) $eType = "Graduate Student Research";
	}
	
	if(isset($_GET["d"])) {
		$d = strip_tags($_GET["d"]);
		if($d == 1) $eDate = $confDate1;
		else if($d == 2) $eDate = $confDate2;
		else if($d == 3) $eDate = $confDate3;
	}
	
	if(isset($_GET["s"])) {
		$sesID = strip_tags($_GET["s"]);
		$qStmt = $db->prepare("SELECT sessions.*, events.event FROM `sessions` LEFT JOIN `events` ON sessions.event = events.id WHERE sessions.id = ? LIMIT 1");
		$qStmt->bind_param('s',$sesID);
	} else {
		$qStr = "SELECT sessions.*, events.event FROM `sessions` LEFT JOIN `events` ON sessions.event = events.id WHERE 1";
		if(isset($_GET["o"])) {
			$qStr .= " ORDER BY ";
			$order = strip_tags($_GET["o"]);
			if($order == "1") $qStr .= "`date`";
			else if($order == "2") $qStr .= "`time`";
			else if($order == "3") $qStr .= "`event`";
		} else $qStr .= " ORDER BY `date` ASC, `time` ASC, `location` DESC";
	
		$qStmt = $db->prepare($qStr);
	}
	
	$qStmt->execute();
	$qStmt->bind_result($sID,$sLocation,$sDate,$sTime,$sEventID,$sTitle,$sPresentations,$sEvent);
	
	$sessions = array();
	while($qStmt->fetch()) {
		if(isset($eDate)) {
			if($eDate != $sDate) continue;
		}
		
		if(isset($eType)) {
			if($eType != "Technology Fairs (Classics)") {
				if($sEvent == $eType) {
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
			} else if($eType == "Technology Fairs (Classics)") {
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
		} else {
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
	
	//get the proposal information
	$pStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `proposals` WHERE 1");
	$pStmt->execute();
	$pStmt->bind_result($pID,$pTitle,$pPresenters,$pSummary);
	$proposals = array();
	while($pStmt->fetch()) {
		$proposals[] = array(
			"id" => $pID,
			"title" => $pTitle,
			"presenters" => $pPresenters,
			"summary" => $pSummary
		);
	}
	
	//get the presenters information
	$prStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email` FROM `presenters` WHERE 1");
	$prStmt->execute();
	$prStmt->bind_result($prID,$prFN,$prLN,$prEmail,$prAN,$prAC,$prPE);
	$presenters = array();
	while($prStmt->fetch()) {
		if($prAC == "United States") $prAC = "USA";
		else if($prAC == "Korea, South") $prAC = "South Korea";
		else if($prAC == "Russian Federation") $prAC = "Russia";
		else if($prAC == "United Arab Emirates") $prAC = "UAE";
		else if($prAC == "United Kingdom") $prAC = "UK";
		else if($prAC == "United States Minor Outlying Islands") $prAC = "USA";
		$presenters[] = array(
			"id" => $prID,
			"first_name" => $prFN,
			"last_name" => $prLN,
			"email" => $prEmail,
			"affiliation" => $prAN,
			"country" => $prAC,
			"emailOK" => $prPE
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
	
	//now, update the proposals array with the presenters information
	for($i = 0; $i < count($proposals); $i++) {
		$tmp = explode("|",$proposals[$i]["presenters"]);
		$thisPres = array();
		for($j = 0; $j < count($tmp); $j++) {
			for($k = 0; $k < count($presenters); $k++) {
				if($tmp[$j] == $presenters[$k]["id"]) {
					$thisPres[] = $presenters[$k];
					break;
				}
			}
		}
		
		$proposals[$i]["presenters"] = $thisPres;
	}
	
	//now, do the same with the "other" presentations and presenters
	//get the proposal information
	$opStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `other_proposals` WHERE 1");
	$opStmt->execute();
	$opStmt->bind_result($opID,$opTitle,$opPresenters,$opSummary);
	$other_proposals = array();
	while($opStmt->fetch()) {
		$other_proposals[] = array(
			"id" => $opID,
			"title" => $opTitle,
			"presenters" => $opPresenters,
			"summary" => $opSummary
		);
	}
	
	//get the presenters information
	$oprStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email` FROM `other_presenters` WHERE 1");
	$oprStmt->execute();
	$oprStmt->bind_result($oprID,$oprFN,$oprLN,$oprEmail,$oprAN,$oprAC,$oprPE);
	$other_presenters = array();
	while($oprStmt->fetch()) {
		if($oprAC == "United States") $oprAC = "USA";
		else if($oprAC == "Korea, South") $oprAC = "South Korea";
		else if($oprAC == "Russian Federation") $oprAC = "Russia";
		else if($oprAC == "United Arab Emirates") $oprAC = "UAE";
		else if($oprAC == "United Kingdom") $oprAC = "UK";
		else if($oprAC == "United States Minor Outlying Islands") $oprAC = "USA";
		$other_presenters[] = array(
			"id" => $oprID,
			"first_name" => $oprFN,
			"last_name" => $oprLN,
			"email" => $oprEmail,
			"affiliation" => $oprAN,
			"country" => $oprAC,
			"emailOK" => $oprPE
		);
	}	

	//now, update the proposals array with the presenters information
	for($i = 0; $i < count($other_proposals); $i++) {
		$tmp = explode("|",$other_proposals[$i]["presenters"]);
		$thisPres = array();
		for($j = 0; $j < count($tmp); $j++) {
			for($k = 0; $k < count($other_presenters); $k++) {
				if($tmp[$j] == $other_presenters[$k]["id"]) {
					$thisPres[] = $other_presenters[$k];
					break;
				}
			}
		}
		
		$other_proposals[$i]["presenters"] = $thisPres;
	}
	
	//now, do the same with the "classics" presentations and presenters
	//get the proposal information
	$cpStmt = $db->prepare("SELECT `id`,`title`,`presenters`,`summary` FROM `classics_proposals` WHERE 1");
	$cpStmt->execute();
	$cpStmt->bind_result($cpID,$cpTitle,$cpPresenters,$cpSummary);
	$classics_proposals = array();
	while($cpStmt->fetch()) {
		$classics_proposals[] = array(
			"id" => $cpID,
			"title" => $cpTitle,
			"presenters" => $cpPresenters,
			"summary" => $cpSummary
		);
	}
	
	//get the presenters information
	$cprStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email` FROM `classics_presenters` WHERE 1");
	$cprStmt->execute();
	$cprStmt->bind_result($cprID,$cprFN,$cprLN,$cprEmail,$cprAN,$cprAC,$cprPE);
	$classics_presenters = array();
	while($cprStmt->fetch()) {
		if($cprAC == "United States") $cprAC = "USA";
		else if($cprAC == "Korea, South") $cprAC = "South Korea";
		else if($cprAC == "Russian Federation") $cprAC = "Russia";
		else if($cprAC == "United Arab Emirates") $cprAC = "UAE";
		else if($cprAC == "United Kingdom") $cprAC = "UK";
		else if($cprAC == "United States Minor Outlying Islands") $cprAC = "USA";
		$classics_presenters[] = array(
			"id" => $cprID,
			"first_name" => $cprFN,
			"last_name" => $cprLN,
			"email" => $cprEmail,
			"affiliation" => $cprAN,
			"country" => $cprAC,
			"emailOK" => $cprPE
		);
	}	

	//now, update the proposals array with the presenters information
	for($i = 0; $i < count($classics_proposals); $i++) {
		$tmp = explode("|",$classics_proposals[$i]["presenters"]);
		$thisPres = array();
		for($j = 0; $j < count($tmp); $j++) {
			for($k = 0; $k < count($classics_presenters); $k++) {
				if($tmp[$j] == $classics_presenters[$k]["id"]) {
					$thisPres[] = $classics_presenters[$k];
					break;
				}
			}
		}
		
		$classics_proposals[$i]["presenters"] = $thisPres;
	}
	
	//now, update the sessions array with the proposals information
	for($i = 0; $i < count($sessions); $i++) {
		$tmp = explode("||",$sessions[$i]["presentations"]);
		$thisPres = array();
		$pCount = 0;
		for($j = 0; $j < count($tmp); $j++) {
			$tmpP = explode("|",$tmp[$j]);
			$thisPres[$pCount] = array();
			if(count($tmpP) > 1) { //includes a station name
				if($tmpP[0] != "0" && $tmpP[1] != "0") { //there is a presentation scheduled for this station
					for($k = 0; $k < count($stations); $k++) {
						if($stations[$k]["id"] == $tmpP[0]) {
							$thisPres[$pCount]["station"] = $stations[$k]["name"];
							break;
						}
					}
				}
				
				$pID = $tmpP[1];
			} else $pID = $tmpP[0];
			
			if($sessions[$i]["event"] != "Other" && $sessions[$i]["event"] != "Technology Fairs Classics") {
				for($k = 0; $k < count($proposals); $k++) {
					if($proposals[$k]["id"] == $pID) {
						$thisPres[$pCount]["title"] = $proposals[$k]["title"];
						$thisPres[$pCount]["summary"] = $proposals[$k]["summary"];
						$thisPres[$pCount]["presenters"] = $proposals[$k]["presenters"];
						break;
					}
				}
			} else {
				if($sessions[$i]["event"] == "Technology Fairs Classics") {
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
		
		if($sessions[$i]["title"] == "CALL for Newcomers") {
			$sessions[$i]["presentations"] = array(array('title' => '', 'summary' => $cfnSummary, 'presenters' => array()));
		}
	}
	
	if(isset($_GET["s"])) {
		$i = 0;

		$curDate = $sessions[$i]["date"];
		$tmpDate = explode("-",$curDate);
		$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];

		$tmpTime = explode("-",$sessions[$i]["time"]);
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
		
		if($sessions[$i]["location"] == "ev") $locationStr = $evLocationStr;
		else if($sessions[$i]["location"] == "ts") $locationStr = $tsLocationStr;
?>
	<h2 style="text-align: center; font-family: Arial; margin: 0"><?=$sessions[$i]["title"]?></h2>
	<h3 style="text-align: center; font-family: Arial; margin: 0"><?=$timeStr?> (<?=$dateStr?>)</h3>
	<h3 style="text-align: center; font-family: Arial; margin: 0; margin-bottom: 10px"><?=$locationStr?></h3>
	<table border="0" align="center" width="100%" style="border-spacing: 0; border-collapse: collapse">
<?php
		$pArr = $sessions[$i]["presentations"];
		for($j = 0; $j < count($pArr); $j++) {
			if(count($pArr[$j]) > 0) {
				if(isset($pArr[$j]["presenters"])) {
					$prArr = $pArr[$j]["presenters"];
					$presStr = "";
					for($k = 0; $k < count($prArr); $k++) {
						$presStr .= '&nbsp; &nbsp; <span style="font-weight: bold">'.stripslashes($prArr[$k]["first_name"]).' '.stripslashes($prArr[$k]["last_name"]).',</span> '.stripslashes(trim($prArr[$k]["affiliation"])).', '.$prArr[$k]["country"];
						if($prArr[$k]["emailOK"] == "1") $presStr .= ' ('.$prArr[$k]["email"].')';
						if($k < (count($prArr) - 1)) $presStr .= '<br />';
					}
				}

				if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs") {
					$stationCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; padding-left: 4px; padding-top: 2px; padding-bottom: 2px; border-top: solid 1px #CCCCCC";
					$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; border-top: solid 1px #CCCCCC";
					$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
?>
		<tr>
			<td rowspan="2" width="80" valign="top" style="<?=$stationCSS?>"><?=$pArr[$j]["station"]?></td>
			<td style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
		</tr>
<?php
					if(isset($presStr)) {
?>
		<tr>
			<td style="<?=$prPresCSS?>"><?=$presStr?></td>
		</tr>
<?php
					}
				} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs Classics") {
					$stationCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; padding-left: 4px; padding-top: 2px; padding-bottom: 2px; border-top: solid 1px #CCCCCC";
					$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; border-top: solid 1px #CCCCCC";
					$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
				
					if($j < (count($pArr) - 1)) {
						$stationCSS .= "; border-bottom: solid 1px #CCCCCC";
						if(isset($presStr)) $prPresCSS .= "; border-bottom: solid 1px #CCCCCC";
						else $prSummaryCSS .= "; border-bottom: solid 1px #CCCCCC";
					}
?>
		<tr>
			<td rowspan="2" width="80" valign="top" style="<?=$stationCSS?>"><?=$pArr[$j]["station"]?></td>
			<td style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
		</tr>
<?php
					if(isset($presStr)) {
?>
		<tr>
			<td style="<?=$prPresCSS?>"><?=$presStr?></td>
		</tr>
<?php
					}
				} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] != "Technology Fairs") {
					$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; border-top: solid 1px #CCCCCC";
					$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
?>
		<tr>
			<td colspan="2" style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
		</tr>
		<tr>
			<td colsapn="2" style="<?=$prSummaryCSS?>"><?=stripslashes($pArr[$j]["summary"])?></td>
		</tr>
<?php
					if(isset($presStr)) {
?>
		<tr>
			<td colspan="2" style="<?=$prPresCSS?>"><?=$presStr?></td>
		</tr>
<?php
					}				
				}
			}
		}
?>
	</table>
<?php
		exit();
	}
	
	include "adminTop.php";
?>
	<style type="text/css">
		th.sList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
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
	</style>
	<script type="text/javascript">
		function highlightRow(e,r,n) {
			var rEl = document.getElementById('session' + e + '_row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) cEl.className = 'sList_highlighted';
				else if(n == 0) {
					if(parseInt(r) % 2 == 0) cEl.className = 'sList_rowEven';
					else cEl.className = 'sList_rowOdd';
				}
			}
		}
		
		function doPrint(n) {
			var url = 'print_signs.php?s=' + n;
			window.location.href = url;
		}
	</script>
		<p align="left">Click on a session to print the schedule for that particular session.</p>
<?php
	$eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Hot Topics","Graduate Student Research","Technology Fairs Classics","Other"); //get all event types -- Classroom of the future is not held in the EV, so it's not on our schedule

	for($e = 0; $e < count($eTypes); $e++) {
		ob_start();
		$rN = 0;
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] == $eTypes[$e]) { //list this session
				if($rN % 2 == 0) $rowClass = 'sList_rowEven';
				else $rowClass = 'sList_rowOdd';
			
				//get the number of presentations scheduled for this session
				$pCount = count($sessions[$i]["presentations"]);
				
				$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
				$tmpDate = explode("-",$sessions[$i]["date"]);
				$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
				
				$tmpTime = explode("-",$sessions[$i]["time"]);
				$tmpStart = explode(":",$tmpTime[0]);
				$tmpSHour = intval($tmpStart[0]);
				if($tmpSHour < 12) $sAMPM = "AM";
				else {
					$sAMPM = "PM";
					if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
				}
				$tmpSMinutes = $tmpStart[1];
					
				$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
				
				$tmpEnd = explode(":",$tmpTime[1]);
				$tmpEHour = intval($tmpEnd[0]);
				if($tmpEHour < 12) $eAMPM = "AM";
				else {
					$eAMPM = "PM";
					if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
				}
				$tmpEMinutes = $tmpEnd[1];
				
				$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
		<tr id="session<?=$e?>_row<?=$rN?>">
			<td class="<?=$rowClass?>" width="400" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$sessions[$i]['title']?></td>
			<td class="<?=$rowClass?>" width="100" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$dateStr?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$timeStr?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$pCount?></td>
		</tr>
<?php
				$rN++;
			}
		}
	
		$rows = ob_get_contents();
		ob_end_clean();
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="3"><?=$eTypes[$e]?> (Total #: <?=$rN?>)</td>
		</tr>
		<tr>
			<th class="sList">Title</td>
			<th class="sList">Date</td>
			<th class="sList">Time</td>
			<th class="sList" style="text-align: center"># of Presentations</td>
		</tr>
<?php			
		echo $rows;
?>
	</table><p>&nbsp;</p>

<!--
<span style="text-align: left">
<pre>
	<?php print_r($sessions); ?>
</pre>
</span>
-->

<?php
	}

	include "adminBottom.php";
?>