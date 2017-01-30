<?php

namespace Drupal\config_provider\Plugin\ConfigProvider;

use Drupal\config_provider\InMemoryStorage;
use Drupal\config_provider\Plugin\ConfigProviderBase;
use Drupal\Core\Config\Entity\ConfigEntityDependency;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for providing configuration from an install directory.
 *
 * @ConfigProvider(
 *   id = \Drupal\config_provider\Plugin\ConfigProvider\ConfigProviderOptional::ID,
 *   weight = 10,
 *   label = @Translation("Optional"),
 *   description = @Translation("Configuration to be installed when an extension is installed."),
 * )
 */
class ConfigProviderOptional extends ConfigProviderBase {

  /**
   * The configuration provider ID.
   */
  const ID = InstallStorage::CONFIG_OPTIONAL_DIRECTORY;

  /**
   * {@inheritdoc}
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    // Optional configuration is installed subsequently, so we can't add it
    // here.
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(InMemoryStorage $installable_config, array $extensions = []) {
    // This method is adapted from ConfigInstaller::installOptionalConfig().
    // Non-default configuration collections are not supported for
    // config/optional.
    $storage = $this->getExtensionInstallStorage(static::ID);

    $enabled_extensions = $this->getEnabledExtensions();
    $existing_config = $this->getActiveStorages()->listAll();

    $list = $this->listConfig($storage, $extensions);

    $list = array_filter($list, function($config_name) use ($existing_config) {
      // Only list configuration that:
      // - does not already exist
      // - is a configuration entity (this also excludes config that has an
      //   implicit dependency on modules that are not yet installed)
      return !in_array($config_name, $existing_config) && $this->configManager->getEntityTypeIdByName($config_name);
    });

    $all_config = array_merge($existing_config, $list);
    // Merge in the configuration provided already by previous config
    // providers.
    $all_config = array_merge($all_config, $installable_config->listAll());
    $all_config = array_combine($all_config, $all_config);
    $config_to_create = $storage->readMultiple($list);

    // Sort $config_to_create in the order of the least dependent first.
    $dependency_manager = new ConfigDependencyManager();
    $dependency_manager->setData($config_to_create);
    $config_to_create = array_merge(array_flip($dependency_manager->sortAll()), $config_to_create);

    foreach ($config_to_create as $config_name => $data) {
      // Remove configuration where its dependencies cannot be met.
      $remove = !$this->validateDependencies($config_name, $data, $enabled_extensions, $all_config);
      // If $dependency is defined, remove configuration that does not have a
      // matching dependency.
      if (!$remove && !empty($dependency)) {
        // Create a light weight dependency object to check dependencies.
        $config_entity = new ConfigEntityDependency($config_name, $data);
        $remove = !$config_entity->hasDependency(key($dependency), reset($dependency));
      }

      if ($remove) {
        // Remove from the list of configuration to create.
        unset($config_to_create[$config_name]);
        // Remove from the list of all configuration. This ensures that any
        // configuration that depends on this configuration is also removed.
        unset($all_config[$config_name]);
      }
      else {
        $installable_config->write($config_name, $data);
      }
    }

  }

}
