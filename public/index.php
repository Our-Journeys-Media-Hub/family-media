<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>World map · full page</title>
  <link rel="stylesheet" href="/Journeys-media/public/css/app.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap/dist/css/jsvectormap.min.css">
  <style>
    /* Full-page map below the nav */
    :root { --nav-h: 56px; } /* will be updated by JS to match your real nav height */
    html, body { height: 100%; }
    body { margin: 0; }
    nav  { position: sticky; top: 0; z-index: 10; }
    #worldmap {
      position: fixed;
      top: var(--nav-h);
      left: 0;
      right: 0;
      bottom: 0;         /* full height under the nav */
      width: 100vw;      /* full width */
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
    <!-- Add a button that links to the upload page -->
    <button onclick="window.location.href='/Journeys-media/public/upload_image.php'">Upload Image</button>
  </div>
</nav>

<div id="worldmap"></div>

<script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/js/jsvectormap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsvectormap/dist/maps/world.js"></script>
<script src="/Journeys-media/public/js/countryNames.js"></script>
<script>

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
      initial: { fill: '#8d8d8dff' },
      hover:   { fill: '#ffffffff' }
    },
    onRegionTooltipShow: (tooltip, code) => {
      let regionName = code;
      try {
        const worldMap = window.jsVectorMap?.maps?.world;
        if (worldMap && worldMap.paths[code]) {
          regionName = worldMap.paths[code].name;
        }
      } catch (e) {
        console.warn("Unable to retrieve the country name for:", code);
      }
      tooltip.text(regionName);
    },
    onRegionClick: (event, code) => {
      const regionName = countryNames[code] || code;
      try {
        const worldMap = window.jsVectorMap?.maps?.world;
        if (worldMap && worldMap.paths[code]) {
          regionName = worldMap.paths[code].name;
        }
      } catch (e) {
        console.warn("Unable to retrieve the country name for:", code);
      }
      window.location.href = `/Journeys-media/public/ViewCountry/country.php?name=${encodeURIComponent(regionName)}`;
    }
  });

  addEventListener('resize', () => map.updateSize());
</script>
</body>
</html>
