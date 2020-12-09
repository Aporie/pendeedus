<?php

namespace Drupal\payment\Entity\PaymentMethodConfiguration;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks access for payment method configurations.
 */
class PaymentMethodConfigurationAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function __construct(EntityTypeInterface $entity_type, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('module_handler'));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $payment_method_configuration, $operation, AccountInterface $account) {
    /** @var \Drupal\payment\Entity\PaymentMethodConfigurationInterface $payment_method_configuration */
    if ($operation == 'enable') {
      return AccessResult::allowedIf(!$payment_method_configuration->status())->andIf($this->access($payment_method_configuration, 'update', $account, TRUE))->addCacheableDependency($payment_method_configuration);
    }
    elseif ($operation == 'disable') {
      return AccessResult::allowedIf($payment_method_configuration->status())->andIf($this->access($payment_method_configuration, 'update', $account, TRUE))->addCacheableDependency($payment_method_configuration);
    }
    elseif ($operation == 'duplicate') {
      return $this->createAccess($payment_method_configuration->bundle(), $account, [], TRUE)->andIf($this->access($payment_method_configuration, 'view', $account, TRUE));
    }
    else {
      $permission_prefix = 'payment.payment_method_configuration.' . $operation;
      return AccessResult::allowedIfHasPermission($account, $permission_prefix . '.any')
        ->orIf(
          AccessResult::allowedIfHasPermission($account, $permission_prefix . '.own')
            ->andIf(AccessResult::allowedIf($account->id() == $payment_method_configuration->getOwnerId())->addCacheableDependency($payment_method_configuration))
        );
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'payment.payment_method_configuration.create.' . $bundle);
  }

  /**
   * {@inheritdoc}
   */
  protected function getCache($cid, $operation, $langcode, AccountInterface $account) {
    // Disable the cache, because the intensive operations are cached elsewhere
    // already and the results of all other operations are too volatile to
    // cache.
  }
}
