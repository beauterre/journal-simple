<?php
// POST
// Handle POST request to add an entry

$response=[];
		$response['success']=false;// assume something will go wrong..
		$response['error']="unknown error, sorry"; // assume we will not know what..
		$response['data']=[]; // empty array is default data type, no entries
if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // Get request body as JSON
    $json = file_get_contents('php://input');

    // Decode JSON
    $data = json_decode($json, true);

    if ($data !== null && isset($data['timestamp'], $data['text'])) {
        // Clean and lowercase the user name
        $cleanUserName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_GET['user']));
        $response['data']["cleanUserName"] = $cleanUserName;

        if ($cleanUserName == "") {
            $response['error'] = "No user name given";
            // Respond with the JSON-encoded response
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

        // Construct the file name with the user name and today's date
        $fileName = "${cleanUserName}_" . date('Y-m-d') . ".txt";
        $response['data']["filename"] = $fileName;

        // Append entry to the file with error handling
        $putFile = file_put_contents($fileName, time() . "|{$data['text']}\n", FILE_APPEND);

        if ($putFile !== false) {
            // Entry was successfully added to the file
            $response['success'] = true;
            $response['data']['putFile'] = $putFile;
        } else {
            // Error writing to the file
            $response['error'] = 'Failed to write entry to file.';
        }

        // Respond with the JSON-encoded response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
//REQUEST
// Handle GET request to fetch entries
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get user name from query parameters
     $cleanUserName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_GET['user']));
      $response['data']["cleanUserName"] = $cleanUserName;

    // Get date from query parameters, default to today's date if not provided
    $requestDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

    // Get entries for the user and date
    $entries = getEntries($cleanUserName, $requestDate);
	$response['data']['entries'] = $entries; // var-dump it for further testing.
	$response['success']=true;//if we are here, it is a success!
    // Respond with entries
	
	
   header('Content-Type: application/json');
   echo json_encode($response);
    exit;
}
// Function to get entries for a specific user and date
function getEntries($userName, $requestDate)
{
    // Construct the file name with the user name and date
    $fileName = "${userName}_$requestDate.txt";
	
	if(!file_exists($fileName))
	{
		$response['success']=true;//if we are here, it is a success!
		$response['data']['entries'] = [];
		 header('Content-Type: application/json');
	   echo json_encode($response);
		exit;
	}
	
	
	$response['data']['filename'] = $fileName;
	
    // Read existing entries from the file
    $fileEntries = file($fileName, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$response['data']['fileEntries'] = $fileEntries; // var-dump it for further testing.
	$response['success']=true;//if we are here, it is a success!
	 
   $formattedEntries = array_map(function ($entry) use ($requestDate) {
    list($timestamp, $text) = explode("|", $entry, 2);
    return ["timestamp" => $timestamp, "text" => $text, "date" => $requestDate];
	}, $fileEntries);


    // Sort entries by timestamp in descending order
    usort($formattedEntries, function ($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    return $formattedEntries;
}
?>
