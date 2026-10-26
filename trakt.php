<?php
// ============================================================================
// VERSION - bump this manually with each release. Shown in the footer.
// ============================================================================
$TraktVersion = 'v2.4.0';

date_default_timezone_set('Europe/Athens');

// Renders a friendly month-selector page when ?month= is missing or invalid,
// styled to match the rest of the site instead of a raw PHP error.
function renderMonthPicker($badValue, $year) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    $currentMonth = (int)date('n');
    $hadValue = ($badValue !== null && $badValue !== '');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pick a Month</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg: #0a0c10; --bg-panel: #12151c; --card: #151923; --card-border: #242a38;
                --gold: #e8b545; --gold-soft: #f4d385; --crimson: #e0384d; --text: #eef0f4;
                --text-dim: #9aa2b1; --text-faint: #5c6478;
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                background: radial-gradient(circle at 15% 0%, rgba(232,181,69,0.08), transparent 40%),
                            radial-gradient(circle at 85% 20%, rgba(224,56,77,0.07), transparent 45%), var(--bg);
                color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh;
                display: flex; align-items: center; justify-content: center; padding: 24px;
            }
            .panel { max-width: 640px; width: 100%; text-align: center; }
            .panel h1 {
                font-family: 'Bebas Neue', sans-serif; font-size: clamp(2rem, 6vw, 3.2rem); letter-spacing: 2px;
                margin-bottom: 14px; display: flex; align-items: center; justify-content: center; gap: 14px;
            }
            .panel h1 .title-text {
                background: linear-gradient(90deg, var(--gold-soft), var(--gold) 40%, var(--crimson));
                -webkit-background-clip: text; background-clip: text; color: transparent;
            }
            .panel h1 .title-emoji {
                font-size: 0.9em; -webkit-text-fill-color: initial; color: initial;
            }
            .panel .msg { color: var(--text-dim); font-size: 0.95rem; margin-bottom: 8px; line-height: 1.5; }
            .panel .bad-value { color: var(--crimson); font-weight: 700; }
            .hint { color: var(--text-faint); font-size: 0.82rem; margin-bottom: 32px; }
            .month-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; }
            .month-btn {
                display: block; padding: 16px 10px; background: var(--card); border: 1px solid var(--card-border);
                border-radius: 12px; color: var(--text); text-decoration: none; font-weight: 700; font-size: 0.95rem;
                transition: all .18s ease;
            }
            .month-btn:hover { border-color: var(--gold); background: rgba(232,181,69,0.1); transform: translateY(-3px); }
            .month-btn.current { border-color: var(--gold); box-shadow: 0 0 0 1px var(--gold); }
            .month-btn .num { display: block; color: var(--text-faint); font-size: 0.7rem; font-weight: 400; margin-top: 4px; }
        </style>
    </head>
    <body>
        <div class="panel">
            <h1><span class="title-emoji">📅</span><span class="title-text">Pick a Month</span></h1>
            <?php if ($hadValue): ?>
                <div class="msg">"<span class="bad-value"><?php echo htmlspecialchars((string)$badValue); ?></span>" isn't a valid month.</div>
            <?php else: ?>
                <div class="msg">No month was specified.</div>
            <?php endif; ?>
            <div class="hint">Add <code>?month=</code> to the URL with a number from 1 to 12, or just pick one below.</div>
            <div class="month-grid">
                <?php foreach ($months as $num => $name): ?>
                    <a class="month-btn<?php echo ($num === $currentMonth) ? ' current' : ''; ?>" href="?month=<?php echo $num; ?>">
                        <?php echo $name; ?>
                        <span class="num"><?php echo $year; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
}

// ============================== USER CONFIGURATION ==============================
// Year can be overridden via ?year= (needed so Dec -> Jan month navigation can cross into the next year)
$requestedYear = $_GET['year'] ?? null;
$TraktYear = ($requestedYear !== null && ctype_digit((string)$requestedYear))
    ? (int)$requestedYear
    : (int)date("Y");

// Month now comes from ?month=1..12 in the URL instead of being hardcoded.
// Missing or invalid value -> show a friendly month-picker page instead of erroring out.
$requestedMonth = $_GET['month'] ?? null;
$isValidMonth = $requestedMonth !== null
    && ctype_digit((string)$requestedMonth)
    && (int)$requestedMonth >= 1
    && (int)$requestedMonth <= 12;

if (!$isValidMonth) {
    renderMonthPicker($requestedMonth, (int)$TraktYear);
    exit;
}
$TraktMonth = (int)$requestedMonth;

// Prev/next month targets for the header navigation arrows (wraps across year boundaries)
$prevMonth = $TraktMonth - 1; $prevYear = $TraktYear;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $TraktMonth + 1; $nextYear = $TraktYear;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// ============================================================================
// CENTRAL STORAGE API (replaces localStorage so state is shared across devices)
// One JSON file per month/year, e.g. data/state_2026_7.json
// GET  ?month=X&api=state          -> returns { notWatching: [], history: [], lastCount: N|null }
// POST ?month=X&api=state (body=JSON of the same shape) -> saves it
// ============================================================================
if (isset($_GET['api']) && $_GET['api'] === 'state') {
    header('Content-Type: application/json');
    $dataDir = __DIR__ . '/data';
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0775, true);
    }
    $stateFile = $dataDir . "/state_{$TraktYear}_{$TraktMonth}.json";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($input)) {
            $toSave = [
                'notWatching' => array_values($input['notWatching'] ?? []),
                'history'     => array_values($input['history'] ?? []),
                'lastCount'   => isset($input['lastCount']) ? (int)$input['lastCount'] : null,
                'lastShows'   => array_values($input['lastShows'] ?? []),
            ];
            if (@file_put_contents($stateFile, json_encode($toSave, JSON_PRETTY_PRINT)) === false) {
                http_response_code(500);
                echo json_encode(['ok' => false, 'error' => 'Could not write state file - check folder permissions on /data']);
            } else {
                echo json_encode(['ok' => true]);
            }
        } else {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON body']);
        }
        exit;
    }

    // GET
    if (file_exists($stateFile)) {
        echo file_get_contents($stateFile);
    } else {
        echo json_encode(['notWatching' => [], 'history' => [], 'lastCount' => null, 'lastShows' => null]);
    }
    exit;
}

$TraktDays  = (int)date('t', strtotime(sprintf("%04d-%02d-01", $TraktYear, $TraktMonth)));

// Your Trakt Client ID lives in config.php (NOT committed to git - see config.example.php).
// No OAuth access token is needed - the /calendars/all/ endpoint used below is public
// and works with just the Client ID (trakt-api-key header). See CHANGELOG for how we confirmed this.
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die('Missing config.php. Copy config.example.php to config.php and fill in your Trakt Client ID.');
}
require $configFile;

// TMDB API key (optional) - used to fetch & locally cache network logos. Add
// $TmdbApiKey = "your-tmdb-read-access-token"; to your config.php to enable this.
// If it's not set, network chips simply fall back to the 📡 emoji + name, same as before -
// nothing breaks if you skip this.
if (!isset($TmdbApiKey)) {
    $TmdbApiKey = null;
}

// ============================================================================
// EXPERIMENTAL HEADER BANNER (temp.php only - not part of trakt.php)
// Real TMDB posters + a TV in the header, ported from the "Το έχω δει;" page
// (index.php)'s own animation: a drifting poster wall behind the title, and
// a TV whose screen cycles through backdrops of the same titles. Entirely
// self-contained (own cache dir, own helper functions, own JSON endpoint) so
// it can't interfere with anything trakt.php already does.
// ============================================================================
define('BANNER_CACHE_DIR', rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'trakt-banner-' . substr(md5(__DIR__), 0, 8));
if (!is_dir(BANNER_CACHE_DIR)) @mkdir(BANNER_CACHE_DIR, 0775, true);

