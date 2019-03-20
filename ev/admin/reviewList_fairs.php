<?php
	//reviewList_fairs.php -- lists the proposals in the database
	//accessible only to reviewers (technology fairs)
	
	$topTitle = "Reviewer List (Technology Fairs)";
	include "login.php";
	
	if(strpos($_SESSION['user_role'],"reviewer_") !== false || strpos($_SESSION['user_role'],"lead_") !== false) {
		if(strpos($_SESSION['user_role'],"_fairs") === false) {
			include "adminTop.php";
?>
	<h3 align="center">You do not permission to access this page!</h3>
<?php
			include "adminBottom.php";
			exit();
		}
	}

	//We need to count and do other things to the data before we display the rows, so we need to put the
	//data into an array for use later.
	$pStmt = $db->prepare("SELECT `id`, `title`, `contact`, `type` FROM `proposals` WHERE `type` = ?");
	$eType = 'Technology Fairs';
	$pStmt->bind_param('s', $eType);
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
		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
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
	ob_start();
	$rN = 0;
	for($i = 0; $i < count($results); $i++) {
		if($rN % 2 == 0) $rowClass = 'pList_rowEven';
		else $rowClass = 'pList_rowOdd';
?>
		<tr id="row<?=$rN?>">
			<td class="<?=$rowClass?>" width="50" style="text-align: right" onMouseOver="highlightRow('<?=$rN?>',1)" onMouseOut="highlightRow('<?=$rN?>',0)" onClick="viewProp('<?=$results[$i]['id']?>')"><?=$results[$i]['id']?></td>
			<td class="<?=$rowClass?>" onMouseOver="highlightRow('<?=$rN?>',1)" onMouseOut="highlightRow('<?=$rN?>',0)" onClick="viewProp('<?=$results[$i]['id']?>')"><?=$results[$i]['title']?></td>
			<td class="<?=$rowClass?>" width="150" onMouseOver="highlightRow('<?=$rN?>',1)" onMouseOut="highlightRow('<?=$rN?>',0)" onClick="viewProp('<?=$results[$i]['id']?>')"><?=$results[$i]['contact']?></td>
		</tr>
<?php
		$rN++;
	}
		
	$rows = ob_get_contents();
	ob_end_clean();
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="3">Technology Fairs (Total #: <?=$rN?>)</td>
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
	include "adminBottom.php";
?>