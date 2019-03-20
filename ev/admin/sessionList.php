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
	$sStmt = $db->prepare("SELECT s.id AS id, l.name AS location, s.date AS date, s.time AS time, e.event AS event, s.title AS title FROM sessions AS s LEFT JOIN events AS e ON e.id = s.event LEFT JOIN locations AS l ON l.id = s.location ORDER BY s.event, s.date, s.time");
	$sStmt->execute();
	$sStmt->bind_result($id, $location, $date, $time, $event, $title);
	
	//Create an array of the sessions
	$sessions = array();
	while($sStmt->fetch()) {
		if($event == '') $event = 'Other';
		$sessions[] = array(
			"id" => $id,
			"location" => $location,
			"date" => $date,
			"time" => $time,
			"event" => $event,
			"title" => $title
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
	$curEvent = '';
	$eN = 0;
	foreach($sessions AS $s) {
		if($s['event'] != $curEvent) { // this is a new event
			if($curEvent != '') { // we have some data we need to output first
				$rows = ob_get_contents();
				ob_end_clean();
				$eN++;
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="3"><?php echo $curEvent; ?> (Total #: <?php echo $rN; ?>)</td>
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
	
			$curEvent = $s['event'];		
			ob_start();
			$rN = 0;
		}

		if($rN % 2 == 0) $rowClass = 'sList_rowEven';
		else $rowClass = 'sList_rowOdd';
					
		$tmpTime = explode("-",$s["time"]);
		$sStart = $s['date'].' '.$tmpTime[0].':00';
		$sEnd = $s['date'].' '.$tmpTime[1].':00';
?>
		<tr id="session<?php echo $eN; ?>_row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="350" onMouseOver="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>',0)" onClick="editSession('<?php echo $s['id']; ?>')"><?php echo $s['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>',0)" onClick="editSession('<?php echo $s['id']; ?>')"><?php echo date("F j, Y", strtotime($s['date'])); ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>',0)" onClick="editSession('<?php echo $s['id']; ?>')"><?php echo date("g:i A", strtotime($sStart)).' to '.date("g:i A", strtotime($sEnd)); ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>',0)" onClick="editSession('<?php echo $s['id']; ?>')"><?php echo $s['location']; ?></td>
		</tr>
<?php

		$rN++;
	}
		
	$rows = ob_get_contents();
	ob_end_clean();
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="3"><?php echo $curEvent; ?> (Total #: <?php echo $rN; ?>)</td>
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
	include "adminBottom.php";
?>