<?php
// ============================================================================
// CONFIG TEMPLATE
// ============================================================================
// 1. Copy this file to "config.php" (same folder).
// 2. Fill in your Trakt Client ID below.
// 3. "config.php" is listed in .gitignore and will NEVER be committed to git.
//
// Get your Client ID at: https://app.trakt.tv/settings/apps/api
// (create an app there if you don't have one yet - no OAuth/authorization needed,
// just the Client ID shown after creating the app)
// ============================================================================

$TraktClientId = "YOUR_TRAKT_CLIENT_ID_HERE";

// Optional: enables network logo chips (Netflix/HBO/etc. logos instead of the 📡 emoji).
// Get a free "API Read Access Token" at https://www.themoviedb.org/settings/api
// Leave as null (or delete the line) to skip this feature - falls back to 📡 + name.
$TmdbApiKey = "YOUR_TMDB_READ_ACCESS_TOKEN_HERE";
