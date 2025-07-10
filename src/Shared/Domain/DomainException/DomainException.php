<?php
/**
 * @package     Shared/Domain
 * @subpackage  DomainException
 * @file        DomainException
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 10:59:39
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Shared\Domain\DomainException;
use \Exception;
class DomainException extends Exception {
   protected $message = 'Domain Exception:: ';
   protected $code = 422;

   public function __construct(string $message = '', int $code = 0, ?Exception $previous = null) {
      if ($message) {
         $this->message .= $message;
      }
      parent::__construct($this->message, $code, $previous);
   }

}