<?php

namespace Drupal\payment\Entity\Payment;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment deletion form.
 */
class PaymentDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var static $form */
    $form = parent::create($container);
    $form->logger = $container->get('payment.logger');
    $form->stringTranslation = $container->get('string_translation');
    return $form;
  }

  /**
   * Sets the logger.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   */
  public function setLogger(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to delete payment #@payment_id?', array(
      '@payment_id' => $this->getEntity()->id(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getEntity()->delete();
    $this->logger->info('Payment #@payment_id has been deleted.', [
      '@payment_id' => $this->getEntity()->id(),
    ]);
    $this->messenger()->addMessage($this->t('Payment #@payment_id has been deleted.', array(
      '@payment_id' => $this->getEntity()->id(),
    )));
    $form_state->setRedirect('<front>');
  }
}
