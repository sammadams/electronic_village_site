<?php
	// attendance.php -- allows a user to enter the attendance at a particular session (or at presentations in that session)
	
	include_once "login.php";
	
	$topTitle = "Attendance Records";
	
	$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');

	
	if(strpos($_SESSION['user_role'],"admin") === false) {
		include "adminTop.php";
?>
	<h2 align="center" style="color: red">Access Denied!</h2>
	<h3 align="center">You do not have permission to access this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(isset($_POST["sessionID"])) {
		//Check to see if this session is already there
		$eStmt = $db->prepare("SELECT * FROM `stats` WHERE `sessionID` = ? LIMIT 1");
		$eStmt->bind_param('s',$_POST["sessionID"]);
		$eStmt->execute();
		$eStmt->store_result();
		if($eStmt->num_rows > 0) { //already exists, so update
			$uStmt = $db->prepare("UPDATE `stats` SET `attendance` = ? WHERE `sessionID` = ? LIMIT 1");
			$uStmt->bind_param('ss',$_POST["attendNums"],$_POST["sessionID"]);
			$uStmt->execute();
			$uStmt->close();
		} else { //insert
			$eStmt->close();
			$iStmt = $db->prepare("INSERT INTO `stats` (`sessionID`,`attendance`) VALUES (?,?)");
			$iStmt->bind_param('ss',$_POST["sessionID"],$_POST["attendNums"]);
			$iStmt->execute();
			$iStmt->close();
		}
	}

	if(isset($_GET["s"]) && strip_tags($_GET["s"]) != "t") {
		$sesID = strip_tags($_GET["s"]);
		$qStmt = $db->prepare("SELECT * FROM `sessions` WHERE `id` = ? LIMIT 1");
		$qStmt->bind_param('s',$sesID);
	} else {
		$qStr = "SELECT * FROM `sessions` WHERE 1";
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
	$qStmt->bind_result($sID,$sLocation,$sDate,$sTime,$sEvent,$sTitle,$sPresentations);
	
	$sessions = array();
	while($qStmt->fetch()) {
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
	
	//get the attendance numbers
	$aStmt = $db->prepare("SELECT * FROM `stats` WHERE 1");
	$aStmt->execute();
	$aStmt->bind_result($asID,$asNums);
	$attendanceNumbers = array();
	while($aStmt->fetch()) {
		$tmpNms = explode('||',$asNums);
		$tmpNumbers = array();
		for($tn = 0; $tn < count($tmpNms); $tn++) {
			$tmpTN = explode("|",$tmpNms[$tn]);
			$tmpNumbers[] = array("propID" => $tmpTN[0],"attnNum" => $tmpTN[1]);
		}
		$attendanceNumbers[] = array("id" => $asID,"numbers" => $tmpNumbers);
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
		$totalAttend = 0;
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
			
			if($sessions[$i]["event"] != "Other") {
				for($k = 0; $k < count($proposals); $k++) {
					if($proposals[$k]["id"] == $pID) {
						$thisPres[$pCount]["id"] = $proposals[$k]["id"];
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
							$thisPres[$pCount]["id"] = $classics_proposals[$k]["id"];
							$thisPres[$pCount]["title"] = $classics_proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $classics_proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $classics_proposals[$k]["presenters"];
							break;
						}
					}				
				} else {
					for($k = 0; $k < count($other_proposals); $k++) {
						if($other_proposals[$k]["id"] == $pID) {
							$thisPres[$pCount]["id"] = $other_proposals[$k]["id"];
							$thisPres[$pCount]["title"] = $other_proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $other_proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $other_proposals[$k]["presenters"];
							break;
						}
					}				
				}
			}
			
			if(count($thisPres[$pCount]) > 0) {
				$thisPres[$pCount]["attendance"] = 0;
				for($an = 0; $an < count($attendanceNumbers); $an++) {
					if($attendanceNumbers[$an]["id"] == $sessions[$i]["id"]) {
						$pANArr = $attendanceNumbers[$an]["numbers"];
						for($pAN = 0; $pAN < count($pANArr); $pAN++) {
							if($pANArr[$pAN]["propID"] == $pID) {
								$thisPres[$pCount]["attendance"] = $pANArr[$pAN]["attnNum"];
								$totalAttend += $pANArr[$pAN]["attnNum"];
								break;
								break;
							}
						}
					}
				}
			}

			
			if(count($thisPres[$pCount]) == 0) array_splice($thisPres, $pCount, 1);
			else $pCount++;
		}
		
		$sessions[$i]["presentations"] = $thisPres;
		$sessions[$i]["total_attendance"] = $totalAttend;
	}
	
	if(isset($_GET["s"]) && strip_tags($_GET["s"]) == "t") {
		//First, get the session information into an array
		$events = array();
		
		for($e = 0; $e < count($sessions); $e++) {
			list($tmpYear,$tmpMonth,$tmpDay) = explode("-",$sessions[$e]["date"]);
			
			$tmpTime = explode("-",$sessions[$e]["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			$tmpSMinutes = $tmpStart[1];

			$startTime = mktime($tmpSHour,($tmpSMinutes - 10),0,$tmpMonth,$tmpDay,$tmpYear);

			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			$tmpEMinutes = $tmpEnd[1];

			$endTime = mktime($tmpEHour,$tmpEMinutes,0,$tmpMonth,$tmpDay,$tmpYear);
				
			
			$events[$e] = array(
				"room" => $sessions[$e]["location"],
				"title" => $sessions[$e]["title"],
				"start" => $startTime,
				"end" => $endTime,
				"attendance" => 0
			);
		}
		
		//Now, add events not in the program (e.g. CALL-IS Open Meeting)
		$eI = $e;
		$events[$eI] = array(
			"room" => "ts",
			"title" => "CALL-IS Open Meeting",
			"start" => mktime(16,50,0,3,26,2015),
			"end" => mktime(23,59,59,3,26,2015),
			"attendance" => 0
		);
		
		$eI++;
		$events[$eI] = array(
			"room" => "ev",
			"title" => "EV Planning Meeting",
			"start" => mktime(15,0,0,3,27,2015),
			"end" => mktime(23,59,59,3,27,2015),
			"attendance" => 0
		);
		
		$eI++;

		//get the total attendance for each session from the scanner data
		$data = file_get_contents("2015_EV_TS_Attendance.csv");
		//$s = 0;
		//$e = strpos($data,"ev,");
		//$evts = false;
		//$lines = array();
		//while($e !== false) {
		//	$lines[] = substr($data,$s,$e);
		//	$s = $e;
		//	if(strpos($data,"ev,",($s + 1)) === false) { //so look for ts
		//		$e = strpos($data,"ts,",($s + 1));
		//	} else $e = strpos($data,"ev,",($s + 1));
		//}
		
		$lines = explode("|n|",$data);
		$ucAttendance = 0; //scans not counted as part of a session
		for($i = 1; $i < count($lines); $i++) {
			$l = explode(",",trim($lines[$i]));
			list($m,$d,$y) = explode("/",$l[3]);
			//echo "Month: ".$m."<br>Day: ".$d."<br>Year: ".$y."<br><br>";
			list($h,$min,$s) = explode(":",$l[4]);
			$time = mktime($h,$min,$s,$m,$d,$y);
			//echo $time."<br />";
			
			//echo date("F j, Y",$time)." at ".date("h:i A",$time)."<br>";
			$room = $l[0];
			
			$isCounted = false;
			for($e = 0; $e < count($events); $e++) {
				if($room == $events[$e]['room']) {
					if($time > $events[$e]['start'] && $time < $events[$e]['end']) {
						$events[$e]['attendance']++;
						$isCounted = true;
						break;
					}
				}
			}
			
			if($isCounted == false) $ucAttendance++;
		}
		
		$topTitle = "Attendance Records (from Scanners)";
		
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
		</style>
		<table border="0" align="center" cellspacing="0" cellpadding="5">
			<tr>
				<th class="sList">Room</th>
				<th class="sList">Event</th>
				<th class="sList">Date</th>
				<th class="sList">Time</th>
				<th class="sList">Attendance</th>
			</tr>
<?php
	for($m = 0; $m < count($events); $m++) {
		if($m % 2 == 0) $rowClass = "sList_rowEven";
		else $rowClass = "sList_rowOdd";
?>
			<tr>
				<td class="<?=$rowClass?>" style="width: 150px"><? if($events[$m]['room'] == "ts") { ?>Technology Showcase<? } else { ?>Electronic Village<? } ?></td>
				<td class="<?=$rowClass?>"><?=$events[$m]['title']?></td>
				<td class="<?=$rowClass?>" style="width: 100px"><?=date("F j",$events[$m]["start"])?></td>
				<td class="<?=$rowClass?>" style="width: 110px"><?=date("g:i",$events[$m]["start"])." - ".date("g:i A",$events[$m]["end"])?></td>
				<td class="<?=$rowClass?>" style="text-align: center"><?=$events[$m]['attendance']?></td>
			</tr>
<?php
	}
?>
		</table>
<?php
		include "adminBottom.php";
		exit();
	} else if(isset($_GET["s"])) {
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
		
		if($sessions[$i]["location"] == "ev") $locationStr = "Convention Center Room 701-B: Electronic Village";
		else if($sessions[$i]["location"] == "ts") $locationStr = "Convention Center Room: 701-A: Technology Showcase";
		
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
		
		ol { 
			margin: 0;
			padding-left: 20px;
		}
		
		li { margin: 0; }

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
		}
		
		#saveMsg {
			font-weigth: bold;
			color: red;
			font-size: 16pt;
			text-align: center;
		}

		#propTableDiv {
			padding-bottom: 60px;
			overflow: auto;
		}
	</style>
	<script type="text/javascript">
		function editNum(el) {
			var tmp = el.id.split('_');
			var n = tmp[1];
			if(document.getElementById('inputNum_' + n)) { //save the number
				var nEl = document.getElementById('inputNum_' + n);
				var nNum = nEl.value;
				nEl = document.getElementById('num_' + n);
				nEl.innerHTML = nNum;
				nEl.onclick = function() {
					editNum(this);
				};
				
				checkChanges();
			} else { //show the input field
				var nEl = document.getElementById('num_' + n);
				var nNum = nEl.innerHTML;
				nEl.innerHTML = '<input type="text" id="inputNum_' + n + '" value="' + nNum + '" size="4" onkeyup="checkEnter(this)" />';
				nEl.onclick = function() {
					return false;
				};
				
				document.getElementById('inputNum_' + n).focus();
				document.getElementById('inputNum_' + n).select();
			}
		}
		
		function checkEnter(el) {
			if(event.keyCode == 13) {
				editNum(el);
			}
		}
	</script>
	<h3 style="text-align: center; font-family: Arial; margin: 0"><?=$sessions[$i]["title"]?></h2>
	<h3 style="text-align: center; font-family: Arial; margin: 0"><?=$timeStr?> (<?=$dateStr?>)</h3>
	<h3 style="text-align: center; font-family: Arial; margin: 0; margin-bottom: 10px"><?=$locationStr?></h3>
	<p>Click on a session to edit the number of attendees.</p>
	<div id="propTableDiv">
		<table border="0" align="center" cellpadding="5" width="100%" style="border-spacing: 0; border-collapse: collapse">
			<tr>
<?php
		if($sessions[$i]["event"] == "Technology Fairs" || $sessions[$i]["title"] == "Technology Fair: Classics") {
?>
				<th class="sList">Station</th>
<?php
		}
?>
				<th class="sList">Title</th>
				<th class="sList">Presenters</th>
				<th class="sList"># in Attendance</th>
			</tr>
<?php
		$pArr = $sessions[$i]["presentations"];
		$rN = 0;
		for($j = 0; $j < count($pArr); $j++) {
			if(count($pArr[$j]) > 0) {
				if($rN % 2 == 0) $rowClass = "sList_rowEven";
				else $rowClass = "sList_rowOdd";
				
				if(isset($pArr[$j]["presenters"])) {
					$prArr = $pArr[$j]["presenters"];
					$presStr = "";
					if(count($prArr) > 1) {
						$presStr .= '<ol>';
						for($k = 0; $k < count($prArr); $k++) {
							$presStr .= '<li>'.stripslashes($prArr[$k]["first_name"]).' '.stripslashes($prArr[$k]["last_name"]).'</li>'; 
						}
						$presStr .= '</ol>';
					} else $presStr = stripslashes($prArr[0]["first_name"]).' '.stripslashes($prArr[0]["last_name"]);
				}
?>
			<tr id="row_<?=$rN?>">
<?php
				if($sessions[$i]["event"] == "Technology Fairs" || $sessions[$i]["title"] == "Technology Fair: Classics") {
?>
				<td id="station_<?=$rN?>" valign="top" width="80" class="<?=$rowClass?>" onClick="editNum(this)"><?=$pArr[$j]["station"]?></td>
<?php
				}
?>
				<td id="title_<?=$rN?>" valign="top" class="<?=$rowClass?>" onClick="editNum(this)"><?=stripslashes($pArr[$j]["title"])?><input type="hidden" id="propID_<?=$rN?>" value="<?=$pArr[$j]['id']?>" /></td>
				<td id="pres_<?=$rN?>" valign="top" width="200" class="<?=$rowClass?>" onClick="editNum(this)"><?=$presStr?></td>
				<td id="num_<?=$rN?>" valign="top" width="110" class="<?=$rowClass?>" style="text-align: center" onClick="editNum(this)"><?=$pArr[$j]["attendance"]?></td>
			</tr>
<?php
				$rN++;
			}
		}
?>
		</table>
	</div>
	<div id="footer">
		<p id="saveMsg" align="center"><? if(isset($_POST['sessionID'])) { ?><span style="color: green">CHANGES SAVED!</span> <input type="button" value="Back to Session List" onClick="window.location.href='attendance.php'" /><? } else { ?>&nbsp;<? } ?></p>
	</div>
	<form name="attendanceForm" id="attendanceForm" method="post" action="">
		<input type="hidden" name="sessionID" id="sessionID" value="<?=$sessions[$i]['id']?>" />
		<input type="hidden" name="attendNums" id="attendNums" value="" />
	</form>
	<script type="text/javascript">
		var attend = new Array();
<?php
		$rN = 0;
		for($j = 0; $j < count($pArr); $j++) {
			if(count($pArr[$j]) > 0) {
?>
		attend[<?=$rN?>] = <?=$pArr[$j]['attendance']?>;
<?php
				$rN++;
			}
		}
?>

		function checkChanges() {
			var sEl = document.getElementById('saveMsg');
			sEl.innerHTML = '';
			for(var i = 0; i < attend.length; i++) {
				var tN = parseInt(document.getElementById('num_' + i).innerHTML);
				var aN = parseInt(attend[i]);
				if(tN != aN) {
					sEl.innerHTML = '<span style="color: red">CHANGES NOT SAVED!</span> <input type="button" value="Save Changes" onClick="saveChanges()" />';
					break; //no need to go any further
				}
			}
		}
		
		function saveChanges() {
			//get the numbers from the table
			var attendStr = '';
			for(var i = 0; i < attend.length; i++) {
				var nID = document.getElementById('propID_' + i).value;
				var nNum = document.getElementById('num_' + i).innerHTML;
				
				attendStr += nID + '|' + nNum;
				if(i < (attend.length - 1)) attendStr += '||';
			}
			
			document.getElementById('attendNums').value = attendStr;
			
			document.getElementById('attendanceForm').submit();
		}
	</script>
<?php
		include "adminBottom.php";
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
			var url = 'attendance.php?s=' + n;
			window.location.href = url;
		}
	</script>
		<table border="0" align="center" cellpadding="5" width="100%">
			<tr>
				<td width="50%" align="center">Technology Showcase: <span id="tsTotalNum">0</span></td>
				<td width="50%" align="center">Electronic Village: <span id="evTotalNum">0</span></td>
			</tr>
		</table>
		<p align="left">Click on a session to see or edit attendance numbers for that particular session.</p>
