<?php

namespace Drupal\payment_reference\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Entity\PaymentInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "resume context" route.
 */
class ResumeContext extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(AccountInterface $current_user, TranslationInterface $string_translation) {
    $this->currentUser = $current_user;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_user'), $container->get('string_translation'));
  }

  /**
   * Resumes the payment context.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *
   * @return array
   *   A renderable array.
   */
  public function execute(PaymentInterface $payment) {
    $message = $this->t('You can now <span class="payment_reference-window-close">close this window</span>.');
    if ($payment->access('view')) {
      $message = $this->t('Your payment is %status.', [
          '%status' => $payment->getPaymentStatus()->getPluginDefinition()['label'],
        ]) . ' ' . $message;
    }

    return [
      '#attached' => [
        'library' => [
          'payment_reference/resume_context',
        ],
      ],
      '#type' => 'markup',
      '#markup' => $message,
    ];
  }

  /**
   * Returns the label of a field instance.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *
   * @return string
   */
  public function title(PaymentInterface $payment) {
    return $payment->label();
  }

  /**
   * Checks if the user has access to resume a payment's context.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   */
  public function access(PaymentInterface $payment) {
    return AccessResult::allowedIf($payment->getPaymentType()->resumeContextAccess($this->currentUser));
  }

}
