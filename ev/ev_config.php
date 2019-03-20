<?php
	//ev_config.php -- defines variables used in the authentication and db connect pages
	//stored outside the public_html folder to prevent access via URL
	
	define("HOST","localhost");
	define("USER","root");
	define("PASSWORD","password");
	define("DATABASE","ev");
	define("SECURE",FALSE); //we don't have HTTPS availabe, so this must be false
?>