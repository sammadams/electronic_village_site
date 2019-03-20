<?php
	//listProps_lead.php -- allows a lead to view all proposals for their event
	
	$topTitle = "Proposals List";
	$y = date("Y") + 1;

	include "login.php";

	if(strpos($_SESSION['user_role'],"_fairs") !== false) $eType = "Technology Fairs";
	else if(strpos($_SESSION['user_role'],"_mini") !== false) $eType = "Mini-Workshops";
	else if(strpos($_SESSION['user_role'],"_ds") !== false) $eType = "Developers Showcase";
	else if(strpos($_SESSION['user_role'],"_mae") !== false) $eType = "Mobile Apps for Education Showcase";
	else if(strpos($_SESSION['user_role'],"_cotf") !== false) $eType = "Classroom of the Future";
	else if(strpos($_SESSION['user_role'],"_ht") !== false) $eType = "Hot Topics";
	else if(strpos($_SESSION['user_role'],"_grad") !== false) $eType = "Graduate Student Research";
	else if(strpos($_SESSION['user_role'],"_classics") !== false) $eType = "Technology Fair Classics";
	
	if(isset($_POST["acceptedProps"]) && $_POST["acceptedProps"] != "") { //update the database for all the accepted proposals
		//Get a count of how many we are updating
		$tmpA = explode("|",$_POST["acceptedProps"]);
		$type = 's';
		$query = "UPDATE `proposals` SET `status` = ? WHERE `id` IN (";
		for($i = 0; $i < count($tmpA); $i++) {
			$query .= "?";
			if($i < (count($tmpA) - 1)) $query .= ",";
			$type .= 's';
		}
		$query .= ")";

		$params = array($type,'accepted');
		for($i = 0; $i < count($tmpA); $i++) {
			$params[] = $tmpA[$i];
		}
		
		$aStmt = $db->prepare($query);
		call_user_func_array(array($aStmt, 'bind_param'), $params);
		if(!$aStmt->execute()) {
			echo $aStmt->error;
			exit();
		}
	}
	
	if(isset($_POST["rejectedProps"]) && $_POST["rejectedProps"] != "") { //update the database for all the rejected proposals
		//Get a count of how many we are updating
		$tmpA = explode("|",$_POST["rejectedProps"]);
		$type = 's';
		$query = "UPDATE `proposals` SET `status` = ? WHERE `id` IN (";
		for($i = 0; $i < count($tmpA); $i++) {
			$query .= "?";
			if($i < (count($tmpA) - 1)) $query .= ",";
			$type .= 's';
		}
		$query .= ")";

		$params = array($type,'rejected');
		for($i = 0; $i < count($tmpA); $i++) {
			$params[] = $tmpA[$i];
		}
		
		$rStmt = $db->prepare($query);
		call_user_func_array(array($rStmt, 'bind_param'), $params);
		if(!$rStmt->execute()) {
			echo $rStmt->error;
			exit();
		}
	}

	if(isset($_POST["newProps"]) && $_POST["newProps"] != "") { //update the database for all the undecided proposals
		//Get a count of how many we are updating
		$tmpA = explode("|",$_POST["newProps"]);
		$type = 's';
		$query = "UPDATE `proposals` SET `status` = ? WHERE `id` IN (";
		for($i = 0; $i < count($tmpA); $i++) {
			$query .= "?";
			if($i < (count($tmpA) - 1)) $query .= ",";
			$type .= 's';
		}
		$query .= ")";

		$params = array($type,'new');
		for($i = 0; $i < count($tmpA); $i++) {
			$params[] = $tmpA[$i];
		}
		
		$nStmt = $db->prepare($query);
		call_user_func_array(array($nStmt, 'bind_param'), $params);
		if(!$nStmt->execute()) {
			echo $nStmt->error;
			exit();
		}
	}
	
	if(isset($_POST["confirmedProps"]) && $_POST["confirmedProps"] != "") { // update the database for all the confirmed proposals
		// Get a count of how many we are updating
		$tmpA = explode("|", $_POST["confirmedProps"]);
		$type = 's';
		$query = "UPDATE `proposals` SET `confirmed` = ? WHERE `id` IN (";
		for($i = 0; $i < count($tmpA); $i++) {
			$query .= "?";
			if($i < (count($tmpA) - 1)) $query .= ",";
			$type .= 's';
		}
		$query .= ")";
		
		$params = array($type,'Y');
		for($i = 0; $i < count($tmpA); $i++) {
			$params[] = $tmpA[$i];
		}
		
		$cStmt = $db->prepare($query);
		call_user_func_array(array($cStmt, 'bind_param'), $params);
		if(!$cStmt->execute()) {
			echo $cStmt->error;
			exit();
		}
	}
	
	if(isset($_POST["unconfirmedProps"]) && $_POST["unconfirmedProps"] != "") { // update the database for all the unconfirmed proposals
		// Get a count of how many we are updating
		$tmpA = explode("|", $_POST["unconfirmedProps"]);
		$type = 's';
		$query = "UPDATE `proposals` SET `confirmed` = ? WHERE `id` IN (";
		for($i = 0; $i < count($tmpA); $i++) {
			$query .= "?";
			if($i < (count($tmpA) - 1)) $query .= ",";
			$type .= 's';
		}
		$query .= ")";
		
		$params = array($type,'N');
		for($i = 0; $i < count($tmpA); $i++) {
			$params[] = $tmpA[$i];
		}
		
		$cStmt = $db->prepare($query);
		call_user_func_array(array($cStmt, 'bind_param'), $params);
		if(!$cStmt->execute()) {
			echo $cStmt->error;
			exit();
		}
	}
	
	if(isset($_POST["unknownProps"]) && $_POST["unknownProps"] != "") { // update the database for all the unknown proposals (confirmed?)
		// Get a count of how many we are updating
		$tmpA = explode("|", $_POST["unknownProps"]);
		$type = 's';
		$query = "UPDATE `proposals` SET `confirmed` = ? WHERE `id` IN (";
		for($i = 0; $i < count($tmpA); $i++) {
			$query .= "?";
			if($i < (count($tmpA) - 1)) $query .= ",";
			$type .= 's';
		}
		$query .= ")";
		
		$params = array($type,'?');
		for($i = 0; $i < count($tmpA); $i++) {
			$params[] = $tmpA[$i];
		}
		
		$cStmt = $db->prepare($query);
		call_user_func_array(array($cStmt, 'bind_param'), $params);
		if(!$cStmt->execute()) {
			echo $cStmt->error;
			exit();
		}
	}
			
	//First, get a list of all the reviewers for this event
	$rStmt = $db->prepare("SELECT `username`, `first_name`, `last_name` FROM `users` WHERE `role` LIKE ?");
	
	//If the user is a lead, we can just look for reviewers from their event
	if(strpos($_SESSION['user_role'],"lead_") !== false) $rRole = str_replace("lead_","reviewer_",$_SESSION['user_role']);
	else {
		//the event type is specified in the URL
		$rRole = "reviewer".strip_tags($_GET["event"]);
	}
	
	$param = "%".$rRole."%";
	
	$rStmt->bind_param('s',$param);
	$rStmt->execute();
	$rStmt->bind_result($user,$firstName,$lastName);
	$reviewers = array();
	while($rStmt->fetch()) {
		$reviewers[] = array($user,$firstName." ".$lastName,array());
	}
	
	//Now get the proposals assigned to each reviewer
	$rpStmt = $db->prepare("SELECT `username`, `proposals` FROM `reviewers` WHERE `event` = ?");
	$rpStmt->bind_param('s',$eType);
	$rpStmt->execute();
	$rpStmt->bind_result($rUser,$rProps);
	while($rpStmt->fetch()) {
		for($r = 0; $r < count($reviewers); $r++) {
			if($reviewers[$r][0] == $rUser) {
				$reviewers[$r][2] = explode("|",$rProps);
				break;
			}
		}
	}
	
	//Now, get a list of presenters for this event
	//Rather than make repeated calls to the DB for each proposal to find the names of the presenters,
	//we'll get them all in an array and then search that as needed
	$prResult = $db->query("SELECT `id`,`First Name`,`Last Name` FROM presenters WHERE 1");
	$presenters = array();
	while($prRow = $prResult->fetch_assoc()) {
		$presenters[] = array($prRow['id'],$prRow['First Name']." ".$prRow['Last Name']);
	}
	
	//Now, get a list of the reviews for each proposal
	$rsStmt = $db->prepare("SELECT `id`,`prop_id`,`reviewer`,`review`,`comments` FROM `reviews` WHERE `event` = ?");
	$rsStmt->bind_param('s',$eType);
	$rsStmt->execute();
	$rsStmt->bind_result($rID,$rPropID,$rReviewer,$rReview,$rComments);
	$reviews = array();
	while($rsStmt->fetch()) {
		$reviews[] = array($rID,$rPropID,$rReviewer,$rReview,$rComments);
	}
	
	//Now get a list of proposals for this event
	$pStmt = $db->prepare("SELECT `id`, `title`, `presenters`, `status`, `confirmed` FROM `proposals` WHERE `type` = ? ORDER BY `id`");
	
	$pStmt->bind_param('s',$eType);
	$pStmt->execute();
	$pStmt->bind_result($pID,$pTitle,$pPres,$pStatus,$pConfirmed);
	$proposals = array();
	while($pStmt->fetch()) {
		if($pStatus == "withdrawn") continue; //skip proposals listed as withdrawn
		//Get the names of all the presenters for this proposal
		$tmpPres = explode("|",$pPres);
		$thisPresenters = array();
		for($t = 0; $t < count($tmpPres); $t++) {
			for($pr = 0; $pr < count($presenters); $pr++) {
				if($presenters[$pr][0] == $tmpPres[$t]) { //ids match
					$thisPresenters[] = $presenters[$pr][1];
					break;
				}
			}
		}
		
		//Get any reviews for this proposal
		$thisReviews = array();
		for($r = 0; $r < count($reviews); $r++) {
			if($pID == $reviews[$r][1]) { //the proposal id was found for this review				
				$thisReviews[] = $reviews[$r][3];
			}
		}
		
		//Get the score for this proposal
		$thisScore = 0;
		for($r = 0; $r < count($thisReviews); $r++) {
			if($eType == "Technology Fairs") {
				$rTotal = 0;
				$rCount = 0;
				for($i = 0; $i < count($thisReviews); $i++) {
					$tmpR = explode("#",$thisReviews[$i]);
					$rTotal = $rTotal + $tmpR[0];
					$rCount++;
				}
				
				if($rCount == 0) $thisScore = 0;
				else $thisScore = round(($rTotal/$rCount),2);
			} else if($eType == "Mini-Workshops") {
				$rTotal = array(0,0,0,0,0,0);
				$rCount = array(0,0,0,0,0,0);
				for($i = 0; $i < count($thisReviews); $i++) {
					$tmpR = explode("#",$thisReviews[$i]);
					$tmpS = explode("|",$tmpR[0]);
					$rCount++;
					for($j = 0; $j < count($tmpS); $j++) {
						$rTotal[$j] = $rTotal[$j] + $tmpS[$j];
						$rCount[$j]++;
					}
				}
				
				$rScore = array();
				for($k = 0; $k < count($rTotal); $k++) {
					if($rCount[$k] == 0) $rScore[$k] = 0;
					else $rScore[$k] = round(($rTotal[$k]/$rCount[$k]),2);
				}
				
				$thisScore = $rScore[5];
			} else if($eType == "Developers Showcase") {
				$thisScore = 0;
			} else if($eType == "Mobile Apps for Education Showcase") {
				$rTotal = array(0,0,0,0,0);
				$rCount = array(0,0,0,0,0);
				for($i = 0; $i < count($thisReviews); $i++) {
					$tmpS = explode("|",$thisReviews[$i]);
					for($j = 0; $j < count($tmpS); $j++) {
						$rTotal[$j] = $rTotal[$j] + $tmpS[$j];
						$rCount[$j]++;
					}
				}
				
				$rScore = array();
				for($k = 0; $k < count($rTotal); $k++) {
					if($rCount[$k] == 0) $rScore[$k] = 0;
					else $rScore[$k] = round(($rTotal[$k]/$rCount[$k]),2);
				}
				
				$thisScore = $rScore[4];
			} else if($eType == "Classroom of the Future") {
				$rTotal = array(0,0,0,0,0);
				$rCount = array(0,0,0,0,0);
				for($i = 0; $i < count($thisReviews); $i++) {
					$tmpS = explode("|",$thisReviews[$i]);
					for($j = 0; $j < count($tmpS); $j++) {
						$rTotal[$j] = $rTotal[$j] + $tmpS[$j];
						$rCount[$j]++;
					}
				}
				
				$rScore = array();
				for($k = 0; $k < count($rTotal); $k++) {
					if($rCount[$k] == 0) $rScore[$k] = 0;
					else $rScore[$k] = round(($rTotal[$k]/$rCount[$k]),2);
				}

				$thisScore = $rScore[4];
			} else if($eType == "Hot Topics") {
				$rTotal = array(0,0,0,0,0);
				$rCount = array(0,0,0,0,0);
				for($i = 0; $i < count($thisReviews); $i++) {
					$tmpS = explode("|",$thisReviews[$i]);
					for($j = 0; $j < count($tmpS); $j++) {
						$rTotal[$j] = $rTotal[$j] + $tmpS[$j];
						$rCount[$j]++;
					}
				}
				
				$rScore = array();
				for($k = 0; $k < count($rTotal); $k++) {
					if($rCount[$k] == 0) $rScore[$k] = 0;
					else $rScore[$k] = round(($rTotal[$k]/$rCount[$k]),2);
				}

				$thisScore = $rScore[4];
			} else if($eType == "Graduate Student Research") {
				$rTotal = array(0,0,0,0,0);
				$rCount = array(0,0,0,0,0);
				for($i = 0; $i < count($thisReviews); $i++) {
					$tmpS = explode("|",$thisReviews[$i]);
					for($j = 0; $j < count($tmpS); $j++) {
						$rTotal[$j] = $rTotal[$j] + $tmpS[$j];
						$rCount[$j]++;
					}
				}
				
				$rScore = array();
				for($k = 0; $k < count($rTotal); $k++) {
					if($rCount[$k] == 0) $rScore[$k] = 0;
					else $rScore[$k] = round(($rTotal[$k]/$rCount[$k]),2);
				}

				$thisScore = $rScore[4];
			} else if($eType == "Technology Fair Classics") {
				$rTotal = array(0,0,0,0,0);
				$rCount = array(0,0,0,0,0);
				for($i = 0; $i < count($thisReviews); $i++) {
					$tmpS = explode("|",$thisReviews[$i]);
					for($j = 0; $j < count($tmpS); $j++) {
						$rTotal[$j] = $rTotal[$j] + $tmpS[$j];
						$rCount[$j]++;
					}
				}
				
				$rScore = array();
				for($k = 0; $k < count($rTotal); $k++) {
					if($rCount[$k] == 0) $rScore[$k] = 0;
					else $rScore[$k] = round(($rTotal[$k]/$rCount[$k]),2);
				}

				$thisScore = $rScore[4];
			}
		}
		
		
		$proposals[] = array($pID,$pTitle,$thisPresenters,$thisReviews,$thisScore,$pStatus,$pConfirmed);
	}
	
	include "adminTop.php";
