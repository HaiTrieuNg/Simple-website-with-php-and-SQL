<?php //continue.php
	session_start();

	if (!isset($_SESSION['initiated']))
	{
	  session_regenerate_id();
	  $_SESSION['initiated'] = 1;
	}
	if (!isset($_SESSION['count'])) 
	  	$_SESSION['count'] = 0;
	 else 
	 	++$_SESSION['count'];


	if (isset($_SESSION['username'])) {
		$username = $_SESSION['username'];
		$password = $_SESSION['password'];

	}
	else echo "Please <a href='final.php'>click here</a> to log in or sign up.<br>";

	require_once 'login.php';

  	$conn = new mysqli($hn, $un, $pw, $db);

 	if ($conn->connect_error)
  	{
  		die("Uh oh, something went wrong.Please close the window and then try again");
  	}

echo <<<_END

<form action="continue.php" method="post" enctype="multipart/form-data">
  <br>Upload dictionary:<br><br>
  Original    <input name="file_name" type="file" /><br />
  Translation <input name="file_name1" type="file" /><br />
  <input type="submit" value="Add" />
</form>
<br>
<form action="continue.php" method="post">
  <input type="text" placeholder="Translation.." name="search">
  <input type="submit" name= "submit" value="Translate">
</form>

<form action="continue.php" method="post"><pre><br>Sign Out (A pop up window will appear, please close the pop up window for a total sign out)
<input type="submit" value="Sign Out" name = "signout">
</pre></form>
_END;


	$f_content ="";
	$f_content1 ="";
	$noFile_or_notAccepted = false;


	if (isset($_POST['signout'] ))
	{
		destroy_session_and_data();

		echo "You have logged out.";
		echo " Please <a href='final.php'>click here</a> to log in or sign up.";

		//to unset the variables that the browser remembers.
		//users should simply press cancel and not filling in anything
		header('WWW-Authenticate: Basic realm="Restricted Section"');
		header('HTTP/1.0 401 Unauthorized');
		die ();


	}

	// CHECK FILE
	if ($_FILES && isset($_SESSION['username']))
	{
		$name = $_FILES["file_name"]['name'];
		$name = sanitizeMySQL($conn,strtolower(preg_replace("[^A-Za-z0-9.]", "", $name)));

		$name1 = $_FILES["file_name1"]['name'];
		$name1 = sanitizeMySQL($conn,strtolower(preg_replace("[^A-Za-z0-9.]", "", $name1)));


		switch($_FILES["file_name"]['type']) 
		{
			case 'text/plain'  : $ext = 'txt'; break;
			default  : $ext = ''; break;
		}

		switch($_FILES["file_name1"]['type']) 
		{
			case 'text/plain'  : $ext1 = 'txt'; break;
			default  : $ext1 = ''; break;
		}

		if ($ext && $ext1) 
		{
			$n = $name;
			$f_content = file_get_contents($n); 
			$n1 = $name1;
			$f_content1 = file_get_contents($n1); 
			echo "Your dictionary has been uploaded.<br>";
		}
		else 
		{
			echo "The files are not accepted or you only uploaded one file. Please try again.<br>";
			$noFile_or_notAccepted = true;
		}
		
	}
	elseif ($_FILES && !isset($_SESSION['username']))
	{
		header("Location: final.php");
		exit();
	}
	else 
	{
		$noFile_or_notAccepted = true;
	}


	//ADD NEW FILE
	if ($_FILES && isset($_SESSION['username'])&& !$noFile_or_notAccepted)
	{
		
		$login_user = sanitizeMySQL($conn,$_SESSION['username']);
		$content = str_replace("rn","\n",sanitizeMySQL($conn,$f_content));
		$content1 =  str_replace("rn","\n",sanitizeMySQL($conn,$f_content1));

		$array = explode("\n", $content);
		$array1 = explode("\n", $content1);

		foreach( $array as $index => $a   )
		{
			$stmt1 = $conn->prepare('INSERT INTO usercontent VALUES(?,?,?)');
			$stmt1->bind_param('sss', $login_user,$a,$array1[$index] );

			$stmt1->execute();

			if ($stmt1->affected_rows < 1)
			{
		  		die("Couldn't add dictionary.");
			}
		
			$stmt1->close();
		}

	}


	//Search for translation

	if (isset($_POST['search'])) 
		{

			$usn ="";
			$c = "";

			if( empty( $_POST['search']) ) 
			{
			        $usn = " ";
			        $c = " ";
			}
			else
			{
				if(isset($_SESSION['username']))
					$usn = sanitizeMySQL($conn,$_SESSION['username']);
				$c = sanitizeMySQL($conn,$_POST['search']);
			}

			//check if there's a dictionary
			$query = "SELECT * FROM usercontent  WHERE username = '$usn'";

			$result = $conn->query($query);

			//if not, use default dictionary
			if (!$result or $result->num_rows == 0) 
			{
			  	//echo "No translation found.<br>";

			  	$query1 = "SELECT * FROM default_translate WHERE original = '$c'";

				$result = $conn->query($query1);

				if (!$result or $result->num_rows == 0) 
				{
				  	echo "You haven't uploaded any dictionary and no translation found in the default dictionary for the searched word.<br>";	
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
						You haven't uploaded any dictionary. Below is the translation from the default dictionary.<br>
						Original: $row[0]
						Translation: $row[1]
						</pre>

						_END;
					}


				}
			  	
			}

			//if there is user's dictionary, use the dictionary
			else
			{
				$query2 = "SELECT * FROM usercontent  WHERE username = '$usn' AND original = '$c'";

				$result = $conn->query($query2);

				if (!$result or $result->num_rows == 0) 
				{
			  		echo "No translation found in your dictionary.<br>";
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
						Original: $row[1]
						Translation: $row[2]
						</pre>

						_END;
					}
				}
				


			}

			$result->close();
		}



		if(isset($_SESSION['username']))
	{
		$usn = sanitizeMySQL($conn,$_SERVER['PHP_AUTH_USER']);
		$query = "SELECT * FROM usercontent  WHERE username = '$usn'";

		$content_result = $conn->query($query);

		if (!$content_result or $content_result->num_rows == 0) 
		{
			die("<br>You haven't uploaded a dictionary or your dictionary can't be retrieved.<br>");
		}

		else
		{
			$rows = $content_result->num_rows;
			echo "<br>Below is your uploaded dictionary:<br>";

			for ($j = 0 ; $j < $rows ; ++$j)
			{
				$content_result->data_seek($j);
				$row = $content_result->fetch_array(MYSQLI_NUM);
				echo <<<_END
				<pre>
				Orignal: $row[1]
				Translation: $row[2]
				</pre>
				_END;
			}
		}
		$content_result->close();
	}




	function destroy_session_and_data() {
		$_SESSION = array();
		setcookie(session_name(), '', time() - 2592000, '/');
		session_destroy();

		$_SERVER['PHP_AUTH_USER'] = "";
  		$_SERVER['PHP_AUTH_PW'] = "";
		unset($_SERVER['PHP_AUTH_USER']);
  		unset($_SERVER['PHP_AUTH_PW']);

	}


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
?>
