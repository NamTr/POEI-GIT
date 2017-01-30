<?php

namespace Drupal\administerusersbyrole;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides dynamic permissions of the administerusersbyrole module.
 */
class AdministerusersbyrolePermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new AdministerusersbyrolePermissions instance.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'));
  }

  /**
   * Returns an array of administerusersbyrole permissions.
   *
   * @return array
   */
  public function permissions() {
    $roles = user_roles(TRUE);
    $perms = [];
    $ops = array('edit' => t('Edit'), 'cancel' => t('Cancel'));

    foreach ($roles as $rid => $role) {
      if ($role->isAdmin()) {
        // Exclude the admin role.  Once you can edit an admin, you can set their password, log in and do anything,
        // which defeats the point of using this module.
        continue;
      }

      foreach ($ops as $op => $operation) {
        $perm_string = _administerusersbyrole_build_perm_string($rid, $op);
        if ($rid == AccountInterface::AUTHENTICATED_ROLE) {
          $perm_title = $this->t("@operation users with no custom roles", array(
            '@operation' => $operation,
          ));
        }
        else {
          $perm_title = $this->t("@operation users with role %role", array(
            '@operation' => $operation,
            '%role' => $role->label(),
          ));
        }
        $perms[$perm_string] = array('title' => $perm_title);
      }
    }

    return $perms;
  }
}
