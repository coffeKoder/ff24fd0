<?php
/**
 * @package     Shared/Application
 * @subpackage  Actions
 * @file        Action
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 11:04:45
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Shared\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Viex\Shared\Domain\DomainException\DomainRecordNotFoundException;


abstract class Action {
   protected LoggerInterface $logger;

   protected Request $request;

   protected Response $response;

   protected array $args;

   public function __construct(LoggerInterface $logger) {
      $this->logger = $logger;
   }

   /**
    * @throws HttpNotFoundException
    * @throws HttpBadRequestException
    */
   public function __invoke(Request $request, Response $response, array $args): Response {
      $this->request = $request;
      $this->response = $response;
      $this->args = $args;

      try {
         return $this->action();
      } catch (DomainRecordNotFoundException $e) {
         throw new HttpNotFoundException($this->request, $e->getMessage());
      }
   }

   /**
    * @throws DomainRecordNotFoundException
    * @throws HttpBadRequestException
    */
   abstract protected function action(): Response;

   /**
    * @return array|object
    */
   protected function getFormData() {
      return $this->request->getParsedBody();
   }

   /**
    * @return mixed
    * @throws HttpBadRequestException
    */
   protected function resolveArg(string $name) {
      if (!isset($this->args[$name])) {
         throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
      }

      return $this->args[$name];
   }

   /**
    * @param array|object|null $data
    */
   protected function respondWithData($data = null, int $statusCode = 200): Response {
      $payload = new ActionPayload($statusCode, $data);

      return $this->respond($payload);
   }

   protected function respond(ActionPayload $payload): Response {
      $json = json_encode($payload, JSON_PRETTY_PRINT);
      $this->response->getBody()->write($json);

      return $this->response
         ->withHeader('Content-Type', 'application/json')
         ->withStatus($payload->getStatusCode());
   }
}