<?php
/**
 * @package     ff24fd0/app
 * @subpackage  Contracts
 * @file        SettingsInterface
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 09:44:19
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

namespace App\Contracts;

interface SettingsInterface {
   public function get(string $key, mixed $default = null): mixed;
   public function set(string $key, mixed $value): void;
   public function has(string $key): bool;
}