<?php
/**
 * @package     Organizational
 * @subpackage  Config
 * @file        OrganizationalServiceProvider
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:40:00
 * @version     1.0.0
 * @description Proveedor de servicios para el módulo Organizational
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Config;

use DI\ContainerBuilder;
use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;
use Viex\Modules\Organizational\Infrastructure\Persistence\Doctrine\DoctrineOrganizationalUnitRepository;
use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;
use Viex\Modules\Organizational\Application\Services\UnitManagementService;
use Viex\Modules\Organizational\Application\Services\ContextService;
use Viex\Modules\Organizational\Application\Events\EventDispatcherInterface;
use Viex\Modules\Organizational\Application\Events\SimpleEventDispatcher;
use Viex\Modules\Organizational\Application\UseCases\CreateOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\UpdateOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\DeleteOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\GetOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\GetHierarchyTree;
use Viex\Modules\Organizational\Application\UseCases\SearchOrganizationalUnits;
use Viex\Modules\Organizational\Application\UseCases\GetHierarchyStatistics;

class OrganizationalServiceProvider {

   /**
    * Obtener configuración de servicios para PHP-DI
    */
   public static function getDefinitions(): array {
      return [
            // Repositorio
         OrganizationalUnitRepositoryInterface::class => \DI\autowire(DoctrineOrganizationalUnitRepository::class),

            // Event Dispatcher
         EventDispatcherInterface::class => \DI\autowire(SimpleEventDispatcher::class),

            // Servicios de aplicación
         OrganizationalHierarchyService::class => \DI\autowire(OrganizationalHierarchyService::class),
         UnitManagementService::class => \DI\autowire(UnitManagementService::class),
         ContextService::class => \DI\autowire(ContextService::class),

            // Casos de uso
         CreateOrganizationalUnit::class => \DI\autowire(CreateOrganizationalUnit::class),
         UpdateOrganizationalUnit::class => \DI\autowire(UpdateOrganizationalUnit::class),
         DeleteOrganizationalUnit::class => \DI\autowire(DeleteOrganizationalUnit::class),
         GetOrganizationalUnit::class => \DI\autowire(GetOrganizationalUnit::class),
         GetHierarchyTree::class => \DI\autowire(GetHierarchyTree::class),
         SearchOrganizationalUnits::class => \DI\autowire(SearchOrganizationalUnits::class),
         GetHierarchyStatistics::class => \DI\autowire(GetHierarchyStatistics::class),
      ];
   }

   /**
    * Configurar listeners de eventos
    */
   public static function configureEventListeners(EventDispatcherInterface $eventDispatcher): void {
      // Configurar listeners para eventos de unidades
      $eventDispatcher->addListener(
         'Viex\Modules\Organizational\Application\Events\UnitCreated',
         function ($event) {
            // Log la creación de unidad
            error_log("Unidad creada: " . $event->getUnit()->getName());
         }
      );

      $eventDispatcher->addListener(
         'Viex\Modules\Organizational\Application\Events\UnitMoved',
         function ($event) {
            // Log el movimiento de unidad
            error_log("Unidad movida: " . $event->getUnit()->getName());
         }
      );

      $eventDispatcher->addListener(
         'Viex\Modules\Organizational\Application\Events\HierarchyChanged',
         function ($event) {
            // Log cambios en jerarquía
            error_log("Jerarquía cambiada: " . $event->getChangeType());
         }
      );
   }

   /**
    * Ejemplo de uso manual (sin contenedor)
    */
   public static function createManualServices(): array {
      // Crear repositorio (requiere EntityManager de Doctrine)
      $entityManager = null; // Debe ser inyectado desde el contexto principal
      $repository = new DoctrineOrganizationalUnitRepository($entityManager);

      // Crear event dispatcher
      $eventDispatcher = new SimpleEventDispatcher();

      // Crear servicios
      $hierarchyService = new OrganizationalHierarchyService($repository);
      $unitManagementService = new UnitManagementService($repository, $hierarchyService, $eventDispatcher);
      $contextService = new ContextService($repository, $hierarchyService);

      // Crear casos de uso
      $createUseCase = new CreateOrganizationalUnit($unitManagementService, $eventDispatcher);
      $updateUseCase = new UpdateOrganizationalUnit($unitManagementService, $eventDispatcher);
      $deleteUseCase = new DeleteOrganizationalUnit($unitManagementService, $hierarchyService, $eventDispatcher);
      $getUseCase = new GetOrganizationalUnit($repository);
      $getTreeUseCase = new GetHierarchyTree($hierarchyService);
      $searchUseCase = new SearchOrganizationalUnits($hierarchyService);
      $statsUseCase = new GetHierarchyStatistics($hierarchyService);

      return [
         'repository' => $repository,
         'eventDispatcher' => $eventDispatcher,
         'hierarchyService' => $hierarchyService,
         'unitManagementService' => $unitManagementService,
         'contextService' => $contextService,
         'useCases' => [
            'create' => $createUseCase,
            'update' => $updateUseCase,
            'delete' => $deleteUseCase,
            'get' => $getUseCase,
            'getTree' => $getTreeUseCase,
            'search' => $searchUseCase,
            'stats' => $statsUseCase,
         ]
      ];
   }
}
