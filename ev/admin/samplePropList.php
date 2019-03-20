<?php
	//samplePropList.php - allows a user to see sample proposals in the database
	//accessible only to admins
	
	include_once "login.php";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false && strpos($_SESSION['user_role'],"lead_") === false) { //reviewers don't have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$topTitle = "Sample Proposals List";
		
	//Get the list of sample proposals
	$eStmt = $db->prepare("SELECT sp.id AS id, e.event AS event, sp.title AS title, sp.visible AS visible FROM events AS e, sample_proposals AS sp WHERE sp.event = e.id AND e.isActive = 'Y' ORDER BY e.id ASC, sp.title ASC");
	$eStmt->execute();
	$eStmt->bind_result($id,$event,$title,$visible);
	
	//Create an array of the users
	$props = array();
	while($eStmt->fetch()) {
		$props[] = array(
			"id" => $id,
			"event" => $event,
			"title" => $title,
			"visible" => $visible
		);
	}

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
		
		function editProp(n) {
			window.location.href = 'editSampleProp.php?id=' + n;
		}
	</script>
	<p align="center"><input type="button" value="Add Sample Proposal" onClick="window.location.href='addSampleProp.php'" /></p>
<?php
	$curEvent = '';
	$eN = 0;
	foreach($props AS $p) {
		if($curEvent == '') {
			$curEvent = $p['event'];
			$rN = 0;
			ob_start();
		} else if($curEvent != $p['event']) { //new event, so output what we've got already
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<h3 align="center"><?php echo $curEvent; ?></h3>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<th class="pList">Title</th>
			<th class="pList" style="text-align: center; width: 150px;">Visible on site?</th>
		</tr>
<?php
			echo $rows;
?>
	</table><br /><br />
<?php
			$curEvent = $p['event'];
			$rN = 0;
			$eN++;
			ob_start();
		}
		
		if($rN % 2 == 0) $rowClass = 'pList_rowEven';
		else $rowClass = 'pList_rowOdd';
?>
		<tr id="event<?php echo $eN; ?>_row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" style="vertical-align: middle;" onMouseOver="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>', 1)" onMouseOut="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>', 0)" onClick="editProp('<?php echo $p['id']; ?>')"><?php echo $p['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" style="text-align: center;" onMouseOver="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>', 1)" onMouseOut="highlightRow('<?php echo $eN; ?>','<?php echo $rN; ?>', 0)" onClick="editProp('<?php echo $p['id']; ?>')"><?php if($p['visible'] == 'Y') { ?><img src="green_check.png" height="20" width="20" /><?php } else { ?><img src="red_x.png" height="20" width="20" /><?php } ?></td>
		</tr>
<?php
		$rN++;
	}
	
	$rows = ob_get_contents();
	ob_end_clean();
?>
	<h3 align="center"><?php echo $curEvent; ?></h3>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<th class="pList">Title</th>
			<th class="pList" style="text-align: center; width: 150px;">Visible on site?</th>
		</tr>
<?php
			echo $rows;
?>
	</table><br /><br />
<?php
	include "adminBottom.php";
?>