<p align="center">
  <img src="images/tvbanner.png" width="320" alt="Trakt New Shows banner">
</p>

<p align="center">
  <img src="images/title-banner.svg" width="420" alt="Trakt New Shows">
</p>

<p align="center">
  A self-hosted PHP page that shows you every new TV show premiering in a given month — something Trakt's official site stopped offering after its <a href="https://forums.trakt.tv/t/new-trakt-feedback/84794/836">V3 redesign</a>.
</p>

<p align="center">
  <img src="https://img.shields.io/badge/license-MIT-e8b545.svg" alt="MIT License">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-4fa3e0.svg" alt="PHP 7.4+">
</p>

---

<div align="center">

https://github.com/user-attachments/assets/7619c048-f2f6-48b8-8592-92448c0cc80a

</div>

---

## Why this exists

Trakt's V3 redesign removed the ability to simply browse "what new shows are premiering this month." This tool brings that back: pick a month, and see every new show premiere, grouped by day, with posters, ratings, genres, and network info.

## Features

- 📅 Browse new show premieres for any month/year (works on any Trakt account)
- 🎬 Grouped by day, with posters, ratings, genres, network & country
- 📡 Country flags and network logos (Netflix, HBO, Apple TV+, etc.) on each card, with graceful emoji/text fallback when unavailable
- ✅ Mark shows as "watching" / "not watching" — synced server-side, so it follows you across devices
- 📈 Tracks premiere counts over time and shows the change since your last visit
- 🆕 Green "NEW SHOW" badge on premieres added since your last visit
- 🖼️ All static assets served locally — no third-party bandwidth used on every page load

### Filtering by genre, country, and network

This script supports all three, purely via query parameters / local filtering — no VIP subscription or OAuth needed (see [Configuration](#configuration) below, and the note at the bottom of this README on how we confirmed this):

- 🌍 Choose which countries' shows to include (or exclude)
- 🎭 Filter out genres you don't care about (e.g. reality, talk shows, anime)
- 📡 Optionally restrict results to specific networks/channels only (e.g. just Netflix, HBO, Apple TV+)

## Configuration

All filtering is controlled by three PHP variables near the top of `trakt.php` (in the `USER CONFIGURATION` section, around line 155):

### Genres — `$TraktGenres`
```php
$TraktGenres = '-animation,-anime,-children,-game-show,-home-and-garden,-music,-reality,-special-interest,-talk-show';
```
A comma-separated list of [Trakt genre slugs](https://trakt.tv/genres). Prefix a genre with `-` to **exclude** it (the default list above excludes reality TV, talk shows, anime, etc.). Leave off the `-` to only **include** that genre instead. Set to `''` (empty string) to disable genre filtering entirely.

### Countries — `$TraktCountries`
```php
$TraktCountries = 'ar,au,at,be,br,ca,cl,cn,co,cz,dk,fi,fr,de,gr,hk,is,in,ie,it,jp,kr,mx,nl,nz,no,pl,pt,za,es,se,ch,tr,gb,us';
```
A comma-separated list of 2-letter [ISO country codes](https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2) — only shows produced in these countries will show up. Remove any codes you don't want, or set to `''` to disable country filtering.

### Networks/channels — `$TraktNetworkFilter`
```php
$TraktNetworkFilter = []; // empty = no network filtering, shows everything

$TraktNetworkFilter = [
    "Netflix", "Prime Video", "HBO", "Apple TV+", "Disney+"
];
```
A PHP array of exact network names (case-sensitive, must match Trakt's naming). Leave it as `[]` to show shows from every network. Fill it in to **only** show premieres from those specific channels/services. A large commented-out example list is included right below it in the file — uncomment and trim it to what you want.

### Network logos — `$TmdbApiKey` (optional)
```php
$TmdbApiKey = "your-tmdb-read-access-token";
```
Optional. Get a free "API Read Access Token" at [themoviedb.org/settings/api](https://www.themoviedb.org/settings/api). When set, each show's network chip shows the actual brand logo (Netflix, HBO, Apple TV+, etc.) instead of the 📡 emoji — fetched once per network via TMDB and cached locally in `images/networks/` from then on. Leave it unset to skip this entirely; chips just fall back to `📡 Network Name` as before.

## Setup

1. Clone or download this repository to your PHP-capable web server
2. Copy `config.example.php` to `config.php` and set `$TraktClientId` — get one at [app.trakt.tv/settings/apps/api](https://app.trakt.tv/settings/apps/api) (create an app there if needed; no OAuth authorization step needed, just the Client ID)
3. Make sure the `data/` folder is writable by the web server (it stores your watch-status per month)
4. Visit `yoursite.com/trakt.php?month=7`

`config.php` is git-ignored and will never be committed — your Client ID stays local to your server.

> **Note on authentication:** earlier versions of this README asked for a Trakt OAuth access token as well, following the API docs' general guidance. After extensive live testing (including deliberately supplying an invalid/garbage token and cross-checking against a different calendar month to rule out caching), it turned out the `/calendars/all/` endpoint this script uses — including genre, country, and network filtering — works correctly with just the Client ID. No access token, no OAuth flow, no VIP subscription required. If a future version of this script ever needs personalized/VIP-gated data, an access token setup guide will be added back at that point.

## Requirements

- PHP 7.4+ with cURL enabled
- A free [Trakt API](https://app.trakt.tv/settings/apps/api) application (Client ID only)

## License

Released under the [MIT License](LICENSE).

