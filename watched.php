<?php
// Kept in sync manually with the same variable in trakt.php - bump both
// together when releasing a new version.
$TraktVersion = 'v2.2.0';

date_default_timezone_set('Europe/Athens');

// Renders a friendly notice when this month has never been visited on
// trakt.php yet (no state file = nothing has actually been decided, so
// showing "everything is watched" would be misleading).
function renderNoDataYet($monthLabel, $month, $year) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>No Data Yet</title>
        <link rel="shortcut icon" href="images/favicon.ico">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --bg: #0a0c10; --gold: #e8b545; --gold-soft: #f4d385; --crimson: #e0384d;
                --text: #eef0f4; --text-dim: #9aa2b1; --text-faint: #5c6478;
                --card: #151923; --card-border: #242a38;
            }
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body {
                background: radial-gradient(circle at 15% 0%, rgba(232,181,69,0.08), transparent 40%),
                            radial-gradient(circle at 85% 20%, rgba(224,56,77,0.07), transparent 45%), var(--bg);
                color: var(--text); font-family: 'Inter', sans-serif; min-height: 100vh;
                display: flex; align-items: center; justify-content: center; padding: 24px;
            }
            .panel { max-width: 480px; width: 100%; text-align: center; }
            .panel img.error-icon { display: block; margin: 0 auto 22px auto; width: 220px; height: auto; }
            .panel h1 {
                font-family: 'Bebas Neue', sans-serif; font-size: clamp(1.8rem, 5vw, 2.6rem); letter-spacing: 1.5px;
                background: linear-gradient(90deg, var(--gold-soft), var(--gold) 40%, var(--crimson));
                -webkit-background-clip: text; background-clip: text; color: transparent; margin-bottom: 14px;
            }
            .panel .msg { color: var(--text-dim); font-size: 0.95rem; line-height: 1.6; margin-bottom: 28px; }
            .go-btn {
                display: inline-block; padding: 12px 26px; background: var(--card); border: 1px solid var(--card-border);
                border-radius: 10px; color: var(--text); text-decoration: none; font-weight: 700; font-size: 0.95rem;
                transition: all .18s ease;
            }
            .go-btn:hover { border-color: var(--gold); background: rgba(232,181,69,0.1); transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class="panel">
            <img class="error-icon" src="images/nowatching.png" alt="No data yet">
            <h1>No Data Yet</h1>
            <div class="msg">
                You haven't visited <strong><?php echo htmlspecialchars($monthLabel); ?></strong> on the main page yet, so nothing has been marked "not watching" for that month.
            </div>
            <a class="go-btn" href="trakt.php?month=<?php echo (int)$month; ?>&amp;year=<?php echo (int)$year; ?>">&larr; Go to New Shows for this month</a>
        </div>
    </body>
    </html>
    <?php
}

// This page is only ever reached via a valid link from trakt.php (the eye
// icon next to "Watching:"), so unlike trakt.php it doesn't need the full
// friendly month-picker - missing/invalid input just quietly falls back to
// the current month/year instead of showing an error.
$requestedYear = $_GET['year'] ?? null;
$TraktYear = ($requestedYear !== null && ctype_digit((string)$requestedYear))
    ? (int)$requestedYear
    : (int)date('Y');

$requestedMonth = $_GET['month'] ?? null;
$isValidMonth = $requestedMonth !== null
    && ctype_digit((string)$requestedMonth)
    && (int)$requestedMonth >= 1
    && (int)$requestedMonth <= 12;
$TraktMonth = $isValidMonth ? (int)$requestedMonth : (int)date('n');

$TraktDays = (int)date('t', strtotime(sprintf("%04d-%02d-01", $TraktYear, $TraktMonth)));

// Trakt API credentials live in config.php (NOT committed to git - see config.example.php).
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die('Missing config.php. Copy config.example.php to config.php and fill in your Trakt Client ID.');
}
require $configFile;

if (!isset($TmdbApiKey)) {
    $TmdbApiKey = null;
}

// Keep these filters identical to trakt.php, so the shows considered here
// match exactly what you'd have seen (and marked watching) on the main page.
$TraktGenres    = '-animation,-anime,-children,-game-show,-home-and-garden,-music,-reality,-special-interest,-talk-show';
$TraktCountries = 'ar,au,at,be,br,ca,cl,cn,co,cz,dk,fi,fr,de,gr,hk,is,in,ie,it,jp,kr,mx,nl,nz,no,pl,pt,za,es,se,ch,tr,gb,us';
$TraktNetworkFilter = []; // e.g. ["Netflix", "Prime Video"]

$TraktLogoTop    = 'images/trakttop.png';
$TraktLogoButton = 'images/traktlogo.png';
$TraktNoPoster   = 'images/nopostertv.png';

// Same flag lookup table as trakt.php.
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

