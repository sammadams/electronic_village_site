<?php
	//addSampleProp.php - allows a user to add a sample proposal to the database
	//accessible only to leads, chairs, and admin users
	
	include_once "login.php";
	$topTitle = "Add Sample Proposal";
	
	if(strpos($_SESSION['user_role'],"admin") === false && strpos($_SESSION['user_role'],"chair") === false && strpos($_SESSION['user_role'],"lead_") === false) {
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	if(isset($_POST["prop_title"])) {
		//Enter the other proposal information into the database
		$q_stmt = $db->prepare("INSERT INTO `sample_proposals` (`id`, `event`, `title`, `summary`, `abstract`, `visible`) VALUES ('0',?,?,?,?,?)");
	
		$q_stmt->bind_param('sssss',strip_tags($_POST['prop_event']), strip_tags($_POST['prop_title']), strip_tags($_POST['prop_summary']), strip_tags($_POST["prop_abstract"]), strip_tags($_POST["prop_visible"]));

		if(!$q_stmt->execute()) {
			echo "Error: ".$q_stmt->error;
			exit();
		}
		
		include "adminTop.php";
?>
	<h3 align="center">Sample proposal added successfully!</h3>
	<p align="center"><a href="addSampleProp.php">Add Another Sample Proposal</a></p>
	<p align="center"><a href="samplePropList.php">Back to Sample Proposal List</a></p>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$evtStmt = $db->prepare("SELECT `id`,`event`,`summaryMaxWords`,`abstractMaxWords` FROM `events` WHERE `isActive` = 'Y'");
	$evtStmt->execute();
	$evtStmt->bind_result($evtID,$evtEvent,$evtSummaryMaxWords,$evtAbstractMaxWords);
	$events = array();
	while($evtStmt->fetch()) {
		$events[] = array(
			"id" => $evtID,
			"event" => $evtEvent,
			"summaryMaxWords" => $evtSummaryMaxWords,
			"abstractMaxWords" => $evtAbstractMaxWords
		);
	}
	
	include "adminTop.php";
?>
	<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}
			
			span.label {
				color: green;
				font-size: 9pt;
				font-style: italic;
			}
			
			span.required {
				color: red;
				font-weight: bold;
			}
			
			#presFormDiv {
				position: absolute;
				height: 500px;
				width: 700px;
				padding: 20px;
				border: solid 2px #000000;
				background-color: #FFFFFF;
				z-index: 2;
			}
			
			#bgDiv {
				position: absolute;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				background-color: #000000;
				opacity: 0.5;
				filter: alpha(opacity=50);
				z-index: 1;
			}			
	</style>
	<script type="text/javascript">
		var summaryMaxWords = 0;
		var abstractMaxWords = 0;
		
		function init() {
			document.getElementById('bgDiv').style.display = 'none';
		}
				
		function countWords(el) {
			//alert(el.id);
			var words = el.value.match(/\S+/g).length;
			if(el.id == 'proposal_abstract') {
				var thisMaxWords = abstractMaxWords;
				var wC = document.getElementById('abstract_total_words');
			} else if(el.id == 'proposal_summary') {
				var thisMaxWords = summaryMaxWords;
				var wC = document.getElementById('summary_total_words');
			}
			
			wC.innerHTML = words;
			if(words > thisMaxWords) wC.style.color = 'red';
			else wC.style.color = 'black';
		}
		
		function checkForm() {
			//Check for a title
			if(document.getElementById('proposal_title').value == '') {
				alert('You did not enter a title for this presentation!');
				document.getElementById('proposal_title').focus();
				return false;
			}
			
			//check to see that a summary was entered
			if(document.getElementById('proposal_summary').value == '') {
				alert('You did not enter a summary for this proposal!');
				document.getElementById('proposal_summary').focus();
				return false;
			}
			
			//check to see that an abstract was entere
			if(document.getElementById('proposal_abstract').value == '') {
				alert('You did not enter an abstract for this proposal!');
				document.getElementById('proposal_abstract').focus();
				return false;
			}

			//IF we get this far, then there are no problems with the data, so we can put the data into the hidden form and submit
			document.getElementById('prop_event').value = document.getElementById('proposal_event').options[document.getElementById('proposal_event').selectedIndex].value;
			document.getElementById('prop_title').value = document.getElementById('proposal_title').value;			
			document.getElementById('prop_summary').value = document.getElementById('proposal_summary').value;
			document.getElementById('prop_abstract').value = document.getElementById('proposal_abstract').value;
			document.getElementById('prop_visible').value = document.getElementById('proposal_visible').options[document.getElementById('proposal_visible').selectedIndex].value;
			document.getElementById('propForm').submit();
		}
		
		function setEvent() {
			var evtEl = document.getElementById('proposal_event');
			var evtID = evtEl.options[evtEl.selectedIndex].value;
			for(var i = 0; i < events.length; i++) {
				if(events[i]['id'] == evtID) {
					summaryMaxWords = events[i]['summaryMaxWords'];
					abstractMaxWords = events[i]['abstractMaxWords'];
					document.getElementById('abstractMaxWordsSpan').innerHTML = abstractMaxWords;
					document.getElementById('summaryMaxWordsSpan').innerHTML = summaryMaxWords;
					break;
				}
			}
		}
		
		var events = new Array();
