![Trakt New Shows banner](./images/tvbanner.png)

<h1 align="center">Trakt New Shows</h1>

<p align="center">
  A self-hosted PHP page that shows you every new TV show premiering in a given month — something Trakt's official site stopped offering after its <a href="https://forums.trakt.tv/t/new-trakt-feedback/84794" target="_blank">V3 redesign</a>.
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
- ✅ Mark shows as "watching" / "not watching" — synced server-side, so it follows you across devices
- 📈 Tracks premiere counts over time and shows the change since your last visit
- 🖼️ All static assets served locally — no third-party bandwidth used on every page load

### Advanced filtering (requires Trakt VIP)

Trakt gates advanced calendar filtering — by genre, country, and network — behind a [VIP subscription](https://trakt.tv/vip/filtering). This script supports all three:

- 🌍 Choose which countries' shows to include (or exclude)
- 🎭 Filter out genres you don't care about (e.g. reality, talk shows, anime)
- 📡 Optionally restrict results to specific networks/channels only (e.g. just Netflix, HBO, Apple TV+)

If your Trakt account isn't VIP, these filters may be silently ignored by the API. The unfiltered calendar (all new shows for the month) works on any account.

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
