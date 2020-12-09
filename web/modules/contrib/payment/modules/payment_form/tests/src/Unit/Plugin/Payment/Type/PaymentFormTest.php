<?php

namespace Drupal\Tests\payment_form\Unit\Plugin\Payment\Type;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\Response\ResponseInterface;
use Drupal\payment_form\Plugin\Payment\Type\PaymentForm;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment_form\Plugin\Payment\Type\PaymentForm
 *
 * @group Payment Form Field
 */
class PaymentFormTest extends UnitTestCase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\payment\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The payment.
   *
   * @var \Drupal\payment\Entity\PaymentInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $payment;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment_form\Plugin\Payment\Type\PaymentForm
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new PaymentForm([], 'payment_form', [], $this->eventDispatcher, $this->entityFieldManager, $this->stringTranslation);

    $this->payment = $this->createMock(PaymentInterface::class);

    $this->sut->setPayment($this->payment);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['entity_field.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->entityFieldManager],
      ['payment.event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->eventDispatcher],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $sut = PaymentForm::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PaymentForm::class, $sut);
  }

  /**
   * @covers ::setEntityTypeId
   * @covers ::getEntityTypeId
   */
  public function testGetEntityTypeId() {
    $id = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setEntityTypeId($id));
    $this->assertSame($id, $this->sut->getEntityTypeId());
  }

  /**
   * @covers ::setBundle
   * @covers ::getBundle
   */
  public function testGetBundle() {
    $bundle = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setBundle($bundle));
    $this->assertSame($bundle, $this->sut->getBundle());
  }

  /**
   * @covers ::setFieldName
   * @covers ::getFieldName
   */
  public function testGetFieldName() {
    $name = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setFieldName($name));
    $this->assertSame($name, $this->sut->getFieldName());
  }

  /**
   * @covers ::getPaymentDescription
   *
   * @depends testGetEntityTypeId
   * @depends testGetBundle
   * @depends testGetFieldName
   */
  public function testPaymentDescription() {
    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();
    $field_name = $this->randomMachineName();
    $label = $this->randomMachineName();
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->expects($this->atLeastOnce())
      ->method('getLabel')
      ->willReturn($label);

    $definitions = [
      $field_name => $field_definition,
    ];

    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getFieldDefinitions')
      ->with($entity_type_id, $bundle)
      ->willReturn($definitions);

    $this->sut->setEntityTypeId($entity_type_id);
    $this->sut->setBundle($bundle);
    $this->sut->setFieldName($field_name);

    $this->assertSame($label, $this->sut->getPaymentDescription());
  }

  /**
   * @covers ::getPaymentDescription
   */
  public function testGetPaymentDescriptionWithNonExistingField() {
    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();

    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getFieldDefinitions')
      ->with($entity_type_id, $bundle)
      ->willReturn([]);

    $this->sut->setEntityTypeId($entity_type_id);
    $this->sut->setBundle($bundle);

    $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->getPaymentDescription());
  }

  /**
   * @covers ::setDestinationUrl
   * @covers ::getDestinationUrl
   */
  public function testGetDestinationUrl() {
    $destination_url = $this->randomMachineName();
    $this->assertSame(spl_object_hash($this->sut), spl_object_hash($this->sut->setDestinationUrl($destination_url)));
    $this->assertSame($destination_url, $this->sut->getDestinationUrl());
  }

  /**
   * @covers ::resumeContextAccess
   */
  public function testResumeContextAccess() {
    $account = $this->createMock(AccountInterface::class);

    $access = $this->sut->resumeContextAccess($account);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertTrue($access->isAllowed());
  }

  /**
   * @covers ::doGetResumeContextResponse
   * @depends testGetDestinationUrl
   */
  public function testDoGetResumeContextResponse() {
    $url = 'http://example.com/' . $this->randomMachineName();

    $this->sut->setDestinationUrl($url);

    $response = $this->sut->getResumeContextResponse();

    $this->assertInstanceOf(ResponseInterface::class, $response);
    $this->assertSame($url, $response->getRedirectUrl()->getUri());
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $configuration = $this->sut->defaultConfiguration();
    $this->assertIsArray($configuration);
    $this->assertArrayHasKey('destination_url', $configuration);
    $this->assertNull($configuration['destination_url']);
  }

}
