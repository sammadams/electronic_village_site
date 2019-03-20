<?php
	$y = date("Y"); //get the current year
	if(intval(date("n")) > 7) $y++; //probably the next year, so increase by 1
	
	//echo "<pre>";
	//print_r($_SESSION);
	//echo "</pre>";
	
	$userRoles = array(
		'reviewer_fairs' => 'Reviewer (Technology Fairs)',
		'reviewer_mini' => 'Reviewer (Mini-Workshops)',
		'reviewer_ds' => 'Reviewer (Developers Showcase)',
		'reviewer_mae' => 'Reviewer (Mobile Apps for Education Showcase)',
		'reviewer_cotf' => 'Reviewer (Classroom of the Future)',
		'reviewer_ht' => 'Reviewer (Hot Topics)',
		'reviewer_grad' => 'Reviewer (Graduate Student Research)',
		'reviewer_classics' => 'Reviewer (Technology Fair Classics)',
		'lead_fairs' => 'Event Lead (Technology Fairs)',
		'lead_mini' => 'Event Lead (Mini-Workshops)',
		'lead_ds' => 'Event Lead (Developers Showcase)',
		'lead_mae' => 'Event Lead (Mobile Apps for Education Showcase)',
		'lead_cotf' => 'Event Lead (Classroom of the Future)',
		'lead_ht' => 'Event Lead (Hot Topics)',
		'lead_grad' => 'Event Lead (Graduate Student Research)',
		'lead_classics' => 'Event Lead (Technology Fair Classics)',
		'chair' => 'EV Coordinator/Chair',
		'admin' => 'Site Administrator'
	);	
?>
<html>
	<head>
		<title><?=$topTitle?> -- Electronic Village Proposals</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
				margin-bottom: 50px;
			}

			span.label {
				color: green;
				font-size: 9pt;
				font-style: italic;
			}	
			
			td.userInfo {
				font-size: .85em;
			}
			
			a {
				text-decoration: none;
				border-bottom: dashed 1px #CCCCCC;
				color: #0066CC;
			}
			
			a:hover {
				border-bottom: solid 1px #0066CC;
			}
			
			input[type=submit], input[type=button] {
				font-size: 16px;
				height: auto;
				width: auto;
				padding-left: 25px;
				padding-right: 25px;
				border: solid 1px #000000;
				background-color: #CCCCCC;
				border-radius: 5px;
				color: #000000;
				font-weight: bold;
			}
			
			input[type=submit]:hover, input[type=button]:hover {
				background-color: #888888;
				color: #FFFFFF;
			}
		</style>
		<link rel="icon" type="image/png" href="/ev/favicon.ico" />
<?php
	if(isset($enableTinyMCE) && is_array($enableTinyMCE) && count($enableTinyMCE) > 0) {
?>
		<script type="text/javascript" src="tinymce/tinymce.min.js"></script>
		<script type="text/javascript">
<?php
		foreach($enableTinyMCE AS $etm) {
?>
			tinymce.init({
				selector: '#<?php echo $etm[0]; ?>',
				width: <?php echo $etm[1]; ?>,
				height: <?php echo $etm[2]; ?>,
				resize: false,
				plugins: 'lists code textcolor',
				toolbar: 'undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | forecolor | code',
				menubar: false
			});
<?php
		}
?>
		</script>
<?php
	}
?>
	</head>
	
	<body<?php if(isset($onload) && $onload != "") { ?> onload="<?php echo $onload; ?>"<?php } ?>>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="../tesol_banner.jpg" style="max-width: 800" /></td>
			</tr>
<?php
	if(isset($_SESSION['first_name'], $_SESSION['last_name'], $_SESSION['user_name'])) {
?>
			<tr>
				<td style="padding-top: 10px; border-top: solid 1px #AAAAAA; padding-bottom: 10px; border-bottom: solid 1px #AAAAAA">
					<table border="0" align="center" width="100%">
						<tr>
							<td width="35%" align="left" class="userInfo"><?=$_SESSION['first_name']?> <?=$_SESSION['last_name']?> (<?=$_SESSION['user_name']?>)</td>
<?php
		if(isset($_SESSION['multiple_roles']) && $_SESSION['multiple_roles'] == "1") {
?>
							<td width="20%" align="center" class="userInfo"><a href="index.php">Back to Menu</a></td>
							<td align="center" width="25%" class="userInfo"><a href="changeRole.php">Change User Role</a></td>
							<td width="20%" align="right" class="userInfo"><a href="../logout.php">Logout</a></td>
						</tr>
						<tr>
							<td colspan="4" align="center" style="padding-top: 10px">
								<?=$userRoles[$_SESSION['user_role']]?>
<?php
			if(isset($_SESSION['admin_name']) && $_SESSION['admin_name'] != '') { //an admin is logged in as another user
?>
								&nbsp; &nbsp; &nbsp; 
								(<a href="logoutAdmin.php">RETURN TO ADMIN MENU</a>)
<?php
			}
?>
							</td>
<?php
		} else {
?>
							<td width="35%" align="center" class="userInfo"><a href="index.php">Back to Menu</a></td>
							<td width="30%" align="right" class="userInfo"><a href="../logout.php">Logout</a></td>
						</tr>
						<tr>
							<td colspan="3" align="center" style="padding-top: 10px">
								<?=$userRoles[$_SESSION['user_role']]?>
<?php
			if(isset($_SESSION['admin_name']) && $_SESSION['admin_name'] != '') { //an admin is logged in as another user
?>
								&nbsp; &nbsp; &nbsp; 
								(<a href="logoutAdmin.php">RETURN TO ADMIN MENU</a>)
<?php
			}
?>
							</td>
<?php
		}
?>
						</tr>
					</table>
				</td>
			</tr>
<?php
	}
?>
			<tr>
				<td align="center" style="padding-top: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?=$y?>)<br /><br /><span style="font-size: 18pt; font-weight: bold"><?=$topTitle?></span></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px">