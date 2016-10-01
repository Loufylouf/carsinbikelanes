<?php

require 'admin/config_pointer.php';

//VERIFY ATTACHMENT
if (empty($_FILES["image_submission"]["tmp_name"])){
	error("noimage");
}

//CHECK IF ATTACHMENT IS AN IMAGE
if(isset($_POST['upload'])) {
    $check = getimagesize($_FILES["image_submission"]["tmp_name"]);
	//error_log($_FILES["image_submission"]["tmp_name"]);
	//error_log(getimagesize($_FILES["image_submission"]["tmp_name"]));
    if($check !== false){
    	$target_extension = pathinfo(basename($_FILES["image_submission"]["name"]), PATHINFO_EXTENSION);
    	if($target_extension == "jpg" ||
    		$target_extension == "JPG" ||
    		$target_extension == "png" ||
    		$target_extension == "PNG" ||
    		$target_extension == "jpeg" ||
    		$target_extension == "JPEG" ||
    		$target_extension == "gif" ||
    		$target_extension == "GIF")
    	{	
        	//echo "File is good: " . basename($_FILES["image_submission"]["name"]) . " (". $check["mime"] . "). <br>";
        }
		else
		{
    		error("badimage");
    	}     
    } else {
    	error("badimage");
    }
}
//VERIFY COORDINATES WITHIN PROJECT AREA
if ( $_POST["lat"] > $config['north_bounds'] || 
	 $_POST["lat"] < $config['south_bounds'] || 
	 $_POST["lng"] > $config['east_bounds'] || 
	 $_POST["lng"] < $config['west_bounds'] ){
	$message = "";
	if ( $_POST["lat"] != '' && $_POST["lat"] > $config['north_bounds'] ){
		$message .= $_POST["lat"] . " is too far north. ";
	}
	if ( $_POST["lat"] != '' && $_POST["lat"] < $config['south_bounds'] ){
		$message .= $_POST["lat"] . " is too far south. ";
	}
	if ( $_POST["lng"] != '' && $_POST["lng"] > $config['east_bounds'] ){
		$message .= $_POST["lng"] . " is too far east. ";
	}
	if ( $_POST["lng"] != '' && $_POST["lng"] < $config['west_bounds'] ){
		$message .= $_POST["lng"] . " is too far west. ";
	}
	error('badlocation', $message);
}

//VERIFY FOLDER TO UPLOAD INTO OR CREATE IT
$today = getdate();
//IMAGES DIRECTORY
if (!file_exists( "images/" . $today['year'] )){
	mkdir("images/" . $today['year'] . "/"); }
if (!file_exists( "images/" . $today['year'] . "/" . $today['mon'] )){
	mkdir("images/" . $today['year'] . "/" . $today['mon'] . "/"); }
if (!file_exists( "images/" . $today['year'] . "/" . $today['mon'] . "/" . $today['mday'] )){
	mkdir("images/" . $today['year'] . "/" . $today['mon'] . "/" . $today['mday'] . "/"); }
//THUMBS DIRECTORY
if (!file_exists( "thumbs/" . $today['year'] )){
	mkdir("thumbs/" . $today['year'] . "/"); }
if (!file_exists( "thumbs/" . $today['year'] . "/" . $today['mon'] )){
	mkdir("thumbs/" . $today['year'] . "/" . $today['mon'] . "/"); }
if (!file_exists( "thumbs/" . $today['year'] . "/" . $today['mon'] . "/" . $today['mday'] )){
	mkdir("thumbs/" . $today['year'] . "/" . $today['mon'] . "/" . $today['mday'] . "/"); }

