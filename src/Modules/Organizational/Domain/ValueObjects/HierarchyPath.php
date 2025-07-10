<?php
/**
 * @package     Organizational/Domain
 * @subpackage  ValueObjects
 * @file        HierarchyPath
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 14:35:27
 * @version     1.0.0
 * @description define la Ruta jerárquica completa
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Domain\ValueObjects;

use InvalidArgumentException;

class HierarchyPath {
   private const SEPARATOR = ' > ';
   private const MIN_LENGTH = 1;
   private const MAX_LENGTH = 1000;

   private string $value;
   private array $segments;

   private function __construct(string $value) {
      $this->validatePath($value);
      $this->value = $value;
      $this->segments = $this->parseSegments($value);
   }

   public static function create(string $value): self {
      return new self($value);
   }

   public static function fromArray(array $segments): self {
      if (empty($segments)) {
         throw new InvalidArgumentException('Los segmentos de la ruta no pueden estar vacíos');
      }

      $validSegments = array_filter($segments, fn($segment) =>
         is_string($segment) && trim($segment) !== ''
      );

      if (count($validSegments) !== count($segments)) {
         throw new InvalidArgumentException('Todos los segmentos deben ser cadenas no vacías');
      }

      return new self(implode(self::SEPARATOR, $validSegments));
   }

   public static function root(string $rootName): self {
      return new self($rootName);
   }

   public function getValue(): string {
      return $this->value;
   }

   /**
    * Obtener los segmentos de la ruta
    * 
    * @return string[]
    */
   public function getSegments(): array {
      return $this->segments;
   }

   /**
    * Obtener el nombre de la unidad raíz
    */
   public function getRoot(): string {
      return $this->segments[0] ?? '';
   }

   /**
    * Obtener el nombre de la unidad hoja (último segmento)
    */
   public function getLeaf(): string {
      return end($this->segments) ?: '';
   }

   /**
    * Obtener el nivel de profundidad (número de segmentos)
    */
   public function getDepth(): int {
      return count($this->segments);
   }

   /**
    * Verificar si esta ruta es ancestro de otra
    */
   public function isAncestorOf(HierarchyPath $other): bool {
      $thisSegments = $this->segments;
      $otherSegments = $other->segments;

      // No puede ser ancestro si tiene igual o mayor profundidad
      if (count($thisSegments) >= count($otherSegments)) {
         return false;
      }

      // Verificar que todos los segmentos coincidan
      for ($i = 0; $i < count($thisSegments); $i++) {
         if ($thisSegments[$i] !== $otherSegments[$i]) {
            return false;
         }
      }

      return true;
   }

   /**
    * Verificar si esta ruta es descendiente de otra
    */
   public function isDescendantOf(HierarchyPath $other): bool {
      return $other->isAncestorOf($this);
   }

   /**
    * Verificar si esta ruta es hermana de otra (mismo padre)
    */
   public function isSiblingOf(HierarchyPath $other): bool {
      $thisParent = $this->getParentPath();
      $otherParent = $other->getParentPath();

      if ($thisParent === null || $otherParent === null) {
         return false;
      }

      return $thisParent->equals($otherParent) && !$this->equals($other);
   }

   /**
    * Obtener la ruta del padre
    */
   public function getParentPath(): ?HierarchyPath {
      if (count($this->segments) <= 1) {
         return null;
      }

      $parentSegments = array_slice($this->segments, 0, -1);
      return self::fromArray($parentSegments);
   }

   /**
    * Crear una nueva ruta agregando un segmento hijo
    */
   public function appendChild(string $childName): HierarchyPath {
      $childName = trim($childName);
      if ($childName === '') {
         throw new InvalidArgumentException('El nombre del hijo no puede estar vacío');
      }

      $newSegments = array_merge($this->segments, [$childName]);
      return self::fromArray($newSegments);
   }

   /**
    * Verificar si contiene un segmento específico
    */
   public function contains(string $segment): bool {
      return in_array($segment, $this->segments, true);
   }

   /**
    * Verificar si comienza con una ruta específica
    */
   public function startsWith(HierarchyPath $other): bool {
      $otherSegments = $other->segments;

      if (count($otherSegments) > count($this->segments)) {
         return false;
      }

      for ($i = 0; $i < count($otherSegments); $i++) {
         if ($this->segments[$i] !== $otherSegments[$i]) {
            return false;
         }
      }

      return true;
   }

   /**
    * Obtener una sub-ruta desde un índice específico
    */
   public function getSubPath(int $startIndex, ?int $length = null): HierarchyPath {
      if ($startIndex < 0 || $startIndex >= count($this->segments)) {
         throw new InvalidArgumentException('Índice de inicio fuera de rango');
      }

      $subSegments = array_slice($this->segments, $startIndex, $length);

      if (empty($subSegments)) {
         throw new InvalidArgumentException('No se pueden crear rutas vacías');
      }

      return self::fromArray($subSegments);
   }

   public function equals(HierarchyPath $other): bool {
      return $this->value === $other->value;
   }

   public function __toString(): string {
      return $this->value;
   }

   private function validatePath(string $path): void {
      $trimmedPath = trim($path);

      if (strlen($trimmedPath) < self::MIN_LENGTH) {
         throw new InvalidArgumentException('La ruta jerárquica no puede estar vacía');
      }

      if (strlen($trimmedPath) > self::MAX_LENGTH) {
         throw new InvalidArgumentException(
            sprintf('La ruta jerárquica no puede exceder %d caracteres', self::MAX_LENGTH)
         );
      }
   }

   private function parseSegments(string $path): array {
      $segments = explode(self::SEPARATOR, $path);

      // Limpiar y validar cada segmento
      $cleanSegments = [];
      foreach ($segments as $segment) {
         $trimmed = trim($segment);
         if ($trimmed === '') {
            throw new InvalidArgumentException('Los segmentos de la ruta no pueden estar vacíos');
         }
         $cleanSegments[] = $trimmed;
      }

      return $cleanSegments;
   }
}