<?php
/**
 * @package     ff24fd0/app
 * @subpackage  Bootstrap
 * @file        Container
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 09:43:49
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace App\Bootstrap;

use DI\ContainerBuilder;
use DI\Container as ContainerDI;
use App\Contracts\SettingsInterface;
use App\Bootstrap\Settings;

class Container {
   private $container;


   public function __construct() {
      $this->container = new ContainerBuilder();
      $this->container->useAutowiring(true);
      $this->baseDefinitions();
   }

   private function baseDefinitions(): self {
      $this->container->addDefinitions([
            // Aquí se pueden agregar definiciones base
         SettingsInterface::class => function (): Settings {
            return new Settings(__DIR__ . '/../../config');
         },
         // Otras definiciones base pueden ir aquí
      ]);
      return $this;
   }

   // metodo que recibe un archivo de definiciones que retorna una funcion anonima
   private function addDefinitions(): self {
      // recorre el directorio de app/ProviderServices y agrega los archivos que terminan en .php
      $files = glob(__DIR__ . '/../ProviderServices/*.php');

      foreach ($files as $file) {
         $definitions = require $file;
         if (!is_callable($definitions)) {
            throw new \InvalidArgumentException("El archivo de definiciones debe retornar una función.");
         }
         // el archivo debe retornar una función anoniima que recibe el contenedor y retorna un array de definiciones
         if (!is_callable($definitions)) {
            throw new \InvalidArgumentException("El archivo de definiciones debe retornar una función anónima.");
         }
         $definitions($this->container);
      }

      return $this;
   }

   public function container(): ContainerDI {
      // carga las definiciones dinamicamente
      $this->addDefinitions();
      try {
         return $this->container->build();
      } catch (\Exception $e) {
         throw new \RuntimeException("Error al construir el contenedor: " . $e->getMessage(), 0, $e);
      }
   }

}