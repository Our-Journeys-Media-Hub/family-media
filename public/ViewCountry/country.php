<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/auth.php';

// ➤ Ajoute ici ton fichier PHP contenant la liste des pays ISO → noms complets
$countryNames = require __DIR__ . '/../countryNames.php';

require_login();

$pdo = db_connect();
$user = current_user();

// 🔹 Récupère le nom du pays depuis l’URL
$countryName = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Unknown Country';

// 🔹 Trouve le code ISO correspondant au nom du pays
$countryCode = array_search($countryName, $countryNames, true);

// Si aucun code trouvé, essaie avec les deux premières lettres
if (!$countryCode) {
    $countryCode = strtoupper(substr($countryName, 0, 2));
}

$images = [];
if ($countryCode) {
    // 🔹 Récupère toutes les images liées à ce pays
    $stmt = $pdo->prepare("SELECT * FROM images WHERE country_code = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$countryCode]);
    $allImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 🔹 Filtre : ne garde que les images que l’utilisateur peut voir
    foreach ($allImages as $img) {
        if (can_view_image($pdo, $user['id'], (int)$img['id'])) {
            $images[] = $img;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Country Details - <?= htmlspecialchars($countryName) ?></title>
  <link rel="stylesheet" href="/family-media/public/css/app.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }
    h1 {
      margin-bottom: 10px;
    }
    .images-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }
    .images-grid img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
      cursor: pointer;
      transition: transform 0.3s;
    }
    .images-grid img:hover {
      transform: scale(1.05);
    }
    #lightbox {
      display: none;
      position: fixed;
      z-index: 9999;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.8);
      align-items: center;
      justify-content: center;
    }
    #lightbox img {
      max-width: 90%;
      max-height: 90%;
      border-radius: 8px;
      box-shadow: 0 2px 15px rgba(0,0,0,0.5);
    }
  </style>
</head>
<body>
  <h1>Country: <?= htmlspecialchars($countryName) ?></h1>
  <a href="/family-media/public/index.php">← Back to Map</a>

  <?php if (!empty($images)): ?>
    <div class="images-grid">
      <?php foreach ($images as $img): ?>
        <img src="/family-media/<?= htmlspecialchars($img['file_path']) ?>" 
             alt="<?= htmlspecialchars($img['title']) ?>"
             title="<?= htmlspecialchars($img['title']) ?>">
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No images available for this country.</p>
  <?php endif; ?>

  <div id="lightbox" onclick="this.style.display='none'">
    <img id="lightbox-img" src="" alt="">
  </div>

  <script>
    const images = document.querySelectorAll('.images-grid img');
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');

    images.forEach(img => {
      img.addEventListener('click', () => {
        lightboxImg.src = img.src;
        lightbox.style.display = 'flex';
      });
    });

    lightbox.addEventListener('click', () => {
      lightbox.style.display = 'none';
      lightboxImg.src = '';
    });
  </script>
</body>
</html>
