<?php
/**
 * @package     Organizational/Domain
 * @subpackage  ValueObjects
 * @file        UnitType
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 14:34:54
 * @version     1.0.0
 * @description define el tipo de unidad organizacional Tipos de unidades (Sede, Facultad, etc.)
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Domain\ValueObjects;

use InvalidArgumentException;

class UnitType {
   // Constantes para tipos válidos según la estructura universitaria
   public const SEDE = 'Sede';
   public const FACULTAD = 'Facultad';
   public const CENTRO_REGIONAL = 'Centro Regional';
   public const INSTITUTO = 'Instituto';
   public const DEPARTAMENTO = 'Departamento';
   public const ESCUELA = 'Escuela';
   public const DIRECCION = 'Direccion';
   public const COORDINACION = 'Coordinacion';
   public const DIVISION = 'Division';
   public const CENTRO = 'Centro';

   private const VALID_TYPES = [
      self::SEDE,
      self::FACULTAD,
      self::CENTRO_REGIONAL,
      self::INSTITUTO,
      self::DEPARTAMENTO,
      self::ESCUELA,
      self::DIRECCION,
      self::COORDINACION,
      self::DIVISION,
      self::CENTRO
   ];

   private string $value;

   private function __construct(string $value) {
      $this->value = $value;
   }

   public static function create(string $value): self {
      if (!in_array($value, self::VALID_TYPES, true)) {
         throw new InvalidArgumentException(
            sprintf('El tipo de unidad "%s" no es válido. Tipos válidos: %s',
               $value,
               implode(', ', self::VALID_TYPES)
            )
         );
      }

      return new self($value);
   }

   public static function sede(): self {
      return new self(self::SEDE);
   }

   public static function facultad(): self {
      return new self(self::FACULTAD);
   }

   public static function centroRegional(): self {
      return new self(self::CENTRO_REGIONAL);
   }

   public static function instituto(): self {
      return new self(self::INSTITUTO);
   }

   public static function departamento(): self {
      return new self(self::DEPARTAMENTO);
   }

   public static function escuela(): self {
      return new self(self::ESCUELA);
   }

   public static function direccion(): self {
      return new self(self::DIRECCION);
   }

   public static function coordinacion(): self {
      return new self(self::COORDINACION);
   }

   public static function division(): self {
      return new self(self::DIVISION);
   }

   public static function centro(): self {
      return new self(self::CENTRO);
   }

   public function getValue(): string {
      return $this->value;
   }

   public function equals(UnitType $other): bool {
      return $this->value === $other->value;
   }

   public function isSede(): bool {
      return $this->value === self::SEDE;
   }

   public function isFacultad(): bool {
      return $this->value === self::FACULTAD;
   }

   public function isCentroRegional(): bool {
      return $this->value === self::CENTRO_REGIONAL;
   }

   public function isInstituto(): bool {
      return $this->value === self::INSTITUTO;
   }

   public function isDepartamento(): bool {
      return $this->value === self::DEPARTAMENTO;
   }

   public function isEscuela(): bool {
      return $this->value === self::ESCUELA;
   }

   public function isDireccion(): bool {
      return $this->value === self::DIRECCION;
   }

   public function isCoordinacion(): bool {
      return $this->value === self::COORDINACION;
   }

   public function isDivision(): bool {
      return $this->value === self::DIVISION;
   }

   public function isCentro(): bool {
      return $this->value === self::CENTRO;
   }

   /**
    * Obtener todos los tipos válidos
    * 
    * @return string[]
    */
   public static function getValidTypes(): array {
      return self::VALID_TYPES;
   }

   /**
    * Verificar si es un tipo de unidad académica principal
    */
   public function isAcademicUnit(): bool {
      return in_array($this->value, [
         self::FACULTAD,
         self::CENTRO_REGIONAL,
         self::INSTITUTO
      ], true);
   }

   /**
    * Verificar si es un tipo de unidad administrativa
    */
   public function isAdministrativeUnit(): bool {
      return in_array($this->value, [
         self::DIRECCION,
         self::COORDINACION,
         self::DIVISION
      ], true);
   }

   /**
    * Verificar si es un tipo de unidad de enseñanza
    */
   public function isTeachingUnit(): bool {
      return in_array($this->value, [
         self::DEPARTAMENTO,
         self::ESCUELA
      ], true);
   }

   public function __toString(): string {
      return $this->value;
   }
}