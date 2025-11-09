<?php
$countryName = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : 'Unknown Country';


$countryFolder = $countryName; 
$imagePath = __DIR__ . "/../images/$countryFolder/";


$images = [];
if (is_dir($imagePath)) {
    $files = scandir($imagePath);
    foreach ($files as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
            $images[] = "/family-media/public/images/$countryFolder/$file";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Country Details - <?php echo $countryName; ?></title>
  <link rel="stylesheet" href="/family-media/public/css/app.css">
  <style>
    .images-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 20px;
    }
    .images-grid img {
      width: 100%;
      border-radius: 8px;
      object-fit: cover;
    }
  </style>
</head>
<body>
  <h1>Country: <?php echo $countryName; ?></h1>
  <a href="/family-media/public/index.php">Back to Map</a>

  <?php if(!empty($images)): ?>
    <div class="images-grid">
      <?php foreach($images as $img): ?>
        <img src="<?php echo $img; ?>" alt="<?php echo $countryName; ?>">
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>No images available for this country.</p>
  <?php endif; ?>
</body>
</html>
