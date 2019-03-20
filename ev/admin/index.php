<?php
	//index.php - the main page that users see when they login
	//accessible only to admin users (including reviewers)
	
	include_once "login.php";
	
	if(!isset($_SESSION["role_selected"]) || $_SESSION["role_selected"] != "1") { //the user has not chosen a role yet
		header("Location: changeRole.php");
	}
	
	//Show the menu for a particular role
	if(strpos($_SESSION["user_role"],"reviewer_") !== false) header("Location: reviewerList.php");
	else if(strpos($_SESSION["user_role"],"lead_") !== false && strpos($_SESSION['user_role'],"_classics") === false) {
		$topTitle = "Main Menu";
		include "adminTop.php";
?>
		<p align="center"><a href="listProps_lead.php">Proposals List</a></p>
		<p align="center"><a href="userList.php">List of Reviewers</p>
		<p align="center"><a href="assignReviewer.php">Assign Reviewers</a></p>
		<p align="center"><a href="scheduleSession.php">Schedule Presentations</a></p>
		<!-- <p align="center"><a href="schedule.php">Schedule Presentations</a></p> -->
<?php
		//<p align="center"><a href="sendNotifications.php">Send Notifications</a></p>
		//<p align="center"><a href="sendEmails.php">Send Email to Proposals Authors</p>
		include "adminBottom.php";
	} else if(strpos($_SESSION['user_role'],'_classics') !== false) {
		$topTitle = "Main Menu";
		include "adminTop.php";
?>
		<p align="center"><a href="listProps_classics.php">Presentations List</a></p>
		<p align="center"><a href="scheduleSession.php">Schedule Presentations</a></p>
		<!-- <p align="center"><a href="schedule.php">Schedule Presentations</a></p> -->
<?php
		include "adminBottom.php";	
	} else if($_SESSION["user_role"] == "chair") {
		$topTitle = "Main Menu";
		include "adminTop.php";
?>
		<p align="center"><a href="listProps_admin.php">Proposals List</a></p>
		<p align="center"><a href="userList.php">List of Users</p>
		<p align="center"><a href="sessionList.php">List of Schedule Sessions</a></p>
		<p align="center"><a href="attendance.php">Attendance Statistics</a></p>
		<p align="center"><a href="exportData.php">Export Proposal Data</a></p>
		<p align="center"><a href="sendNotifications.php">Send Notifications</a></p>
		<p align="center"><a href="sendEmails.php">Send Email to Proposals Authors</p>
		<!-- <p align="center"><a href="schedule.php">Schedule Presentations</a></p> -->
<?php
		include "adminBottom.php";
	} else if($_SESSION["user_role"] == "admin") {
		$topTitle = "Main Menu";
		include "adminTop.php";
?>
		<p align="center"><a href="listProps_admin.php">Proposals List</a></p>
		<p align="center"><a href="listProps_classics.php">Classics Proposals List</a></p>
		<p align="center"><a href="reviewStatus.php">Reviewer Status</a></p>
		<p align="center"><a href="eventList.php">Manage Events</a></p>
		<p align="center"><a href="samplePropList.php">Manage Sample Proposals</a></p>
		<p align="center"><a href="siteConfig.php">Site Configuration</a></p>
		<p align="center"><a href="userList.php">List of Users</p>
		<p align="center"><a href="sessionList.php">List of Schedule Sessions</a></p>
		<p align="center"><a href="scheduleSession.php">Schedule Presentations</a></p>
		<p align="center"><a href="scheduleCheck.php">Check Schedule for Double Bookings</a></p>
		<p align="center"><a href="print_signs.php">Print Signs for Bulletin Boards</a></p>
		<p align="center"><a href="program.php">Print Program Book Formatted</a></p>
		<p align="center"><a href="attendance.php">Attendance Statistics</a></p>
		<p align="center"><a href="exportData.php">Export Proposal Data</a></p>
		<p align="center"><a href="exportCMS.php">Export Data for CMS (TESOL Portal)</a></p>
		<p align="center"><a href="sendNotifications.php">Send Notifications</a></p>
		<p align="center"><a href="print_certificates.php">Print Certificates</a></p>
		<p align="center"><a href="print_cert_tables.php">Print Tables for Certificate Mail Merge</a></p>
		<p align="center"><a href="sendEmails.php">Send Email to Proposals Authors (NOT WORKING)</p>
		<p align="center"><a href="send_certificates.php">Send Certificates to Presenters</p>
		<!-- <p align="center"><a href="schedule.php">Schedule Presentations</a></p> -->
<?php
	}
			
	include "adminBottom.php";
?>