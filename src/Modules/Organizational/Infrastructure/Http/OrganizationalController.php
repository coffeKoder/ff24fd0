<?php
/**
 * @package     Organizational/Infrastructure
 * @subpackage  Http
 * @file        OrganizationalController
 * @author      Fernando Castillo <fdocst@gmail.com>
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Infrastructure\Http;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Viex\Modules\Organizational\Application\UseCases\CreateOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\UpdateOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\DeleteOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\GetOrganizationalUnit;
use Viex\Modules\Organizational\Application\UseCases\SearchOrganizationalUnits;
use Viex\Modules\Organizational\Domain\Exceptions\UnitNotFoundException;
use Viex\Modules\Organizational\Domain\Exceptions\InvalidHierarchyException;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class OrganizationalController {

   private CreateOrganizationalUnit $createUseCase;
   private UpdateOrganizationalUnit $updateUseCase;
   private DeleteOrganizationalUnit $deleteUseCase;
   private GetOrganizationalUnit $getUseCase;
   private SearchOrganizationalUnits $searchUseCase;

   public function __construct(
      CreateOrganizationalUnit $createUseCase,
      UpdateOrganizationalUnit $updateUseCase,
      DeleteOrganizationalUnit $deleteUseCase,
      GetOrganizationalUnit $getUseCase,
      SearchOrganizationalUnits $searchUseCase
   ) {
      $this->createUseCase = $createUseCase;
      $this->updateUseCase = $updateUseCase;
      $this->deleteUseCase = $deleteUseCase;
      $this->getUseCase = $getUseCase;
      $this->searchUseCase = $searchUseCase;
   }

   /**
    * Listar unidades organizacionales con filtros
    * GET /api/organizational/units
    */
   public function index(Request $request, Response $response): Response {
      try {
         $queryParams = $request->getQueryParams();

         // Determinar el tipo de búsqueda
         if (isset($queryParams['search'])) {
            $units = $this->searchUseCase->execute($queryParams['search']);
         } elseif (isset($queryParams['type'])) {
            $units = $this->searchUseCase->getByType($queryParams['type']);
         } elseif (isset($queryParams['level'])) {
            $level = (int) $queryParams['level'];
            $units = $this->searchUseCase->getByLevel($level);
         } elseif (isset($queryParams['root']) && $queryParams['root'] === 'true') {
            $units = $this->searchUseCase->getRootUnits();
         } else {
            // Obtener todas las unidades raíz por defecto
            $units = $this->searchUseCase->getRootUnits();
         }

         $responseData = [
            'status' => 'success',
            'data' => array_map(fn($unit) => $unit->toArray(), $units),
            'count' => count($units)
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al obtener unidades organizacionales',
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Obtener una unidad específica
    * GET /api/organizational/units/{id}
    */
   public function show(Request $request, Response $response, array $args): Response {
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

         $unit = $this->getUseCase->execute($unitId);

         $responseData = [
            'status' => 'success',
            'data' => $unit->toArray()
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (UnitNotFoundException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Unidad organizacional no encontrada'
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al obtener la unidad organizacional',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Crear nueva unidad organizacional
    * POST /api/organizational/units
    */
   public function store(Request $request, Response $response): Response {
      try {
         $data = json_decode((string) $request->getBody(), true);

         // Validar datos de entrada
         $this->validateCreateData($data);

         $name = $data['name'];
         $type = $data['type'];
         $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;

         $unit = $this->createUseCase->execute($name, $type, $parentId);

         $responseData = [
            'status' => 'success',
            'message' => 'Unidad organizacional creada exitosamente',
            'data' => $unit->toArray()
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

      } catch (NestedValidationException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Datos de entrada inválidos',
            'errors' => $e->getMessages()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(422);

      } catch (\InvalidArgumentException $e) {
         $responseData = [
            'status' => 'error',
            'message' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(400);

      } catch (InvalidHierarchyException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error de jerarquía',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(409);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al crear la unidad organizacional',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Actualizar unidad organizacional
    * PUT /api/organizational/units/{id}
    */
   public function update(Request $request, Response $response, array $args): Response {
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

         // Validar datos de entrada
         $this->validateUpdateData($data);

         $name = $data['name'];
         $type = $data['type'];

         $unit = $this->updateUseCase->execute($unitId, $name, $type);

         $responseData = [
            'status' => 'success',
            'message' => 'Unidad organizacional actualizada exitosamente',
            'data' => $unit->toArray()
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (UnitNotFoundException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Unidad organizacional no encontrada'
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al actualizar la unidad organizacional',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Eliminar unidad organizacional
    * DELETE /api/organizational/units/{id}
    */
   public function destroy(Request $request, Response $response, array $args): Response {
      try {
         $unitId = (int) $args['id'];
         $queryParams = $request->getQueryParams();
         $forceDelete = isset($queryParams['force']) && $queryParams['force'] === 'true';

         if ($unitId <= 0) {
            $responseData = [
               'status' => 'error',
               'message' => 'ID de unidad inválido'
            ];
            $response->getBody()->write(json_encode($responseData));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
         }

         $this->deleteUseCase->execute($unitId, $forceDelete);

         $responseData = [
            'status' => 'success',
            'message' => 'Unidad organizacional eliminada exitosamente'
         ];

         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json');

      } catch (UnitNotFoundException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Unidad organizacional no encontrada'
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(404);

      } catch (InvalidHierarchyException $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'No se puede eliminar la unidad',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(409);

      } catch (\Exception $e) {
         $responseData = [
            'status' => 'error',
            'message' => 'Error al eliminar la unidad organizacional',
            'error' => $e->getMessage()
         ];
         $response->getBody()->write(json_encode($responseData));
         return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
      }
   }

   /**
    * Validar datos para crear unidad
    */
   private function validateCreateData(?array $data): void {
      if (!$data) {
         throw new \InvalidArgumentException('Datos requeridos');
      }

      $validator = v::arrayType()
         ->key('name', v::stringType()->notEmpty()->length(1, 255))
         ->key('type', v::stringType()->notEmpty()->in(['UNIVERSITY', 'CAMPUS', 'FACULTY', 'DEPARTMENT', 'SCHOOL']))
         ->key('parent_id', v::optional(v::intType()->positive()), false);

      $validator->assert($data);
   }

   /**
    * Validar datos para actualizar unidad
    */
   private function validateUpdateData(?array $data): void {
      if (!$data) {
         throw new \InvalidArgumentException('Datos requeridos');
      }

      $validator = v::arrayType()
         ->key('name', v::stringType()->notEmpty()->length(1, 255))
         ->key('type', v::stringType()->notEmpty()->in(['UNIVERSITY', 'CAMPUS', 'FACULTY', 'DEPARTMENT', 'SCHOOL']));

      $validator->assert($data);
   }
}
