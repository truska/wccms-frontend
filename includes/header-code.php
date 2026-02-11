<?php
require_once __DIR__ . '/../../private/dbcon.php';
require_once __DIR__ . '/prefs.php';
require_once __DIR__ . '/lib/cms_log.php';

$baseURL = cms_base_url();
$pageTitle = $pageTitle ?? 'ITFix - Managed IT Services';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo cms_h($pageTitle); ?></title>
  <?php if (!empty($pageMetaDescription)): ?>
    <meta name="description" content="<?php echo cms_h($pageMetaDescription); ?>">
  <?php endif; ?>
  <?php if (!empty($pageMetaKeywords)): ?>
    <meta name="keywords" content="<?php echo cms_h($pageMetaKeywords); ?>">
  <?php endif; ?>
  <?php
    $metadataPath = __DIR__ . '/metadata.php';
    if (file_exists($metadataPath)) {
      include $metadataPath;
    }
    $googlePath = __DIR__ . '/google.php';
    if (file_exists($googlePath)) {
      include $googlePath;
    }
  ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Crimson+Pro:wght@400;600;700&family=Work+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" integrity="sha512-ypPIy8wSRHn2yT7nL2R4AR6j2B2cPNCEQkI6Q5r92eUhc6Oe3wM3wN1s0h+J5S3E6fM6D6KhGxZxJ5mFq4Y8Kg==" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="stylesheet" href="<?php echo $baseURL; ?>/css/site.css">
  <?php
    // Optional per-site head assets/scripts (e.g. MagicZoom, MagicScroll, one-off integrations).
    $customHeadPath = __DIR__ . '/custom-head.php';
    if (file_exists($customHeadPath)) {
      include $customHeadPath;
    }
  ?>
  <script src="https://kit.fontawesome.com/3e4371248d.js" crossorigin="anonymous"></script>
</head>
<body>