?>
	<style type="text/css">
		th.pList, th.pList_sort {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			cursor: default;
		}
		
		th.pList_sort:hover {
			color: #000000;
			background-color: #FFFFFF;
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
		
		td.pList_accepted_highlighted {
			background-color: #006600;
			color: #FFFFFF;
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
		
		td.pList_accepted {
			background-color: #CCFFCC;
			color: #000000;
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
		
		ol {
			padding-left: 18;
		}
		
		div.header {
			position: fixed;
			top: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
		}
		
		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
		
		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
		}
		
		#saveMsg {
			font-weigth: bold;
			color: red;
			font-size: 16pt;
			text-align: center;
		}
	</style>
	<div id="headerDiv">
		<p align="center" id="BtnPara">
			<input type="button" value="Accept / Reject Proposals" onClick="acceptRejectProps()" />
			&nbsp; &nbsp; &nbsp; &nbsp;
			<input type="button" value="Confirm Proposals" onClick="confirmProps()" />
		</p>
		<p align="center"><strong>Total:</strong> <span id="totalPropSpan">0</span> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <strong>Accepted:</strong> <span id="acceptedPropSpan" style="width:30px">0</span> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <strong>Rejected:</strong> <span id="rejectedPropSpan" style="width:30px">0</span> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <strong>Undecided:</strong> <span id="newPropSpan" style="width:30px">0</span></p>
	</div>
	<div id="propTableDiv">
		<table id="propTable" border="0" width="800" cellpadding="5">
			<tr>
				<th class="pList">#</th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
				<th class="pList">Reviews</th>
				<th class="pList">Status</th>
				<th class="pList">Confirmed</th>
			</tr>
		</table>
	</div>
	<div id="footer">
		<p align="center"><span id="saveMsg">&nbsp;</span> &nbsp; &nbsp; &nbsp; &nbsp; <span id="saveBtn">&nbsp;</span></p>
	</div>
	<form name="acForm" id="acForm" method="post" action="">
		<input type="hidden" name="acceptedProps" id="acceptedProps" value="" />
		<input type="hidden" name="rejectedProps" id="rejectedProps" value="" />
		<input type="hidden" name="newProps" id="newProps" value="" />
	</form>
	<form name="cfForm" id="cfForm" method="post" action="">
		<input type="hidden" name="confirmedProps" id="confirmedProps" value="" />
		<input type="hidden" name="unconfirmedProps" id="unconfirmedProps" value="" />
		<input type="hidden" name="unknownProps" id="unknownProps" value="" />
	</form>
	<script type="text/javascript">
		var setAcceptReject = false;
		var setConfirm = false;
		var notSaved = false;
		
		var proposals = new Array();
<?php
	for($p = 0; $p < count($proposals); $p++) {
?>

		proposals[<?php echo $p; ?>] = new Array();
		proposals[<?php echo $p; ?>]['id'] = '<?php echo $proposals[$p][0]; ?>';
		proposals[<?php echo $p; ?>]['title'] = '<?php echo addslashes(stripslashes($proposals[$p][1])); ?>';
		
		proposals[<?php echo $p; ?>]['presenters'] = new Array();
<?php
		for($a = 0; $a < count($proposals[$p][2]); $a++) {
?>
		proposals[<?php echo $p; ?>]['presenters'][<?php echo $a; ?>] = '<?php echo addslashes(stripslashes($proposals[$p][2][$a])); ?>';
<?php
		}
?>

		proposals[<?php echo $p; ?>]['reviews'] = new Array();
<?php
		for($r = 0; $r < count($proposals[$p][3]); $r++) {
?>
		proposals[<?php echo $p; ?>]['reviews'][<?php echo $r; ?>] = '<?php echo addslashes(stripslashes($proposals[$p][3][$r])); ?>';
<?php
		}
?>
		proposals[<?php echo $p; ?>]['score'] = <?php echo $proposals[$p][4]; ?>;
		proposals[<?php echo $p; ?>]['status'] = '<?php echo $proposals[$p][5]; ?>';
		proposals[<?php echo $p; ?>]['confirmed'] = '<?php echo $proposals[$p][6]; ?>';
<?php
	}
?>

		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			var rN = r - 1;
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
						if(parseInt(rN) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function sortProps(t) {
			if(t == 'title') {
				proposals.sort(function(a,b) {
					if(a['title'] > b['title']) return 1;
					if(a['title'] < b['title']) return -1;
					return 0;
				});
			} else if(t == 'score') {
		  		proposals.sort(function(a,b) {
		  			return b['score'] - a['score'];
		 	 	});
			} else if(t == 'status') {
				// There is a problem sorting by just 2 or 3 values (e.g., 'accepted','rejected','new')
				// So, we will go through the array and get all the proposals of each type into separate
				// arrays. Then we will sort each array by score. Then we will put them back into the 
				// proposals array.
				
				var acceptedProposals = new Array();
				var rejectedProposals = new Array();
				var newProposals = new Array();
				
				for(var i = 0; i < proposals.length; i++) {
					if(proposals[i]['status'] == 'accepted') acceptedProposals.push(proposals[i]);
					else if(proposals[i]['status'] == 'rejected') rejectedProposals.push(proposals[i]);
					else newProposals.push(proposals[i]);
				}
				
				acceptedProposals.sort(function(a,b) {
					return b['score'] - a['score'];
				});
				
				rejectedProposals.sort(function(a,b) {
					return b['score'] - a['score'];
				});
				
				newProposals.sort(function(a,b) {
					return b['score'] - a['score'];
				});
				
				// Now, put the three arrays back together into the proposals array
				var sortedStatus = new Array();
				for(var i = 0; i < acceptedProposals.length; i++) {
					sortedStatus.push(acceptedProposals[i]);
				}
				
				for(var j = 0; j < rejectedProposals.length; j++) {
					sortedStatus.push(rejectedProposals[j]);
				}
				
				for(var k = 0; k < newProposals.length; k++) {
					sortedStatus.push(newProposals[k]);
				}
				
				proposals = sortedStatus;				
			} else if(t == 'presenters') {
				proposals.sort(function(a,b) {
					if(a['presenters'][0] < b['presenters'][0]) return -1;
					if(a['presenters'][0] > b['presenters'][0]) return 1;
					return 0;
				});
			} else if(t == 'confirmed') {
				proposals.sort(function(a,b) {
					if(a['confirmed'] < b['confirmed']) return 1;
					if(a['confirmed'] > b['confirmed']) return -1;
					return 0;
				});
			}
			
			listProposals();
		}
		
		function listProposals() {
			var pTStr = '		<table id="propTable" border="0" width="800" cellpadding="5">\n';
			pTStr += '			<tr>\n';
			pTStr += '				<th class="pList">#</th>\n';
			pTStr += '				<th class="pList_sort" onclick="sortProps(\'title\')">Title</th>\n';
			pTStr += '				<th class="pList_sort" width="250" onclick="sortProps(\'presenters\')">Presenters</th>\n';
			pTStr += '				<th class="pList_sort" style="text-align: center" width="50" onclick="sortProps(\'score\')">Average<br />Score</th>\n';
			pTStr += '				<th class="pList_sort" style="text-align: center" width="100" onclick="sortProps(\'status\')">Status</th>\n';
			pTStr += '				<th class="pList_sort" style="text-align: center" width="100" onclick="sortProps(\'confirmed\')">Confirmed</th>\n';
			pTStr += '			</tr>\n';

			var acceptedCount = 0;
			var rejectedCount = 0;
			var newCount = 0;

			for(p = 0; p < proposals.length; p++) {
				if(proposals[p]['status'] == 'accepted') {
					var tC = 'pList_accepted';
					acceptedCount++;
				} else if(proposals[p]['status'] == 'rejected') {
					var tC = 'pList_rejected';
					rejectedCount++;
				} else {
					newCount++;
					if(p % 2 == 0) var tC = 'pList_rowEven';
					else var tC = 'pList_rowOdd';
				}
				
				var tRN = parseInt(p) + parseInt(1);
				
				pTStr += '			<tr id="row' + tRN + '">\n';
				pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')"><input type="hidden" name="pN' + tRN + '" id="pN' + tRN + '" value="' + proposals[p]['id'] + '" />' + tRN + '</td>\n';
				pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + proposals[p]['title'] + '</td>\n';
				var pStr = '';
				if(proposals[p]['presenters'].length > 1) {
					pStr += '<ol>';
					for(i = 0; i < proposals[p]['presenters'].length; i++) {
						pStr += '<li>' + proposals[p]['presenters'][i] + '</li>';
					}
					pStr += '</ol>';
				} else pStr += proposals[p]['presenters'][0];

				pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + pStr + '</td>\n';
<?php
	if($eType == "Technology Fairs") {
?>
				var rTotal = 0;
				var rCount = 0;
				for(i = 0; i < proposals[p]['reviews'].length; i++) {
					var tmpR = proposals[p]['reviews'][i].split('#');
					rTotal = parseInt(rTotal) + parseInt(tmpR[0]);
					rCount++;
				}
				
				if(rCount == 0) var rScore = '0';
				else var rScore = Math.round((rTotal/rCount) * 100) / 100;
				var rStr = rScore;

				pTStr += '				<td class="' + tC + '" style="width: 100px; text-align: center" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')"># of Reviews:' + rCount + '<br /><br />' + rStr + '</td>\n';
<?php
	} else if($eType == "Mini-Workshops") {
?>
				var rTotal = new Array(0,0,0,0,0,0);
				var rCount = new Array(0,0,0,0,0,0);
				for(i = 0; i < proposals[p]['reviews'].length; i++) {
					var tmpR = proposals[p]['reviews'][i].split('#');
					var tmpS = tmpR[0].split('|');
					for(j = 0; j < tmpS.length; j++) {
						rTotal[j] = parseInt(rTotal[j]) + parseInt(tmpS[j]);
						rCount[j]++;
					}
				}
				
				var rScore = new Array();
				for(k = 0; k < rTotal.length; k++) {
					if(rCount[k] == 0) rScore[k] = '0';
					else rScore[k] = Math.round((rTotal[k]/rCount[k]) * 100) / 100;
				}
							
				var sCount = 0;
				for(s = 0; s < rCount.length; s++) {
					if(sCount < rCount[s]) sCount = rCount[s];
				}
				
				var rStr = '<p style="margin-top:0; margin-bottom:0; border-bottom: solid 1px #555555;"># of Reviews: ' + sCount + '<br /><br /><strong>Total: ' + rScore[5] + '</strong></p><br />';
				rStr += '<p style="margin-top: 0">Style & Content: ' + rScore[0] + '<br />';
				rStr += 'Novelty: ' + rScore[1] + '<br />';
				rStr += 'Practicality: ' + rScore[2] + '<br />';
				rStr += 'Feasibility: ' + rScore[3] + '<br />';
				rStr += 'Pedagogical Soundness: ' + rScore[4] + '</p>';

				pTStr += '				<td class="' + tC + '" style="width: 150px" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + rStr + '</td>\n';
<?php	
	} else if($eType == "Developers Showcase") {
?>
				pTStr += '              <td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">&nbsp;</td>\n';
<?php
	} else if($eType == "Mobile Apps for Education Showcase") {
?>
				var rTotal = new Array(0,0,0,0,0);
				var rCount = new Array(0,0,0,0,0);
				for(i = 0; i < proposals[p]['reviews'].length; i++) {
					var tmpS = proposals[p]['reviews'][i].split('|');
					for(j = 0; j < tmpS.length; j++) {
						rTotal[j] = parseInt(rTotal[j]) + parseInt(tmpS[j]);
						rCount[j]++;
					}
				}
				
				var rScore = new Array();
				for(k = 0; k < rTotal.length; k++) {
					if(rCount[k] == 0) rScore[k] = '0';
					else rScore[k] = Math.round((rTotal[k]/rCount[k]) * 100) / 100;
				}
				
				var sCount = 0;
				for(s = 0; s < rCount.length; s++) {
					if(sCount < rCount[s]) sCount = rCount[s];
				}
				
				var rStr = '<p style="margin-top:0; margin-bottom:0; border-bottom: solid 1px #555555;"># of Reviews: ' + sCount + '<br /><br /><strong>Total: ' + rScore[4] + '</strong></p><br />';
				rStr += '<p style="margin-top: 0">Innovation: ' + rScore[0] + '<br />';
				rStr += 'Usability: ' + rScore[1] + '<br />';
				rStr += 'Format/TIme: ' + rScore[2] + '<br />';
				rStr += 'Abstract Quality: ' + rScore[3] + '<br />';

				pTStr += '				<td class="' + tC + '" style="width: 150px" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + rStr + '</td>\n';
<?php	
	} else if($eType == "Classroom of the Future") {
?>
				var rTotal = new Array(0,0,0,0,0);
				var rCount = new Array(0,0,0,0,0);
				for(i = 0; i < proposals[p]['reviews'].length; i++) {
					var tmpS = proposals[p]['reviews'][i].split('|');
					for(j = 0; j < tmpS.length; j++) {
						rTotal[j] = parseInt(rTotal[j]) + parseInt(tmpS[j]);
						rCount[j]++;
					}
				}
				
				var rScore = new Array();
				for(k = 0; k < rTotal.length; k++) {
					if(rCount[k] == 0) rScore[k] = '0';
					else rScore[k] = Math.round((rTotal[k]/rCount[k]) * 100) / 100;
				}

				var sCount = 0;
				for(s = 0; s < rCount.length; s++) {
					if(sCount < rCount[s]) sCount = rCount[s];
				}
				
				var rStr = '<p style="margin-top:0; margin-bottom:0; border-bottom: solid 1px #555555;"># of Reviews: ' + sCount + '<br /><br /><strong>Total: ' + rScore[4] + '</strong></p><br />';
				rStr += '<p style="margin-top: 0">Innovation: ' + rScore[0] + '<br />';
				rStr += 'Style: ' + rScore[1] + '<br />';
				rStr += 'Context: ' + rScore[2] + '<br />';
				rStr += 'Writing: ' + rScore[3] + '<br />';

				pTStr += '				<td class="' + tC + '" style="width: 150px" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + rStr + '</td>\n';
<?php	
	} else if($eType == "Hot Topics") {
?>
				var rTotal = new Array(0,0,0,0,0);
				var rCount = new Array(0,0,0,0,0);
				for(i = 0; i < proposals[p]['reviews'].length; i++) {
					var tmpS = proposals[p]['reviews'][i].split('|');
					for(j = 0; j < tmpS.length; j++) {
						rTotal[j] = parseInt(rTotal[j]) + parseInt(tmpS[j]);
						rCount[j]++;
					}
				}
				
				var rScore = new Array();
				for(k = 0; k < rTotal.length; k++) {
					if(rCount[k] == 0) rScore[k] = '0';
					else rScore[k] = Math.round((rTotal[k]/rCount[k]) * 100) / 100;
				}

				var sCount = 0;
				for(s = 0; s < rCount.length; s++) {
					if(sCount < rCount[s]) sCount = rCount[s];
				}
				
				var rStr = '<p style="margin-top:0; margin-bottom:0; border-bottom: solid 1px #555555;"># of Reviews: ' + sCount + '<br /><br /><strong>Total: ' + rScore[4] + '</strong></p><br />';
				rStr += '<p style="margin-top: 0">Innovation: ' + rScore[0] + '<br />';
				rStr += 'Context: ' + rScore[1] + '<br />';
				rStr += 'Quality: ' + rScore[2] + '<br />';
				rStr += 'Pedagogy: ' + rScore[3] + '<br />';

				pTStr += '				<td class="' + tC + '" style="width: 150px" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + rStr + '</td>\n';
<?php	
	} else if($eType == "Graduate Student Research") {
?>
				var rTotal = new Array(0,0,0,0,0);
				var rCount = new Array(0,0,0,0,0);
				for(i = 0; i < proposals[p]['reviews'].length; i++) {
					var tmpS = proposals[p]['reviews'][i].split('|');
					for(j = 0; j < tmpS.length; j++) {
						rTotal[j] = parseInt(rTotal[j]) + parseInt(tmpS[j]);
						rCount[j]++;
					}
				}
				
				var rScore = new Array();
				for(k = 0; k < rTotal.length; k++) {
					if(rCount[k] == 0) rScore[k] = '0';
					else rScore[k] = Math.round((rTotal[k]/rCount[k]) * 100) / 100;
				}

				var sCount = 0;
				for(s = 0; s < rCount.length; s++) {
					if(sCount < rCount[s]) sCount = rCount[s];
				}
				
				var rStr = '<p style="margin-top:0; margin-bottom:0; border-bottom: solid 1px #555555;"># of Reviews: ' + sCount + '<br /><br /><strong>Total: ' + rScore[4] + '</strong></p><br />';
				rStr += '<p style="margin-top: 0">Innovation: ' + rScore[0] + '<br />';
				rStr += 'Context: ' + rScore[1] + '<br />';
				rStr += 'Quality: ' + rScore[2] + '<br />';
				rStr += 'Pedagogy: ' + rScore[3] + '<br />';

				pTStr += '				<td class="' + tC + '" style="width: 150px" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + rStr + '</td>\n';
<?php	
	} else if($eType == "Technology Fair Classics") {
?>
				var rTotal = new Array(0,0,0,0,0);
				var rCount = new Array(0,0,0,0,0);
				for(i = 0; i < proposals[p]['reviews'].length; i++) {
					var tmpS = proposals[p]['reviews'][i].split('|');
					for(j = 0; j < tmpS.length; j++) {
						rTotal[j] = parseInt(rTotal[j]) + parseInt(tmpS[j]);
						rCount[j]++;
					}
				}
				
				var rScore = new Array();
				for(k = 0; k < rTotal.length; k++) {
					if(rCount[k] == 0) rScore[k] = '0';
					else rScore[k] = Math.round((rTotal[k]/rCount[k]) * 100) / 100;
				}

				var sCount = 0;
				for(s = 0; s < rCount.length; s++) {
					if(sCount < rCount[s]) sCount = rCount[s];
				}
				
				var rStr = '<p style="margin-top:0; margin-bottom:0; border-bottom: solid 1px #555555;"># of Reviews: ' + sCount + '<br /><br /><strong>Total: ' + rScore[4] + '</strong></p><br />';
				rStr += '<p style="margin-top: 0">Innovation: ' + rScore[0] + '<br />';
				rStr += 'Context: ' + rScore[1] + '<br />';
				rStr += 'Quality: ' + rScore[2] + '<br />';
				rStr += 'Pedagogy: ' + rScore[3] + '<br />';

				pTStr += '				<td class="' + tC + '" style="width: 150px" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + rStr + '</td>\n';
<?php		
	}
?>

				if(setAcceptReject == false) {
					if(proposals[p]['status'] == 'accepted') var statStr = 'Accepted';
					else if(proposals[p]['status'] == 'rejected') var statStr = 'Rejected';
					else if(proposals[p]['status'] == 'new') var statStr = 'Undecided';
					
					pTStr += '				<td class="' + tC + '" style="text-align: center" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + statStr + '</td>\n';
				} else {
					pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)"><input type="radio" name="ac' + tRN + '" id="ac' + tRN + '_accepted" value="accepted" onClick="setStatus(this)"';
					if(proposals[p]['status'] == 'accepted') pTStr += ' checked="true"';
					pTStr += ' /> <span onClick="checkEl(\'ac' + tRN + '_accepted\')">Accepted</span><br /><input type="radio" name="ac' + tRN + '" id="ac' + tRN + '_rejected" value="rejected" onClick="setStatus(this)"'
					if(proposals[p]['status'] == 'rejected') pTStr += ' checked="true"';
					pTStr += ' /> <span onClick="checkEl(\'ac' + tRN + '_rejected\')">Rejected</span><br /><input type="radio" name="ac' + tRN + '" id="ac' + tRN + '_new" value="new" onClick="setStatus(this)"';
					if(proposals[p]['status'] == 'new') pTStr += ' checked="true"';
					pTStr += ' /> <span onClick="checkEl(\'ac' + tRN + '_new\')">Undecided</span></td>\n';
				}
				
				if(setConfirm == false) {
					pTStr += '				<td class="' + tC + '" style="text-align: center; vertical-align: center;" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="setConfirmed(\'' + tRN + '\')">';
					if(proposals[p]['confirmed'] == 'Y') pTStr += '<img src="green_check.png" height="30" width="30" />';
					else if(proposals[p]['confirmed'] == 'N') pTStr += '<img src="red_x.png" height="30" width="30" />';
					else if(proposals[p]['confirmed'] == '?') pTStr += '<img src="q_mark.png" height="30" width="30" />';
					pTStr += '</td>\n';
				} else {
					pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)"><input type="radio" name="cf' + tRN + '" id="cf' + tRN + '_confirmed" value="Y" onClick="setConfirmed(this)"';
					if(proposals[p]['confirmed'] == 'Y') pTStr += ' checked="true"';
					pTStr += ' /> <span onClick="checkEl(\'cf' + tRN + '_confirmed\')">Confirmed</span><br /><input type="radio" name="cf' + tRN + '" id="cf' + tRN + '_unconfirmed" value="N" onClick="setConfirmed(this)"';
					if(proposals[p]['confirmed'] == 'N') pTStr += ' checked="true"';
					pTStr += ' /> <span onClick="checkEl(\'cf' + tRN + '_unconfirmed\')">Unconfirmed</span><br /><input type="radio" name="cf' + tRN + '" id="cf' + tRN + '_unknown" value="?" onClick="setConfirmed(this)"';
					if(proposals[p]['confirmed'] == '?') pTStr += ' checked="true"';
					pTStr += ' /> <span onClick="checkEl(\'cf' + tRN + '_unknown\')">Unknown</span></td>\n';
				}
				
				pTStr += '			</tr>\n';
			}
			
			pTStr += '		</table>';
			document.getElementById('propTableDiv').innerHTML = pTStr;
			document.getElementById('acceptedPropSpan').innerHTML = acceptedCount;
			document.getElementById('rejectedPropSpan').innerHTML = rejectedCount;
			document.getElementById('newPropSpan').innerHTML = newCount;
			document.getElementById('totalPropSpan').innerHTML = proposals.length;
		}
		
		function acceptRejectProps() {
			setAcceptReject = true;
			document.getElementById('BtnPara').innerHTML = '<input type="button" value="Cancel Accept / Reject Proposals" onClick="cancelAcceptReject()" />';
			listProposals();
		}
		
		function cancelAcceptReject() {
			if(notSaved == true) {
				var cancelOK = confirm('You have made changes that have not been saved! If you cancel now, your changes will be lost!\n\nClick "OK" to cancel (and lose your changes). Click "Cancel" to continue accepting/rejecting proposals.');
				if(!cancelOK) return false;
			}
			
			setAcceptReject = false;
			document.getElementById('BtnPara').innerHTML = '<input type="button" value="Accept / Reject Proposals" onClick="acceptRejectProps()" /> &nbsp; &nbsp; &nbsp; &nbsp;	<input type="button" value="Confirm Proposals" onClick="confirmProps()" />';
			document.getElementById('saveMsg').innerHTML = '';
			document.getElementById('saveBtn').innerHTML = '';
			listProposals();
		}
		
		function confirmProps() {
			setConfirm = true;
			document.getElementById('BtnPara').innerHTML = '<input type="button" value="Cancel Confirm Proposals" onClick="cancelConfirm()" />';
			listProposals();
		}
		
		function cancelConfirm() {
			if(notSaved == true) {
				var cancelOK = confirm('You have made changes that have not been saved! If you cancel now, your changes will be lost!\n\nClick "OK" to cancel (and lose your changes). Click "Cancel" to continue confirming proposals.');
				if(!cancelOK) return false;
			}
			
			setConfirm = false;
			document.getElementById('BtnPara').innerHTML = '<input type="button" value="Accept / Reject Proposals" onClick="acceptRejectProps()" /> &nbsp; &nbsp; &nbsp; &nbsp;	<input type="button" value="Confirm Proposals" onClick="confirmProps()" />';
			document.getElementById('saveMsg').innerHTML = '';
			document.getElementById('saveBtn').innerHTML = '';
			listProposals();
		}
		
		function checkEl(elStr) {
			var el = document.getElementById(elStr);
			el.checked = true;
			if(elStr.indexOf('accepted') != -1 || elStr.indexOf('rejected') != -1 || elStr.indexOf('new') != -1) setStatus(el);
			else if(elStr.indexOf('confirmed') != -1 || elStr.indexOf('unconfirmed') != -1 || elStr.indexOf('unknown') != -1) setConfirmed(el);
		}
		
		function setConfirmed(el) {
			var rN = parseInt(el.name.substring(2,el.name.length));
			var n = rN - 1;
			
			// When this function is called, the use is mousing over the cells, so use the highlighted classes.
			// However, we need to know the status, so get the status from the proposals array.
			if(proposals[n]['status'] == 'accepted') {
				var tC = 'pList_accepted_highlighted';
			} else if(proposals[n]['status'] == 'rejected') {
				var tC = 'pList_rejected_highlighted';
			} else if(proposals[n]['status'] == 'new') {
				var tC = 'pList_highlighted';
			}
			
			var rEl = document.getElementById('row' + rN);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				cEl.className = tC;
			}
			
			notSaved = true;
			document.getElementById('saveMsg').innerHTML = 'CHANGES NOT SAVED YET!'
			document.getElementById('saveBtn').innerHTML = '<input type="button" value="Save Changes" onClick="saveConfirmed()" />';
		}

		function setStatus(el) {
			var rN = parseInt(el.name.substring(2,el.name.length));
			var n = rN - 1;
			
			//When this function is called, the user is mousing over the cells, so use the highlighted classes
			if(el.value == 'accepted') {
				var tC = 'pList_accepted_highlighted';
			} else if(el.value == 'rejected') {
				var tC = 'pList_rejected_highlighted';
			} else if(el.value == 'new') {
				var tC = 'pList_highlighted';
			}
			
			var rEl = document.getElementById('row' + rN);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				cEl.className = tC;
			}
			
			//Now, we need to update the counts -- since only one proposal is changed at a time, we just
			//need to look at the previous for this proposal and see what was checked before, and then
			//adjust the count accordingly.
			var aCount = parseInt(document.getElementById('acceptedPropSpan').innerHTML);
			var rCount = parseInt(document.getElementById('rejectedPropSpan').innerHTML);
			var nCount = parseInt(document.getElementById('newPropSpan').innerHTML);
			
			var prevStat = proposals[n]['status'];
			if(prevStat == 'accepted') aCount--;
			else if(prevStat == 'rejected') rCount--;
			else if(prevStat == 'new') nCount--;
			
			if(el.value == 'accepted') aCount++;
			else if(el.value == 'rejected') rCount++;
			else if(el.value == 'new') nCount++;
			
			notSaved = true;
			document.getElementById('saveMsg').innerHTML = 'CHANGES NOT SAVED YET!'
			document.getElementById('saveBtn').innerHTML = '<input type="button" value="Save Changes" onClick="saveAcceptReject()" />';
			document.getElementById('acceptedPropSpan').innerHTML = aCount;
			document.getElementById('rejectedPropSpan').innerHTML = rCount;
			document.getElementById('newPropSpan').innerHTML = nCount;
		}
		
		function saveConfirmed() {
			// We will create a "confirmed" string of ids (separated by |)
			// and an "unconfirmed" string of ids (separated by |)
			var confirmedStr = '';
			var unconfirmedStr = '';
			var unknownStr = '';
			
			for(i = 0; i < proposals.length; i++) {
				var rN = parseInt(i) + parseInt(1);
				if(document.getElementById('cf' + rN + '_confirmed').checked) confirmedStr += proposals[i]['id'] + '|';
				else if(document.getElementById('cf' + rN + '_unconfirmed').checked) unconfirmedStr += proposals[i]['id'] + '|';
				else if(document.getElementById('cf' + rN + '_unknown').checked) unknownStr += proposals[i]['id'] + '|';
			}
			
			// remove the last | char from each string
			confirmedStr = confirmedStr.substring(0,confirmedStr.length - 1);
			unconfirmedStr = unconfirmedStr.substring(0,unconfirmedStr.length - 1);
			unknownStr = unknownStr.substring(0,unknownStr.length - 1);
			
			// update the form and submit
			document.getElementById('confirmedProps').value = confirmedStr;
			document.getElementById('unconfirmedProps').value = unconfirmedStr;
			document.getElementById('unknownProps').value = unknownStr;
			document.getElementById('cfForm').submit();
		}
		
		function saveAcceptReject() {
			//We will create an "accepted" string of id (separated by |)
			//and a "rejected" string of ids (separated by |)
			var acceptedStr = '';
			var rejectedStr = '';
			var newStr = '';
			for(i = 0; i < proposals.length; i++) {
				var rN = parseInt(i) + parseInt(1);
				if(document.getElementById('ac' + rN + '_accepted').checked) acceptedStr += proposals[i]['id'] + '|';
				else if(document.getElementById('ac' + rN + '_rejected').checked) rejectedStr += proposals[i]['id'] + '|';
				else if(document.getElementById('ac' + rN + '_new').checked) newStr += proposals[i]['id'] + '|';
			}
			
			//remove the last | char from each string
			acceptedStr = acceptedStr.substring(0,acceptedStr.length - 1);
			rejectedStr = rejectedStr.substring(0,rejectedStr.length - 1);
			newStr = newStr.substring(0,newStr.length - 1);
			
			//update and submit the form
			document.getElementById('acceptedProps').value = acceptedStr;
			document.getElementById('rejectedProps').value = rejectedStr;
			document.getElementById('newProps').value = newStr;
			document.getElementById('acForm').submit();
		}
		
		function viewProp(rN) {
			//First, get the ID of the proposal
			var pID = document.getElementById('pN' + rN).value;
			window.location.href = 'viewProp.php?id=' + pID;
		}
		
		function checkHeader() {
			var hDiv = document.getElementById('headerDiv');
			var pDiv = document.getElementById('propTableDiv');
			
			var h = hDiv.offsetHeight;
			
			var hRect = hDiv.getBoundingClientRect();
			var pRect = pDiv.getBoundingClientRect();

			if(hRect.top < 0) {
				hDiv.className = 'header';
				pDiv.style.paddingTop = h + 'px';
			} else if(pRect.top > 0) {
				hDiv.className = '';
				pDiv.style.paddingTop = '0px';
			}
		}
				
		window.onload = listProposals();
		window.onscroll = function() {
			checkHeader();
		};
	</script>
<?php
	include "adminBottom.php";
?>
