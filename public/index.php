<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$pdo = db_connect();
$user = current_user();

$uploadedCountries = [];

/*
  Wir holen nur die minimalen Felder und prüfen dann pro Bild:
  can_view_image($pdo, $user['id'], $imageId)
*/
$stmt = $pdo->query("
  SELECT id, country_code
  FROM images
  WHERE country_code IS NOT NULL
    AND country_code <> ''
");

$seen = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $imageId = (int)$row['id'];
  $code = strtoupper(trim($row['country_code']));

  if ($code === '') continue;

  if (can_view_image($pdo, (int)$user['id'], $imageId)) {
    $seen[$code] = true;
  }
}

$uploadedCountries = array_keys($seen);
sort($uploadedCountries);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>World map · full page</title>
  <link rel="stylesheet" href="/Journeys-media/public/css/app.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css">
  <style>
    :root { --nav-h: 56px; }
    html, body { height: 100%; }
    body { margin: 0; }
    nav  { position: sticky; top: 0; z-index: 10; }
    #worldmap {
      position: fixed;
      top: var(--nav-h);
      left: 0;
      right: 0;
      bottom: 0;
      width: 100vw;
      height: calc(100vh - var(--nav-h));
    }
  </style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="/Journeys-media/public/index.php">Home</a>
    <a href="/Journeys-media/public/groups.php?view=list">Groups</a>
    <a href="/Journeys-media/public/groups.php?view=myinvites">My invites</a>
    <a href="/Journeys-media/public/logout.php">Logout</a>
    <button onclick="window.location.href='/Journeys-media/public/upload_image.php'">Upload Image</button>
  </div>
</nav>

<div id="worldmap"></div>

<script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/js/jsvectormap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>
<script src="/Journeys-media/public/js/countryNames.js"></script>
<script>
  const uploadedCountries = <?= json_encode($uploadedCountries) ?>;

  const nav = document.querySelector('nav');
  const setNavH = () => {
    document.documentElement.style.setProperty('--nav-h', nav.offsetHeight + 'px');
  };
  setNavH();
  addEventListener('resize', setNavH);

  const map = new jsVectorMap({
    selector: '#worldmap',
    map: 'world',
    zoomButtons: true,

    regionStyle: {
      initial:  { fill: '#8d8d8dff' },
      hover:    { fill: '#070000ff' },
      selected: { fill: '#4100aaff' }
    },

    selectedRegions: uploadedCountries,

    onRegionTooltipShow: (tooltip, code) => {
      let regionName = code;
      try {
        const worldMap = window.jsVectorMap?.maps?.world;
        if (worldMap && worldMap.paths[code]) regionName = worldMap.paths[code].name;
      } catch (e) {}

      if (uploadedCountries.includes(code)) {
        tooltip.text(`${regionName} (images available)`);
      } else {
        tooltip.text(regionName);
      }
    },

    onRegionClick: (event, code) => {
      if (!uploadedCountries.includes(code)) return;

      let regionName = code;
      try {
        const worldMap = window.jsVectorMap?.maps?.world;
        if (worldMap && worldMap.paths[code]) regionName = worldMap.paths[code].name;
      } catch (e) {}

      window.location.href = `/Journeys-media/public/ViewCountry/country.php?code=${encodeURIComponent(code)}&name=${encodeURIComponent(regionName)}`;
    }
  });

  addEventListener('resize', () => map.updateSize());
</script>

</body>
</html>
