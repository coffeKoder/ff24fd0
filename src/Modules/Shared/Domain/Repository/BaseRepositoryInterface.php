<?php
/**
 * @package     Shared/Domain
 * @subpackage  Repository
 * @file        BaseRepositoryInterface,php
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 13:47:09
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace Viex\Modules\Shared\Domain\Repository;

interface BaseRepositoryInterface {
   /**
    * Buscar una entidad por su ID
    * 
    * @param int|string $id El identificador único de la entidad
    * @return object|null La entidad encontrada o null si no existe
    */
   public function findById($id): ?object;

   /**BaseRepositoryInterface
    * Buscar todas las entidades
    * 
    * @return array Lista de todas las entidades
    */
   public function findAll(): array;

   /**
    * Buscar entidades que coincidan con los criterios especificados
    * 
    * @param array $criteria Array asociativo con los criterios de búsqueda
    * @param array|null $orderBy Array asociativo para ordenamiento (campo => direccion)
    * @param int|null $limit Número máximo de resultados
    * @param int|null $offset Número de resultados a omitir
    * @return array Lista de entidades que coinciden con los criterios
    */
   public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

   /**
    * Buscar una sola entidad que coincida con los criterios
    * 
    * @param array $criteria Array asociativo con los criterios de búsqueda
    * @return object|null La primera entidad encontrada o null
    */
   public function findOneBy(array $criteria): ?object;

   /**
    * Persistir una entidad (crear o actualizar)
    * 
    * @param object $entity La entidad a persistir
    * @return object La entidad persistida
    */
   public function save(object $entity): object;

   /**
    * Eliminar una entidad
    * 
    * @param object $entity La entidad a eliminar
    * @return void
    */
   public function delete(object $entity): void;

   /**
    * Contar el número total de entidades
    * 
    * @param array $criteria Array asociativo con criterios opcionales
    * @return int El número total de entidades
    */
   public function count(array $criteria = []): int;
}