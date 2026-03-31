<?php
echo "Checking all bind_param calls for issues...\n";

// Read the current file and analyze
$file_content = file_get_contents('dashboard/doctor_dashboard.php');

// Find all bind_param calls and check them
preg_match_all('/mysqli_stmt_bind_param\([^,]*\)/', $file_content, $matches);

echo "Found " . count($matches[0]) . " bind_param calls\n";

foreach ($matches[0] as $index => $match) {
    echo "Call " . ($index + 1) . ": " . trim($match) . "\n";
}

echo "\nAnalysis complete. Check each call manually.\n";
?>
