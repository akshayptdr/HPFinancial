<?php /* minimal layout for auth pages */ ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($pageTitle ?? 'HP Financial') ?> · HP Financial</title>
<link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
</head>
<body>
<?= $content ?>
</body>
</html>
