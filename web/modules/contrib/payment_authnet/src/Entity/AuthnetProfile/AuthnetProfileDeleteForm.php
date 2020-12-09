<?php

namespace Drupal\payment_authnet\Entity\AuthnetProfile;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Authnet profile deletion form.
 */
class AuthnetProfileDeleteForm extends EntityConfirmFormBase {

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
   *   The logger object.
   */
  public function __construct(TranslationInterface $string_translation, LoggerInterface $logger) {
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('payment_authnet.logger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you really want to delete %label?', [
      '%label' => $this->getEntity()->label(),
    ]);
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
    $this->logger->info('Authorize.net profile %label (@id) has been deleted.', [
      '@id' => $this->getEntity()->id(),
      '%label' => $this->getEntity()->label(),
    ]);
    $this->messenger()->addStatus($this->t('%label has been deleted.', [
      '%label' => $this->getEntity()->label(),
    ]));
    $form_state->setRedirectUrl($this->getEntity()->toUrl('collection'));
  }

}
