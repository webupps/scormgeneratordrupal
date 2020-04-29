<?php

/**
 * @file
 * Contains \Drupal\skinr\Plugin\Discovery\SkinYamlDirectoryDiscovery.
 */

namespace Drupal\skinr\Plugin\Discovery;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\skinr\Component\Discovery\SkinYamlDirectoryDiscovery as ComponentSkinYamlDirectoryDiscovery;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Allows YAML files to define plugin definitions.
 *
 * If the value of a key (like title) in the definition is translatable then
 * the addTranslatableProperty() method can be used to mark it as such and also
 * to add translation context. Then
 * \Drupal\Core\StringTranslation\TranslatableMarkup will be used to translate
 * the string and also to mark it safe. Only strings written in the YAML files
 * should be marked as safe, strings coming from dynamic plugin definitions
 * potentially containing user input should not.
 *
 * Once https://www.drupal.org/node/2671034 goes into core we can eliminate this.
 */
class SkinYamlDirectoryDiscovery extends YamlDiscovery {

  /**
   * Constructs a YamlDirectoryDiscovery object.
   *
   * @param array $directories
   *   An array of directories to scan, keyed by the provider.
   * @param string $file_cache_key_suffix
   *   The file cache key suffix. This should be unique for each type of
   *   discovery.
   * @param string $key
   *   (optional) The key contained in the discovered data that identifies it.
   *   Defaults to 'id'.
   */
  public function __construct(array $directories, $file_cache_key_suffix, $key = 'id') {
    // Intentionally does not call parent constructor as this class uses a
    // different YAML discovery.
    $this->discovery = new ComponentSkinYamlDirectoryDiscovery($directories, $file_cache_key_suffix, $key);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $plugins = $this->discovery->findAll();

    // Flatten definitions into what's expected from plugins.
    // @todo Fix translatable to work for multi-levels.
    $definitions = array();
    foreach ($plugins as $provider => $list) {
      foreach ($list as $id => $definition) {
        // Add TranslatableMarkup.
        foreach ($this->translatableProperties as $property => $context_key) {
          if (isset($definition[$property])) {
            $options = [];
            // Move the t() context from the definition to the translation
            // wrapper.
            if ($context_key && isset($definition[$context_key])) {
              $options['context'] = $definition[$context_key];
              unset($definition[$context_key]);
            }
            $definition[$property] = new TranslatableMarkup($definition[$property], [], $options);
          }
        }
        // Add ID and provider.
        $definitions[$id] = $definition + array(
          'provider' => $provider,
          'id' => $id,
        );
      }
    }

    return $definitions;
  }

}
