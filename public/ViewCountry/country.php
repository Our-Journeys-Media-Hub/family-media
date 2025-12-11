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

// Handle Image Upload
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    // Use the country from the form or fallback to current view context if set, but form must send it.
    // In the form (line 190), name="country" value="$countryCode" (ISO)
    $uploadCountryCode = $_POST['country'] ?? '';
    
    $family_id = (int)($_POST['family'] ?? 0);
    $visibility = $_POST['visibility'] ?? 'family';
    $custom_users = $_POST['custom_users'] ?? '';

    // Verify country code exists/is valid could be good, but we assume hidden input is correct.
    
    if (isset($_POST['delete_id'])) {
        $deleteId = (int)$_POST['delete_id'];
        $stmt = $pdo->prepare("SELECT * FROM images WHERE id = ?");
        $stmt->execute([$deleteId]);
        $imgToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($imgToDelete && $imgToDelete['uploaded_by'] == $user['id']) {
            // Delete physical file
            $fullPath = __DIR__ . '/../../' . $imgToDelete['file_path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            // Delete DB records
            $pdo->prepare("DELETE FROM image_permissions WHERE image_id = ?")->execute([$deleteId]);
            $pdo->prepare("DELETE FROM images WHERE id = ?")->execute([$deleteId]);
            $message = "✅ Media deleted successfully.";
        } else {
            $message = "❌ Error: Permission denied or file not found.";
        }
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'][0] === 0) {
        // Use ISO code for folder name as per existing logic?
        // upload_image.php used: $uploadDir = __DIR__ . '/../images/' . $country . '/';
        // In country.php, $countryCode is the ISO.
        
        $uploadDir = __DIR__ . '/../../images/' . $uploadCountryCode . '/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                $message = "❌ Failed to create directory: $uploadDir";
            }
        }

        if (empty($message) && !is_writable($uploadDir)) {
           $message = "❌ Directory $uploadDir is not writable by PHP";
        }

        if (empty($message)) {
            foreach ($_FILES['image']['name'] as $index => $fileName) {
                $filename = basename($fileName);
                // Avoid overwriting? Unique ID? For now keeping original logic.
                // Original: Just basename. 
                $targetFile = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'][$index], $targetFile)) {
                    $filePath = "images/$uploadCountryCode/$filename";

                    $stmt = $pdo->prepare("
                        INSERT INTO images (family_id, uploaded_by, country_code, title, file_path, visibility)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$family_id, $user['id'], $uploadCountryCode, $title, $filePath, $visibility]);
                    $imageId = $pdo->lastInsertId();

                    if ($visibility === 'custom' && !empty($custom_users)) {
                        $users = array_map('trim', explode(',', $custom_users));
                        $stmtPerm = $pdo->prepare("INSERT INTO image_permissions (image_id, user_id) VALUES (?, ?)");
                        foreach ($users as $uid) {
                            if (is_numeric($uid)) {
                                $stmtPerm->execute([$imageId, (int)$uid]);
                            }
                        }
                    }
                } else {
                    $message .= "❌ Error while uploading $fileName. ";
                }
            }
            if (empty($message)) {
                $message = "✅ Images uploaded successfully!";
                // Refresh logic: The logic continues below to fetch images, so new images will be seen.
                // Redirect to avoid form resubmission on refresh?
                // header("Location: " . $_SERVER['REQUEST_URI']);
                // exit;
                // But if we redirect we lose the success message unless we use session.
                // User asked to "stay on the page". Standard POST/Redirect/Get pattern is best, 
                // but "state on the page" usually just means "don't go to a different separate success page".
                // I'll keep it simple: Show message on same page.
            }
        }
    } else {
        $message = "❌ No files selected or upload error.";
    }
}

// Fetch images related to the country
$inputName = $_GET['name'] ?? $_POST['country'] ?? 'Unknown Country';
$countryCode = htmlspecialchars($inputName);
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

// Resolve full country name
$countryName = $countryNames[$countryCode] ?? $countryCode;

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Country Details - <?= htmlspecialchars($countryName) ?></title>
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

