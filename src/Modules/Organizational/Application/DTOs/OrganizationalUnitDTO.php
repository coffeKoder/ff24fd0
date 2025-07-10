<?php
/**
 * @package     Organizational/Application
 * @subpackage  DTOs
 * @file        OrganizationalUnitDTO
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 15:30:00
 * @version     1.0.0
 * @description DTO para transferir datos de unidades organizacionales
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\DTOs;

use DateTimeImmutable;

class OrganizationalUnitDTO {
   private int $id;
   private string $name;
   private string $type;
   private ?int $parentId;
   private ?string $parentName;
   private string $hierarchyPath;
   private int $depthLevel;
   private int $childrenCount;
   private bool $isActive;
   private bool $isAcademicUnit;
   private bool $isAdministrativeUnit;
   private bool $isTeachingUnit;
   private DateTimeImmutable $createdAt;
   private DateTimeImmutable $updatedAt;
   private array $children;

   public function __construct(
      int $id,
      string $name,
      string $type,
      ?int $parentId = null,
      ?string $parentName = null,
      string $hierarchyPath = '',
      int $depthLevel = 0,
      int $childrenCount = 0,
      bool $isActive = true,
      bool $isAcademicUnit = false,
      bool $isAdministrativeUnit = false,
      bool $isTeachingUnit = false,
      ?DateTimeImmutable $createdAt = null,
      ?DateTimeImmutable $updatedAt = null,
      array $children = []
   ) {
      $this->id = $id;
      $this->name = $name;
      $this->type = $type;
      $this->parentId = $parentId;
      $this->parentName = $parentName;
      $this->hierarchyPath = $hierarchyPath;
      $this->depthLevel = $depthLevel;
      $this->childrenCount = $childrenCount;
      $this->isActive = $isActive;
      $this->isAcademicUnit = $isAcademicUnit;
      $this->isAdministrativeUnit = $isAdministrativeUnit;
      $this->isTeachingUnit = $isTeachingUnit;
      $this->createdAt = $createdAt ?? new DateTimeImmutable();
      $this->updatedAt = $updatedAt ?? new DateTimeImmutable();
      $this->children = $children;
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
   public function getParentId(): ?int {
      return $this->parentId;
   }
   public function getParentName(): ?string {
      return $this->parentName;
   }
   public function getHierarchyPath(): string {
      return $this->hierarchyPath;
   }
   public function getDepthLevel(): int {
      return $this->depthLevel;
   }
   public function getChildrenCount(): int {
      return $this->childrenCount;
   }
   public function isActive(): bool {
      return $this->isActive;
   }
   public function isAcademicUnit(): bool {
      return $this->isAcademicUnit;
   }
   public function isAdministrativeUnit(): bool {
      return $this->isAdministrativeUnit;
   }
   public function isTeachingUnit(): bool {
      return $this->isTeachingUnit;
   }
   public function getCreatedAt(): DateTimeImmutable {
      return $this->createdAt;
   }
   public function getUpdatedAt(): DateTimeImmutable {
      return $this->updatedAt;
   }
   public function getChildren(): array {
      return $this->children;
   }

   public static function fromEntity(\Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit $unit): self {
      $parent = $unit->getParent();

      return new self(
         $unit->getId(),
         $unit->getName(),
         $unit->getType(),
         $parent ? $parent->getId() : null,
         $parent ? $parent->getName() : null,
         $unit->getHierarchyPath(),
         $unit->getDepthLevel(),
         $unit->getChildren()->count(),
         $unit->isActive(),
         $unit->isAcademicUnit(),
         $unit->isAdministrativeUnit(),
         $unit->isTeachingUnit(),
         $unit->getCreatedAt(),
         $unit->getUpdatedAt(),
         []
      );
   }

   public function toArray(): array {
      return [
         'id' => $this->id,
         'name' => $this->name,
         'type' => $this->type,
         'parent_id' => $this->parentId,
         'parent_name' => $this->parentName,
         'hierarchy_path' => $this->hierarchyPath,
         'depth_level' => $this->depthLevel,
         'children_count' => $this->childrenCount,
         'is_active' => $this->isActive,
         'is_academic_unit' => $this->isAcademicUnit,
         'is_administrative_unit' => $this->isAdministrativeUnit,
         'is_teaching_unit' => $this->isTeachingUnit,
         'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
         'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
         'children' => $this->children
      ];
   }
}
