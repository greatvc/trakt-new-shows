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

### Advanced filtering (requires Trakt VIP)

Trakt gates advanced calendar filtering — by genre, country, and network — behind a [VIP subscription](https://trakt.tv/vip/filtering). This script supports all three:

- 🌍 Choose which countries' shows to include (or exclude)
- 🎭 Filter out genres you don't care about (e.g. reality, talk shows, anime)
- 📡 Optionally restrict results to specific networks/channels only (e.g. just Netflix, HBO, Apple TV+)

If your Trakt account isn't VIP, these filters may be silently ignored by the API. The unfiltered calendar (all new shows for the month) works on any account.

## Configuration

All filtering is controlled by three PHP variables near the top of `trakt_new_shows_fixed.php` (in the `USER CONFIGURATION` section, around line 155):

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

⚠️ As noted above, genre/country/network filtering are advanced-filter features that Trakt gates behind [VIP](https://trakt.tv/vip/filtering) at the API level.

### Network logos — `$TmdbApiKey` (optional)
```php
$TmdbApiKey = "your-tmdb-read-access-token";
```
Optional. Get a free "API Read Access Token" at [themoviedb.org/settings/api](https://www.themoviedb.org/settings/api). When set, each show's network chip shows the actual brand logo (Netflix, HBO, Apple TV+, etc.) instead of the 📡 emoji — fetched once per network via TMDB and cached locally in `images/networks/` from then on. Leave it unset to skip this entirely; chips just fall back to `📡 Network Name` as before.

## Setup

1. Clone or download this repository to your PHP-capable web server
2. Copy `config.example.php` to `config.php` and add your own Trakt API credentials (get them at [trakt.tv/oauth/applications](https://trakt.tv/oauth/applications))
3. Make sure the `data/` folder is writable by the web server (it stores your watch-status per month)
4. Visit `yoursite.com/trakt_new_shows_fixed.php?month=7`

`config.php` is git-ignored and will never be committed — your credentials stay local to your server.

## Requirements

- PHP 7.4+ with cURL enabled
- A free [Trakt API](https://trakt.tv/oauth/applications) application (Client ID + Access Token)

## License

Released under the [MIT License](LICENSE).
