<?php /* bare layout for printable documents (no sidebar / header) */ ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= e($pageTitle ?? 'Print') ?></title>
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
</head>
<body style="background:#fff">
<?= $content ?>
</body>
</html>
