<?php
/**
 * @package     Shared/Application
 * @subpackage  Actions
 * @file        ActionPayload
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 11:03:13
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Shared\Application\Actions;
use JsonSerializable;
use Viex\Shared\Application\Actions\ActionError;

class ActionPayload implements JsonSerializable {
   private int $statusCode;

   /**
    * @var array|object|null
    */
   private $data;

   private ?ActionError $error;

   public function __construct(
      int $statusCode = 200,
      $data = null,
      ?ActionError $error = null
   ) {
      $this->statusCode = $statusCode;
      $this->data = $data;
      $this->error = $error;
   }

   public function getStatusCode(): int {
      return $this->statusCode;
   }

   /**
    * @return array|null|object
    */
   public function getData() {
      return $this->data;
   }

   public function getError(): ?ActionError {
      return $this->error;
   }

   #[\ReturnTypeWillChange]
   public function jsonSerialize(): array {
      $payload = [
         'statusCode' => $this->statusCode,
      ];

      if ($this->data !== null) {
         $payload['data'] = $this->data;
      } elseif ($this->error !== null) {
         $payload['error'] = $this->error;
      }

      return $payload;
   }

}