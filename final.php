<?php   // final.php
  require_once 'login.php';
  $conn = new mysqli($hn, $un, $pw, $db);
  if ($conn->connect_error)
  {
  	die("Uh oh, something went wrong.Please close the window and then try again");
  }


echo <<<_END

<!DOCTYPE html>
<html>
<head>
	<style>
	.signup {
		border:1px solid #999999; font: normal 14px helvetica; color: #444444;
	}

	</style>
	<script>
	function validate(form) {

		fail = ""

		fail += validateUsername(form.username.value)
		fail += validatePassword(form.password.value)
		
		if (fail == "")
		 return true	
		else
		 {
		  alert(fail); 
		  return false 
		}
	}

function validateUsername(field)
{
	if (field == "") return "No Username was entered."
	else if (field.length < 5)
		return "Usernames must be at least 5 characters."
	else if (/[^a-zA-Z0-9_-]/.test(field))
		return "Only a-z, A-Z, 0-9, - and _ allowed in Usernames."
	return ""
}

function validatePassword(field)
{
	if (field == "") 
		return "No Password was entered.";
	else if (field.length < 6)
		return "Passwords must be at least 6 characters.";
	else if (!/[a-z]/.test(field) || ! /[A-Z]/.test(field) ||!/[0-9]/.test(field))
		return "Passwords require one each of a-z, A-Z and 0-9.";
	return "";
}
	</script>
</head>
<body>
	<table border="0" cellpadding="2" cellspacing="5" bgcolor="#eeeeee">
		<th colspan="2" align="center">Sign Up</th>
		<form method="post" action="final.php" onsubmit="return validate(this)">  
			<tr><td>Username</td>
				<td><input type="text" maxlength="16" name="username"></td></tr>
			<tr><td>Password</td>
				<td><input type="password" name="password"></td></tr>
			<tr><td colspan="2" align="center"><input type="submit"
				value="Sign Up"></td></tr>
		</form>
	</table>

	<form action="final.php" method="post"><pre><br>Sign In
<input type="submit" value="Sign In" name = "signin">
</pre></form>
<br>
<form action="final.php" method="post"><pre>Default Translation
<input type="text" placeholder="Default translation.." name="search">
<input type="submit" name= "submit" value="Translate">
</pre></form> 
</body>
</html>

_END;



