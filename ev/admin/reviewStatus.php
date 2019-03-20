<?php
	//reviewStatus.php - displays the list of reviewers for each event along with the number of proposals assigned and reviews completed
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
	
	$topTitle = "Review Status";
			
	//Get the list of reviewers
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

	//Get review information
	$rStmt = $db->prepare("SELECT `id`,`prop_id`,`reviewer`,`event` FROM `reviews` WHERE 1");
	$rStmt->execute();
	$rStmt->bind_result($rID,$rPropID,$rReviewer,$rEvent);

	//Create an array of reviews
	$reviews = array();
	while($rStmt->fetch()) {
		$reviews[] = array(
			"id" => $rID,
			"prop_id" => $rPropID,
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
	
	/*
		We want to see a list of the events, the number of proposals assigned for each event, and the
		number of reviews completed for each event, along with the average number of reviews per
		proposal. To that end, we will build an array for each event that holds the reviewers, and
		the numbers we need.
	 */
	
	$events = array();
	
	/*
		First, we need to get all the reviewers for an event and create an array for that event
		in the "events" array.
	 */
	
	for($t = 0; $t < count($reviewers); $t++) {
		$thisE = $reviewers[$t]["event"];
		
		//First, see if the event exists in the array
		if(!array_key_exists($thisE, $events)) { //event doesn't exist, so create it
			$events[$thisE] = array('reviewers' => array(), 'assigned' => 0, 'completed' => 0, 'average' => 0);
		}
		
		$thisR = $reviewers[$t]["username"];
		$tmpCount = explode("|", $reviewers[$t]["props"]);
		if($tmpCount[0] == "") $tmpCount = 0;
		else $tmpCount = count($tmpCount);
		for($u = 0; $u < count($users); $u++) {
			if($thisR == $users[$u]['username']) {
				$thisFN = $users[$u]['first_name'];
				$thisLN = $users[$u]['last_name'];
				break;
			}
		}
		
		$events[$thisE]['reviewers'][$thisR] = array('first_name' => $thisFN, 'last_name' => $thisLN, 'rCount' => 0, 'aCount' => $tmpCount, 'reviewed_ids' => array());
		$events[$thisE]['assigned'] += $tmpCount;
	}
	
	/*
		Now, get the number of completed reviews for each reviewer by cycling through the "reviews" array and keeping count.
		
		UPDATE: for some reason, duplicate reviews are entered in the system, making it appear that a reviewer has completed
		more reviews than assigned. To account for this, we will record the proposal ID of each review and ignore any
		duplicate ids.
	 */
	
	for($r = 0; $r < count($reviews); $r++) {
		$thisE = $reviews[$r]["event"];
		$thisR = $reviews[$r]["reviewer"];
		if(array_search($reviews[$r]["prop_id"], $events[$thisE]['reviewers'][$thisR]['reviewed_ids']) === false) {
			$events[$thisE]['reviewers'][$thisR]['rCount']++;
			$events[$thisE]['completed']++;
			$events[$thisE]['reviewers'][$thisR]['reviewed_ids'][] = $reviews[$r]["prop_id"];
		}
	}
	
	/*
	 	Finally, we need to get the assigned, completed, and average values for each event.
	 */

	foreach($events AS $eK => $eV) {
		$events[$eK]['average'] = round(($eV["completed"] / count($eV["reviewers"])), 0);
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
	</script>
<?php
	$eN = 0;
	foreach($events AS $eK => $eV) {		
		if(count($eV['reviewers']) > 0) {
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="4">
				<div style="float: left; font-weight: bold; font-size: 1.2em;"><?=$eK?></div>
				<div style="float: right;">(Assigned: <?=$eV['assigned']?> &nbsp; &nbsp; &nbsp; Completed: <?=$eV['completed']?> &nbsp; &nbsp; &nbsp; Average<sup style="font-size: .5em">*</sup>: <?=$eV['average']?>)</div>
			</td>
		</tr>
		<tr>
			<th class="pList">Name</td>
			<th class="pList">Username</td>
			<th class="pList" style="text-align: center"># Completed</td>
			<th class="pList" style="text-align: center"># Assigned</td>
		</tr>
<?php
			$rN = 0;
			foreach($eV['reviewers'] AS $rK => $rV) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
?>
		<tr id="event<?=$eN?>_row<?=$rN?>">
			<td class="<?=$rowClass?>" onMouseOver="highlightRow('<?=$eN?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$eN?>','<?=$rN?>',0)"><?=$rV['first_name']." ".$rV['last_name']?></td>
			<td class="<?=$rowClass?>" width="200" onMouseOver="highlightRow('<?=$eN?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$eN?>','<?=$rN?>',0)"><?=$rK?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="50" onMouseOver="highlightRow('<?=$eN?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$eN?>','<?=$rN?>',0)"><?=$rV['rCount']?></td>
			<td class="<?=$rowClass?>" style="text-align: center" width="50" onMouseOver="highlightRow('<?=$eN?>','<?=$rN?>',1)" onMouseOut="highlightRow('<?=$eN?>','<?=$rN?>',0)"><?=$rV['aCount']?></td>
		</tr>
<?php
				$rN++;
			}
?>
	</table><br /><br />
<?php
			$eN++;
		}
	}

	include "adminBottom.php";
?>