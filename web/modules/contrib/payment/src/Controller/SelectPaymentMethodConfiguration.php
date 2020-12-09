<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityAccessControlHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "select a payment method configuration to add" route.
 */
class SelectPaymentMethodConfiguration extends ControllerBase {

  /**
   * The payment method configuration access control handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $paymentMethodConfigurationAccessControlHandler;

  /**
   * The payment method configuration plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface
   */
  protected $paymentMethodConfigurationManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityAccessControlHandlerInterface $payment_method_configuration_access_control_handler
   * @param \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface $payment_method_configuration_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(EntityAccessControlHandlerInterface $payment_method_configuration_access_control_handler, PaymentMethodConfigurationManagerInterface $payment_method_configuration_manager, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->paymentMethodConfigurationAccessControlHandler = $payment_method_configuration_access_control_handler;
    $this->paymentMethodConfigurationManager = $payment_method_configuration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($entity_type_manager->getAccessControlHandler('payment_method_configuration'), $container->get('plugin.manager.payment.method_configuration'), $container->get('current_user'));
  }

  /**
   * Displays a list of available payment method plugins.
   *
   * @return string
   */
  public function execute() {
    $definitions = $this->paymentMethodConfigurationManager->getDefinitions();
    unset($definitions['payment_unavailable']);
    $items = [];
    foreach ($definitions as $plugin_id => $definition) {
      $access = $this->paymentMethodConfigurationAccessControlHandler->createAccess($plugin_id);
      if ($access) {
        $items[] = [
          'title' => $definition['label'],
          'description' => $definition['description'],
          'localized_options' => [],
          'url' => new Url('payment.payment_method_configuration.add', [
            'plugin_id' => $plugin_id,
          ]),
        ];
      }
    }

    return [
      '#theme' => 'admin_block_content',
      '#content' => $items,
    ];
  }

  /**
   * Checks access to self::select().
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $definitions = $this->paymentMethodConfigurationManager->getDefinitions();
    unset($definitions['payment_unavailable']);
    $access_result = AccessResult::forbidden();
    foreach (array_keys($definitions) as $plugin_id) {
      $access_result = $this->paymentMethodConfigurationAccessControlHandler->createAccess($plugin_id, $this->currentUser, [], TRUE);
      if ($access_result->isAllowed()) {
        return $access_result;
      }
    }
    return $access_result;
  }

}
