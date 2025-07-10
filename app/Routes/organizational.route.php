<?php
/**
 * @package     ff24fd0/app
 * @subpackage  Routes
 * @file        organizational.route
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 20:00:00
 * @version     1.0.0
 * @description Rutas del módulo Organizational
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

   // Rutas adicionales para vistas HTML (opcional)
   $app->group('/admin/organizational', function (RouteCollectorProxy $group) {
      // Estas rutas pueden ser implementadas posteriormente para interfaz administrativa
      $group->get('/dashboard', function ($request, $response) {
         $response->getBody()->write('Organizational Dashboard - Coming Soon');
         return $response;
      });
   });
};
