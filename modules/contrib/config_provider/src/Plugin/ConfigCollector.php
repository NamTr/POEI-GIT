<?php

namespace Drupal\config_provider\Plugin;

use Drupal\config_provider\InMemoryStorage;
use Drupal\config_provider\Plugin\ConfigCollectorInterface;
use Drupal\config_provider\Plugin\ConfigProviderManager;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for invoking configuration providers..
 */
class ConfigCollector implements ConfigCollectorInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The configuration provider manager.
   *
   * @var \Drupal\config_provider\Plugin\ConfigProviderManager
   */
  protected $configProviderManager;

  /**
   * The configuration provider plugin instances.
   *
   * @var \Drupal\config_provider\Plugin\ConfigProvider
   */
  protected $configProviders;

  /**
   * Constructor for ConfigCollector objects.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\config_provider\Plugin\ConfigProviderManager $config_provider_manager
   *   The configuration provider manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StorageInterface $active_storage, ConfigManagerInterface $config_manager, ConfigProviderManager $config_provider_manager) {
    $this->configFactory = $config_factory;
    $this->activeStorage = $active_storage;
    $this->configManager = $config_manager;
    $this->configProviderManager = $config_provider_manager;
    $this->configProviders = [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigProviders() {
    if (empty($this->configProviders)) {
      $definitions = $this->configProviderManager->getDefinitions();
      $config_providers = [];
      foreach (array_keys($definitions) as $id) {
        $this->initConfigProviderInstance($id);
      }
    }
    return $this->configProviders;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstallableConfig(array $extensions = []) {
    $config_storage = new InMemoryStorage();

    /* @var \Drupal\config_provider\Plugin\ConfigProviderInterface[] $providers */
    $providers = $this->getConfigProviders();

    foreach ($providers as $provider) {
      $provider->addInstallableConfig($config_storage, $extensions);
    }

    return $config_storage;
  }

  /**
   * Initializes an instance of the specified configuration provider.
   *
   * @param string $id
   *   The string identifier of the configuration provider.
   */
  protected function initConfigProviderInstance($id) {
    if (!isset($this->configProviders[$id])) {
      $instance = $this->configProviderManager->createInstance($id, []);
      $instance->setConfigFactory($this->configFactory);
      $instance->setActiveStorages($this->activeStorage);
      $instance->setConfigManager($this->configManager);
      $this->configProviders[$id] = $instance;
    }
  }

}
