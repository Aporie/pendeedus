<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles the "add payment method configuration" route.
 */
class AddPaymentMethodConfiguration extends ControllerBase {

  /**
   * The payment method configuration plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface
   */
  protected $paymentMethodConfigurationManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a new instance.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationManagerInterface $payment_method_configuration_manager
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(RequestStack $request_stack, TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager, PaymentMethodConfigurationManagerInterface $payment_method_configuration_manager, EntityFormBuilderInterface $entity_form_builder, AccountInterface $current_user) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->paymentMethodConfigurationManager = $payment_method_configuration_manager;
    $this->requestStack = $request_stack;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('request_stack'), $container->get('string_translation'), $container->get('entity_type.manager'), $container->get('plugin.manager.payment.method_configuration'), $container->get('entity.form_builder'), $container->get('current_user'));
  }

  /**
   * Displays a payment method configuration add form.
   *
   * @param string $plugin_id
   *
   * @return array
   */
  public function execute($plugin_id) {
    $payment_method_configuration = $this->entityTypeManager->getStorage('payment_method_configuration')->create([
      'pluginId' => $plugin_id,
    ]);

    return $this->entityFormBuilder->getForm($payment_method_configuration, 'default');
  }

  /**
   * Returns the title for the payment method configuration add form.
   *
   * @param string $plugin_id
   *
   * @return string
   */
  public function title($plugin_id) {
    $plugin_definition = $this->paymentMethodConfigurationManager->getDefinition($plugin_id);

    return $this->t('Add %label payment method configuration', [
      '%label' => $plugin_definition['label'],
    ]);
  }

  /**
   * Checks access to the route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access() {
    $plugin_id = $this->requestStack->getCurrentRequest()->attributes->get('plugin_id');

    return $this->entityTypeManager->getAccessControlHandler('payment_method_configuration')->createAccess($plugin_id, $this->currentUser, [], TRUE);
  }

}
