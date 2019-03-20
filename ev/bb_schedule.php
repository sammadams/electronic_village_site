<?php
	// bb.php -- displays the schedule on an electronic bulletin board with automatic scrolling, etc.
	// available to the public, so no login is required
	
	$isAllowed = false; //the schedule is not ready to be published yet

	include_once "../../ev_config.php";
	include_once "../../ev_library.php";
	
	$confStart = mktime(0,0,0,3,28,2018);
	$confEnd = mktime(0,0,0,3,30,2018);
	$confLocation = "Chicago, Illinois, USA";
	$evLocation = "Exhibition Hall - Booth 491";
	$tsLocation = "Exhibition Hall - Booth 540";
	$tableWidth = "1240";
	$stationWidth = "150";
	$locationWidth = "500";
	$dateWidth = "200";
	$timeWidth = "250";
	
	//$pdfFileName = "ev_program_2018.pdf";
	//$showPDF = true;

	/**************************************************************************************************
	 *	Summary for CALL for Newcomers because there is no actual presentation information in the DB  *
	 **************************************************************************************************/
	$cfnSummary = "Learn CALL basics from experts and enhance your teaching with digital resources. This event includes hands-on guided practice in the Electronic Village on a variety of introductory CALL techniques and tools.";
	
	$y = date("Y",$confStart);
	
	$gmtConversion = 5; //the hour difference between the location and GMT (Toronto is GMT -4, so add 4 to get GMT)
	$tzStr = "America/Chicago";
	
	if($isAllowed || (isset($_GET["db"]) && $_GET["db"] == "1")) { //allowed or we are debugging
		//First, get all the schedule information from the database
		$qStr = "SELECT * FROM `sessions` WHERE 1";
		if(isset($_GET["o"])) {
			$qStr .= " ORDER BY ";
			$order = strip_tags($_GET["o"]);
			if($order == "1") $qStr .= "`date`";
			else if($order == "2") $qStr .= "`time`";
			else if($order == "3") $qStr .= "`event`";
		} else $qStr .= " ORDER BY `date` ASC, `time` ASC, `location` DESC";
	
		$qStmt = $db->prepare($qStr);
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
			
				if($sessions[$i]["event"] != "Other" && $sessions[$i]["event"] != "Technology Fairs (Classics)") {
					for($k = 0; $k < count($proposals); $k++) {
						if($proposals[$k]["id"] == $pID) {
							$thisPres[$pCount]["title"] = $proposals[$k]["title"];
							$thisPres[$pCount]["summary"] = $proposals[$k]["summary"];
							$thisPres[$pCount]["presenters"] = $proposals[$k]["presenters"];
							break;
						}
					}
				} else {
					if($sessions[$i]["event"] == "Technology Fairs (Classics)") {
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
		
			//Currently, the only session types that have stations are Techology Fairs and Classics. If we find one of
			//those types of events, we need to sort by station name to get them in the correct order for listing in
			//the schedule.
		
			if(strpos($sessions[$i]["title"],"Technology Fair") !== false) { //this is a technology fair or a classics
				//Before we add it to the sessions array, we need to sort by station name
				//We want the Mac stations listed first, and then the PC stations and then the BYOD stations.
				//The IDs for each group are sequential, so we need to get the IDs for each group sort them.
			
				$stationTypes = array("Mac","PC","BYOD");
				$tmpStations = array();
				foreach($stationTypes AS $sType) {
					$tmpStationIDs = array();
					foreach($stations AS $k => $v) {
						if(strpos($v["name"],$sType) !== false) {
							$tmpStationIDs[] = $v["id"];
						}
					}
					
					sort($tmpStationIDs); //sort numerically ASC
		
					foreach($tmpStationIDs AS $tsID) {
						for($tStI = 0; $tStI < count($stations); $tStI++) {
							if($stations[$tStI]["id"] == $tsID) {
								$tmpStations[] = $stations[$tStI];
								break;
							}
						}
					}
				}
		
				$stations = $tmpStations; //get the sorted order back into the stations array
		
				//Now we have the stations sorted by ID ascending, so go through the $thisPres array and sort the same way
				$tmpPresentations = array();
				foreach($stations AS $k => $v) {
					for($tPrI = 0; $tPrI < count($thisPres); $tPrI++) {
						if($thisPres[$tPrI]["station"] == $v["name"]) { //found the right presentation for this station
							$tmpPresentations[] = $thisPres[$tPrI];
							break;
						}
					}
				}
			
				$thisPres = $tmpPresentations; //get the sorted array back into $thisPres
			} else if($sessions[$i]["title"] == "CALL for Newcomers") {
				$thisPres = array(array('title' => '','summary' => $cfnSummary,'presenters' => array()));
			}
		
			$sessions[$i]["presentations"] = $thisPres;		
		}

		if(isset($_POST["days"])) { // POST data was submitted, so get the schedule for the selected days
			$display_days = explode(",", $_POST["days"]); //creates an array with elements representing the days of the conference (e.g. array('1','3') => show day 1 and day 3
			//$display_events = explode(",", $_POST["events"]); //creates an array with elements representing the events in the EV (e.g. array('1','4','5') => show events 1, 4 and 5
			
			// SETTINGS FOR DISPLAYING AND SCROLLING THE SCHEDULE CONTENT
			// These can be set in the form and so are included here with their defaults, just in case.
			$scrollPixels = (isset($_POST["scrollPixels"])) ? preg_replace("/\D/", "", $_POST["scrollPixels"]) : "50"; //the pixels per speed below
			$scrollSpeed = (isset($_POST["scrollSpeed"])) ? preg_replace("/\D/", "", $_POST["scrollSpeed"]) : "1000"; //speed in milliseconds
			$waitInterval = (isset($_POST["waitInterval"])) ? preg_replace("/\D/", "", $_POST["waitInterval"]) : "3000"; //time in milliseconds (per certain number of pixels in height of div)
			$waitPixels = (isset($_POST["waitPixels"])) ? preg_replace("/\D/", "", $_POST["waitPixels"]) : "50"; //pixels per wait interval above
			$scrollTopDiff = (isset($_POST["scrollTopDiff"])) ? preg_replace("/\D/", "", $_POST["scrollTopDiff"]) : "99"; //the number of pixels that are somehow being additionally subtracted from the scroll top
?>
<html>
	<head>
		<title>Schedule -- Electronic Village</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 16pt;
				color: #000000;
				background-color: #FFFFFF;
				margin-left: 30px;
				margin-right: 30px;
				margin-top: 30px;
				margin-bottom: 30px;
			}
			
			div.sessionHeader {
				overflow: auto;
				background-color: #FFFFFF;
			}
			
			div.sessionTitle {
				width: 100%;
				text-align: center;
				font-weight :bold;
				font-size: 2em;
				background-color: #FFFFFF;
			}
			
			div.sessionTime {
				clear: both;
				float: left;
				margin-left: <?= $stationWidth ?>px;
				font-size: 1.2em;
				background-color: #FFFFFF;
				color: #AA0000;
			}
			
			div.sessionLocation {
				clear: both;
				float: left;
				margin-left: <?= $stationWidth ?>px;
				margin-bottom: 30px;
				font-size: 1.2em;
				background-color: #FFFFFF;
				color: #AA0000;
			}
			
			div.sessionPresentations {
				clear: both;
				margin-bottom: 100px;
				overflow: auto;
			}
			
			div.stationInfo {
				float: left;
				width: <?= $stationWidth ?>px;
				margin-bottom: 20px;
			}
			
			div.presentationInfo {
				float: left;
				width: <?= $tableWidth - $stationWidth ?>px;
				margin-bottom: 20px;
			}
			
			div.presentationTitle {
				margin-top: 20px;
				color: #0000AA;
				font-weight: bold;
			}
			
			div.presentationTitle_fairs {
				color: #0000AA;
				font-weight: bold;
			}
			
			div.presentersInfo {
				/* padding-left: 20px; */
			}
			
			h1 {
				background-color: #0d487e;
				color: #FFFFFF;
			}
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script type="text/javascript">
			var isScrolling = false;
			var scrolled = 1;
			var looped = 0;
			var rTimeout; //the timeout to remove the loop
			var sTimeout; //the timeout for the next scrolling action
			var elLength = 0;
			var wW = 0;
			
			$(document).ready(function() {
				wW = $(document.body).width();
				$('.presentationInfo').css('width', (wW - <?= $stationWidth ?>) + 'px');
				setTimeout(function() { scrollInfo(); }, 3000);
				$(document.body).click(function() {
					var leaveOK = confirm('Are you sure you want to go back to the settings screen?');
					if(leaveOK) window.location.href = '<?= $_SERVER["REQUEST_URI"] ?>';
				});
			});
						
			function scrollInfo() {
				isScrolling = true;
				elLength = $('#loop' + looped + ' > .sessionHeader').length;
				$('#loop' + looped + ' > .sessionHeader').each(function() {
					var ot = $(this).offset().top;
					var st = $(document.body).scrollTop();
										
					if(parseInt(ot) > parseInt(st)) {
						var divht = $(this).nextAll('.sessionPresentations').height();
						var wait = (divht / <?= $waitPixels ?>) * <?= $waitInterval ?>;
						console.log('OT: ' + ot + '\nST: ' + st + '\nHTML: ' + $(this).find('>:first').html());
						//console.log(wait);
						
						var scrollht = ot - st - 8; // add 8px just for padding so it's not touching the top (8px was chosen because the first DIV's offset top is 8px)

						console.log(scrolled);
						if(scrolled == elLength) {
							console.log('End of first loop!');
							$(document.body).stop(true,true).animate(
								{ scrollTop: ot + 'px' },
								(scrollht / <?= $scrollPixels ?>) * <?= $scrollSpeed ?>,
								'linear',
								function() {
									scrolled++;
									var lDiv = document.createElement('DIV');
									var lHTML = $('#loop' + looped).html();
									looped++;
									lDiv.setAttribute('id','loop' + looped);
									document.body.appendChild(lDiv);
									$('#loop' + looped).html(lHTML);
									sTimeout = setTimeout(function() {
										scrollInfo();
									}, wait);
								}
							);
						} else if(scrolled == (parseInt(elLength) + parseInt(1))) {
							$(document.body).stop(true,true).animate(
								{ scrollTop: ot + 'px' },
								(scrollht / <?= $scrollPixels ?>) * <?= $scrollSpeed ?>,
								'linear',
								function() {
									rTimeout = setTimeout(function() {
										removeLoop();
									}, 1000);
								}
							);
						} else {
							console.log('Scrolling!');
							$(document.body).stop(true,true).animate(
								{ scrollTop: ot + 'px' },
								(scrollht / <?= $scrollPixels ?>) * <?= $scrollSpeed ?>,
								'linear',
								function() {
									scrolled++;
									sTimeout = setTimeout(function() {
										scrollInfo();
									}, wait);
								}
							);
						}
						
						return false;
					}
				});
			}
			
			function removeLoop() {
				var oldLoop = looped - 1;
				var rDiv = $('#loop' + oldLoop);
				var rHt = $(rDiv).height();
				var cST = $(document.body).scrollTop();
				var nST = cST - rHt - <?= $scrollTopDiff ?>;
				$('#loop' + oldLoop).remove();
				$(document.body).scrollTop(nST);
				scrolled = scrolled - elLength;
				scrollInfo(); //start the scrolling again
			}
			
			function stopScroll() {
				isScrolling = false;
				$(document.body).stop(true,true); //stop any current animation
				clearTimeout(); //stop any waiting timeouts
			}
		</script>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" />
	</head>
	
	<body>
		<div id="loop0">
<?php
			for($dI = 0; $dI < count($display_days); $dI++) {
				$thisDay = $display_days[$dI];
				$dStr = '';

				ob_start();

				for($i = 0; $i < count($sessions); $i++) {
					if($thisDay == $sessions[$i]["date"]) { //we are at session for this day
						$dateStr = date("l, F j", strtotime($sessions[$i]["date"]));
						$tmpTime = explode("-",$sessions[$i]["time"]);
						$timeStr = date("g:i A", strtotime($sessions[$i]["date"]." ".$tmpTime[0]))." to ".date("g:i A", strtotime($sessions[$i]["date"]." ".$tmpTime[1]));

						if($sessions[$i]["location"] == "ev") $locationStr = "Electronic Village (".$evLocation.")";
						else if($sessions[$i]["location"] == "ts") $locationStr = "Technology Showcase (".$tsLocation.")";
?>
			<div class="sessionHeader">
				<div class="sessionTitle"><?= $sessions[$i]["title"] ?></div>
				<div class="sessionTime"><?= $timeStr." (".$dateStr.")" ?></div>
				<div class="sessionLocation"><?= $locationStr ?></div>
			</div>
<?php
						$pArr = $sessions[$i]["presentations"];
						if(count($pArr) > 0 && $sessions[$i]["title"] != "CALL for Newcomers") {
?>
			<div class="sessionPresentations">
<?php
						} else if(strpos($sessions[$i]["title"],"Ask Us:") !== false) {
?>
			<div class="sessionPresentations" style="margin-left: <?= $stationWidth ?>px;">
				<p>Come to the Electronic Village during this time to ask CALL experts questions and try out CALL resources!</p>
			</div>
<?php				
						} else if($sessions[$i]["title"] == "CALL for Newcomers") {
?>
			<div class="sessionPresentations" style="margin-left: <?= $stationWidth ?>px;"><p><?= $cfnSummary ?></p>
<?php
						}
		
						for($j = 0; $j < count($pArr); $j++) {
							if(count($pArr[$j]) > 0) {
								if(isset($pArr[$j]["presenters"])) {
									$prArr = $pArr[$j]["presenters"];
									$presStr = "";
									for($k = 0; $k < count($prArr); $k++) {
										$presStr .= '<div class="presenterInfo"><span style="font-weight: bold">'.stripslashes($prArr[$k]["first_name"]).' '.stripslashes($prArr[$k]["last_name"]).',</span> '.stripslashes(trim($prArr[$k]["affiliation"])).', '.$prArr[$k]["country"]."</div>";
									}
								}

								if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs") {
?>
				<div class="stationInfo"><?= $pArr[$j]["station"] ?></div>
				<div class="presentationInfo">
					<div class="presentationTitle_fairs"><?= stripslashes($pArr[$j]["title"]) ?></div>
<?php
									if(isset($presStr)) {
?>
					<div class="presentersInfo"><?= $presStr ?></div>
<?php
									}
?>
				</div>
<?php
								} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs Classics") {
?>
				<div class="stationInfo"><?= $pArr[$j]["station"] ?></div>
				<div class="presentationInfo">
					<div class="presentationTitle_fairs"><?= stripslashes($pArr[$j]["title"]) ?></div>
<?php
									if(isset($presStr)) {
?>
					<div class="presentersInfo"><?= $presStr ?></div>
<?php
									}
?>
				</div>
<?php
								} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] != "Technology Fairs") {
?>
				<div class="presentationTitle" style="margin-left: <?= $stationWidth ?>px;"><?= stripslashes($pArr[$j]["title"]) ?></div>
				<div class="presentersInfo" style="margin-left: <?= $stationWidth ?>px;"><?= $presStr ?></div>
<?php
								}
							}
						}
		
						if(count($pArr) > 0) {
?>
			</div>	
<?php
						}			
					}
				}

				$dStr .= ob_get_contents();
				ob_end_clean();
		
				$thisDate = date("l, F j", strtotime($thisDay));
