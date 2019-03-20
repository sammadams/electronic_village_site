<?php
	//listProps_classics.php -- allows a lead to view all proposals for their event
	
	$topTitle = "Presentations List";

	include "login.php";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false && strpos($_SESSION['user_role'],"_classics") === false) {
		include "adminTop.php";
?>
		<h3 align="center">You do not have permission to access this page!</h3>	
<?php
		include "adminBottom.php";
		exit();
	}
	
	//Now, get a list of presenters for this event
	//Rather than make repeated calls to the DB for each proposal to find the names of the presenters,
	//we'll get them all in an array and then search that as needed
	$prResult = $db->query("SELECT `ID`,`First Name`,`Last Name` FROM `classics_presenters` WHERE 1");
	$presenters = array();
	while($prRow = $prResult->fetch_assoc()) {
		$presenters[] = array($prRow['ID'],$prRow['First Name']." ".$prRow['Last Name']);
	}
		
	//Now get a list of proposals for this event
	$pStmt = $db->prepare("SELECT `id`, `title`, `presenters`, `confirmed` FROM `classics_proposals` WHERE 1 ORDER BY `id`");
	$pStmt->execute();
	$pStmt->bind_result($pID,$pTitle,$pPres, $pConfirmed);
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
		
		$proposals[] = array($pID,$pTitle,$thisPresenters,$pConfirmed);
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
		
		img.greenCheck, img.redX {
			width: 20px;
			height: 20px;
		}
		
		img.qMark {
			width: 30px;
			height: 30px;
		}
	</style>
	<div id="headerDiv">
		<p align="center" id="BtnPara"><input type="button" value="Add Presentation" onClick="window.location.href='addClassics.php'" /></p>
		<p align="center"><strong>Total:</strong> <span id="totalPropSpan">0</span>
	</div>
	<div id="propTableDiv">
		<table id="propTable" border="0" width="800" cellpadding="5">
			<tr>
				<th class="pList">#</th>
				<th class="pList">Title</th>
				<th class="pList">Presenters</th>
				<th class="pList">Confirmed</th>
			</tr>
		</table>
	</div>
	<script type="text/javascript">		
		var proposals = new Array();
<?php
	for($p = 0; $p < count($proposals); $p++) {
?>

		proposals[<?php echo $p; ?>] = new Array();
		proposals[<?php echo $p; ?>]['id'] = '<?php echo $proposals[$p][0]; ?>';
		proposals[<?php echo $p; ?>]['title'] = '<?php echo addslashes(stripslashes($proposals[$p][1])); ?>';
		proposals[<?php echo $p; ?>]['confirmed'] = '<?php echo $proposals[$p][3]; ?>';
		
		proposals[<?php echo $p; ?>]['presenters'] = new Array();
<?php
		for($a = 0; $a < count($proposals[$p][2]); $a++) {
?>
		proposals[<?php echo $p; ?>]['presenters'][<?php echo $a; ?>] = '<?php echo addslashes(stripslashes($proposals[$p][2][$a])); ?>';
<?php
		}
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
		
		function listProposals() {
			var pTStr = '		<table id="propTable" border="0" width="800" cellpadding="5">\n';
			pTStr += '			<tr>\n';
			pTStr += '				<th class="pList">#</th>\n';
			pTStr += '				<th class="pList">Title</th>\n';
			pTStr += '				<th class="pList" width="200">Presenters</th>\n';
			pTStr += '				<th class="pList" width="50">Confirmed</th>\n';
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
				
				var confirmedStr = '';
				if(proposals[p]['confirmed'] == 'Y') confirmedStr = '<img src="green_check.png" class="greenCheck" />';
				else if(proposals[p]['confirmed'] == 'N') confirmedStr = '<img src="red_x.png" class="redX" />';
				else confirmedStr = '<img src="q_mark.png" class="qMark" />';
				
				pTStr += '				<td class="' + tC + '" style="text-align: center" onMouseOver="highlightRow(\'' + tRN + '\',1)" onMouseOut="highlightRow(\'' + tRN + '\',0)" onClick="viewProp(\'' + tRN + '\')">' + confirmedStr + '</td>\n';
				pTStr += '			</tr>\n';
			}
			
			pTStr += '		</table>';
			document.getElementById('propTableDiv').innerHTML = pTStr;
			document.getElementById('totalPropSpan').innerHTML = proposals.length;
		}
				
		function viewProp(rN) {
			//First, get the ID of the proposal
			var pID = document.getElementById('pN' + rN).value;
			window.location.href = 'editClassics.php?id=' + pID;
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