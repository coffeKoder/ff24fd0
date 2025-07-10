<?php
/**
 * @package     ff24fd0/app
 * @subpackage  ProviderServices
 * @file        SessionServiceProvider
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 13:00:30
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);
use Aura\Session\Session;
use Aura\Session\SessionFactory;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use App\Contracts\SettingsInterface;

return function (ContainerBuilder $containerBuilder) {
   $containerBuilder->addDefinitions([
      Session::class => function (ContainerInterface $container) {
         $settings = $container->get(SettingsInterface::class);
         $sessionFactory = new SessionFactory();
         $session = $sessionFactory->newInstance($_COOKIE);
         return $session;
      }
   ]);

};