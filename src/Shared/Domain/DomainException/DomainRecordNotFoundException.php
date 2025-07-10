<?php
/**
 * @package     Shared/Domain
 * @subpackage  DomainException
 * @file        DomainRecordNotFoundException
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 11:01:03
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Shared\Domain\DomainException;
use DomainException;

class DomainRecordNotFoundException extends DomainException {
   function __construct() {
      parent::__construct("El registro solicitado no fue encontrado.");
   }
}