<?php

namespace Drupal\config_provider\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\config_provider\Plugin\ConfigProviderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Base class for Configuration provider plugins.
 */
abstract class ConfigProviderBase extends PluginBase implements ConfigProviderInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The active configuration storages, keyed by collection.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $activeStorages;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * {@inheritdoc}
   */
  public function providesFullConfig() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectory() {
    return static::ID;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveStorages(StorageInterface $active_storage) {
    $this->activeStorages[$active_storage->getCollectionName()] = $active_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigManager(ConfigManagerInterface $config_manager) {
    $this->configManager = $config_manager;
  }

  /**
   * Gets the storage for a designated configuration provider.
   *
   * @param string $directory
   *   The configuration directory (for example, config/install).
   * @param string $collection
   *   (optional) The configuration collection. Defaults to the default
   *   collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage that provides the default configuration.
   */
  protected function getExtensionInstallStorage($directory, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return new ExtensionInstallStorage($this->getActiveStorages($collection), $directory, $collection);
  }

  /**
   * Gets the configuration storage that provides the active configuration.
   *
   * @param string $collection
   *   (optional) The configuration collection. Defaults to the default
   *   collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage that provides the default configuration.
   */
  protected function getActiveStorages($collection = StorageInterface::DEFAULT_COLLECTION) {
    if (!isset($this->activeStorages[$collection])) {
      $this->activeStorages[$collection] = reset($this->activeStorages)->createCollection($collection);
    }
    return $this->activeStorages[$collection];
  }

  /**
   * Gets the list of enabled extensions including both modules and themes.
   *
   * @return array
   *   A list of enabled extensions which includes both modules and themes.
   */
  protected function getEnabledExtensions() {
    // Read enabled extensions directly from configuration to avoid circular
    // dependencies on ModuleHandler and ThemeHandler.
    $extension_config = $this->configFactory->get('core.extension');
    $enabled_extensions = (array) $extension_config->get('module');
    $enabled_extensions += (array) $extension_config->get('theme');
    // Core can provide configuration.
    $enabled_extensions['core'] = 'core';
    return array_keys($enabled_extensions);
  }

  /**
   * Validates an array of config data that contains dependency information.
   *
   * @param string $config_name
   *   The name of the configuration object that is being validated.
   * @param array $data
   *   Configuration data.
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   * @param array $all_config
   *   A list of all the active configuration names.
   *
   * @return bool
   *   TRUE if the dependencies are met, FALSE if not.
   */
  protected function validateDependencies($config_name, array $data, array $enabled_extensions, array $all_config) {
    if (isset($data['dependencies'])) {
      $all_dependencies = $data['dependencies'];

      // Ensure enforced dependencies are included.
      if (isset($all_dependencies['enforced'])) {
        $all_dependencies = array_merge($all_dependencies, $data['dependencies']['enforced']);
        unset($all_dependencies['enforced']);
      }
      // Ensure the configuration entity type provider is in the list of
      // dependencies.
      list($provider) = explode('.', $config_name, 2);
      if (!isset($all_dependencies['module'])) {
        $all_dependencies['module'][] = $provider;
      }
      elseif (!in_array($provider, $all_dependencies['module'])) {
        $all_dependencies['module'][] = $provider;
      }

      foreach ($all_dependencies as $type => $dependencies) {
        $list_to_check = [];
        switch ($type) {
          case 'module':
          case 'theme':
            $list_to_check = $enabled_extensions;
            break;
          case 'config':
            $list_to_check = $all_config;
            break;
        }
        if (!empty($list_to_check)) {
          $missing = array_diff($dependencies, $list_to_check);
          if (!empty($missing)) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Returns a list of all configuration items or those of extensions.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A configuration storage.
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   An associative array of Extension objects, keyed by extension name.
   *
   * @return array
   *   An array containing configuration object names.
   */
  protected function listConfig(StorageInterface $storage, array $extensions = []) {
    if (!empty($extensions)) {
      $config_names = [];
      /* @var \Drupal\Core\Extension\Extension $extension */
      foreach ($extensions as $name => $extension) {
        $config_names = array_merge($config_names, array_keys($storage->getComponentNames([
          $name => $extension,
        ])));
      }
    }
    else {
      $config_names = $storage->listAll();
    }

    return $config_names;
  }

}
