<?php

namespace Drupal\payment\Entity\PaymentStatus;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payments status deletion form.
 */
class PaymentStatusDeleteForm extends EntityConfirmFormBase {

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(TranslationInterface $string_translation, LoggerInterface $logger) {
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('payment.logger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to delete %label?', array(
      '%label' => $this->getEntity()->label(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl('collection');
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
    $this->logger->info('Payment status %label (@id) has been deleted.', [
      '@id' => $this->getEntity()->id(),
      '%label' => $this->getEntity()->label(),
    ]);
    $this->messenger()->addMessage($this->t('%label has been deleted.', array(
      '%label' => $this->getEntity()->label(),
    )));
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
  }
}
