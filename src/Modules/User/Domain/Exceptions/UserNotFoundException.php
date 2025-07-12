<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando un usuario no es encontrado
 */
class UserNotFoundException extends Exception {
   public function __construct(string $message = 'Usuario no encontrado', int $code = 404, ?Exception $previous = null) {
      parent::__construct($message, $code, $previous);
   }
}