?>
			<p style="text-align: center"><img class="bannerImg" src="tesol_banner.jpg" style="max-width: 800px" /></p>
			<h1 align="center"><?= $thisDate ?></h1>
<?php
				echo $dStr;
			}
?>
		</div>
	</body>
</html>
<?php
		} else { // Need to select the days, so show the form
			// SETTINGS FOR THE DISPLAY AND SCROLLING OF SCHEDULE CONTENT
			// These can be set in the form below. The defaults are given here.
			$scrollPixels = "50"; //the pixels per speed below
			$scrollSpeed = "1000"; //speed in milliseconds
			$waitInterval = "3000"; //time in milliseconds (per certain number of pixels in height of div)
			$waitPixels = "50"; //pixels per wait interval above
			$scrollTopDiff = "99"; //the number of pixels that are somehow being additionally subtracted from the scroll top
?>
<html>
	<head>
		<title>Schedule -- Electronic Village</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 16pt;
				color: #000000;
				background-color: #FFFFFF;
				margin-left: 30px;
				margin-right: 30px;
				margin-top: 30px;
				margin-bottom: 30px;
			}			
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<script type="text/javascript">
		</script>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" />
	</head>
	
	<body>
<?php
			$display_days = array();
			for($i = 0; $i < count($sessions); $i++) {
				$curDate = $sessions[$i]["date"];
				if(!in_array($curDate, $display_days)) $display_days[] = $curDate;
				else continue;
			}
