<?php

namespace Drupal\payment\Plugin\views\argument_validator;

use Drupal\Core\Session\AccountInterface;
use Drupal\user\Plugin\views\argument_validator\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates whether the current user has access to view a user's payments.
 *
 * @ViewsArgumentValidator(
 *   id = "payment_view_payments_by_owner",
 *   title = @Translation("Access to view a user's payments"),
 *   entity_type = "user"
 * )
 */
class ViewPaymentsByOwner extends User {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $argument_validator = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $argument_validator->currentUser = $container->get('current_user');
    return $argument_validator;
  }

  /**
   * Sets the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($argument) {
    if (!parent::validateArgument($argument)) {
      return FALSE;
    }

    // Extract the IDs from the argument. See parent::validateArgument().
    if ($this->multipleCapable && $this->options['multiple']) {
      $user_ids = array_filter(preg_split('/[,+ ]/', $argument));
    }
    else {
      $user_ids = [$argument];
    }

    // Allow access when the current user has access to view all payments, or
    // when the current user only tries to view their own payments and has
    // permission to do so.
    return [$this->currentUser->id()] == $user_ids && $this->currentUser->hasPermission('payment.payment.view.own') || $this->currentUser->hasPermission('payment.payment.view.any');
  }

}
