<?php
require_once __DIR__ . '/../init.php';
?>

<!DOCTYPE html>
<html lang="<?=  e(current_lang()) ?>">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <base href="/">
        <title><?=  e(t('home.title')) ?> </title>
        <link rel="stylesheet" href="../dashboard/home.css">
        <link rel="stylesheet" href="../navebar/navebar.css">
        <link rel="stylesheet" href="../footer/footer.css">
    </head>

    <body>
        <?php include __DIR__ . '/../navebar/navebar.php'; ?>

        <section class="hero">
            <div class="infor">
                <h1>Nexory.Org</h1>
                <p>Open source projects - 5+ years of experience - Python, JavaScript, PHP</p>
            </div>
            <div id="editor" style="height: 400px; width: 600px;"></div>
        </section>

        <?php include __DIR__ . '/../footer/footer.php'; ?>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.38.0/min/vs/loader.min.js"></script>
        <script src="../dashboard/home.js"></script>
    </body>

</html>