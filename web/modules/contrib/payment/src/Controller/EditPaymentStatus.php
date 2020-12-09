<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Entity\PaymentStatusInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "edit payment status" route.
 */
class EditPaymentStatus extends ControllerBase {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
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
   * Returns the title for the edit page.
   *
   * @param \Drupal\payment\Entity\PaymentStatusInterface $payment_status
   *
   * @return string
   */
  public function title(PaymentStatusInterface $payment_status) {
    return $this->t('Edit %label', [
      '%label' => $payment_status->label(),
    ]);
  }

}
