<?php
	//reviewerList.php -- lists the proposals in the database assigned to the user logged in
	//accessible only to reviewers and other admin users (leads, chairs, admins)
	
	$topTitle = "Review Proposals";
	$y = date("Y") + 1;

	include "login.php";
	
	if(strpos($_SESSION['user_role'],"_fairs")) $eType = "Technology Fairs";
	else if(strpos($_SESSION['user_role'],"_mini")) $eType = "Mini-Workshops";
	else if(strpos($_SESSION['user_role'],"_ds")) $eType = "Developers Showcase";
	else if(strpos($_SESSION['user_role'],"_mae")) $eType = "Mobile Apps for Education Showcase";
	else if(strpos($_SESSION['user_role'],"_cotf")) $eType = "Classroom of the Future";
	else if(strpos($_SESSION['user_role'],"_ht")) $eType = "Hot Topics";
	else if(strpos($_SESSION['user_role'],"_grad")) $eType = "Graduate Student Research";
	else if(strpos($_SESSION['user_role'],"_classics")) $eType = "Technology Fair Classics";
	
	$topTitle = "Review List (".$eType.")";
	
	//The proposals assigned to each reviewer are listed in the 'reviewers' table, as a string with each proposal ID
	//separated by a "|". We need to get the string and then get the proposal information.
	$rStmt = $db->prepare("SELECT `proposals` FROM `reviewers` WHERE `username` = ? AND `event` = ?");	
	$rStmt->bind_param('ss',$_SESSION['user_name'],$eType);	
	$rStmt->execute();
	$rStmt->bind_result($propStr);
	$rStmt->fetch();
	$rStmt->close();
	
	$rArray = explode("|",$propStr); //now we have the ids for each proposal assigned to this reviewer
	$unreviewed = array();
	$reviewed = array();
	for($i = 0; $i < count($rArray); $i++) {
		$pStmt = $db->prepare("SELECT `id`, `title` FROM `proposals` WHERE `id` = ?");
		$pStmt->bind_param('s',$rArray[$i]);
		$pStmt->execute();
		$pStmt->bind_result($pID,$pTitle);
		$pStmt->fetch();
		$pStmt->close();
		
		//Check to see if a reivew has already been saved for this proposal for this reviewer
		$rpStmt = $db->prepare("SELECT `id`,`review` FROM `reviews` WHERE `prop_id` = ? AND `reviewer` = ?");
		$rpStmt->bind_param('ss',$rArray[$i],$_SESSION['user_name']);
		$rpStmt->execute();
		$rpStmt->store_result();

		if($rpStmt->num_rows > 0) { //a review already exists, so put this in the reviewed array
			$rpStmt->bind_result($rID,$pScore);
			$rpStmt->fetch();
			if(strpos($pScore,"|") !== false) { //more than one number is recorded (a score for each category)
				//the total will be the last number
				$tmpScore = explode("|",$pScore);
				$pScore = array_pop($tmpScore);
			}
			$reviewed[] = array($pID,$pTitle,$pScore,$rID);
		} else { //no review exists
			$unreviewed[] = array($pID,$pTitle);
		}
		$rpStmt->close();
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
			var rEl = document.getElementById(e);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) cEl.className = 'pList_highlighted';
				else if(n == 0) {
					if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
					else cEl.className = 'pList_rowOdd';
				}
			}
		}
		
		function viewProp(n,e) {
			var myURL = 'reviewProp.php?id=' + n;
			if(e != null || e != undefined) myURL += '&editID=' + e;
			window.location.href = myURL;
		}
	</script>
<?php
	//Show the unreviewed first
	ob_start();
	$rN = 0;
	for($i = 0; $i < count($unreviewed); $i++) {
		if($rN % 2 == 0) $rowClass = 'pList_rowEven';
		else $rowClass = 'pList_rowOdd';
?>
		<tr id="unreviewed_row<?=$rN?>">
			<td class="<?=$rowClass?>" style="width: 100%" onMouseOver="highlightRow('unreviewed_row<?=$rN?>','<?=$rN?>',1)" onMouseOut="highlightRow('unreviewed_row<?=$rN?>','<?=$rN?>',0)" onClick="viewProp('<?=$unreviewed[$i][0]?>')"><?=$unreviewed[$i][1]?></td>
		</tr>
<?php
		$rN++;
	}
		
	$rows = ob_get_contents();
	ob_end_clean();
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="2">Unreviewed (Total #: <?=$rN?>)</td>
		</tr>
		<tr>
			<th class="pList">Title</td>
		</tr>
<?php
	echo $rows;
?>
	</table><br /><br />
<?php
	//now show the reviewed
	ob_start();
	$rN = 0;
	for($i = 0; $i < count($reviewed); $i++) {
		if($rN % 2 == 0) $rowClass = 'pList_rowEven';
		else $rowClass = 'pList_rowOdd';
?>
		<tr id="reviewed_row<?=$rN?>">
			<td class="<?=$rowClass?>" style="width: 100%" onMouseOver="highlightRow('reviewed_row<?=$rN?>','<?=$rN?>',1)" onMouseOut="highlightRow('reviewed_row<?=$rN?>','<?=$rN?>',0)" onClick="viewProp('<?=$reviewed[$i][0]?>','<?=$reviewed[$i][3]?>')"><?=$reviewed[$i][1]?></td>
			<td class="<?=$rowClass?>" style="width: 100px; text-align: center" onMouseOver="highlightRow('reviewed_row<?=$rN?>','<?=$rN?>',1)" onMouseOut="highlightRow('reviewed_row<?=$rN?>','<?=$rN?>',0)" onClick="viewProp('<?=$reviewed[$i][0]?>','<?=$reviewed[$i][3]?>')"><?=$reviewed[$i][2]?></td>
		</tr>
<?php
		$rN++;
	}
		
	$rows = ob_get_contents();
	ob_end_clean();
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="2">Reviewed (Total #: <?=$rN?>)</td>
		</tr>
		<tr>
			<th class="pList" style="width: 700px">Title</td>
			<th class="pList" style="width: 100px; text-align: center">Score</td>
		</tr>
<?php
	echo $rows;
?>
	</table><br /><br />
<?php
	include "adminBottom.php";
?>