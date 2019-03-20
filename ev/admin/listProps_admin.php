<?php
	//listProps.php -- lists the proposals in the database
	//accessible only to admin users
	//includes views for chair and admin users
	
	$topTitle = "Proposals List";
	include "login.php";
	
	if(strpos($_SESSION["user_role"],"lead_") !== false || strpos($_SESSION["user_role"],"reviewer_") !== false) {
		include "adminTop.php";
?>
		<h2 align="center">You do not have permission to access this page!</h2>
		<p align="center"><a href="index.php">Back to Main Menu</a></p>
<?php
		include "adminBottom.php";
		exit();
	}
	
	//We need to count and do other things to the data before we display the rows, so we need to put the
	//data into an array for use later.
	$pStmt = $db->prepare("SELECT `id`, `title`, `contact`, `type`, `status`, `confirmed` FROM `proposals` WHERE 1");
	$pStmt->execute();
	$pStmt->bind_result($id, $title, $contact, $type, $status, $confirmed);
	
	$results = array();
	while($pStmt->fetch()) {
		$results[] = array(
			"id" => $id,
			"title" => $title,
			"contact" => $contact,
			"type" => $type,
			"status" => $status,
			"confirmed" => $confirmed
		);
	}
	
	$pStmt->close();
	
	$cStmt = $db->prepare("SELECT `id`, `title`, `contact`, `confirmed` FROM `classics_proposals` WHERE 1");
	$cStmt->execute();
	$cStmt->bind_result($cID, $cTitle, $cContact, $cConfirmed);
	
	$cResults = array();
	while($cStmt->fetch()) {
		$cResults[] = array(
			"id" => $cID,
			"title" => $cTitle,
			"contact" => $cContact,
			"confirmed" => $cConfirmed
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
		
		td.pList_accepted {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_accepted_highlighted {
			background-color: #006600;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.pList_rejected {
			background-color: #FFCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_rejected_highlighted {
			background-color: #660000;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		img.qMark {
			width: 30px;
			height: 30px;
		}
		
		img.greenCheck {
			width: 20px;
			height: 20px;
		}
		
		img.redX {
			width: 20px;
			height: 20px;
		}
	</style>
	<script type="text/javascript">
		function highlightRow(e,r,n) {
			var rEl = document.getElementById('event' + e + '_row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_accepted') cEl.className = 'pList_accepted_highlighted';
					else if(cEl.className == 'pList_rejected') cEl.className = 'pList_rejected_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_accepted_highlighted') cEl.className = 'pList_accepted';
					else if(cEl.className == 'pList_rejected_highlighted') cEl.className = 'pList_rejected';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function viewProp(n) {
			window.location.href = 'viewProp.php?id=' + n;
		}

		function viewCProp(n) {
			window.location.href = 'editClassics.php?id=' + n;
		}
	</script>
	<p align="center"><strong>Total: </strong><?php echo count($results); ?></p>
<?php
	for($e = 0; $e < count($events); $e++) {
		ob_start();
		$rN = 0;
		if($events[$e]['event'] != "Technology Fair Classics") {
			for($i = 0; $i < count($results); $i++) {
				if($results[$i]["type"] == $events[$e]['event']) { //list this proposal
					if($results[$i]["status"] == "accepted") $rowClass = 'pList_accepted';
					else if($results[$i]["status"] == "rejected") $rowClass = 'pList_rejected';
					else {
						if($rN % 2 == 0) $rowClass = 'pList_rowEven';
						else $rowClass = 'pList_rowOdd';
					}
?>
		<tr id="event<?php echo $e; ?>_row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" style="text-align: right" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $results[$i]['id']; ?>')"><?php echo($rN + 1); ?></td>
			<td class="<?php echo $rowClass; ?>" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $results[$i]['id']; ?>')"><?php echo $results[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $results[$i]['id']; ?>')"><?php echo $results[$i]['contact']; ?></td>
			<td class="<?php echo $rowClass; ?>" style="text-align: center" width="50" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $results[$i]['id']; ?>')"><?php if($results[$i]['confirmed'] == 'Y') { ?><img src="green_check.png" class="greenCheck"/><?php } else if($results[$i]['confirmed'] == 'N') { ?><img src="red_x.png" class="redX" /><?php } else { ?><img src="q_mark.png" class="qMark" /><?php } ?></td>
		</tr>
<?php
					$rN++;
				}
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
			
			if($rN > 0) {
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="3"><?php echo $events[$e]['event']; ?> (Total #: <?php echo $rN; ?>)</td>
		</tr>
		<tr>
			<th class="pList">ID #</th>
			<th class="pList">Title</th>
			<th class="pList">Author</th>
			<th class="pList">Confirmed</th>
		</tr>
<?php
			echo $rows;
?>
	</table><br /><br />
<?php
			}
		} else {
			for($i = 0; $i < count($cResults); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
?>
		<tr id="event<?php echo $e; ?>_row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" style="text-align: right" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewCProp('<?php echo $cResults[$i]['id']; ?>')"><?php echo($rN + 1); ?></td>
			<td class="<?php echo $rowClass; ?>" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewCProp('<?php echo $cResults[$i]['id']; ?>')"><?php echo $cResults[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $cResults[$i]['id']; ?>')"><?php echo $cResults[$i]['contact']; ?></td>
			<td class="<?php echo $rowClass; ?>" style="text-align: center" width="50" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $cResults[$i]['id']; ?>')"><?php if($cResults[$i]['confirmed'] == 'Y') { ?><img src="green_check.png" class="greenCheck" /><?php } else if($cResults[$i]['confirmed'] == 'N') { ?><img src="red_x.png" class="redX" /><?php } else { ?><img src="q_mark.png" class="qMark" /><?php } ?></td>			
		</tr>
<?php
				$rN++;
			}
			
			$rows = ob_get_contents();
			ob_end_clean();
			
			if($rN > 0) {
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="3"><?php echo $events[$e]['event']; ?> (Total #: <?php echo $rN; ?>)</td>
		</tr>
		<tr>
			<th class="pList">ID #</th>
			<th class="pList">Title</th>
			<th class="pList">Author</th>
			<th class="pList">Confirmed</th>
		</tr>
<?php
			echo $rows;
?>
	</table><br /><br />
<?php
			}
		}
	}
	
	include "adminBottom.php";
?>