<?php
	//scheduleSession.php - allows a user to edit the presentations in a particular session on the schedule in the database
	//accessible only to leads, chairs, and admin users
	
	include_once "login.php";
	$topTitle = "Schedule Sessions";
	
	if(strpos($_SESSION['user_role'],"reviewer_") !== false) {
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$evtStmt = $db->prepare("SELECT id, event, adminSuffix, propTable FROM events WHERE isActive = 'Y'");
	$evtStmt->execute();
	$evtStmt->bind_result($evtID, $evtEvent, $evtAdminSuffix, $evtPropTable);
	
	$events = array();
	while($evtStmt->fetch()) {
		$events[] = array(
			"id" => $evtID,
			"event" => $evtEvent,
			"adminSuffix" => $evtAdminSuffix,
			"propTable" => $evtPropTable
		);
	}
	
	$evtStmt->close();
	
	if(isset($_POST["scheduledProposals"])) { //the form was submitted
		$scStmt = $db->prepare("UPDATE `sessions` SET `presentations` = ? WHERE `id` = ?");
		$scStmt->bind_param('ss',$_POST["scheduledProposals"],$_POST["scheduledSession"]);
		if(!$scStmt->execute()) {
			echo $scStmt->error;
			exit();
		}
		
		$scStmt->close();
		
		header("Location: scheduleSession.php"); //return to the session list
		exit();
	}
	
	if(isset($_GET["s"])) { //a specific session id was given
		$sesID = strip_tags($_GET["s"]);

		// get the session information
		$thisSession = getSessions($sesID)[0];

		// get the presentations for this event
		$proposals = getProposals($thisSession['eventID'], $thisSession['id']);
		
		//get the stations information
		$stations = getStations();

		$topTitle = "Schedule Session";
		include "adminTop.php";
		
		/******************************************************************************
		 *                                                                            *
		 *                               TECHNOLOGY FAIRS                             * 
		 *                                                                            *
		 ******************************************************************************/
		
		if($thisSession["event"] == "Technology Fairs") {
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_assigned {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
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
		
		td.pList_assigned_highlighted {
			background-color: #006600;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_assigned_other_highlighted {
			background-color: #660000;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.pList_assigned_other {
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

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
		
		td.stationTD {
			background-color: #FFFFFF;
			text-align: center;
		}
		
		td.stationTD_assigned {
			background-color: #CCFFCC;
			text-align: center;
		}
	</style>
	<script type="text/javascript">
		var schedule = new Array();
		
<?php
			for($y = 0; $y < count($stations); $y++) {
?>
		schedule[<?php echo $y; ?>] = new Array();
		schedule[<?php echo $y; ?>]['station'] = '<?php echo $stations[$y]["id"]; ?>';
		schedule[<?php echo $y; ?>]['proposal'] = '';
<?php
			}
?>

		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_assigned') cEl.className = 'pList_assigned_highlighted';
					else if(cEl.className == 'pList_assigned_other') cEl.className = 'pList_assigned_other_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_assigned_highlighted') cEl.className = 'pList_assigned';
					else if(cEl.className == 'pList_assigned_other_highlighted') cEl.className = 'pList_assigned_other';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function updateSchedule(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				var sM = document.getElementById('saveMsg');
				sM.style.visibility = '';
			
				//When a station is selected, we need to save the proposal id to that station in the array
				//We also need to make sure that the proposal is only saved in one station
				//First, get the proposal id
				var rN = el.id.substring(7,el.id.length);
				var tP = document.getElementById('propID' + rN).value; //gets the proposal ID for that row
				var rEl = document.getElementById('row' + rN);
				
				//Next, check to see if this proposal is already assigned to any station
				for(i = 0; i < schedule.length; i++) {
					if(schedule[i]['proposal'] == tP) { //the proposal is assigned to this station
						schedule[i]['proposal'] = ''; //clear it from that station
						
						var cS = parseInt(i) + parseInt(1);
						document.getElementById('sTD' + cS).className = 'stationTD';
						for(c = 0; c < rEl.cells.length; c++) {
							if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
							else rEl.cells[c].className = 'pList_rowOdd';
						}
					}
				}
				
				//Next, assign it to the correct station
				var tS = el.options[el.selectedIndex].value; //gets the ID of the station
				if(tS != '') { //only set if a station was selected
					var sI = tS - 1;

					//check to see if something else is already assigned to this station
					if(schedule[sI]['proposal'] != '') {
						alert('There is already a presentation assigned to this station!');
						el.selectedIndex = 0;
						return false;
					} else {
						schedule[sI]['proposal'] = tP;
						document.getElementById('sTD' + tS).className = 'stationTD_assigned';
						for(c = 0; c < rEl.cells.length; c++) {
							rEl.cells[c].className = 'pList_assigned_highlighted';
						}
					}
				}
				
				//Finally, update the counts
				var sCount = 0;
				var rCount = schedule.length;
				for(i = 0; i < schedule.length; i++) {
					if(schedule[i]['proposal'] != '') {
						sCount++;
						rCount--;
					}
				}
				
				document.getElementById('schedNum').innerHTML = sCount;
				document.getElementById('remainingNum').innerHTML = rCount;
				
				return;
			} else {
				//When the page first loads, we need to update the schedule array with the correct values
				//which are in the select elements (as set by the PHP script)
				var totalRows = document.getElementById('propTable').rows.length - 1;
				var sCount = 0;
				var rCount = schedule.length;
			
				for(i = 0; i < totalRows; i++) {
					var tSEl = document.getElementById('station' + i);
//					alert('Row: ' + i + '\ntSEl: ' + tSEl);
					if(tSEl != null) {
						if(tSEl.selectedIndex > 0) { //this proposal is scheduled for this session
							var tS = tSEl.options[tSEl.selectedIndex].value;
							var tP = document.getElementById('propID' + i).value;
							for(s = 0; s < schedule.length; s++) {
								if(schedule[s]['station'] == tS) {
									//see if this station is already occupied
									if(schedule[s]['proposal'] != '' && schedule[s]['proposal'] != tP) {
										alert('There is already a proposal scheduled at this station!');
										tSEl.selectedIndex = 0;
										return false;
									} else {
										schedule[s]['proposal'] = tP;
										document.getElementById('sTD' + tS).className = 'stationTD_assigned';
										var rEl = document.getElementById('row' + i);
										for(c = 0; c < rEl.cells.length; c++) {
											rEl.cells[c].className = 'pList_assigned';
										}
															
										sCount++;
										rCount--;
									}
							
									break;
								}
							}
						}
					}
				}
			
				document.getElementById('schedNum').innerHTML = sCount;
				document.getElementById('remainingNum').innerHTML = rCount;
				return;
			}
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
		
		function saveChanges() {
			var s = '';
			for(i = 0; i < schedule.length; i++) {
				s += schedule[i]['station'] + '|';
				if(schedule[i]['proposal'] != '') s += schedule[i]['proposal'];
				else s += '0';
				if(i < (schedule.length - 1)) s += '||';
			}
			
			document.getElementById('scheduledProposals').value = s;
			document.getElementById('schedForm').submit();
		}
		
		function editSession(n) {
			window.location.href = 'scheduleSession.php?s=' + n;
		}
		
		window.onload = function() {
			updateSchedule();
		};
		
		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 11pt">
					<strong>Session Information:</strong>
					<span style="font-style: italic; padding-left: 20px">Title:</span> <?php echo $thisSession["title"]; ?>
<?php
			$dateStr = date("F j, Y", strtotime($thisSession['date']));
?>
					<span style="padding-left: 20px; font-style: italic">Date:</span> <?php echo $dateStr; ?>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$sStart = strtotime($thisSession['date'].' '.$tmpTime[0]);
			$sEnd = strtotime($thisSession['date'].' '.$tmpTime[1]);
			$timeStr = date("g:i A", $sStart)." to ".date("g:i A", $sEnd);
?>
					<span style="padding-left: 20px; font-style: italic">Time:</span> <?php echo $timeStr; ?>
				</td>
			</tr>
		</table>
		<table width="800" border="0" align="center" style="border-bottom: solid 1px #AAAAAA">
			<tr>
				<td width="50%">Scheduled: <span id="schedNum">0</span></td>
				<td width="50%" align="right">Remaining: <span id="remainingNum"><?php echo count($stations); ?></span></td>
			</tr>
			<tr>
				<td colspan="2">
					<table border="0" width="100%">
						<tr>
<?php
			$tdWidth = floor(800 / (count($stations) / 2));
			for($z = 0; $z < count($stations); $z++) {
				if($z == (floor(count($stations) / 2))) {
?>
						</tr>
						<tr>
<?php
				}
?>
							<td id="sTD<?php echo $stations[$z]["id"]; ?>" class="stationTD" width="<?php echo $tdWidth; ?>"><?php echo $stations[$z]["name"]; ?></td>
<?php
			}
?>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';

				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];

				$tmpTimes = explode("|",$proposals[$i]["times"]);
				if(count($tmpTimes) > 1) {
					$timesStr = "";
					for($t = 0; $t < count($tmpTimes); $t++) {
						$timesStr .= $tmpTimes[$t];
						if($t < (count($tmpTimes) - 1)) $timesStr .= "<br />";
					}
				} else $timesStr = $tmpTimes[0];

				$tmpTopics = explode("|",$proposals[$i]["topics"]);
				if(count($tmpTopics) > 1) {
					$topicStr = "";
					for($t = 0; $t < count($tmpTopics); $t++) {
						$topicStr .= $tmpTopics[$t];
						if($t < (count($tmpTopics) - 1)) $topicStr .= "<br />";
					}
				} else $topicStr = $tmpTopics[0];

				if(strpos($proposals[$i]["session"],"|") !== false) { //this proposal is assigned to a different session
					$rowClass = "pList_assigned_other";
					
					$tmp = explode("|",$proposals[$i]["session"]);
					//$tmpDate = explode("-",$tmp[0]);
					//$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					$dateStr = date("F j, Y", strtotime($tmp[0]));
					
					$tmpTime = explode("-",$tmp[1]);
					//$tmpStart = explode(":",$tmpTime[0]);
					//$tmpSHour = intval($tmpStart[0]);
					//if($tmpSHour < 12) $sAMPM = "AM";
					//else {
					//	$sAMPM = "PM";
					//	if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
					//}
					//$tmpSMinutes = $tmpStart[1];
				
					//$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
					$timeStr = date("g:i A", strtotime($tmpTime[0]));
			
					//$tmpEnd = explode(":",$tmpTime[1]);
					//$tmpEHour = intval($tmpEnd[0]);
					//if($tmpEHour < 12) $eAMPM = "AM";
					//else {
					//	$eAMPM = "PM";
					//	if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
					//}
					//$tmpEMinutes = $tmpEnd[1];
				
					//$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
					$timeStr .= " to ".date("g:i A", strtotime($tmpTime[1]));
					
					for($z = 0; $z < count($stations); $z++) {
						if($proposals[$i]["station"] == $stations[$z]["id"]) {
							$stationStr = $stations[$z]["name"];
							break;
						}
					}
					
					$assignedStr = '<span style="font-size: .6em">'.$dateStr.'<br />'.$timeStr.'<br />'.$stationStr.'</span>';
					$otherSession = $tmp[2];
				} else { //this proposals is assigned to this session or not assigned to any session
					$assignedStr = '<select name="station'.$rN.'" id="station'.$rN.'" onChange="updateSchedule(this)"><option value="">--</option>';
					for($z = 0; $z < count($stations); $z++) {
						$isChecked = false;					
						if($proposals[$i]["session"] == $thisSession["id"]) { //this proposal is scheduled for this session
							if($proposals[$i]["station"] == $stations[$z]["id"]) { //this station is assigned for this proposal
								$isChecked = true;
							}
						}
						
						if($isChecked) $assignedStr .= '<option value="'.$stations[$z]['id'].'" selected="true">'.$stations[$z]['name'].'</option>';
						else $assignedStr .= '<option value="'.$stations[$z]['id'].'">'.$stations[$z]['name'].'</option>';
					}
					
					$assignedStr .= '</select>';
					$otherSession = 0;
				}
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo stripslashes($proposals[$i]['title']); ?></td>
<?php
?>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo $presStr; ?></td>
<?php
?>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><input type="hidden" name="propID<?php echo $rN; ?>" id="propID<?php echo $rN; ?>" value="<?php echo $proposals[$i]["id"]; ?>" /><?php echo $timesStr; ?></td>
<?php
?>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo $topicStr; ?></td>			
			<td class="<?php echo $rowClass; ?>" width="100" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo $proposals[$i]['computer']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="100" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo $assignedStr; ?></td>
			</td>
		</tr>
<?php

				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
				<th class="pList">Preferred Times</th>
				<th class="pList">Topics</th>
				<th class="pList">Computer Preference</th>
				<th class="pList">Assigned Station</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">CHANGES NOT SAVED!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input type="button" value="Save Changes" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="schedForm" id="schedForm" method="post" action="">
		<input type="hidden" name="scheduledProposals" id="scheduledProposals" value="" />
		<input type="hidden" name="scheduledSession" id="scheduledSession" value="<?php echo $thisSession["id"]; ?>" />
	</form>

<?php

		
		/******************************************************************************
		 *                                                                            *
		 *                               MINI-WORKSHOPS                               * 
		 *                                                                            *
		 ******************************************************************************/
		
		} else if($thisSession["event"] == "Mini-Workshops") {
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
		
		td.pList_assigned {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_assigned_highlighted {
			background-color: #006600;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_assigned_other {
			background-color: #FFCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_assigned_other_highlighted {
			background-color: #660000;
			color: #FFFFFF;
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

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
	</style>
	<script type="text/javascript">
		var prop1 = '0';
		var prop2 = '0';
		
		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_assigned') cEl.className = 'pList_assigned_highlighted';
					else if(cEl.className == 'pList_assigned_other') cEl.className = 'pList_assigned_other_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_assigned_highlighted') cEl.className = 'pList_assigned';
					else if(cEl.className == 'pList_assigned_other_highlighted') cEl.className = 'pList_assigned_other';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function checkBox(n) {
			document.getElementById('chk' + n).checked = true;
			updateSchedule(document.getElementById('chk' + n));
		}

		function updateSchedule(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				var sM = document.getElementById('saveMsg');
				sM.style.visibility = '';
			
				//When a checkbox is checked, we need to save the proposal id.
				//We also need to make sure that only two proposals are chosen for each session

				//First, get the proposal id
				var tP = el.value;
				var rN = el.id.substring(3,el.id.length);
				
				if(el.checked) { //the box is checked, so save this as one of the proposals				
					if(prop1 != '0' && prop2 != '0') { //both slots are filled
						alert('You have already selected two propsals for this session!');
						el.checked = false;
						return false;
					} else if(prop1 == '0') {
						prop1 = tP;
						
						//get the title
						var pR = document.getElementById('row' + rN);
						var pT = pR.cells[1].innerHTML;
						
						//put the title in the slot for Workshop 1
						document.getElementById('wkshp1_1').innerHTML = pT;
						document.getElementById('wkshp1_0').style.backgroundColor = '#CCFFCC';
						document.getElementById('wkshp1_1').style.backgroundColor = '#CCFFCC';
						for(c = 0; c < pR.cells.length; c++) {
							pR.cells[c].className = 'pList_assigned_highlighted';
						}
					} else if(prop2 == '0') {
						prop2 = tP;
						
						//get the title
						var pR = document.getElementById('row' + rN);
						var pT = pR.cells[1].innerHTML;
						
						//put the title in the slot for Workshop 2
						document.getElementById('wkshp2_1').innerHTML = pT;
						document.getElementById('wkshp2_0').style.backgroundColor = '#CCFFCC';
						document.getElementById('wkshp2_1').style.backgroundColor = '#CCFFCC';
						for(c = 0; c < pR.cells.length; c++) {
							pR.cells[c].className = 'pList_assigned_highlighted';
						}
					}
				} else { //el is unchecked
					if(prop1 == tP) {
						prop1 = '0';
						document.getElementById('wkshp1_1').innerHTML = '';
						document.getElementById('wkshp1_0').style.backgroundColor = '#FFFFFF';
						document.getElementById('wkshp1_1').style.backgroundColor = '#FFFFFF';
						var rEl = document.getElementById('row' + rN);
						for(c = 0; c < rEl.cells.length; c++) {
							if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
							else rEl.cells[c].className = 'pList_rowOdd';
						}
					} else if(prop2 == tP) {
						prop2 = '0';
						document.getElementById('wkshp2_1').innerHTML = '';
						document.getElementById('wkshp2_0').style.backgroundColor = '#FFFFFF';
						document.getElementById('wkshp2_1').style.backgroundColor = '#FFFFFF';
						var rEl = document.getElementById('row' + rN);
						for(c = 0; c < rEl.cells.length; c++) {
							if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
							else rEl.cells[c].className = 'pList_rowOdd';
						}
					}
				}
				
				return;
			} else {
				//When the page first loads, we need to update the schedule array with the correct values
				//which are in the checkbox elements (as set by the PHP script)
				var totalRows = document.getElementById('propTable').rows.length - 1;
				var tooManyProps = false;

				for(i = 0; i < totalRows; i++) {
					var tChkEl = document.getElementById('chk' + i);
					if(tChkEl != null) {
						if(tChkEl.checked) { //this proposal is scheduled for this session
							var tP = tChkEl.value;
							if(prop1 != '0' && prop2 != '0') { //both slots are already filled
								tChkEl.checked = false;
								tooManyProps = true;
							} else if(prop1 == '0') {
								prop1 = tChkEl.value;
							
								//get the title
								var pR = document.getElementById('row' + i);
								var pT = pR.cells[1].innerHTML;
						
								//put the title in the slot for Workshop 1
								document.getElementById('wkshp1_1').innerHTML = pT;						
								document.getElementById('wkshp1_0').style.backgroundColor = '#CCFFCC';
								document.getElementById('wkshp1_1').style.backgroundColor = '#CCFFCC';
								for(c = 0; c < pR.cells.length; c++) {
									pR.cells[c].className = 'pList_assigned';
								}
							} else if(prop2 == '0') {
								prop2 = tChkEl.value;
							
								//get the title
								var pR = document.getElementById('row' + i);
								var pT = pR.cells[1].innerHTML;
						
								//put the title in the slot for Workshop 2
								document.getElementById('wkshp2_1').innerHTML = pT;						
								document.getElementById('wkshp2_0').style.backgroundColor = '#CCFFCC';
								document.getElementById('wkshp2_1').style.backgroundColor = '#CCFFCC';
								for(c = 0; c < pR.cells.length; c++) {
									pR.cells[c].className = 'pList_assigned';
								}
							}
						}
					}
				}
			
				if(tooManyProps) { //more than two proposals were already selected for this session in the database
					alert('More than 2 proposals were selected for this session!\n\nAny proposals after the first 2 proposals were automatically unchecked! Please verify that the proposals selected are correct!');
					return false;
				}
				
				return;
			}
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
		
		function saveChanges() {
			document.getElementById('scheduledProposals').value = '1|' + prop1 + '||2|' + prop2;
			document.getElementById('schedForm').submit();
		}

		window.onload = function() {
			updateSchedule();
		};

		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td colspan="2"><strong>Session Information:</strong></td>
			</tr>
<?php
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
			$tmpDate = explode("-",$thisSession["date"]);
			$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
?>
			<tr>
				<td style="padding-left: 20px; font-weight: bold">Date:</td>
				<td><?php echo $dateStr; ?></td>			
			</tr>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			if($tmpSHour < 12) $sAMPM = "AM";
			else {
				$sAMPM = "PM";
				if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
			}
			$tmpSMinutes = $tmpStart[1];
				
			$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			if($tmpEHour < 12) $eAMPM = "AM";
			else {
				$eAMPM = "PM";
				if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
			}
			$tmpEMinutes = $tmpEnd[1];
				
			$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
			<tr>
				<td style="padding-left: 20px; font-weight: bold">Time:</td>
				<td><?php echo $timeStr; ?></td>			
			</tr>
		</table>
		<table width="800" border="0" align="center" style="border-bottom: solid 1px #AAAAAA">
			<tr>
				<td id="wkshp1_0" width="100">Workshop 1:</td>
				<td id="wkshp1_1" width="700" style="padding-left: 10px">&nbsp;</td>
			</tr>
			<tr>
				<td id="wkshp2_0" width="100">Workshop 2:</td>
				<td id="wkshp2_1" width="700" style="padding-left: 10px">&nbsp;</td>
			</tr>
		</table>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if(strpos($proposals[$i]["session"],"|") !== false) {
					$rowClass = 'pList_assigned_other';

					$tmp = explode("|",$proposals[$i]["session"]);
					$tmpDate = explode("-",$tmp[0]);
					$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					
					$tmpTime = explode("-",$tmp[1]);
					$tmpStart = explode(":",$tmpTime[0]);
					$tmpSHour = intval($tmpStart[0]);
					if($tmpSHour < 12) $sAMPM = "AM";
					else {
						$sAMPM = "PM";
						if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
					}
					$tmpSMinutes = $tmpStart[1];
				
					$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
					$tmpEnd = explode(":",$tmpTime[1]);
					$tmpEHour = intval($tmpEnd[0]);
					if($tmpEHour < 12) $eAMPM = "AM";
					else {
						$eAMPM = "PM";
						if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
					}
					$tmpEMinutes = $tmpEnd[1];
				
					$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
					
					for($z = 0; $z < count($stations); $z++) {
						if($proposals[$i]["station"] == $stations[$z]["id"]) {
							$stationStr = $stations[$z]["name"];
							break;
						}
					}
					
					$assignedStr = '<span style="font-size: .6em">'.$dateStr.'<br />'.$timeStr.'<br />'.$stationStr.'</span>';
					$otherSession = $tmp[2];									
				} else {
					if($rN % 2 == 0) $rowClass = 'pList_rowEven';
					else $rowClass = 'pList_rowOdd';
					
					$assignedStr = '<input type="checkbox" name="chk'.$rN.'" id="chk'.$rN.'" value="'.$proposals[$i]["id"].'" onClick="updateSchedule(this)"';
					if($proposals[$i]["session"] == $thisSession["id"]) $assignedStr .= ' checked="true"';
					$assignedStr .= ' />';
					$otherSession = 0;
				}
				
				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];

				$tmpTimes = explode("|",$proposals[$i]["times"]);
				if(count($tmpTimes) > 1) {
					$timesStr = "";
					for($t = 0; $t < count($tmpTimes); $t++) {
						$timesStr .= $tmpTimes[$t];
						if($t < (count($tmpTimes) - 1)) $timesStr .= "<br />";
					}
				} else $timesStr = $tmpTimes[0];

				$tmpTopics = explode("|",$proposals[$i]["topics"]);
				if(count($tmpTopics) > 1) {
					$topicStr = "";
					for($t = 0; $t < count($tmpTopics); $t++) {
						$topicStr .= $tmpTopics[$t];
						if($t < (count($tmpTopics) - 1)) $topicStr .= "<br />";
					}
				} else $topicStr = $tmpTopics[0];
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" valign="center" align="center" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?> onClick="editSession('<?php echo $otherSession; ?>')" <?php } ?>><?php echo $assignedStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?> onClick="editSession('<?php echo $otherSession; ?>')" <?php } else { ?> onClick="checkBox('<?php echo $rN; ?>')"<?php } ?>><?php echo $proposals[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?> onClick="editSession('<?php echo $otherSession; ?>')" <?php } else { ?> onClick="checkBox('<?php echo $rN; ?>')"<?php } ?>><?php echo $presStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?> onClick="editSession('<?php echo $otherSession; ?>')" <?php } else { ?> onClick="checkBox('<?php echo $rN; ?>')"<?php } ?>><?php echo $timesStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?> onClick="editSession('<?php echo $otherSession; ?>')" <?php } else { ?> onClick="checkBox('<?php echo $rN; ?>')"<?php } ?>><?php echo $topicStr; ?></td>			
		</tr>
<?php

				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">&nbsp;</th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
				<th class="pList">Preferred Times</th>
				<th class="pList">Topics</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">CHANGES NOT SAVED!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input type="button" value="Save Changes" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="schedForm" id="schedForm" method="post" action="">
		<input type="hidden" name="scheduledProposals" id="scheduledProposals" value="" />
		<input type="hidden" name="scheduledSession" id="scheduledSession" value="<?php echo $thisSession["id"]; ?>" />
	</form>
<?php

		
		/******************************************************************************
		 *                                                                            *
		 *                             DEVELOPERS SHOWCASE                            * 
		 *                                                                            *
		 ******************************************************************************/
		

		} else if($thisSession["event"] == "Developers Showcase") {
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_assigned {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
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
		
		td.pList_assigned_highlighted {
			background-color: #006600;
			color: #FFFFFF;
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

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
	</style>
	<script type="text/javascript">
		var schedule = new Array();

		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_assigned') cEl.className = 'pList_assigned_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_assigned_highlighted') cEl.className = 'pList_assigned';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function checkBox(n) {
			document.getElementById('chk' + n).checked = true;
			updateSchedule(document.getElementById('chk' + n));
		}

		function updateSchedule(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				var sM = document.getElementById('saveMsg');
				sM.style.visibility = '';
			
				//When a checkbox is checked, we need to save the proposal id.
				//First, get the proposal id
				var tP = el.value;
				var rN = el.id.substring(3,el.id.length);
				
				if(el.checked) { //the box is checked, so save this as one of the proposals
					//check to see if the proposal is already scheduled
					for(i = 0; i < schedule.length; i++) {
						if(schedule[i] == tP) return false; //already scheduled, so do nothing
					}
					
					var sI = schedule.length;
					schedule[sI] = tP;
					
					//get the title
					var pR = document.getElementById('row' + rN);
					for(c = 0; c < pR.cells.length; c++) {
						pR.cells[c].className = 'pList_assigned_highlighted';
					}
				} else { //el is unchecked
					//remove the proposal from the schedule array
					for(i = 0; i < schedule.length; i++) {
						if(schedule[i] == tP) {
							schedule.splice(i,1); //remove the proposal from the array
							break;
						}
					}
					
					var rEl = document.getElementById('row' + rN);
					for(c = 0; c < rEl.cells.length; c++) {
						if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
						else rEl.cells[c].className = 'pList_rowOdd';
					}
				}
				
				document.getElementById('schedNum').innerHTML = schedule.length;
				
				return;
			} else {
				//When the page first loads, we need to update the schedule array with the correct values
				//which are in the checkbox elements (as set by the PHP script)
				var totalRows = document.getElementById('propTable').rows.length - 1;

				for(i = 0; i < totalRows; i++) {
					var tChkEl = document.getElementById('chk' + i);
					if(tChkEl.checked) { //this proposal is scheduled for this session
						var tP = tChkEl.value;
						var sI = schedule.length;
						schedule[sI] = tP;

						var pR = document.getElementById('row' + i);
						for(c = 0; c < pR.cells.length; c++) {
							pR.cells[c].className = 'pList_assigned';
						}
					}
				}
				
				document.getElementById('schedNum').innerHTML = schedule.length;

				return;
			}
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
		
		function saveChanges() {
			var schedStr = '';
			for(i = 0; i < schedule.length; i++) {
				schedStr += schedule[i];
				if(i < (schedule.length - 1)) schedStr += '||';
			}
			
			document.getElementById('scheduledProposals').value = schedStr;
			document.getElementById('schedForm').submit();
		}

		window.onload = function() {
			updateSchedule();
		};

		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 11pt">
					<strong>Session Information:</strong>
					<span style="font-style: italic; padding-left: 20px">Title:</span> <?php echo $thisSession["title"]; ?>
<?php
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
			$tmpDate = explode("-",$thisSession["date"]);
			$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
?>
					<span style="padding-left: 20px; font-style: italic">Date:</span> <?php echo $dateStr; ?>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			if($tmpSHour < 12) $sAMPM = "AM";
			else {
				$sAMPM = "PM";
				if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
			}
			$tmpSMinutes = $tmpStart[1];
				
			$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			if($tmpEHour < 12) $eAMPM = "AM";
			else {
				$eAMPM = "PM";
				if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
			}
			$tmpEMinutes = $tmpEnd[1];
				
			$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
					<span style="padding-left: 20px; font-style: italic">Time:</span> <?php echo $timeStr; ?>
				</td>
			</tr>
		</table>
		<p align="center">Scheduled: <span id="schedNum">0</span></p>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
					
				$assignedStr = '<input type="checkbox" name="chk'.$rN.'" id="chk'.$rN.'" value="'.$proposals[$i]["id"].'" onClick="updateSchedule(this)"';
				if($proposals[$i]["session"] == $thisSession["id"]) $assignedStr .= ' checked="true"';
				$assignedStr .= ' />';
				
				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" valign="center" align="center" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"><?php echo $assignedStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="450" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="checkBox('<?php echo $rN; ?>')"><?php echo $proposals[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="checkBox('<?php echo $rN; ?>')"><?php echo $presStr; ?></td>
		</tr>
<?php

				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">&nbsp;</th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">CHANGES NOT SAVED!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input type="button" value="Save Changes" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="schedForm" id="schedForm" method="post" action="">
		<input type="hidden" name="scheduledProposals" id="scheduledProposals" value="" />
		<input type="hidden" name="scheduledSession" id="scheduledSession" value="<?php echo $thisSession["id"]; ?>" />
	</form>
<?php		

		
		/******************************************************************************
		 *                                                                            *
		 *                     MOBILE APPS FOR EDUCATION SHOWCASE                     * 
		 *                                                                            *
		 ******************************************************************************/
		

		} else if($thisSession["event"] == "Mobile Apps for Education Showcase") {
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_assigned {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
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
		
		td.pList_assigned_highlighted {
			background-color: #006600;
			color: #FFFFFF;
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

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
	</style>
	<script type="text/javascript">
		var schedule = new Array();

		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_assigned') cEl.className = 'pList_assigned_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_assigned_highlighted') cEl.className = 'pList_assigned';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function checkBox(n) {
			document.getElementById('chk' + n).checked = true;
			updateSchedule(document.getElementById('chk' + n));
		}

		function updateSchedule(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				var sM = document.getElementById('saveMsg');
				sM.style.visibility = '';
			
				//When a checkbox is checked, we need to save the proposal id.
				//First, get the proposal id
				var tP = el.value;
				var rN = el.id.substring(3,el.id.length);
				
				if(el.checked) { //the box is checked, so save this as one of the proposals
					//check to see if the proposal is already scheduled
					for(i = 0; i < schedule.length; i++) {
						if(schedule[i] == tP) return false; //already scheduled, so do nothing
					}
					
					var sI = schedule.length;
					schedule[sI] = tP;
					
					//get the title
					var pR = document.getElementById('row' + rN);
					for(c = 0; c < pR.cells.length; c++) {
						pR.cells[c].className = 'pList_assigned_highlighted';
					}
				} else { //el is unchecked
					//remove the proposal from the schedule array
					for(i = 0; i < schedule.length; i++) {
						if(schedule[i] == tP) {
							schedule.splice(i,1); //remove the proposal from the array
							break;
						}
					}
					
					var rEl = document.getElementById('row' + rN);
					for(c = 0; c < rEl.cells.length; c++) {
						if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
						else rEl.cells[c].className = 'pList_rowOdd';
					}
				}
				
				document.getElementById('schedNum').innerHTML = schedule.length;
				
				return;
			} else {
				//When the page first loads, we need to update the schedule array with the correct values
				//which are in the checkbox elements (as set by the PHP script)
				var totalRows = document.getElementById('propTable').rows.length - 1;

				for(i = 0; i < totalRows; i++) {
					var tChkEl = document.getElementById('chk' + i);
					if(tChkEl.checked) { //this proposal is scheduled for this session
						var tP = tChkEl.value;
						var sI = schedule.length;
						schedule[sI] = tP;

						var pR = document.getElementById('row' + i);
						for(c = 0; c < pR.cells.length; c++) {
							pR.cells[c].className = 'pList_assigned';
						}
					}
				}
				
				document.getElementById('schedNum').innerHTML = schedule.length;

				return;
			}
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
		
		function saveChanges() {
			var schedStr = '';
			for(i = 0; i < schedule.length; i++) {
				schedStr += schedule[i];
				if(i < (schedule.length - 1)) schedStr += '||';
			}
			
			document.getElementById('scheduledProposals').value = schedStr;
			document.getElementById('schedForm').submit();
		}

		window.onload = function() {
			updateSchedule();
		};

		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 11pt">
					<strong>Session Information:</strong>
					<span style="font-style: italic; padding-left: 20px">Title:</span> <?php echo $thisSession["title"]; ?>
<?php
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
			$tmpDate = explode("-",$thisSession["date"]);
			$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
?>
					<span style="padding-left: 20px; font-style: italic">Date:</span> <?php echo $dateStr; ?>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			if($tmpSHour < 12) $sAMPM = "AM";
			else {
				$sAMPM = "PM";
				if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
			}
			$tmpSMinutes = $tmpStart[1];
				
			$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			if($tmpEHour < 12) $eAMPM = "AM";
			else {
				$eAMPM = "PM";
				if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
			}
			$tmpEMinutes = $tmpEnd[1];
				
			$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
					<span style="padding-left: 20px; font-style: italic">Time:</span> <?php echo $timeStr; ?>
				</td>
			</tr>
		</table>
		<p align="center">Scheduled: <span id="schedNum">0</span></p>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
					
				$assignedStr = '<input type="checkbox" name="chk'.$rN.'" id="chk'.$rN.'" value="'.$proposals[$i]["id"].'" onClick="updateSchedule(this)"';
				if($proposals[$i]["session"] == $thisSession["id"]) $assignedStr .= ' checked="true"';
				$assignedStr .= ' />';
				
				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" valign="center" align="center" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"><?php echo $assignedStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="450" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="checkBox('<?php echo $rN; ?>')"><?php echo $proposals[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="checkBox('<?php echo $rN; ?>')"><?php echo $presStr; ?></td>
		</tr>
<?php

				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">&nbsp;</th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">CHANGES NOT SAVED!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input type="button" value="Save Changes" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="schedForm" id="schedForm" method="post" action="">
		<input type="hidden" name="scheduledProposals" id="scheduledProposals" value="" />
		<input type="hidden" name="scheduledSession" id="scheduledSession" value="<?php echo $thisSession["id"]; ?>" />
	</form>
<?php					

		/******************************************************************************
		 *                                                                            *
		 *                                  HOT TOPICS                                * 
		 *                                                                            *
		 ******************************************************************************/
		

		} else if($thisSession["event"] == "Hot Topics") {
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_assigned {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
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
		
		td.pList_assigned_highlighted {
			background-color: #006600;
			color: #FFFFFF;
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

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
	</style>
	<script type="text/javascript">
		var schedule = new Array();

		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_assigned') cEl.className = 'pList_assigned_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_assigned_highlighted') cEl.className = 'pList_assigned';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function checkBox(n) {
			document.getElementById('chk' + n).checked = true;
			updateSchedule(document.getElementById('chk' + n));
		}

		function updateSchedule(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				var sM = document.getElementById('saveMsg');
				sM.style.visibility = '';
			
				//When a checkbox is checked, we need to save the proposal id.
				//First, get the proposal id
				var tP = el.value;
				var rN = el.id.substring(3,el.id.length);
				
				if(el.checked) { //the box is checked, so save this as one of the proposals
					//check to see if the proposal is already scheduled
					for(i = 0; i < schedule.length; i++) {
						if(schedule[i] == tP) return false; //already scheduled, so do nothing
					}
					
					var sI = schedule.length;
					schedule[sI] = tP;
					
					//get the title
					var pR = document.getElementById('row' + rN);
					for(c = 0; c < pR.cells.length; c++) {
						pR.cells[c].className = 'pList_assigned_highlighted';
					}
				} else { //el is unchecked
					//remove the proposal from the schedule array
					for(i = 0; i < schedule.length; i++) {
						if(schedule[i] == tP) {
							schedule.splice(i,1); //remove the proposal from the array
							break;
						}
					}
					
					var rEl = document.getElementById('row' + rN);
					for(c = 0; c < rEl.cells.length; c++) {
						if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
						else rEl.cells[c].className = 'pList_rowOdd';
					}
				}
				
				document.getElementById('schedNum').innerHTML = schedule.length;
				
				return;
			} else {
				//When the page first loads, we need to update the schedule array with the correct values
				//which are in the checkbox elements (as set by the PHP script)
				var totalRows = document.getElementById('propTable').rows.length - 1;

				for(i = 0; i < totalRows; i++) {
					var tChkEl = document.getElementById('chk' + i);
					if(tChkEl.checked) { //this proposal is scheduled for this session
						var tP = tChkEl.value;
						var sI = schedule.length;
						schedule[sI] = tP;

						var pR = document.getElementById('row' + i);
						for(c = 0; c < pR.cells.length; c++) {
							pR.cells[c].className = 'pList_assigned';
						}
					}
				}
				
				document.getElementById('schedNum').innerHTML = schedule.length;

				return;
			}
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
		
		function saveChanges() {
			var schedStr = '';
			for(i = 0; i < schedule.length; i++) {
				schedStr += schedule[i];
				if(i < (schedule.length - 1)) schedStr += '||';
			}
			
			document.getElementById('scheduledProposals').value = schedStr;
			document.getElementById('schedForm').submit();
		}

		window.onload = function() {
			updateSchedule();
		};

		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 11pt">
					<strong>Session Information:</strong>
					<span style="font-style: italic; padding-left: 20px">Title:</span> <?php echo $thisSession["title"]; ?>
<?php
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
			$tmpDate = explode("-",$thisSession["date"]);
			$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
?>
					<span style="padding-left: 20px; font-style: italic">Date:</span> <?php echo $dateStr; ?>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			if($tmpSHour < 12) $sAMPM = "AM";
			else {
				$sAMPM = "PM";
				if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
			}
			$tmpSMinutes = $tmpStart[1];
				
			$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			if($tmpEHour < 12) $eAMPM = "AM";
			else {
				$eAMPM = "PM";
				if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
			}
			$tmpEMinutes = $tmpEnd[1];
				
			$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
					<span style="padding-left: 20px; font-style: italic">Time:</span> <?php echo $timeStr; ?>
				</td>
			</tr>
		</table>
		<p align="center">Scheduled: <span id="schedNum">0</span></p>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
					
				$assignedStr = '<input type="checkbox" name="chk'.$rN.'" id="chk'.$rN.'" value="'.$proposals[$i]["id"].'" onClick="updateSchedule(this)"';
				if($proposals[$i]["session"] == $thisSession["id"]) $assignedStr .= ' checked="true"';
				$assignedStr .= ' />';
				
				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" valign="center" align="center" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"><?php echo $assignedStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="450" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="checkBox('<?php echo $rN; ?>')"><?php echo $proposals[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="checkBox('<?php echo $rN; ?>')"><?php echo $presStr; ?></td>
		</tr>
<?php

				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">&nbsp;</th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">CHANGES NOT SAVED!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input type="button" value="Save Changes" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="schedForm" id="schedForm" method="post" action="">
		<input type="hidden" name="scheduledProposals" id="scheduledProposals" value="" />
		<input type="hidden" name="scheduledSession" id="scheduledSession" value="<?php echo $thisSession["id"]; ?>" />
	</form>
<?php	

		/******************************************************************************
		 *                                                                            *
		 *                        GRADUATE STUDENT RESEARCH                           * 
		 *                                                                            *
		 ******************************************************************************/
		

		} else if($thisSession["event"] == "Graduate Student Research") {
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_assigned {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
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
		
		td.pList_assigned_highlighted {
			background-color: #006600;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.pList_assigned_other {
			background-color: #FFCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_assigned_other_highlighted {
			background-color: #660000;
			color: #FFFFFF;
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

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
	</style>
	<script type="text/javascript">
		var schedule = new Array();

		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_assigned') cEl.className = 'pList_assigned_highlighted';
					else if(cEl.className == 'pList_assigned_other') cEl.className = 'pList_assigned_other_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_assigned_highlighted') cEl.className = 'pList_assigned';
					else if(cEl.className == 'pList_assigned_other_highlighted') cEl.className = 'pList_assigned_other';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function checkBox(n) {
			document.getElementById('chk' + n).checked = true;
			updateSchedule(document.getElementById('chk' + n));
		}

		function updateSchedule(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				var sM = document.getElementById('saveMsg');
				sM.style.visibility = '';
			
				//When a checkbox is checked, we need to save the proposal id.
				//First, get the proposal id
				var tP = el.value;
				var rN = el.id.substring(3,el.id.length);
				
				if(el.checked) { //the box is checked, so save this as one of the proposals
					//check to see if the proposal is already scheduled
					for(i = 0; i < schedule.length; i++) {
						if(schedule[i] == tP) return false; //already scheduled, so do nothing
					}
					
					var sI = schedule.length;
					schedule[sI] = tP;
					
					//get the title
					var pR = document.getElementById('row' + rN);
					for(c = 0; c < pR.cells.length; c++) {
						pR.cells[c].className = 'pList_assigned_highlighted';
					}
				} else { //el is unchecked
					//remove the proposal from the schedule array
					for(i = 0; i < schedule.length; i++) {
						if(schedule[i] == tP) {
							schedule.splice(i,1); //remove the proposal from the array
							break;
						}
					}
					
					var rEl = document.getElementById('row' + rN);
					for(c = 0; c < rEl.cells.length; c++) {
						if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
						else rEl.cells[c].className = 'pList_rowOdd';
					}
				}
				
				document.getElementById('schedNum').innerHTML = schedule.length;
				
				return;
			} else {
				//When the page first loads, we need to update the schedule array with the correct values
				//which are in the checkbox elements (as set by the PHP script)
				var totalRows = document.getElementById('propTable').rows.length - 1;

				for(i = 0; i < totalRows; i++) {
					var tChkEl = document.getElementById('chk' + i);
					if(tChkEl != null) {
						if(tChkEl.checked) { //this proposal is scheduled for this session
							var tP = tChkEl.value;
							var sI = schedule.length;
							schedule[sI] = tP;

							var pR = document.getElementById('row' + i);
							for(c = 0; c < pR.cells.length; c++) {
								pR.cells[c].className = 'pList_assigned';
							}
						}
					}
				}
				
				document.getElementById('schedNum').innerHTML = schedule.length;

				return;
			}
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
		
		function saveChanges() {
			var schedStr = '';
			for(i = 0; i < schedule.length; i++) {
				schedStr += schedule[i];
				if(i < (schedule.length - 1)) schedStr += '||';
			}
			
			document.getElementById('scheduledProposals').value = schedStr;
			document.getElementById('schedForm').submit();
		}
		
		function editSession(n) {
			window.location.href = 'scheduleSession.php?s=' + n;
		}
		
		window.onload = function() {
			updateSchedule();
		};

		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 11pt">
					<strong>Session Information:</strong>
					<span style="font-style: italic; padding-left: 20px">Title:</span> <?php echo $thisSession["title"]; ?>
<?php
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
			$tmpDate = explode("-",$thisSession["date"]);
			$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
?>
					<span style="padding-left: 20px; font-style: italic">Date:</span> <?php echo $dateStr; ?>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			if($tmpSHour < 12) $sAMPM = "AM";
			else {
				$sAMPM = "PM";
				if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
			}
			$tmpSMinutes = $tmpStart[1];
				
			$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			if($tmpEHour < 12) $eAMPM = "AM";
			else {
				$eAMPM = "PM";
				if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
			}
			$tmpEMinutes = $tmpEnd[1];
				
			$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
					<span style="padding-left: 20px; font-style: italic">Time:</span> <?php echo $timeStr; ?>
				</td>
			</tr>
		</table>
		<p align="center">Scheduled: <span id="schedNum">0</span></p>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if(strpos($proposals[$i]["session"],"|") !== false) {
					$rowClass = 'pList_assigned_other';

					$tmp = explode("|",$proposals[$i]["session"]);
					$tmpDate = explode("-",$tmp[0]);
					$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					
					$tmpTime = explode("-",$tmp[1]);
					$tmpStart = explode(":",$tmpTime[0]);
					$tmpSHour = intval($tmpStart[0]);
					if($tmpSHour < 12) $sAMPM = "AM";
					else {
						$sAMPM = "PM";
						if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
					}
					$tmpSMinutes = $tmpStart[1];
				
					$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
					$tmpEnd = explode(":",$tmpTime[1]);
					$tmpEHour = intval($tmpEnd[0]);
					if($tmpEHour < 12) $eAMPM = "AM";
					else {
						$eAMPM = "PM";
						if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
					}
					$tmpEMinutes = $tmpEnd[1];
				
					$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
					
					for($z = 0; $z < count($stations); $z++) {
						if($proposals[$i]["station"] == $stations[$z]["id"]) {
							$stationStr = $stations[$z]["name"];
							break;
						}
					}
					
					$assignedStr = '<span style="font-size: .6em">'.$dateStr.'<br />'.$timeStr.'</span>';
					$otherSession = $tmp[2];									
				} else {
					if($rN % 2 == 0) $rowClass = 'pList_rowEven';
					else $rowClass = 'pList_rowOdd';
					
					$assignedStr = '<input type="checkbox" name="chk'.$rN.'" id="chk'.$rN.'" value="'.$proposals[$i]["id"].'" onClick="updateSchedule(this)"';
					if($proposals[$i]["session"] == $thisSession["id"]) $assignedStr .= ' checked="true"';
					$assignedStr .= ' />';
					$otherSession = 0;
				}
				
				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="50" valign="center" align="center" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"><?php echo $assignedStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="450" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="checkBox('<?php echo $rN; ?>')"><?php echo $proposals[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="checkBox('<?php echo $rN; ?>')"><?php echo $presStr; ?></td>
		</tr>
<?php

				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">&nbsp;</th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">CHANGES NOT SAVED!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input type="button" value="Save Changes" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="schedForm" id="schedForm" method="post" action="">
		<input type="hidden" name="scheduledProposals" id="scheduledProposals" value="" />
		<input type="hidden" name="scheduledSession" id="scheduledSession" value="<?php echo $thisSession["id"]; ?>" />
	</form>
<?php	

		/******************************************************************************
		 *                                                                            *
		 *                                   CLASSICS                                 * 
		 *                                                                            *
		 ******************************************************************************/
		
		} else if($thisSession["event"] == "Technology Fair Classics") {
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_assigned {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
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
		
		td.pList_assigned_highlighted {
			background-color: #006600;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_assigned_other_highlighted {
			background-color: #660000;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.pList_assigned_other {
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

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
		
		td.stationTD {
			background-color: #FFFFFF;
			text-align: center;
		}
		
		td.stationTD_assigned {
			background-color: #CCFFCC;
			text-align: center;
		}
		
		td.stationTD_assignedTwice {
			background-color: #FFFFCC;
			text-align: center;
		}
		
		td.stationTD_assignedError {
			background-color: #FFCCCC;
			text-align: center;
		}
	</style>
	<script type="text/javascript">
		var schedule = new Array();
		
		var stations = new Array();
<?php
			for($y = 0; $y < count($stations); $y++) {
?>
		stations[<?php echo $y; ?>] = new Array();
		stations[<?php echo $y; ?>]['id'] = '<?php echo $stations[$y]["id"]; ?>';
		stations[<?php echo $y; ?>]['name'] = '<?php echo $stations[$y]["name"]; ?>';
		stations[<?php echo $y; ?>]['count'] = 0;
<?php
			}
?>


		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_assigned') cEl.className = 'pList_assigned_highlighted';
					else if(cEl.className == 'pList_assigned_other') cEl.className = 'pList_assigned_other_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_assigned_highlighted') cEl.className = 'pList_assigned';
					else if(cEl.className == 'pList_assigned_other_highlighted') cEl.className = 'pList_assigned_other';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function updateSchedule(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				var sM = document.getElementById('saveMsg');
				sM.style.visibility = '';
			
				//When a station is selected, we need to save the proposal id to that station in the array
				//We also need to make sure that the proposal is only saved in one station
				//First, get the proposal id
				var rN = el.id.substring(7,el.id.length);
				var tP = document.getElementById('propID' + rN).value; //gets the proposal ID for that row
				var rEl = document.getElementById('row' + rN);
				
				//Next, check to see if this proposal is already assigned to any station
				for(i = 0; i < schedule.length; i++) {
					if(schedule[i]['proposal'] == tP) { //the proposal is assigned to this station
						schedule.splice(i,1); //clear it from that station
						
						for(c = 0; c < rEl.cells.length; c++) {
							if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
							else rEl.cells[c].className = 'pList_rowOdd';
						}
					}
				}
				
				//Next, assign it to the correct station
				var tS = el.options[el.selectedIndex].value; //gets the ID of the station
				if(tS != '') { //only set if a station was selected
					var sI = schedule.length;
					schedule[sI] = new Array();
					schedule[sI]['proposal'] = tP;
					schedule[sI]['station'] = tS;
					for(c = 0; c < rEl.cells.length; c++) {
						rEl.cells[c].className = 'pList_assigned_highlighted';
					}
				}
				
				//Finally, update the counts
				var sCount = 0;
				var rCount = schedule.length;
				for(i = 0; i < schedule.length; i++) {
					if(schedule[i]['proposal'] != '') {
						sCount++;
					}
				}
				
				document.getElementById('schedNum').innerHTML = sCount;
			} else {
				//When the page first loads, we need to update the schedule array with the correct values
				//which are in the select elements (as set by the PHP script)
				var totalRows = document.getElementById('propTable').rows.length - 1;
				var sCount = 0;
			
				for(i = 0; i < totalRows; i++) {
					var tSEl = document.getElementById('station' + i);
					if(tSEl != null) {
						if(tSEl.selectedIndex > 0) { //this proposal is scheduled for this session
							var tS = tSEl.options[tSEl.selectedIndex].value;
							var tP = document.getElementById('propID' + i).value;
							var sI = schedule.length;
							schedule[sI] = new Array();
							schedule[sI]['proposal'] = tP;
							schedule[sI]['station'] = tS;
							var rEl = document.getElementById('row' + i);
							for(c = 0; c < rEl.cells.length; c++) {
								rEl.cells[c].className = 'pList_assigned';
							}
															
							sCount++;
						}
					}
				}
			
				document.getElementById('schedNum').innerHTML = sCount;
			}
			
			showStations();
		}
		
		function showStations() {
			//First, reset the stations array
			for(k = 0; k < stations.length; k++) {
				stations[k]['count'] = 0;
			}
			
			//Next, update the station count from the schedule array
			for(i = 0; i < schedule.length; i++) {
				var tS = schedule[i]['station'];
				for(j = 0; j < stations.length; j++) {
					if(stations[j]['id'] == tS) stations[j]['count']++;
				}
			}
			
			//Next, update the colors on teh station TDs
			for(l = 0; l < stations.length; l++) {
				var tS = stations[l]['id'];
				var sTD = document.getElementById('sTD' + tS);
				if(stations[l]['count'] == 0) sTD.className = 'stationTD';
				else if(stations[l]['count'] == 1) sTD.className = 'stationTD_assigned';
				else if(stations[l]['count'] == 2) sTD.className = 'stationTD_assignedTwice';
				else sTD.className = 'stationTD_assignedError';
			}
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
		
		function saveChanges() {
			var s = '';
			for(i = 0; i < schedule.length; i++) {
				s += schedule[i]['station'] + '|';
				if(schedule[i]['proposal'] != '') s += schedule[i]['proposal'];
				else s += '0';
				if(i < (schedule.length - 1)) s += '||';
			}
			
			document.getElementById('scheduledProposals').value = s;
			document.getElementById('schedForm').submit();
		}
		
		function editSession(n) {
			window.location.href = 'scheduleSession.php?s=' + n;
		}
		
		window.onload = function() {
			updateSchedule();
		};
		
		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 11pt">
					<strong>Session Information:</strong>
					<span style="font-style: italic; padding-left: 20px">Title:</span> <?php echo $thisSession["title"]; ?>
<?php
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
			$tmpDate = explode("-",$thisSession["date"]);
			$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
?>
					<span style="padding-left: 20px; font-style: italic">Date:</span> <?php echo $dateStr; ?>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			if($tmpSHour < 12) $sAMPM = "AM";
			else {
				$sAMPM = "PM";
				if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
			}
			$tmpSMinutes = $tmpStart[1];
				
			$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			if($tmpEHour < 12) $eAMPM = "AM";
			else {
				$eAMPM = "PM";
				if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
			}
			$tmpEMinutes = $tmpEnd[1];
				
			$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
					<span style="padding-left: 20px; font-style: italic">Time:</span> <?php echo $timeStr; ?>
				</td>
			</tr>
		</table>
		<table width="800" border="0" align="center" style="border-bottom: solid 1px #AAAAAA">
			<tr>
				<td width="100%" align="center">Scheduled: <span id="schedNum">0</span></td>
			</tr>
			<tr>
				<td>
					<table border="0" width="100%">
						<tr>
<?php
			$tdWidth = floor(800 / (count($stations) / 2));
			for($z = 0; $z < count($stations); $z++) {
				if($z == (floor(count($stations) / 2))) {
?>
						</tr>
						<tr>
<?php
				}
?>
							<td id="sTD<?php echo $stations[$z]["id"]; ?>" class="stationTD" width="<?php echo $tdWidth; ?>"><?php echo $stations[$z]["name"]; ?></td>
<?php
			}
?>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';

				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];

				if(strpos($proposals[$i]["session"],"|") !== false) { //this proposal is assigned to a different session
					$rowClass = "pList_assigned_other";
					
					$tmp = explode("|",$proposals[$i]["session"]);
					$tmpDate = explode("-",$tmp[0]);
					$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					
					$tmpTime = explode("-",$tmp[1]);
					$tmpStart = explode(":",$tmpTime[0]);
					$tmpSHour = intval($tmpStart[0]);
					if($tmpSHour < 12) $sAMPM = "AM";
					else {
						$sAMPM = "PM";
						if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
					}
					$tmpSMinutes = $tmpStart[1];
				
					$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
					$tmpEnd = explode(":",$tmpTime[1]);
					$tmpEHour = intval($tmpEnd[0]);
					if($tmpEHour < 12) $eAMPM = "AM";
					else {
						$eAMPM = "PM";
						if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
					}
					$tmpEMinutes = $tmpEnd[1];
				
					$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
					
					for($z = 0; $z < count($stations); $z++) {
						if($proposals[$i]["station"] == $stations[$z]["id"]) {
							$stationStr = $stations[$z]["name"];
							break;
						}
					}
					
					$assignedStr = '<span style="font-size: .6em">'.$dateStr.'<br />'.$timeStr.'<br />'.$stationStr.'</span>';
					$otherSession = $tmp[2];
				} else { //this proposals is assigned to this session or not assigned to any session
					$assignedStr = '<select name="station'.$rN.'" id="station'.$rN.'" onChange="updateSchedule(this)"><option value="">--</option>';
					for($z = 0; $z < count($stations); $z++) {
						$isChecked = false;					
						if($proposals[$i]["session"] == $thisSession["id"]) { //this proposal is scheduled for this session
							if($proposals[$i]["station"] == $stations[$z]["id"]) { //this station is assigned for this proposal
								$isChecked = true;
							}
						}
						
						if($isChecked) $assignedStr .= '<option value="'.$stations[$z]['id'].'" selected="true">'.$stations[$z]['name'].'</option>';
						else $assignedStr .= '<option value="'.$stations[$z]['id'].'">'.$stations[$z]['name'].'</option>';
					}
					
					$assignedStr .= '</select>';
					$otherSession = 0;
				}
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="300" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><input type="hidden" name="propID<?php echo $rN; ?>" id="propID<?php echo $rN; ?>" value="<?php echo $proposals[$i]["id"]; ?>" /><?php echo stripslashes($proposals[$i]['title']); ?></td>
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo $presStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="100" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo $assignedStr; ?></td>
			</td>
		</tr>
<?php

				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
				<th class="pList">Assigned Station</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">CHANGES NOT SAVED!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input type="button" value="Save Changes" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="schedForm" id="schedForm" method="post" action="">
		<input type="hidden" name="scheduledProposals" id="scheduledProposals" value="" />
		<input type="hidden" name="scheduledSession" id="scheduledSession" value="<?php echo $thisSession["id"]; ?>" />
	</form>

<?php		
		} else if($thisSession["event"] == "Technology Fairs (Classics)") {
			//First, get the presenters information
			$cpStmt = $db->prepare("SELECT `ID`, `First Name`, `Last Name` FROM `classics_presenters` WHERE 1");
			$cpStmt->execute();
			$cpStmt->bind_result($cpID,$cpFN,$cpLN);
			$presenters = array();
			while($cpStmt->fetch()) {
				$presenters[] = array(
					"id" => $cpID,
			 		"first_name" => $cpFN,
			 		"last_name" => $cpLN
				);
			}
			$cpStmt->close();
				 
			//Now, get the proposals information
			$cStmt = $db->prepare("SELECT `id`,`presenters`,`title`,`summary` FROM `classics_proposals` WHERE 1");
			$cStmt->execute();
			$cStmt->bind_result($cID,$cPres,$cTitle,$cSummary);
			$proposals = array();
			while($cStmt->fetch()) {
				$proposals[] = array(
					"id" => $cID,
			 		"presenters" => $cPres,
			 		"title" => $cTitle,
			 		"summary" => $cSummary,
					"session" => 0,
					"station" => ""
			 	);
			}
			$cStmt->close();

			//get the information for all the sessions
			$sessions = getSessions();
			
			//Now, build the schedule array
			$schedule = array();
			for($i = 0; $i < count($sessions); $i++) {
				if($sessions[$i]["presentations"] != "") {
					$tmpSes = explode("||",$sessions[$i]["presentations"]);
					for($j = 0; $j < count($tmpSes); $j++) {
						$tmpSched = explode("|",$tmpSes[$j]);
						if(count($tmpSched) == 1) { //no station id included, only a proposal id
							$tmpSched[1] = $tmpSched[0];
							$tmpSched[0] = "";
						}
					
						$schedule[] = array(
							"session_id" => $sessions[$i]["id"],
							"proposal_id" => $tmpSched[1],
							"station_id" => $tmpSched[0]
						);
					}
				}
			}
		
			//get the stations
			$stations = getStations();

			//update the proposals array with the presenters information
			for($p = 0; $p < count($proposals); $p++) {
				//First, update the presenters
				$tmpPres = explode("|",$proposals[$p]["presenters"]);
				$presStr = "";
				for($j = 0; $j < count($tmpPres); $j++) {
					for($k = 0; $k < count($presenters); $k++) {
						if($presenters[$k]["id"] == $tmpPres[$j]) {
							$presStr .= $presenters[$k]["first_name"]." ".$presenters[$k]["last_name"];
							if($j < (count($tmpPres) - 1)) $presStr .= "|";
							break;
						}
					}
				}
				$proposals[$p]["presenters"] = $presStr;
			
				//Second, update the schedule information
				for($l = 0; $l < count($schedule); $l++) {
					if($schedule[$l]["proposal_id"] == $proposals[$p]["id"]) { //proposal is scheduled
						if($schedule[$l]["session_id"] != $thisSession["id"]) { //this proposal is assigned to a different session
							//get the other session information
							for($ses = 0; $ses < count($sessions); $ses++) {
								if($sessions[$ses]["id"] == $schedule[$l]["session_id"]) { //get the session information
									$proposals[$p]["session"] = $sessions[$ses]["date"]."|".$sessions[$ses]["time"]."|".$sessions[$ses]["id"];
									break;
								}
							}
						} else $proposals[$p]["session"] = $schedule[$l]["session_id"];
						for($m = 0; $m < count($stations); $m++) {
							if($schedule[$l]["station_id"] == $stations[$m]["id"]) {
								$proposals[$p]["station"] = $stations[$m]["id"];
								break;
							}
						}
					
						break;
					}
				}
			}		
?>
	<style type="text/css">
		th.pList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.pList_assigned {
			background-color: #CCFFCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
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
		
		td.pList_assigned_highlighted {
			background-color: #006600;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
		
		td.pList_assigned_other_highlighted {
			background-color: #660000;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.pList_assigned_other {
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

		#footer {
			position: fixed;
			bottom: 0;
			left: 0;
			background-color: #FFFFFF;
			width: 100%;
			height: 50px;
		}
		
		#saveMsg {
			font-weight: bold;
			color: red;
			font-size: 16pt;
		}

		#propTableDiv {
			padding-bottom: 50px;
			overflow: auto;
		}
		
		td.stationTD {
			background-color: #FFFFFF;
			text-align: center;
		}
		
		td.stationTD_assigned {
			background-color: #CCFFCC;
			text-align: center;
		}
	</style>
	<script type="text/javascript">
		var schedule = new Array();
		
<?php
			for($y = 0; $y < count($stations); $y++) {
?>
		schedule[<?php echo $y; ?>] = new Array();
		schedule[<?php echo $y; ?>]['station'] = '<?php echo $stations[$y]["id"]; ?>';
		schedule[<?php echo $y; ?>]['proposal'] = '';
<?php
			}
?>

		function highlightRow(r,n) {
			var rEl = document.getElementById('row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) {
					if(cEl.className == 'pList_assigned') cEl.className = 'pList_assigned_highlighted';
					else if(cEl.className == 'pList_assigned_other') cEl.className = 'pList_assigned_other_highlighted';
					else cEl.className = 'pList_highlighted';
				} else if(n == 0) {
					if(cEl.className == 'pList_assigned_highlighted') cEl.className = 'pList_assigned';
					else if(cEl.className == 'pList_assigned_other_highlighted') cEl.className = 'pList_assigned_other';
					else {
						if(parseInt(r) % 2 == 0) cEl.className = 'pList_rowEven';
						else cEl.className = 'pList_rowOdd';
					}
				}
			}
		}
		
		function updateSchedule(el) {
			if(el != null || el != undefined) { //an element was changed vs. the initial update
				var sM = document.getElementById('saveMsg');
				sM.style.visibility = '';
			
				//When a station is selected, we need to save the proposal id to that station in the array
				//We also need to make sure that the proposal is only saved in one station
				//First, get the proposal id
				var rN = el.id.substring(7,el.id.length);
				//alert(rN);
				var tP = document.getElementById('propID' + rN).value; //gets the proposal ID for that row
				var rEl = document.getElementById('row' + rN);
				
				//Next, check to see if this proposal is already assigned to any station
				for(i = 0; i < schedule.length; i++) {
					if(schedule[i]['proposal'] == tP) { //the proposal is assigned to this station
						schedule[i]['proposal'] = ''; //clear it from that station
						
						var cS = parseInt(i) + parseInt(1);
						document.getElementById('sTD' + cS).className = 'stationTD';
						for(c = 0; c < rEl.cells.length; c++) {
							if(rN % 2 == 0) rEl.cells[c].className = 'pList_rowEven';
							else rEl.cells[c].className = 'pList_rowOdd';
						}
					}
				}
				
				//Next, assign it to the correct station
				var tS = el.options[el.selectedIndex].value; //gets the ID of the station
				if(tS != '') { //only set if a station was selected
					var sI = tS - 1;

					//check to see if something else is already assigned to this station
					if(schedule[sI]['proposal'] != '') {
						alert('There is already a presentation assigned to this station!');
						el.selectedIndex = 0;
						return false;
					} else {
						schedule[sI]['proposal'] = tP;
						document.getElementById('sTD' + tS).className = 'stationTD_assigned';
						for(c = 0; c < rEl.cells.length; c++) {
							rEl.cells[c].className = 'pList_assigned_highlighted';
						}
					}
				}
				
				//Finally, update the counts
				var sCount = 0;
				var rCount = schedule.length;
				for(i = 0; i < schedule.length; i++) {
					if(schedule[i]['proposal'] != '') {
						sCount++;
						rCount--;
					}
				}
				
				document.getElementById('schedNum').innerHTML = sCount;
				document.getElementById('remainingNum').innerHTML = rCount;
				
				return;
			} else {
				//When the page first loads, we need to update the schedule array with the correct values
				//which are in the select elements (as set by the PHP script)
				var totalRows = document.getElementById('propTable').rows.length - 1;
				var sCount = 0;
				var rCount = schedule.length;
			
				for(i = 0; i < totalRows; i++) {
					var tSEl = document.getElementById('station' + i);
//					alert('Row: ' + i + '\ntSEl: ' + tSEl);
					if(tSEl != null) {
						if(tSEl.selectedIndex > 0) { //this proposal is scheduled for this session
							var tS = tSEl.options[tSEl.selectedIndex].value;
							var tP = document.getElementById('propID' + i).value;
							for(s = 0; s < schedule.length; s++) {
								if(schedule[s]['station'] == tS) {
									//see if this station is already occupied
									if(schedule[s]['proposal'] != '' && schedule[s]['proposal'] != tP) {
										alert('There is already a proposal scheduled at this station!');
										tSEl.selectedIndex = 0;
										return false;
									} else {
										schedule[s]['proposal'] = tP;
										document.getElementById('sTD' + tS).className = 'stationTD_assigned';
										var rEl = document.getElementById('row' + i);
										for(c = 0; c < rEl.cells.length; c++) {
											rEl.cells[c].className = 'pList_assigned';
										}
															
										sCount++;
										rCount--;
									}
							
									break;
								}
							}
						}
					}
				}
			
				document.getElementById('schedNum').innerHTML = sCount;
				document.getElementById('remainingNum').innerHTML = rCount;
				return;
			}
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
		
		function saveChanges() {
			var s = '';
			for(i = 0; i < schedule.length; i++) {
				s += schedule[i]['station'] + '|';
				if(schedule[i]['proposal'] != '') s += schedule[i]['proposal'];
				else s += '0';
				if(i < (schedule.length - 1)) s += '||';
			}
			
			document.getElementById('scheduledProposals').value = s;
			document.getElementById('schedForm').submit();
		}
		
		function editSession(n) {
			window.location.href = 'scheduleSession.php?s=' + n;
		}
		
		window.onload = function() {
			updateSchedule();
		};
		
		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td style="font-size: 11pt">
					<strong>Session Information:</strong>
					<span style="font-style: italic; padding-left: 20px">Title:</span> <?php echo $thisSession["title"]; ?>
<?php
			$months = array('','January','February','March','April','May','June','July','August','September','October','November','December');
			$tmpDate = explode("-",$thisSession["date"]);
			$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
?>
					<span style="padding-left: 20px; font-style: italic">Date:</span> <?php echo $dateStr; ?>
<?php
			$tmpTime = explode("-",$thisSession["time"]);
			$tmpStart = explode(":",$tmpTime[0]);
			$tmpSHour = intval($tmpStart[0]);
			if($tmpSHour < 12) $sAMPM = "AM";
			else {
				$sAMPM = "PM";
				if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
			}
			$tmpSMinutes = $tmpStart[1];
				
			$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
			$tmpEnd = explode(":",$tmpTime[1]);
			$tmpEHour = intval($tmpEnd[0]);
			if($tmpEHour < 12) $eAMPM = "AM";
			else {
				$eAMPM = "PM";
				if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
			}
			$tmpEMinutes = $tmpEnd[1];
				
			$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
					<span style="padding-left: 20px; font-style: italic">Time:</span> <?php echo $timeStr; ?>
				</td>
			</tr>
		</table>
		<table width="800" border="0" align="center" style="border-bottom: solid 1px #AAAAAA">
			<tr>
				<td width="50%">Scheduled: <span id="schedNum">0</span></td>
				<td width="50%" align="right">Remaining: <span id="remainingNum"><?php echo count($stations); ?></span></td>
			</tr>
			<tr>
				<td colspan="2">
					<table border="0" width="100%">
						<tr>
<?php
			$tdWidth = floor(800 / (count($stations) / 2));
			for($z = 0; $z < count($stations); $z++) {
				if($z == (floor(count($stations) / 2))) {
?>
						</tr>
						<tr>
<?php
				}
?>
							<td id="sTD<?php echo $stations[$z]["id"]; ?>" class="stationTD" width="<?php echo $tdWidth; ?>"><?php echo $stations[$z]["name"]; ?></td>
<?php
			}
?>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';

				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];

				if(strpos($proposals[$i]["session"],"|") !== false) { //this proposal is assigned to a different session
					$rowClass = "pList_assigned_other";
					
					$tmp = explode("|",$proposals[$i]["session"]);
					$tmpDate = explode("-",$tmp[0]);
					$dateStr = $months[intval($tmpDate[1])]." ".intval($tmpDate[2]).", ".$tmpDate[0];
					
					$tmpTime = explode("-",$tmp[1]);
					$tmpStart = explode(":",$tmpTime[0]);
					$tmpSHour = intval($tmpStart[0]);
					if($tmpSHour < 12) $sAMPM = "AM";
					else {
						$sAMPM = "PM";
						if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
					}
					$tmpSMinutes = $tmpStart[1];
				
					$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
					$tmpEnd = explode(":",$tmpTime[1]);
					$tmpEHour = intval($tmpEnd[0]);
					if($tmpEHour < 12) $eAMPM = "AM";
					else {
						$eAMPM = "PM";
						if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
					}
					$tmpEMinutes = $tmpEnd[1];
				
					$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
					
					for($z = 0; $z < count($stations); $z++) {
						if($proposals[$i]["station"] == $stations[$z]["id"]) {
							$stationStr = $stations[$z]["name"];
							break;
						}
					}
					
					$assignedStr = '<span style="font-size: .6em">'.$dateStr.'<br />'.$timeStr.'<br />'.$stationStr.'</span>';
					$otherSession = $tmp[2];
				} else { //this proposals is assigned to this session or not assigned to any session
					$assignedStr = '<select name="station'.$rN.'" id="station'.$rN.'" onChange="updateSchedule(this)"><option value="">--</option>';
					for($z = 0; $z < count($stations); $z++) {
						$isChecked = false;					
						if($proposals[$i]["session"] == $thisSession["id"]) { //this proposal is scheduled for this session
							if($proposals[$i]["station"] == $stations[$z]["id"]) { //this station is assigned for this proposal
								$isChecked = true;
							}
						}
						
						if($isChecked) $assignedStr .= '<option value="'.$stations[$z]['id'].'" selected="true">'.$stations[$z]['name'].'</option>';
						else $assignedStr .= '<option value="'.$stations[$z]['id'].'">'.$stations[$z]['name'].'</option>';
					}
					
					$assignedStr .= '</select>';
					$otherSession = 0;
				}
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="400" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><input type="hidden" name="propID<?php echo $rN; ?>" id="propID<?php echo $rN; ?>" value="<?php echo $proposals[$i]["id"]; ?>" /><?php echo stripslashes($proposals[$i]['title']); ?></td>
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo $presStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)"<?php if($otherSession != 0) { ?>  onClick="editSession('<?php echo $otherSession; ?>')"<?php } ?>><?php echo $assignedStr; ?></td>
			</td>
		</tr>
<?php

				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
				<th class="pList">Assigned Station</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
	<div id="footer">
		<table id="saveMsg" border="0" align="center" style="visibility: hidden">
			<tr>
				<td align="center" valign="center" style="font-weight: bold; color: red; font-size: 16pt; height: 50px">CHANGES NOT SAVED!</td>
				<td align="center" valign="center" style="padding-left: 20px"><input type="button" value="Save Changes" onClick="saveChanges()" /></td>
			</tr>
		</table>
	</div>
	<form name="schedForm" id="schedForm" method="post" action="">
		<input type="hidden" name="scheduledProposals" id="scheduledProposals" value="" />
		<input type="hidden" name="scheduledSession" id="scheduledSession" value="<?php echo $thisSession["id"]; ?>" />
	</form>

<?php		
		/******************************************************************************
		 *                                                                            *
		 *                                   OTHER                                    * 
		 *                                                                            *
		 ******************************************************************************/
		
		} else {
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
			overflow: auto;
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
		
		function editOther(n) {
			window.location.href = 'editOther.php?id=' + n + '&s=<?php echo $sesID; ?>';
		}

		window.onscroll = function() {
			checkHeader();
		};
	</script>
	<p align="center"><a href="scheduleSession.php">Back to Session List</a></p>
	<p align="center"><a href="addOther.php?s=<?php echo $thisSession["id"]; ?>">Add a Presentation</a></p>
	<div id="headerDiv">
		<table border="0" align="center" cellpadding="5" cellspacing="0">
			<tr>
				<td colspan="2"><strong>Session Information:</strong></td>
			</tr>
			<tr>
				<td style="padding-left: 20px; font-weight: bold;">Title:</td>
				<td><?php echo $thisSession['title']; ?></td>
			</tr>
<?php
				$dateStr = date("F j, Y", strtotime($thisSession['date']));
?>
			<tr>
				<td style="padding-left: 20px; font-weight: bold">Date:</td>
				<td><?php echo $dateStr; ?></td>			
			</tr>
<?php
				$tmpTime = explode("-",$thisSession["time"]);
				$tmpStart = explode(":",$tmpTime[0]);
				$tmpSHour = intval($tmpStart[0]);
				if($tmpSHour < 12) $sAMPM = "AM";
				else {
					$sAMPM = "PM";
					if($tmpSHour > 12) $tmpSHour = $tmpSHour - 12;
				}
				$tmpSMinutes = $tmpStart[1];
				
				$timeStr = $tmpSHour.":".$tmpSMinutes." ".$sAMPM;
			
				$tmpEnd = explode(":",$tmpTime[1]);
				$tmpEHour = intval($tmpEnd[0]);
				if($tmpEHour < 12) $eAMPM = "AM";
				else {
					$eAMPM = "PM";
					if($tmpEHour > 12) $tmpEHour = $tmpEHour - 12;
				}
				$tmpEMinutes = $tmpEnd[1];
				
				$timeStr .= " to ".$tmpEHour.":".$tmpEMinutes." ".$eAMPM;
?>
			<tr>
				<td style="padding-left: 20px; font-weight: bold">Time:</td>
				<td><?php echo $timeStr; ?></td>			
			</tr>
		</table>
	</div>
<?php
			ob_start();
			$rN = 0;
			for($i = 0; $i < count($proposals); $i++) {
				if($rN % 2 == 0) $rowClass = 'pList_rowEven';
				else $rowClass = 'pList_rowOdd';
?>
		<tr id="row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="250" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="editOther('<?php echo $proposals[$i]["id"]; ?>')"><?php echo $proposals[$i]['title']; ?></td>
<?php
				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				if(count($tmpPres) > 1) {
					$presStr = "<ol>";
					for($t = 0; $t < count($tmpPres); $t++) {
						$presStr .= "<li>".$tmpPres[$t]."</li>";
					}
					$presStr .= "</ol>";
				} else $presStr = $tmpPres[0];
?>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $rN; ?>',0)" onClick="editOther('<?php echo $proposals[$i]["id"]; ?>')"><?php echo $presStr; ?></td>
<?php
				$rN++;
			}
		
			$rows = ob_get_contents();
			ob_end_clean();
?>
	<div id="propTableDiv">
		<table id="propTable" border="0" align="center" cellpadding="5" width="800">
			<tr>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
			</tr>
<?php			
			echo $rows;
?>
		</table>
	</div>
<?php
		}
		
		include "adminBottom.php";
		exit();
	} 
		
	//Get the event type
	$eTypes = array();
	foreach($events AS $evt) {
		if(strpos($_SESSION['user_role'], $evt['adminSuffix']) !== false) $eTypes[] = $evt['event'];
		else if($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'chair') {
			if(!isset($eTypes[$evt['event']])) $eTypes[] = $evt['event'];
		}
	}
	
	$eTypes[] = ''; // add an empty one for the Special Sessions, which have a NULL event
	
	//get the sessions
	$sStmt = $db->prepare("SELECT s.id, l.name AS location, s.date, s.time, e.event AS event, s.title, s.presentations FROM sessions AS s LEFT JOIN events AS e ON e.id = s.event LEFT JOIN locations AS l ON l.id = s.location ORDER BY s.date, s.time");
	$sStmt->execute();
	$sStmt->bind_result($id, $location, $date, $time, $event, $title, $presentations);
	
	$sessions = array();
	while($sStmt->fetch()) {
		$sessions[] = array(
			"id" => $id,
			"date" => $date,
			"time" => $time,
			"event" => $event,
			"title" => $title,
			"presentations" => $presentations
		);
	}
	
	$sStmt->close();
	
	//now, build the schedule array
	$schedule = array();
	for($i = 0; $i < count($sessions); $i++) {
		if($sessions[$i]["presentations"] != "" && $sessions[$i]["presentations"] != NULL) {
			$tmpS = explode("||",$sessions[$i]["presentations"]);
			for($j = 0; $j < count($tmpS); $j++) {
				if(strpos($tmpS[$j],"|") !== false) {
					list($tsID,$tpID) = explode("|",$tmpS[$j]);
					$schedule[] = array(
						"session_id" => $sessions[$i]["id"],
						"proposal_id" => $tpID,
						"station" => $tsID
					);
				} else {
					$schedule[] = array(
						"session_id" => $sessions[$i]["id"],
						"proposal_id" => $tmpS[$j],
						"station" => ""
					);
				}
			}
		}
	}
	
	include "adminTop.php";
?>
	<style type="text/css">
		th.sList {
			background-color: #333333;
			color: #FFFFFF;
			font-size: .85em;
			text-align: left;
		}
		
		td.sList_rowEven {
			background-color: #FFFFFF;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}

		td.sList_rowOdd {
			background-color: #CCCCCC;
			color: #000000;
			font-size: .85em;
			text-align: left;
			vertical-align: top;
			cursor: hand;
			cursor: pointer;
		}
				
		td.sList_highlighted {
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
			var rEl = document.getElementById('session' + e + '_row' + r);
			for(i = 0; i < rEl.cells.length; i++) {
				var cEl = rEl.cells[i];
				if(n == 1) cEl.className = 'sList_highlighted';
				else if(n == 0) {
					if(parseInt(r) % 2 == 0) cEl.className = 'sList_rowEven';
					else cEl.className = 'sList_rowOdd';
				}
			}
		}
		
		function editSchedule(n) {
			window.location.href = 'scheduleSession.php?s=' + n;
		}
	</script>
	<p align="left">Click on a session to select or change the presentations that will happen during that particular session.<?php if($_SESSION['user_role'] == 'admin' || $_SESSION['user_role'] == 'chair') { ?> To edit the time, date, or title of a session, please go to the <a href="sessionList.php">Session List</a>.<?php } ?></p>
<?php
	for($e = 0; $e < count($eTypes); $e++) {
		ob_start();
		$rN = 0;
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["event"] == $eTypes[$e]) { //list this session
				if($rN % 2 == 0) $rowClass = 'sList_rowEven';
				else $rowClass = 'sList_rowOdd';
				
				//get the number of presentations scheduled for this session
				$pCount = 0;
				for($p = 0; $p < count($schedule); $p++) {
					if($schedule[$p]["session_id"] == $sessions[$i]["id"] && $schedule[$p]["proposal_id"] != 0) $pCount++;
				}
					
				$dateStr = date("F j, Y", strtotime($sessions[$i]["date"]));
					
				$tmpTime = explode("-",$sessions[$i]["time"]);
				$sStart = strtotime($sessions[$i]["date"]." ".$tmpTime[0]);
				$sEnd = strtotime($sessions[$i]["date"]." ".$tmpTime[1]);
				$timeStr = date("g:i A", $sStart)." to ".date("g:i A", $sEnd);
?>
		<tr id="session<?php echo $e; ?>_row<?php echo $rN; ?>">
			<td class="<?php echo $rowClass; ?>" width="400" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="editSchedule('<?php echo $sessions[$i]['id']; ?>')"><?php echo $sessions[$i]['title']; ?></td>
			<td class="<?php echo $rowClass; ?>" width="100" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="editSchedule('<?php echo $sessions[$i]['id']; ?>')"><?php echo $dateStr; ?></td>
			<td class="<?php echo $rowClass; ?>" width="150" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="editSchedule('<?php echo $sessions[$i]['id']; ?>')"><?php echo $timeStr; ?></td>
			<td class="<?php echo $rowClass; ?>" style="text-align: center" width="150" onMouseOver="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',1)" onMouseOut="highlightRow('<?php echo $e; ?>','<?php echo $rN; ?>',0)" onClick="editSchedule('<?php echo $sessions[$i]['id']; ?>')"><?php echo $pCount; ?></td>
		</tr>
<?php

				$rN++;
			}
		}
		
		$rows = ob_get_contents();
		ob_end_clean();
		
		if($eTypes[$e] == '') $eventName = "Special Sessions";
		else $eventName = $eTypes[$e];
?>
	<table border="0" align="center" cellpadding="5" width="800">
		<tr>
			<td colspan="3"><?php echo $eventName; ?> (Total #: <?php echo $rN; ?>)</td>
		</tr>
		<tr>
			<th class="sList">Title</td>
			<th class="sList">Date</td>
			<th class="sList">Time</td>
			<th class="sList" style="text-align: center"># of Presentations</td>
		</tr>
<?php			
		echo $rows;
?>
	</table><br /><br />
<?php
	}

	include "adminBottom.php";
	
	function getSessions($sID = 0) {
		global $db;
		
		$sQry = "SELECT s.id, l.name AS location, s.date, s.time, e.event AS event, e.id AS eventID, s.title, s.presentations FROM sessions AS s LEFT JOIN events AS e ON e.id = s.event LEFT JOIN locations AS l ON l.id = s.location";
		if($sID > 0) $sQry .= " WHERE s.id = '".$sID."'";		
		$sQry .= " ORDER BY s.date, s.time";

		//get the sessions
		$sStmt = $db->prepare($sQry);
		echo $db->error;
		
		$sStmt->execute();
		$sStmt->bind_result($id, $location, $date, $time, $event, $eventID, $title, $presentations);
	
		$sessions = array();
		while($sStmt->fetch()) {
			$sessions[] = array(
				"id" => $id,
				"date" => $date,
				"time" => $time,
				"event" => $event,
				"eventID" => $eventID,
				"title" => $title,
				"presentations" => $presentations
			);
		}
		
		$sStmt->close();
		
		return $sessions;
	}
	
	function getStations() {
		global $db;
		
		//get the stations
		$stStmt = $db->prepare("SELECT `id`,`name` FROM `stations` WHERE 1");
		$stStmt->execute();
		$stStmt->bind_result($stID, $stName);
		$stations = array();
		while($stStmt->fetch()) {
			$stations[] = array(
				"id" => $stID,
				"name" => $stName
			);
		}
		
		$stStmt->close();
		
		return $stations;		
	}
	
	function getSchedule() {
		global $db;
		
		$sessions = getSessions();
		
		//Now, build the schedule array
		$schedule = array();
		for($i = 0; $i < count($sessions); $i++) {
			if($sessions[$i]["presentations"] != "") {
				$tmpSes = explode("||",$sessions[$i]["presentations"]);
				for($j = 0; $j < count($tmpSes); $j++) {
					$tmpSched = explode("|",$tmpSes[$j]);
					if(count($tmpSched) == 1) { //no station id included, only a proposal id
						$tmpSched[1] = $tmpSched[0];
						$tmpSched[0] = "";
					}
					
					$schedule[] = array(
						"session_id" => $sessions[$i]["id"],
						"session_event" => $sessions[$i]["event"],
						"proposal_id" => $tmpSched[1],
						"station_id" => $tmpSched[0]
					);
				}
			}
		}
		
		return $schedule;
	}
	
	function getPresenters($type = '') {
		global $db;
		
		if($type == 'classics') $prTable = 'classics_presenters';
		else if($type == 'other') $prTable = 'other_presenters';
		else $prTable = 'presenters';
		
		//get the presenters information
		$prStmt = $db->prepare("SELECT `ID`,`First Name`,`Last Name` FROM `".$prTable."` WHERE 1");
		$prStmt->execute();
		$prStmt->bind_result($prID, $prFirstName, $prLastName);
		$presenters = array();
		while($prStmt->fetch()) {
			$presenters[] = array(
				"id" => $prID,
				"first_name" => $prFirstName,
				"last_name" => $prLastName
			);
		}
		
		$prStmt->close();
		
		return $presenters;
	}
	
	function getProposals($eventID, $sesID, $status = 'accepted', $type = '') {
		global $db, $events;
		
		// If the event solicits proposals, we need to use the "proposals" table. Otherwise, we need to use the Classics or Other proposals tables
		foreach($events AS $e) {
			if($e["id"] == $eventID) {
				$event = $e['event'];
				$propTable = $e['propTable'];
				break;
			}
		}
		
		$proposals = array();
		if($propTable == 'proposals') {
			$pStmt = $db->prepare("SELECT id, title, presenters, times, topics, computer, comments, type, status, confirmed FROM proposals WHERE 1");
			$pStmt->execute();
			$pStmt->bind_result($pID, $pTitle, $pPres, $pTimes, $pTopics, $pComputer, $pComments, $pType, $pStatus, $pConfirmed);
			while($pStmt->fetch()) {
				if($pType == $event && $pStatus == $status) {
					$proposals[] = array(
						"id" => $pID,
						"type" => $pType,
						"title" => $pTitle,
						"presenters" => $pPres,
						"times" => $pTimes,
						"topics" => $pTopics,
						"computer" => $pComputer,
						"comments" => $pComments,
						"session" => 0,
						"station" => ""
					);
				}
			}
			
			$pStmt->close();
		
			//get the presenters information
			$presenters = getPresenters();
			
			// get the schedule information
			$schedule = getSchedule();
			
			// get the sesions information
			$sessions = getSessions();
			
			// get the stations information
			$stations = getStations();
			
			//update the proposals array with the presenters information
			for($p = 0; $p < count($proposals); $p++) {
				//First, update the presenters
				$tmpPres = explode("|",$proposals[$p]["presenters"]);
				$presStr = "";
				for($j = 0; $j < count($tmpPres); $j++) {
					for($k = 0; $k < count($presenters); $k++) {
						if($presenters[$k]["id"] == $tmpPres[$j]) {
							$presStr .= $presenters[$k]["first_name"]." ".$presenters[$k]["last_name"];
							if($j < (count($tmpPres) - 1)) $presStr .= "|";
							break;
						}
					}
				}
				
				$proposals[$p]["presenters"] = $presStr;

				//Second, update the proposals array with the schedule information
				for($l = 0; $l < count($schedule); $l++) {
					if($schedule[$l]["proposal_id"] == $proposals[$p]["id"] && $proposals[$p]["type"] == $schedule[$l]["schedule_event"]) { //proposal is scheduled
						if($schedule[$l]["session_id"] != $sesID) { //this proposal is assigned to a different session
							//get the other session information
							for($ses = 0; $ses < count($sessions); $ses++) {
								if($sessions[$ses]["id"] == $schedule[$l]["session_id"]) { //get the session information
									$proposals[$p]["session"] = $sessions[$ses]["date"]."|".$sessions[$ses]["time"]."|".$sessions[$ses]["id"];
									break;
								}
							}
						} else $proposals[$p]["session"] = $schedule[$l]["session_id"];
	
						for($m = 0; $m < count($stations); $m++) {
							if($schedule[$l]["station_id"] == $stations[$m]["id"]) {
								$proposals[$p]["station"] = $stations[$m]["id"];
								break;
							}
						}
				
						break;
					}
				}
			}
		} else if($propTable == 'classics_proposals') {
			//First, get the presenters information
			$presenters = getPresenters('classics');
				 
			//Now, get the proposals information
			$cStmt = $db->prepare("SELECT `id`,`presenters`,`title`,`summary` FROM `classics_proposals` WHERE 1");
			$cStmt->execute();
			$cStmt->bind_result($cID,$cPres,$cTitle,$cSummary);
			while($cStmt->fetch()) {
				$proposals[] = array(
					"id" => $cID,
			 		"presenters" => $cPres,
			 		"title" => $cTitle,
			 		"summary" => $cSummary,
					"session" => 0,
					"station" => ""
			 	);
			}
			
			$cStmt->close();

			// get the information for all the sessions
			$sessions = getSessions('classics');

			// get the schedule information
			$schedule = getSchedule('classics');
			
			//get the stations
			$stations = getStations();

			//update the proposals array with the presenters information
			for($p = 0; $p < count($proposals); $p++) {
				//First, update the presenters
				$tmpPres = explode("|",$proposals[$p]["presenters"]);
				$presStr = "";
				for($j = 0; $j < count($tmpPres); $j++) {
					for($k = 0; $k < count($presenters); $k++) {
						if($presenters[$k]["id"] == $tmpPres[$j]) {
							$presStr .= $presenters[$k]["first_name"]." ".$presenters[$k]["last_name"];
							if($j < (count($tmpPres) - 1)) $presStr .= "|";
							break;
						}
					}
				}

				$proposals[$p]["presenters"] = $presStr;
			
				//Second, update the schedule information
				for($l = 0; $l < count($schedule); $l++) {
					if($schedule[$l]["proposal_id"] == $proposals[$p]["id"]) { //proposal is scheduled
						if($schedule[$l]["session_id"] != $sesID) { //this proposal is assigned to a different session
							//get the other session information
							for($ses = 0; $ses < count($sessions); $ses++) {
								if($sessions[$ses]["id"] == $schedule[$l]["session_id"]) { //get the session information
									$proposals[$p]["session"] = $sessions[$ses]["date"]."|".$sessions[$ses]["time"]."|".$sessions[$ses]["id"];
									break;
								}
							}
						} else $proposals[$p]["session"] = $schedule[$l]["session_id"];

						for($m = 0; $m < count($stations); $m++) {
							if($schedule[$l]["station_id"] == $stations[$m]["id"]) {
								$proposals[$p]["station"] = $stations[$m]["id"];
								break;
							}
						}
					
						break;
					}
				}
			}
		} else if($propTable == 'other_proposals') {
			$thisSession = getSessions($sesID)[0];
			
			//First, get all presentations tied this particular session
			$oStmt = $db->prepare("SELECT `id`,`presenters`,`title`,`summary` FROM `other_proposals`");
			$oStmt->execute();
			$oStmt->bind_result($otherID,$otherPresenters,$otherTitle,$otherSummary);
			
			$sesPres = explode("||",$thisSession["presentations"]);
			
			while($oStmt->fetch()) {
				for($sP = 0; $sP < count($sesPres); $sP++) {
					if(strpos($sesPres[$sP],"|") !== false) {
						$tmpSP = explode("|",$sesPres[$sP]);
						$thisPID = $tmpSP[1];
					} else $thisPID = $sesPres[$sP];

					if($thisPID == $otherID) { //this proposal is scheduled in this session
						$proposals[] = array(
							"id" => $otherID,
							"presenters" => $otherPresenters,
							"title" => $otherTitle,
							"summary" => $otherSummary
						);
						break;
					}
				}
			}
			
			$oStmt->close();	

			//Now, get the presenters information
			$presenters = getPresenters('other');
			
			//Now, update the presenters information in the proposals array
			for($i = 0; $i < count($proposals); $i++) {
				$tmpPres = explode("|",$proposals[$i]["presenters"]);
				$presStr = "";
				for($p = 0; $p < count($tmpPres); $p++) {
					for($j = 0; $j < count($presenters); $j++) {
						if($presenters[$j]["id"] == $tmpPres[$p]) {
							$presStr .= $presenters[$j]["first_name"]." ".$presenters[$j]["last_name"];
							if($p < (count($tmpPres) - 1)) $presStr .= "|";
							break;
						}
					}
				}
		
				$proposals[$i]["presenters"] = $presStr;
			}
		}
		
		return $proposals;
	}
?>