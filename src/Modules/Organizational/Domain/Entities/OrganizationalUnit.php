<?php
/**
 * @package     Organizational/Domain
 * @subpackage  Entities
 * @file        Organization   public function isActive(): bool {
      return $this->isActive === 1;
   }

   public function getCreatedAt(): DateTimeImmutable {
      return $this->createdAt;
   }

   public function getUpdatedAt(): DateTimeImmutable {
      return $this->updatedAt;
   }

   public function isSoftDeleted(): bool {
      return $this->softDeleted === 1;
   }or      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 13:39:03
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Domain\Entities;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use DateTimeImmutable;
use Viex\Modules\Organizational\Domain\ValueObjects\UnitType;
use Viex\Modules\Organizational\Domain\ValueObjects\HierarchyPath;
use Viex\Modules\Organizational\Domain\Exceptions\InvalidHierarchyException;

#[ORM\Entity]
#[ORM\Table(name: 'organizational_units')]
#[ORM\HasLifecycleCallbacks]
class OrganizationalUnit {
   #[ORM\Id]
   #[ORM\GeneratedValue]
   #[ORM\Column(type: 'integer')]
   private int $id;

   #[ORM\Column(type: 'string', length: 255)]
   private string $name;

   #[ORM\Column(type: 'string', length: 50)]
   private string $type;

   #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children', fetch: 'LAZY')]
   #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
   private ?OrganizationalUnit $parent = null;

   /**
    * Relación One-to-Many con las unidades hijas
    */
   #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent', fetch: 'LAZY')]
   private Collection $children;
   #[ORM\Column(name: 'is_active', type: 'smallint', options: ['default' => 1])]
   private int $isActive = 1;

   #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
   private DateTimeImmutable $createdAt;

   #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
   private DateTimeImmutable $updatedAt;
   #[ORM\Column(name: 'soft_deleted', type: 'smallint', options: ['default' => 0])]
   private int $softDeleted = 0;

   /**
    * Constructor
    */
   public function __construct(
      string $name,
      string $type,
      ?OrganizationalUnit $parent = null
   ) {
      $this->name = $name;
      $this->type = $type;
      $this->parent = $parent;
      $this->children = new ArrayCollection();
      $this->createdAt = new DateTimeImmutable();
      $this->updatedAt = new DateTimeImmutable();

      // Validar que el tipo sea válido
      UnitType::create($type);
   }

   // Getters
   public function getId(): int {
      return $this->id;
   }

   public function getName(): string {
      return $this->name;
   }

   public function getType(): string {
      return $this->type;
   }

   public function getParent(): ?OrganizationalUnit {
      return $this->parent;
   }

   /**
    * @return Collection<int, OrganizationalUnit>
    */
   public function getChildren(): Collection {
      return $this->children;
   }

   public function isActive(): bool {
      return $this->isActive === 1;
   }

   public function getCreatedAt(): DateTimeImmutable {
      return $this->createdAt;
   }

   public function getUpdatedAt(): DateTimeImmutable {
      return $this->updatedAt;
   }

   public function isSoftDeleted(): bool {
      return $this->softDeleted === 1;
   }

   // Setters y métodos de negocio
   public function setName(string $name): void {
      $this->name = $name;
      $this->updateTimestamp();
   }

   public function setType(string $type): void {
      // Validar que el tipo sea válido
      UnitType::create($type);
      $this->type = $type;
      $this->updateTimestamp();
   }

   public function setParent(?OrganizationalUnit $parent): void {
      // Verificar que no se cree una referencia circular
      if ($parent !== null && $this->wouldCreateCircularReference($parent)) {
         throw InvalidHierarchyException::circularReference($this->name, $parent->getName());
      }

      $this->parent = $parent;
      $this->updateTimestamp();
   }

   public function activate(): void {
      $this->isActive = 1;
      $this->updateTimestamp();
   }

   public function deactivate(): void {
      $this->isActive = 0;
      $this->updateTimestamp();
   }

   /**
    * Soft delete de la unidad organizacional
    */
   public function delete(): void {
      $this->softDeleted = 1;
      $this->isActive = 0;
      $this->updateTimestamp();
   }

   /**
    * Restaurar unidad organizacional eliminada
    */
   public function restore(): void {
      $this->softDeleted = 0;
      $this->isActive = 1;
      $this->updateTimestamp();
   }

   /**
    * Agregar unidad hija
    */
   public function addChild(OrganizationalUnit $child): void {
      if (!$this->children->contains($child)) {
         $this->children->add($child);
         $child->setParent($this);
      }
   }

   /**
    * Remover unidad hija
    */
   public function removeChild(OrganizationalUnit $child): void {
      if ($this->children->contains($child)) {
         $this->children->removeElement($child);
         $child->setParent(null);
      }
   }

   /**
    * Verificar si la unidad tiene hijos
    */
   public function hasChildren(): bool {
      return !$this->children->isEmpty();
   }

   /**
    * Obtener la ruta jerárquica completa
    */
   public function getHierarchyPath(): string {
      $path = [$this->name];
      $current = $this->parent;

      while ($current !== null) {
         array_unshift($path, $current->getName());
         $current = $current->getParent();
      }

      return implode(' > ', $path);
   }

   /**
    * Obtener todos los ancestros
    * @return OrganizationalUnit[]
    */
   public function getAncestors(): array {
      $ancestors = [];
      $current = $this->parent;

      while ($current !== null) {
         $ancestors[] = $current;
         $current = $current->getParent();
      }

      return $ancestors;
   }

   /**
    * Obtener todos los descendientes (hijos, nietos, etc.)
    * @return OrganizationalUnit[]
    */
   public function getDescendants(): array {
      $descendants = [];

      foreach ($this->children as $child) {
         $descendants[] = $child;
         $descendants = array_merge($descendants, $child->getDescendants());
      }

      return $descendants;
   }

   /**
    * Obtener el nivel de profundidad en la jerarquía (0 = raíz)
    */
   public function getDepthLevel(): int {
      $level = 0;
      $current = $this->parent;

      while ($current !== null) {
         $level++;
         $current = $current->getParent();
      }

      return $level;
   }

   /**
    * Verificar si esta unidad es ancestro de otra
    */
   public function isAncestorOf(OrganizationalUnit $unit): bool {
      $current = $unit->getParent();

      while ($current !== null) {
         if ($current->getId() === $this->getId()) {
            return true;
         }
         $current = $current->getParent();
      }

      return false;
   }

   /**
    * Verificar si esta unidad es descendiente de otra
    */
   public function isDescendantOf(OrganizationalUnit $unit): bool {
      return $unit->isAncestorOf($this);
   }

   /**
    * Verificar si asignar un padre crearía una referencia circular
    */
   private function wouldCreateCircularReference(OrganizationalUnit $potentialParent): bool {
      // Si el potencial padre es esta misma unidad
      if ($potentialParent->getId() === $this->getId()) {
         return true;
      }

      // Si esta unidad es ancestro del potencial padre
      return $this->isAncestorOf($potentialParent);
   }

   /**
    * Lifecycle callback - actualizar timestamp antes de persistir
    */
   #[ORM\PreUpdate]
   public function updateTimestamp(): void {
      $this->updatedAt = new DateTimeImmutable();
   }

   // Métodos que utilizan Value Objects

   /**
    * Obtener el tipo como Value Object
    */
   public function getUnitType(): UnitType {
      return UnitType::create($this->type);
   }

   /**
    * Obtener la ruta jerárquica como Value Object
    */
   public function getHierarchyPathVO(): HierarchyPath {
      return HierarchyPath::create($this->getHierarchyPath());
   }

   /**
    * Verificar si es de un tipo específico usando Value Object
    */
   public function isOfType(UnitType $unitType): bool {
      return $this->getUnitType()->equals($unitType);
   }

   /**
    * Verificar si es una unidad académica
    */
   public function isAcademicUnit(): bool {
      return $this->getUnitType()->isAcademicUnit();
   }

   /**
    * Verificar si es una unidad administrativa
    */
   public function isAdministrativeUnit(): bool {
      return $this->getUnitType()->isAdministrativeUnit();
   }

   /**
    * Verificar si es una unidad de enseñanza
    */
   public function isTeachingUnit(): bool {
      return $this->getUnitType()->isTeachingUnit();
   }

   /**
    * Crear una unidad hijo con validación de jerarquía
    */
   public function createChild(string $name, string $type): OrganizationalUnit {
      $childType = UnitType::create($type);
      $parentType = $this->getUnitType();

      // Validar que la jerarquía sea válida según las reglas de negocio
      $this->validateChildType($parentType, $childType);

      $child = new OrganizationalUnit($name, $type, $this);
      $this->addChild($child);

      return $child;
   }

   /**
    * Validar que un tipo de unidad puede ser hijo de otro
    */
   private function validateChildType(UnitType $parentType, UnitType $childType): void {
      // Reglas de jerarquía universitaria
      $validHierarchies = [
         UnitType::SEDE => [UnitType::FACULTAD, UnitType::CENTRO_REGIONAL, UnitType::INSTITUTO],
         UnitType::FACULTAD => [UnitType::DEPARTAMENTO, UnitType::ESCUELA, UnitType::DIRECCION],
         UnitType::CENTRO_REGIONAL => [UnitType::DEPARTAMENTO, UnitType::ESCUELA, UnitType::COORDINACION],
         UnitType::INSTITUTO => [UnitType::DEPARTAMENTO, UnitType::DIVISION, UnitType::CENTRO],
         UnitType::DEPARTAMENTO => [UnitType::COORDINACION, UnitType::CENTRO],
         UnitType::ESCUELA => [UnitType::COORDINACION, UnitType::CENTRO],
         UnitType::DIRECCION => [UnitType::COORDINACION, UnitType::DIVISION],
      ];

      $parentValue = $parentType->getValue();
      $childValue = $childType->getValue();

      if (!isset($validHierarchies[$parentValue]) ||
         !in_array($childValue, $validHierarchies[$parentValue], true)) {
         throw InvalidHierarchyException::invalidParentType($childValue, $parentValue);
      }
   }

   /**
    * Validar la profundidad máxima de la jerarquía
    */
   private function validateMaxDepth(int $maxDepth = 6): void {
      $currentDepth = $this->getDepthLevel();

      if ($currentDepth >= $maxDepth) {
         throw InvalidHierarchyException::maxDepthExceeded($maxDepth, $currentDepth);
      }
   }

   /**
    * Obtener información resumida de la unidad
    */
   public function getSummary(): array {
      return [
         'id' => $this->getId(),
         'name' => $this->getName(),
         'type' => $this->getType(),
         'hierarchy_path' => $this->getHierarchyPath(),
         'depth_level' => $this->getDepthLevel(),
         'children_count' => $this->children->count(),
         'is_active' => $this->isActive(),
         'is_academic_unit' => $this->isAcademicUnit(),
         'is_administrative_unit' => $this->isAdministrativeUnit(),
         'is_teaching_unit' => $this->isTeachingUnit(),
         'created_at' => $this->getCreatedAt()->format('Y-m-d H:i:s'),
         'updated_at' => $this->getUpdatedAt()->format('Y-m-d H:i:s'),
      ];
   }
}