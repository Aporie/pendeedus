<?php

namespace Drupal\payment\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ConfigurableActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Entity\PaymentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets a status on a payment.
 *
 * @Action(
 *   id = "payment_line_item_unset",
 *   label = @Translation("Delete a line item"),
 *   type = "payment"
 * )
 */
class UnsetLineItem extends ConfigurableActionBase {

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed[] $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('string_translation'));
  }

  /**
   * {@inheritdoc}
   */
  public function execute(PaymentInterface $payment = NULL) {
    if ($payment) {
      $payment->unsetLineItem($this->configuration['line_item_name']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'line_item_name' => '',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['line_item_name'] = array(
      '#default_value' => $this->configuration['line_item_name'],
      '#required' => TRUE,
      '#title' => $this->t('Line item name'),
      '#type' => 'textfield',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['line_item_name'] = $values['line_item_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function access($payment, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($payment instanceof PaymentInterface) {
      return $payment->access('update', $account, $return_as_object);
    }
    $access = AccessResult::forbidden();
    return $return_as_object ? $access : $access->isAllowed();
  }

}
