<?php

require 'admin/config_write.php';

// Create a salt that will be saved using some random data
mt_srand() ; 
$salt = base64_encode( "".mt_rand(mt_getrandmax()/10, mt_getrandmax())."".mt_rand(mt_getrandmax()/10, mt_getrandmax()) ) ;
$salt = substr($salt, 0, 16) ; 


// Input data validation
$error = '' ; 
$submit_notify = false ; 

if ( !empty($_POST["email"]) && !filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) )
   $error .= "Invalid email address.\n" ;
else
{
   $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) ? $_POST['email'] : '' ;
   $submit_notify = true ; 
}

if ( empty($_POST["password1"]) /*|| empty($_POST["password2"]) || $_POST["password1"] != $_POST["password2"]*/ )
   $error .= "Passwords entered do not match or are not filled.\n" ;
else
   $hash = crypt($_POST["password1"], '$6$rounds=20000$'.$salt.'$') ;

// Make sure no error was present in the user data verification
if ( !empty($error) )
   return_error('Invalid email address.'); 


// There's no way to verify at this point that the SQL parameters are correct.
// Simply trim the data in case a whitespace has slipped through
$config = array(
   'sqlhost'  => trim($_POST["sqlhost"]),
   'sqluser'  => trim($_POST["sqluser"]),
   'sqlpass'  => $_POST["sqlpass"],
   'database' => trim($_POST["database"]),
   'salt'     => $salt 
);

$username = $_POST["username"];
$config_folder = $_POST["config_folder"];

// SQL queries to be executed. 
$queries = array( 
   array(
      "query"   => "CREATE DATABASE IF NOT EXISTS " . $config['database'] . " CHARACTER SET utf8 COLLATE utf8_general_ci;", 
      "error"   => "MySQL connection successful but error setting up database : "),
   array(
      "query"   => "USE " . $config['database'],
      "success" => "Database " . $config['database'] . " set up successfully.",
      "error"   => "Unable to set the database to use : "),
   array(
      "query"   => "DROP  TABLE IF EXISTS cibl_data ;"),
   array(
      "query"   => "CREATE TABLE cibl_data ( increment int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, url text NOT NULL, plate text NOT NULL, state tinytext NOT NULL, date_occurrence timestamp DEFAULT '0000-00-00 00:00:00', date_added timestamp DEFAULT CURRENT_TIMESTAMP, gps_lat float(10,6) NOT NULL, gps_long float(10,6) NOT NULL, street1 text NOT NULL, street2 text NOT NULL, description text NOT NULL ) ; ",
      "success" => "Records table populated successfully.",
      "error"   => "MySQL error populating database : "),
   array(
      "query"   => "DROP  TABLE IF EXISTS cibl_queue ;"),
   array(
      "query"   => "CREATE TABLE cibl_queue ( increment int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, url text NOT NULL, plate text NOT NULL, state tinytext NOT NULL, date_occurrence timestamp DEFAULT '0000-00-00 00:00:00', date_added timestamp DEFAULT CURRENT_TIMESTAMP, gps_lat float(10,6) NOT NULL, gps_long float(10,6) NOT NULL, street1 text NOT NULL, street2 text NOT NULL, description text NOT NULL ) ; ",
      "success" => "Submission queue table populated successfully.",
      "error"   => "MySQL error populating database : "),
   array(
      "query"   => "DROP  TABLE IF EXISTS cibl_users ;"),
   array(
      "query"   => "CREATE TABLE cibl_users ( username CHAR(30) NOT NULL, hash CHAR(60) NOT NULL, admin BOOLEAN NOT NULL, submit_notify BOOLEAN NOT NULL, email CHAR(255) ) ; ",
      "success" => "Logins table populated successfully.",
      "error"   => "MySQL error populating database : "),
   array(
      "query"   => "INSERT INTO cibl_users VALUES ('" . $username . "', '" . $hash . "', TRUE, " . $submit_notify . ", '" . $email . "'); ",
      "success" => "Admin credentials saved.",
      "error"   => "MySQL error saving admin credentials : ")
 ) ;


// Open the MySQL connection
$connection = new mysqli($config['sqlhost'], $config['sqluser'], $config['sqlpass']);
if ($connection->connect_error) 
{
	return_error("MySQL connection failed: " . $connection->connect_error);
} 

// Execute all queries specified above
$progress = "" ;
foreach ( $queries as $query )
{
   $resultQuery = $connection->query($query["query"]) ; 
   if ( $resultQuery === TRUE && !empty($query["success"]) ) 
   {
      $progress .= $query["success"]."<br>";
   } 
   else if ( $resultQuery === FALSE && !empty($query["error"]) )
   {
      return_error ( $query["error"] . $connection->error ) ;
   }
}

$connection->close();


//MAKE SURE CONFIG FOLDER PATH IS VALID AND DOES NOT ALREADY EXIST
$path_parts = explode('/', $config_folder);
array_pop($path_parts);
$config_parent = implode('/', $path_parts);
if (!file_exists($config_parent))
{
	return_error("Config folder path not valid");
}


//MOVE AND RENAME CONFIG FOLDER
if (!rename('config', $config_folder))
{
	return_error("Problem setting up configuration folder.");
}

//CREATE POINTER TO CONFIG FILE
$config_pointer = fopen('admin/config_pointer.php', 'w');
$pointer_contents = 
	"<?php \n" . 
	"include ('" . $config_folder . "/config.php');\n" . 
	"\$config_folder = '" . $config_folder . "';\n" .  
	"\$config_location = '" . $config_folder . "/config.php';\n" .
	"?>";
fwrite($config_pointer, $pointer_contents);
fclose($config_pointer);

//CREATE CONFIG FILE, CREATE EMPTY DIRECTORIES, SWAP SETUP AND MAIN INDEX PAGE
config_write($config);
mkdir("images");
mkdir("thumbs");
rename('index.php', 'index_old.php');
rename('index_actual.php', 'index.php');

$progress .= "Setup complete!<br>";
$progress .= "<script>location.href = 'index.php?setup_success_dialog=true';</script>";
echo $progress;

function return_error($error)
{
	error_log($error);
	$error_parsed = rawurlencode($error);
	$url = 'Location: index.php?error=' . $error_parsed;
	header($url);
	exit();
}

?>