const BANNER_WALL_TITLES = [
    ['tv', 'Lost', 2004],              ['tv', 'Alias', 2001],
    ['tv', 'Friends', 1994],           ['tv', 'Sex and the City', 1998],
    ['tv', 'Gilmore Girls', 2000],     ['tv', 'Grey\'s Anatomy', 2005],
    ['tv', 'Desperate Housewives', 2004], ['tv', 'Breaking Bad', 2008],
    ['tv', 'Game of Thrones', 2011],   ['tv', 'The Office', 2005],
    ['tv', 'Stranger Things', 2016],   ['tv', 'Prison Break', 2005],
    ['tv', 'House', 2004],             ['tv', 'The Crown', 2016],
    ['movie', 'Titanic', 1997],        ['movie', 'The Notebook', 2004],
    ['movie', 'Pretty Woman', 1990],   ['movie', 'La La Land', 2016],
    ['movie', 'The Devil Wears Prada', 2006], ['movie', 'Notting Hill', 1999],
    ['movie', 'Mamma Mia!', 2008],     ['movie', 'Forrest Gump', 1994],
    ['movie', 'Pulp Fiction', 1994],   ['movie', 'Inception', 2010],
    ['movie', 'The Godfather', 1972],  ['movie', 'Barbie', 2023],
];

function banner_cache_get(string $key, int $ttl) {
    $f = BANNER_CACHE_DIR . DIRECTORY_SEPARATOR . md5($key) . '.json';
    if (is_file($f) && (time() - filemtime($f)) < $ttl) {
        $d = json_decode((string)@file_get_contents($f), true);
        if (is_array($d) && array_key_exists('v', $d)) return $d['v'];
    }
    return null;
}

function banner_cache_put(string $key, $value): void {
    @file_put_contents(BANNER_CACHE_DIR . DIRECTORY_SEPARATOR . md5($key) . '.json', json_encode(['v' => $value]), LOCK_EX);
}

function banner_tmdb_headers(string $tmdbKey): array {
    $h = ['Accept: application/json'];
    if (strlen($tmdbKey) > 40) $h[] = 'Authorization: Bearer ' . $tmdbKey;
    return $h;
}

function banner_tmdb_url(string $tmdbKey, string $path, array $params = []): string {
    if (strlen($tmdbKey) <= 40) $params['api_key'] = $tmdbKey;
    return 'https://api.themoviedb.org/3/' . $path . '?' . http_build_query($params);
}

/** Fires every TMDB search in parallel instead of one request at a time. */
function banner_http_multi(array $reqs): array {
    $mh = curl_multi_init();
    $hs = [];
    foreach ($reqs as $k => $r) {
        $ch = curl_init($r['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $r['headers'],
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_ENCODING       => '',
        ]);
        curl_multi_add_handle($mh, $ch);
        $hs[$k] = $ch;
    }
    do {
        $st = curl_multi_exec($mh, $running);
        if ($running) curl_multi_select($mh, 1.0);
    } while ($running && $st === CURLM_OK);
    $out = [];
    foreach ($hs as $k => $ch) {
        $body = (string)curl_multi_getcontent($ch);
        $out[$k] = $body !== '' ? json_decode($body, true) : null;
        curl_multi_remove_handle($mh, $ch);
    }
    curl_multi_close($mh);
    return $out;
}

/** Real TMDB posters + backdrops for the header banner (cached 30 days). */
function banner_poster_wall(?string $tmdbKey): array {
    if (!$tmdbKey) return [];
    $cacheKey = 'wall:' . md5(json_encode(BANNER_WALL_TITLES));
    $cached = banner_cache_get($cacheKey, 2592000);
    if ($cached !== null) return $cached;

    $reqs = [];
    foreach (BANNER_WALL_TITLES as $i => [$kind, $title, $year]) {
        $yearKey  = $kind === 'tv' ? 'first_air_date_year' : 'year';
        $reqs[$i] = ['url' => banner_tmdb_url($tmdbKey, "search/$kind", ['query' => $title, $yearKey => $year]), 'headers' => banner_tmdb_headers($tmdbKey)];
    }
    $out = [];
    foreach (banner_http_multi($reqs) as $r) {
        $h = $r['results'][0] ?? null;
        if (!$h || empty($h['poster_path'])) continue;
        $out[] = [
            'p' => 'https://image.tmdb.org/t/p/w185' . $h['poster_path'],
            'b' => !empty($h['backdrop_path']) ? 'https://image.tmdb.org/t/p/w780' . $h['backdrop_path'] : null,
        ];
    }
    if (count($out) >= 6) banner_cache_put($cacheKey, $out);
    return $out;
}

// JSON endpoint the banner's own JS calls on every page load: ?action=wall
// (keeps whatever ?month=&year= are already in the URL, so it never collides
// with the month-picker / api=state logic above or below it.)
if (($_GET['action'] ?? '') === 'wall') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(['items' => banner_poster_wall($TmdbApiKey)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$TraktGenres    = '-animation,-anime,-children,-game-show,-home-and-garden,-music,-reality,-special-interest,-talk-show';
// $TraktCountries = 'be,dk,fr,de,is,it,kr,mx,no,es,gb,us';
$TraktCountries = 'ar,au,at,be,br,ca,cl,cn,co,cz,dk,fi,fr,de,gr,hk,is,in,ie,it,jp,kr,mx,nl,nz,no,pl,pt,za,es,se,ch,tr,gb,us';

$TraktNetworkFilter = []; // e.g. ["Netflix", "Prime Video"]
/*
$TraktNetworkFilter = [
    "ITV", "ITV1", "ITV2", "ABC", "ABC Family", "Amazon", "AMC", "AMC+",
    "Apple TV+", "BBC", "BBC iPlayer", "BBC UKTV", "CBS", "CBS All Access",
    "CW", "Disney+", "Epix", "FOX", "FX", "HBO", "HBO Max", "Hulu",
    "IMDb TV", "NBC", "Netflix", "Paramount Network", "Paramount+",
    "Paramount+ with Showtime", "Peacock", "Quibi", "Showtime", "Sky",
    "Sky Atlantic", "Sky One", "Sky One (UK)", "Starz", "Starz Encore",
    "Syfy", "The CW", "TNT", "USA Network", "YouTube Premium", "Channel 5"
];
*/

// Served locally from the images/ folder instead of Backblaze, to avoid burning
// B2 bandwidth credits on every page load (these logos never change).
$TraktLogoTop    = 'images/trakttop.png';
$TraktLogoButton = 'images/traktlogo.png';
$TraktNoPoster   = 'images/nopostertv.png';
// ===================================================================================

// Flag emoji + full country name per ISO code, used on each show card instead of a
// generic 🌍 globe icon. Covers every code in $TraktCountries above. If a show ever
// comes back with a country code that isn't in this list (flag unavailable), the
// card falls back to showing the raw code with a 🌍 globe instead of guessing.
$CountryFlags = [
    'ar' => ['flag' => '🇦🇷', 'name' => 'Argentina'],
    'au' => ['flag' => '🇦🇺', 'name' => 'Australia'],
    'at' => ['flag' => '🇦🇹', 'name' => 'Austria'],
    'be' => ['flag' => '🇧🇪', 'name' => 'Belgium'],
    'br' => ['flag' => '🇧🇷', 'name' => 'Brazil'],
    'ca' => ['flag' => '🇨🇦', 'name' => 'Canada'],
    'cl' => ['flag' => '🇨🇱', 'name' => 'Chile'],
    'cn' => ['flag' => '🇨🇳', 'name' => 'China'],
    'co' => ['flag' => '🇨🇴', 'name' => 'Colombia'],
    'cz' => ['flag' => '🇨🇿', 'name' => 'Czechia'],
    'dk' => ['flag' => '🇩🇰', 'name' => 'Denmark'],
    'fi' => ['flag' => '🇫🇮', 'name' => 'Finland'],
    'fr' => ['flag' => '🇫🇷', 'name' => 'France'],
    'de' => ['flag' => '🇩🇪', 'name' => 'Germany'],
    'gr' => ['flag' => '🇬🇷', 'name' => 'Greece'],
    'hk' => ['flag' => '🇭🇰', 'name' => 'Hong Kong'],
    'is' => ['flag' => '🇮🇸', 'name' => 'Iceland'],
    'in' => ['flag' => '🇮🇳', 'name' => 'India'],
    'ie' => ['flag' => '🇮🇪', 'name' => 'Ireland'],
    'it' => ['flag' => '🇮🇹', 'name' => 'Italy'],
    'jp' => ['flag' => '🇯🇵', 'name' => 'Japan'],
    'kr' => ['flag' => '🇰🇷', 'name' => 'South Korea'],
    'mx' => ['flag' => '🇲🇽', 'name' => 'Mexico'],
    'nl' => ['flag' => '🇳🇱', 'name' => 'Netherlands'],
    'nz' => ['flag' => '🇳🇿', 'name' => 'New Zealand'],
    'no' => ['flag' => '🇳🇴', 'name' => 'Norway'],
    'pl' => ['flag' => '🇵🇱', 'name' => 'Poland'],
    'pt' => ['flag' => '🇵🇹', 'name' => 'Portugal'],
    'za' => ['flag' => '🇿🇦', 'name' => 'South Africa'],
    'es' => ['flag' => '🇪🇸', 'name' => 'Spain'],
    'se' => ['flag' => '🇸🇪', 'name' => 'Sweden'],
    'ch' => ['flag' => '🇨🇭', 'name' => 'Switzerland'],
    'tr' => ['flag' => '🇹🇷', 'name' => 'Turkey'],
    'gb' => ['flag' => '🇬🇧', 'name' => 'United Kingdom'],
    'us' => ['flag' => '🇺🇸', 'name' => 'United States'],
];

// ============================================================================
// NETWORK LOGOS (TMDB, dynamically fetched + locally cached)
// The first time a given network is seen, its logo is downloaded from TMDB
// (using the show's own TMDB id from Trakt, so it's the *actual* network for
// that show, not a guess) and saved into images/networks/. Every later card
// for the same network - this run or any future one - reads straight from
// that local file, no repeat API calls. Networks with no logo, or when
// TMDB lookup isn't configured/available, are remembered as "failed" in the
// same manifest so we don't retry every page load; the card just falls back
// to the 📡 emoji + name as before.
// ============================================================================
function getNetworkLogo($networkName, $tmdbShowId, $tmdbApiKey) {
    static $manifest = null;
    static $manifestFile = null;
    static $manifestDirty = false;

    if ($manifest === null) {
        $dataDir = __DIR__ . '/data';
        if (!is_dir($dataDir)) { @mkdir($dataDir, 0775, true); }
        $manifestFile = $dataDir . '/network_logos.json';
        $manifest = file_exists($manifestFile)
            ? (json_decode(file_get_contents($manifestFile), true) ?? [])
            : [];
    }

    $key = strtolower(trim($networkName));
    if ($key === '') { return null; }

    // Already resolved (either a working logo path, or a remembered failure) - no network call needed.
    if (isset($manifest[$key])) {
        return $manifest[$key]['file'] ?? null;
    }

    // Nothing to look up with - no TMDB key configured, or this show has no TMDB id from Trakt.
    if (!$tmdbApiKey || !$tmdbShowId) {
        return null;
    }

    $logoPath = null;
    try {
        $ch = curl_init("https://api.themoviedb.org/3/tv/{$tmdbShowId}?language=en-US");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$tmdbApiKey}",
            "accept: application/json",
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode === 200 && $response) {
            $details = json_decode($response, true);
            $networks = $details['networks'] ?? [];

            // Prefer an exact (normalized) name match; fall back to the only network if there's just one.
            $targetNorm = preg_replace('/[^a-z0-9]/', '', strtolower($networkName));
            $matched = null;
            foreach ($networks as $n) {
                $norm = preg_replace('/[^a-z0-9]/', '', strtolower($n['name'] ?? ''));
                if ($norm === $targetNorm) { $matched = $n; break; }
            }
            if (!$matched && count($networks) === 1) {
                $matched = $networks[0];
            }

            if ($matched && !empty($matched['logo_path'])) {
                $ext = pathinfo($matched['logo_path'], PATHINFO_EXTENSION) ?: 'png';
                $safeName = preg_replace('/[^a-z0-9]+/', '-', strtolower($networkName));
                $safeName = trim($safeName, '-');
                $logosDir = __DIR__ . '/images/networks';
                if (!is_dir($logosDir)) { @mkdir($logosDir, 0775, true); }
                $destFile = "{$logosDir}/{$safeName}.{$ext}";
                $relativePath = "images/networks/{$safeName}.{$ext}";

                $imgData = @file_get_contents("https://image.tmdb.org/t/p/original{$matched['logo_path']}");
                if ($imgData !== false && @file_put_contents($destFile, $imgData) !== false) {
                    $logoPath = $relativePath;
                }
            }
        }
    } catch (Exception $e) {
        // Swallow errors - a missing logo is not worth breaking the page over, just falls back below.
    }

    // Remember the result either way (including failure), so we never re-hit TMDB for this network again.
    $manifest[$key] = ['file' => $logoPath];
    $manifestDirty = true;
    @file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));

    return $logoPath;
}

