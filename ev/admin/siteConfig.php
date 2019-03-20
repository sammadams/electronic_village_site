<?php
	//siteConfig.php - allows a user to edit site configuation settings
	//accessible only to admins
	
	include_once "login.php";
	
	if(strpos($_SESSION['user_role'],"admin") === false) { //reviewers don't have permission to view this page
		include "adminTop.php";
?>
				<h3 align="center">You do not have permission to view this page!</h3>
<?php
		include "adminBottom.php";
		exit();
	}
	
	$configs = getConfigs();
	
	$topTitle = "Site Configuration";
	
	if($_POST) { //the form was submitted
		$modified = date("Y-m-d H:m:s");
		foreach($configs AS $cK => $cV) {
			if($cV['type'] == 'date') {
				if(!preg_match("/^\d{4}-\d{2}-\d{2}$/", $_POST[$cK])) {
					$errMsg = "The ".$cV['title']." was invalid!";
					break;
				}
				
				$newValue = $_POST[$cK];
			} else if($cV['type'] == 'binary') {
				$newValue = isset($_POST[$cK]) ? 1 : 0;
			} else if($cV['type'] == 'text') {
				$newValue = filter_var($_POST[$cK]);
			} else if($cV['type'] == 'number') {
				$newValue = preg_replace("/\D/", "", $_POST[$cK]);
			} else if(preg_match("/^select\|\|/", $cV['type'])) {
				$newValue = $_POST[$cK];
			} else if($cV['type'] == 'longtext') {
				$newValue = filter_var($_POST[$cK]);
			}
			
			if(!$db->query("UPDATE `configs` SET `value` = '".$db->real_escape_string($newValue)."', `updated` = '".$modified."' WHERE `name` = '".$cK."'")) {
				$errMsg = "The ".$cV['title']." could not be updated! ".$db->error;
			}
		}
		
		if(!isset($errMsg)) $saveMsg = "Configuration saved!";
		
		$configs = getConfigs(); //reload the configs in case something changed	
	}
		
	include "adminTop.php";
?>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
	<script type="text/javascript">
		$(document).ready(function() {
<?php
	foreach($configs AS $cK => $cV) {
		if($cV['type'] == 'date') {
?>
			$('#<?php echo $cK; ?>').datepicker({ dateFormat: 'yy-mm-dd' });
<?php
		}
	}
?>
       	    $('#configForm').submit(function() {
       	    	//return false;
       	    });
		});		
	</script>
<?php
	if(isset($errMsg) && $errMsg != "") {
?>
	<p style="text-align: center; color: red;"><strong>ERROR:</strong> <?php echo $errMsg; ?></p>
<?php
	} else if(isset($saveMsg) && $saveMsg != "") {
?>
	<p style="text-align: center; color: #009900; font-weight: bold"><?php echo $saveMsg; ?></p>
<?php
	}
?>
	<form action="" method="post" id="configForm" name="configForm">
		<p style="font-weight: bold; text-align: left;">* Conference dates below refer to days that EV sessions are running. Do not enter dates such as the first day of the conference, which is only the opening plenary.</p>
		<table border="0" cellpadding="5" cellspacing="5">
<?php
	foreach($configs AS $cK => $cV) {
?>
			<tr>
				<td valign="top"><?php echo $cV['title']; ?>:</td>
				<td>
<?php
		if($cV['type'] == 'date') {
?>
					<input type="text" name="<?php echo $cK; ?>" id="<?php echo $cK; ?>" value="<?php echo $cV['value']; ?>" />
<?php
		} else if($cV['type'] == 'binary') {
?>
					<input type="checkbox" name="<?php echo $cK; ?>" id="<?php echo $cK; ?>"<?php if($cV['value'] == '1') { ?> checked="true"<?php } ?> />
<?php		
		} else if($cV['type'] == 'text') {
?>
					<input type="text" name="<?php echo $cK; ?>" id="<?php echo $cK; ?>" value="<?php echo $cV['value']; ?>" />
<?php
		} else if($cV['type'] == 'number') {
?>
					<input type="number" name="<?php echo $cK; ?>" id="<?php echo $cK; ?>" value="<?php echo $cV['value']; ?>" />
<?php	
		} else if(preg_match("/^select\|\|/", $cV['type'])) {
?>
					<select name="<?php echo $cK; ?>" id="<?php echo $cK; ?>">
<?php
			$tmpOps = explode("||", $cV['type']);
			for($t = 1; $t < count($tmpOps); $t++) { // skip the first one (which is "select")
				$thisOp = explode("|", $tmpOps[$t]);
				if(count($thisOp) < 2) {
					$thisVal = $thisOp[0];
					$thisTxt = $thisVal;
				} else {
					$thisVal = $thisOp[0];
					$thisTxt = $thisOp[1];
				}
?>
						<option value="<?php echo $thisVal; ?>"<?php if($thisVal == $cV['value']) { ?> selected<?php } ?>><?php echo $thisTxt; ?></option>
<?php
			}
?>					
					</select>
<?php
		} else if($cV['type'] == 'longtext') {
?>
					<textarea name="<?php echo $cK; ?>" id="<?php echo $cK; ?>" style="height: 150px; width: 300px;"><?php echo $cV['value']; ?></textarea>
<?php
		}
?>
				</td>
			</tr>
<?php
	}
?>
		</table><br /><br />
		<p style="text-align: center;"><input type="submit" value="Save Configuration" /></p>
	</form>
<?php
	include "adminBottom.php";
	
	function getConfigs() {
		global $db;
		
		//Get the list of configuration settings
		$cStmt = $db->prepare("SELECT `id`,`name`,`value`,`comment`, `title`, `type` FROM `configs` WHERE `enabled` = '1'");
		$cStmt->execute();
		$cStmt->bind_result($id, $name, $value, $comment, $title, $type);
	
		//Create an array of the configuration settings
		$configs = array();
		while($cStmt->fetch()) {
			$configs[$name] = array(
				"id" => $id,
				"value" => $value,
				"comment" => $comment,
				"title" => $title,
				"type" => $type
			);
		}
		
		return $configs;
	}
?>