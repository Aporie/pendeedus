<?php

namespace Drupal\payment\Entity\PaymentMethodConfiguration;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists payment method configurations..
 */
class PaymentMethodConfigurationListBuilder extends ConfigEntityListBuilder {

  /**
   * The payment method configuration manager.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface
   */
  protected $paymentMethodConfigurationManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $list_builder */
    $list_builder = parent::createInstance($container, $entity_type);
    $list_builder->moduleHandler = $container->get('module_handler');
    $list_builder->paymentMethodConfigurationManager = $container->get('plugin.manager.payment.method_configuration');
    $list_builder->stringTranslation = $container->get('string_translation');
    return $list_builder;
  }

  /**
   * Sets the payment method configuration manager.
   *
   * @param \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface $payment_method_configuration_manager
   *   The payment method configuration manager.
   */
  public function setPaymentMethodConfigurationManager(PaymentMethodConfigurationManagerInterface $payment_method_configuration_manager) {
    $this->paymentMethodConfigurationManager = $payment_method_configuration_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $row['label'] = [
      'data' => $this->t('Name'),
    ];
    $row['plugin'] = [
      'data' => $this->t('Type'),
    ];
    $row['owner'] = array(
      'data' => $this->t('Owner'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $row['status'] = array(
      'data' => $this->t('Status'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    $row['operations'] = [
      'data' => $this->t('Operations'),
    ];

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\payment\Entity\PaymentMethodConfigurationInterface $payment_method_configuration */
    $payment_method_configuration = $entity;

    $row['data']['label'] = $payment_method_configuration->label();

    $plugin_definition = $this->paymentMethodConfigurationManager->getDefinition($payment_method_configuration->getPluginId());
    $row['data']['plugin'] = isset($plugin_definition['label']) ? $plugin_definition['label'] : $this->t('Unknown');

    $row['data']['owner']['data'] = array(
      '#theme' => 'username',
      '#account' => $payment_method_configuration->getOwner(),
    );

    $row['data']['status'] = $payment_method_configuration->status() ? $this->t('Enabled') : $this->t('Disabled');

    $operations = $this->buildOperations($entity);
    $row['data']['operations']['data'] = $operations;

    if (!$payment_method_configuration->status()) {
      $row['class']= array('payment-method-configuration-disabled');
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    foreach (array('enable', 'disable') as $operation) {
      if (!$entity->access($operation)) {
        unset($operations[$operation]);
      }
    }
    if ($entity->access('duplicate')) {
      $operations['duplicate'] = array(
        'title' => $this->t('Duplicate'),
        'weight' => 99,
        'url' => $entity->toUrl('duplicate-form'),
      );
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#attached']['library'][] = 'payment/payment_method_configuration.list';
    $build['table']['#attributes']['class'][] = 'payment-method-configuration-list';
    $build['table']['#empty'] = $this->t('There is no payment method configuration yet.');

    return $build;
  }
}
