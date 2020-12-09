<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "edit payment method configuration" route.
 */
class EditPaymentMethodConfiguration extends ControllerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'));
  }

  /**
   * Returns the title for the payment method configuration edit form.
   *
   * @param \Drupal\payment\Entity\PaymentMethodConfigurationInterface $payment_method_configuration
   *
   * @return string
   */
  public function title(PaymentMethodConfigurationInterface $payment_method_configuration) {
    return $this->t('Edit %label', [
      '%label' => $payment_method_configuration->label(),
    ]);
  }

}