<?php
	$eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Other"); //get all event types -- Classroom of the future is not held in the EV, so it's not on our schedule

	$tsTotal = 0;
	$evTotal = 0;
	for($e = 0; $e < count($eTypes); $e++) {
		ob_start();
		$rN = 0;
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] == $eTypes[$e]) { //list this session
				if($sessions[$i]["location"] == "ev") $evTotal += intval($sessions[$i]["total_attendance"]);
				else if($sessions[$i]["location"] == "ts") $tsTotal += intval($sessions[$i]["total_attendance"]);
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
			<td class="<?=$rowClass?>" width="300" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$sessions[$i]['title']?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$dateStr?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$timeStr?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="100" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$pCount?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="100" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doPrint('<?=$sessions[$i]['id']?>')"><?=$sessions[$i]['total_attendance']?></td>
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
			<th class="sList" style="text-align: center">Total Attendance</td>
		</tr>
<?php			
		echo $rows;
	}
?>
	</table><br /><br />
	<script type="text/javascript">
		window.addEventListener('load',function() {
			var tsTotal = <?=$tsTotal?>;
			var evTotal = <?=$evTotal?>;
			document.getElementById('evTotalNum').innerHTML = evTotal;
			document.getElementById('tsTotalNum').innerHTML = tsTotal;
		});
	</script>
<?php
	include "adminBottom.php";
?>