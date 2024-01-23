<?php

// Enable CORS (Cross-Origin Resource Sharing) to allow requests from different domains
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Get the request method
$method = $_SERVER["REQUEST_METHOD"];

// Handle different request methods
switch ($method) {
    case "GET":
        getEntries();
        break;
    case "POST":
        addEntry();
        break;
    default:
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        break;
}

function getEntries() {
    $entries = [];

    // Get all text files in the current directory
    $textFiles = glob("*.txt");

    // Read entries from each text file
    foreach ($textFiles as $file) {
        $fileEntries = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Extract date from the file name
        $date = pathinfo($file, PATHINFO_FILENAME);

        // Format entries with timestamp, text, and date
        $formattedEntries = array_map(function ($entry) use ($date) {
            list($timestamp, $text) = explode("|", $entry, 2);
            return ["timestamp" => $timestamp, "text" => $text, "date" => $date];
        }, $fileEntries);

        $entries = array_merge($entries, $formattedEntries);
    }

  
    echo json_encode($entries);
}


function addEntry() {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data["timestamp"]) || !isset($data["text"])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid request data"]);
        return;
    }

    // Use the date as the file name
    $fileName = date("Y-m-d") . ".txt";
    $entry = $data["timestamp"] . "|" . sanitizeText($data["text"]) . PHP_EOL;
    file_put_contents($fileName, $entry, FILE_APPEND);

    echo json_encode(["success" => true]);
}

function sanitizeText($text) {
    // Limit characters to alphanumerical, spaces, commas, and periods
    return preg_replace("/[^a-zA-Z0-9\s,.\-]/", "", $text);
}
