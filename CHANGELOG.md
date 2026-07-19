# Changelog

All notable changes to this project are documented here. Format loosely follows [Keep a Changelog](https://keepachangelog.com/).

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
