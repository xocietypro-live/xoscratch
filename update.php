<?php
/**
 * OTT Playlist to JSON Converter & Proxy (Enhanced Version)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// --- CONFIGURATION ---
$targetPlaylist = "https://example.com/playlist.m3u"; 
// ---------------------

function fetchAndParse($url) {
    $ch = curl_init();
    
    // cURL configuration to mimic a browser and bypass common blocks
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    // Forces IPv4 resolution (Fixes most "HTTP Code 0" issues on local servers)
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    
    $headers = [
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Cache-Control: no-cache',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $data = curl_exec($ch);
    
    // Error Handling for "Code 0"
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return [
            "status" => "error",
            "message" => "CURL Error: " . $error_msg,
            "hint" => "Check your internet connection or firewall. Code 0 often means DNS or SSL failure."
        ];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return [
            "status" => "error",
            "message" => "Failed to fetch playlist. HTTP Code: $httpCode"
        ];
    }

    return parseM3U($data);
}

function parseM3U($data) {
    $channels = [];
    // Standardize line endings to prevent explode issues
    $data = str_replace(["\r\n", "\r"], "\n", $data);
    $lines = explode("\n", $data);
    $tempItem = null;

    foreach ($lines as $line) {
        $line = trim($line);

        if (empty($line)) continue;

        // Matches #EXTINF lines
        if (strpos($line, '#EXTINF:') === 0) {
            $tempItem = [];
            
            // Regex to extract key-value attributes (logo, group)
            preg_match('/tvg-logo="(.*?)"/', $line, $logo);
            $tempItem['logo'] = $logo[1] ?? '';
            
            preg_match('/group-title="(.*?)"/', $line, $group);
            $tempItem['group'] = $group[1] ?? 'Uncategorized';
            
            // Extract Name (the text after the last comma)
            $commaPos = strrpos($line, ',');
            if ($commaPos !== false) {
                $tempItem['name'] = trim(substr($line, $commaPos + 1));
            } else {
                $tempItem['name'] = 'Unknown Channel';
            }
        } 
        // Matches the URL line (any non-empty line that doesn't start with #)
        elseif (strpos($line, '#') !== 0 && $tempItem !== null) {
            $tempItem['url'] = $line;
            $channels[] = $tempItem;
            $tempItem = null; // Reset for next channel
        }
    }

    return [
        "status" => "success",
        "total_channels" => count($channels),
        "data" => $channels
    ];
}

// Execute and output
$result = fetchAndParse($targetPlaylist);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
