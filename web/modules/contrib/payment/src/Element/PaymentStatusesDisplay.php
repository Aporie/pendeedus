<?php

namespace Drupal\payment\Element;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\currency\FormElementCallbackTrait;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an element to display payment statuses.
 *
 * @RenderElement("payment_statuses_display")
 */
class PaymentStatusesDisplay extends FormElement implements ContainerFactoryPluginInterface {

  use FormElementCallbackTrait;

  /**
   * The fate formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $string_translation, DateFormatter $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->dateFormatter = $date_formatter;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('string_translation'), $container->get('date.formatter'));
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $plugin_id = $this->getPluginId();

    return array(
      // An array of
      // \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface instances.
      '#payment_statuses' => [],
      '#pre_render' => [[get_class($this), 'instantiate#preRender#' . $plugin_id]],
    );
  }

  /**
   * Implements form #pre_render callback.
   *
   * @throws \InvalidArgumentException
   */
  public function preRender(array $element) {
    if (!isset($element['#payment_statuses']) || !is_array($element['#payment_statuses'])) {
      throw new \InvalidArgumentException('#payment_statuses must be an array of \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface instances.');
    }
    $element['table'] = array(
      '#empty' => $this->t('There are no statuses.'),
      '#header' => array($this->t('Status'), $this->t('Date')),
      '#type' => 'table',
    );
    /** @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface $status */
    foreach ($element['#payment_statuses'] as $delta => $payment_status) {
      if (!$payment_status instanceof PaymentStatusInterface) {
        $type = is_object($payment_status) ? get_class($payment_status) : gettype($payment_status);
        throw new \InvalidArgumentException(sprintf('#payment_statuses must be an array of \Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface instances, but the array contained %s.', $type));
      }
      $definition = $payment_status->getPluginDefinition();
      $element['table']['status_' . $delta] = array(
        '#attributes' => array(
          'class' => array(
            'payment-status-plugin-' . $payment_status->getPluginId(),
          ),
        ),
        'label' => array(
          '#attributes' => array(
            'class' => array('payment-status-label'),
          ),
          '#markup' => $definition['label'],
        ),
        'created' => array(
          '#attributes' => array(
            'class' => array('payment-line-item-quantity'),
          ),
          '#markup' => $this->dateFormatter->format($payment_status->getCreated()),
        ),
      );
    }

    return $element;
  }

}
