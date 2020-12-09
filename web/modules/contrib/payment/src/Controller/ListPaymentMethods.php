<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "list all payment methods" route.
 */
class ListPaymentMethods extends ControllerBase {

  /**
   * The payment method plugin manager used for testing.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface
   */
  protected $paymentMethodManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\payment\Plugin\Payment\Method\PaymentMethodManagerInterface $payment_method_manager
   */
  public function __construct(TranslationInterface $string_translation, PaymentMethodManagerInterface $payment_method_manager) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('plugin.manager.payment.method'));
  }

  /**
   * Lists all available payment method plugins.
   *
   * @return array
   *   A renderable array.
   */
  public function execute() {
    $rows = [];
    foreach ($this->paymentMethodManager->getDefinitions() as $plugin_id => $definition) {
      $operations_provider = $this->paymentMethodManager->getOperationsProvider($plugin_id);
      $row = [
        'label' => [
          '#markup' => $definition['label'],
        ],
        'status' => [
          '#markup' => $definition['active'] ? $this->t('Enabled') : $this->t('Disabled'),
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => $operations_provider ? $operations_provider->getOperations($plugin_id) : [],
        ],
      ];
      if (!$definition['active']) {
        $row['#attributes']['class'] = ['payment-method-disabled'];
      }
      $rows[$plugin_id] = $row;
    }

    return [
      '#attached' => [
        'library' => [
          'payment/payment_method.list',
        ],
      ],
      '#attributes' => [
        'class' => ['payment-method-list'],
      ],
      '#header' => [$this->t('Name'), $this->t('Status'), $this->t('Operations')],
      '#type' => 'table',
    ] + $rows;
  }

}
