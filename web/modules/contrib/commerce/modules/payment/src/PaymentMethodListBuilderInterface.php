<?php

namespace Drupal\commerce_payment;

use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityListBuilderInterface;
use Drupal\user\Entity\User;

/**
 * Interface for the payment methods list builder.
 */
interface PaymentMethodListBuilderInterface extends EntityListBuilderInterface, EntityHandlerInterface {

 /**
   * Set payment methods user owner.
   * 
   * @param Drupal\user\Entity\User $user
   *  The payment methods owner.
   * 
   * @return Drupal\commerce_payment\PaymentMethodListBuilder
   *  The payment method list builder object.
   */
  public function setUser(User $user);

}
