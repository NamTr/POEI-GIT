<?php

namespace Drupal\config_sync\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\config_sync\ConfigSyncInitializerInterface;
use Drupal\config_sync\ConfigSyncListerInterface;
use Drupal\config_update\ConfigListInterface as ConfigUpdateListerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigSyncInitialize extends FormBase {

  /**
   * The configuration synchronizer initializer.
   *
   * @var \Drupal\config_sync\configSyncInitializerInterface
   */
  protected $configSyncInitializer;

  /**
   * The configuration synchronizer lister.
   *
   * @var \Drupal\config_sync\configSyncListerInterface
   */
  protected $configSyncLister;

  /**
   * The configuration update lister.
   *
   * @var \Drupal\config_update\ConfigListInterface
   */
  protected $configUpdateLister;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new ConfigSync object.
   *
   * @param \Drupal\config_sync\ConfigSyncInitializerInterface $config_sync_initializer
   *   The configuration syncronizer initializer.
   * @param \Drupal\config_sync\ConfigSyncListerInterface $config_sync_lister
   *   The configuration syncronizer lister.
   * @param \Drupal\config_update\ConfigListInterface $config_update_lister
   *   The configuration update lister.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigSyncInitializerInterface $config_sync_initializer, ConfigSyncListerInterface $config_sync_lister, ConfigUpdateListerInterface $config_update_lister, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->configSyncInitializer = $config_sync_initializer;
    $this->configSyncLister = $config_sync_lister;
    $this->configUpdateLister = $config_update_lister;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config_sync.initializer'),
      $container->get('config_sync.lister'),
      $container->get('config_update.config_list'),
      $container->get('module_handler'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_sync_initialize';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $changelists = $this->configSyncLister->getExtensionChangelists();

    if (!empty($changelists)) {

      foreach ($changelists as $type => $extension_changelists) {
        $form[$type] = $this->buildUpdatesListing($type, $extension_changelists);
      }
      $form['retain_active_overrides'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Retain customizations'),
        '#default_value' => TRUE,
        '#description' => $this->t('If you select this option, configuration updates will be merged into the active configuration, retaining any customizations.'),
      ];

      $form['message'] = [
        '#markup' => $this->t('Use the button below to initialize data to be imported from updated modules and themes.'),
      ];

      $form['actions'] = [
        '#type' => 'actions',
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Initialize'),
      ];
    }
    else {
      $form['message'] = [
        '#markup' => $this->t('No configuration updates are available from installed modules and themes.'),
      ];
    }

    return $form;
  }

  /**
   * Builds the portion of the form showing a listing of updates.
   *
   * @param string $type
   *   The type of extension (module or theme).
   * @param array $extension_changelists
   *   Associative array of configuration changes keyed by extension name.
   *
   * @return array
   *   A render array of a form element.
   */
  protected function buildUpdatesListing($type, array $extension_changelists) {
    $type_labels = [
      'module' => $this->t('Module'),
      'theme' => $this->t('Theme'),
    ];
    $header = [
      'name' => [
        'data' => $this->t('@type name', ['@type' => $type_labels[$type]]),
      ],
      'details' => [
        'data' => $this->t('Available configuration changes'), 'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $options = [];
    $rows = [];

    foreach ($extension_changelists as $name => $changelist) {
      $options[$name] = $this->buildExtensionDetail($type, $name, $changelist);
    }

    $element = [
      // @todo: convert to a tableselect once we refresh the extension
      // snapshot values of a given config item on update.
      // '#type' => 'tableselect',
      '#type' => 'table',
      '#header' => $header,
      // '#options' => $options,
      '#rows' => $options,
      '#attributes' => ['class' => ['config-sync-listing']],
      '#default_value' => array_fill_keys(array_keys($options), TRUE),
    ];

    return $element;
  }

  /**
   * Builds the details of a package.
   *
   * @param string $type
   *   The type of extension (module or theme).
   * @param string $name
   *   The machine name of the extension.
   * @param array $changelist
   *   Associative array of configuration changes keyed by the type of change.
   *
   * @return array
   *   A render array of a form element.
   */
  protected function buildExtensionDetail($type, $name, $changelist) {
    switch ($type) {
      case 'module':
        $label = $this->moduleHandler->getName($name);
        break;
      case 'theme':
        $label = $this->themeHandler->getName($name);
        break;
    }

    $element['name'] = [
      'data' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $label,
      ],
      'class' => ['config-sync-extension-name'],
    ];

    $extension_config = [];
    foreach (['create', 'update'] as $change_type) {
      if (isset($changelist[$change_type])) {
        $extension_config[$change_type] = [];
        foreach ($changelist[$change_type] as $item_name => $item_label) {
          $config_type = $this->configUpdateLister->getTypeNameByConfigName($item_name);
          if (!$config_type) {
            $config_type = 'system_simple';
          }

          if (!isset($extension_config[$change_type][$config_type])) {
            $extension_config[$change_type][$config_type] = [];
          }
          $extension_config[$change_type][$config_type][$item_name] = [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $item_label,
            '#attributes' => [
              'title' => $item_name,
              'class' => ['config-sync-item'],
            ],
          ];
        }
      }
    }

    $rows = [];

    $change_type_labels = [
      'create' => $this->t('New'),
      'update' => $this->t('Updated'),
    ];

    // List config types for order.
    $config_types = $this->configSyncLister->listConfigTypes();

    foreach ($extension_config as $change_type => $change_type_data) {

      $rows[] = [
        [
          'data' => [
            '#type' => 'html_tag',
            '#tag' => 'strong',
            '#value' => $change_type_labels[$change_type],
          ],
          'colspan' => 2,
        ],
      ];

      foreach ($config_types as $config_type => $config_type_label) {
        if (isset($change_type_data[$config_type])) {
          $row = [];
          $row[] = [
            'data' => [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => $config_type_label,
              '#attributes' => [
                'title' => $config_type,
                'class' => ['config-sync-item-label'],
              ],
            ],
          ];
          $row[] = [
            'data' => [
              '#theme' => 'item_list',
              '#items' => $change_type_data[$config_type],
              '#context' => [
                'list_style' => 'comma-list',
              ],
            ],
            'class' => ['item'],
          ];
          $rows[] = $row;
        }

      }

    }

    $element['details'] = [
      'data' => [
        '#type' => 'table',
        '#rows' => $rows,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo: feed second argument to ::initialize.
    $this->configSyncInitializer->initialize($form_state->getValue('retain_active_overrides'));
    $form_state->setRedirect('config_sync.import');
  }

}
