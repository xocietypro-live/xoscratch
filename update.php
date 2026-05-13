<?php
/**
 * OTT Playlist to JSON Converter & Proxy
 * Bypasses basic blocks using custom Headers and cURL
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// --- CONFIGURATION ---
$targetPlaylist = "https://ksr.indevs.in/playlist/playlist.php?token=b2e48c058bd3ab5151161c7457c2e149"; 
// ---------------------

function fetchAndParse($url) {
    $ch = curl_init();
    
    // cURL settings to mimic a real browser and bypass blocks
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follows redirects
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypasses SSL issues
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/115.0.0.0',
        'Accept: */*',
        'Connection: keep-alive'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$data) {
        return ["error" => "Failed to fetch playlist. HTTP Code: $httpCode"];
    }

    return parseM3U($data);
}

function parseM3U($data) {
    $channels = [];
    $lines = explode("\n", $data);
    $tempItem = null;

    foreach ($lines as $line) {
        $line = trim($line);

        if (strpos($line, '#EXTINF:') === 0) {
            $tempItem = [];
            
            // Extract Logo (tvg-logo)
            preg_match('/tvg-logo="(.*?)"/', $line, $logo);
            $tempItem['logo'] = $logo[1] ?? '';
            
            // Extract Group (group-title)
            preg_match('/group-title="(.*?)"/', $line, $group);
            $tempItem['group'] = $group[1] ?? 'Uncategorized';
            
            // Extract Name (after the last comma)
            $nameParts = explode(',', $line);
            $tempItem['name'] = trim(end($nameParts));
        } 
        elseif (!empty($line) && strpos($line, '#') !== 0 && $tempItem !== null) {
            // This line is the Stream URL
            $tempItem['url'] = $line;
            $channels[] = $tempItem;
            $tempItem = null;
        }
    }

    return [
        "status" => "success",
        "count" => count($channels),
        "data" => $channels
    ];
}

// Execute and output
echo json_encode(fetchAndParse($targetPlaylist), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
