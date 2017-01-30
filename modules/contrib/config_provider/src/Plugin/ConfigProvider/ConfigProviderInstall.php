<?php

namespace Drupal\config_provider\Plugin\ConfigProvider;

use Drupal\config_provider\InMemoryStorage;
use Drupal\config_provider\Plugin\ConfigProviderBase;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for providing configuration from an install directory.
 *
 * @ConfigProvider(
 *   id = \Drupal\config_provider\Plugin\ConfigProvider\ConfigProviderInstall::ID,
 *   weight = -10,
 *   label = @Translation("Install"),
 *   description = @Translation("Configuration to be installed when an extension is installed."),
 * )
 */
class ConfigProviderInstall extends ConfigProviderBase {

  /**
   * The configuration provider ID.
   */
  const ID = InstallStorage::CONFIG_INSTALL_DIRECTORY;

  /**
   * {@inheritdoc}
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    // The caller will aready have loaded config for install.
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(InMemoryStorage $installable_config, array $extensions = []) {
    $storage = $this->getExtensionInstallStorage(static::ID);
    $config_names = $this->listConfig($storage, $extensions);

    $config_to_add = $storage->readMultiple($config_names);
    foreach ($config_to_add as $name => $data) {
      $installable_config->write($name, $data);
    }

    // Get all data from the remaining collections.
    // Gather information about all the supported collections.
    $collection_info = $this->configManager->getConfigCollectionInfo();

    foreach ($collection_info->getCollectionNames() as $collection) {
      $collection_storage = $storage->createCollection($collection);
      $config_names = $this->listConfig($collection_storage, $extensions);

      $config_to_add = $collection_storage->readMultiple($config_names);
      foreach ($config_to_add as $name => $data) {
        $installable_config->writeToCollection($name, $data, $collection);
      }
    }

  }

}
