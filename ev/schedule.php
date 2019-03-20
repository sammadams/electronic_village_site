<?php
	// schedule.php -- displays the schedule based on information given by the user
	// available to the public, so no login is required
	
	include_once "../../ev_config.php";
	include_once "../../ev_library.php";
	include_once "sched_main.php";

	if($isAllowed || (isset($_GET["db"]) && $_GET["db"] == "1")) { //allowed or we are debugging
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
<?php
		if(isset($_GET["t"])) {
			if(strip_tags($_GET["t"]) == "e") {
?>
					<p align="center"><a href="schedule.php?t=d">Organize by Day</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php if($showPDF) { ?> <a href="<?php echo $pdfFileName; ?>" target="blank">Download PDF</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php } if($showMap) { ?> <a href="<?php echo $mapFileName; ?>">Download Map</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php } ?><a href="schedSearch.php">Search</a></p>
<?php
			} else {
?>
					<p align="center"><a href="schedule.php?t=e">Organize by Event</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php if($showPDF) { ?> <a href="<?php echo $pdfFileName; ?>" target="blank">Download PDF</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php } if($showMap) { ?>  <a href="<?php echo $mapFileName; ?>">Download Map</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php } ?><a href="schedSearch.php">Search</a></p>
<?php
			}
		} else {
?>
					<p align="center"><a href="schedule.php?t=e">Organize by Event</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php if($showPDF) { ?> <a href="<?php echo $pdfFileName; ?>" target="blank">Download PDF</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php } if($showMap) { ?><a href="<?=$mapFileName?>">Download Map</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <?php } ?><a href="schedSearch.php">Search</a></p>
<?php
		}
?>
					<p align="left">Click on a session to show the schedule for that particular session.</p>
<?php
		if(isset($_GET["t"]) && strip_tags($_GET["t"]) == "e") {
			//$eTypes = array("Technology Fairs","Mini-Workshops","Developers Showcase","Mobile Apps for Education Showcase","Hot Topics","Graduate Student Research","InterSection","CALL for Newcomers","Academic Session","Technology Fairs Classics","Other"); //get all event types -- Classroom of the future is not held in the EV, so it's not on our schedule

			for($e = 0; $e < count($events); $e++) {
				ob_start();
				$rN = 0;
				for($i = 0; $i < count($sessions); $i++) {
					if($sessions[$i]["eventID"] == $events[$e]['id']) {
						if($rN % 2 == 0) $rowClass = 'sList_rowEven';
						else $rowClass = 'sList_rowOdd';
					
						$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
						$tmpDate = explode("-",$sessions[$i]["date"]);
						$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					
						$tmpTime = explode("-",$sessions[$i]["time"]);
						$tmpStart = explode(":",$tmpTime[0]);
						$tmpSHour = intval($tmpStart[0]);
						$gCSHour = $tmpSHour + $gmtConversion; //get the GMT hour
					
						if($tmpSHour < 12) $sAMPM = "AM";
						else {
							$sAMPM = "PM";
							if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
						}
						$tmpSMinutes = $tmpStart[1];
						
						$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
					
						$tmpEnd = explode(":",$tmpTime[1]);
						$tmpEHour = intval($tmpEnd[0]);
						$gCEHour = $tmpEHour + $gmtConversion; //get the GMT hour
					
						if($tmpEHour < 12) $eAMPM = "AM";
						else {
							$eAMPM = "PM";
							if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
						}
						$tmpEMinutes = $tmpEnd[1];
					
						$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
					
						//Calculate the Google Calendar times and dates
						$gCSStamp = mktime($gCSHour,intval($tmpSMinutes),0,intval($tmpDate[1]),intval($tmpDate[2]),$tmpDate[0]);
						$gCEStamp = mktime($gCEHour,intval($tmpEMinutes),0,intval($tmpDate[1]),intval($tmpDate[2]),$tmpDate[0]);
					
						$gCSStr = date("Ymd",$gCSStamp)."T".date("Hi",$gCSStamp)."00Z";
						$gCEStr = date("Ymd",$gCEStamp)."T".date("Hi",$gCEStamp)."00Z";
					
						$gCalStr = "https://www.google.com/calendar/render?action=TEMPLATE&text=";
						$gCalStr .= str_replace(" ","+",$sessions[$i]["title"]);
						$gCalStr .= "&dates=";
						$gCalStr .= $gCSStr."/".$gCEStr."&ctz=".$tzStr;
						$gCalStr .= "&details=For+a+list+of+presentations,+visit:+http://call-is.org/ev/schedule.php?s=".$i;				

						$locationStr = $sessions[$i]["location"];
						$gCalStr .= "&location=".$locationStr."&sf=true&output=xml";
?>
						<tr>
							<td width="800" colspan="4">
								<table border="0" width="100%" cellpadding="5">
									<tr id="session<?=$e?>_row<?=$rN?>">
										<td class="<?=$rowClass?>" width="<?=(800 - $locationWidth - ($dateWidth + $timeWidth))?>" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1,'<?=$rowClass?>')" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0,'<?=$rowClass?>')" onClick="showPres('<?=$i?>')"><?=$sessions[$i]['title']?></td>
										<td class="<?=$rowClass?>" width="<?=$dateWidth?>" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1,'<?=$rowClass?>')" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0,'<?=$rowClass?>')" onClick="showPres('<?=$i?>')"><?=$dateStr?></td>
										<td class="<?=$rowClass?>" width="<?=$timeWidth?>" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1,'<?=$rowClass?>')" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0,'<?=$rowClass?>')" onClick="showPres('<?=$i?>')"><?=$timeStr?></td>
										<td class="<?=$rowClass?>" width="<?=$locationWidth?>" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1,'<?=$rowClass?>')" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0,'<?=$rowClass?>')" onClick="showPres('<?=$i?>')"><?=$locationStr?></td>									</tr>
								</table>
								<div id="session<?=$i?>_pres" class="presDiv" style="display: none">
									<p align="center" style="font-size: 10pt"><a href="<?=$gCalStr?>" target="new" style="font-size: 10pt">Add to Google Calendar</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href="getICS.php?s=<?=$sessions[$i]['id']?>" style="font-size: 10pt">Add to Outlook / iCalendar</a> (downloads an ICS file)</p>
<?php
						$pArr = $sessions[$i]["presentations"];
						if(count($pArr) > 0) {
?>
									<table border="0" align="center" width="100%" style="border-spacing: 0; border-collapse: collapse">
<?php
						} else if(strpos($sessions[$i]["title"],"Ask Us:") !== false) {
?>
									<p style="font-size: 10pt">Come to the Electronic Village during this time to ask CALL experts questions and try out CALL resources!</p>
<?php				
						}
						
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
									$stationCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; padding-left: 4px; padding-top: 2px; padding-bottom: 2px";
									$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px";
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
										<tr>
											<td style="<?=$prSummaryCSS?>"><?=stripslashes($pArr[$j]["summary"])?></td>
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
									$stationCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; padding-left: 4px; padding-top: 2px; padding-bottom: 2px";
									$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px";
									$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
									$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					
									if($j < (count($pArr) - 1)) {
										$stationCSS .= "; border-bottom: dashed 1px #CCCCCC";
										if(isset($presStr)) $prPresCSS .= "; border-bottom: dashed 1px #CCCCCC";
										else $prSummaryCSS .= "; border-bottom: dashed 1px #CCCCCC";
									}
?>
										<tr>
											<td rowspan="3" width="80" valign="top" style="<?=$stationCSS?>"><?=$pArr[$j]["station"]?></td>
											<td style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
										</tr>
										<tr>
											<td style="<?=$prSummaryCSS?>"><?=stripslashes($pArr[$j]["summary"])?></td>
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
									$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px";
									$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
									$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					
									if($j < (count($pArr) - 1)) {
										if(isset($presStr)) $prPresCSS .= "; border-bottom: dashed 1px #CCCCCC";
										else $prSummaryCSS .= "; border-bottom: dashed 1px #CCCCCC";
									}
?>
										<tr>
											<td style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
										</tr>
										<tr>
											<td style="<?=$prSummaryCSS?>"><?=stripslashes($pArr[$j]["summary"])?></td>
										</tr>
										<tr>
											<td style="<?=$prPresCSS?>"><?=$presStr?></td>
										</tr>
<?php
								}
							}
						}
					
						if(count($pArr) > 0) {
?>
									</table>	
<?php
						}