?>
		<p style="text-align: center"><img src="tesol_banner.jpg" style="max-width: 800px" /></p>
		<h3 align="center">Select the days to display:</h3>
		<table align="center">
			<tr>
				<td>
<?php
			foreach($display_days AS $tmpDay) {
?>
					<input type="checkbox" value="<?= $tmpDay ?>" class="display_day_check"> <?= date("l, F j", strtotime($tmpDay)) ?><br>
<?php
			}
?>
				</td>
			</tr>
		</table>
		<h3 align="center">Set the display options:</h3>
		<table align="center" width="800" cellpadding="5" cellspacing="0">
			<tr>
				<td colspan="2">
					"Scrolling Pixels" refers to the amount of content is scroller per "Scrolling Speed Unit" below. For example, if "Scrolling Pixels" is set to 50 and "Scrolling Speed Unit" below is set to 1 second, 50 pixels will be scrolled every second. You can increase the scrolling speed by either adjusting the "Scrolling Pixels", or the "Scrolling Speed Unit", or both.
				</td>
			</tr>
			<tr>
				<td align="right" width="50%">Scrolling Pixels:</td>
				<td align="left" width="50%"><input id="scrollPixels_input" type="number" step="50" style="width: 50px;" value="<?= $scrollPixels ?>" /> pixels</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2">"Scrolling Speed Unit" is the number of seconds that the set content length above will scroll. You can adjust the scrolling speed by adjusting the "Scrolling Speed Unit" below, or the "Scrolling Pixels" value above, or both.
			</tr>
			<tr>
				<td align="right" width="50%">Scrolling Speed Unit:</td>
				<td align="left" width="50%"><input id="scrollSpeed_input" type="number" step="0.5" style="width: 35px;" value="<?= ($scrollSpeed / 1000) ?>" /> seconds</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2">While scrolling, the script stops at every session and waits for a set amount of time to give the readers a chance to view all the content before moving it off the screen. The "Wait Interval" is the number of seconds the script will wait per "Wait Pixels" amount below. For exmaple, if the "Wait Interval" is set to 1 second and the "Wait Pixels" is set to 50, the program will wait 1 second for every 50 pixels in length of content. You can adjust the wait time by adjusting the "Wait Interval", or the "Wait Pixels", or both.</td>
			</tr>
			<tr>
				<td align="right" width="50%">Wait Interval:</td>
				<td align="left" width="50%"><input id="waitInterval_input" type="number" step="0.5" style="width: 35px;" value="<?= ($waitInterval / 1000) ?>" /> seconds</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2">"Wait Pixels" is the amount of content that will be used to calculate the wait time as described above. You can adjust the wait time by adjusting the "Wait Pixels", or the "Wait Interval", or both.</td>
			</tr>
			<tr>
				<td align="right" width="50%">Wait Pixels:</td>
				<td align="left" width="50%"><input id="waitPixels_input" type="number" step="50" style="width: 50px;" value="<?= $waitPixels ?>" /> pixels</td>
			</tr>
			<tr>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td colspan="2">For some reason, when looping the content to create the continuous scrolling effect, there is a jump. We can eliminate the jump by setting the "Scroll Top Difference" to the right number of pixels.</td>
			</tr>
			<tr>
				<td align="right" width="50%">Scroll Top Difference:</td>
				<td align="left" width="50%"><input id="scrollTopDiff_input" type="number" step="1" style="width: 50px;" value="<?= $scrollTopDiff ?>" /> pixels</td>
			</tr>
		</table>
		
		<form id="scrollForm" action="" method="post">
			<input type="hidden" id="days_input" name="days" value="" />
			<input type="hidden" id="scrollPixels" name="scrollPixels" value="<?= $scrollPixels ?>" />
			<input type="hidden" id="scrollSpeed" name="scrollSpeed" value="<?= $scrollSpeed ?>" />
			<input type="hidden" id="waitInterval" name="waitInterval" value="<?= $waitInterval ?>" />
			<input type="hidden" id="waitPixels" name="waitPixels" value="<?= $waitPixels ?>" />
			<input type="hidden" id="scrollTopDiff" name="scrollTopDiff" value="<?= $scrollTopDiff ?>" />
			<p style="text-align: center;"><input type="submit" value="Display Schedule" style="background-color: #CCCCCC; font-size: 14pt; border: solid 1px #000000; border-radius: 5px;" /></p>
		</form>
		<script type="text/javascript">
			$('.display_day_check').click(function() {
				var dayStr = '';
				$('.display_day_check').each(function() {
					if($(this)[0].checked) {
						if(dayStr != '') dayStr += ',';
						dayStr += $(this).val();
					}
					
					$('#days_input').val(dayStr);
				});
			});
			
			$('#scrollPixels_input').keyup(function() {
				$('#scrollPixels').val($(this).val());
			});
			
			$('#scrollSpeed_input').keyup(function() {
				$('#scrollSpeed').val($(this).val() * 1000);
			});
			
			$('#waitInterval_input').keyup(function() {
				$('#waitInterval').val($(this).val() * 1000);
			});
			
			$('#waitPixels_input').keyup(function() {
				$('#waitPixels').val($(this).val());
			});
			
			$('#scrollForm').submit(function() {
				if($('#days_input').val() == '') {
					alert('Please select one or more days to display!');
					return false;
				}				
			});
		</script>
	</body>
