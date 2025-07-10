<?php
/**
 * @package     ff24fd0/app
 * @subpackage  ProviderServices
 * @file        LoggerProvider
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 12:47:52
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

use App\Contracts\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return function (ContainerBuilder $containerBuilder) {
   $containerBuilder->addDefinitions([
      LoggerInterface::class => function (ContainerInterface $container) {
         $settings = $container->get(SettingsInterface::class);

         $loggerSettings = $settings->get('logger');
         $logger = new Logger($settings->get('logger.name', 'app'));

         $processor = new UidProcessor();
         $logger->pushProcessor($processor);

         $handler = new StreamHandler($settings->get('logger.path', 'storage/logs'), $settings->get('logger.level', Logger::DEBUG));
         $logger->pushHandler($handler);

         return $logger;
      }
   ]);

};