<?php
/**
 * @package     ViexUP/ff24fd0
 * @subpackage  public
 * @file        index
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 10:50:38
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap\Application;

$app = new Application();

$app->addMiddleware(Viex\Shared\Middlewares\SessionMiddleware::class);


\Kint\Kint::dump($app);

$app->run();
