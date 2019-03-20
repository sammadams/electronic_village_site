<?php
	//userList.php - allows a user to see users in the database
	//accessible only to admin users (admins and leads)
	
	include_once "login.php";
	
	if(strpos($_SESSION['user_role'],"reviewer_") !== false) { //reviewers don't have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(strpos($_SESSION['user_role'],"lead_") !== false) {
		if(strpos($_SESSION['user_role'],"_fairs") !== false) $eType = 'Technology Fairs';
		else if(strpos($_SESSION['user_role'],"_mini") !== false) $eType = 'Mini-Workshops';
		else if(strpos($_SESSION['user_role'],"_ds") !== false) $eType = 'Developers Showcase';
		else if(strpos($_SESSION['user_role'],"_mae") !== false) $eType = 'Mobile Apps for Education Showcase';
		else if(strpos($_SESSION['user_role'],"_cotf") !== false) $eType = 'Classroom of the Future';
		else if(strpos($_SESSION['user_role'],"_ht") !== false) $eType = 'Hot Topics';
		else if(strpos($_SESSION['user_role'],"_grad") !== false) $eType = 'Graduate Student Research';
		else if(strpos($_SESSION['user_role'],"_classics") !== false) $eType = 'Technology Fair (Classics)';

		$topTitle = "Reviewer List (".$eType.")";
	} else $topTitle = "User List";
		
	//Get the list of users
	$uStmt = $db->prepare("SELECT `username`,`first_name`,`last_name`,`role` FROM `users` WHERE 1");
	$uStmt->execute();
	$uStmt->bind_result($username,$first_name,$last_name,$role);
	
	//Create an array of the users
	$users = array();
	while($uStmt->fetch()) {
		$users[] = array(
			"username" => $username,
			"first_name" => $first_name,
			"last_name" => $last_name,
			"role" => $role,
			"count" => 0,
			"total" => 0
		);
	}

	if(strpos($_SESSION["user_role"],"lead_") !== false) {
		//Get review information
		$rStmt = $db->prepare("SELECT `id`,`reviewer`,`event` FROM `reviews` WHERE 1");
		$rStmt->execute();
		$rStmt->bind_result($rID,$rReviewer,$rEvent);
	
		//Create an array of reviews
		$reviews = array();
		while($rStmt->fetch()) {
			$reviews[] = array(
				"id" => $rID,
				"reviewer" => $rReviewer,
				"event" => $rEvent
			);
		}
	
		//Get reviewer information
		$tStmt = $db->prepare("SELECT `username`,`event`,`proposals` FROM `reviewers` WHERE 1");
		$tStmt->execute();
		$tStmt->bind_result($tUser,$tEvent,$tProps);
	
		//Create an array of reviews
		$reviewers = array();
		while($tStmt->fetch()) {
			$reviewers[] = array(
				"username" => $tUser,
				"event" => $tEvent,
				"props" => $tProps
			);
		}
	
		for($i = 0; $i < count($users); $i++) {
			/*
				If someone is assigned as a reviewer in more than one event, it pulls the first one, which may
				not be the one we want. We only need the number of reviews if the person viewing the list is a
				lead, so we will only pull the reviewers who are assigned to the event that the lead is viewing.
			 */
			$role = $users[$i]["role"];
			$username = $users[$i]["username"];
		
			$rCount = 0;
			for($r = 0; $r < count($reviews); $r++) {
				if($reviews[$r]["reviewer"] == $username && $reviews[$r]["event"] == $eType) $rCount++;
			}
			
			$pCount = 0;
			for($t = 0; $t < count($reviewers); $t++) {
				if($reviewers[$t]["username"] == $username && $reviewers[$t]["event"] == $eType) {
					if($reviewers[$t]["props"] != "") {
						$tmpCount = explode("|",$reviewers[$t]["props"]);
						$pCount = count($tmpCount);
					}
					
					break;
				}
			}
			
			$users[$i]["count"] = $rCount;
			$users[$i]["total"] = $pCount;
		}
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
			var rEl = document.getElementById('user' + e + '_row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) cEl.className = 'pList_highlighted';
				else if(n == 0) {
					if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
					else cEl.className = 'pList_rowOdd';
				}
			}
		}
		
		function editUser(n) {
			window.location.href = 'editUser.php?u=' + n;
		}
		
		function loginAdmin(n) {
			window.location.href = 'loginAdmin.php?u=' + n;
		}
	</script>
