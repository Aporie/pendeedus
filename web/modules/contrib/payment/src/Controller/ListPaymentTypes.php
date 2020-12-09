<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "list payment types" route.
 */
class ListPaymentTypes extends ControllerBase {

  /**
   * The payment type plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface
   */
  protected $paymentTypeManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface $payment_type_manager
   *   The payment type plugin manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(ModuleHandlerInterface $module_handler, PaymentTypeManagerInterface $payment_type_manager, AccountInterface $current_user, TranslationInterface $string_translation) {
    $this->moduleHandler = $module_handler;
    $this->paymentTypeManager = $payment_type_manager;
    $this->stringTranslation = $string_translation;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('module_handler'), $container->get('plugin.manager.payment.type'), $container->get('current_user'), $container->get('string_translation'));
  }

  /**
   * Displays a list of available payment types.
   *
   * @return array
   *   A render array.
   */
  public function execute() {
    $table = [
      '#empty' => $this->t('There are no available payment types.'),
      '#header' => [$this->t('Type'), $this->t('Description'), $this->t('Operations')],
      '#type' => 'table',
    ];
    $definitions = $this->paymentTypeManager->getDefinitions();
    unset($definitions['payment_unavailable']);
    foreach ($definitions as $plugin_id => $definition) {
      $operations_provider = $this->paymentTypeManager->getOperationsProvider($plugin_id);
      $operations = $operations_provider ? $operations_provider->getOperations($plugin_id) : [];

      // Add the payment type's global configuration operation.
      $operations['configure'] = [
        'url' => new Url('payment.payment_type', [
          'bundle' => $plugin_id,
        ]),
        'title' => $this->t('Configure'),
      ];

      // Add Field UI operations.
      if ($this->moduleHandler->moduleExists('field_ui')) {
        if ($this->currentUser->hasPermission('administer payment fields')) {
          $operations['manage-fields'] = [
            'title' => $this->t('Manage fields'),
            'url' => new Url('entity.payment.field_ui_fields', [
              'bundle' => $plugin_id,
            ]),
          ];
        }
        if ($this->currentUser->hasPermission('administer payment form display')) {
          $operations['manage-form-display'] = [
            'title' => $this->t('Manage form display'),
            'url' => new Url('entity.entity_form_display.payment.default', [
              'bundle' => $plugin_id,
            ]),
          ];
        }
        if ($this->currentUser->hasPermission('administer payment display')) {
          $operations['manage-display'] = [
            'title' => $this->t('Manage display'),
            'url' => new Url('entity.entity_view_display.payment.default', [
              'bundle' => $plugin_id,
            ]),
          ];
        }
      }

      $table[$plugin_id]['label'] = [
        '#markup' => $definition['label'],
      ];
      $table[$plugin_id]['description'] = [
        '#markup' => isset($definition['description']) ? $definition['description'] : NULL,
      ];
      $table[$plugin_id]['operations'] = [
        '#links' => $operations,
        '#type' => 'operations',
      ];
    }

    return $table;
  }

}
