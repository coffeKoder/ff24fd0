<?php
/**
 * @package     Organizational/Domain
 * @subpackage  Exceptions
 * @file        UnitNotFoundException
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 14:35:27
 * @version     1.0.0
 * @description Excepción para unidades organizacionales no encontradas
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Domain\Exceptions;

use Exception;

class UnitNotFoundException extends Exception {
   public static function withId(int $id): self {
      return new self(
         sprintf('No se encontró la unidad organizacional con ID: %d', $id)
      );
   }

   public static function withName(string $name): self {
      return new self(
         sprintf('No se encontró la unidad organizacional con nombre: "%s"', $name)
      );
   }

   public static function withType(string $type): self {
      return new self(
         sprintf('No se encontraron unidades organizacionales de tipo: "%s"', $type)
      );
   }

   public static function withNameAndType(string $name, string $type): self {
      return new self(
         sprintf(
            'No se encontró la unidad organizacional con nombre "%s" y tipo "%s"',
            $name,
            $type
         )
      );
   }

   public static function withParent(int $parentId): self {
      return new self(
         sprintf('No se encontraron unidades organizacionales con padre ID: %d', $parentId)
      );
   }

   public static function withCriteria(array $criteria): self {
      $criteriaString = implode(', ', array_map(
         fn($key, $value) => sprintf('%s: %s', $key, $value),
         array_keys($criteria),
         array_values($criteria)
      ));

      return new self(
         sprintf('No se encontraron unidades organizacionales que cumplan con los criterios: %s', $criteriaString)
      );
   }

   public static function inactiveUnit(int $id): self {
      return new self(
         sprintf('La unidad organizacional con ID %d existe pero está inactiva', $id)
      );
   }

   public static function deletedUnit(int $id): self {
      return new self(
         sprintf('La unidad organizacional con ID %d ha sido eliminada', $id)
      );
   }

   public static function noRootUnits(): self {
      return new self('No se encontraron unidades organizacionales raíz en el sistema');
   }

   public static function noActiveUnits(): self {
      return new self('No se encontraron unidades organizacionales activas en el sistema');
   }

   public static function emptyHierarchy(): self {
      return new self('La jerarquía organizacional está vacía');
   }

   public static function unitNotInHierarchy(int $id, string $hierarchyPath): self {
      return new self(
         sprintf(
            'La unidad con ID %d no pertenece a la jerarquía "%s"',
            $id,
            $hierarchyPath
         )
      );
   }

   public static function invalidUnitAccess(int $unitId, int $userId): self {
      return new self(
         sprintf(
            'El usuario con ID %d no tiene acceso a la unidad organizacional con ID %d',
            $userId,
            $unitId
         )
      );
   }
}
