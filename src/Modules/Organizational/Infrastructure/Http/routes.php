<?php
/**
 * @package     Organizational/Infrastructure
 * @subpackage  Http
 * @file        Routes
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 18:15:00
 * @version     1.0.0
 * @description Rutas HTTP para el módulo Organizational
 */

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Viex\Modules\Organizational\Infrastructure\Http\OrganizationalController;
use Viex\Modules\Organizational\Infrastructure\Http\HierarchyController;

return function (App $app) {
   
   // Grupo de rutas para unidades organizacionales
   $app->group('/api/organizational', function (RouteCollectorProxy $group) {
      
      // Rutas CRUD para unidades organizacionales
      $group->group('/units', function (RouteCollectorProxy $unitsGroup) {
         $unitsGroup->get('', [OrganizationalController::class, 'index']);
         $unitsGroup->post('', [OrganizationalController::class, 'store']);
         $unitsGroup->get('/{id:[0-9]+}', [OrganizationalController::class, 'show']);
         $unitsGroup->put('/{id:[0-9]+}', [OrganizationalController::class, 'update']);
         $unitsGroup->delete('/{id:[0-9]+}', [OrganizationalController::class, 'destroy']);
      });

      // Rutas para navegación jerárquica
      $group->group('/hierarchy', function (RouteCollectorProxy $hierarchyGroup) {
         
         // Obtener árbol jerárquico
         $hierarchyGroup->get('/tree', [HierarchyController::class, 'getTree']);
         
         // Obtener estadísticas generales
         $hierarchyGroup->get('/stats', [HierarchyController::class, 'getStatistics']);
         
         // Rutas específicas para unidades
         $hierarchyGroup->group('/units/{id:[0-9]+}', function (RouteCollectorProxy $unitGroup) {
            $unitGroup->get('/context', [HierarchyController::class, 'getUnitContext']);
            $unitGroup->get('/lineage', [HierarchyController::class, 'getLineage']);
            $unitGroup->get('/descendants', [HierarchyController::class, 'getDescendants']);
            $unitGroup->patch('/move', [HierarchyController::class, 'moveUnit']);
         });
      });
   });

   // Middleware para CORS (si es necesario)
   $app->options('/{routes:.+}', function ($request, $response, $args) {
      return $response;
   });

   // Middleware para autenticación (a implementar según necesidades)
   // $app->add(new AuthenticationMiddleware());

   // Middleware para autorización basada en roles (a implementar)
   // $app->add(new AuthorizationMiddleware());
};
