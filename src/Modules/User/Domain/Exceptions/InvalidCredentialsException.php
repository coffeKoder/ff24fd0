<?php

declare(strict_types=1);

namespace Viex\Modules\User\Domain\Exceptions;

use Exception;

/**
 * Excepción lanzada cuando las credenciales de autenticación son inválidas
 */
class InvalidCredentialsException extends Exception {
   public function __construct(string $message = 'Credenciales inválidas', int $code = 401, ?Exception $previous = null) {
      parent::__construct($message, $code, $previous);
   }
}
