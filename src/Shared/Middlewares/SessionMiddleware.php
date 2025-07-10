<?php
/**
 * @package     src/Shared
 * @subpackage  Middlewares
 * @file        SessionMiddleware
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 12:40:23
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Shared\Middlewares;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class SessionMiddleware implements Middleware {
   /**
    * {@inheritdoc}
    */
   public function process(Request $request, RequestHandler $handler): Response {
      if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
         session_start();
         $request = $request->withAttribute('session', $_SESSION);
      }

      return $handler->handle($request);
   }
}
