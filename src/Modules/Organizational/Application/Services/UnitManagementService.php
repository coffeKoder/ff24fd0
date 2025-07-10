<?php
/**
 * @package     Organizational/Application
 * @subpackage  Services
 * @file        UnitManagementService
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:00:00
 * @version     1.0.0
 * @description Servicio para gestión CRUD de unidades organizacionales
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\Services;

use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;
use Viex\Modules\Organizational\Domain\Entities\OrganizationalUnit;
use Viex\Modules\Organizational\Domain\ValueObjects\UnitType;
use Viex\Modules\Organizational\Domain\Exceptions\UnitNotFoundException;
use Viex\Modules\Organizational\Domain\Exceptions\InvalidHierarchyException;
use Viex\Modules\Organizational\Application\DTOs\OrganizationalUnitDTO;
use Viex\Modules\Organizational\Application\Events\UnitCreated;
use Viex\Modules\Organizational\Application\Events\UnitMoved;
use Viex\Modules\Organizational\Application\Events\HierarchyChanged;
use Viex\Modules\Organizational\Application\Events\EventDispatcherInterface;

class UnitManagementService {
   private OrganizationalUnitRepositoryInterface $repository;
   private OrganizationalHierarchyService $hierarchyService;
   private EventDispatcherInterface $eventDispatcher;

   public function __construct(
      OrganizationalUnitRepositoryInterface $repository,
      OrganizationalHierarchyService $hierarchyService,
      EventDispatcherInterface $eventDispatcher
   ) {
      $this->repository = $repository;
      $this->hierarchyService = $hierarchyService;
      $this->eventDispatcher = $eventDispatcher;
   }

   /**
    * Crear una nueva unidad organizacional
    */
   public function createUnit(string $name, string $type, ?int $parentId = null): OrganizationalUnitDTO {
      // Validar que el tipo sea válido
      UnitType::create($type);

      // Verificar que no existe otra unidad con el mismo nombre y tipo
      if ($this->repository->existsByNameAndType($name, $type)) {
         throw InvalidHierarchyException::duplicateUnitInHierarchy($name, $type);
      }

      // Obtener unidad padre si se especifica
      $parent = null;
      if ($parentId !== null) {
         $parent = $this->repository->findById($parentId);
         if (!$parent) {
            throw UnitNotFoundException::withId($parentId);
         }

         // Validar jerarquía usando los value objects
         $parentType = UnitType::create($parent->getType());
         $childType = UnitType::create($type);
         $this->validateHierarchyRules($parentType, $childType);
      }

      // Crear la unidad
      $unit = new OrganizationalUnit($name, $type, $parent);
      $this->repository->save($unit);

      // Disparar evento
      $event = new UnitCreated($unit->getId(), $name, $type, $parentId);
      $this->dispatchEvent($event);

      // Invalidar caché de jerarquía
      $this->hierarchyService->flushCache();

      return OrganizationalUnitDTO::fromEntity($unit);
   }

   /**
    * Actualizar una unidad organizacional
    */
   public function updateUnit(int $unitId, string $name, string $type): OrganizationalUnitDTO {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      // Validar que el tipo sea válido
      UnitType::create($type);

      // Verificar que no existe otra unidad con el mismo nombre y tipo (excluyendo la actual)
      if ($this->repository->existsByNameAndType($name, $type, $unitId)) {
         throw InvalidHierarchyException::duplicateUnitInHierarchy($name, $type);
      }

      $unit->setName($name);
      $unit->setType($type);
      $this->repository->save($unit);

      // Disparar evento de cambio en jerarquía
      $event = new HierarchyChanged('updated', $unitId);
      $this->dispatchEvent($event);

      // Invalidar caché
      $this->hierarchyService->flushCache();

      return OrganizationalUnitDTO::fromEntity($unit);
   }

   /**
    * Mover una unidad en la jerarquía
    */
   public function moveUnit(int $unitId, ?int $newParentId): OrganizationalUnitDTO {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      $oldParentId = $unit->getParent() ? $unit->getParent()->getId() : null;

      // Validar el movimiento
      $this->hierarchyService->validateUnitMove($unitId, $newParentId);

      // Obtener nuevo padre si se especifica
      $newParent = null;
      if ($newParentId !== null) {
         $newParent = $this->repository->findById($newParentId);
         if (!$newParent) {
            throw UnitNotFoundException::withId($newParentId);
         }

         // Validar reglas de jerarquía
         $parentType = UnitType::create($newParent->getType());
         $childType = UnitType::create($unit->getType());
         $this->validateHierarchyRules($parentType, $childType);
      }

      // Realizar el movimiento
      $unit->setParent($newParent);
      $this->repository->save($unit);

      // Disparar eventos
      $moveEvent = new UnitMoved($unitId, $unit->getName(), $oldParentId, $newParentId);
      $this->dispatchEvent($moveEvent);

      $hierarchyEvent = new HierarchyChanged('moved', $unitId, $this->getAffectedUnitIds($unit));
      $this->dispatchEvent($hierarchyEvent);

      // Invalidar caché
      $this->hierarchyService->flushCache();

      return OrganizationalUnitDTO::fromEntity($unit);
   }

   /**
    * Eliminar una unidad organizacional
    */
   public function deleteUnit(int $unitId, bool $forceDelete = false): bool {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      // Verificar si se puede eliminar
      if (!$forceDelete && !$this->hierarchyService->canDeleteUnit($unitId)) {
         if ($unit->hasChildren()) {
            throw InvalidHierarchyException::cannotDeleteUnitWithChildren(
               $unit->getName(),
               $unit->getChildren()->count()
            );
         }

         throw InvalidHierarchyException::cannotMoveUnitWithDependencies(
            $unit->getName(),
            'usuarios asignados'
         );
      }

      // Si se fuerza la eliminación, eliminar primero los hijos
      if ($forceDelete && $unit->hasChildren()) {
         $descendants = $this->hierarchyService->getDescendantsForUnit($unitId);
         foreach ($descendants as $descendant) {
            $this->deleteUnit($descendant->getId(), true);
         }
      }

      // Realizar soft delete
      $unit->delete();
      $this->repository->save($unit);

      // Disparar evento
      $event = new HierarchyChanged('deleted', $unitId);
      $this->dispatchEvent($event);

      // Invalidar caché
      $this->hierarchyService->flushCache();

      return true;
   }

   /**
    * Activar/Desactivar una unidad
    */
   public function toggleUnitStatus(int $unitId): OrganizationalUnitDTO {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      if ($unit->isActive()) {
         $unit->deactivate();
      } else {
         $unit->activate();
      }

      $this->repository->save($unit);

      // Invalidar caché
      $this->hierarchyService->flushCache();

      return OrganizationalUnitDTO::fromEntity($unit);
   }

   /**
    * Obtener unidad por ID
    */
   public function getUnitById(int $unitId): OrganizationalUnitDTO {
      $unit = $this->repository->findById($unitId);
      if (!$unit) {
         throw UnitNotFoundException::withId($unitId);
      }

      return OrganizationalUnitDTO::fromEntity($unit);
   }

   /**
    * Buscar unidades con paginación
    */
   public function searchUnits(int $page = 1, int $limit = 20, ?string $search = null, ?string $type = null): array {
      $units = $this->repository->findPaginated($page, $limit, $search, $type);

      return array_map(
         fn(OrganizationalUnit $unit) => OrganizationalUnitDTO::fromEntity($unit),
         $units
      );
   }

   /**
    * Validar reglas de jerarquía universitaria
    */
   private function validateHierarchyRules(UnitType $parentType, UnitType $childType): void {
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
    * Obtener IDs de unidades afectadas por un cambio
    */
   private function getAffectedUnitIds(OrganizationalUnit $unit): array {
      $affectedIds = [$unit->getId()];

      // Agregar descendientes
      foreach ($unit->getDescendants() as $descendant) {
         $affectedIds[] = $descendant->getId();
      }

      return $affectedIds;
   }

   /**
    * Disparar evento
    */
   private function dispatchEvent($event): void {
      $this->eventDispatcher->dispatch($event);
   }
}