</html>
<?php			
		}
	} else { //schedule is not allowed at this time
?>
<html>
	<head>
		<title>Schedule -- Electronic Village</title>
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
			
			div.presDiv {
				padding-left: 25px;
				padding-right: 5px;
				border-left: dashed 1px #000000;
				border-right: dashed 1px #000000;
				border-bottom: dashed 1px #000000;
			}
		</style>
		<script type="text/javascript">
			function highlightRow(e,r,n,c) {
				var rEl = document.getElementById('session' + e + '_row' + r);
				for(i = 0; i < rEl.cells.length; i++) {
					var cEl = rEl.cells[i];
					if(n == 1) cEl.className = 'sList_highlighted';
					else if(n == 0) cEl.className = c;
				}
			}
		
			function showPres(n) {
				var el = document.getElementById('session' + n + '_pres');
				if(el.style.display == 'none') el.style.display = '';
				else el.style.display = 'none';
			}
		</script>
		<link rel="icon" type="image/png" href="http://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" />
	</head>
	
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td style="padding-top: 25px">
					<h2 align="center">Electronic Village Online Schedule</h2>
					<p>The schedule for Electronic Village <?=$y?> is not available at this time. The schedule will be available approximately 3 weeks before the TESOL convention. Please check back later.</p>
					<p>If you have any questions, please contact the Electronic Village Program Manager at <a href="mailto:ev@call-is.org">ev@call-is.org</a>.</p>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php	
	}
?>