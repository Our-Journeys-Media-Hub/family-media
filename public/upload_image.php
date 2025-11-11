<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$countryCodes = require __DIR__ . '/countryNames.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$pdo = db_connect();
$user = current_user();

// Retrieve the user's families
$stmt = $pdo->prepare("SELECT f.id, f.family_name FROM families f
    JOIN family_memberships m ON f.id = m.family_id
    WHERE m.user_id = ?");
$stmt->execute([$user['id']]);
$families = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load the list of countries (ISO → Full name)
$countryList = require __DIR__ . '/countryNames.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $country = $_POST['country'] ?? '';
    $family_id = (int)($_POST['family'] ?? 0);
    $visibility = $_POST['visibility'] ?? 'family';
    $custom_users = $_POST['custom_users'] ?? '';

    // Check if any files were uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'][0] === 0) {
        $uploadDir = __DIR__ . '/../images/' . $country . '/';

        // Create the directory if it doesn't exist, with permissions 0777
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                die("❌ Failed to create directory: $uploadDir");
            }
        }

        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            die("❌ Directory $uploadDir is not writable by PHP");
        }

        // Loop through each file uploaded
        foreach ($_FILES['image']['name'] as $index => $fileName) {
            $filename = basename($fileName);
            $targetFile = $uploadDir . $filename;

            // Move the uploaded file to the target directory
            if (move_uploaded_file($_FILES['image']['tmp_name'][$index], $targetFile)) {
                $filePath = "images/$country/$filename";

                // Insert image into the `images` table
                $stmt = $pdo->prepare("
                    INSERT INTO images (family_id, uploaded_by, country_code, title, file_path, visibility)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$family_id, $user['id'], $country, $title, $filePath, $visibility]);
                $imageId = $pdo->lastInsertId();

                // If visibility is 'custom', insert permissions into image_permissions
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
                echo "❌ Error while uploading the image: $fileName. Please check folder permissions.";
            }
        }

        echo "✅ Images uploaded successfully!";
    } else {
        echo "❌ No files selected or upload error.";
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Upload Image</title>
</head>
<body>
<h1>Upload Image</h1>
<form action="upload_image.php" method="post" enctype="multipart/form-data">
    <label for="title">Title:</label>
    <input type="text" name="title" id="title" required><br>

    <label for="country">Country:</label>
    <select name="country" id="country" required>
        <?php foreach ($countryList as $code => $name): ?>
            <option value="<?= $code ?>"><?= htmlspecialchars($name) ?></option>
        <?php endforeach; ?>
    </select><br>

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

    <label for="image">Files (multiple files allowed, JPG, PNG, GIF, MP4, MOV):</label>
    <input type="file" name="image[]" id="image" accept="image/*,video/*" multiple required><br><br>

    <button type="submit">Upload</button>
</form>
</body>
</html>
