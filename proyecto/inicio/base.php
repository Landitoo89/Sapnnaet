<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Tilin Dashboard Pro' ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'gestion/sidebar.html'; ?>
    
    <main> 
        <?= $content ?? '' ?> </main>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="js/sidebar.js"></script>
    <?= $scripts ?? '' ?>
</body>
</html>
