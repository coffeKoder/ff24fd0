<?php
/**
 * @package     ff24fd0/app
 * @subpackage  ProviderServices
 * @file        ValidationServiceProvider
 * @author      Fernando Castillo <fdocst@gmail.com>
 * @date        2025-07-10 13:24:31
 * @version     1.0.0
 * @description
 */

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use App\Contracts\SettingsInterface;
use DI\ContainerBuilder;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;


return function (ContainerBuilder $containerBuilder) {
   $containerBuilder->addDefinitions([
      ValidatorInterface::class => function (ContainerInterface $container) {
         return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->setTranslator($container->get(TranslatorInterface::class))
            ->getValidator();
      }
   ]);
};