//DETERMINE TARGET FILE NAME
$target_dir = $today['year'] . "/" . $today['mon'] . "/" . $today['mday'] . "/";
$target_increment1 = mysqli_fetch_array(mysqli_query($connection, "SELECT MAX(increment) AS increment FROM cibl_data"))[0] + 1;
$target_increment2 = mysqli_fetch_array(mysqli_query($connection, "SELECT MAX(increment) AS increment FROM cibl_queue"))[0] + 1;
$target_increment = ($target_increment1 > $target_increment2) ? $target_increment1 : $target_increment2;
$target_extension = pathinfo(basename($_FILES["image_submission"]["name"]), PATHINFO_EXTENSION);
$target_file = $target_dir . "queue_" . $target_increment . "." . $target_extension;
$target_image = __DIR__ . "/images/" . $target_file;
$target_thumb = __DIR__ . "/thumbs/" . $target_file;

//DETERMINE TIME
$time = date('Y-m-d H:i:s', strtotime($_POST["date"]));

//VALIDATE LICENSE PLATE
$plate = $_POST["plate"];
if (!ctype_alnum($plate)) { error("plate"); }
if (strlen($plate) > 7) { $plate = substr($plate,0,7); }
$plate = strtoupper($plate);

//VALIDATE STREETS
$street1 = mysqli_real_escape_string($connection, $_POST["street1"]);
$street2 = mysqli_real_escape_string($connection, $_POST["street2"]);

//ESCAPE CHARACTERS IN COMMENTS FIELD
$description_string = mysqli_real_escape_string($connection, $_POST["description"]);

