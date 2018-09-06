<html>
	<head><title>Yifat's Search</title></head>

<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
<style>
.w3-btn {margin-bottom:10px;} 

input[type=text] {
    width: 20%;
    box-sizing: border-box;
    border: 2px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    background-color: white;
    background-position: 10px 10px; 
    background-repeat: no-repeat;
    padding: 12px 20px 12px 40px;
    -webkit-transition: width 0.4s ease-in-out;
    transition: width 0.4s ease-in-out;
}

input[type=text]:focus {
    width: 25%;
}

</style>

<body style="font-family: Monospace; ">

<?php
$serverName = "FILL_IN.database.windows.net";
$connectionOptions = array(
    "Database" => "FILL_IN",
    "Uid" => "FILL_IN",
    "PWD" => "FILL_IN"
);
//Establishes the connection
$conn = sqlsrv_connect($serverName, $connectionOptions);
?>

<div class="w3-panel w3-pink">
  <h1 class="w3-opacity" >
  <b>Search For An Instagram Profile</b></h1>
</div>

<div style="margin-left: 50px">
<form name="bing" method="get" onsubmit="return newBingWebSearch(this)">
<input required="required" type="text" name="name" placeholder="Search.." ><br><br>
<button class="w3-btn w3-white w3-border w3-border-grey w3-round-large" type="submit" value="submit" name="submit">submit</button>
</form>
</div>

<?php
//handle search form
if (isset($_GET["submit"])) {

$accessKey = 'FILL_IN';
$endpoint = 'https://api.cognitive.microsoft.com/bing/v7.0/search';

$term = 'Instagram '.$_GET["name"];

//adding term to search history table
$tsql= "INSERT INTO search_history (search_val) values ('".$_GET["name"]."');";
$getResults= sqlsrv_query($conn, $tsql);

if ($getResults == FALSE) {
    echo (sqlsrv_errors());
}
sqlsrv_free_stmt($getResults);

//search term in Bing
function BingWebSearch ($url, $key, $query) {
    $headers = "Ocp-Apim-Subscription-Key: $key\r\n";
    $options = array ('http' => array (
                          'header' => $headers,
                           'method' => 'GET'));

    // Perform the Web request and get the JSON response
    $context = stream_context_create($options);
    $result = file_get_contents($url . "?q=" . urlencode($query), false, $context);

    // Extract Bing HTTP headers
    $headers = array();
    foreach ($http_response_header as $k => $v) {
        $h = explode(":", $v, 2);
        if (isset($h[1]))
            if (preg_match("/^BingAPIs-/", $h[0]) || preg_match("/^X-MSEdge-/", $h[0]))
                $headers[trim($h[0])] = trim($h[1]);
    }

    return array($headers, $result);
}

if (strlen($accessKey) == 32) {
    echo "<div class=\"w3-container\">";
    echo "<div class=\"w3-panel w3-pale-yellow w3-leftbar w3-rightbar w3-border-yellow\" style=\"font-size:120%;\">";

    echo "Searching for the profile of: " . $_GET["name"] . "<br>";

    list($headers, $json) = BingWebSearch($endpoint, $accessKey, $term);

    if (strpos($json, 'webPages') !== false) {    
        $result_process = json_decode($json,true);
        $i = 0;
        $res_len = count($result_process['webPages']['value']);

        while($i < $res_len) {
            $link = $result_process['webPages']['value'][$i]['url'];
            //confirm this is an Instagram page
            if ((strpos($link, 'www.instagram.com') !== false) and (strlen($link) > strlen("https://www.instagram.com/")+1)) {
               $sub_link = substr($link, strlen("https://www.instagram.com/"), -1);
               //confirm this is an Instagram **profile**
               if (strpos($sub_link, '/') !== false) {
                   ++$i;
				   continue;
               }
               else {
                    echo "RESULT: <a href = \"".$link."\">".$link."</a>";
                    break;
               }
            }
			++$i;
        }
		if ($i >= $res_len) {
			echo "No Instagram profiles were found for ".$_GET["name"];			
		} 
    }
    else { 
        echo "No Instagram profiles were found for ".$_GET["name"];
    }
} 
else {

    print("Invalid Bing Search API subscription key!\n");
    print("Please paste yours into the source code.\n");
}
}
echo "</div></div>";

//present search history table
echo "<div class=\"w3-panel w3-pink\"><h1 class=\"w3-opacity\" ><b>Search History:</b></h1></div>";
$tsql= "SELECT search_val FROM search_history ORDER BY search_id DESC;";
$getResults= sqlsrv_query($conn, $tsql);

if ($getResults == FALSE) {
    echo (sqlsrv_errors());
}

echo "<ul style=\"list-style-type:circle\">";  
while ($row = sqlsrv_fetch_array($getResults, SQLSRV_FETCH_ASSOC)) {
    echo "<li>".$row['search_val'] ."</li>";
}
echo "</ul>";
sqlsrv_free_stmt($getResults);
  
?>

</body>
</html>
