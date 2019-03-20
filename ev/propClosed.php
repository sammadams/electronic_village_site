<?php
	include_once('../../ev_config.php');
	include_once('../../ev_library.php');
	
	if(!$propOpen) {
?>
<html>
	<head>
		<title>Electronic Village Proposals</title>
		<style type="text/css">
			body {
				font-family: Arial,Helvetica;
				font-size: 12pt;
				color: #000000;
				background-color: #FFFFFF;
			}			
		</style>
		<link rel="icon" type="image/png" href="https://call-is.org/ev/favicon.ico" />
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	</head>
	
	<body>
		<table border="0" align="center" cellspacing="0" cellpadding="0" width="800">
			<tr>
				<td><img src="tesol_banner.jpg" style="max-width: 800px" /></td>
			</tr>
			<tr>
				<td align="center" style="padding-top: 20px; padding-bottom: 20px"><span style="font-size: 24pt; font-weight: bold">CALL-IS Electronic Village Events (<?php echo $confYear; ?>)</td>
			</tr>
			<tr>
				<td style="border-top: solid 1px #CCCCCC; border-bottom: solid 1px #CCCCCC; padding: 20px">
					<h1 align="center">CLOSED</h1>
<?php
		if(time() >= $propCloseDate) {
?>
					<p>The proposals system is closed. The deadline for submission was <?php echo date("F j, Y",$propCloseDate); ?>. You will hear back from the CALL-IS about your proposal submissions sometime around the end of December. If you have questions, please contact the Electronic Village at <a href="mailto:ev@call-is.org">ev@call-is.org</a>.</p>
<?php
		} else if(time() <= $propOpenDate) {
?>
					<p>The proposals system is closed. The proposals site is scheduled to open on <?php echo date("F j, Y",$propCloseDate); ?>. Please check back then. If you have questions, please contact the Electronic Village at <a href="mailto:ev@call-is.org">ev@call-is.org</a>.</p>
<?php
		} else { // we are in the open and close dates, but the site is still not open yet
?>
					<p>The proposals system is closed. If you have questions, please contact the Electronic Village at <a href="mailto:ev@call-is.org">ev@call-is.org</a>.</p>
<?php
		}
?>
				</td>
			</tr>
		</table>
	</body>
</html>
<?php
		exit();
	}
?>