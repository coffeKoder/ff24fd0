<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando se intenta crear un usuario que ya existe
 */
class UserAlreadyExistsException extends Exception {
   public function __construct(string $message = 'Usuario ya existe', int $code = 409, ?Exception $previous = null) {
      parent::__construct($message, $code, $previous);
   }
}
