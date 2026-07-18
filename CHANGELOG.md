## 🏷️ v1.0.1

### ✨ Added
- 📅 `?month=` (1-12) and `?year=` URL parameters to pick which month to view, replacing a hardcoded value. Missing/invalid input shows a styled month-picker page instead of erroring out.
- ◀️▶️ Prev / next month navigation arrows in the header, with year wraparound at Dec ↔ Jan.
- 💾 Central, server-side storage (`data/state_{year}_{month}.json`) for watch status and visit history — replaces `localStorage`, so state is now shared across every device instead of being per-browser.
- 🎉 Animated "pop" effect on the stats numbers (Total / Watching / Not Watching) whenever a value changes.
- 🧾 Footer with credit line and a version tag driven by `$TraktVersion`.

### 🛠️ Changed
- 🔐 Trakt API credentials moved out of the script into a git-ignored `config.php` (see `config.example.php`).
- 🖼️ Site logos/icons switched from a Backblaze-hosted CDN to local files under `images/`, using relative paths (works whether the site is hosted at a domain root or in a subfolder).

📖 **Full Changelog**: see [CHANGELOG.md](../../blob/main/CHANGELOG.md)
