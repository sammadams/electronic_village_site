<?php
	//assignReviewer.php -- allows a lead to assign proposals to a reviewer to be reviewed
	
	$topTitle = "Assign Reviewers";
	$y = date("Y") + 1;

	include "login.php";
	
	if(isset($_GET["event"])) {
		if($_GET["event"] == "_fairs") $eType = "Technology Fairs";
		else if($_GET["event"] == "_mini") $eType = "Mini-Workshops";
		else if($_GET["event"] == "_ds") $eType = "Developers Showcase";
		else if($_GET["event"] == "_mae") $eType = "Mobile Apps for Education Showcase";
		else if($_GET["event"] == "_cotf") $eType = "Classroom of the Future";
		else if($_GET["event"] == "_ht") $eType = "Hot Topics";
		else if($_GET["event"] == "_grad") $eType = "Graduate Student Research";
		else if($_GET["event"] == "_classics") $eType = "Technology Fair Classics";
	} else {
		if(strpos($_SESSION['user_role'], "_fairs") !== false) $eType = "Technology Fairs";
		else if(strpos($_SESSION['user_role'], "_mini") !== false) $eType = "Mini-Workshops";
		else if(strpos($_SESSION['user_role'], "_ds") !== false) $eType = "Developers Showcase";
		else if(strpos($_SESSION['user_role'], "_mae") !== false) $eType = "Mobile Apps for Education Showcase";
		else if(strpos($_SESSION['user_role'], "_cotf") !== false) $eType = "Classroom of the Future";
		else if(strpos($_SESSION['user_role'], "_ht") !== false) $eType = "Hot Topics";
		else if(strpos($_SESSION['user_role'], "_grad") !== false) $eType = "Graduate Student Research";
		else if(strpos($_SESSION['user_role'], "_classics") !== false) $eType = "Technology Fair Classics";
	}
	
	if(isset($_POST['rReviewer']) && isset($_POST['rAssigned'])) { //save the assignment
		//First, see if the reviewer is already in the reviewers table
		$raStmt = $db->prepare("SELECT * FROM `reviewers` WHERE `username` = ? AND `event` = ?");
		$raStmt->bind_param('ss',$_POST['rReviewer'],$eType);
		$raStmt->execute();
		$raStmt->store_result();
		if($raStmt->num_rows > 0) { //update the current entry to be the new assignments
			$rbStmt = $db->prepare("UPDATE `reviewers` SET `proposals` = ? WHERE `username` = ? AND `event` = ? LIMIT 1");
			$rbStmt->bind_param('sss',$_POST['rAssigned'],$_POST['rReviewer'],$eType);
			if(!$rbStmt->execute()) {
				echo $rbStmt->error;
				exit();
			}
		} else { //insert the reviewer
			$rbStmt = $db->prepare("INSERT INTO `reviewers` (`username`,`event`,`proposals`) VALUES (?,?,?)");
			$rbStmt->bind_param('sss',$_POST['rReviewer'],$eType,$_POST['rAssigned']);
			if(!$rbStmt->execute()) {
				echo $rbStmt->error;
				exit();
			}
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
	
	//Now get a list of proposals for this event
	$pStmt = $db->prepare("SELECT `id`, `title`, `presenters` FROM `proposals` WHERE `type` = ? ORDER BY `id`");
	
	$pStmt->bind_param('s',$eType);
	$pStmt->execute();
	$pStmt->store_result();
	$pStmt->bind_result($pID,$pTitle,$pPres);
	$proposals = array();
	while($pStmt->fetch()) {
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
		
		//Get any reviewers already assigned to this proposal
		$thisReviewers = array();
		for($r = 0; $r < count($reviewers); $r++) {
			if(in_array($pID,$reviewers[$r][2])) { //the proposal id was found assigned to this reviewer
				//Get the full name of the reviewer, not the username because this is what will be displayed
				//to the lead when assigning reviewers (they will see the full name of the reviewer)
				$thisReviewers[] = $reviewers[$r][0]."|".$reviewers[$r][1]."| "; //get the name of the reviewer (not their username)
			}
		}
		
		$proposals[] = array($pID,$pTitle,$thisPresenters,$thisReviewers);
	}
	
	/*
		We want to see how many reviews have been completed for each proposal so we can assign those with less reviews
		to specific reviewers. We will get all the reviews from the database and into an array, and then use that to
		mark which reviews have been done.
	 */
	
	$reviewsStmt = $db->prepare("SELECT id, prop_id, reviewer, event FROM reviews WHERE event = ?");
	$reviewsStmt->bind_param('s', $eType);
	$reviewsStmt->execute();
	$reviewsStmt->bind_result($rvwID, $rvwPropID, $rvwReviewer, $rvwEvent);
	
	$reviews = array();
	while($reviewsStmt->fetch()) {
		$reviews[] = array(
			"id" => $rvwID,
			"prop_id" => $rvwPropID,
			"reviewer" => $rvwReviewer,
			"event" => $rvwEvent
		);
	}
	
	for($i = 0; $i < count($proposals); $i++) {
		$tmpReviewers = $proposals[$i][3];
		for($r = 0; $r < count($tmpReviewers); $r++) {
			$tmpR = explode("|", $tmpReviewers[$r]);
			$thisR = $tmpR[0]; //the username of the reviewer
			for($rI = 0; $rI < count($reviews); $rI++) {
				if($reviews[$rI]["reviewer"] == $thisR && $reviews[$rI]["prop_id"] == $proposals[$i][0]) { //found a review by this reviewer for this proposal
					//Make sure the review is not already recorded for this reviewer
					if($tmpR[2] == " ") $proposals[$i][3][$r] = $tmpR[0]."|".$tmpR[1]."|true";
					break;
				}
			}
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
		
		td.pList_selected_highlighted {
			background-color: #006600;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;		
		}
		
		td.pList_selected {
			background-color: #CCFFCC;
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
		
		#header {
			position: fixed;
			top: 0;
			left: 0;
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
	<p align="center">
		Reviewer:
		<select name="propReviewer" id="propReviewer" onChange="showAssigned()">
			<option value="">Please select a reviewer...</option>
<?php
	for($tR = 0; $tR < count($reviewers); $tR++) {
?>
			<option value="<?php echo $reviewers[$tR][0]; ?>"><?php echo $reviewers[$tR][1]; ?></option>
<?php
	}
?>
		</select>
		&nbsp; &nbsp; &nbsp; 
		<input type="button" value="Save Assignments" onClick="saveAssigned()" />
	</p>
	<p align="center">
		Select multiple rows:
		<select name="fromRows" id="fromRows" onChange="selectMultiRows()">
			<option>*</option>
<?php
	for($p = 0; $p < count($proposals); $p++) {
?>
			<option><?php echo ($p + 1); ?></option>
<?php
	}
?>
		</select>
		&nbsp; to &nbsp;
		<select name="toRows" id="toRows" onChange="selectMultiRows()">
			<option>*</option>
<?php
	for($p = 0; $p < count($proposals); $p++) {
?>
			<option><?php echo ($p + 1); ?></option>
<?php
	}
?>
		</select>
	</p>
	<div id="propTableDiv">
		<table id="propTable" border="0" width="800" cellpadding="5">
			<tr>
				<th class="pList">#</th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
				<th class="pList">Reviewers</th>
			</tr>
		</table>
	</div>
	<div id="footer">
		<table width="800">
			<tr>
				<td id="saveMsg" align="center" width="600"><?php if(isset($_POST['rReviewer'])) { ?><span style="color: green">CHANGES SAVED!</span><?php } else { ?>&nbsp;<?php } ?></td>
				<td id="assignedCount" align="center" width="200">&nbsp;</td>
			</tr>
		</table>
	</div>
	<form name="rAssignForm" id="rAssignForm" method="post" action="">
		<input type="hidden" name="rReviewer" id="rReviewer" value="" />
		<input type="hidden" name="rAssigned" id="rAssigned" value="" />
	</form>
	<script type="text/javascript">
		var aCount = 0;
		
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

		proposals[<?php echo $p; ?>]['reviewers'] = new Array();
<?php
		for($r = 0; $r < count($proposals[$p][3]); $r++) {
?>
		proposals[<?php echo $p; ?>]['reviewers'][<?php echo $r; ?>] = '<?php echo addslashes(stripslashes($proposals[$p][3][$r])); ?>';
<?php
		}
?>

		proposals[<?php echo $p; ?>]['selected'] = false;	
<?php
	}
?>

		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			var rN = r - 1;
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(proposals[rN]['selected']) cEl.className = 'pList_selected_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(proposals[rN]['selected']) cEl.className = 'pList_selected';
					else {
						if(parseInt(rN) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function selectMultiRows() {
			var fEl = document.getElementById('fromRows');
			var tEl = document.getElementById('toRows');
			if(fEl.selectedIndex == 0 || tEl.selectedIndex == 0) return false;
			if(document.getElementById('propReviewer').selectedIndex == 0) {
				alert('Please select a reviewer first!');
				fEl.selectedIndex = 0;
				tEl.selectedIndex = 0;
				return false;
			}
			
			//there is a number in both select elements
			var f = parseInt(fEl.options[fEl.selectedIndex].text);
			var t = parseInt(tEl.options[tEl.selectedIndex].text);
			while(f <= t) {
				selectProp(f);
				f++;
			}
			
			listProposals();
		}
		
		function selectProp(n) {
			if(document.getElementById('propReviewer').selectedIndex == 0) {
				alert('Please select a reviewer first!');
				return false;
			}
			
			var rEl = document.getElementById('row' + n);
			var pN = n - 1;
			if(proposals[pN]['selected']) {
				proposals[pN]['selected'] = false;
				aCount--;
				for(i = 0; i < rEl.cells.length; i++) {
					var cEl = rEl.cells[i];
					if(parseInt(pN) % 2 == 0) cEl.classname = 'pList_rowEven';
					else cEl.className = 'pList_rowOdd';
				}
			} else { //not already selected
				proposals[pN]['selected'] = true;
				aCount++;
				for(i = 0; i < rEl.cells.length; i++) {
					var cEl = rEl.cells[i];
					cEl.className = 'pList_selected';
				}
			}
			
			document.getElementById('saveMsg').innerHTML = 'CHANGES NOT SAVED YET!'
			document.getElementById('saveMsg').style.color = 'red';
			document.getElementById('assignedCount').innerHTML = 'Assigned: ' + aCount;
		}
		
		function listProposals() {
			var pTStr = '		<table id="propTable" border="0" width="800" cellpadding="5">\n';
			pTStr += '			<tr>\n';
			pTStr += '				<th class="pList">#</th>\n';
			pTStr += '				<th class="pList">Title</th>\n';
			pTStr += '				<th class="pList" width="150">Presenters</th>\n';
			pTStr += '				<th class="pList" width="150">Reviewers</th>\n';
			pTStr += '			</tr>\n';

			for(p = 0; p < proposals.length; p++) {
				if(proposals[p]['selected']) var tC = 'pList_selected';
				else {
					if(p % 2 == 0) var tC = 'pList_rowEven';
					else var tC = 'pList_rowOdd';
				}
				
				var tRN = parseInt(p) + parseInt(1);
				
				pTStr += '			<tr id="row' + tRN + '">\n';
				pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="selectProp(\'' + tRN + '\')"><input type="hidden" name="pN' + tRN + '" id="pN' + tRN + '" value="' + proposals[p]['id'] + '" />' + proposals[p]['id'] + '</td>\n';
				pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="selectProp(\'' + tRN + '\')">' + proposals[p]['title'] + '</td>\n';
				var pStr = '';
				if(proposals[p]['presenters'].length > 1) {
					pStr += '<ol>';
					for(i = 0; i < proposals[p]['presenters'].length; i++) {
						pStr += '<li>' + proposals[p]['presenters'][i] + '</li>';
					}
					pStr += '</ol>';
				} else pStr += proposals[p]['presenters'][0];

				pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="selectProp(\'' + tRN + '\')">' + pStr + '</td>\n';

				var rStr = '<ol>';
				var minR = 2;
				if(proposals[p]['reviewers'].length > minR) minR = proposals[p]['reviewers'].length;
				for(i = 0; i < minR; i++) {
					if(proposals[p]['reviewers'][i] != undefined && proposals[p]['reviewers'][i] != null) {
						var tmpR = proposals[p]['reviewers'][i].split('|');
						if(tmpR[2] == 'true') rStr += '<li style="background-color: #CCFFCC">' + tmpR[1] + '</li>';
						else rStr += '<li style="background-color: #FFCCCC">' + tmpR[1] + '</li>';
					} else rStr += '<li>&nbsp;</li>';
				}
				rStr += '</ol>';

				pTStr += '				<td class="' + tC + '" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + rStr + '</td>\n';
				pTStr += '			</tr>\n';
			}
			
			pTStr += '		</table>';
			document.getElementById('propTableDiv').innerHTML = pTStr;
		}
		
		function showAssigned() {
			var rEl = document.getElementById('propReviewer');

			if(document.getElementById('saveMsg').innerHTML == 'CHANGES NOT SAVED YET!') {
				var chOK = confirm('You have made changes to the current reviewer\'s assignment, but did not click "Save Assignments".\n\nIf you click "OK", your changes to the current reviewer will NOT be saved. To save your current assignments, click "Cancel" and then click "Save Assignments".');
				if(!chOK) { //do NOT change to the new reviewer
					//reset the select element to be the previously selected reviewer
					var cR = document.getElementById('rReviewer').value;
					
					for(i = 0; i < rEl.options.length; i++) {
						if(rEl.options[i].value == cR) {
							rEl.selectedIndex = i;
							return;
						}
					}
				} else { //change to the new reviewer
					//reset all the selected to false
					for(i = 0; i < proposals.length; i++) {
						proposals[i]['selected'] = false;
					}
				}
			}
			
			var r = rEl.options[rEl.selectedIndex].value;
			document.getElementById('rReviewer').value = r;
			
			aCount = 0;
			for(i = 0; i < proposals.length; i++) {
				for(j = 0; j < proposals[i]['reviewers'].length; j++) {
					var tmpR = proposals[i]['reviewers'][j].split('|');
					if(tmpR[0] == r) {
						proposals[i]['selected'] = true;
						aCount++;
						break;
					}
				}
			}
			
			document.getElementById('assignedCount').innerHTML = 'Assigned: ' + aCount;
			
			listProposals();
		}
		
		function saveAssigned() {
			//Get the selected proposals
			var pStr = '';
			for(i = 0; i < proposals.length; i++) {
				if(proposals[i]['selected']) pStr += proposals[i]['id'] + '|';
			}
			
			//remove the last | char
			pStr = pStr.substring(0,pStr.length - 1);
			document.getElementById('rAssigned').value = pStr;
			document.getElementById('rAssignForm').submit();
		}
		
		window.onload = listProposals();
	</script>
<?php
	include "adminBottom.php";
?>
