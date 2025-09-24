    <?php

    require 'vendor/autoload.php';

    require 'Lang/lang.php';

    $loader = new \Twig\Loader\FilesystemLoader('templates');
    $twig = new \Twig\Environment($loader, [
        'cache' => false,
        'debug' => true,

    ]);

    $twig->addExtension(new \Twig\Extension\DebugExtension());
    $twig->addFunction(new \Twig\TwigFunction('t', 't'));
    $template = $twig->load('planTournoi.twig');

    echo $template->render([
        'idTournoi' => $_GET['idTournoi'],
        
    ]);
