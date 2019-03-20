<?php
	//sessionList.php - allows a user to see and edit the sessions in the schedule
	//accessible only to admin users (admins)
	
	include_once "login.php";
	
	$topTitle = "Session List";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false) { //only admin and char users have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	//Get the list of sessions
	$sStmt = $db->prepare("SELECT * FROM `sessions` WHERE 1 ORDER BY `date`, `time`");
	$sStmt->execute();
	$sStmt->bind_result($id,$location,$date,$time,$event,$title,$presentations);
	
	//Create an array of the sessions
	$sessions = array();
	while($sStmt->fetch()) {
		$sessions[] = array(
			"id" => $id,
			"location" => $location,
			"date" => $date,
			"time" => $time,
			"event" => $event,
			"title" => $title,
			"presentations" => $presentations
		);
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
		
		function editSession(n) {
			window.location.href = 'editSession.php?s=' + n;
		}
	</script>
	<p align="center"><input type="button" value="Add Session" onClick="window.location.href='addSession.php'" /></p>
<?php
	$eTypes = array('Technology Fairs','Mini-Workshops','Developers Showcase','Mobile Apps for Education Showcase','Other'); //Classroom of the Future is not actually on the EV schedule
		
	for($e = 0; $e < count($eTypes); $e++) {
		ob_start();
		$rN = 0;
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] == $eTypes[$e]) { //list this session
				if($rN % 2 == 0) $rowClass = 'sList_rowEven';
				else $rowClass = 'sList_rowOdd';
					
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
				
				if($sessions[$i]['location'] == "ts") $locStr = "Technology Showcase";
				else if($sessions[$i]['location'] == "ev") $locStr = "Electronic Village";
?>
		<tr id="session<?=$e?>_row<?=$rN?>">
			<td class="<?=$rowClass?>" width="350" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="editSession('<?=$sessions[$i]['id']?>')"><?=$sessions[$i]['title']?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="editSession('<?=$sessions[$i]['id']?>')"><?=$dateStr?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="editSession('<?=$sessions[$i]['id']?>')"><?=$timeStr?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('<?=$e?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$e?>','<?=$rN?>',0)" onClick="editSession('<?=$sessions[$i]['id']?>')"><?=$locStr?></td>
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
			<th class="sList">Location</td>
		</tr>
<?php			
		echo $rows;
?>
	</table><br /><br />
<?php
	}

	include "adminBottom.php";
?>