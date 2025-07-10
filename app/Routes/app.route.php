<?php
/**
 * @package     ff24fd0/app
 * @subpackage  Routes
 * @file        app.route
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 12:37:20
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
   $app->options('/{routes:.*}', function (Request $request, Response $response) {
      // CORS Pre-Flight OPTIONS Request Handler
      return $response;
   });

   $app->get('/', function (Request $request, Response $response) {
      $response->getBody()->write('Hello world!');
      return $response;
   });
};
