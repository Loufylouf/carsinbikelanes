<?php

require 'admin/config_pointer.php';

$id = $_GET["id"];

$full_query = "SELECT * FROM cibl_data WHERE increment =" . $id . " LIMIT 1";
$entries = mysqli_query($connection, $full_query);
$row = mysqli_fetch_array($entries);

$image_url = $row[1];
echo '<img src="images/' . $image_url . '" id="fullsize" class="fullsize" />';

echo "\n\n <div class='column_entry single_view_column_entry' style='background: transparent');'>";

//---SECTION 2: DETAILS---
echo "\n <div class='moderation_queue_details'>";

	//---SECTION 2.TOP: PLATE AND DETAILS---
echo "\n <div class='details_top'>";

		//---SECTION 2.TOP.LEFT: PLATE---
echo "\n <div class='details_plate'>";	
echo "\n <div class='plate_name'><div><br/><h2>#" . $row[0] . ":</h2></div>";
echo "\n <div class='info plate_container plate_link' id='plate" . $row[0] . "' onclick='event.stopPropagation();plate_search(\"" . $row[2] . "\")'>";

if ($row[3] == "NYPD"){
	$plate_split = str_split($row[2], 4);
	echo "\n <div class='plate NYPD'>" . $plate_split[0] . "<span class='NYPDsuffix'>" . $plate_split[1] . "</span></div></div>";
}
else {
	echo "\n <div class='plate ". $row[3] . "'>" . $row[2] . "</div></div>";
}

echo "\n </div>";
echo "\n </div>";

		//---SECTION 2.TOP.RIGHT: TIME AND PLACE---
echo "<div class='details_timeplace'>";
$datetime = new DateTime($row[4]);
$datetime = strtoupper($datetime->format('m/d/Y g:ia'));

echo "\n<span>TIME: </span>";
echo "<div class='info edit_date' id='date" . $row[0] . "'>";
echo "<span>" . $datetime . "</span>";
echo "</div><br/>";

if ($row[8] !== ''){
	echo "\n<span>STREETS: </span>";
	echo "<div id='streets" . $row[0] . "' class='info edit_streets main_font'>";
	echo "<span>" . strtoupper($row[8]);
	if ($row[9] !== ''){
		echo " & " . strtoupper($row[9]);
	}
	echo "</span></div><br/>";
}

echo "\n<span>GPS: </span>";
echo "<div id='gps" . $row[0] . "' class='info edit_gps'><span>";
echo $row[6] . " / " . $row[7];
echo "</span></div>";

echo "\n</div>";
echo "\n</div>";

	//---SECTION 2.BOTTOM: COMMENT---
echo "\n <div>";
if (strlen($row[10]) > 0){
	echo "\n <span>COMMENT:</span>";
	echo "\n <div id='comment" . $row[0] . "'><span>" . nl2br($row[10]) . "</span></div>";
}
echo "\n </div>";

echo "\n </div>";
echo "\n </div>";

if ($config['comments']){ include 'comment.php'; }

echo "\n <script type='text/javascript'> ";
echo "\n $(document).ready(function() { ";
echo "\n var marker" . $row[0] . " = new L.marker([" . $row[6] . ", " . $row[7] . "], {title: '#" . $row[0] . ": " . strtoupper($row[2]) . "'});";
echo "\n";
echo "\n marker" . $row[0] . ".on('click', function(e) {";
echo "\n	zoomToEntry(" . $row[6] . ", " . $row[7] . ", " . $row[0] . ");";
echo "\n });";
echo "\n";
echo "\n var newCount = 0;";
echo "\n newMarkers.eachLayer(function (layer) {";
echo "\n newCount++;";
echo "\n });";
echo "\n";
echo "\n newMarkers.addLayer(marker" . $row[0] . ");";
echo "\n }); ";
echo "\n";
echo "\n </script> ";

?>