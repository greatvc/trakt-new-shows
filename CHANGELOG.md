# Changelog

All notable changes to this project are documented here. Format loosely follows [Keep a Changelog](https://keepachangelog.com/).

## 🏷️ [v2.2.0] - 8/9/26 ![Release](https://img.shields.io/badge/Release-22c55e)

### ✨ Added
- 👀 New-show cards now get two dedicated buttons — eye.png ("mark as watching") and eyeclosed.png ("mark as not watching") — right where the toggle normally sits, so you can resolve a fresh premiere in one click without hunting for the toggle under the badge.
- 🎉 The "X shows joined the lineup" counter now pops with the same boom animation as the stats numbers — on page load, and again every time you resolve a new show, counting down live as you go. Once every new show is resolved, the message clears itself instead of sitting at "0 shows joined the lineup".
- 🛈 The "X shows dropped off" message now shows a hover tooltip (cursor turns into a "?") listing exactly which shows disappeared, as "Show Name (Year)" - previously you'd just get the count with no idea which ones left.

### 🛠️ Changed
- 🖼️ Watch-toggle icon switched from an inline vector eye graphic to eye.png / eyeclosed.png image assets, with a tooltip that now says exactly what clicking it will do ("Click to mark as watching" / "...as not watching").
- 🎨 "Not watching" state is now much easier to spot at a glance — the toggle button itself gets a reddish tint (matching the "not watching" ribbon color) instead of a subtle opacity fade that tended to disappear against dark posters. Hovering it to switch back to watching is now much more vivid too - the icon snaps to full color with a stronger glow, instead of staying washed out mid-hover.
- ✨ NEW SHOW badge made larger and bolder with a gradient background, plus a soft pulsing glow so it doesn't blend into the poster art.
- 🔁 A show that temporarily drops off the calendar and later reappears (e.g. Trakt correcting a wrong air date) is now treated as a genuinely fresh premiere - any stale "not watching" mark from before it disappeared is cleared, instead of showing a confusing mix of "not watching" styling next to the NEW SHOW badge.

## 🏷️ [v2.1.0] - 2/8/26

### 🛠️ Changed
- 🔓 Removed the Trakt OAuth access token requirement entirely. After extensive live testing — deliberately supplying an invalid token, and cross-checking a different calendar month to rule out caching — it's confirmed that the `/calendars/all/` endpoint this script uses (including genre, country, and network filtering) works correctly with just the Client ID (`trakt-api-key` header). No access token, no OAuth device/authorization flow, and no VIP subscription are needed for anything this script does. This contradicts the general "advanced filtering requires VIP" guidance in Trakt's docs, which appears to describe the trakt.tv website's own calendar UI rather than this specific public API endpoint.
- 📄 `config.example.php` now only asks for `$TraktClientId`. Existing `config.php` files with a leftover `$TraktAccessToken` line are unaffected — it's just no longer read anywhere, nothing to change on your end.
- 📖 README simplified accordingly: removed the Device Code Flow token setup guide and the "requires VIP" warning, replaced with a short note on how this was confirmed.

## 🏷️ [v2.0.0] - 28/7/26

### 💥 Breaking
- 📛 Renamed the main script from `trakt_new_shows_fixed.php` to `trakt.php`. If you have this bookmarked or scheduled somewhere, update the URL/path after updating — the old filename will 404. Versioning going forward is handled by git tags/releases and this CHANGELOG, so the `_fixed` suffix (and the rest of the old descriptive name) from earlier iterations no longer made sense to keep.

### ✨ Added
- 📖 New README section on obtaining a Trakt access token via the Device Code Flow, including a note that Trakt access tokens expire after 7 days and need to be regenerated periodically (credit: feedback from a Reddit user on r/trakt).

## 🏷️ [v1.3.1] - 27/7/26

### 🛠️ Changed
- 🎨 Several visual and text changes.

## 🏷️ [v1.3.0] - 21/7/26

### ✨ Added
- 🖼️ Network logo chips (Netflix, HBO, Apple TV+, etc.) instead of the generic 📡 emoji. Looked up dynamically via TMDB using the show's own TMDB id from Trakt (not a name-based guess), and cached locally after the first fetch — so it's fast on every later view and never re-hits the TMDB API for a network it's already seen. Fully optional: without a `$TmdbApiKey` in `config.php`, chips simply fall back to `📡 Network Name` exactly as before, nothing breaks.

## 🏷️ [v1.2.0] - 20/7/26

### ✨ Added
- 🖼️ Custom "No Shows Found" illustration on the empty state, replacing the generic 🕵️ emoji.
- 🏳️ Flag emoji for each show's country chip, replacing the generic 🌍 globe icon — covers all 35 countries in `$TraktCountries`, with hover tooltip showing the full country name. Falls back to full name (with 🌍) or raw code (with 🌍) if a flag is ever unavailable for a given code.

### 🛠️ Changed
- 🔢 The premieres chip now reads "No Shows" instead of "0 premieres" when a month has no matches; unchanged for 1+ results.
- 💬 Empty state message updated to "No new shows matched your filters for this month."

## 🏷️ [v1.1.0] - 19/7/26

### ✨ Added
- 🆕 Green "NEW SHOW" badge on any premiere that wasn't present the last time the page was visited, so newly added shows stand out at a glance. Doesn't appear on the very first run (no previous data to compare against yet), and is hidden on cards already marked "not watching" to avoid clutter.

### 🛠️ Changed
- 🎨 Minor visual changes (footer spacing cleanup).

## 🏷️ [v1.0.1] - 18/7/26

### ✨ Added
- 📅 `?month=` (1-12) and `?year=` URL parameters to pick which month to view, replacing a hardcoded value. Missing/invalid input shows a styled month-picker page instead of erroring out.
- ◀️▶️ Prev / next month navigation arrows in the header, with year wraparound at Dec ↔ Jan.
- 💾 Central, server-side storage (`data/state_{year}_{month}.json`) for watch status and visit history — replaces `localStorage`, so state is now shared across every device instead of being per-browser.
- 🎉 Animated "pop" effect on the stats numbers (Total / Watching / Not Watching) whenever a value changes.
- 🧾 Footer with credit line and a version tag driven by `$TraktVersion`.

### 🛠️ Changed
- 🔐 Trakt API credentials moved out of the script into a git-ignored `config.php` (see `config.example.php`).
- 🖼️ Site logos/icons switched from a Backblaze-hosted CDN to local files under `images/`, using relative paths (works whether the site is hosted at a domain root or in a subfolder).

## 🏷️ [v1.0.0] - Initial version

### ✨ Added
- 🎬 First working version: fetches new show premieres for a hardcoded month from the Trakt API, filtered by genre/country, grouped by day, with a "not watching" toggle saved to `localStorage`.
