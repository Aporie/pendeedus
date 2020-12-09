<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles the "configure payment type" route.
 */
class ConfigurePaymentType extends ControllerBase {

  /**
   * The payment type plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface
   */
  protected $paymentTypeManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface $payment_type_manager
   *   The payment type plugin manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   */
  public function __construct(FormBuilderInterface $form_builder, PaymentTypeManagerInterface $payment_type_manager, TranslationInterface $string_translation) {
    $this->formBuilder = $form_builder;
    $this->paymentTypeManager = $payment_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('form_builder'), $container->get('plugin.manager.payment.type'), $container->get('string_translation'));
  }

  /**
   * Builds the payment type's configuration form.
   *
   * @param string $bundle
   *   The payment bundle, also known as the payment type's plugin ID.
   *
   * @return array
   *   A renderable array
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   */
  public function execute($bundle) {
    $definition = $this->paymentTypeManager->getDefinition($bundle, FALSE);
    if (is_null($definition)) {
      throw new NotFoundHttpException();
    }
    elseif (isset($definition['configuration_form'])) {
      return $this->formBuilder->getForm($definition['configuration_form']);
    }
    else {
      return [
        '#markup' => $this->t('This payment type has no configuration.'),
      ];
    }
  }

  /**
   * Gets the title of the payment type configuration page.
   *
   * @param string $bundle
   *   The payment type's plugin ID.
   *
   * @return string
   */
  public function title($bundle) {
    $definition = $this->paymentTypeManager->getDefinition($bundle);

    return $definition['label'];
  }

}