?>

								</div>
							</td>
						</tr>
<?php
						$rN++;
					}
				}
		
				$rows = ob_get_contents();
				ob_end_clean();
?>
					<table border="0" align="center" cellpadding="0" width="800">
						<tr>
							<td colspan="3" style="font-weight: bold"><?=$events[$e]['event']?></td>
						</tr>
						<tr>
							<th class="sList" width="<?=(800 - $locationWidth - ($dateWidth + $timeWidth))?>">Title</td>
							<th class="sList" width="<?=$dateWidth?>">Date</td>
							<th class="sList" width="<?=$timeWidth?>">Time</td>
							<th class="sList" width="<?=$locationWidth?>">Location</td> 
						</tr>
<?php			
				echo $rows;
?>
					</table><br /><br />
<?php
			}
		} else {
			$curDate = '';
			$dCount = 0;
			$dStr = '';
			for($i = 0; $i < count($sessions); $i++) {
				if($curDate != $sessions[$i]["date"]) { //we are at a new date
					if($dCount > 0) { //this is not the first date
						if($dCount == 1) $dateStr = "Wednesday, ";
						else if($dCount == 2) $dateStr = "Thursday, ";
						else if($dCount == 3) $dateStr = "Friday, ";
	
						$tmpDate = explode("-",$curDate);
						$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');

						$dateStr .= $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
						echo '<p style="font-weight: bold; margin-bottom: 0px">'.$dateStr.'</p>';
?>
					<table border="0" align="center" cellpadding="0" width="800" style="border-spacing: 0px; border-collapse: collapse">
						<tr>
							<th class="sList" width="<?=(800 - $locationWidth - $timeWidth)?>">Title</td>
							<th class="sList" width="<?=$timeWidth?>">Time</td>
							<th class="sList" width="<?=$locationWidth?>">Location</td>
						</tr>
<?php			
						echo $dStr;
?>
					</table><br /><br />
<?php
						$dStr = '';
					}

					$curDate = $sessions[$i]["date"];
					$dCount++;
					$rN = 0;
				}
		
				ob_start();
				if($rN % 2 == 0) $rowClass = 'sList_rowEven';
				else $rowClass = 'sList_rowOdd';
				
				$tmpTime = explode("-",$sessions[$i]["time"]);
				$tmpStart = explode(":",$tmpTime[0]);
				$tmpSHour = intval($tmpStart[0]);
				$gCSHour = $tmpSHour + $gmtConversion; //get the GMT hour
			
				if($tmpSHour < 12) $sAMPM = "AM";
				else {
					$sAMPM = "PM";
					if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
				}
				$tmpSMinutes = $tmpStart[1];
						
				$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
					
				$tmpEnd = explode(":",$tmpTime[1]);
				$tmpEHour = intval($tmpEnd[0]);
				$gCEHour = $tmpEHour + $gmtConversion; //get the GMT hour
			
				if($tmpEHour < 12) $eAMPM = "AM";
				else {
					$eAMPM = "PM";
					if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
				}
				$tmpEMinutes = $tmpEnd[1];
					
				$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;

				$tmpDate = explode("-",$sessions[$i]["date"]);
				//Calculate the Google Calendar times and dates
				$gCSStamp = mktime($gCSHour,intval($tmpSMinutes),0,intval($tmpDate[1]),intval($tmpDate[2]),$tmpDate[0]);
				$gCEStamp = mktime($gCEHour,intval($tmpEMinutes),0,intval($tmpDate[1]),intval($tmpDate[2]),$tmpDate[0]);
			
				$gCSStr = date("Ymd",$gCSStamp)."T".date("Hi",$gCSStamp)."00Z";
				$gCEStr = date("Ymd",$gCEStamp)."T".date("Hi",$gCEStamp)."00Z";
			
				$gCalStr = "https://www.google.com/calendar/render?action=TEMPLATE&text=";
				$gCalStr .= str_replace(" ","+",$sessions[$i]["title"]);
				$gCalStr .= "&dates=";
				$gCalStr .= $gCSStr."/".$gCEStr."&ctz=".$tzStr;
				$gCalStr .= "&details=For+a+list+of+presentations,+visit:+http://call-is.org/ev/schedule.php?s=".$i;				
				/*
				if($sessions[$i]["location"] == "ev") $locationStr = "Electronic Village (".$evLocation.")";
				else if($sessions[$i]["location"] == "ts") $locationStr = "Technology Showcase (".$tsLocation.")";
				*/
				$locationStr = $sessions[$i]["location"];
				$gCalStr .= "&location=".$locationStr."&sf=true&output=xml";
?>
						<tr>
							<td width="800" colspan="3">
								<table border="0" width="100%" cellpadding="5">
									<tr id="session0_row<?=$i?>">
										<td class="<?=$rowClass?>" width="<?=(800 - $locationWidth - $timeWidth)?>" onMouseOver="highlightRow('0','<?=$i?>',1,'<?=$rowClass?>')" onMouseOut="highlightRow('0','<?=$i?>',0,'<?=$rowClass?>')" onClick="showPres('<?=$i?>')"><?=$sessions[$i]['title']?></td>
										<td class="<?=$rowClass?>" width="<?=$timeWidth?>" onMouseOver="highlightRow('0','<?=$i?>',1,'<?=$rowClass?>')" onMouseOut="highlightRow('0','<?=$i?>',0,'<?=$rowClass?>')" onClick="showPres('<?=$i?>')"><?=$timeStr?></td>
										<td class="<?=$rowClass?>" width="<?=$locationWidth?>" onMouseOver="highlightRow('0','<?=$i?>',1,'<?=$rowClass?>')" onMouseOut="highlightRow('0','<?=$i?>',0,'<?=$rowClass?>')" onClick="showPres('<?=$i?>')"><?=$locationStr?></td>
									</tr>
								</table>
								<div id="session<?=$i?>_pres" class="presDiv" style="display: none">
									<p align="center" style="font-size: 10pt"><a href="<?=$gCalStr?>" target="new" style="font-size: 10pt">Add to Google Calendar</a> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href="getICS.php?s=<?php echo $sessions[$i]['id']; ?>" style="font-size: 10pt">Add to Outlook / iCalendar</a> (downloads an ICS file)</p>
<?php
				$pArr = $sessions[$i]["presentations"];
				if(count($pArr) > 0) {
?>
									<table border="0" align="center" width="100%" style="border-spacing: 0; border-collapse: collapse">
<?php
				} else if(strpos($sessions[$i]["title"],"Ask Us:") !== false) {
?>
									<p style="font-size: 10pt">Come to the Electronic Village during this time to ask CALL experts questions and try out CALL resources!</p>
<?php				
				}
		
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
							$stationCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; padding-left: 4px; padding-top: 2px; padding-bottom: 2px";
							$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px";
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
										<tr>
											<td style="<?=$prSummaryCSS?>"><?=stripslashes($pArr[$j]["summary"])?></td>
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
							$stationCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; padding-left: 4px; padding-top: 2px; padding-bottom: 2px";
							$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px";
							$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
							$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					
							if($j < (count($pArr) - 1)) {
								$stationCSS .= "; border-bottom: dashed 1px #CCCCCC";
								if(isset($presStr)) $prPresCSS .= "; border-bottom: dashed 1px #CCCCCC";
								else $prSummaryCSS .= "; border-bottom: dashed 1px #CCCCCC";
							}
?>
										<tr>
											<td rowspan="3" width="80" valign="top" style="<?=$stationCSS?>"><?=$pArr[$j]["station"]?></td>
											<td style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
										</tr>
										<tr>
											<td style="<?=$prSummaryCSS?>"><?=stripslashes($pArr[$j]["summary"])?></td>
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
							$prTitleCSS = "font-weight: bold; font-family: Arial; font-size: 9pt; font-style: italic; padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px";
							$prSummaryCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
							$prPresCSS = "padding-top: 2px; padding-bottom: 2px; padding-left: 4px; padding-right: 4px; font-family: Arial; font-size: 9pt";
					
							if($j < (count($pArr) - 1)) {
								if(isset($presStr)) $prPresCSS .= "; border-bottom: dashed 1px #CCCCCC";
								else $prSummaryCSS .= "; border-bottom: dashed 1px #CCCCCC";
							}
?>
								<tr>
									<td style="<?=$prTitleCSS?>"><?=stripslashes($pArr[$j]["title"])?></td>
								</tr>
								<tr>
									<td style="<?=$prSummaryCSS?>"><?=stripslashes($pArr[$j]["summary"])?></td>
								</tr>
								<tr>
									<td style="<?=$prPresCSS?>"><?=$presStr?></td>
								</tr>
<?php
						}
					}
				}
		
				if(count($pArr) > 0) {
?>
									</table>	
<?php
				}
?>
								</div>
							</td>
						</tr>
<?php
				$dStr .= ob_get_contents();
				ob_end_clean();
		
				$rN++;
			}

			if($dCount == 1) $dateStr = "Wednesday, ";
			else if($dCount == 2) $dateStr = "Thursday, ";
			else if($dCount == 3) $dateStr = "Friday, ";
	
			$tmpDate = explode("-",$curDate);
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');

			$dateStr .= $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
			echo '<p style="font-weight: bold; margin-bottom: 0px">'.$dateStr.'</p>';
?>
					<table border="0" align="center" cellpadding="0" width="800">
						<tr>
							<th class="sList" width="<?=(800 - $locationWidth - $timeWidth)?>">Title</td>
							<th class="sList" width="<?=$timeWidth?>">Time</td>
							<th class="sList" width="<?=$locationWidth?>">Location</td>
						</tr>
<?php			
			echo $dStr;
		}
?>
					</table>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php
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