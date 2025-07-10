<?php
/**
 * @package     Organizational/Application
 * @subpackage  Events
 * @file        SimpleEventDispatcher
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:25:00
 * @version     1.0.0
 * @description Implementación simple del despachador de eventos
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\Events;

class SimpleEventDispatcher implements EventDispatcherInterface {
   private array $listeners = [];

   /**
    * Despachar un evento
    */
   public function dispatch(object $event): void {
      $eventClass = get_class($event);
      
      if (!isset($this->listeners[$eventClass])) {
         return;
      }

      foreach ($this->listeners[$eventClass] as $listener) {
         try {
            $listener($event);
         } catch (\Throwable $e) {
            // Log el error pero no interrumpir el flujo
            error_log("Error en listener para evento {$eventClass}: " . $e->getMessage());
         }
      }
   }

   /**
    * Registrar un listener para un evento
    */
   public function addListener(string $eventClass, callable $listener): void {
      if (!isset($this->listeners[$eventClass])) {
         $this->listeners[$eventClass] = [];
      }

      $this->listeners[$eventClass][] = $listener;
   }

   /**
    * Obtener listeners para un evento
    */
   public function getListeners(string $eventClass): array {
      return $this->listeners[$eventClass] ?? [];
   }

   /**
    * Limpiar todos los listeners
    */
   public function clearListeners(): void {
      $this->listeners = [];
   }

   /**
    * Remover listeners para un evento específico
    */
   public function removeListeners(string $eventClass): void {
      unset($this->listeners[$eventClass]);
   }
}
