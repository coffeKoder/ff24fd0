<?php

declare(strict_types=1);

namespace Viex\Modules\User\Application\DTOs;

use Viex\Modules\User\Domain\Entities\Permission;

/**
 * DTO para transferir información de permisos entre capas
 */
final class PermissionDTO {
   public int $id;
   public string $name;
   public string $description;
   public string $category;
   public bool $isActive;
   public ?\DateTimeImmutable $createdAt;
   public ?\DateTimeImmutable $updatedAt;

   public function __construct(
      int $id,
      string $name,
      string $description,
      string $category,
      bool $isActive = true,
      ?\DateTimeImmutable $createdAt = null,
      ?\DateTimeImmutable $updatedAt = null
   ) {
      $this->id = $id;
      $this->name = $name;
      $this->description = $description;
      $this->category = $category;
      $this->isActive = $isActive;
      $this->createdAt = $createdAt;
      $this->updatedAt = $updatedAt;
   }

   /**
    * Crea un PermissionDTO desde una entidad Permission
    */
   public static function fromEntity(Permission $permission): self {
      return new self(
         $permission->getId(),
         $permission->getName(),
         $permission->getDescription(),
         $permission->getModule(), // Usar module como category
         $permission->isActive(),
         $permission->getCreatedAt(),
         $permission->getUpdatedAt()
      );
   }

   /**
    * Convierte múltiples entidades Permission en DTOs
    */
   public static function fromEntities(array $permissions): array {
      return array_map(
         fn(Permission $permission) => self::fromEntity($permission),
         $permissions
      );
   }

   /**
    * Convierte el DTO a array
    */
   public function toArray(): array {
      return [
         'id' => $this->id,
         'name' => $this->name,
         'description' => $this->description,
         'category' => $this->category,
         'isActive' => $this->isActive,
         'createdAt' => $this->createdAt?->format('Y-m-d H:i:s'),
         'updatedAt' => $this->updatedAt?->format('Y-m-d H:i:s')
      ];
   }

   /**
    * Obtiene información básica del permiso
    */
   public function getBasicInfo(): array {
      return [
         'id' => $this->id,
         'name' => $this->name,
         'description' => $this->description,
         'category' => $this->category
      ];
   }
}

/**
 * DTO para agrupar permisos por categoría
 */
final class PermissionsByCategoryDTO {
   private array $permissionsByCategory = [];

   public function __construct(array $permissions = []) {
      $this->groupByCategory($permissions);
   }

   /**
    * Agrupa permisos por categoría
    */
   private function groupByCategory(array $permissions): void {
      foreach ($permissions as $permission) {
         if ($permission instanceof PermissionDTO) {
            $this->permissionsByCategory[$permission->category][] = $permission;
         }
      }
   }

   /**
    * Agrega un permiso
    */
   public function addPermission(PermissionDTO $permission): void {
      $this->permissionsByCategory[$permission->category][] = $permission;
   }

   /**
    * Obtiene permisos por categoría
    */
   public function getByCategory(string $category): array {
      return $this->permissionsByCategory[$category] ?? [];
   }

   /**
    * Obtiene todas las categorías
    */
   public function getCategories(): array {
      return array_keys($this->permissionsByCategory);
   }

   /**
    * Obtiene todos los permisos agrupados
    */
   public function getGrouped(): array {
      return $this->permissionsByCategory;
   }

   /**
    * Obtiene todos los permisos en una lista plana
    */
   public function getFlat(): array {
      $flat = [];
      foreach ($this->permissionsByCategory as $permissions) {
         $flat = array_merge($flat, $permissions);
      }
      return $flat;
   }

   /**
    * Cuenta total de permisos
    */
   public function getTotalCount(): int {
      return count($this->getFlat());
   }

   /**
    * Cuenta permisos por categoría
    */
   public function getCountByCategory(): array {
      $counts = [];
      foreach ($this->permissionsByCategory as $category => $permissions) {
         $counts[$category] = count($permissions);
      }
      return $counts;
   }

   /**
    * Convierte a array
    */
   public function toArray(): array {
      $result = [];
      foreach ($this->permissionsByCategory as $category => $permissions) {
         $result[$category] = array_map(
            fn(PermissionDTO $permission) => $permission->toArray(),
            $permissions
         );
      }
      return $result;
   }
}
