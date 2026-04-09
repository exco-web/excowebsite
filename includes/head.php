    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - Expert Consulting' : 'Expert Consulting' ?></title>
        <link rel="stylesheet" href="<?= BASE_URL ?>/styles.css?v=<?= filemtime(__DIR__ . '/../styles.css') ?>"> <!-- TODO: remove version query string before production -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.14.0/css/all.css" integrity="sha384-HzLeBuhoNPvSl5KYnjx0BT+WB0QEEqLprO+NBkkk5gbc67FTaL7XIGa2w1L0Xbgc" crossorigin="anonymous"/>
    </head>