.images-container .media-item img:hover, 
.images-container .media-item video:hover {
  transform: scale(1.05);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.upload-container {
  flex: 0 0 300px;
  padding: 20px;
  background-color: #1a1a1a;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
  width: 100%;
  max-width: 350px;
}

.upload-container h2 {
  margin-bottom: 20px;
  font-size: 20px;
  font-weight: 600;
  color: #fff;
}

.upload-container label {
  font-size: 14px;
  color: #ccc;
}

.upload-container input,
.upload-container select,
.upload-container button {
  width: 100%;
  padding: 12px;
  margin: 12px 0;
  font-size: 16px;
  border-radius: 8px;
  border: 1px solid #444;
  background-color: #222;
  color: #fff;
  transition: border-color 0.3s, background-color 0.3s;
}

.upload-container input:focus,
.upload-container select:focus {
  outline: none;
  border-color: #5a67d8;
}

.upload-container input[type="file"] {
  padding-left: 20px;
  background-color: #222;
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

.media-item {
  position: relative;
  overflow: hidden;
  border-radius: 8px;
}

.images-container .media-item img, 
.images-container .media-item video {
  width: 100%;
  height: 200px;
  object-fit: cover;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  display: block;
}

.images-container .media-item:hover img, 
.images-container .media-item:hover video {
  transform: scale(1.05);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}

.delete-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  background: rgba(220, 38, 38, 0.9);
  color: white;
  border: none;
  border-radius: 50%;
  width: 28px;
  height: 28px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  line-height: 1;
  transition: background 0.2s;
  z-index: 10;
}

.delete-btn:hover {
  background: rgba(185, 28, 28, 1);
}

.filter-bar {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  padding: 10px 0; /* Remove horizontal padding as it's not a card anymore */
  background-color: transparent;
  align-items: center;
  flex-wrap: wrap;
}

.filter-bar select {
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #444; /* Dark border */
  font-size: 16px;
  background-color: #1a1a1a; /* Dark background */
  color: #fff; /* White text */
  transition: border-color 0.3s, background-color 0.3s;
  min-width: 150px;
}

.filter-bar select:focus {
  outline: none;
  border-color: #5a67d8;
}

.filter-bar label {
  font-weight: 600;
  color: #fff; /* White text matching body */
  font-size: 14px;
}
  </style>
</head>
<body>

  <h1>Country: <?= htmlspecialchars($countryName) ?></h1>
  <a href="/Journeys-media/public/index.php">← Back to Map</a>

  <div class="container">
    <!-- Images Grid (Left Side) -->
    <div style="flex: 2;">
        <div class="filter-bar">
            <label>Sort by:</label>
            <select id="sortDate">
                <option value="newest">Date (Newest)</option>
                <option value="oldest">Date (Oldest)</option>
            </select>

            <label>Filter by:</label>
            <select id="filterType">
                <option value="all">All Media</option>
                <option value="image">Photos</option>
                <option value="video">Videos</option>
            </select>
        </div>
        <div class="images-container">
      <?php if (!empty($images)): ?>
        <?php foreach ($images as $img): ?>
          <?php 
            $ext = pathinfo($img['file_path'], PATHINFO_EXTENSION);
            $isVideo = in_array(strtolower($ext), ['mp4', 'mov', 'avi']);
            $type = $isVideo ? 'video' : 'image';
          ?>
          <?php if ($isVideo): ?>
          <div class="media-item" data-date="<?= $img['uploaded_at'] ?>" data-type="<?= $type ?>">
            <video controls>
              <source src="/Journeys-media/<?= htmlspecialchars($img['file_path']) ?>" type="video/<?= $ext ?>">
              Your browser does not support the video tag.
            </video>
            <?php if ($img['uploaded_by'] == $user['id']): ?>
                <form method="post" action="?name=<?= htmlspecialchars($countryCode) ?>" onsubmit="return confirm('Are you sure you want to delete this?');" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $img['id'] ?>">
                    <input type="hidden" name="country" value="<?= htmlspecialchars($countryCode) ?>">
                    <button type="submit" class="delete-btn" title="Delete">&times;</button>
                </form>
            <?php endif; ?>
          </div>
          <?php else: ?>
          <div class="media-item" data-date="<?= $img['uploaded_at'] ?>" data-type="<?= $type ?>">
            <img src="/Journeys-media/<?= htmlspecialchars($img['file_path']) ?>" 
                 alt="<?= htmlspecialchars($img['title']) ?>"
                 title="<?= htmlspecialchars($img['title']) ?>">
            <?php if ($img['uploaded_by'] == $user['id']): ?>
                <form method="post" action="?name=<?= htmlspecialchars($countryCode) ?>" onsubmit="return confirm('Are you sure you want to delete this?');" style="display:inline;">
                    <input type="hidden" name="delete_id" value="<?= $img['id'] ?>">
                     <input type="hidden" name="country" value="<?= htmlspecialchars($countryCode) ?>">
                    <button type="submit" class="delete-btn" title="Delete">&times;</button>
                </form>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No images or videos available for this country.</p>
      <?php endif; ?>
    </div>
    </div>

    <!-- Upload Form (Right Side) -->
    <div class="upload-container">
      <?php if (!empty($message)): ?>
          <div class="alert" style="margin-bottom: 15px; padding: 10px; border-radius: 5px; font-size: 14px; 
              <?php echo strpos($message, '❌') !== false ? 'background: #fee2e2; color: #7f1d1d;' : 'background: #dcfce7; color: #14532d;'; ?>">
              <?= htmlspecialchars($message) ?>
          </div>
      <?php endif; ?>
      <h2>Upload a new image/video</h2>
      <form action="/Journeys-media/public/ViewCountry/country.php?name=<?= htmlspecialchars($countryCode) ?>" method="post" enctype="multipart/form-data">
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
    const container = document.querySelector('.images-container');
    const sortSelect = document.getElementById('sortDate');
    const filterSelect = document.getElementById('filterType');
    
    // Lightbox logic
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightbox-img');

    function attachLightboxEvents() {
        const images = document.querySelectorAll('.images-container .media-item img');
        const videos = document.querySelectorAll('.images-container .media-item video');
    
        images.forEach(img => {
          img.addEventListener('click', () => {
            lightboxImg.src = img.src;
            lightbox.style.display = 'flex';
          });
        });
    
        videos.forEach(video => {
          video.addEventListener('click', () => {
             // For video, we might want to show it in a video tag in lightbox, 
             // currently the lightbox only has an img tag. 
             // Keeping original behavior where it puts video src in img (which might fail or just show poster if set)
             // or maybe the user wants to play it. 
             // Given the original code: "lightboxImg.src = video.src", this suggests it might not play properly 
             // if lightbox-img is an <img> tag. But I will respect existing logic or improve it slightly 
             // if I can. The user asked for filter, not lightbox fix.
             // I'll stick to preserving existing behavior but re-attaching events is crucial if I re-render,
             // but here I am just hiding/showing or re-appending elements, so events *should* persist
             // as long as I don't destroy the elements.
          });
        });
    }

    // Since I'm only re-ordering or hiding elements, I don't need to re-attach listeners 
    // IF I treat them as DOM nodes.

    const originalItems = Array.from(container.children);

    function updateGallery() {
        const sortMode = sortSelect.value;
        const filterMode = filterSelect.value;

        // Filter
        let visibleItems = originalItems.filter(item => {
            const itemType = item.getAttribute('data-type');
            if (filterMode === 'all') return true;
            return itemType === filterMode;
        });

        // Sort
        visibleItems.sort((a, b) => {
            const dateA = new Date(a.getAttribute('data-date'));
            const dateB = new Date(b.getAttribute('data-date'));
            return sortMode === 'newest' ? dateB - dateA : dateA - dateB;
        });

        // Render
        container.innerHTML = '';
        visibleItems.forEach(item => container.appendChild(item));
        
        // Note: filtered-out items are removed from DOM. 
        // If we want to keep them but hide them, we could use style.display,
        // but removing/appending is fine for re-sorting.
        
        // Re-attach lightbox events? 
        // No, the elements are the same objects, listeners are preserved.
        // But the original "images" and "videos" nodelists in the previous script 
        // are static or won't cover these moved elements if specific logic was tied to index? 
        // The original logic was:
        // images.forEach(...) adding click listener.
        // Moving the element in DOM does not remove listeners.
    }

    sortSelect.addEventListener('change', updateGallery);
    filterSelect.addEventListener('change', updateGallery);

    // Initial Lightbox Setup (Preserving original logic style)
    const images = document.querySelectorAll('.images-container .media-item img');
    const videos = document.querySelectorAll('.images-container .media-item video');

    images.forEach(img => {
      img.addEventListener('click', () => {
        lightboxImg.src = img.src;
        lightbox.style.display = 'flex';
      });
    });

    videos.forEach(video => {
      video.addEventListener('click', () => {
        // Warning: Original code put video src into img tag. 
        // If it works for the user, I leave it.
        lightboxImg.src = video.currentSrc || video.src; 
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