<?php
	//ev_library.php -- contains functions used on every page (such as session starting)
	//stored outside the public_html folder to prevent access by URL
	
	//Since we will call this at the start of every page, we will include the db_connect script here
	//(must call "ev_config.php" before including this in any page)
	$db = new mysqli(HOST,USER,PASSWORD,DATABASE);
	if(mysqli_connect_errno()) {
		echo "Failed to connect to MySQL: ".mysqli_connect_error();
		exit();
	}
	
	//Most of these functions came from http://www.wikihow.com/Create-a-Secure-Login-Script-in-PHP-and-MySQL
	
	//This function creates a secure PHP session (more secure than just using "session_start()".
	function sec_session_start() {
		$session_name = 'sec_session_id';   // Set a custom session name
		$secure = SECURE; //this is defined in the config file
		
		// This stops JavaScript being able to access the session id.
		$httponly = true;
		
		// Forces sessions to only use cookies.
		if (ini_set('session.use_only_cookies', 1) === FALSE) {
			header("Location: /ev/error.php?err=Could not initiate a safe session (ini_set)");
			exit();
		}
		
		// Gets current cookies params.
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params($cookieParams["lifetime"],
			$cookieParams["path"], 
			$cookieParams["domain"], 
			$secure,
			$httponly);
		
		// Sets the session name to the one set above.
		session_name($session_name);
		session_start();            // Start the PHP session 
		session_regenerate_id();    // regenerated the session, delete the old one. 
	}
	
	//This function checks the email (main contact) and password against the database
	//Returns TRUE if there is a match
	function login($id, $email, $password, $mysqli) {
		// Using prepared statements means that SQL injection is not possible.
		$stmt = $mysqli->prepare("SELECT `id`, `contact`, `password`, `salt` FROM `proposals` WHERE `contact` = ? AND `id` = ? LIMIT 1");
		
		if($stmt) {
			$stmt->bind_param('ss', $email, $id);  // Bind "$email" to parameter.
			$stmt->execute();    // Execute the prepared query.
			$stmt->store_result();
 
			// get variables from result.
			$stmt->bind_result($propID, $propContact, $propPassword, $propSalt);
			$stmt->fetch();
 
			// hash the password with the unique salt.
			$password = hash('sha512', $password.$propSalt);
			if ($stmt->num_rows == 1) {
				// If the user exists we check if the account is locked
				// from too many login attempts 
 
				if (checkbrute($propID, $mysqli) == true) {
					// Account is locked 
					// Send an email to user saying their account is locked
					return false;
				} else {
					// Check if the password in the database matches
					// the password the user submitted.
					if ($propPassword == $password) {
						// Password is correct!
						// Get the user-agent string of the user.
						$user_browser = $_SERVER['HTTP_USER_AGENT'];
						// XSS protection as we might print this value
						$propID = preg_replace("/[^0-9]+/", "", $propID);
						$_SESSION['propID'] = $propID;
						// XSS protection as we might print this value
						$propContact = preg_replace("/[^a-zA-Z0-9_\-]+/", 
																	"", 
																	$propContact);
						$_SESSION['propContact'] = $propContact;
						$_SESSION['login_string'] = hash('sha512', 
								  $password . $user_browser);
						// Login successful.
						return true;
					} else {
						// Password is not correct
						// We record this attempt in the database
						$now = time();
						
						$mysqli->query("INSERT INTO login_attempts(propID, time)
										VALUES ('$propID', '$now')");
						return false;
					}
				}
			} else {
				// No user exists.
				return false;
			}
		}
	}
	
	function checkbrute($propID, $mysqli) {
		// Get timestamp of current time 
		$now = time();
 
		// All login attempts are counted from the past 2 hours. 
		$valid_attempts = $now - (2 * 60 * 60);
 
		if ($stmt = $mysqli->prepare("SELECT `time` FROM `login_attempts` WHERE `propID` = ? AND `time` > '$valid_attempts'")) {
			$stmt->bind_param('i', $propID);
 
			// Execute the prepared query. 
			$stmt->execute();
			$stmt->store_result();
 
			// If there have been more than 5 failed logins 
			if ($stmt->num_rows > 5) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	function login_check($mysqli) {
		// Check if all session variables are set 
		if (isset($_SESSION['propID'], 
							$_SESSION['propContact'], 
							$_SESSION['login_string'])) {
 
			$propID = $_SESSION['propID'];
			$login_string = $_SESSION['login_string'];
			$propContact = $_SESSION['propContact'];
 
			// Get the user-agent string of the user.
			$user_browser = $_SERVER['HTTP_USER_AGENT'];
 
			if ($stmt = $mysqli->prepare("SELECT `password` FROM `proposals` WHERE `id` = ? LIMIT 1")) {
				// Bind "$user_id" to parameter. 
				$stmt->bind_param('i', $propID);
				$stmt->execute();   // Execute the prepared query.
				$stmt->store_result();
 
				if ($stmt->num_rows == 1) {
					// If the user exists get variables from result.
					$stmt->bind_result($password);
					$stmt->fetch();
					$login_check = hash('sha512', $password . $user_browser);
 
					if ($login_check == $login_string) {
						// Logged In!!!! 
						return true;
					} else {
						// Not logged in 
						return false;
					}
				} else {
					// Not logged in 
					return false;
				}
			} else {
				// Not logged in 
				return false;
			}
		} else {
			// Not logged in 
			return false;
		}
	}
	
	function esc_url($url) {
 		if('' == $url) {
			return $url;
		}
 
		$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
 
		$strip = array('%0d', '%0a', '%0D', '%0A');
		$url = (string)$url;
 
		$count = 1;
		while ($count) {
			$url = str_replace($strip, '', $url, $count);
		}
 
		$url = str_replace(';//', '://', $url);
 
		$url = htmlentities($url);
 
		$url = str_replace('&amp;', '&#038;', $url);
		$url = str_replace("'", '&#039;', $url);
 
		if ($url[0] !== '/') {
			// We're only interested in relative links from $_SERVER['PHP_SELF']
			return '';
		} else {
			return $url;
		}
	}

	function logout() {
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
	}

	/********************** FUNCTIONS FOR LIMITED USERS (EVENT LEADS, REVIEWERS, ETC.) **************************/
	//This function checks the email (main contact) and password against the database
	//Returns TRUE if there is a match
	function loginUser($uName, $uPass, $mysqli) {
		global $loginError;
		
		// Using prepared statements means that SQL injection is not possible.
		$stmt = $mysqli->prepare("SELECT `username`, `first_name`, `last_name`, `password`, `salt`, `role` FROM `users` WHERE `username` = ? LIMIT 1");
		
		if($stmt) {
			$stmt->bind_param('s', $uName);  // Bind "$uname" to parameter.
			$stmt->execute();    // Execute the prepared query.
			$stmt->store_result();
 
			// get variables from result.
			$stmt->bind_result($username, $firstName, $lastName, $password, $salt, $role);
			$stmt->fetch();
 
			// hash the password with the unique salt.
			$uPass = hash('sha512', $uPass.$salt);
			if ($stmt->num_rows == 1) {
				// If the user exists we check if the account is locked
				// from too many login attempts 
 
				if (checkbruteUser($uName, $mysqli) == true) {
					// Account is locked 
					// Send an email to user saying their account is locked
					$loginError = "You have unsuccessfully attempted to login too many times.<br />Please contact <a href=\"mailto:ev@call-is.org\">ev@call-is.org</a> to reset your password.";
					return false;
				} else {
					// Check if the password in the database matches
					// the password the user submitted.
					if ($uPass == $password) {
						// Password is correct!
						// Get the user-agent string of the user.
						$user_browser = $_SERVER['HTTP_USER_AGENT'];

						// XSS protection as we might print this value
						$user_name = strip_tags($username);
						$first_name = strip_tags($firstName);
						$last_name = strip_tags($lastName);
						$user_role = strip_tags($role);
						
						// a user can be assigned more than one role
						// if a user has more than one role, we will choose
						// the highest role for logging in (they can change their role later)
						if(strpos($user_role,"|") !== false) {
							if(strpos($user_role,"admin") !== false) $user_role = "admin";
							else if(strpos($user_role,"chair") !== false) $user_role = "chair";
							else if(strpos($user_role,"lead_") !== false) {
								// it is possible to be assigned more than one lead
								// so, choose the first lead we find
								if(strpos($user_role,"lead_fairs") !== false) $user_role = "lead_fairs";
								else if(strpos($user_role,"lead_mini") !== false) $user_role = "lead_mini";
								else if(strpos($user_role,"lead_ds") !== false) $user_role = "lead_ds";
								else if(strpos($user_role,"lead_mae") !== false) $user_role = "lead_mae";
								else if(strpos($user_role,"lead_cotf") !== false) $user_role = "lead_cotf";
								else if(strpos($user_role,"lead_ht") !== false) $user_role = "lead_ht";
								else if(strpos($user_role,"lead_grad") !== false) $user_role = "lead_grad";
								else if(strpos($user_role,"lead_classics") !== false) $user_role = "lead_classics";
							} else if(strpos($user_role,"reviewer_") !== false) {
								// it is possible to be assigned as a reviewer in more than one event
								// so, choose the first reviewer role we find
								if(strpos($user_role,"reviewer_fairs") !== false) $user_role = "reviewer_fairs";
								else if(strpos($user_role,"reviewer_mini") !== false) $user_role = "reviewer_mini";
								else if(strpos($user_role,"reviewer_ds") !== false) $user_role = "reviewer_ds";
								else if(strpos($user_role,"reviewer_mae") !== false) $user_role = "reviewer_mae";
								else if(strpos($user_role,"reviewer_cotf") !== false) $user_role = "reviewer_cotf";
								else if(strpos($user_role,"reviewer_ht") !== false) $user_role = "reviewer_ht";
								else if(strpos($user_role,"reviewer_grad") !== false) $user_role = "reviewer_grad";
								else if(strpos($user_role,"reviewer_classics") !== false) $user_role = "reviewer_classics";
							}
							
							$_SESSION['role_selected'] = "0";
							$_SESSION['multiple_roles'] = "1";
						} else $_SESSION['role_selected'] = "1"; //only one role, so don't need to select one later
						
						$_SESSION['user_name'] = $user_name;
						$_SESSION['first_name'] = $first_name;
						$_SESSION['last_name'] = $last_name;
						$_SESSION['user_role'] = $user_role;
						$_SESSION['login_string'] = hash('sha512', $password . $user_browser);
						// Login successful.
						return true;
					} else {
						// Password is not correct
						// We record this attempt in the database
						$now = time();
						$user_name = strip_tags($username);
						$mysqli->query("INSERT INTO login_attemptsUsers(username, time) VALUES ('$user_name', '$now')");
						$loginError = "The password is incorrect. Please check your password and try again.";

						return false;
					}
				}
			} else {
				// No user exists.
				$loginError = "There is no user that matches the information you entered.<br />Please check the email address and try again.";
				return false;
			}
		}
	}
	
	function checkbruteUser($username, $mysqli) {
		// Get timestamp of current time 
		$now = time();
 
		// All login attempts are counted from the past 2 hours. 
		$valid_attempts = $now - (2 * 60 * 60);
 
		if ($stmt = $mysqli->prepare("SELECT `time` FROM `login_attempts` WHERE `username` = ? AND `time` > '$valid_attempts'")) {
			$stmt->bind_param('i', $username);
 
			// Execute the prepared query. 
			$stmt->execute();
			$stmt->store_result();
 
			// If there have been more than 5 failed logins 
			if ($stmt->num_rows > 5) {
				return true;
			} else {
				return false;
			}
		}
	}
	
	function login_checkUser($mysqli) {
		// Check if all session variables are set 
		if (isset($_SESSION['user_name'], $_SESSION['login_string'])) {
			$username = $_SESSION['user_name'];
			$login_string = $_SESSION['login_string'];

			// Get the user-agent string of the user.
			$user_browser = $_SERVER['HTTP_USER_AGENT'];

			if ($stmt = $mysqli->prepare("SELECT `password` FROM `users` WHERE `username` = ? LIMIT 1")) {
				// Bind "$user_id" to parameter. 
				$stmt->bind_param('s', $username);
				$stmt->execute();   // Execute the prepared query.
				$stmt->store_result();
 
				if ($stmt->num_rows == 1) {
					// If the user exists get variables from result.
					$stmt->bind_result($password);
					$stmt->fetch();
					$login_check = hash('sha512', $password . $user_browser);

					if ($login_check == $login_string) {
						// Logged In!!!! 
						return true;
					} else {
						// Not logged in 
						return false;
					}
				} else {
					// Not logged in 
					return false;
				}
			} else {
				// Not logged in 
				return false;
			}
		} else {
			// Not logged in 
			return false;
		}
	}

	// This function allows an admin user (i.e. not a lead, chair, or reviewer)
	// to login as another user for debugging purposes.
	
	function loginAdmin($uName, $aName, $aPass, $mysqli) {
		global $loginError;
		
		// First, check to make sure an administrator is logged in
		$aStmt = $mysqli->prepare("SELECT `username`, `first_name`, `last_name`, `password`, `salt`, `role` FROM `users` WHERE `username` = ? LIMIT 1");
		if($aStmt) {
			$aStmt->bind_param('s', $aName);
			$aStmt->execute();
			$aStmt->store_result();
			$aStmt->bind_result($aUsername, $aFirstname, $aLastname, $aPassword, $aSalt, $aRole);
			$aStmt->fetch();
			
			//Check the credentials of the administrator
			$aPass = hash('sha512', $aPass.$aSalt);
			if($aPass == $aPassword) { //password is correct, so proceed to login as another user
				//Now, make sure this user is an admin
				if(strpos($aRole,"admin") !== false) { //is an admin user
					//Get the user information of the other user to be logged in
					$stmt = $mysqli->prepare("SELECT `username`, `first_name`, `last_name`, `password`, `role` FROM `users` WHERE `username` = ? LIMIT 1");
					if($stmt) {
						$stmt->bind_param('s', $uName);  // Bind "$uname" to parameter.
						$stmt->execute();    // Execute the prepared query.
						$stmt->store_result();
 
						// get variables from result.
						$stmt->bind_result($username, $firstName, $lastName, $password, $role);
						$stmt->fetch();
 
						$user_browser = $_SERVER['HTTP_USER_AGENT'];

						// XSS protection as we might print this value
						$user_name = strip_tags($username);
						$first_name = strip_tags($firstName);
						$last_name = strip_tags($lastName);
						$user_role = strip_tags($role);
						
						// a user can be assigned more than one role
						// if a user has more than one role, we will choose
						// the highest role for logging in (they can change their role later)
						if(strpos($user_role,"|") !== false) {
							if(strpos($user_role,"admin") !== false) {
								return false; //admins cannot login as other admins
							} else if(strpos($user_role,"chair") !== false) $user_role = "chair";
							else if(strpos($user_role,"lead_") !== false) {
								// it is possible to be assigned more than one lead
								// so, choose the first lead we find
								if(strpos($user_role,"lead_fairs") !== false) $user_role = "lead_fairs";
								else if(strpos($user_role,"lead_mini") !== false) $user_role = "lead_mini";
								else if(strpos($user_role,"lead_ds") !== false) $user_role = "lead_ds";
								else if(strpos($user_role,"lead_mae") !== false) $user_role = "lead_mae";
								else if(strpos($user_role,"lead_cotf") !== false) $user_role = "lead_cotf";
								else if(strpos($user_role,"lead_ht") !== false) $user_role = "lead_ht";
								else if(strpos($user_role,"lead_grad") !== false) $user_role = "lead_grad";
								else if(strpos($user_role,"lead_classics") !== false) $user_role = "lead_classics";
							} else if(strpos($user_role,"reviewer_") !== false) {
								// it is possible to be assigned as a reviewer in more than one event
								// so, choose the first reviewer role we find
								if(strpos($user_role,"reviewer_fairs") !== false) $user_role = "reviewer_fairs";
								else if(strpos($user_role,"reviewer_mini") !== false) $user_role = "reviewer_mini";
								else if(strpos($user_role,"reviewer_ds") !== false) $user_role = "reviewer_ds";
								else if(strpos($user_role,"reviewer_mae") !== false) $user_role = "reviewer_mae";
								else if(strpos($user_role,"reviewer_cotf") !== false) $user_role = "reviewer_cotf";
								else if(strpos($user_role,"reviewer_ht") !== false) $user_role = "reviewer_ht";
								else if(strpos($user_role,"reviewer_grad") !== false) $user_role = "reviewer_grad";
								else if(strpos($user_role,"reviewer_classics") !== false) $user_role = "reviewer_classics";
							}
							
							$_SESSION['role_selected'] = "0";
							$_SESSION['multiple_roles'] = "1";
						} else $_SESSION['role_selected'] = "1"; //only one role, so don't need to select one later
						
						$_SESSION['user_name'] = $user_name;
						$_SESSION['first_name'] = $first_name;
						$_SESSION['last_name'] = $last_name;
						$_SESSION['user_role'] = $user_role;
						$_SESSION['login_string'] = hash('sha512', $password . $user_browser);
						$_SESSION['admin_name'] = $aUsername;
						$_SESSION['admin_first_name'] = $aFirstname;
						$_SESSION['admin_last_name'] = $aLastname;
						// Login successful.
						return true;
					}
				} else { //not an admin user
					$loginError = "You do not have permission to login as another user. Please contact a site administrator.";
					return false;
				}
			} else { //admin password was incorrect
				$loginError = "Your password did not match our records. Please try again.";
				return false;
			}
		}
	}
?>