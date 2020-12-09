<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "duplicate payment method configuration" route.
 */
class DuplicatePaymentMethodConfiguration extends ControllerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   */
  public function __construct(TranslationInterface $string_translation, EntityFormBuilderInterface $entity_form_builder) {
    $this->entityFormBuilder = $entity_form_builder;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('entity.form_builder'));
  }

  /**
   * Displays a payment method duplication form.
   *
   * @param \Drupal\payment\Entity\PaymentMethodConfigurationInterface $payment_method_configuration
   *
   * @return array
   *   A render array.
   */
  public function execute(PaymentMethodConfigurationInterface $payment_method_configuration) {
    $clone = $payment_method_configuration
      ->createDuplicate();
    $clone->setLabel($this->t('@label (duplicate)', [
      '@label' => $payment_method_configuration->label(),
    ]));

    return $this->entityFormBuilder->getForm($clone, 'default');
  }

  /**
   * Returns the title for the payment method configuration duplicate form.
   *
   * @param \Drupal\payment\Entity\PaymentMethodConfigurationInterface $payment_method_configuration
   *
   * @return string
   */
  public function title(PaymentMethodConfigurationInterface $payment_method_configuration) {
    return $this->t('Duplicate %label', [
      '%label' => $payment_method_configuration->label(),
    ]);
  }

}