$startDate = sprintf("%04d-%02d-01", $TraktYear, $TraktMonth);
$endDate   = date('Y-m-t', strtotime($startDate)); // last calendar day of the configured month
$monthLabel = (new DateTime($startDate))->format('F'); // English month name, e.g. "August"
$generatedStamp = date("H:i");

// Build Trakt API URI
$baseUri = "https://api.trakt.tv/calendars/all/shows/new/{$startDate}/{$TraktDays}";
$queryParts = ["extended=full,images"];
if (!empty($TraktGenres))    $queryParts[] = "genres=" . urlencode($TraktGenres);
if (!empty($TraktCountries)) $queryParts[] = "countries=" . urlencode($TraktCountries);
$uri = $baseUri . "?" . implode("&", $queryParts);

// Fetch data via cURL
// NOTE: /calendars/all/... is a public Trakt endpoint - confirmed via extensive live
// testing (including deliberately sending an invalid token) that it works correctly
// with just the trakt-api-key (Client ID), no OAuth access token needed at all -
// genre/country filters included. See CHANGELOG for details.
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $uri);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "trakt-api-version: 2",
    "trakt-api-key: {$TraktClientId}",
    "Content-Type: application/json",
    "User-Agent: PHP-Script/1.0",
    "X-Pagination-Page: 1",
    "X-Pagination-Limit: 300"
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$shows = [];
if ($httpcode == 200 && $response) {
    $shows = json_decode($response, true) ?? [];
}

// FIX: Trakt returns air times in UTC. After converting to local time (Athens,
// UTC+3), a show airing late in the evening on the last day of the month can
// roll over into the 1st of the NEXT month locally - which is why an "August"
// show could appear while browsing July. We filter using the LOCAL date
// (same conversion used for day-grouping below) so the range stays accurate.
if (!empty($shows)) {
    $shows = array_filter($shows, function($item) use ($startDate, $endDate) {
        $localDate = new DateTime($item['first_aired']);
        $localDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $airDate = $localDate->format('Y-m-d');
        return ($airDate >= $startDate && $airDate <= $endDate);
    });
}

// Network Filtering
if (!empty($TraktNetworkFilter) && !empty($shows)) {
    $shows = array_filter($shows, function($item) use ($TraktNetworkFilter) {
        return in_array($item['show']['network'] ?? '', $TraktNetworkFilter);
    });
}

// Sorting and Grouping
usort($shows, function($a, $b) {
    return strtotime($a['first_aired']) - strtotime($b['first_aired']);
});

$groupedByDay = [];
foreach ($shows as $item) {
    // Convert to local timezone structure
    $dateObj = new DateTime($item['first_aired']);
    $dateObj->setTimezone(new DateTimeZone(date_default_timezone_get()));
    $dayKey = $dateObj->format('Y-m-d');
    
    if (!isset($groupedByDay[$dayKey])) {
        $groupedByDay[$dayKey] = [];
    }
    $groupedByDay[$dayKey][] = $item;
}

