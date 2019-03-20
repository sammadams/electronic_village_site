<?php
	// program.php -- prints out the schedule in the format needed for the printed EV Program Book
	
	include_once "login.php";
	include_once "../../../ev_config.php";
	include_once "../../../ev_library.php";
	include_once "../sched_main.php";
	
	/*
	echo "<pre>";
	print_r($sessions);
	echo "</pre>";
	exit();
	*/
	$evLocationStr = "Exhibition Hall - Booth 917";
	$tsLocationStr = "Exhibition Hall - Booth 1111";
	$spLocationStr = "GWC A315";

	
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
	
	
	if(!isset($_GET["d"]) && !isset($_GET["t"]) && !isset($_GET["s"])) { //show the "index" part of the page (gives a list of different printing options (e.g. by date, by event, etc.)
		$topTitle = "Print Program";
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
			if(n == undefined || n == null || n == '') { //get the date or event values
				var tSel = document.getElementById('pEvent');
				var t = tSel.options[tSel.selectedIndex].value;
			
				var dSel = document.getElementById('pDate');
				var d = dSel.options[dSel.selectedIndex].value;
			
				var url = 'program_bulletin.php';
				if(t != '' && d != '') {
					url += `?t=${t}&d=${d}`;
				};
				if(t != '' && d == '') {
					url += `?t=${t}`; 
				};
				if(d != '' && t == '') { 
					url += `?d=${d}`;
				};
			} else { //get the schedule for specific session
				var url = 'program_bulletin.php?s=' + n;
			}
			
			window.location.href = url;
		}
	</script>
		<p align="center">Choose the date: 
			<select id="pDate">
				<option value="">Wednesday, Thursday, and Friday</option>
				<option value="1">Wednesday only</option>
				<option value="2">Thursday only</option>
				<option value="3">Friday only</option>
			</select>
			&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;
			Choose the Event:
			<select id="pEvent">
				<option value="">All Events</option>
				<option value="1">Technology Fairs only</option>
				<option value="2">Mini-Workshops only</option>
				<option value="3">Developers Showcase only</option>
				<option value="4">Mobile Apps for Education Showcase only</option>
				<option value="5">Technology Fair Classics only</option>
				<option value="6">Other Events (e.g. InterSections)</option>
				<option value="7">Hot Topics only</option>
				<option value="8">Graduate Student Research only</option>
			</select>
		</p>
		<p align="center"><input type="button" value="Print Schedule" onClick="doPrint()" /></p>
		<h2 align="center">OR</h2>
		<p align="left">Click on a session to print the schedule for that particular session.</p>
<?php
		$eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Other","Hot Topics","Graduate Student Research","Technology Fairs Classics"); //get all event types -- Classroom of the future is not held in the EV, so it's not on our schedule

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
					// TODO: hard-coded month string because array was not replacing int value correctly
					$dateStr = "March ".intval($tmpDate[2]).", ".$tmpDate[0];
					
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
	</table><br /><br />
<?php
		}

		include "adminBottom.php";
		exit();
	}
	
	/*
		We need to print some html head information to include such things as charset.
	 */
?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<style type="text/css">

			div.pagebreak {
				page-break-before: always;
				text-align: center;
			}

			span.pagebreak {
				font-size: 34px;
			}

			tbody { 
				border-top: 1px solid black;
				border-bottom: 1px solid black;
    			display: block;
    			width: 100%;
				break-inside: avoid;  
			}

			tbody.event-heading {
				display: inline-table;
				page-break-inside: avoid;
			}

			tbody.event-group {
				break-inside: avoid;
			}

		</style>
	</head>

	<body>
