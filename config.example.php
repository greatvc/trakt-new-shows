<?php
// ============================================================================
// CONFIG TEMPLATE
// ============================================================================
// 1. Copy this file to "config.php" (same folder).
// 2. Fill in your own Trakt API credentials below.
// 3. "config.php" is listed in .gitignore and will NEVER be committed to git.
//
// Get your credentials at: https://trakt.tv/oauth/applications
// ============================================================================

$TraktClientId    = "YOUR_TRAKT_CLIENT_ID_HERE";
$TraktAccessToken = "YOUR_TRAKT_ACCESS_TOKEN_HERE";

// Optional: enables network logo chips (Netflix/HBO/etc. logos instead of the 📡 emoji).
// Get a free "API Read Access Token" at https://www.themoviedb.org/settings/api
// Leave as null (or delete the line) to skip this feature - falls back to 📡 + name.
$TmdbApiKey = "YOUR_TMDB_READ_ACCESS_TOKEN_HERE";