// Same network-logo lookup/cache as trakt.php (shares the same manifest file
// and images/networks/ folder, so a logo fetched from either page is reused
// by the other - no duplicate downloads).
function getNetworkLogo($networkName, $tmdbShowId, $tmdbApiKey) {
    static $manifest = null;
    static $manifestFile = null;

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

    if (isset($manifest[$key])) {
        return $manifest[$key]['file'] ?? null;
    }

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
        // A missing logo isn't worth breaking the page over - falls back below.
    }

    $manifest[$key] = ['file' => $logoPath];
    @file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT));

    return $logoPath;
}

$startDate = sprintf("%04d-%02d-01", $TraktYear, $TraktMonth);
$endDate   = date('Y-m-t', strtotime($startDate));
$monthLabel = (new DateTime($startDate))->format('F');

// If trakt.php has never been visited for this month, there's no state file -
// which would otherwise make every show here look "watched" by default
// (nothing has been excluded, because nothing has ever been decided). Rather
// than silently show possibly-misleading data, bail out with a clear message.
$stateFile = __DIR__ . "/data/state_{$TraktYear}_{$TraktMonth}.json";
if (!file_exists($stateFile)) {
    renderNoDataYet($monthLabel, $TraktMonth, $TraktYear);
    exit;
}

$baseUri = "https://api.trakt.tv/calendars/all/shows/new/{$startDate}/{$TraktDays}";
$queryParts = ["extended=full,images"];
if (!empty($TraktGenres))    $queryParts[] = "genres=" . urlencode($TraktGenres);
if (!empty($TraktCountries)) $queryParts[] = "countries=" . urlencode($TraktCountries);
$uri = $baseUri . "?" . implode("&", $queryParts);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $uri);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "trakt-api-version: 2",
    "trakt-api-key: {$TraktClientId}",
    "Content-Type: application/json",
    "User-Agent: PHP-Script/1.0",
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$shows = [];
if ($httpcode == 200 && $response) {
    $shows = json_decode($response, true) ?? [];
}

// Same UTC -> local boundary fix as trakt.php.
if (!empty($shows)) {
    $shows = array_filter($shows, function($item) use ($startDate, $endDate) {
        $localDate = new DateTime($item['first_aired']);
        $localDate->setTimezone(new DateTimeZone(date_default_timezone_get()));
        $airDate = $localDate->format('Y-m-d');
        return ($airDate >= $startDate && $airDate <= $endDate);
    });
}

if (!empty($TraktNetworkFilter) && !empty($shows)) {
    $shows = array_filter($shows, function($item) use ($TraktNetworkFilter) {
        return in_array($item['show']['network'] ?? '', $TraktNetworkFilter);
    });
}

usort($shows, function($a, $b) {
    return strtotime($a['first_aired']) - strtotime($b['first_aired']);
});

// Load this month's persisted "not watching" list, so we only keep shows
// that are actually being watched (i.e. NOT in that list). (Existence of
// $stateFile was already confirmed above, before the API call.)
$state = json_decode(file_get_contents($stateFile), true);
$notWatching = $state['notWatching'] ?? [];
$notWatchingSet = array_flip($notWatching);

