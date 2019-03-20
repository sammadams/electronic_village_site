<?php
	require("phpToPDF.php");

	$html = '<body style="margin: 0">
				<img src="http://call-is.org/ev/admin/Volunteer.png" height="750" width="1000" />
				<div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 999">
					<table border="0">
						<tr>
							<td width="1000" height="745" align="center" valign="center">
								<p align="center" style="font-size: 35px; font-family: Arial">Justin Shewell Justin Shewell Justin Shewell</p>
								<p>&nbsp;</p>
								<p>&nbsp;</p>
								<p>&nbsp;</p>
							</td>
						</tr>
					</table>
				</div>
			</body>';

	$pdf_options = array(
		"source_type" => 'html',
		"source" => $html,
		"action" => 'save',
		"save_directory" => '',
		"file_name" => 'cert_test.pdf',
		"page_orientation" => 'landscape',
		"margin" => array("right" => "10", "left" => "10", "top" => "10", "bottom" => "10")
	);

	phptopdf($pdf_options);

	echo '<a href="cert_test.pdf">Download PDF</a>';
?>