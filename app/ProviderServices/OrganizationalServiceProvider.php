<?php
/**
 * @package     ff24fd0/app
 * @subpackage  ProviderServices
 * @file        OrganizationalServiceProvider
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 20:00:00
 * @version     1.0.0
 * @description Provider de servicios para el módulo Organizational
 */

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Organizational\Config\OrganizationalServiceProvider as ModuleServiceProvider;
use Viex\Modules\Organizational\Domain\Repository\OrganizationalUnitRepositoryInterface;
use Viex\Modules\Organizational\Infrastructure\Persistence\Doctrine\DoctrineOrganizationalUnitRepository;
use Viex\Modules\Organizational\Infrastructure\Cache\HierarchyCacheService;
use Viex\Modules\Organizational\Application\Services\OrganizationalHierarchyService;
use Viex\Modules\Organizational\Application\Services\UnitManagementService;
use Viex\Modules\Organizational\Application\Services\ContextService;
use Viex\Modules\Organizational\Application\Events\SimpleEventDispatcher;
use Viex\Modules\Organizational\Application\Events\EventDispatcherInterface;
use Viex\Modules\Organizational\Infrastructure\Http\OrganizationalController;
use Viex\Modules\Organizational\Infrastructure\Http\HierarchyController;

return function (ContainerBuilder $containerBuilder) {
   $containerBuilder->addDefinitions([
         // Repository
      OrganizationalUnitRepositoryInterface::class => function (ContainerInterface $container) {
         return new DoctrineOrganizationalUnitRepository(
            $container->get(EntityManagerInterface::class)
         );
      },

         // Event Dispatcher
      EventDispatcherInterface::class => function (ContainerInterface $container) {
         return new SimpleEventDispatcher();
      },

         // Cache Service
      HierarchyCacheService::class => function (ContainerInterface $container) {
         return new HierarchyCacheService();
      },

         // Services
      OrganizationalHierarchyService::class => function (ContainerInterface $container) {
         return new OrganizationalHierarchyService(
            $container->get(OrganizationalUnitRepositoryInterface::class),
            $container->get(HierarchyCacheService::class)
         );
      },

      UnitManagementService::class => function (ContainerInterface $container) {
         return new UnitManagementService(
            $container->get(OrganizationalUnitRepositoryInterface::class),
            $container->get(OrganizationalHierarchyService::class),
            $container->get(EventDispatcherInterface::class)
         );
      },

      ContextService::class => function (ContainerInterface $container) {
         return new ContextService(
            $container->get(OrganizationalUnitRepositoryInterface::class),
            $container->get(OrganizationalHierarchyService::class)
         );
      },

         // Controllers
      OrganizationalController::class => function (ContainerInterface $container) {
         // Crear servicios manualmente para el controlador
         $entityManager = $container->get(EntityManagerInterface::class);
         $manualServices = ModuleServiceProvider::createManualServices($entityManager);

         return new OrganizationalController(
            $manualServices['useCases']['create'],
            $manualServices['useCases']['update'],
            $manualServices['useCases']['delete'],
            $manualServices['useCases']['get'],
            $manualServices['useCases']['search']
         );
      },

      HierarchyController::class => function (ContainerInterface $container) {
         // Crear servicios manualmente para el controlador
         $entityManager = $container->get(EntityManagerInterface::class);
         $manualServices = ModuleServiceProvider::createManualServices($entityManager);

         return new HierarchyController(
            $manualServices['useCases']['getTree'],
            $manualServices['useCases']['stats'],
            $manualServices['useCases']['move']
         );
      },

      // Servicios manuales como fallback
      'OrganizationalModule_Services' => function (ContainerInterface $container) {
         $entityManager = $container->get(EntityManagerInterface::class);
         return ModuleServiceProvider::createManualServices($entityManager);
      },
   ]);
};
