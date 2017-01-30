<?php

namespace Drupal\deploy\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ReplicationViewBuilder extends EntityViewBuilder {

  /** @var FormBuilderInterface $formBuilder **/
  protected $formBuilder;

  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('form_builder')
    );  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    unset($build['#theme']);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'FULL', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    $build['info'] = [
      '#prefix' => '<p>',
      '#markup' => $entity->get('source')->entity->label() . ' to ' . $entity->get('target')->entity->label(),
      '#suffix' => '</p>',
    ];
    $form = $this->formBuilder->getForm('\Drupal\deploy\Form\ReplicationActionForm', $entity);
    $build['form'] = $form;

    return $build;
  }

}