<?php
	if(strpos($_SESSION['user_role'],"lead_") !== false) { //an event lead is viewing the list
?>
	<p align="center"><input type="button" value="Add Reviewer" onClick="window.location.href='addUser.php'" /></p>
<?php

		if(strpos($_SESSION['user_role'],"_fairs") !== false) $uType = "reviewer_fairs";
		else if(strpos($_SESSION['user_role'],"_mini") !== false) $uType = "reviewer_mini";
		else if(strpos($_SESSION['user_role'],"_ds") !== false) $uType = "reviewer_ds";
		else if(strpos($_SESSION['user_role'],"_mae") !== false) $uType = "reviewer_mae";
		else if(strpos($_SESSION['user_role'],"_cotf") !== false) $uType = "reviewer_cotf";
		else if(strpos($_SESSION['user_role'],"_ht") !== false) $uType = "reviewer_ht";
		else if(strpos($_SESSION['user_role'],"_grad") !== false) $uType = "reviewer_grad";
		else if(strpos($_SESSION['user_role'],"_classics") !== false) $uType = "reviewer_classics";

		ob_start();
		$rN = 0;
		for($i = 0; $i < count($users); $i++) {
			if(strpos($users[$i]["role"],$uType) !== false) { //list this user
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
?>
		<tr id="user0_row<?=$rN?>">
			<td class="<?=$rowClass?>" onMouseOver="highlightRow('0','<?=$rN?>',1)" onMouseOut="highlightRow('0','<?=$rN?>',0)" onClick="editUser('<?=$users[$i]['username']?>')"><?=$users[$i]['first_name']." ".$users[$i]['last_name']?></td>
			<td class="<?=$rowClass?>" width="200" onMouseOver="highlightRow('0','<?=$rN?>',1)" onMouseOut="highlightRow('0','<?=$rN?>',0)" onClick="editUser('<?=$users[$i]['username']?>')"><?=$users[$i]['username']?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="50" onMouseOver="highlightRow('0','<?=$rN?>',1)" onMouseOut="highlightRow('0','<?=$rN?>',0)" onClick="editUser('<?=$users[$i]['username']?>')"><?=$users[$i]['count']?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="50" onMouseOver="highlightRow('0','<?=$rN?>',1)" onMouseOut="highlightRow('0','<?=$rN?>',0)" onClick="editUser('<?=$users[$i]['username']?>')"><?=$users[$i]['total']?></td>
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
			<th class="pList">Name</td>
			<th class="pList">Username</td>
			<th class="pList" style="text-align: center"># Completed</td>
			<th class="pList" style="text-align: center"># Assigned</td>
		</tr>
<?php
		echo $rows;
?>
	</table><br /><br />
<?php
	} else {
?>
	<p align="center"><input type="button" value="Add User" onClick="window.location.href='addUser.php'" /></p>
<?php

		if(strpos($_SESSION['user_role'],"chair") !== false) $uTypes = array('lead_fairs','lead_mini','lead_ds','lead_mae','lead_cotf','lead_ht','lead_grad','lead_classics','reviewer_fairs','reviewer_mini','reviewer_ds','reviewer_mae','reviewer_cotf','reviewer_ht','reviewer_grad','reviewer_classics');
		else if(strpos($_SESSION['user_role'],"admin") !== false) $uTypes = array('admin','chair','lead_fairs','lead_mini','lead_ds','lead_mae','lead_cotf','lead_ht','lead_grad','lead_classics','reviewer_fairs','reviewer_mini','reviewer_ds','reviewer_mae','reviewer_cotf','reviewer_ht','reviewer_grad','reviewer_classics');
		
		for($u = 0; $u < count($uTypes); $u++) {
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($users); $i++) {
				if(strpos($users[$i]["role"],$uTypes[$u]) !== false) { //list this user
					if($rN % 2 == 0) $rowClass = 'pList_rowEven';
					else $rowClass = 'pList_rowOdd';
?>
		<tr id="user<?=$u?>_row<?=$rN?>">
			<td class="<?=$rowClass?>" onMouseOver="highlightRow('<?=$u?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$u?>','<?=$rN?>',0)" onClick="editUser('<?=$users[$i]['username']?>')"><?=$users[$i]['first_name']." ".$users[$i]['last_name']?></td>
<?php
					if($_SESSION['user_role'] == "admin") { //the user is logged in as an admin
?>
			<td class="<?=$rowClass?>" width="200" onMouseOver="highlightRow('<?=$u?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$u?>','<?=$rN?>',0)" onClick="loginAdmin('<?=$users[$i]['username']?>')"><?=$users[$i]['username']?></td>
<?php
					} else {
?>
			<td class="<?=$rowClass?>" width="200" onMouseOver="highlightRow('<?=$u?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$u?>','<?=$rN?>',0)" onClick="editUser('<?=$users[$i]['username']?>')"><?=$users[$i]['username']?></td>
<?php
					}
?>
		</tr>
<?php										
					$rN++;
				}
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
			
			if($uTypes[$u] == "reviewer_fairs") $uTitle = "Reviewers (Technology Fairs)";
			if($uTypes[$u] == "reviewer_mini") $uTitle = "Reviewers (Mini-Workshops)";
			if($uTypes[$u] == "reviewer_ds") $uTitle = "Reviewers (Developers Showcase)";
			if($uTypes[$u] == "reviewer_mae") $uTitle = "Reviewers (Mobile Apps for Education Showcase)";
			if($uTypes[$u] == "reviewer_cotf") $uTitle = "Reviewers (Classroom of the Future)";
			if($uTypes[$u] == "reviewer_ht") $uTitle = "Reviewers (Hot Topics)";
			if($uTypes[$u] == "reviewer_grad") $uTitle = "Reviewers (Graduate Student Research)";
			if($uTypes[$u] == "reviewer_classics") $uTitle = "Reviewers (Technology Fair Classics)";
			if($uTypes[$u] == "lead_fairs") $uTitle = "Event Leads (Technology Fairs)";
			if($uTypes[$u] == "lead_mini") $uTitle = "Event Leads (Mini-Workshops)";
			if($uTypes[$u] == "lead_ds") $uTitle = "Event Leads (Developers Showcase)";
			if($uTypes[$u] == "lead_mae") $uTitle = "Event Leads (Mobile Apps for Education Showcase)";
			if($uTypes[$u] == "lead_cotf") $uTitle = "Event Leads (Classroom of the Future)";
			if($uTypes[$u] == "lead_ht") $uTitle = "Event Leads (Hot Topics)";
			if($uTypes[$u] == "lead_grad") $uTitle = "Event Leads (Graduate Student Research)";
			if($uTypes[$u] == "lead_classics") $uTitle = "Event Leads (Technology Fair Classics)";
			if($uTypes[$u] == "chair") $uTitle = "Chairs";
			if($uTypes[$u] == "admin") $uTitle = "Administrators";
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="2"><?=$uTitle?> (Total #: <?=$rN?>)</td>
		</tr>
		<tr>
			<th class="pList">Name</td>
			<th class="pList">Username</td>
		</tr>
<?php			
			echo $rows;
?>
	</table><br /><br />
<?php
		}
	}

	include "adminBottom.php";
?>