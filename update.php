<?php
$url = "https://ksr.indevs.in/playlist/playlist.php?token=b2e48c058bd3ab5151161c7457c2e149";
$save_to = "playlist.m3u";

echo "Starting download from: $url\n";

$ch = curl_init($url);
$fp = fopen($save_to, "w");

// Set a common User-Agent (mimics Chrome on Windows)
$user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";

curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, $user_agent); 

// Added to handle potential redirects and cookie management
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_ENCODING, ""); // Handles GZIP compression

if(curl_exec($ch)) {
    echo "Successfully saved to $save_to (" . filesize($save_to) . " bytes)\n";
} else {
    echo "Download failed: " . curl_error($ch) . "\n";
    exit(1);
}

curl_close($ch);
fclose($fp);
?>
