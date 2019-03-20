<?php
	// print_certificates.php -- creates PDF copies of the certificates and emails them to the participants

	include_once "login.php";

	$topTitle = "Print Certificates";

	$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
	$confDate1 = "2018-03-28";
	$confDate2 = "2018-03-29";
	$confDate3 = "2018-03-30";
	
	$evLocationStr = "Exhibition Hall - Booth 491";
	$tsLocationStr = "Exhibition Hall - Booth 540";

	if(strpos($_SESSION['user_role'],"admin") === false) {
		include "adminTop.php";
?>
	<h2 align="center" style="color: red">Access Denied!</h2>
	<h3 align="center">You do not have permission to access this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}

	if(isset($_POST["certData"])) {
		//First, get the data
		$data = explode("||",trim(strip_tags($_POST["certData"]),"||"));
		$event = strip_tags($_POST["certEvent"]);
		$sesTitle = strip_tags($_POST["sessionTitle"]);
		if($event != "Volunteer") $sessionID = preg_replace("/\D/","",$_POST["certSession"]);

		require("../fpdf/fpdf.php");
		$pdf = new FPDF('L','pt',array(792,612));

		for($i = 0; $i < count($data); $i++) {
			if($event != "Volunteer") list($email,$name,$title,$date,$start,$end) = explode("|",$data[$i]);
			else list($name,$email) = explode("|",$data[$i]);
			//$email = "justin@jshewell.com";
			
			$name = stripslashes($name);
			$title = stripslashes($title);
			
			if($event == "Technology Fairs") $img = "cert_images/fairs.jpg";
			else if($event == "Developers Showcase") $img = "cert_images/ds.jpg";
			else if($event == "Mini-Workshops") $img = "cert_images/workshops.jpg";
			else if($event == "Mobile Apps for Education Showcase") $img = "cert_images/mae.jpg";
			else if($event == "Technology Fairs (Classics)") $img = "cert_images/classics.jpg";
			else if($event == "Volunteer") $img = "cert_images/volunteer.jpg";
			else {
				$event = $sesTitle;
				if(strpos($sesTitle, "Hot Topic") !== false) $img = "cert_images/ht.jpg";
				else if(strpos($sesTitle, "InterSection") !== false) $img = "cert_images/intersection.jpg";
				else if(strpos($sesTitle, "Academic Session") !== false) $img = "cert_images/as.jpg";
				else if(strpos($sesTitle, "Electronic Village Online") !== false) $img = "cert_images/evo.jpg";
				else $img = "cert_images/evo.jpg";
			}
						
			$pdf->AddPage();
			$pdf->Image($img,0,0,-300);
			
			//Print the name
			$pdf->SetFont('Arial','B',24);
			$nameWidth = $pdf->GetStringWidth(stripslashes($name));
			$nameY = 230;
			$nameX = (792 - $nameWidth) / 2;
			$pdf->Text($nameX,$nameY,$name);
			
			//Print the event
			$pdf->SetFont('Arial','B',16);
			$eventWidth = $pdf->GetStringWidth($event);
			$eventY = 350;
			$eventX = (792 - $eventWidth) / 2;
			$pdf->Text($eventX,$eventY, $event);
			
			//Print the title
			$titleWidth = $pdf->GetStringWidth($title);
			$titleFS = 16;
			$titleMaxW = 628;
			while($titleWidth > $titleMaxW) {
				$titleFS--;
				$pdf->SetFont('Arial','B',$titleFS);
				$titleWidth = $pdf->GetStringWidth($title);
			}
			$titleY = 400;
			$titleX = (792 - $titleWidth) / 2;
			$pdf->Text($titleX,$titleY,$title);
			
			//Print the Date and Time
			if(strpos($start,"AM") !== false && strpos($end,"AM") !== false) { //both have AM
				$start = rtrim($start," AM");
			} else if(strpos($start,"PM") !== false && strpos($end,"PM") !== false) { //both have PM
				$start = rtrim($start," PM");
			}
			
			$dateStr = $date.", ".$start." - ".$end;
			$pdf->SetFont('Arial','B',16); //reset the font size
			$dateWidth = $pdf->GetStringWidth($dateStr);
			$dateY = 452;
			$dateX = (792 - $dateWidth) / 2;
			$pdf->Text($dateX,$dateY,$dateStr);			
		}

		$pdf->Output();
		exit();	
	}

	if(isset($_GET["t"])) {
		$t = strip_tags($_GET["t"]);
		if($t == 1) $eType = "Technology Fairs";
		else if($t == 2) $eType = "Mini-Workshops";
		else if($t == 3) $eType = "Developers Showcase";
		else if($t == 4) $eType = "Mobile Apps for Education Showcase";
		else if($t == 5) $eType = "Technology Fairs (Classics)";
		else if($t == 6) $eType = "Other";
	}

	if(isset($_GET["d"])) {
		$d = strip_tags($_GET["d"]);
		if($d == 1) $eDate = $confDate1;
		else if($d == 2) $eDate = $confDate2;
		else if($d == 3) $eDate = $confDate3;
	}

	if(isset($_GET["s"])) {
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
	$prStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email`, `Certificate` FROM `presenters` WHERE 1");
	$prStmt->execute();
	$prStmt->bind_result($prID,$prFN,$prLN,$prEmail,$prAN,$prAC,$prPE,$prCert);
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
			"emailOK" => $prPE,
			"certificate" => $prCert
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
	$oprStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email`, `Certificate` FROM `other_presenters` WHERE 1");
	$oprStmt->execute();
	$oprStmt->bind_result($oprID,$oprFN,$oprLN,$oprEmail,$oprAN,$oprAC,$oprPE,$oprCert);
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
			"emailOK" => $oprPE,
			"certificate" => $oprCert
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
	$cprStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name`, `Email`, `Affiliation Name`, `Affiliation Country`, `Publish Email`, `Certificate` FROM `classics_presenters` WHERE 1");
	$cprStmt->execute();
	$cprStmt->bind_result($cprID,$cprFN,$cprLN,$cprEmail,$cprAN,$cprAC,$cprPE,$cprCert);
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
			"emailOK" => $cprPE,
			"certificate" => $cprCert
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

			if($sessions[$i]["event"] != "Other" && $sessions[$i]["event"] != "Technology Fairs (Classics)") {
				for($k = 0; $k < count($proposals); $k++) {
					if($proposals[$k]["id"] == $pID) {
						$thisPres[$pCount]["id"] = $proposals[$k]["id"];
						$thisPres[$pCount]["title"] = $proposals[$k]["title"];
						$thisPres[$pCount]["summary"] = $proposals[$k]["summary"];
						$thisPres[$pCount]["presenters"] = $proposals[$k]["presenters"];
						break;
					}
				}
			} else if($sessions[$i]["event"] == "Technology Fairs (Classics)") {
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

			if(count($thisPres[$pCount]) == 0) array_splice($thisPres, $pCount, 1);
			else $pCount++;
		}

		$sessions[$i]["presentations"] = $thisPres;
	}

	if(isset($_GET["v"])) { //print certificates to the volunteers
		include "adminTop.php";
?>
		<script type="text/javascript">
			function sendCerts() {
				var tmp = document.getElementById('volunteerData').value;
				tmp = tmp.replace(/\n/g,'||');
				document.getElementById('certData').value = tmp;
				document.getElementById('sendCertForm').submit();
			}
		</script>
		<p style="text-align: left">Enter the names and email addresses of each volunteer below. Use the format "name|email". Put each name and email pair on a NEW LINE.</p>
		<p align="center"><input type="button" value="Print Certificates for Volunteers" onClick="sendCerts()" /></p>
		<p align="center"><a href="print_certificates.php">Back to Session List</a></p>
		<table border="0" width="100%">
			<tr>
				<td width="100%">
					<textarea id="volunteerData" name="volunteerData" rows="10" style="width: 100%"></textarea>
				</td>
			</tr>
		</table>
		<form name="sendCertForm" id="sendCertForm" method="post" action="">
			<input type="hidden" name="certData" id="certData" value="" />
			<input type="hidden" name="certEvent" id="certEvent" value="Volunteer" />
		</form>
<?php
		include "adminBottom.php";
		exit();
	}

	if(isset($_GET["s"])) {
		$i = 0;

		$curDate = $sessions[$i]["date"];
		$tmpDate = explode("-",$curDate);
		$tmpMonth = intval($tmpDate[1]);
		$tmpDay = intval($tmpDate[2]);
		$tmpYear = intval($tmpDate[0]);

		$dateStr = $months[intval($tmpDate[1])]." ".$tmpDate[2].", ".$tmpDate[0];

		$tmpTime = explode("-",$sessions[$i]["time"]);
		$tmpStart = explode(":",$tmpTime[0]);
		$tmpSHour = intval($tmpStart[0]);
		$tmpSMinutes = intval($tmpStart[1]);

		$tmpStartTimeStamp = mktime($tmpSHour,$tmpSMinutes,0,$tmpMonth,$tmpDay,$tmpYear);

		if($tmpSHour < 12) $sAMPM = "AM";
		else {
			$sAMPM = "PM";
			if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
		}
		$tmpSMinutes = $tmpStart[1];

		$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM." to ";

		$tmpEnd = explode(":",$tmpTime[1]);
		$tmpEHour = intval($tmpEnd[0]);
		$tmpEMinutes = intval($tmpEnd[1]);

		$tmpEndTimeStamp = mktime($tmpEHour,$tmpEMinutes,0,$tmpMonth,$tmpDay,$tmpYear);

		if($tmpEHour < 12) $eAMPM = "AM";
		else {
			$eAMPM = "PM";
			if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
		}
		$tmpEMinutes = $tmpEnd[1];

		$timeStr .= $tmpEHour.":".$tmpEMinutes." ".$eAMPM;

		if($sessions[$i]["location"] == "ev") $locationStr = $evLocationStr;
		else if($sessions[$i]["location"] == "ts") $locationStr = $tsLocationStr;
		
		if($sessions[$i]["event"] == "Technology Fairs") $bgImg = "fairs.jpg";
		else if($sessions[$i]["event"] == "Mini-Workshops") $bgImg = "workshops.jpg";
		else if($sessions[$i]["event"] == "Developers Showcase") $bgImg = "ds.jpg";
		else if($sessions[$i]["event"] == "Mobile Apps for Education Showcase") $bgImg = "mae.jpg";
		else if($sessions[$i]["event"] == "Technology Fairs (Classics)") $bgImg = "classics.jpg";
		else if($sessions[$i]["event"] == "Other") {
			if(strpos($sessions[$i]["title"], "Hot Topic") !== false) $bgImg = "ht.jpg";
			else if(strpos($sessions[$i]["title"], "Electronic Village Online") !== false) $bgImg = "evo.jpg";
			else if(strpos($sessions[$i]["title"], "InterSection") !== false) $bgImg = "intersection.jpg";
			else if(strpos($sessions[$i]["title"], "Academic Session") !== false) $bgImg = "as.jpg";
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
		
		td.sList_certNeeded {
			background-color: #CCFFCC;
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

		input.viewBtn {
			background-color: #000000;
			color: #FFFFFF;
			border: solid 1px #000000;
			border-radius: 5px;
			height: 20px;
			padding-right: 10px;
			padding-left: 10px;
		}

		input.viewBtn_selected {
			background-color: #FFFFFF;
			color: #000000;
			border: solid 1px #FFFFFF;
			border-radius: 5px;
			height: 20px;
			padding-right: 10px;
			padding-left: 10px;
		}
		
		#certDiv_bg {
			position: absolute;
			top: 0;
			left: 0;
			height: 100%;
			width: 100%;
			background: rgba(0,0,0,0.6);
			z-index: 1;
		}
		
		#certDiv_mid {
			position: relative;
			height: 100%;
			width: 100%;
			z-index: 2;
		}
		
		#certDiv {
			height: 580px;
			width: 750px;
			position: absolute;
			top: 50%;
			left: 50%;
			margin: -290px 0 0 -375px;
			background-image: url('cert_images/<?=$bgImg?>');
			background-size: 750px 580px;
		}
		
		#certName {
			position: absolute;
			top: 50%;
			left: 0;
			width: 100%;
			margin: -105px 0 0 0;
			text-align: center;
			font-size: 20pt;
			z-index: 3;
		}
		
		#certEvent {
			position: absolute;
			top: 50%;
			left: 0;
			width: 100%;
			margin: 22px 0 0 0;
			text-align: center;
			font-size: 16pt;
			z-index: 3;
		}
		
		#certTitle {
			position: absolute;
			top: 50%;
			left: 0;
			width: 100%;
			margin: 68px 0 0 0;
			text-align: center;
			font-size: 16pt;
			z-index: 3;
		}
		
		#certDate {
			position: absolute;
			top: 50%;
			left: 0;
			width: 100%;
			margin: 118px 0 0 0;
			text-align: center;
			font-size: 16pt;
			z-index: 3;
		}
		
		#certClose {
			position: absolute;
			height: 25px;
			width: 100px;
			top: 50%;
			left: 50%;
			margin: -310px 0 0 285px;
			font-size: 12pt;
			color: #FFFFFF;
			font-weight: bold;
			cursor: pointer;
		}
	</style>
	<script type="text/javascript">
		var presentations = new Array();

<?php
		$pArr = $sessions[$i]["presentations"];
		for($j = 0; $j < count($pArr); $j++) {
?>
		presentations[<?=$j?>] = new Array();
		presentations[<?=$j?>]["id"] = '<?=$pArr[$j]["id"]?>';
		presentations[<?=$j?>]["title"] = '<?=addslashes(stripslashes($pArr[$j]["title"]))?>';
		presentations[<?=$j?>]["date"] = '<?=date("l, F j",$tmpStartTimeStamp)?>';
		presentations[<?=$j?>]["start"] = '<?=date("g:i A",$tmpStartTimeStamp)?>';
		presentations[<?=$j?>]["end"] = '<?=date("g:i A",$tmpEndTimeStamp)?>';
<?php
			if(count($pArr[$j]) > 0) {
				if(isset($pArr[$j]["presenters"])) {
					$prArr = $pArr[$j]["presenters"];
					if(count($prArr) > 0) {
?>
		presentations[<?=$j?>]["presenters"] = new Array();
<?php
						for($k = 0; $k < count($prArr); $k++) {
?>
		presentations[<?=$j?>]["presenters"][<?=$k?>] = new Array();
		presentations[<?=$j?>]["presenters"][<?=$k?>]["name"] = '<?=addslashes(stripslashes($prArr[$k]["first_name"]))." ".addslashes(stripslashes($prArr[$k]["last_name"]))?>';
		presentations[<?=$j?>]["presenters"][<?=$k?>]["email"] = '<?=$prArr[$k]["email"]?>';
		presentations[<?=$j?>]["presenters"][<?=$k?>]["selected"] = false;

<?php
						}
					}
				}
			}
		}
?>
		var selectAllClicked = false;

		function checkAll(n) {
			selectAllClicked = true;
			if(n == '') { //check ALL presenters on the page
				var ckEl = document.getElementById('chkAll');
				var sT = presentations.length;
				for(var i = 0; i < sT; i++) {
					var pEl = document.getElementById('chkAll_' + i);
					if(ckEl.checked) {
						pEl.checked = true;
						document.getElementById('chkTD_' + i).className = 'sList_highlighted';
						document.getElementById('pTD_' + i).className = 'sList_highlighted';
						document.getElementById('vTD_' + i).className = 'sList_highlighted';
						document.getElementById('eTD_' + i).className = 'sList_highlighted';
					} else {
						pEl.checked = false;
						if(i % 2 == 0) {
							document.getElementById('chkTD_' + i).className = 'sList_rowEven';
							document.getElementById('pTD_' + i).className = 'sList_rowEven';
							document.getElementById('vTD_' + i).className = 'sList_rowEven';
							document.getElementById('eTD_' + i).className = 'sList_rowEven';
						} else {
							document.getElementById('chkTD_' + i).className = 'sList_rowOdd';
							document.getElementById('pTD_' + i).className = 'sList_rowOdd';
							document.getElementById('vTD_' + i).className = 'sList_rowOdd';
							document.getElementById('eTD_' + i).className = 'sList_rowOdd';
						}
					}

					var pT = presentations[i]['presenters'].length;
					for(var j = 0; j < pT; j++) {
						var thisEl = document.getElementById('chkPres_' + i + '_' + j);
						if(ckEl.checked) thisEl.checked = true;
						else thisEl.checked = false;
						checkPres(i,j);
					}
				}
			} else {
				var ckEl = document.getElementById('chkAll_' + n);
				if(ckEl.checked) {
					document.getElementById('chkTD_' + n).className = 'sList_highlighted';
					document.getElementById('pTD_' + n).className = 'sList_highlighted';
					document.getElementById('vTD_' + n).className = 'sList_highlighted';
					document.getElementById('eTD_' + n).className = 'sList_highlighted';
				} else {
					if(n % 2 == 0) {
						document.getElementById('chkTD_' + n).className = 'sList_rowEven';
						document.getElementById('pTD_' + n).className = 'sList_rowEven';
						document.getElementById('vTD_' + n).className = 'sList_rowEven';
						document.getElementById('eTD_' + n).className = 'sList_rowEven';
					} else {
						document.getElementById('chkTD_' + n).className = 'sList_rowOdd';
						document.getElementById('pTD_' + n).className = 'sList_rowOdd';
						document.getElementById('vTD_' + n).className = 'sList_rowOdd';
						document.getElementById('eTD_' + n).className = 'sList_rowOdd';
					}
				}

				var pT = presentations[n]['presenters'].length;
				for(var i = 0; i < pT; i++) {
					var thisEl = document.getElementById('chkPres_' + n + '_' + i);
					if(ckEl.checked) thisEl.checked = true;
					else thisEl.checked = false;
					checkPres(n,i);
				}
			}

			selectAllClicked = false;
		}

		function selectAll(n) {
			var ckEl = document.getElementById('chkAll_' + n);
			if(ckEl.checked) ckEl.checked = false;
			else ckEl.checked = true;

			checkAll(n);
		}

		function checkPres(s,n) {
			var ckEl = document.getElementById('chkPres_' + s + '_' + n);
			if(ckEl.checked) {
				presentations[s]["presenters"][n]["selected"] = true;
				document.getElementById('chkTD_' + s + '_' + n).className = 'sList_highlighted';
				document.getElementById('pTD_' + s + '_' + n).className = 'sList_highlighted';
				document.getElementById('vTD_' + s + '_' + n).className = 'sList_highlighted';
				document.getElementById('eTD_' + s + '_' + n).className = 'sList_highlighted';
				document.getElementById('viewBtn_' + s + '_' + n).className = 'viewBtn_selected';
				document.getElementById('editBtn_' + s + '_' + n).className = 'viewBtn_selected';
			} else {
				presentations[s]["presenters"][n]["selected"] = false;
				if(s % 2 == 0) {
					document.getElementById('chkTD_' + s + '_' + n).className = 'sList_rowEven';
					document.getElementById('pTD_' + s + '_' + n).className = 'sList_rowEven';
					document.getElementById('vTD_' + s + '_' + n).className = 'sList_rowEven';
					document.getElementById('eTD_' + s + '_' + n).className = 'sList_rowEven'
				} else {
					document.getElementById('chkTD_' + s + '_' + n).className = 'sList_rowOdd';
					document.getElementById('pTD_' + s + '_' + n).className = 'sList_rowOdd';
					document.getElementById('vTD_' + s + '_' + n).className = 'sList_rowOdd';
					document.getElementById('eTD_' + s + '_' + n).className = 'sList_rowOdd';
				}
				
				document.getElementById('viewBtn_' + s + '_' + n).className = 'viewBtn';
				document.getElementById('editBtn_' + s + '_' + n).className = 'viewBtn';
			}

			//Check to see if all the presenters for this presentation are checked
			var pT = presentations[s]['presenters'].length;
			var allChecked = true;
			for(var i = 0; i < pT; i++) {
				if(presentations[s]['presenters'][i]['selected'] == false) {
					allChecked = false;
					break;
				}
			}

			var ckAllEl = document.getElementById('chkAll_' + s);
			if(selectAllClicked == false) {
				if(allChecked) { // all are checked, so check the box for the title
					if(ckAllEl.checked == false) {
						document.getElementById('chkAll_' + s).checked = true;
						document.getElementById('chkTD_' + s).className = 'sList_highlighted';
						document.getElementById('pTD_' + s).className = 'sList_highlighted';
						document.getElementById('vTD_' + s).className = 'sList_highlighted';
						document.getElementById('eTD_' + s).classname = 'sList_highlighted';
					}
				} else {
					if(ckAllEl.checked) {
						document.getElementById('chkAll_' + s).checked = false;
						if(s % 2 == 0) {
							document.getElementById('chkTD_' + s).className = 'sList_rowEven';
							document.getElementById('pTD_' + s).className = 'sList_rowEven';
							document.getElementById('vTD_' + s).className = 'sList_rowEven';
							document.getElementById('eTD_' + s).className = 'sList_rowEven';
						} else {
							document.getElementById('chkTD_' + s).className = 'sList_rowOdd';
							document.getElementById('pTD_' + s).className = 'sList_rowOdd';
							document.getElementById('vTD_' + s).className = 'sList_rowOdd';
							document.getElementById('eTD_' + s).className = 'sList_rowOdd';
						}
					}
				}
			}
		}

		function selectPres(s,n) {
			var ckEl = document.getElementById('chkPres_' + s + '_' + n);
			if(ckEl.checked) ckEl.checked = false;
			else ckEl.checked = true;

			checkPres(s,n);
		}

		function sendCerts() {
			var dataStr = '';
			for(var i = 0; i < presentations.length; i++) {
				for(var j = 0; j < presentations[i]['presenters'].length; j++) {
					if(presentations[i]['presenters'][j]['selected'] == true) {
						dataStr += presentations[i]['presenters'][j]['email'] + '|';
						dataStr += presentations[i]['presenters'][j]['name'] + '|';
						dataStr += presentations[i]['title'] + '|';
						dataStr += presentations[i]['date'] + '|';
						dataStr += presentations[i]['start'] + '|';
						dataStr += presentations[i]['end'] + '||';
					}
				}
			}

			document.getElementById('certData').value = dataStr;
			document.getElementById('sendCertForm').submit();
		}
		
		function viewCertificate(s,p) {
			document.getElementById('certName').innerHTML = presentations[s]['presenters'][p]['name'];
			document.getElementById('titleSpan').innerHTML = presentations[s]['title'];
			document.getElementById('certDate').innerHTML = presentations[s]['date'] + ', ' + presentations[s]['start'] + ' - ' + presentations[s]['end'];
			var certDiv = document.getElementById('certDiv_bg');
			certDiv.style.top = document.body.scrollTop + 'px';
			document.getElementById('certDiv_bg').style.display = '';
			document.body.style.overflow = 'hidden';
			
			//now, resize the font to the right size for the title
			var maxW = 590;
			var fSize = 16;
			while(document.getElementById('titleSpan').offsetWidth > maxW) {
				fSize--;
				document.getElementById('titleSpan').style.fontSize = fSize + 'pt';
			}
		}
		
		function hideCertificate() {
			//reset the title span
			document.getElementById('titleSpan').style.fontSize = '16pt';
			document.getElementById('certDiv_bg').style.display = 'none';
			document.body.style.overflow = 'auto';
		}
		
		function editInfo(n) {
			window.location.href = 'editProp.php?id=' + presentations[n]['id'] + '&t=print_certificates.php?s=<?=$sesID?>';
		}
	</script>
	<h2 style="text-align: center; font-family: Arial; margin: 0"><?=$sessions[$i]["title"]?></h2>
	<h3 style="text-align: center; font-family: Arial; margin: 0"><?=$timeStr?> (<?=$dateStr?>)</h3>
	<h3 style="text-align: center; font-family: Arial; margin: 0; margin-bottom: 10px"><?=$locationStr?></h3>
	<p align="center"><input type="button" value="Print Certificates for Checked Presenters" onClick="sendCerts()" /></p>
	<p align="center"><a href="print_certificates.php">Back to Session List</a></p>
	<table border="0" align="center" width="100%" style="border-spacing: 0; border-collapse: collapse">
		<tr>
			<th class="sList"><input type="checkbox" id="chkAll" onClick="checkAll('')" /></th>
			<th class="sList">&nbsp;</th>
			<th class="sList">&nbsp;</th>
			<th class="sList">&nbsp;</th>
		</tr>
<?php
		$pArr = $sessions[$i]["presentations"];
		for($j = 0; $j < count($pArr); $j++) {
			if($j % 2 == 0) $row_class = "sList_rowEven";
			else $row_class = "sList_rowOdd";

			$pN = $j + 1;
			if(count($pArr[$j]) > 0) {
				if(isset($pArr[$j]["presenters"])) {
					$prArr = $pArr[$j]["presenters"];
					if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs") {
?>
		<tr>
			<td id="chkTD_<?=$j?>" class="<?=$row_class?>"><input type="checkbox" id="chkAll_<?=$j?>" onClick="checkAll('<?=$j?>')" /></td>
			<td id="pTD_<?=$j?>" class="<?=$row_class?>" style="font-style: italic" onClick="selectAll('<?=$j?>')"><?=$pArr[$j]["title"]?> (<?=$pArr[$j]["station"]?>)</td>
			<td id="vTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
			<td id="eTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
		</tr>
<?php
					} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs (Classics)") {
?>
		<tr>
			<td id="chkTD_<?=$j?>" class="<?=$row_class?>"><input type="checkbox" id="chkAll_<?=$j?>" onClick="checkAll('<?=$j?>')" /></td>
			<td id="pTD_<?=$j?>" class="<?=$row_class?>" style="font-style: italic" onClick="selectAll('<?=$j?>')"><?=$pArr[$j]["title"]?> (<?=$pArr[$j]["station"]?>)</td>
			<td id="vTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
			<td id="eTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
		</tr>
<?php
					} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] != "Technology Fairs") {
?>
		<tr>
			<td id="chkTD_<?=$j?>" class="<?=$row_class?>"><input type="checkbox" id="chkAll_<?=$j?>" onClick="checkAll('<?=$j?>')" /></td>
			<td id="pTD_<?=$j?>" class="<?=$row_class?>" style="font-style: italic" onClick="selectAll('<?=$j?>')"><?=$pArr[$j]["title"]?></td>
			<td id="vTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
			<td id="eTD_<?=$j?>" class="<?=$row_class?>" onClick="selectAll('<?=$j?>')">&nbsp;</td>
		</tr>
<?php
					}

					for($k = 0; $k < count($prArr); $k++) {
						if($prArr[$k]["certificate"] == 1) $row_class = "sList_certNeeded";

?>
		<tr>
			<td id="chkTD_<?=$j?>_<?=$k?>" class="<?=$row_class?>" style="padding-left: 15px"><input type="checkbox" id="chkPres_<?=$j?>_<?=$k?>" onClick="checkPres('<?=$j?>','<?=$k?>')" /></td>
			<td id="pTD_<?=$j?>_<?=$k?>" class="<?=$row_class?>" style="padding-left: 15px" onClick="selectPres('<?=$j?>','<?=$k?>')"><span style="font-weight: bold"><?=stripslashes($prArr[$k]["first_name"]).' '.stripslashes($prArr[$k]["last_name"]).'</span> ('.$prArr[$k]["email"].')'?></td>
			<td id="vTD_<?=$j?>_<?=$k?>" class="<?=$row_class?>"><input type="button" id="viewBtn_<?=$j?>_<?=$k?>" class="viewBtn" value="View Certificate" onClick="viewCertificate('<?=$j?>','<?=$k?>')"></td>
			<td id="eTD_<?=$j?>_<?=$k?>" class="<?=$row_class?>"><input type="button" id="editBtn_<?=$j?>_<?=$k?>" class="viewBtn" value="Edit Info" onClick="editInfo('<?=$j?>')"></td>
		</tr>
<?php
					}
				}
			}
		}

		if($sessions[$i]["event"] == "Other") $thisEvent = $sessions[$i]["title"];
		else $thisEvent = $sessions[$i]["event"];
?>
	</table>
	<form name="sendCertForm" id="sendCertForm" method="post" action="">
		<input type="hidden" name="certData" id="certData" value="" />
		<input type="hidden" name="certEvent" id="certEvent" value="<?=$thisEvent?>" />
		<input type="hidden" name="certSession" id="certSession" value="<?=$_GET["s"]?>" />
		<input type="hidden" name="sessionTitle" id="sessionTitle" value="<?=addslashes(stripslashes($sessions[$i]["title"]))?>" />
	</form>
	<div id="certDiv_bg" style="display: none">
		<div id="certDiv_mid">
			<div id="certDiv"></div>
			<div id="certName">Justin Shewell</div>
			<div id="certTitle"><span id="titleSpan">Test Title</span></div>
			<div id="certEvent"><?=$thisEvent?></div>
			<div id="certDate">Wednesday, April 6, 8:30 - 9:20 AM</div>
			<div id="certClose" onClick="hideCertificate()">CLOSE &times;</div>
		</div>
	</div>
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

		function doSend(n) {
			var url = 'print_certificates.php?s=' + n;
			window.location.href = url;
		}
	</script>
		<p align="left">Click on a session to print the certificates for those presenters for that particular session.</p>
		<p align="right"><input type="button" value="Print Certificates for Volunteers" onClick="window.location.href='print_certificates.php?v=1'" /></p>
<?php
	$eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Technology Fairs (Classics)","Other"); //get all event types -- Classroom of the future is not held in the EV, so it's not on our schedule

	for($e = 0; $e < count($eTypes); $e++) {
		ob_start();
		$rN = 0;
		$cCount = 0;
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] == $eTypes[$e]) { //list this session
				if($rN % 2 == 0) $rowClass = 'sList_rowEven';
				else $rowClass = 'sList_rowOdd';

				//get the number of presentations scheduled for this session
				$pCount = 0;
				for($j = 0; $j < count($sessions[$i]["presentations"]); $j++) {
					$pCount++;
					for($k = 0; $k < count($sessions[$i]["presentations"][$j]["presenters"]); $k++) {
						if($sessions[$i]["presentations"][$j]["presenters"][$k]["certificate"] == 1) $cCount++;
					}
				}
				
				//$pCount = count($sessions[$i]["presentations"]);

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
			<td class="<?=$rowClass?>" width="400" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doSend('<?=$sessions[$i]['id']?>')"><?=$sessions[$i]['title']?></td>
			<td class="<?=$rowClass?>" width="120" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doSend('<?=$sessions[$i]['id']?>')"><?=$dateStr?></td>
			<td class="<?=$rowClass?>" width="170" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doSend('<?=$sessions[$i]['id']?>')"><?=$timeStr?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="doSend('<?=$sessions[$i]['id']?>')"><?=$pCount?></td>
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
			<td colspan="2"><?=$eTypes[$e]?> (Total #: <?=$rN?>)</td>
			<td colspan="2" align="right">Certificates Needed: <?=$cCount?></td>
		</tr>
		<tr>
			<th class="sList">Title</td>
			<th class="sList">Date</td>
			<th class="sList">Time</td>
			<th class="sList" style="text-align: center"># of Presentations</td>
		</tr>
<?php
		echo $rows;
	}
?>
	</table><br /><br />
<?php
	include "adminBottom.php";
?>