//SIGN IN Promt	
if (isset($_POST['signin'] ) && ((!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PW'])) || $_SERVER['PHP_AUTH_USER']=="" && $_SERVER['PHP_AUTH_PW'] = ""))
{
	header('WWW-Authenticate: Basic realm="Restricted Section"');
		header('HTTP/1.0 401 Unauthorized');
		die ("Please enter your username and password.");
}





//SEARCH

if (isset($_POST['search'])) 
	{

		$search_value ="";

		if( empty( $_POST['search']) ) {
		        $search_value = " ";
		    }
		else
			$search_value = sanitizeMySQL($conn, $_POST['search']);


		$query = "SELECT * FROM default_translate where original like '%$search_value%'";

		$result = $conn->query($query);

		if (!$result or $result->num_rows == 0) 
		{
		  	echo "No translation found.<br>";
		  	
		}

		else
		{
			$rows = $result->num_rows;


			for ($j = 0 ; $j < $rows ; ++$j)
			{
				$result->data_seek($j);
				$row = $result->fetch_array(MYSQLI_NUM);
				echo <<<_END
				<pre>
				Original: $row[0]
				Translation: $row[1]
				</pre>

				_END;
			}


		}

		$result->close();
	}




//SIGN UP
$usern = $passw = $fail = "";

if(isset($_POST['username']))
{
	$usern = fix_string($_POST['username']);
}
if(isset($_POST['password']))
{
	$passw = fix_string($_POST['password']);
}

$fail .= validate_username($usern);
$fail .= validate_password($passw);

if($fail == "")
{

	echo "Succesfully validated your chosen username and password.<br>";

	if (isset($_POST['username']) && isset($_POST['password']) && !isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PW']))
	{	
		$stmt = $conn->prepare('INSERT INTO userinfo VALUES(?,?)');

		$stmt->bind_param('ss', $username, $password);

		$username = sanitizeMySQL($conn, $_POST['username']);
		$p = sanitizeMySQL($conn, $_POST['password']);
		$password = password_hash($p, PASSWORD_DEFAULT);

		
		$stmt->execute();

		if ($stmt->affected_rows < 1)
		{
		  	die("User was not added or user already has an account.Please close the window and then try again.");
		}
		
		$stmt->close();

		echo "You have signed up. You can Sign In to upload your dictionnary. <br>";
	}
}
else if ($fail != "" && (isset($_POST['username']) || isset($_POST['password'])) && (!isset($_SERVER['PHP_AUTH_USER']) && !isset($_SERVER['PHP_AUTH_PW'])))
	echo "$fail<br>";


$f_content ="";
$noFile_or_notAccepted = false;


//SIGN IN
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']))
	{
		$un_value = sanitizeMySQL($conn, $_SERVER['PHP_AUTH_USER']);
		$pw_value = sanitizeMySQL($conn, $_SERVER['PHP_AUTH_PW']);

		//look for username in the userinfo table
		$query = "SELECT * FROM userinfo where username like '%$un_value%'";

		$result = $conn->query($query);


		if (!$result or $result->num_rows == 0) 
		{
			header('WWW-Authenticate: Basic realm="Restricted Section"');
			header('HTTP/1.0 401 Unauthorized');
		  	die("Invalid username/password combination.");
		}
		elseif ($result->num_rows) 
		{
			$row = $result->fetch_array(MYSQLI_NUM);
			$result->close();

			if (password_verify($pw_value, $row[1])) //if password is correct	
			{	
				session_start();
				$_SESSION['username'] = $un_value;
				$_SESSION['password'] = $pw_value;

				echo "<br>Hi $row[0], you have logged in<br>";

				die ("<p><a href=continue.php>Click here to use or upload your own dictionary.</a></p>");

				
			}
			else
			{
				header('WWW-Authenticate: Basic realm="Restricted Section"');
			header('HTTP/1.0 401 Unauthorized');
				die ("Invalid username/password combination.");
			}	
		}
				
		else 
			{
				header('WWW-Authenticate: Basic realm="Restricted Section"');
			header('HTTP/1.0 401 Unauthorized');
				die ("Invalid username/password combination.");
			
			}
	}

  	$conn->close();


  	function sanitizeString($var) 
	{
		$var = stripslashes($var);
		$var = strip_tags($var);
		$var = htmlentities($var);
		return $var;
	}

	function sanitizeMySQL($conn, $var) 
	{
			$var = $conn->real_escape_string($var);
			$var = sanitizeString($var);
			return $var;
	}

	function validate_username($usern)
	{
		
		if ($usern == "") 
			return "No Username was entered.<br>";
		else if (strlen($usern) < 5)
			return "Usernames must be at least 5 characters.<br>";
		else if (preg_match("/[^a-zA-Z0-9_-]/", $usern))
			return "Only a-z, A-Z, 0-9, - and _ allowed in Usernames.<br>";
		return "";
	}

	function validate_password($passw)
	{
		if ($passw == "") 
			return "No Password was entered.<br>";
		else if (strlen($passw) < 5)
			return "Passwords must be at least 6 characters.<br>";
		else if (!preg_match("/[a-z]/", $passw) || !preg_match("/[A-Z]/", $passw) ||!preg_match("/[0-9]/", $passw))
			return "Passwords require one each of a-z, A-Z and 0-9.";
		return "";
	}

	function fix_string ($string)	
	{
		if (get_magic_quotes_gpc())
			$string = stripcslashes($string);
		return htmlentities($string);
	}
?>
