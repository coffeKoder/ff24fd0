<?php
/**
 * @package     Shared/Application
 * @subpackage  Handlers
 * @file        ShutdownHandler
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 11:06:59
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Shared\Application\Handlers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpInternalServerErrorException;
use Viex\Shared\Application\ResponseEmitter\ResponseEmitter;

class ShutdownHandler {
   private Request $request;

   private HttpErrorHandler $errorHandler;

   private bool $displayErrorDetails;

   public function __construct(
      Request $request,
      HttpErrorHandler $errorHandler,
      bool $displayErrorDetails
   ) {
      $this->request = $request;
      $this->errorHandler = $errorHandler;
      $this->displayErrorDetails = $displayErrorDetails;
   }

   public function __invoke() {
      $error = error_get_last();
      if ($error) {
         $errorFile = $error['file'];
         $errorLine = $error['line'];
         $errorMessage = $error['message'];
         $errorType = $error['type'];
         $message = 'Ocurrió un error al procesar su solicitud. Por favor, inténtelo de nuevo más tarde.';

         if ($this->displayErrorDetails) {
            switch ($errorType) {
               case E_USER_ERROR:
                  $message = "FATAL ERROR: {$errorMessage}. ";
                  $message .= " on line {$errorLine} in file {$errorFile}.";
                  break;

               case E_USER_WARNING:
                  $message = "WARNING: {$errorMessage}";
                  break;

               case E_USER_NOTICE:
                  $message = "NOTICE: {$errorMessage}";
                  break;

               default:
                  $message = "ERROR: {$errorMessage}";
                  $message .= " on line {$errorLine} in file {$errorFile}.";
                  break;
            }
         }

         $exception = new HttpInternalServerErrorException($this->request, $message);
         $response = $this->errorHandler->__invoke(
            $this->request,
            $exception,
            $this->displayErrorDetails,
            false,
            false,
         );

         $responseEmitter = new ResponseEmitter();
         $responseEmitter->emit($response);
      }
   }
}