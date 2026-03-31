<?php
require_once __DIR__ . "/config/gemini.php";

echo "API KEY LOADED: ";
echo defined("GEMINI_API_KEY") ? "YES" : "NO";
