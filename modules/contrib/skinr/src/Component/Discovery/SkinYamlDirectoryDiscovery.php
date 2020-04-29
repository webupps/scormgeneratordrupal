<?php

/**
 * @file
 * Contains \Drupal\skinr\Component\Discovery\SkinYamlDirectoryDiscovery.
 */

namespace Drupal\skinr\Component\Discovery;

use Drupal\Component\Discovery\DiscoverableInterface;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\FileCache\FileCacheFactory;

/**
 * Discovers multiple YAML files in a set of directories.
 *
 * Once https://www.drupal.org/node/2671034 goes into core we can eliminate this.
 */
class SkinYamlDirectoryDiscovery implements DiscoverableInterface {

  /**
   * An array of directories to scan, keyed by the provider.
   *
   * The value can either be a string or an array of strings. The string values
   * should be the path of a directory to scan.
   *
   * @var array
   */
  protected $directories = [];

  /**
   * The suffix the file cache key.
   *
   * @var string
   */
  protected $fileCacheKeySuffix;

  /**
   * The key which contains the value to key the data by.
   *
   * @var string
   */
  protected $key;

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
    $this->directories = $directories;
    $this->fileCacheKeySuffix = $file_cache_key_suffix;
    $this->key = $key;
  }

  /**
   * {@inheritdoc}
   */
  public function findAll() {
    $all = array();

    $files = $this->findFiles();

    $file_cache = FileCacheFactory::get('yaml_discovery:' . $this->fileCacheKeySuffix);

    // Try to load from the file cache first.
    foreach ($file_cache->getMultiple(array_keys($files)) as $file => $data) {
      $all[$files[$file]][$this->getIdentifier($file, $data)] = $data + ['path' => $file];
      unset($files[$file]);
    }

    // If there are files left that were not returned from the cache, load and
    // parse them now. This list was flipped above and is keyed by filename.
    if ($files) {
      foreach ($files as $file => $provider) {
        // If a file is empty or its contents are commented out, return an empty
        // array instead of NULL for type consistency.
        try {
          $data = Yaml::decode(file_get_contents($file)) ?: [];
        } catch (InvalidDataTypeException $e) {
          throw new SkinDiscoveryException("The $file contains invalid YAML");
        }
        $all[$provider][$this->getIdentifier($file, $data)] = $data + ['path' => $file];
        $file_cache->set($file, $data);
      }
    }

    return $all;
  }

  /**
   * Gets the identifier from the data.
   *
   * @param array $data
   *   The data from the YAML file.
   *
   * @return string
   *   The identifier from the data.
   */
  protected function getIdentifier($file, array $data) {
    if (!isset($data[$this->key])) {
      throw new SkinDiscoveryException("The $file contains no data in the identifier key '{$this->key}'");
    }
    return $data[$this->key];
  }

  /**
   * Returns an array of providers keyed by file path.
   *
   * @return array
   *   An array of providers keyed by file path.
   */
  protected function findFiles() {
    $file_list = [];
    $pattern = '/\.yml$/i';

    foreach ($this->directories as $provider => $directories) {
      $directories = (array) $directories;
      foreach ($directories as $directory) {
        if (is_dir($directory)) {
          $files = file_scan_directory($directory, $pattern);
          foreach ($files as $fileInfo) {
            $file_list[$fileInfo->uri] = $provider;
          }
        }
      }
    }
    return $file_list;
  }

}