//INSERT NEW RECORD INTO DATABASE
$state = $_POST["state"]; $gps_lat = $_POST["lat"]; $gps_long = $_POST["lng"];
$row_added = "INSERT INTO cibl_queue (increment, url, plate, state, date_occurrence, gps_lat, gps_long, street1, street2, description)
	VALUES (" . $target_increment . ", '" .
			$target_file . "', '" .
			$plate . "', '" .
			$state . "', '" .
			$time . "', " .
			$gps_lat . ", " .
			$gps_long . ", '" .
			$street1 . "', '" .
			$street2 . "', '" .
			$description_string . "')";
//echo $row_added . "<br>";
if ($connection->query($row_added) === FALSE) {
	error_log($connection->error);
    error("mysql");
}

//RESIZE AND MOVE RENAMED IMAGE INTO PLACE
$imagick = new Imagick($_FILES['image_submission']['tmp_name']);
$imagick->writeImage($target_image);
$imagick->scaleImage(200, 200, true);
$imagick->writeImage($target_thumb);

//$resized_thumb = resize_image($_FILES["image_submission"]["tmp_name"], 200, 200);
//$save_thumb = imagejpeg($resized_thumb, $target_thumb, 90);
//$save_image = move_uploaded_file($_FILES["image_submission"]["tmp_name"], $target_image);

//if ($save_image == false){
//	error("mysql");
//}

$submission_details = array(
	'id' => $target_increment,
	'plate' => $plate,
	'state' => $state,
	'date' => $time,
	'lat' => $gps_lat,
	'lon' => $gps_long,
	'street1' => $street1,
	'street2' => $street2,
	'description' => $description_string
);
success($config, $connection, $submission_details);

/*
//IMAGE RESIZE FUNCTION
function resize_image($file, $w, $h, $crop=FALSE) {
    list($width, $height) = getimagesize($file);
    $r = $width / $height;
    if ($crop) {
        if ($width > $height) {
            $width = ceil($width-($width*abs($r-$w/$h)));
        } else {
            $height = ceil($height-($height*abs($r-$w/$h)));
        }
        $newwidth = $w;
        $newheight = $h;
    } else {
        if ($w/$h > $r) {
            $newwidth = $h*$r;
            $newheight = $h;
        } else {
            $newheight = $w/$r;
            $newwidth = $w;
        }
    }
    $info = getimagesize($file);
    if ($info['mime'] == 'image/jpeg') 
		$src = imagecreatefromjpeg($file);
	elseif ($info['mime'] == 'image/gif') 
		$src = imagecreatefromgif($file);
	elseif ($info['mime'] == 'image/png') 
		$src = imagecreatefrompng($file);
    
    $dst = imagecreatetruecolor($newwidth, $newheight);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
	
    return $dst;
}
*/

function error($type, $message = '') {
	echo "\n <div class=\"top_dialog_button\" id=\"close\">";
	echo "\n <span>&#x2A09</span>";
	echo "\n </div>";
	
	if ($type == "noimage"){
		echo "\n <h2>Error:</h2>";
		echo "\n <p class=\"submit_detail\">Submissions without an image attached are currently not accepted.</p>";
	}
	if ($type == "badimage"){
		echo "\n <h2>Error:</h2>";
		echo "\n <p class=\"submit_detail\">You must submit a JPG, JPEG, GIF or PNG image.</p>";
	}
	if ($type == "badlocation"){
		echo "\n <h2>Error:</h2>";
		echo "\n <p class=\"submit_detail\">No valid location marked within project area, please mark a valid location on the map.</p>";
		if ($message != ''){ echo "\n<p class=\"submit_detail\">" . $message . "</p>"; }
	}
	if ($type == "mysql"){
		echo "\n <h2>Error:</h2>";
		echo "\n <p class=\"submit_detail\">Something is wrong with the server. Maybe try again later?</p>";
	}
	if ($type == "plate"){
		echo "\n <h2>Error:</h2>";
		echo "\n <p class=\"submit_detail\">License plates must only contain letters and numbers.</p>";
	}
	
	if ($_POST['source'] == 'desktop'){
		echo "\n\n<script>";
		echo "\n $('#close').click( function() {";
		echo "\n 	$(\"#results_form\").animate({opacity: 'toggle', right: '-565px'});";
		echo "\n	open_window('submit_view');";
		echo "\n });";
		echo "\n\n</script>";
	}
	if ($_POST['source'] == 'mobile'){
		echo "\n\n<script>";
		echo "\n $(document).ready(function() {";
		echo "\n 	$(\"#close\").click( function() {";
		echo "\n		open_window('submit_view');";
		//echo "\n 		$(\"#results_view\").empty();";
		echo "\n 	});";
		echo "\n });";
		echo "\n\n</script>";
	}
	
	die();
}

function success($config, $connection, $submission_details) {
		$email_op = 'new_submission';
		include 'email_notify.php';
	
		echo "\n <div class=\"top_dialog_button\" id=\"close\">";
		echo "\n <span>&#x2A09</span>";
		echo "\n </div>";

		echo "\n <h2>Submission received!</h2>";
		echo "\n <p class=\"submit_detail\">Thank you for contributing!
		All submissions require moderator approval before being added to the map.
		Expect yours to show up within 24 hours.</p>";
		echo "\n <button id='submit_another'>Submit Another</button>";
		
		echo "\n\n<script>";
		
		if ($_POST['source'] == 'desktop'){
			echo "\n $('#submit_another').click( function() {";
			echo "\n 	$(\"#results_form\").animate({opacity: 'toggle', right: '-565px'});";
			echo "\n 	document.getElementById(\"the_form\").reset();";
			echo "\n	open_window('submit_view');";
			echo "\n });";
			
			echo "\n $('#close').click( function() {";
			echo "\n 	$(\"#results_form\").animate({opacity: 'toggle', right: '-565px'});";
			echo "\n 	open_window('entry_list');";
			echo "\n });";
		}
		
		if ($_POST['source'] == 'mobile'){
			echo "\n $(document).ready(function() {";
			echo "\n	 $('#close').click( function() {";
			echo "\n 		document.getElementById(\"mobile_submission_form\").reset();";
			echo "\n 		$('#image_prompt').html('TAP TO ADD AN IMAGE');";
			echo "\n		$('#submit_view').scrollTop(0);";
			echo "\n		open_window('entry_view');";
			echo "\n 	});";
			echo "\n });";
			
			echo "\n $('#submit_another').click( function() {";
			echo "\n 		reset_form();";
			echo "\n });";
		}
		
		echo "\n\n</script>";
}

?> 
