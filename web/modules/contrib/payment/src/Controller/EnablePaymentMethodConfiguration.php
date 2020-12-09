<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Handles the "enable payment method configuration" route.
 */
class EnablePaymentMethodConfiguration extends ControllerBase {

  /**
   * Enables a payment method configuration.
   *
   * @param \Drupal\payment\Entity\PaymentMethodConfigurationInterface $payment_method_configuration
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function execute(PaymentMethodConfigurationInterface $payment_method_configuration) {
    $payment_method_configuration->enable();
    $payment_method_configuration->save();

    return new RedirectResponse($payment_method_configuration->toUrl('collection')->setAbsolute()->toString());
  }

}
