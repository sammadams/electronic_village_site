<?php
	include_once '../../ev_config.php';
	include_once '../../ev_library.php';
	
	sec_session_start();
	
	if(isset($_SESSION['user_name']) && $_SESSION['user_name'] != "") $logoutUser = true;
	else $logoutUser = false;
 
	// Unset all session values 
	$_SESSION = array();
 
	// get session parameters 
	$params = session_get_cookie_params();
 
	// Delete the actual cookie. 
	setcookie(session_name(),
    	    '', time() - 42000, 
	        $params["path"], 
    	    $params["domain"], 
        	$params["secure"], 
	        $params["httponly"]);
 
	// Destroy session 
	session_destroy();
	if(!$logoutUser) header('Location: index.php');
	else header('Location: /ev/admin/'); //redirect to the admin page for admin users
?>