$watchingShows = array_values(array_filter($shows, function ($item) use ($notWatchingSet) {
    $slug = $item['show']['ids']['slug'] ?? '';
    return $slug !== '' && !isset($notWatchingSet[$slug]);
}));
$totalWatching = count($watchingShows);
$generatedStamp = date('H:i');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watching Shows &ndash; <?php echo htmlspecialchars($monthLabel); ?></title>
    <link rel="shortcut icon" href="images/favicon.ico">
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
        header.hero { padding: 56px 6vw 34px 6vw; border-bottom: 1px solid var(--card-border); background: linear-gradient(180deg, rgba(232,181,69,0.06), transparent); }
        .brand-row { display: flex; align-items: center; gap: 20px; flex-wrap: wrap; }
        .brand-row img.brand-logo { height: 64px; filter: drop-shadow(0 4px 12px rgba(0,0,0,0.55)); }
        .brand-row h1 {
            font-family: 'Bebas Neue', sans-serif; font-size: clamp(2.4rem, 5.6vw, 4.2rem); letter-spacing: 2px;
            background: linear-gradient(90deg, var(--gold-soft), var(--gold) 40%, var(--crimson)); -webkit-background-clip: text; color: transparent;
        }
        header.hero .sub { margin-top: 18px; color: var(--text-dim); display: flex; align-items: flex-start; gap: 18px; flex-wrap: wrap; }
        header.hero .sub span { background: var(--bg-panel); border: 1px solid var(--card-border); padding: 6px 14px; border-radius: 999px; font-size: 0.86rem; color: var(--text); }
        .stat-icon { height: 17px; width: auto; object-fit: contain; vertical-align: middle; margin-right: 8px; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 18px; color: var(--text-dim); font-size: 0.85rem; text-decoration: none; }
        .back-link:hover { color: var(--gold-soft); }

        main { padding: 10px 6vw 0 6vw; }
        .day-block { margin-top: 44px; }
        .day-header { display: flex; align-items: center; gap: 14px; margin-bottom: 20px; border-bottom: 2px solid var(--card-border); padding-bottom: 10px; }
        .day-header .day-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.9rem; color: var(--gold-soft); letter-spacing: 1px; display: flex; align-items: center; }
        .day-header .day-title img { width: 83px; height: 83px; object-fit: contain; display: block; }
        .day-header .day-count { color: var(--text-faint); font-size: 1.3rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 26px; }

        .card { background: var(--card); border: 1px solid var(--card-border); border-radius: 14px; overflow: hidden; position: relative; transition: all .22s ease; display: flex; flex-direction: column; }
        .card:hover { transform: translateY(-6px) scale(1.015); box-shadow: 0 18px 40px rgba(0,0,0,0.55), 0 0 0 1px rgba(232,181,69,0.35); border-color: var(--gold); }

        .poster-wrap { position: relative; width: 100%; aspect-ratio: 2/3; background: linear-gradient(135deg, #1c2130, #10131b); overflow: hidden; }
        .poster-wrap img { width: 100%; height: 100%; object-fit: cover; }

        .rating-badge { position: absolute; top: 10px; right: 10px; z-index: 5; background: rgba(10,12,16,0.78); border: 1px solid rgba(232,181,69,0.5); color: var(--gold-soft); font-size: 0.78rem; font-weight: 700; padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px); }

        .card-body { padding: 14px 16px 16px 16px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
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

        /* Bigger, centered, gradient air-date - stands out much more than the
           small plain date used on the main trakt.php cards. */
        .premiere-highlight {
            margin-top: auto; padding-top: 12px; border-top: 1px solid var(--card-border);
            text-align: center; font-size: 1.35rem; font-weight: 800; letter-spacing: 0.3px;
            background: linear-gradient(90deg, var(--gold-soft), var(--gold) 45%, var(--crimson));
            -webkit-background-clip: text; background-clip: text; color: transparent;
        }

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
    </style>
</head>
<body>

<header class="hero">
    <div class="brand-row">
        <img class="brand-logo" src="<?php echo htmlspecialchars($TraktLogoTop); ?>" alt="Trakt">
        <h1>Watching Shows &mdash; <?php echo htmlspecialchars($monthLabel); ?></h1>
    </div>
    <div class="sub">
        <span><img src="images/watching.png" alt="" class="stat-icon"> <?php echo ($totalWatching === 0) ? 'No Shows' : $totalWatching . ' Watching'; ?></span>
        <span>🕒 <?php echo htmlspecialchars($generatedStamp); ?></span>
    </div>
    <a class="back-link" href="trakt.php?month=<?php echo $TraktMonth; ?>&amp;year=<?php echo $TraktYear; ?>">&larr; Back to New Shows</a>
</header>

<main>
    <section class="day-block">
        <div class="day-header">
            <div class="day-title"><img src="images/watchedshows.png" alt="Watching Shows"></div>
            <div class="day-count"><?php echo $totalWatching; ?> show<?php echo ($totalWatching == 1) ? '' : 's'; ?></div>
        </div>

        <?php if ($totalWatching === 0): ?>
            <div class="empty-state">
                <img class="empty-icon" src="images/noshows.png" alt="No watching shows">
                <div>No shows marked as watching this month yet.</div>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($watchingShows as $item):
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

                    $traktSlug = $show['ids']['slug'] ?? '';
                    $traktUrl = "https://trakt.tv/shows/{$traktSlug}";
                ?>
                <div class="card">
                    <div class="poster-wrap">
                        <?php if ($rating): ?>
                            <div class="rating-badge">⭐ <?php echo $rating; ?></div>
                        <?php endif; ?>
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
                        <div class="premiere-highlight"><?php echo $airedDateStr; ?></div>
                        <div class="links-row">
                            <a class="trakt-btn" href="<?php echo htmlspecialchars($traktUrl); ?>" target="_blank" rel="noopener">
                                <img src="<?php echo htmlspecialchars($TraktLogoButton); ?>" alt="View on Trakt">
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<hr class="footer-divider">
<footer class="site-footer">
    <p>Idea, design, coded &amp; vibe coded by great_vc -&nbsp;&nbsp;&nbsp;<span class="version-tag"> 🏷️ &nbsp;&nbsp;<?php echo htmlspecialchars($TraktVersion); ?></span><br>
    No AI was harmed during this, except from Fuck you Gemini cannot distinguish &lt;body&gt; from &lt;script&gt;</p>
</footer>

</body>
</html>
