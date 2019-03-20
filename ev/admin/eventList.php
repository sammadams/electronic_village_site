<?php
	//eventList.php - allows a user to see events in the database
	//accessible only to admins
	
	include_once "login.php";
	
	if(strpos($_SESSION['user_role'],"admin") === false) { //reviewers don't have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$evtRes = $db->query("SELECT * FROM events");
	$events = array();
	while($evtRow = $evtRes->fetch_assoc()) {
		$events[] = $evtRow;
	}
	
	$topTitle = "Event List";
		
	include "adminTop.php";
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_rowEven {
			background-color: #FFFFFF;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.pList_rowOdd {
			background-color: #CCCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
				
		td.pList_highlighted {
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
			var rEl = document.getElementById('event' + e + '_row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) cEl.className = 'pList_highlighted';
				else if(n == 0) {
					if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
					else cEl.className = 'pList_rowOdd';
				}
			}
		}
		
		function editEvent(n) {
			window.location.href = 'editEvent.php?id=' + n;
		}
	</script>
	<p align="center"><input type="button" value="Add Event" onClick="window.location.href='addEvent.php'" /></p>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<th class="pList" width="70">Event ID</td>
			<th class="pList">Event Name</td>
			<th class="pList" style="text-align: center;">Included in Regular CFP?</th>
			<th class="pList" style="text-align: center;">Active Event?</th>
		</tr>
<?php
	
	$rN = 0;
	for($i = 0; $i < count($events); $i++) {
		if($rN % 2 == 0) $rowClass = 'pList_rowEven';
		else $rowClass = 'pList_rowOdd';
?>
		<tr id="event<?php echo $i; ?>_row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" onMouseOver="highlightRow('<?php echo $i; ?>','<?php echo $rN; ?>', 1)" onMouseOut="highlightRow('<?php echo $i; ?>','<?php echo $rN; ?>', 0)" onClick="editEvent('<?php echo $events[$i]['id']; ?>')"><?php echo $events[$i]['id']; ?></td>
			<td class="<?php echo $rowClass; ?>" onMouseOver="highlightRow('<?php echo $i; ?>','<?php echo $rN; ?>', 1)" onMouseOut="highlightRow('<?php echo $i; ?>','<?php echo $rN; ?>', 0)" onClick="editEvent('<?php echo $events[$i]['id']; ?>')"><?php echo $events[$i]['event']; ?></td>
			<td class="<?php echo $rowClass; ?>" style="text-align: center;" onMouseOver="highlightRow('<?php echo $i; ?>','<?php echo $rN; ?>', 1)" onMouseOut="highlightRow('<?php echo $i; ?>','<?php echo $rN; ?>', 0)" onClick="editEvent('<?php echo $events[$i]['id']; ?>')"><?php if($events[$i]['getsProposals'] == 'Y') { ?><img src="green_check.png" width="20" height="20" /><?php } else { ?><img src="red_x.png" width="20" height="20" /><?php } ?></td>
			<td class="<?php echo $rowClass; ?>" style="text-align: center;" onMouseOver="highlightRow('<?php echo $i; ?>','<?php echo $rN; ?>', 1)" onMouseOut="highlightRow('<?php echo $i; ?>','<?php echo $rN; ?>', 0)" onClick="editEvent('<?php echo $events[$i]['id']; ?>')"><?php if($events[$i]['isActive'] == 'Y') { ?><img src="green_check.png" width="20" height="20" /><?php } else { ?><img src="red_x.png" width="20" height="20" /><?php } ?></td>
		</tr>
<?php										
		$rN++;
	}
?>
	</table><br /><br />
<?php
	include "adminBottom.php";
?>