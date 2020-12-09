<?php

namespace Drupal\payment_authnet\Plugin\Payment\Method;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives payment method plugin definitions based on configuration entities.
 *
 * @see \Drupal\payment_authnet\Plugin\Payment\Method\Authnet
 */
class AuthnetDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The payment method configuration manager.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface
   */
  protected $paymentMethodConfigurationManager;

  /**
   * The payment method configuration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentMethodConfigurationStorage;

  /**
   * Constructs a new instance.
   */
  public function __construct(EntityStorageInterface $payment_method_configuration_storage, PaymentMethodConfigurationManagerInterface $payment_method_configuration_manager) {
    $this->paymentMethodConfigurationStorage = $payment_method_configuration_storage;
    $this->paymentMethodConfigurationManager = $payment_method_configuration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($entity_type_manager->getStorage('payment_method_configuration'), $container->get('plugin.manager.payment.method_configuration'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\payment\Entity\PaymentMethodConfigurationInterface[] $payment_methods */
    $payment_methods = $this->paymentMethodConfigurationStorage->loadMultiple();
    foreach ($payment_methods as $payment_method) {
      if ($payment_method->getPluginId() == 'payment_authnet') {
        /** @var \Drupal\payment_authnet\Plugin\Payment\MethodConfiguration\Authnet $configuration_plugin */
        $configuration_plugin = $this->paymentMethodConfigurationManager->createInstance('payment_authnet', $payment_method->getPluginConfiguration());
        $this->derivatives[$payment_method->id()] = [
          'id' => $base_plugin_definition['id'] . ':' . $payment_method->id(),
          'active' => $payment_method->status(),
          'label' => $configuration_plugin->brand_label ?: $payment_method->label(),
          'profile' => $configuration_plugin->profile ?: '',
          'message_text' => $configuration_plugin->getMessageText(),
          'message_text_format' => $configuration_plugin->getMessageTextFormat(),
          'execute_status_id' => $configuration_plugin->execute_status_id,
          'cancel_status_id' => $configuration_plugin->cancel_status_id,
          'cancel_zero_amount' => $configuration_plugin->cancel_zero_amount,
          'capture' => $configuration_plugin->capture,
          'capture_status_id' => $configuration_plugin->capture_status_id,
          'refund' => $configuration_plugin->refund,
          'refund_status_id' => $configuration_plugin->refund_status_id,
          'clone_refunded' => $configuration_plugin->clone_refunded,
          'partial_refund' => $configuration_plugin->partial_refund,
          'partial_refund_status_id' => $configuration_plugin->partial_refund_status_id,
        ] + $base_plugin_definition;
      }
    }

    return $this->derivatives;
  }

}
