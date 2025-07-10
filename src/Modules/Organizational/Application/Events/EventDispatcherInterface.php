<?php
/**
 * @package     Organizational/Application
 * @subpackage  Events
 * @file        EventDispatcherInterface
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 16:20:00
 * @version     1.0.0
 * @description Interfaz para el despachador de eventos
 */

declare(strict_types=1);

namespace Viex\Modules\Organizational\Application\Events;

interface EventDispatcherInterface {
   /**
    * Despachar un evento
    * @param object $event
    * @return void
    */
   public function dispatch(object $event): void;

   /**
    * Registrar un listener para un evento
    * @param string $eventClass
    * @param callable $listener
    * @return void
    */
   public function addListener(string $eventClass, callable $listener): void;

   /**
    * Obtener listeners para un evento
    * @param string $eventClass
    * @return array
    */
   public function getListeners(string $eventClass): array;
}
