<?php require_once __DIR__ . '/functions.php'; $pageTitle = $pageTitle ?? $SITE['name']; ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($SITE['tagline']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>
<div class="stars"></div>
<header class="topbar">
  <a href="index.php" class="logo">🔮 <?= e($SITE['name']) ?></a>
  <nav>
    <a href="index.php#play">เปิดไพ่ฟรี</a>
    <a href="index.php#packages">แพ็คเกจ</a>
    <a href="index.php#how">ขั้นตอน</a>
    <a href="booking.php" class="btn btn-small">สั่งดูดวง</a>
  </nav>
</header>
