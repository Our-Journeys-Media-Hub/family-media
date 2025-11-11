<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/auth.php';

// ➤ Ajoute ici ton fichier PHP contenant la liste des pays ISO → noms complets
$countryNames = require __DIR__ . '/../countryNames.php';
require_login();

$pdo = db_connect();
$user = current_user();

// Retrieve the user's families
$stmt = $pdo->prepare("SELECT f.id, f.family_name FROM families f
    JOIN family_memberships m ON f.id = m.family_id
    WHERE m.user_id = ?");
$stmt->execute([$user['id']]);
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch images related to the country
$countryCode = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Unknown Country';
$countryCode = array_search($countryCode, $countryNames, true) ?: strtoupper(substr($countryCode, 0, 2));

$images = [];
if ($countryCode) {
    $stmt = $pdo->prepare("SELECT * FROM images WHERE country_code = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$countryCode]);
    $allImages = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
  <title>Country Details - <?= htmlspecialchars($countryCode) ?></title>
  <link rel="stylesheet" href="/Journeys-media/public/css/app.css">
  <style>
   body {
  font-family: Arial, sans-serif;
  padding: 20px;
  background-color: #000000ff;
}

h1 {
  margin-bottom: 10px;
}

.container {
  display: flex;
  justify-content: space-between;
  gap: 20px;
}

.images-container {
  flex: 2;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
}

.images-container img, .images-container video {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.images-container img:hover, .images-container video:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.upload-container {
  flex: 0 0 300px;
  padding: 20px;
  background-color: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 350px;
}

.upload-container h2 {
  margin-bottom: 20px;
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.upload-container label {
  font-size: 14px;
  color: #555;
}

.upload-container input,
.upload-container select,
.upload-container button {
  width: 100%;
  padding: 12px;
  margin: 12px 0;
  font-size: 16px;
  border-radius: 8px;
  border: 1px solid #ccc;
  transition: border-color 0.3s, background-color 0.3s;
}

.upload-container input:focus,
.upload-container select:focus {
  outline: none;
  border-color: #5a67d8;
}

.upload-container input[type="file"] {
  padding-left: 20px;
  background-color: #f9f9f9;
}

.upload-container button {
  background-color: #5a67d8;
  color: #fff;
  cursor: pointer;
  border: none;
  transition: background-color 0.3s ease;
}

.upload-container button:hover {
  background-color: #434190;
}

#lightbox {
  display: none;
  position: fixed;
  z-index: 9999;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  align-items: center;
  justify-content: center;
}

#lightbox img, #lightbox video {
  max-width: 90%;
  max-height: 90%;
  border-radius: 8px;
  box-shadow: 0 2px 15px rgba(0, 0, 0, 0.5);
}
  </style>
</head>
<body>

  <h1>Country: <?= htmlspecialchars($countryCode) ?></h1>
  <a href="/Journeys-media/public/index.php">← Back to Map</a>

  <div class="container">
    <!-- Images Grid (Left Side) -->
    <div class="images-container">
      <?php if (!empty($images)): ?>
        <?php foreach ($images as $img): ?>
          <?php if (in_array(pathinfo($img['file_path'], PATHINFO_EXTENSION), ['mp4', 'mov', 'avi'])): ?>
            <video controls>
              <source src="/Journeys-media/<?= htmlspecialchars($img['file_path']) ?>" type="video/<?= pathinfo($img['file_path'], PATHINFO_EXTENSION) ?>">
              Your browser does not support the video tag.
            </video>
          <?php else: ?>
            <img src="/Journeys-media/<?= htmlspecialchars($img['file_path']) ?>" 
                 alt="<?= htmlspecialchars($img['title']) ?>"
                 title="<?= htmlspecialchars($img['title']) ?>">
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No images or videos available for this country.</p>
      <?php endif; ?>
    </div>

    <!-- Upload Form (Right Side) -->
    <div class="upload-container">
      <h2>Upload a new image/video</h2>
      <form action="/Journeys-media/public/upload_image.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="country" value="<?= htmlspecialchars($countryCode) ?>">

        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required><br>

        <label for="family">Family:</label>
        <select name="family" id="family" required>
          <?php foreach ($families as $f): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['family_name']) ?></option>
          <?php endforeach; ?>
        </select><br>

        <label for="visibility">Visibility:</label>
        <select name="visibility" id="visibility" required>
          <option value="family">Family</option>
          <option value="private">Private</option>
          <option value="custom">Custom</option>
        </select><br>

        <label for="custom_users">Allowed Users (for custom, enter user IDs separated by commas):</label>
        <input type="text" name="custom_users" id="custom_users"><br>

        <label for="image">File:</label>
        <input type="file" name="image[]" id="image" accept="image/*,video/*" multiple required><br><br>

        <button type="submit">Upload</button>
      </form>
    </div>
  </div>

  <div id="lightbox" onclick="this.style.display='none'">
    <img id="lightbox-img" src="" alt="">
  </div>

  <script>
    const images = document.querySelectorAll('.images-container img');
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const videos = document.querySelectorAll('.images-container video');

    images.forEach(img => {
      img.addEventListener('click', () => {
        lightboxImg.src = img.src;
        lightbox.style.display = 'flex';
      });
    });

    videos.forEach(video => {
      video.addEventListener('click', () => {
        lightboxImg.src = video.src;
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