<?php
/**
 * @package     ff24fd0/app
 * @subpackage  Bootstrap
 * @file        Settings
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 09:43:53
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace App\Bootstrap;
use App\Contracts\SettingsInterface;

class Settings implements SettingsInterface {

   private $settings = [];

   function __construct(string $path) {
      $path = realpath($path) ?: __DIR__ . '/../../config';
      $this->loadSettings($path);
   }

   private function loadSettings(string $path): void {
      if (!file_exists($path)) {
         throw new \Exception("Ruta de configuraciones es inaccesible: $path");
      }

      // dentro de la ruta de configuraciones, se esperan varios archivos *.config.php
      $files = glob($path . '/*.config.php');
      if (empty($files)) {
         throw new \Exception("No se encontraron archivos de configuración en: $path");
      }

      foreach ($files as $file) {
         // carga el archivo de configuracion y se asegura de que sea un array
         $configFile = require $file;
         if (!is_readable($file) || !is_array($configFile)) {
            throw new \Exception("Archivo de configuración no es legible o no es un array: $file");
         }

         // extrae el nombre del archivo sin el sufijo .config.php
         $fileName = basename($file, '.config.php');
         $this->settings[$fileName] = $configFile;
      }
   }

   public function get(string $key, mixed $default = null): mixed {
      $keys = explode('.', $key);
      $value = $this->settings;

      foreach ($keys as $segment) {
         if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
         }
         $value = $value[$segment];
      }

      return $value;
   }

   public function set(string $key, mixed $value): void {
      $keys = explode('.', $key);
      $config = &$this->settings;

      foreach ($keys as $segment) {
         if (!isset($config[$segment]) || !is_array($config[$segment])) {
            $config[$segment] = [];
         }
         $config = &$config[$segment];
      }

      $config = $value;
   }

   public function has(string $key): bool {
      $keys = explode('.', $key);
      $value = $this->settings;

      foreach ($keys as $segment) {
         if (!is_array($value) || !array_key_exists($segment, $value)) {
            return false;
         }
         $value = $value[$segment];
      }

      return true;
   }
}