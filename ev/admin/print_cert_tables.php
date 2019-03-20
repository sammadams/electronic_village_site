<?php
	// program.php -- prints out the schedule in the format needed for the printed EV Program Book
	
	include_once "login.php";
	include_once "../../../ev_config.php";
	include_once "../../../ev_library.php";
	include_once "../sched_main.php";
	
	$topTitle = "Print Table for Mail Merge for Certificates";
	
	if(strpos($_SESSION['user_role'],"admin") === false) {
		include "adminTop.php";
?>
	<h2 align="center" style="color: red">Access Denied!</h2>
	<h3 align="center">You do not have permission to access this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$evtStmt = $db->prepare("SELECT id, event, adminSuffix, propTable FROM events WHERE isActive = 'Y'");
	$evtStmt->execute();
	$evtStmt->bind_result($evtID, $evtEvent, $evtAdminSuffix, $evtPropTable);
	
	$events = array();
	while($evtStmt->fetch()) {
		$events[] = array(
			"id" => $evtID,
			"event" => $evtEvent,
			"adminSuffix" => $evtAdminSuffix,
			"propTable" => $evtPropTable
		);
	}
	
	$evtStmt->close();

	if(isset($_GET["t"])) {
		$t = preg_replace("/\D/", "", $_GET["t"]); // only want numbers
		$tmpEvents = $events;
		foreach($tmpEvents AS $event) {
			if($t == $event["id"]) {
				$events[0] = $event;
				break;
			}
		}
	}
	
	if(isset($_GET["e"])) {
		$e = preg_replace("/[^0-9\-]/", "", $_GET["e"]);
		$tmpSessions = $sessions;
		$sessions = array();
		foreach($tmpSessions AS $session) {
			if($session['eventID'] == $e) $sessions[] = $session;
		}
	}
	
	if(isset($_GET["s"])) {
		$tmpSessions = $sessions;
		$sesID = preg_replace("/\D/", "", $_GET["s"]);
		foreach($tmpSessions AS $session) {
			if($session['id'] == $sesID) {
				$sessions[0] = $session;
				break;
			}
		}
	}

	if(isset($_GET["s"]) || isset($_GET["e"])) {
?>
    <style type="text/css">
		body {
			font-family: Arial,Helvetica;
		}
    </style>
	<table border="0" align="center" width="100%" style="border-spacing: 0; border-collapse: collapse; border: solid 1px #000000;" cellpadding="5">
		<tr>
			<td style="border: solid 1px #000000;">Name</td>
			<td style="border: solid 1px #000000;">Event</td>
			<td style="border: solid 1px #000000;">Title</td>
			<td style="border: solid 1px #000000;">Date</td>
			<td style="border: solid 1px #000000;">Time</td>
		</tr>
<?php
		foreach($sessions AS $session) {
			$curDate = $session["date"];
			$tmpDate = explode("-",$curDate);
			$tmpStamp = mktime(0,0,0,$tmpDate[1],$tmpDate[2],$tmpDate[0]);
			$dateStr = date("l, F j");

			$tmpTime = explode("-",$session["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);		
			$tmpSMinutes = $tmpStart[1];
			$sStamp = mktime($tmpSHour, $tmpSMinutes, 0, $tmpDate[1], $tmpDate[2], $tmpDate[0]);

			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			$tmpEMinutes = $tmpEnd[1];
			$eStamp = mktime($tmpEHour, $tmpEMinutes, 0, $tmpDate[1], $tmpDate[2], $tmpDate[0]);
		
			$dateStr = date("l, F j", $sStamp);
			$timeStr = date("g:i A", $sStamp)." - ".date("g:i A", $eStamp);		

			$pArr = $session["presentations"];
			for($j = 0; $j < count($pArr); $j++) {
				if(count($pArr[$j]) > 0) {
					if(isset($pArr[$j]["presenters"])) {
						$prArr = $pArr[$j]["presenters"];
						$presStr = "";
						for($k = 0; $k < count($prArr); $k++) {
							if($prArr[$k]['certificate'] > 0) {
?>
		<tr>
			<td style="border: solid 1px #000000;"><?php echo stripslashes($prArr[$k]["first_name"]).' '.stripslashes($prArr[$k]["last_name"]); ?></td>
			<td style="border: solid 1px #000000;"><?php echo stripslashes($session["title"]); ?></td>
			<td style="border: solid 1px #000000;"><?php echo stripslashes($pArr[$j]["title"]); ?></td>
			<td style="border: solid 1px #000000;"><?php echo $dateStr; ?></td>
			<td style="border: solid 1px #000000;"><?php echo $timeStr; ?></td>
		</tr>
<?php
							}
						}
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
			var url = 'print_cert_tables.php?s=' + n;
			window.location.href = url;
		}
	</script>
		<p align="left">Click on a session to print the table for that particular session.</p>
<?php
	for($e = 0; $e < count($events); $e++) {
		ob_start();
		$rN = 0;
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["eventID"] == $events[$e]['id']) { //list this session
				if($rN % 2 == 0) $rowClass = 'sList_rowEven';
				else $rowClass = 'sList_rowOdd';
			
				//get the number of presentations scheduled for this session
				$pCount = 0;
				foreach($sessions[$i]['presentations'] AS $pArr) {
					foreach($pArr['presenters'] AS $prArr) {
						if($prArr['certificate'] > 0) $pCount++;
					}
				}
				
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
			<td colspan="4" valign="middle"><?=$events[$e]['event']?> (Total #: <?=$rN?>) <a href="print_cert_tables.php?e=<?php echo $events[$e]['id']; ?>" style="border: solid 1px #000000; background-color: #CCCCCC; color: #000000; padding: 5px; border-radius: 5px; float: right;">Print ALL certs for this event</a></td>
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
<?php
	}

	include "adminBottom.php";
?>