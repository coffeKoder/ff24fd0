<?php
/**
 * @package     Organizational/Infrastructure
 * @subpackage  Http
 * @file        HierarchyController
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 18:00:00
 * @version     1.0.0
 * @description Controlador HTTP para navegación jerárquica de unidades organizacionales
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Infrastructure\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Viex\Modules\Organizational\Application\UseCases\GetHierarchyTree;
use Viex\Modules\Organizational\Application\UseCases\GetHierarchyStatistics;
use Viex\Modules\Organizational\Application\UseCases\MoveUnit;
use Viex\Modules\Organizational\Domain\Exceptions\UnitNotFoundException;
use Viex\Modules\Organizational\Domain\Exceptions\InvalidHierarchyException;

class HierarchyController {

   private GetHierarchyTree $getTreeUseCase;
   private GetHierarchyStatistics $getStatsUseCase;
   private MoveUnit $moveUnitUseCase;

   public function __construct(
      GetHierarchyTree $getTreeUseCase,
      GetHierarchyStatistics $getStatsUseCase,
      MoveUnit $moveUnitUseCase
   ) {
      $this->getTreeUseCase = $getTreeUseCase;
      $this->getStatsUseCase = $getStatsUseCase;
      $this->moveUnitUseCase = $moveUnitUseCase;
   }

   /**
    * Obtener árbol jerárquico completo o subárbol
    * GET /api/organizational/hierarchy/tree[?rootId={id}]
    */
   public function getTree(Request $request, Response $response): Response {
      try {
         $queryParams = $request->getQueryParams();
         $rootId = isset($queryParams['rootId']) ? (int) $queryParams['rootId'] : null;

         if ($rootId !== null && $rootId <= 0) {
            $responseData = [
               'status' => 'error',
               'message' => 'ID de unidad raíz inválido'
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
         }

         $tree = $this->getTreeUseCase->execute($rootId);

         $responseData = [
            'status' => 'success',
            'data' => $tree->toArray(),
            'rootId' => $rootId
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (UnitNotFoundException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Unidad raíz no encontrada'
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al obtener el árbol jerárquico',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Obtener estadísticas de la jerarquía
    * GET /api/organizational/hierarchy/stats
    */
   public function getStatistics(Request $request, Response $response): Response {
      try {
         $stats = $this->getStatsUseCase->execute();

         $responseData = [
            'status' => 'success',
            'data' => $stats
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al obtener estadísticas',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Obtener contexto de una unidad específica
    * GET /api/organizational/hierarchy/units/{id}/context
    */
   public function getUnitContext(Request $request, Response $response, array $args): Response {
      try {
         $unitId = (int) $args['id'];

         if ($unitId <= 0) {
            $responseData = [
               'status' => 'error',
               'message' => 'ID de unidad inválido'
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
         }

         $context = $this->getStatsUseCase->getUnitContext($unitId);

         $responseData = [
            'status' => 'success',
            'data' => $context
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (UnitNotFoundException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Unidad no encontrada'
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al obtener contexto de la unidad',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Obtener línea de ascendencia de una unidad
    * GET /api/organizational/hierarchy/units/{id}/lineage
    */
   public function getLineage(Request $request, Response $response, array $args): Response {
      try {
         $unitId = (int) $args['id'];

         if ($unitId <= 0) {
            $responseData = [
               'status' => 'error',
               'message' => 'ID de unidad inválido'
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
         }

         $lineage = $this->getStatsUseCase->getLineage($unitId);

         $responseData = [
            'status' => 'success',
            'data' => array_map(fn($unit) => $unit->toArray(), $lineage)
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (UnitNotFoundException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Unidad no encontrada'
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al obtener línea de ascendencia',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Obtener descendientes de una unidad
    * GET /api/organizational/hierarchy/units/{id}/descendants
    */
   public function getDescendants(Request $request, Response $response, array $args): Response {
      try {
         $unitId = (int) $args['id'];

         if ($unitId <= 0) {
            $responseData = [
               'status' => 'error',
               'message' => 'ID de unidad inválido'
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
         }

         $descendants = $this->getStatsUseCase->getDescendants($unitId);

         $responseData = [
            'status' => 'success',
            'data' => array_map(fn($unit) => $unit->toArray(), $descendants)
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (UnitNotFoundException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Unidad no encontrada'
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al obtener descendientes',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Mover unidad en la jerarquía
    * PATCH /api/organizational/hierarchy/units/{id}/move
    */
   public function moveUnit(Request $request, Response $response, array $args): Response {
      try {
         $unitId = (int) $args['id'];
         $data = json_decode((string) $request->getBody(), true);

         if ($unitId <= 0) {
            $responseData = [
               'status' => 'error',
               'message' => 'ID de unidad inválido'
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
         }

         if (!$data || !isset($data['new_parent_id'])) {
            $responseData = [
               'status' => 'error',
               'message' => 'new_parent_id es requerido'
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
         }

         $newParentId = $data['new_parent_id'] === null ? null : (int) $data['new_parent_id'];

         $unit = $this->moveUnitUseCase->execute($unitId, $newParentId);

         $responseData = [
            'status' => 'success',
            'message' => 'Unidad movida exitosamente',
            'data' => $unit->toArray()
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (UnitNotFoundException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Unidad no encontrada'
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

      } catch (InvalidHierarchyException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Movimiento jerárquico inválido',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(409);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al mover la unidad',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }
}