<?php
	for($i = 0; $i < count($events); $i++) {
?>
		events[<?php echo $i; ?>] = new Array();
		events[<?php echo $i; ?>]['id'] = '<?php echo $events[$i]["id"]; ?>';
		events[<?php echo $i; ?>]['event'] = '<?php echo $events[$i]["event"]; ?>';
		events[<?php echo $i; ?>]['summaryMaxWords'] = <?php echo $events[$i]["summaryMaxWords"]; ?>;
		events[<?php echo $i; ?>]['abstractMaxWords'] = <?php echo $events[$i]["abstractMaxWords"]; ?>;
		
<?php
	}
?>
	</script>
	<p>
		<span style="font-weight: bold;">Event:</span>
		<select name="proposal_event" id="proposal_event" onchange="setEvent()">
			<option value="0">Please select an event...</option>
<?php
	foreach($events AS $e) {
?>
			<option value="<?php echo $e['id']; ?>"><?php echo $e['event']; ?></option>
<?php
	}
?>
		</select>
	</p>
	<p style="text-align: left; border-top: solid 1px #AAAAAA; padding-top: 20px; padding-bottom: 20px;"><span style="font-weight: bold;">Title:</span> <input type="text" name="proposal_title" id="proposal_title" style="width: 100%"></p>
	<p style="text-align: left; border-top: solid 1px #AAAAAA; padding-top: 20px; padding-bottom: 20px"><span style="font-weight: bold;">Summary (<span id="summaryMaxWordsSpan">50</span> words maximum)</span><br /><br />
		<textarea name="proposal_summary" id="proposal_summary" rows="3" cols="100" onkeyup="countWords(this)"></textarea><br /><br />
		<span style="font-weight: normal">Total Words:</span> <span id="summary_total_words" style="font-weight: normal">0</span>
	</p>
	<p style="text-align: left; border-top: solid 1px #AAAAAA; padding-top: 20px; padding-bottom: 20px;"><span style="font-weight: bold;">Abstract (<span id="abstractMaxWordsSpan">0</span> words maximum)</span><br /><br />
		<textarea name="proposal_abstract" id="proposal_abstract" rows="5" cols="100" onkeyup="countWords(this)"></textarea><br /><br />
		<span style="font-weight: normal">Total Words:</span> <span id="abstract_total_words" style="font-weight: normal">0</span>
	</p>
	<p style="text-align: left; border-top: solid 1px #AAAAAA; padding-top: 20px; padding-bototm: 20px;">
		<span style="font-weight: bold;">Visible on website?</span>
		<select id="proposal_visible" name="proposal_visible">
			<option value="Y">Yes</option>
			<option value="N">No</option>
		</select>
	</p>
	<p align="center"><input type="button" value="Cancel" onClick="window.location.href='samplePropList.php'"> &nbsp; &nbsp; &nbsp; &nbsp; <input type="button" value="Submit" onClick="checkForm()" ></p>
	<form name="propForm" id="propForm" method="post" action="">
		<input type="hidden" name="prop_event" id="prop_event" value="" />
		<input type="hidden" name="prop_title" id="prop_title" value="" />
		<input type="hidden" name="prop_summary" id="prop_summary" value="" />
		<input type="hidden" name="prop_abstract" id="prop_abstract" value="" />
		<input type="hidden" name="prop_visible" id="prop_visible" value="" />
	</form>
	<noscript>
		<style type="text/css">
			#pagecontainer { display: none; }
			#bgDiv { display: none; }
			#presFormDiv { display: none; }
		</style>
		<div id="noscriptmsg">
			<h1 align="center" style="color:red">Javascript Required!</h1>
			<p>This page requires javascript. Please enable javascript in your browser and click your browser's refresh button.</p>
		</div>
	</noscript>
<?php
	include "adminBottom.php";
?>