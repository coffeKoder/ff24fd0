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
         // Repository - Usar autowire para evitar dependencias circulares
      OrganizationalUnitRepositoryInterface::class => \DI\autowire(DoctrineOrganizationalUnitRepository::class),

         // Event Dispatcher - Simple, sin dependencias
      EventDispatcherInterface::class => \DI\autowire(SimpleEventDispatcher::class),

         // Cache Service - Simple, sin dependencias
      HierarchyCacheService::class => \DI\autowire(HierarchyCacheService::class),

         // Services - Usar autowire para manejo automático de dependencias
      OrganizationalHierarchyService::class => \DI\autowire(OrganizationalHierarchyService::class),
      UnitManagementService::class => \DI\autowire(UnitManagementService::class),
      ContextService::class => \DI\autowire(ContextService::class),

         // Controllers - Usar autowire para evitar duplicación de servicios
      OrganizationalController::class => \DI\autowire(OrganizationalController::class),
      HierarchyController::class => \DI\autowire(HierarchyController::class),
   ]);
};
