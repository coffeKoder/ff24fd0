<?php
/**
 * @package     Organizational/Infrastructure
 * @subpackage  Cache
 * @file        HierarchyCacheService
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 18:30:00
 * @version     1.0.0
 * @description Servicio de caché para árboles jerárquicos organizacionales
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Infrastructure\Cache;

use Viex\Modules\Organizational\Application\DTOs\HierarchyTreeDTO;

class HierarchyCacheService {

   private array $cache = [];
   private int $defaultTtl = 3600; // 1 hora por defecto
   private array $timestamps = [];

   /**
    * Obtener árbol jerárquico desde caché
    */
   public function getHierarchyTree(?int $rootId = null): ?HierarchyTreeDTO {
      $key = $this->generateKey('hierarchy_tree', $rootId);

      if ($this->isValid($key)) {
         return $this->cache[$key] ?? null;
      }

      return null;
   }

   /**
    * Almacenar árbol jerárquico en caché
    */
   public function setHierarchyTree(HierarchyTreeDTO $tree, ?int $rootId = null, ?int $ttl = null): void {
      $key = $this->generateKey('hierarchy_tree', $rootId);
      $ttl = $ttl ?? $this->defaultTtl;

      $this->cache[$key] = $tree;
      $this->timestamps[$key] = time() + $ttl;
   }

   /**
    * Obtener estadísticas desde caché
    */
   public function getStatistics(): ?array {
      $key = $this->generateKey('hierarchy_stats');

      if ($this->isValid($key)) {
         return $this->cache[$key] ?? null;
      }

      return null;
   }

   /**
    * Almacenar estadísticas en caché
    */
   public function setStatistics(array $stats, ?int $ttl = null): void {
      $key = $this->generateKey('hierarchy_stats');
      $ttl = $ttl ?? $this->defaultTtl;

      $this->cache[$key] = $stats;
      $this->timestamps[$key] = time() + $ttl;
   }

   /**
    * Obtener contexto de unidad desde caché
    */
   public function getUnitContext(int $unitId): ?array {
      $key = $this->generateKey('unit_context', $unitId);

      if ($this->isValid($key)) {
         return $this->cache[$key] ?? null;
      }

      return null;
   }

   /**
    * Almacenar contexto de unidad en caché
    */
   public function setUnitContext(int $unitId, array $context, ?int $ttl = null): void {
      $key = $this->generateKey('unit_context', $unitId);
      $ttl = $ttl ?? $this->defaultTtl;

      $this->cache[$key] = $context;
      $this->timestamps[$key] = time() + $ttl;
   }

   /**
    * Invalidar toda la caché de jerarquía
    */
   public function flushHierarchy(): void {
      $hierarchyKeys = array_filter(
         array_keys($this->cache),
         fn($key) => str_starts_with($key, 'hierarchy_')
      );

      foreach ($hierarchyKeys as $key) {
         unset($this->cache[$key], $this->timestamps[$key]);
      }
   }

   /**
    * Invalidar caché de una unidad específica
    */
   public function flushUnit(int $unitId): void {
      $unitKeys = array_filter(
         array_keys($this->cache),
         fn($key) => str_contains($key, "_{$unitId}")
      );

      foreach ($unitKeys as $key) {
         unset($this->cache[$key], $this->timestamps[$key]);
      }
   }

   /**
    * Invalidar toda la caché
    */
   public function flushAll(): void {
      $this->cache = [];
      $this->timestamps = [];
   }

   /**
    * Limpiar caché expirada
    */
   public function cleanupExpired(): void {
      $now = time();

      foreach ($this->timestamps as $key => $expiry) {
         if ($expiry <= $now) {
            unset($this->cache[$key], $this->timestamps[$key]);
         }
      }
   }

   /**
    * Obtener información del estado de la caché
    */
   public function getCacheInfo(): array {
      $now = time();
      $valid = 0;
      $expired = 0;

      foreach ($this->timestamps as $expiry) {
         if ($expiry > $now) {
            $valid++;
         } else {
            $expired++;
         }
      }

      return [
         'total_entries' => count($this->cache),
         'valid_entries' => $valid,
         'expired_entries' => $expired,
         'memory_usage' => memory_get_usage(),
         'cache_keys' => array_keys($this->cache)
      ];
   }

   /**
    * Generar clave de caché
    */
   private function generateKey(string $prefix, ?int $id = null): string {
      return $id !== null ? "{$prefix}_{$id}" : $prefix;
   }

   /**
    * Verificar si una entrada de caché es válida
    */
   private function isValid(string $key): bool {
      if (!isset($this->cache[$key]) || !isset($this->timestamps[$key])) {
         return false;
      }

      return $this->timestamps[$key] > time();
   }
}
