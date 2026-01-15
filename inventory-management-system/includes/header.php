<?php
// Header file - can be used for common header across pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Inventory System'; ?></title>
    <link rel="stylesheet" href="<?php echo isset($base_path) ? $base_path : '../'; ?>assets/css/style.css">
</head>
<body>