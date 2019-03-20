<?php
	//listProps.php -- lists the proposals in the database
	//accessible only to admin users
	//includes views for chair, admin, and event leads
	
	$topTitle = "Proposals List";
	include "login.php";
	
	//We need to count and do other things to the data before we display the rows, so we need to put the
	//data into an array for use later.
	if(strpos($_SESSION['user_role'],"lead_") !== false) { //an event lead is viewing the page
		$pStmt = $db->prepare("SELECT `id`, `title`, `contact`, `type` FROM `proposals` WHERE `type` = ?");

		if(strpos($_SESSION['user_role'],"_fairs") !== false) $eType = 'Technology Fairs';
		else if(strpos($_SESSION['user_role'],"_mini") !== false) $eType = 'Mini-Workshops';
		else if(strpos($_SESSION['user_role'],"_ds") !== false) $eType = 'Developers Showcase';
		else if(strpos($_SESSION['user_role'],"_mae") !== false) $eType = 'Mobile Apps for Education Showcase';
		else if(strpos($_SESSION['user_role'],"_cotf") !== false) $eType = 'Classroom of the Future';
		
		$pStmt->bind_param('s', $eType);
	} else {
		$pStmt = $db->prepare("SELECT `id`, `title`, `contact`, `type` FROM `proposals` WHERE 1");
	}
	
	$pStmt->execute();
	$pStmt->bind_result($id, $title, $contact, $type);
	
	$results = array();
	while($pStmt->fetch()) {
		$results[] = array(
			"id" => $id,
			"title" => $title,
			"contact" => $contact,
			"type" => $type
		);
	}
	
	include "adminTop.php";
	if(strpos($_SESSION['user_role'],"lead_") === false) {
?>
	<h3 align="center">Total: <?php echo count($results); ?></h3>
<?php
	}
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
		
		function viewProp(n) {
			window.location.href = 'viewProp.php?id=' + n;
		}
	</script>
<?php
	if(strpos($_SESSION['user_role'],"lead_") !== false) { //an event lead is viewing the list
		$tmpEvents = $events;
		for($i = 0; $i < count($tmpEvents); $i++) {
			if($tmpEvents[$i]['event'] == $eType) $events = $tmpEvents[$i];
			break;
		}		
	}
	
	for($e = 0; $e < count($events); $e++) {
		ob_start();
		$rN = 0;
		for($i = 0; $i < count($results); $i++) {
			if($results[$i]["type"] == $events[$e]['event']) { //list this proposal
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
?>
		<tr id="event<?php echo $e; ?>_row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" style="text-align: right" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $results[$i]['id']; ?>')"><?php echo($rN + 1); ?></td>
			<td class="<?php echo $rowClass; ?>" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $results[$i]['id']; ?>')"><?php echo $results[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="viewProp('<?php echo $results[$i]['id']; ?>')"><?php echo $results[$i]['contact']; ?></td>
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
			<td colspan="3"><?php echo $events[$e]['event']; ?> (Total #: <?php echo $rN; ?>)</td>
		</tr>
		<tr>
			<th class="pList">ID #</td>
			<th class="pList">Title</td>
			<th class="pList">Author</td>
		</tr>
<?php
		echo $rows;
?>
	</table><br /><br />
<?php
	}
	
	include "adminBottom.php";
?>