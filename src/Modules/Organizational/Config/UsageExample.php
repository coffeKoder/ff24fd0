<?php
/**
 * @package     Organizational
 * @subpackage  Config
 * @file        UsageExample
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 17:00:00
 * @version     1.0.0
 * @description Ejemplo de uso del módulo Organizational
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Config;

use DI\Container;
use DI\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Viex\Modules\Organizational\Config\OrganizationalServiceProvider;
use Viex\Modules\Organizational\Application\UseCases\CreateOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\GetHierarchyTree;
use Viex\Modules\Organizational\Application\Events\EventDispatcherInterface;

class UsageExample {
   
   /**
    * Ejemplo de configuración con PHP-DI
    */
   public static function setupWithContainer(): Container {
      $builder = new ContainerBuilder();
      
      // Agregar las definiciones del módulo Organizational
      $builder->addDefinitions(OrganizationalServiceProvider::getDefinitions());
      
      // Agregar otras definiciones del sistema (EntityManager, etc.)
      $builder->addDefinitions([
         // Ejemplo: configurar EntityManager (debe ser configurado según tu setup)
         EntityManagerInterface::class => \DI\factory(function() {
            // Aquí configurarías Doctrine según tu setup
            throw new \Exception('EntityManager debe ser configurado');
         }),
      ]);
      
      $container = $builder->build();
      
      // Configurar event listeners
      $eventDispatcher = $container->get(EventDispatcherInterface::class);
      OrganizationalServiceProvider::configureEventListeners($eventDispatcher);
      
      return $container;
   }
   
   /**
    * Ejemplo de uso con el contenedor
    */
   public static function exampleUsageWithContainer(Container $container): void {
      // Obtener un caso de uso
      $createUseCase = $container->get(CreateOrganizationalUnit::class);
      
      // Usar el caso de uso
      $unit = $createUseCase->execute('Facultad de Ingeniería', 'FACULTY', null);
      
      echo "Unidad creada: " . $unit->getName() . "\n";
      
      // Obtener árbol jerárquico
      $getTreeUseCase = $container->get(GetHierarchyTree::class);
      $tree = $getTreeUseCase->execute();
      
      echo "Árbol jerárquico obtenido\n";
   }
   
   /**
    * Ejemplo de uso manual (sin contenedor DI)
    */
   public static function exampleManualUsage(EntityManagerInterface $entityManager): void {
      // Crear servicios manualmente
      $services = OrganizationalServiceProvider::createManualServices($entityManager);
      
      // Configurar event listeners
      OrganizationalServiceProvider::configureEventListeners($services['eventDispatcher']);
      
      // Usar los casos de uso
      $createUseCase = $services['useCases']['create'];
      $unit = $createUseCase->execute('Facultad de Ingeniería', 'FACULTY', null);
      
      echo "Unidad creada: " . $unit->getName() . "\n";
      
      // Obtener árbol jerárquico
      $getTreeUseCase = $services['useCases']['getTree'];
      $tree = $getTreeUseCase->execute();
      
      echo "Árbol jerárquico obtenido\n";
   }
   
   /**
    * Ejemplo de integración con Slim Framework
    */
   public static function setupWithSlim(): array {
      // Configuración típica para Slim con PHP-DI
      return [
         'organizational' => [
            'definitions' => OrganizationalServiceProvider::getDefinitions(),
            'configurator' => function(Container $container) {
               $eventDispatcher = $container->get(EventDispatcherInterface::class);
               OrganizationalServiceProvider::configureEventListeners($eventDispatcher);
            }
         ]
      ];
   }
}