<?php
	$curDate = '';
	$dCount = -1;
	for($i = 0; $i < count($sessions); $i++) {
		if($curDate != $sessions[$i]["date"]) {
			$curDate = $sessions[$i]["date"];
			$tmpDate = explode("-",$curDate);
			// TODO: hard-coded month string because array was not replacing int value correctly
			$dateStr = "March ".intval($tmpDate[2]).", ".$tmpDate[0];
			if($dCount > -1) { echo "</table><br /><br />"; };

			echo "<div class='pagebreak'><span class='pagebreak'>".$dateStr."</span></div>";
			$dCount++;
?>
	<table border="0" align="center" width="100%" style="border-spacing: 0; border-collapse: collapse; border: solid 1px #000000">
<?php			
		}

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
		
		$timeCSS = "background-color: #999999; color: #FFFFFF; font-family: Arial; font-size: 18px; font-weight: bold; border: solid 1px #000000";
		$locationCSS = "font-weight: bold; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 16px; border-left: solid 1px #000000; border-right: solid 1px #000000";
		
		$titleCSS = "font-weight: bold; font-style: italic; font-family: Arial; font-size: 18px; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; border-left: solid 1px #000000; border-right: solid 1px #000000";

?>
	<tbody class="event-heading">
		<tr>
			<td colspan="2" width="100%" style="<?=$timeCSS?>; text-align: center;">
				<?=$timeStr?>
			</td>
		</tr>
		<tr>
			<td colspan="2" width="100%" style="<?=$locationCSS?>; text-align: center;">
				<?=$sessions[$i]["location"]?>
			</td>
		</tr>
		<tr>
			<td colspan="2" width="100%" style="<?=$titleCSS?>; text-align: center;">
				<?=$sessions[$i]["title"]?>
			</td>
		</tr>
	</tbody>
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
					$stationCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; padding-left: 4px; padding-top: 2px; padding-bottom: 2px; border-left: solid 1px #000000";
					$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px;";
					$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					
					if($j < (count($pArr) - 1)) {
						$stationCSS .= "; border-bottom: solid 1px #CCCCCC";
						if(isset($presStr)) $prPresCSS .= "; border-bottom: solid 1px #CCCCCC";
						else $prSummaryCSS .= "; border-bottom: solid 1px #CCCCCC";
					}
?>
		<tbody>
		<tr>
			<td rowspan="3" width="80" valign="top" style="<?=$stationCSS?>"><?=$pArr[$j]["station"]?></td>
			<td style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
		</tr>
<?php
					if(isset($presStr)) {
?>
		<tr>
			<td style="<?=$prPresCSS?>"><?=$presStr?></td>
		</tr>
		</tbody>
<?php
					}
				} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] == "Technology Fairs Classics") {
					$stationCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; padding-left: 4px; padding-top: 2px; padding-bottom: 2px; border-left: solid 1px #000000";
					$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px;";
					$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					
					if($j < (count($pArr) - 1)) {
						$stationCSS .= "; border-bottom: solid 1px #CCCCCC";
						if(isset($presStr)) $prPresCSS .= "; border-bottom: solid 1px #CCCCCC";
						else $prSummaryCSS .= "; border-bottom: solid 1px #CCCCCC";
					}
?>
		<tr>
			<td rowspan="3" width="80" valign="top" style="<?=$stationCSS?>"><?=$pArr[$j]["station"]?></td>
			<td style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
		</tr>
<?php
					if(isset($presStr)) {
?>
		<tr>
			<td style="<?=$prPresCSS?>"><?=$presStr?></td>
		</tr>
		</tbody>
<?php
					}
				} else if(isset($pArr[$j]["title"]) && $sessions[$i]["event"] != "Technology Fairs") {
					$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; border-left: solid 1px #000000";
					$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt; border-left: solid 1px #000000; border-left: solid 1px #000000";
					$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt; border-left: solid 1px #000000";
					
					if($j < (count($pArr) - 1)) {
						if(isset($presStr)) $prPresCSS .= "; border-bottom: solid 1px #CCCCCC";
						else $prSummaryCSS .= "; border-bottom: solid 1px #CCCCCC";
					}
?>
		<tbody>
		<tr>
			<td colspan="2" style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
		</tr>
<?php
					if(isset($presStr)) {
?>
		<tr>
			<td colspan="2" style="<?=$prPresCSS?>"><?=$presStr?></td>
		</tr>
		</tbody>
<?php

					}				
				}
			}
		}
	}
?>
	</table>
	</body>
</html>