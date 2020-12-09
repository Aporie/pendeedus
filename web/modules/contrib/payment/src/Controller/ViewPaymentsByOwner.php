<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\Payment\PaymentListBuilderInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "view payments by owner" route.
 */
class ViewPaymentsByOwner implements ContainerInjectionInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The payment list builder.
   *
   * @var \Drupal\payment\Entity\Payment\PaymentListBuilderInterface
   */
  protected $paymentListBuilder;

  /**
   * Creates a new instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\payment\Entity\Payment\PaymentListBuilderInterface $payment_list_builder
   */
  public function __construct(AccountInterface $current_user, PaymentListBuilderInterface $payment_list_builder) {
    $this->currentUser = $current_user;
    $this->paymentListBuilder = $payment_list_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($container->get('current_user'), $entity_type_manager->getListBuilder('payment'));
  }

  /**
   * Executes the route.
   *
   * @param \Drupal\user\UserInterface $user
   *
   * @return array
   *   A render array.
   */
  public function execute(UserInterface $user) {
    $this->paymentListBuilder->restrictByOwnerId($user->id());

    return $this->paymentListBuilder->render();
  }

  /**
   * Checks access to the route.
   *
   * @param \Drupal\user\UserInterface $user
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(UserInterface $user) {
    return AccessResult::allowedIf($this->currentUser->id() == $user->id() && $this->currentUser->hasPermission('payment.payment.view.own'))->orIf(AccessResult::allowedIf($this->currentUser->hasPermission('payment.payment.view.any')));
  }

}
