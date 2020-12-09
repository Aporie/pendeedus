<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\payment\Controller\ConfigurePaymentType;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\payment\Controller\ConfigurePaymentType
 *
 * @group Payment
 */
class ConfigurePaymentTypeTest extends UnitTestCase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formBuilder;

  /**
   * The payment type plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentTypeManager;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\ConfigurePaymentType
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->formBuilder = $this->createMock(FormBuilderInterface::class);

    $this->paymentTypeManager= $this->createMock(PaymentTypeManagerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new ConfigurePaymentType($this->formBuilder, $this->paymentTypeManager, $this->stringTranslation);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['form_builder', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->formBuilder],
      ['plugin.manager.payment.type', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentTypeManager],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = ConfigurePaymentType::create($container);
    $this->assertInstanceOf(ConfigurePaymentType::class, $sut);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $bundle_exists = $this->randomMachineName();
    $bundle_exists_definition = [
      'configuration_form' => $this->randomMachineName(),
    ];
    $bundle_exists_no_form = $this->randomMachineName();
    $bundle_exists_no_form_definition = [];
    $bundle_no_exists = $this->randomMachineName();
    $bundle_no_exists_definition = NULL;

    $form_build = [
      '#type' => $this->randomMachineName(),
    ];
    $this->formBuilder->expects($this->once())
      ->method('getForm')
      ->with($bundle_exists_definition['configuration_form'])
      ->willReturn($form_build);

    $map = [
      [$bundle_exists, FALSE, $bundle_exists_definition],
      [$bundle_exists_no_form, FALSE, $bundle_exists_no_form_definition],
      [$bundle_no_exists, FALSE, $bundle_no_exists_definition],
    ];
    $this->paymentTypeManager->expects($this->any())
      ->method('getDefinition')
      ->willReturnMap($map);

    // Test with a bundle of a plugin with a form.
    $build = $this->sut->execute($bundle_exists);
    $this->assertSame($form_build, $build);

    // Test with a bundle of a plugin without a form.
    $build = $this->sut->execute($bundle_exists_no_form);
    $this->assertIsArray($build);

    // Test with a non-existing bundle.
    $this->expectException(NotFoundHttpException::class);
    $this->sut->execute($bundle_no_exists);
  }

  /**
   * @covers ::title
   */
  public function testTitle() {
    $plugin_id = $this->randomMachineName();
    $label = $this->randomMachineName();
    $definition = [
      'label' => $label,
    ];

    $this->paymentTypeManager->expects($this->once())
      ->method('getDefinition')
      ->with($plugin_id)
      ->willReturn($definition);

    $this->assertSame($label, $this->sut->title($plugin_id));
  }

}
