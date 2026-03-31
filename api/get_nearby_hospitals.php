<?php
// This file returns REAL nearby hospitals using Google Maps Places API

if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
    echo json_encode(["error" => "Location not provided"]);
    exit;
}

$lat = $_GET['lat'];
$lng = $_GET['lng'];

/*
⚠️ IMPORTANT
Replace ONLY this key with your Google Maps API key
(do NOT use Gemini key here)
*/
$GOOGLE_MAPS_API_KEY = "PASTE_YOUR_GOOGLE_MAPS_API_KEY_HERE";

$url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?" .
       "location={$lat},{$lng}" .
       "&radius=5000" .
       "&type=hospital" .
       "&key={$GOOGLE_MAPS_API_KEY}";

$response = file_get_contents($url);

header("Content-Type: application/json");
echo $response;
