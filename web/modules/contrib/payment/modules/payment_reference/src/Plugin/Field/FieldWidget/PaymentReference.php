<?php

namespace Drupal\payment_reference\Plugin\Field\FieldWidget;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment_reference\PaymentFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a payment reference field widget.
 *
 * @FieldWidget(
 *   description = @Translation("Allows users to select existing unused payments, or to add a new payment on the fly."),
 *   field_types = {
 *     "payment_reference"
 *   },
 *   id = "payment_reference",
 *   label = @Translation("Payment reference"),
 *   multiple_values = "false"
 * )
 */
class PaymentReference extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The payment reference factory.
   *
   * @var \Drupal\payment_reference\PaymentFactoryInterface
   */
  protected $paymentFactory;

  /**
   * Constructs a new instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param mied[] $settings
   *   The widget settings.
   * @param array[] $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\payment_reference\PaymentFactoryInterface $payment_factory
   *   The payment reference factory.
   */
  public function __construct($plugin_id, array $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, ConfigFactoryInterface $config_factory, AccountInterface $current_user, PaymentFactoryInterface $payment_factory) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->paymentFactory = $payment_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($plugin_id, $plugin_definition, $configuration['field_definition'], $configuration['settings'], $configuration['third_party_settings'], $container->get('config.factory'), $container->get('current_user'), $container->get('payment_reference.payment_factory'));
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('payment_reference.payment_type');
    $payment = $this->paymentFactory->createPayment($this->fieldDefinition);
    $element['target_id'] = array(
      '#default_value' => isset($items[$delta]) ? (int) $items[$delta]->target_id : NULL,
      '#limit_allowed_plugin_ids' => $config->get('limit_allowed_plugins') ? $config->get('allowed_plugin_ids') : NULL,
      '#plugin_selector_id' => $config->get('plugin_selector_id'),
      '#prototype_payment' => $payment,
      '#queue_category_id' => $items->getEntity()->getEntityTypeId() . '.' . $items->getEntity()->bundle() . '.' . $this->fieldDefinition->getName(),
      // The requested user account may contain a string numeric ID.
      '#queue_owner_id' => (int) $this->currentUser->id(),
      '#required' => $this->fieldDefinition->isRequired(),
      '#type' => 'payment_reference',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    return array(
      'target_id' => $form[$this->fieldDefinition->getName()]['widget']['target_id']['#value'],
    );
  }

}
