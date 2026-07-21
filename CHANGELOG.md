# Changelog

All notable changes to this project are documented here. Format loosely follows [Keep a Changelog](https://keepachangelog.com/).

## 🏷️ [v1.3.0] - 2026-07-21

### ✨ Added
- 🖼️ Network logo chips (Netflix, HBO, Apple TV+, etc.) instead of the generic 📡 emoji. Looked up dynamically via TMDB using the show's own TMDB id from Trakt (not a name-based guess), and cached locally after the first fetch — so it's fast on every later view and never re-hits the TMDB API for a network it's already seen. Fully optional: without a `$TmdbApiKey` in `config.php`, chips simply fall back to `📡 Network Name` exactly as before, nothing breaks.

## 🏷️ [v1.2.0] - 2026-07-20

### ✨ Added
- 🖼️ Custom "No Shows Found" illustration on the empty state, replacing the generic 🕵️ emoji.
- 🏳️ Flag emoji for each show's country chip, replacing the generic 🌍 globe icon — covers all 35 countries in `$TraktCountries`, with hover tooltip showing the full country name. Falls back to full name (with 🌍) or raw code (with 🌍) if a flag is ever unavailable for a given code.

### 🛠️ Changed
- 🔢 The premieres chip now reads "No Shows" instead of "0 premieres" when a month has no matches; unchanged for 1+ results.
- 💬 Empty state message updated to "No new shows matched your filters for this month."

## 🏷️ [v1.1.0] - 2026-07-19

### ✨ Added
- 🆕 Green "NEW SHOW" badge on any premiere that wasn't present the last time the page was visited, so newly added shows stand out at a glance. Doesn't appear on the very first run (no previous data to compare against yet), and is hidden on cards already marked "not watching" to avoid clutter.

### 🛠️ Changed
- 🎨 Minor visual changes (footer spacing cleanup).

## 🏷️ [v1.0.1] - 2026-07-18

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
