<?php

namespace Drupal\basic_auth_global;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Defines a service provider for the Basic auth global module.
 */
class BasicAuthGlobalServiceProvider extends ServiceProviderBase
{

    /**
     * {@inheritdoc}
     */
    public function alter(ContainerBuilder $container)
    {
        // Sets the Basic Authentication provider as global.
        $basic_auth_definition = $container->getDefinition('basic_auth.authentication.basic_auth');
        $tags = $basic_auth_definition->getTags();
        $tags['authentication_provider'][0]['global'] = TRUE;
        $basic_auth_definition->setTags($tags);
    }

}
