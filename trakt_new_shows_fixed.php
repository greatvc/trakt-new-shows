<?php
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
$TraktYear  = date("Y");

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
        echo json_encode(['notWatching' => [], 'history' => [], 'lastCount' => null]);
    }
    exit;
}

$TraktDays  = (int)date('t', strtotime(sprintf("%04d-%02d-01", $TraktYear, $TraktMonth)));           

// Credentials live in config.php, which is NOT committed to git (see .gitignore).
// Copy config.example.php to config.php and fill in your own values.
$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    die('Missing config.php. Copy config.example.php to config.php and add your Trakt API credentials.');
}
require $configPath;

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
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $uri);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer {$TraktAccessToken}",
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

// ============================================================================
// TEMPORARY DEBUG - remove once the boundary issue is confirmed fixed
// Prints raw (UTC) vs local first_aired for every show, plus whether it
// passed/failed the date-range filter, so we can see exactly what Trakt sent.
// ============================================================================
if (isset($_GET['debug'])) {
    echo "<pre style='background:#111;color:#0f0;padding:20px;font-size:12px;'>";
    echo "Configured range: {$startDate} to {$endDate}\n\n";
    $debugAll = json_decode($response, true) ?? [];
    foreach ($debugAll as $item) {
        $raw = $item['first_aired'] ?? '(missing)';
        $title = $item['show']['title'] ?? '(unknown)';
        try {
            $ld = new DateTime($item['first_aired']);
            $ld->setTimezone(new DateTimeZone(date_default_timezone_get()));
            $local = $ld->format('Y-m-d H:i');
            $localDateOnly = $ld->format('Y-m-d');
            $inRange = ($localDateOnly >= $startDate && $localDateOnly <= $endDate) ? 'PASS' : 'FILTERED OUT';
        } catch (Exception $e) {
            $local = 'PARSE ERROR: ' . $e->getMessage();
            $inRange = 'ERROR';
        }
        echo str_pad($title, 40) . " | raw: " . str_pad($raw, 26) . " | local: " . str_pad($local, 18) . " | {$inRange}\n";
    }
    echo "</pre>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>New Shows &ndash; <?php echo htmlspecialchars($monthLabel); ?></title>
	<link rel="shortcut icon" href="favicon.ico">
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
        
        /* Updated networks chip width to 800px */
        header.hero .sub span.networks-chip { display: block; align-items: normal; border-radius: 14px; max-width: 800px; line-height: 1.6; text-align: left; white-space: normal; }

        .sync-panel {
            display: flex; position: fixed; top: 20px; right: 20px; z-index: 9999; align-items: center; gap: 8px;
            background: rgba(18,21,28,0.92); border: 1px solid var(--card-border); border-radius: 12px; padding: 8px 10px;
            box-shadow: 0 14px 32px rgba(0,0,0,0.5); backdrop-filter: blur(6px);
        }
        .sync-btn { cursor: pointer; background: rgba(255,255,255,0.05); border: 1px solid var(--card-border); color: var(--text); padding: 6px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 600; transition: all .18s ease; }
        .sync-btn:hover { background: var(--crimson); color: #fff; border-color: var(--crimson); }
        .sync-status { color: var(--text-dim); font-size: 0.72rem; margin-left: 5px; }

       .stats-bar { position: fixed; bottom: 22px; right: 22px; z-index: 9999; padding: 12px 16px; background: rgba(18,21,28,0.92); border: 1px solid var(--card-border); border-radius: 12px; min-width: 200px; font-size: 0.78rem; }
        .stats-row { display: flex; justify-content: space-between; width: 100%; margin-bottom: 4px; }
        .stats-row strong { color: var(--gold-soft); font-size: 0.92rem; display: inline-block; }
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
        .delta-msg { font-size: 0.7rem; color: #a1a1aa; margin-top: 2px; display: block; }
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
        
        .rating-badge { position: absolute; top: 10px; right: 10px; z-index: 5; background: rgba(10,12,16,0.78); border: 1px solid rgba(232,181,69,0.5); color: var(--gold-soft); font-size: 0.78rem; font-weight: 700; padding: 4px 8px; border-radius: 8px; backdrop-filter: blur(4px); }
        
        .watch-toggle { position: absolute; top: 48px; right: 10px; z-index: 6; width: 30px; height: 30px; border-radius: 50%; border: 1px solid rgba(255,255,255,0.18); background: rgba(10,12,16,0.78); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .18s ease; padding: 0; }
        .watch-toggle:hover { transform: scale(1.14); background: rgba(232,181,69,0.9); }
        
        .card-body { padding: 14px 16px 16px 16px; display: flex; flex-direction: column; gap: 8px; flex: 1; transition: opacity .22s ease; }
        .title-row { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
        .show-title { font-size: 1.05rem; font-weight: 700; line-height: 1.25; }
        .show-year { color: var(--text-faint); font-size: 0.85rem; }
        
        .meta-row { display: flex; flex-wrap: wrap; gap: 6px; font-size: 0.74rem; }
        .chip { background: rgba(255,255,255,0.05); border: 1px solid var(--card-border); color: var(--text-dim); padding: 3px 9px; border-radius: 999px; }
        .chip.network { color: var(--accent-blue); border-color: rgba(79,163,224,0.35); }
        .chip.country { color: var(--gold-soft); border-color: rgba(232,181,69,0.3); }
        
        .overview { color: var(--text-dim); font-size: 0.82rem; line-height: 1.45; flex: 1; }
        .premiere-info { margin-top: auto; padding-top: 10px; border-top: 1px solid var(--card-border); font-size: 0.82rem; font-weight: 600; }
        
        .links-row { display: flex; gap: 8px; margin-top: 8px; }
        .trakt-btn { flex: 1; display: flex; align-items: center; justify-content: center; padding: 9px 0; border-radius: 8px; border: 1px solid var(--card-border); background: rgba(255,255,255,0.03); transition: all .18s ease; }
        .trakt-btn img { height: 16px; }
        .trakt-btn:hover { background: var(--gold); box-shadow: 0 0 14px rgba(232,181,69,0.5); transform: translateY(-2px); }
        
        .empty-state { text-align: center; padding: 100px 20px; color: var(--text-faint); }
        .empty-state .emoji { font-size: 3rem; margin-bottom: 14px; }

        .footer-divider { border: 0; border-top: 1px solid var(--card-border); margin: 56px 6vw 0 6vw; }
        .site-footer { text-align: center; padding: 22px 6vw 36px 6vw; }
        .site-footer p { font-size: 0.72rem; color: var(--text-faint); opacity: 0.55; line-height: 1.7; }
    </style>
</head>
<body>

<header class="hero">
    <div class="brand-row">
        <img class="brand-logo" src="<?php echo htmlspecialchars($TraktLogoTop); ?>" alt="Trakt">
        <h1>New Shows &mdash; <?php echo htmlspecialchars($monthLabel); ?></h1>
    </div>
    <div class="sub">
        <span>📊 <?php echo $totalShowsFetched; ?> premiere<?php echo ($totalShowsFetched == 1) ? '' : 's'; ?></span>
        <?php if (!empty($TraktNetworkFilter)): ?>
            <span class="networks-chip">📡 Networks: <?php echo htmlspecialchars(implode(', ', $TraktNetworkFilter)); ?></span>
        <?php endif; ?>
        <span>🕒 <?php echo htmlspecialchars($generatedStamp); ?></span>
    </div>
</header>

<div id="syncPanel" class="sync-panel">
    <span id="syncStatus" class="sync-status">🟢 Storage Connected</span>
    <button type="button" class="sync-btn" onclick="clearData()">🗑️ Reset</button>
</div>

 <div id="statsBar" class="stats-bar">
        <div style="width: 100%;">
            <div class="stats-row"><span>📺 Total:</span><strong id="statTotal"><?php echo $totalShowsFetched; ?></strong></div>
            <span id="deltaMsg" class="delta-msg"></span>
        </div>
        <hr class="stats-divider">
        <div class="stats-row"><span>👀 Watching:</span><strong id="statWatching">0</strong></div>
        <div class="stats-row"><span>🚫 Not Watching:</span><strong id="statNotWatching">0</strong></div>
        <div id="historyLog" class="history-log"></div>
    </div>

<main>
    <?php if (empty($groupedByDay)): ?>
        <div class="empty-state">
            <div class="emoji">🕵️</div>
            <div>No new show premieres matched your filters for this range.</div>
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
                        $country = htmlspecialchars(strtoupper($show['country'] ?? ''));
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
                            <?php if ($rating): ?>
                                <div class="rating-badge">⭐ <?php echo $rating; ?></div>
                            <?php endif; ?>
                            
                            <button type="button" class="watch-toggle" onclick="toggleWatch(this)" title="Toggle watching">
                                <svg class="icon-open" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                <svg class="icon-closed" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"></path><circle cx="12" cy="12" r="3"></circle><line x1="2" y1="2" x2="22" y2="22"></line></svg>
                            </button>
                            <img src="<?php echo htmlspecialchars($posterUrl); ?>" alt="<?php echo $title; ?> poster" loading="lazy">
                        </div>
                        <div class="card-body">
                            <div class="title-row">
                                <div class="show-title"><?php echo $title; ?></div>
                                <?php if ($year): ?><div class="show-year"><?php echo $year; ?></div><?php endif; ?>
                            </div>
                            <div class="meta-row">
                                <?php if ($network): ?><span class="chip network">📡 <?php echo $network; ?></span><?php endif; ?>
                                <?php if ($country): ?><span class="chip country">🌍 <?php echo $country; ?></span><?php endif; ?>
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
    <p>Design, idea, coded &amp; vibe coded by great_vc - 2026<br>
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

async function loadState() {
    const res = await fetch(STATE_URL, { method: 'GET', cache: 'no-store' });
    if (!res.ok) throw new Error('Failed to load state: ' + res.status);
    return res.json();
}

async function saveState() {
    const payload = {
        notWatching: Array.from(notWatching),
        history: historyLog,
        lastCount: currentTotalShows
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
            [...historyLog].reverse().map(i => `<div style="display:flex; justify-content:space-between;"><span>${getRelativeDayLabel(i.date)} ${i.time}</span><span>${i.count} shows</span></div>`).join('');

        // Restore card toggle states
        document.querySelectorAll('.card').forEach(card => {
            const id = card.getAttribute('data-id');
            setCardState(card, notWatching.has(id));
        });

        // Delta tracking (did the count change since last visit?)
        const previousCount = state.lastCount;
        const deltaMsgElement = document.getElementById('deltaMsg');
        if (previousCount !== null && previousCount !== undefined) {
            if (currentTotalShows > previousCount) {
                const diff = currentTotalShows - previousCount;
                deltaMsgElement.textContent = `📈 (+${diff} shows since last run)`;
                deltaMsgElement.style.color = '#34d399';
            } else if (currentTotalShows < previousCount) {
                const diff = previousCount - currentTotalShows;
                deltaMsgElement.textContent = `📉 (-${diff} shows since last run)`;
                deltaMsgElement.style.color = '#f87171';
            } else {
                deltaMsgElement.textContent = `✅ Perfect Match`;
                deltaMsgElement.style.color = '#a1a1aa';
            }
        } else {
            deltaMsgElement.textContent = `(Initial Tracking)`;
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

// Pops/glows a stat number when its value changes, so the change is easy to spot
function popStat(el) {
    el.classList.remove('stat-pop');
    void el.offsetWidth; // force reflow so the animation can restart
    el.classList.add('stat-pop');
}

function updateStats() {
    const total = currentTotalShows;
    const actualNotWatching = document.querySelectorAll('.card.not-watching').length;
    const actualWatching = total - actualNotWatching;

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
}

async function clearData() {
    if (!confirm("Are you sure you want to reset your watching status and count tracking for this month?")) return;

    notWatching.clear();
    historyLog = [];

    document.querySelectorAll('.card').forEach(card => {
        setCardState(card, false);
    });

    document.getElementById('deltaMsg').textContent = `(Reset successfully)`;
    document.getElementById('historyLog').innerHTML = '';
    updateStats();

    try {
        await saveState();
        setSyncStatus(true);
    } catch (e) {
        console.error(e);
        setSyncStatus(false, 'Save failed');
        alert("⚠️ Warning: Reset could not be saved to the server.");
    }
}
</script>
</body>
</html>
