<?php
// playlist.php
header('Content-Type: text/plain'); // Show in browser

// URL of the JSON containing match data
$jsonUrl = "https://raw.githubusercontent.com/drmlive/fancode-live-events/main/fancode.json";

// Fetch JSON
$jsonData = file_get_contents($jsonUrl);
if ($jsonData === false) {
    die("# Error fetching JSON data");
}

// Decode JSON
$data = json_decode($jsonData, true);
if ($data === null) {
    die("# Error decoding JSON");
}

// Start M3U
echo "#EXTM3U\n";

// Loop through matches and generate M3U entries
if (isset($data['matches'])) {
    foreach ($data['matches'] as $match) {
        $title    = $match['match_name'] ?? $match['title'] ?? "Unknown Match";
        $logo     = $match['src'] ?? "";
        $category = $match['event_category'] ?? "Sports";
        $status   = strtoupper($match['status'] ?? "");

        // Use adfree_url if available, otherwise dai_url. Skip if none
        $stream = $match['adfree_url'] ?? ($match['dai_url'] ?? null);
        if (!$stream) {
            continue; // Skip this match
        }

        // Add LIVE or UPCOMING tag in the title
        if ($status === "LIVE") {
            $title .= " [LIVE]";
        } elseif ($status === "UPCOMING") {
            $title .= " [UPCOMING at ".$match['startTime']."]";
        }

        // Output M3U entry
        echo "#EXTINF:-1 tvg-id=\"\" tvg-name=\"".htmlspecialchars($title)."\" tvg-logo=\"".htmlspecialchars($logo)."\" group-title=\"".htmlspecialchars($category)."\"," . htmlspecialchars($title) . "\n";
        echo $stream . "\n";
    }
} else {
    echo "# No matches found\n";
}
?>
