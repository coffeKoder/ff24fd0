<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando se intenta autenticar un usuario inactivo
 */
class InactiveUserException extends Exception {
   public function __construct(string $message = 'Usuario inactivo', int $code = 403, ?Exception $previous = null) {
      parent::__construct($message, $code, $previous);
   }
}
