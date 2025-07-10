<?php
/**
 * @package     Organizational/Domain
 * @subpackage  Exceptions
 * @file        InvalidHierarchyException
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 14:35:27
 * @version     1.0.0
 * @description Excepción para estructuras jerárquicas inválidas
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Domain\Exceptions;

use Exception;

class InvalidHierarchyException extends Exception {
   public static function circularReference(string $unitName, string $parentName): self {
      return new self(
         sprintf(
            'Error de referencia circular: La unidad "%s" no puede ser hija de "%s" porque crearía un ciclo en la jerarquía',
            $unitName,
            $parentName
         )
      );
   }

   public static function selfReference(string $unitName): self {
      return new self(
         sprintf(
            'Error de auto-referencia: La unidad "%s" no puede ser su propio padre',
            $unitName
         )
      );
   }

   public static function invalidParentType(string $childType, string $parentType): self {
      return new self(
         sprintf(
            'Estructura jerárquica inválida: Una unidad de tipo "%s" no puede ser hija de una unidad de tipo "%s"',
            $childType,
            $parentType
         )
      );
   }

   public static function maxDepthExceeded(int $maxDepth, int $currentDepth): self {
      return new self(
         sprintf(
            'La profundidad máxima de jerarquía (%d) ha sido excedida. Profundidad actual: %d',
            $maxDepth,
            $currentDepth
         )
      );
   }

   public static function invalidHierarchyStructure(string $reason): self {
      return new self(
         sprintf('Estructura jerárquica inválida: %s', $reason)
      );
   }

   public static function cannotDeleteUnitWithChildren(string $unitName, int $childrenCount): self {
      return new self(
         sprintf(
            'No se puede eliminar la unidad "%s" porque tiene %d unidades hijas. Elimine o mueva primero las unidades hijas',
            $unitName,
            $childrenCount
         )
      );
   }

   public static function cannotMoveUnitWithDependencies(string $unitName, string $dependencyType): self {
      return new self(
         sprintf(
            'No se puede mover la unidad "%s" porque tiene dependencias activas de tipo "%s"',
            $unitName,
            $dependencyType
         )
      );
   }

   public static function inconsistentHierarchyData(string $details): self {
      return new self(
         sprintf('Datos jerárquicos inconsistentes: %s', $details)
      );
   }

   public static function duplicateUnitInHierarchy(string $unitName, string $hierarchyPath): self {
      return new self(
         sprintf(
            'La unidad "%s" ya existe en la ruta jerárquica "%s"',
            $unitName,
            $hierarchyPath
         )
      );
   }

   public static function orphanedUnit(string $unitName, int $parentId): self {
      return new self(
         sprintf(
            'La unidad "%s" está huérfana. Su padre con ID %d no existe o está inactivo',
            $unitName,
            $parentId
         )
      );
   }

   public static function unitCannotBeDeleted(int $unitId, string $reason): self {
      return new self(
         sprintf(
            'La unidad con ID %d no puede ser eliminada: %s',
            $unitId,
            $reason
         )
      );
   }
}
