<?php
/**
 * @package     projects/viex-app
 * @subpackage  config
 * @file        logger.config
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-07 22:49:52
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);


return [
   'displayErrorDetails' => true, // Should be set to false in production
   'logError' => false,
   'logErrorDetails' => false,
   'name' => 'viex-app',
   'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../storage/logs/app.log',
   'level' => \Monolog\Logger::DEBUG,

];