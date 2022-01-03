<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Set\ValueObject\DowngradeLevelSetList;
use Rector\Set\ValueObject\LevelSetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src'
    ]);

    // Define what rule sets will be applied
//    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_80);
//    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_74);
//    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_73);
//    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_72);
//    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_71);
    $containerConfigurator->import(DowngradeLevelSetList::DOWN_TO_PHP_70);

    // get services (needed for register a single rule)
    // $services = $containerConfigurator->services();

    // register a single rule
    // $services->set(TypedPropertyRector::class);
};