$totalShowsFetched = count($shows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>New Shows &ndash; <?php echo htmlspecialchars($monthLabel); ?></title>
	<link rel="shortcut icon" href="images/favicon.ico">
	<link rel="icon" type="image/png" sizes="32x32" href="images/favicon-32.png">
	<link rel="apple-touch-icon" sizes="180x180" href="images/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0a0c10; --bg-panel: #12151c; --card: #151923; --card-border: #242a38;
            --gold: #e8b545; --gold-soft: #f4d385; --crimson: #e0384d; --text: #eef0f4;
            --text-dim: #9aa2b1; --text-faint: #5c6478; --accent-blue: #4fa3e0;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: radial-gradient(circle at 15% 0%, rgba(232,181,69,0.08), transparent 40%),
                        radial-gradient(circle at 85% 20%, rgba(224,56,77,0.07), transparent 45%), var(--bg);
            color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh; padding-bottom: 40px;
        }
        header.hero { position: relative; overflow: hidden; padding: 56px 6vw 34px 6vw; border-bottom: 1px solid var(--card-border); background: linear-gradient(180deg, rgba(232,181,69,0.06), transparent); }
        header.hero .brand-row, header.hero .sub { position: relative; z-index: 2; }
        .brand-row { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .brand-row img.brand-logo { height: 64px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.55)); }
        .month-nav { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
        .month-nav-btn {
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
            width: 46px; height: 46px; border-radius: 50%;
            background: var(--bg-panel); border: 1px solid var(--card-border);
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, background .2s ease;
        }
        .month-nav-btn img { width: 20px; height: 20px; transition: transform .2s ease; }
        .month-nav-btn:hover { border-color: var(--gold); background: rgba(232,181,69,0.14); box-shadow: 0 0 20px rgba(232,181,69,0.4); }
        .month-nav-btn.prev:hover { transform: scale(1.12) translateX(-4px); }
        .month-nav-btn.next:hover { transform: scale(1.12) translateX(4px); }
        .month-nav-btn:hover img { transform: scale(1.08); }
        .month-nav-btn:active { transform: scale(0.95); }
        .brand-row h1 {
            font-family: 'Bebas Neue', sans-serif; font-size: clamp(2.4rem, 5.6vw, 4.2rem); letter-spacing: 2px;
            background: linear-gradient(90deg, var(--gold-soft), var(--gold) 40%, var(--crimson)); -webkit-background-clip: text; color: transparent;
        }
        header.hero .sub { margin-top: 18px; color: var(--text-dim); display: flex; align-items: flex-start; gap: 18px; flex-wrap: wrap; }
        header.hero .sub span { background: var(--bg-panel); border: 1px solid var(--card-border); padding: 6px 14px; border-radius: 999px; font-size: 0.86rem; color: var(--text); }
        
        /* Updated networks chip width to 800px */
        header.hero .sub span.networks-chip { display: block; align-items: normal; border-radius: 14px; max-width: 800px; line-height: 1.6; text-align: left; white-space: normal; }

        .sync-panel {
            display: flex; position: fixed; top: 20px; right: 20px; z-index: 9999; align-items: center; gap: 8px;
            background: rgba(18,21,28,0.92); border: 1px solid var(--card-border); border-radius: 12px; padding: 8px 10px;
            box-shadow: 0 14px 32px rgba(0,0,0,0.5); backdrop-filter: blur(6px);
        }
        .sync-status { color: var(--text-dim); font-size: 0.72rem; }

       .floating-bar { position: fixed; bottom: 22px; right: 22px; z-index: 9999; display: flex; align-items: flex-end; gap: 14px; }
       .stats-bar { padding: 12px 16px; background: rgba(18,21,28,0.92); border: 1px solid var(--card-border); border-radius: 12px; min-width: 200px; font-size: 0.78rem; }
       .back-to-top {
            width: 52px; height: 52px; border-radius: 50%; flex-shrink: 0;
            background: rgba(18,21,28,0.92); border: 1px solid rgba(232,181,69,0.5);
            box-shadow: 0 10px 26px rgba(0,0,0,0.5);
            display: flex; align-items: center; justify-content: center; cursor: pointer;
            opacity: 0; visibility: hidden; transform: translateY(10px) scale(0.85);
            transition: opacity .25s ease, transform .25s ease, visibility .25s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .back-to-top.visible { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }
        .back-to-top:hover { border-color: var(--gold); box-shadow: 0 12px 30px rgba(0,0,0,0.55), 0 0 20px rgba(232,181,69,0.45); transform: translateY(-3px) scale(1.08); }
        .back-to-top.visible:hover { transform: translateY(-3px) scale(1.08); }
        .back-to-top:active { transform: scale(0.9); }
        .back-to-top img { width: 30px; height: 30px; object-fit: contain; }
        .stats-row { display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 4px; }
        .stats-row strong { color: var(--gold-soft); font-size: 0.92rem; display: inline-block; }
        .watching-label { display: flex; align-items: center; gap: 14px; }
        .watching-eye-btn {
            display: inline-flex; align-items: center; justify-content: center;
            width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
            background: rgba(255,255,255,0.05); border: 1px solid var(--card-border);
            transition: all .18s ease; text-decoration: none;
        }
        .watching-eye-btn img { width: 14px; height: 14px; object-fit: contain; }
        .watching-eye-btn:hover { background: rgba(232,181,69,0.9); border-color: var(--gold); box-shadow: 0 0 10px rgba(232,181,69,0.55); transform: scale(1.15); }
        @keyframes statPop {
            0%   { transform: scale(1) rotate(0deg); text-shadow: none; }
            25%  { transform: scale(1.7) rotate(-4deg); text-shadow: 0 0 10px var(--gold), 0 0 22px var(--crimson); color: var(--crimson); }
            50%  { transform: scale(1.35) rotate(3deg); text-shadow: 0 0 8px var(--gold); }
            75%  { transform: scale(0.9) rotate(-2deg); }
            100% { transform: scale(1) rotate(0deg); text-shadow: none; }
        }
        .stat-pop { animation: statPop 0.55s cubic-bezier(.36,.07,.19,.97); }
        .delta-msg { font-size: 0.7rem; font-weight: normal; margin-top: 2px; text-align: left; display: block; }
                .stats-divider { border: 0; border-top: 1px solid #242a38; margin: 6px 0; }
     .history-log { margin-top: 8px; font-size: 0.65rem; color: #888; border-top: 1px solid #242a38; padding-top: 5px; }
        main { padding: 10px 6vw 0 6vw; }
        .day-block { margin-top: 44px; }
        .day-header { display: flex; align-items: baseline; gap: 14px; margin-bottom: 20px; border-bottom: 2px solid var(--card-border); padding-bottom: 10px; }
        .day-header .day-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.9rem; color: var(--gold-soft); letter-spacing: 1px;}
        .day-header .day-count { color: var(--text-faint); font-size: 0.9rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 26px; }
        .delta-row { display: flex; align-items: center; gap: 3px; }
        .delta-msg { font-size: 0.7rem; color: #a1a1aa; margin-top: 2px; display: block; }
        .delta-help {
            display: none; position: relative; margin-top: 2px;
            color: #fbbf24; font-weight: 900; font-size: 0.72rem;
            cursor: help; vertical-align: middle; line-height: 1;
        }
        .delta-help.visible { display: inline-block; }
        .delta-help-box {
            display: none; position: absolute; bottom: 135%; right: -6px; z-index: 10000;
            background: #12151c; border: 1px solid rgba(251,191,36,0.5); border-radius: 8px;
            padding: 8px 11px; min-width: 170px; max-width: 260px;
            font-size: 0.7rem; font-weight: 400; color: var(--text); text-align: left;
            white-space: pre-line; line-height: 1.5;
            box-shadow: 0 10px 24px rgba(0,0,0,0.55);
        }
        .delta-help:hover .delta-help-box { display: block; }
        .card { background: var(--card); border: 1px solid var(--card-border); border-radius: 14px; overflow: hidden; position: relative; transition: all .22s ease; display: flex; flex-direction: column; }
        .card:hover { transform: translateY(-6px) scale(1.015); box-shadow: 0 18px 40px rgba(0,0,0,0.55), 0 0 0 1px rgba(232,181,69,0.35); border-color: var(--gold); }
        .card.not-watching { opacity: 0.72; border-color: rgba(224,56,77,0.5); }
        .card.not-watching:hover { transform: none; box-shadow: none; border-color: rgba(224,56,77,0.7); }
        .card.not-watching .card-body { opacity: 0.55; }
        
        .poster-wrap { position: relative; width: 100%; aspect-ratio: 2/3; background: linear-gradient(135deg, #1c2130, #10131b); overflow: hidden; }
        .poster-wrap img { width: 100%; height: 100%; object-fit: cover; transition: filter .22s ease; }
        .card.not-watching .poster-wrap img { filter: grayscale(0.95) brightness(0.5); }
        
        .ribbon-wrap { position: absolute; top: 0; left: 0; width: 170px; height: 170px; overflow: hidden; z-index: 4; display: none; }
        .card.not-watching .ribbon-wrap { display: block; }
        .ribbon-wrap .ribbon-text { position: absolute; top: 34px; left: -46px; width: 220px; transform: rotate(-45deg); background: var(--crimson); color: #fff; text-align: center; font-size: 0.7rem; font-weight: 800; padding: 6px 0; box-shadow: 0 2px 6px rgba(0,0,0,0.45); }
        
        @keyframes newBadgePulse {
            0%, 100% { box-shadow: 0 3px 12px rgba(34,197,94,0.55), 0 0 0 0 rgba(74,222,128,0.55); }
            50%      { box-shadow: 0 3px 16px rgba(34,197,94,0.8), 0 0 0 7px rgba(74,222,128,0); }
        }
        .new-badge {
            display: none; position: absolute; top: 10px; left: 10px; z-index: 5;
            background: linear-gradient(135deg, #4ade80, #16a34a);
            color: #052e13; font-size: 0.82rem; font-weight: 800; letter-spacing: 0.3px;
            padding: 6px 13px; border-radius: 9px;
            animation: newBadgePulse 1.8s ease-in-out infinite;
        }
        .card.is-new .new-badge { display: block; }
        .card.not-watching .new-badge { display: none; }
        
        .rating-badge { position: absolute; top: 10px; right: 10px; z-index: 5; background: rgba(10,12,16,0.78); border: 1px solid rgba(232,181,69,0.5); color: var(--gold-soft); font-size: 0.78rem; font-weight: 700; padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px); }
        
        .watch-toggle { position: absolute; top: 48px; right: 10px; z-index: 6; width: 30px; height: 30px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.18); background: rgba(10,12,16,0.78); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .18s ease; padding: 0; }
        .watch-toggle:hover { transform: scale(1.16); background: rgba(232,181,69,0.9); box-shadow: 0 0 16px rgba(232,181,69,0.55); }
        .watch-toggle:active { transform: scale(0.9); }
        .watch-toggle img { height: 14px; width: auto; max-width: 22px; object-fit: contain; transition: opacity .18s ease, filter .18s ease; }
        .card.not-watching .watch-toggle { background: rgba(224,56,77,0.25); border-color: rgba(224,56,77,0.55); }
        .card.not-watching .watch-toggle img { opacity: 0.5; filter: grayscale(1); }
        .card.not-watching .watch-toggle:hover { background: rgba(232,181,69,0.95); border-color: var(--gold); box-shadow: 0 0 20px rgba(232,181,69,0.65); }
        .card.not-watching .watch-toggle:hover img { opacity: 1; filter: none; }
        .card.is-new .watch-toggle { display: none; }

        /* New-show cards get two always-visible buttons (mark watching / mark not watching)
           instead of the single toggle, stacked exactly where the toggle normally sits. */
        .new-watch-actions { display: none; position: absolute; top: 48px; right: 10px; z-index: 6; flex-direction: column; align-items: center; gap: 8px; }
        .card.is-new .new-watch-actions { display: flex; }
        .watch-btn { width: 34px; height: 34px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.18); background: rgba(10,12,16,0.78); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .18s ease; padding: 0; }
        .watch-btn img { height: 16px; width: auto; max-width: 24px; object-fit: contain; }
        .watch-btn:hover { transform: scale(1.14); background: rgba(232,181,69,0.9); box-shadow: 0 0 14px rgba(232,181,69,0.5); }
        .watch-btn:active { transform: scale(0.88); }
        
        .card-body { padding: 14px 16px 16px 16px; display: flex; flex-direction: column; gap: 8px; flex: 1; transition: opacity .22s ease; }
        .title-row { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
        .show-title { font-size: 1.05rem; font-weight: 700; line-height: 1.25; }
        .show-year { color: var(--text-faint); font-size: 0.85rem; }
        
        .meta-row { display: flex; flex-wrap: wrap; gap: 6px; font-size: 0.74rem; }
        .chip { background: rgba(255,255,255,0.05); border: 1px solid var(--card-border); color: var(--text-dim); padding: 3px 9px; border-radius: 999px; }
        .chip.network { color: var(--accent-blue); border-color: rgba(79,163,224,0.35); }
        .chip.network-logo { background: rgba(154,162,177,0.92); border-color: rgba(255,255,255,0.12); padding: 4px 10px; display: flex; align-items: center; height: 26px; }
        .chip.network-logo img { height: 16px; width: auto; max-width: 90px; object-fit: contain; display: block; }
        .chip.country { color: var(--gold-soft); border-color: rgba(232,181,69,0.3); }
        
        .overview { color: var(--text-dim); font-size: 0.82rem; line-height: 1.45; flex: 1; }
        .premiere-info { margin-top: auto; padding-top: 10px; border-top: 1px solid var(--card-border); font-size: 0.82rem; font-weight: 600; }
        
        .links-row { display: flex; gap: 8px; margin-top: 8px; }
        .trakt-btn { flex: 1; display: flex; align-items: center; justify-content: center; padding: 9px 0; border-radius: 8px; border: 1px solid var(--card-border); background: rgba(255,255,255,0.03); transition: all .18s ease; }
        .trakt-btn img { height: 22px; }
        .trakt-btn:hover { background: var(--gold); box-shadow: 0 0 14px rgba(232,181,69,0.5); transform: translateY(-2px); }
        
        .empty-state { text-align: center; padding: 100px 20px; color: var(--text-faint); }
        .empty-state img.empty-icon { display: block; margin: 0 auto 20px auto; width: 180px; height: auto; }

        .footer-divider { border: 0; border-top: 1px solid var(--card-border); margin: 56px 6vw 0 6vw; }
        .site-footer { text-align: center; padding: 22px 6vw 36px 6vw; }
        .site-footer p { font-size: 0.72rem; color: var(--text-faint); opacity: 0.55; line-height: 1.7; }
        .site-footer .version-tag { color: var(--gold-soft); font-weight: 700; letter-spacing: 0.3px; }

        /* ====================================================================
           EXPERIMENTAL HEADER BANNER (temp.php only) - real TMDB posters
           drifting behind the title, plus a TV whose screen cycles through
           backdrops of those same titles. Ported from index.php's animation;
           recolored blue/grey (not pink) to match this page, and scoped
           entirely under .banner so nothing above is touched.
           ==================================================================== */
        .banner { position: absolute; inset: 0; overflow: hidden; pointer-events: none; z-index: 0; }

        .banner .fade {
            position: absolute; inset: 0; overflow: hidden;
            -webkit-mask-image: linear-gradient(180deg, #000 0%, #000 55%, transparent 100%);
                    mask-image: linear-gradient(180deg, #000 0%, #000 55%, transparent 100%);
        }

        .banner .wall {
            position: absolute; left: -20%; right: -20%; top: -18%;
            display: flex; flex-direction: column; gap: 12px;
            transform: rotate(-7deg);
            opacity: 0; transition: opacity 1.2s ease;
        }
        .banner .wall.ready { opacity: .5; }

        .banner .row { display: flex; gap: 12px; width: max-content; animation: bannerDrift 120s linear infinite; }
        .banner .row:nth-child(2) { animation-direction: reverse; animation-duration: 150s; }
        .banner .row:nth-child(3) { animation-duration: 135s; }
        .banner .row img {
            width: clamp(58px, 6.5vw, 92px); aspect-ratio: 2 / 3; object-fit: cover;
            border-radius: 8px; display: block; box-shadow: 0 8px 18px -8px rgba(0,0,0,0.6);
        }
        @keyframes bannerDrift { to { transform: translateX(-50%); } }

        .banner .fade::after {
            content: ""; position: absolute; inset: 0;
            background:
                linear-gradient(180deg, color-mix(in srgb, var(--bg) 55%, transparent) 0%, var(--bg) 100%),
                linear-gradient(90deg, color-mix(in srgb, var(--bg-panel) 70%, transparent), transparent 30%, transparent 70%, color-mix(in srgb, var(--crimson) 22%, transparent));
        }

        /* the TV - blue/grey, not pink, drawn purely with CSS */
        .banner .tv {
            --tv-light: #D9E3EA; --tv-mid: #8FA6B8; --tv-deep: #526378;
            --tv-screen-glow: #324354; --tv-knob-ring: #9FC2D6;
            position: absolute; z-index: 2;
            right: clamp(150px, 17vw, 260px);
            bottom: clamp(10px, 2vw, 22px);
            width: clamp(92px, 10vw, 148px);
            aspect-ratio: 5 / 4;
            border-radius: 22% / 26%;
            background: linear-gradient(150deg, var(--tv-light) 0%, var(--tv-mid) 45%, var(--tv-deep) 100%);
            box-shadow: inset 0 -6px 0 #0000001f, 0 18px 30px -12px #050608;
        }
        .banner .tv::before, .banner .tv::after {
            content: ""; position: absolute; bottom: 100%; left: 44%;
            width: 3px; height: 34%; border-radius: 3px;
            background: var(--tv-light);
            transform-origin: bottom center; transform: rotate(-32deg);
        }
        .banner .tv::after { transform: rotate(30deg); }
        .banner .tv .screen {
            position: absolute; top: 10%; bottom: 12%; left: 8%; right: 27%;
            border-radius: 16% / 20%; overflow: hidden;
            background: radial-gradient(circle at 40% 35%, var(--tv-screen-glow), var(--bg));
            box-shadow: inset 0 0 0 3px #00000066;
        }
        .banner .tv .screen img {
            position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover;
            opacity: 0; transition: opacity 1s ease;
        }
        .banner .tv .screen img.on { opacity: 1; }
        .banner .tv .screen::after {
            content: ""; position: absolute; inset: 0;
            background:
                repeating-linear-gradient(180deg, #ffffff10 0 1px, transparent 1px 3px),
                radial-gradient(circle at 30% 20%, #ffffff30, transparent 45%);
        }
        .banner .tv .knobs { position: absolute; right: 8%; top: 22%; display: flex; flex-direction: column; gap: 14%; width: 11%; height: 56%; }
        .banner .tv .knobs i { display: block; aspect-ratio: 1; border-radius: 50%; background: var(--bg); box-shadow: inset 0 0 0 3px var(--tv-knob-ring); }
        .banner .tv .legs::before, .banner .tv .legs::after {
            content: ""; position: absolute; top: 100%; width: 9%; height: 9%;
            border-radius: 0 0 4px 4px; background: var(--tv-deep); left: 16%;
        }
        .banner .tv .legs::after { left: auto; right: 16%; }

        @media (max-width: 560px) {
            .banner .tv { display: none; }
        }
    </style>
</head>
<body>

<header class="hero">
    <div class="banner" aria-hidden="true">
        <div class="fade"><div class="wall" id="bannerWall"></div></div>
        <div class="tv">
            <div class="screen" id="bannerScreen"></div>
            <div class="knobs"><i></i><i></i></div>
            <span class="legs"></span>
        </div>
    </div>
    <div class="brand-row">
        <img class="brand-logo" src="<?php echo htmlspecialchars($TraktLogoTop); ?>" alt="Trakt">
        <div class="month-nav">
            <a class="month-nav-btn prev" href="?month=<?php echo $prevMonth; ?>&amp;year=<?php echo $prevYear; ?>" title="Previous month" aria-label="Previous month">
                <img src="images/arrow-left.png" alt="Previous month">
            </a>
            <h1>New Shows &mdash; <?php echo htmlspecialchars($monthLabel); ?></h1>
            <a class="month-nav-btn next" href="?month=<?php echo $nextMonth; ?>&amp;year=<?php echo $nextYear; ?>" title="Next month" aria-label="Next month">
                <img src="images/arrow-right.png" alt="Next month">
            </a>
        </div>
    </div>
    <div class="sub">
        <span>📊 <?php echo ($totalShowsFetched === 0) ? 'No Shows' : $totalShowsFetched . ' premiere' . (($totalShowsFetched == 1) ? '' : 's'); ?></span>
        <?php if (!empty($TraktNetworkFilter)): ?>
            <span class="networks-chip">📡 Networks: <?php echo htmlspecialchars(implode(', ', $TraktNetworkFilter)); ?></span>
        <?php endif; ?>
        <span>🕒 <?php echo htmlspecialchars($generatedStamp); ?></span>
    </div>
</header>

<div id="syncPanel" class="sync-panel">
    <span id="syncStatus" class="sync-status">🟢 Storage Connected</span>
</div>

<div class="floating-bar">
    <button type="button" id="backToTop" class="back-to-top" title="Back to top" aria-label="Back to top">
        <img src="images/top.png" alt="Back to top">
    </button>
    <div id="statsBar" class="stats-bar">
        <div style="width: 100%;">
            <div class="stats-row"><span>📺 Total:</span><strong id="statTotal"><?php echo $totalShowsFetched; ?></strong></div>
            <div class="delta-row"><span id="deltaMsg" class="delta-msg"></span><span id="deltaHelp" class="delta-help">❓<span id="deltaHelpBox" class="delta-help-box"></span></span></div>
        </div>
        <hr class="stats-divider">
        <div class="stats-row">
            <span class="watching-label">👀 Watching:
                <a id="watchingEyeBtn" class="watching-eye-btn" href="watched.php?month=<?php echo $TraktMonth; ?>&amp;year=<?php echo $TraktYear; ?>" title="Display Watching Items" aria-label="Display Watching Items" style="display:none;">
                    <img src="images/filter.png" alt="View watching shows">
                </a>
            </span>
            <strong id="statWatching">0</strong>
        </div>
        <div class="stats-row"><span>🚫 Not Watching:</span><strong id="statNotWatching">0</strong></div>
        <div id="historyLog" class="history-log"></div>
    </div>
</div>

<main>
    <?php if (empty($groupedByDay)): ?>
        <div class="empty-state">
            <img class="empty-icon" src="images/noshows.png" alt="No shows found">
            <div>No new shows matched your filters for this month.</div>
        </div>
    <?php else: ?>
        <?php foreach ($groupedByDay as $date => $dayShows): 
            $dayDateObj = new DateTime($date);
            $dayTitle = $dayDateObj->format('l, d F');
        ?>
            <section class="day-block">
                <div class="day-header">
                    <div class="day-title">📅 <?php echo htmlspecialchars($dayTitle); ?></div>
                    <div class="day-count"><?php echo count($dayShows); ?> show<?php echo (count($dayShows) == 1) ? '' : 's'; ?></div>
                </div>
                <div class="grid">
                    <?php foreach ($dayShows as $item): 
                        $show = $item['show'];
                        $title = htmlspecialchars($show['title'] ?? '');
                        $year = $show['year'] ?? '';
                        $network = htmlspecialchars($show['network'] ?? '');
                        $tmdbShowId = $show['ids']['tmdb'] ?? null;
                        $networkLogo = $network ? getNetworkLogo($show['network'], $tmdbShowId, $TmdbApiKey) : null;
                        $countryCode = strtolower($show['country'] ?? '');
                        $country = htmlspecialchars(strtoupper($countryCode));
                        $countryFlag = $CountryFlags[$countryCode]['flag'] ?? null;
                        $countryName = $CountryFlags[$countryCode]['name'] ?? null;
                        $rating = isset($show['rating']) ? round((float)$show['rating'], 1) : null;
                        
                        $overview = htmlspecialchars($show['overview'] ?? '');
                        if (strlen($overview) > 200) { $overview = substr($overview, 0, 197) . '...'; }
                        
                        $localAired = new DateTime($item['first_aired']);
                        $localAired->setTimezone(new DateTimeZone(date_default_timezone_get()));
                        $airedDateStr = $localAired->format('d M Y');
                        
                        $posterUrl = $TraktNoPoster;
                        if (isset($show['images']['poster'][0])) {
                            $posterUrl = $show['images']['poster'][0];
                            if (!preg_match('/^https?:\/\//', $posterUrl)) { $posterUrl = "https://" . $posterUrl; }
                        }
                        
                        // FIX: added fallback in case 'slug' is missing, to avoid an undefined-index warning
                        $traktSlug = $show['ids']['slug'] ?? '';
                        $traktUrl = "https://trakt.tv/shows/{$traktSlug}";
                    ?>
                    
                    <div class="card" data-id="<?php echo htmlspecialchars($traktSlug); ?>">
                        <div class="poster-wrap">
                            <div class="ribbon-wrap"><div class="ribbon-text">NOT WATCHING</div></div>
                            <div class="new-badge">✨ NEW SHOW</div>
                            <?php if ($rating): ?>
                                <div class="rating-badge">⭐ <?php echo $rating; ?></div>
                            <?php endif; ?>
                            
                            <button type="button" class="watch-toggle" onclick="toggleWatch(this)" title="Mark as not watching">
                                <img class="icon-open" src="images/eye.png" alt="Watching">
                                <img class="icon-closed" src="images/eyeclosed.png" alt="Not watching" style="display:none;">
                            </button>
                            <div class="new-watch-actions">
                                <button type="button" class="watch-btn watch-btn-eye" onclick="resolveNewShow(this, false)" title="Mark as watching">
                                    <img src="images/eye.png" alt="Mark as watching">
                                </button>
                                <button type="button" class="watch-btn watch-btn-eyeclosed" onclick="resolveNewShow(this, true)" title="Mark as not watching">
                                    <img src="images/eyeclosed.png" alt="Mark as not watching">
                                </button>
                            </div>
                            <img src="<?php echo htmlspecialchars($posterUrl); ?>" alt="<?php echo $title; ?> poster" loading="lazy">
                        </div>
                        <div class="card-body">
                            <div class="title-row">
                                <div class="show-title"><?php echo $title; ?></div>
                                <?php if ($year): ?><div class="show-year"><?php echo $year; ?></div><?php endif; ?>
                            </div>
                            <div class="meta-row">
                                <?php if ($networkLogo): ?>
                                    <span class="chip network-logo" title="<?php echo $network; ?>"><img src="<?php echo htmlspecialchars($networkLogo); ?>" alt="<?php echo $network; ?>"></span>
                                <?php elseif ($network): ?>
                                    <span class="chip network">📡 <?php echo $network; ?></span>
                                <?php endif; ?>
                                <?php if ($countryFlag): ?>
                                    <span class="chip country" title="<?php echo htmlspecialchars($countryName ?: $country); ?>"><?php echo $countryFlag; ?></span>
                                <?php elseif ($countryName): ?>
                                    <span class="chip country">🌍 <?php echo htmlspecialchars($countryName); ?></span>
                                <?php elseif ($country): ?>
                                    <span class="chip country">🌍 <?php echo $country; ?></span>
                                <?php endif; ?>
                                <?php 
                                if (isset($show['genres'])) {
                                    foreach ($show['genres'] as $g) {
                                        echo "<span class=\"chip\">" . htmlspecialchars(ucwords($g)) . "</span>";
                                    }
                                }
                                ?>
                            </div>
                            <?php if ($overview): ?><div class="overview"><?php echo $overview; ?></div><?php endif; ?>
                            <div class="premiere-info"><span class="date"><?php echo $airedDateStr; ?></span></div>
                            <div class="links-row">
                                <a class="trakt-btn" href="<?php echo htmlspecialchars($traktUrl); ?>" target="_blank" rel="noopener">
                                    <img src="<?php echo htmlspecialchars($TraktLogoButton); ?>" alt="View on Trakt">
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

<hr class="footer-divider">
<footer class="site-footer">
    <p>Idea, design, coded &amp; vibe coded by great_vc -&nbsp;&nbsp;&nbsp;<span class="version-tag"> 🏷️ &nbsp;&nbsp;<?php echo htmlspecialchars($TraktVersion); ?></span><br>
    No AI was harmed during this, except from Fuck you Gemini cannot distinguish &lt;body&gt; from &lt;script&gt;</p>
</footer>

<script>
// ============================================================================
// CENTRAL STORAGE (server-side, shared across all devices)
// ============================================================================
const STATE_URL = '?month=<?php echo $TraktMonth; ?>&year=<?php echo $TraktYear; ?>&api=state';
const currentTotal = <?php echo $totalShowsFetched; ?>;
const currentTotalShows = <?php echo $totalShowsFetched; ?>;
let notWatching = new Set();
let historyLog = [];
let lastKnownStats = { total: null, watching: null, notWatching: null };
let currentShows = [];
let remainingNewCount = 0;

async function loadState() {
    const res = await fetch(STATE_URL, { method: 'GET', cache: 'no-store' });
    if (!res.ok) throw new Error('Failed to load state: ' + res.status);
    return res.json();
}

async function saveState() {
    const payload = {
        notWatching: Array.from(notWatching),
        history: historyLog,
        lastCount: currentTotalShows,
        lastShows: currentShows
    };
    const res = await fetch(STATE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    if (!res.ok) throw new Error('Failed to save state: ' + res.status);
    return res.json();
}

function setSyncStatus(ok, message) {
    const el = document.getElementById('syncStatus');
    if (!el) return;
    el.textContent = ok ? '🟢 Storage Connected' : ('🔴 ' + (message || 'Storage Error'));
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const state = await loadState();
        notWatching = new Set(state.notWatching || []);
        historyLog = state.history || [];
        setSyncStatus(true);

        const now = new Date();
        const ts = now.getHours() + ":" + String(now.getMinutes()).padStart(2, '0');
        const todayKey = now.toISOString().slice(0, 10);

        // Only add a new history entry if the count changed since the last run
        if (historyLog.length === 0 || historyLog[historyLog.length - 1].count !== currentTotal) {
            historyLog.push({ time: ts, count: currentTotal, date: todayKey });
            if (historyLog.length > 3) historyLog.shift();
        }

        // Render History
        document.getElementById('historyLog').innerHTML = '<strong>History:</strong>' +
            [...historyLog].reverse().map(i => `<div style="display:flex; justify-content:space-between;"><span>${getRelativeDayLabel(i.date)} ${i.time}</span><span>${i.count} show${i.count === 1 ? '' : 's'}</span></div>`).join('');

        // Restore card toggle states
        document.querySelectorAll('.card').forEach(card => {
            const id = card.getAttribute('data-id');
            setCardState(card, notWatching.has(id));
        });

        // Collect this run's shows (id + title + year) - used for "new show" /
        // "dropped off" detection below, and saved for next visit.
        currentShows = Array.from(document.querySelectorAll('.card')).map(c => ({
            id: c.getAttribute('data-id'),
            title: c.querySelector('.show-title')?.textContent || '',
            year: c.querySelector('.show-year')?.textContent || ''
        }));
        const currentIds = new Set(currentShows.map(s => s.id));

        // previousShows is null only on the very first run ever for this month
        // (no previous data to compare against). Falls back to the older
        // lastShowIds format (plain id strings, no title/year) if this state
        // file predates this feature, so upgrading doesn't wrongly flag every
        // existing show as new on the next visit.
        let previousShows = null;
        if (Array.isArray(state.lastShows)) {
            previousShows = state.lastShows;
        } else if (Array.isArray(state.lastShowIds)) {
            previousShows = state.lastShowIds.map(id => ({ id, title: null, year: null }));
        }

        // On a true first-ever visit, previousShows is null - treat that as an
        // empty list rather than skipping detection entirely, so every show
        // gets the same "undecided, needs a choice" treatment as an individual
        // new arrival would: NEW SHOW badge, two-button UI, excluded from the
        // Watching/Not Watching stats until resolved. This keeps the stats
        // honest (no silent default-to-watching for shows you've never
        // actually seen) instead of a one-off "everything is new" skip.
        const previousIds = new Set((previousShows || []).map(s => s.id));

        document.querySelectorAll('.card').forEach(card => {
            const id = card.getAttribute('data-id');
            if (id && !previousIds.has(id)) {
                card.classList.add('is-new');
                // If this show was marked "not watching" before it vanished and
                // is now reappearing, treat it as a genuinely fresh premiere -
                // clear the stale flag so it doesn't show a confusing mix of
                // "not watching" styling alongside the NEW SHOW UI.
                if (notWatching.has(id)) {
                    notWatching.delete(id);
                    setCardState(card, false);
                }
            }
        });

        // Shows that were present last visit but aren't in this run's calendar at all.
        const droppedShows = (previousShows || []).filter(s => s.id && !currentIds.has(s.id));

        // Delta tracking (did the count change since last visit?)
        // If there are unresolved NEW SHOW cards, that counter takes over the
        // message entirely (see renderNewShowsDelta) - it's more specific and
        // directly tied to the actionable badges below. Otherwise, fall back
        // to the original total-count-based message as before.
        remainingNewCount = document.querySelectorAll('.card.is-new').length;
        const deltaMsgElement = document.getElementById('deltaMsg');
        const deltaHelpEl = document.getElementById('deltaHelp');
        const deltaHelpBoxEl = document.getElementById('deltaHelpBox');
        deltaHelpEl.classList.remove('visible');
        deltaHelpBoxEl.textContent = '';
        if (remainingNewCount > 0) {
            renderNewShowsDelta(true);
        } else {
            const previousCount = state.lastCount;
            if (previousCount !== null && previousCount !== undefined) {
                if (currentTotalShows > previousCount) {
                    const diff = currentTotalShows - previousCount;
                    deltaMsgElement.textContent = `📈 ${diff} show${diff === 1 ? '' : 's'} joined the lineup`;
                    deltaMsgElement.style.color = '#34d399';
                } else if (currentTotalShows < previousCount) {
                    const diff = previousCount - currentTotalShows;
                    deltaMsgElement.textContent = `📉 ${diff} show${diff === 1 ? '' : 's'} dropped off`;
                    deltaMsgElement.style.color = '#f87171';
                    if (droppedShows.length > 0) {
                        deltaHelpBoxEl.textContent = droppedShows
                            .map(s => `${s.title || 'Unknown show'}${s.year ? ' (' + s.year + ')' : ''}`)
                            .join('\n');
                        deltaHelpEl.classList.add('visible');
                    }
                    popStat(deltaMsgElement);
                } else {
                    deltaMsgElement.textContent = `✅ Shows Matched`;
                    deltaMsgElement.style.color = '#a1a1aa';
                }
            } else {
                deltaMsgElement.textContent = `🕵️ First look this month`;
            }
        }

        updateStats();
        await saveState();
    } catch (e) {
        console.error(e);
        setSyncStatus(false, 'Load failed');
        // Still show cards / stats even if storage is unreachable
        updateStats();
    }
});

// Turns a stored YYYY-MM-DD date into "Today", "Yesterday", or a short date string
function getRelativeDayLabel(dateStr) {
    if (!dateStr) return 'Today';
    const today = new Date();
    const todayKey = today.toISOString().slice(0, 10);

    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    const yesterdayKey = yesterday.toISOString().slice(0, 10);

    if (dateStr === todayKey) return 'Today';
    if (dateStr === yesterdayKey) return 'Yesterday';

    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function setCardState(card, isNotWatching) {
    card.classList.toggle('not-watching', isNotWatching);
    const btn = card.querySelector('.watch-toggle');
    btn.querySelector('.icon-open').style.display = isNotWatching ? 'none' : 'block';
    btn.querySelector('.icon-closed').style.display = isNotWatching ? 'block' : 'none';
    btn.title = isNotWatching ? 'Mark as watching' : 'Mark as not watching';
}

async function toggleWatch(btn) {
    const card = btn.closest('.card');
    const id = card.getAttribute('data-id');
    const isNotWatching = !card.classList.contains('not-watching');

    setCardState(card, isNotWatching);

    if (isNotWatching) {
        notWatching.add(id);
    } else {
        notWatching.delete(id);
    }

    updateStats();

    try {
        await saveState();
        setSyncStatus(true);
    } catch (e) {
        console.error(e);
        setSyncStatus(false, 'Save failed');
        alert("⚠️ Warning: Your change could not be saved to the server. Check your connection and try again.");
    }
}

// Called when one of the two dedicated buttons on a "NEW SHOW" card is clicked.
// markAsNotWatching = false -> eye.png was clicked (mark watching)
// markAsNotWatching = true  -> eyeclosed.png was clicked (mark not watching)
async function resolveNewShow(btn, markAsNotWatching) {
    const card = btn.closest('.card');
    const id = card.getAttribute('data-id');

    // Drop the "new" state - this hides the NEW SHOW badge, hides both of these
    // buttons, and reveals the normal single watch-toggle (already showing the
    // correct eye/eyeclosed icon once setCardState runs below).
    card.classList.remove('is-new');
    setCardState(card, markAsNotWatching);

    if (markAsNotWatching) {
        notWatching.add(id);
    } else {
        notWatching.delete(id);
    }

    decrementNewShowCount();
    updateStats();

    try {
        await saveState();
        setSyncStatus(true);
    } catch (e) {
        console.error(e);
        setSyncStatus(false, 'Save failed');
        alert("⚠️ Warning: Your change could not be saved to the server. Check your connection and try again.");
    }
}

// Renders the "X shows joined the lineup" counter tied to unresolved NEW SHOW
// cards specifically (not the raw total-count delta). pop=true also triggers
// the boom animation - used both on first render and every decrement.
function renderNewShowsDelta(pop) {
    const deltaMsgElement = document.getElementById('deltaMsg');
    if (remainingNewCount > 0) {
        deltaMsgElement.textContent = `📈 ${remainingNewCount} show${remainingNewCount === 1 ? '' : 's'} joined the lineup`;
        deltaMsgElement.style.color = '#34d399';
    } else {
        deltaMsgElement.textContent = ''; // fully resolved - leave it blank, no wording
    }
    if (pop) popStat(deltaMsgElement);
}

function decrementNewShowCount() {
    remainingNewCount = Math.max(0, remainingNewCount - 1);
    renderNewShowsDelta(true);
}

// Pops/glows a stat number when its value changes, so the change is easy to spot
function popStat(el) {
    el.classList.remove('stat-pop');
    void el.offsetWidth; // force reflow so the animation can restart
    el.classList.add('stat-pop');
}

function updateStats() {
    const total = currentTotalShows;
    const actualNotWatching = document.querySelectorAll('.card.not-watching').length;
    const actualNew = document.querySelectorAll('.card.is-new').length;
    const actualWatching = total - actualNotWatching - actualNew;

    const totalEl = document.getElementById('statTotal');
    const watchingEl = document.getElementById('statWatching');
    const notWatchingEl = document.getElementById('statNotWatching');

    if (lastKnownStats.total !== null && lastKnownStats.total !== total) popStat(totalEl);
    if (lastKnownStats.watching !== null && lastKnownStats.watching !== actualWatching) popStat(watchingEl);
    if (lastKnownStats.notWatching !== null && lastKnownStats.notWatching !== actualNotWatching) popStat(notWatchingEl);

    totalEl.textContent = total;
    watchingEl.textContent = actualWatching;
    notWatchingEl.textContent = actualNotWatching;

    lastKnownStats = { total, watching: actualWatching, notWatching: actualNotWatching };
    refreshWatchingEyeBtn();
}

// Single source of truth for whether the "Watching" eye-icon link should be
// visible: hidden whenever there's unresolved new-show ambiguity, AND
// whenever there are simply zero shows currently marked watching (nothing to
// show on watched.php anyway). Called after every action that could affect
// either value, so it never needs a page refresh to update.
function refreshWatchingEyeBtn() {
    const watchingEyeBtn = document.getElementById('watchingEyeBtn');
    if (!watchingEyeBtn) return;
    const canShow = remainingNewCount === 0 && lastKnownStats.watching > 0;
    watchingEyeBtn.style.display = canShow ? 'inline-flex' : 'none';
}

// ============================================================================
// BACK TO TOP button - appears after scrolling down a bit, stays floating
// until you scroll back up above the threshold or click it.
// ============================================================================
const backToTopBtn = document.getElementById('backToTop');
const BACK_TO_TOP_THRESHOLD = 300; // roughly 3 normal mouse-wheel scrolls

window.addEventListener('scroll', () => {
    backToTopBtn.classList.toggle('visible', window.scrollY > BACK_TO_TOP_THRESHOLD);
}, { passive: true });

backToTopBtn.addEventListener('click', () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// ============================================================================
// EXPERIMENTAL HEADER BANNER (temp.php only) - poster wall + TV, ported from
// the "Το έχω δει;" page's own animation. Self-contained IIFE; touches
// nothing above besides #bannerWall / #bannerScreen.
// ============================================================================
(async () => {
    const wallEl = document.getElementById('bannerWall');
    const screenEl = document.getElementById('bannerScreen');
    if (!wallEl || !screenEl) return;

    let items = [];
    try {
        const url = new URL(location.href);
        url.searchParams.set('action', 'wall');
        const res = await fetch(url, { headers: { Accept: 'application/json' } });
        const data = await res.json();
        items = data.items || [];
    } catch (_) { return; }
    if (items.length < 6) return;

    const shuffle = (a) => {
        a = a.slice();
        for (let i = a.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [a[i], a[j]] = [a[j], a[i]];
        }
        return a;
    };

    for (let r = 0; r < 3; r++) {
        const row = document.createElement('div');
        row.className = 'row';
        const set = shuffle(items);
        [...set, ...set].forEach((it) => {
            const img = document.createElement('img');
            img.src = it.p;
            img.alt = '';
            img.decoding = 'async';
            img.onerror = () => img.remove();
            row.append(img);
        });
        wallEl.append(row);
    }
    requestAnimationFrame(() => wallEl.classList.add('ready'));

    // TV screen: one random backdrop, picked fresh on every page load / month
    // change (i.e. on refresh) - deliberately NOT a slideshow/animation.
    const withBackdrop = items.filter((x) => x.b);
    if (withBackdrop.length) {
        const pick = withBackdrop[Math.floor(Math.random() * withBackdrop.length)];
        const img = document.createElement('img');
        img.src = pick.b;
        img.alt = '';
        img.decoding = 'async';
        img.className = 'on';
        screenEl.append(img);
    }
})();
</script>
</body>
</html>
