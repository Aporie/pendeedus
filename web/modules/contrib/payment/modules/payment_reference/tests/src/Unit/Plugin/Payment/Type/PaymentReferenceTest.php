<?php

namespace Drupal\Tests\payment_reference\Unit\Plugin\Payment\Type;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\Response\ResponseInterface;
use Drupal\payment_reference\Plugin\Payment\Type\PaymentReference;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment_reference\Plugin\Payment\Type\PaymentReference
 *
 * @group Payment Reference Field
 */
class PaymentReferenceTest extends UnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Drupal\payment\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

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
   * @var \Drupal\payment_reference\Plugin\Payment\Type\PaymentReference
   */
  protected $sut;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);

    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      ->willReturn('http://example.com');

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->payment = $this->createMock(PaymentInterface::class);

    $this->sut = new PaymentReference([], 'payment_reference', [], $this->eventDispatcher, $this->urlGenerator, $this->entityFieldManager, $this->stringTranslation);
    $this->sut->setPayment($this->payment);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('entity_field.manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->entityFieldManager),
      array('payment.event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->eventDispatcher),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
      array('url_generator', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->urlGenerator),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $sut = PaymentReference::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PaymentReference::class, $sut);
  }

  /**
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    $this->assertIsArray($this->sut->defaultConfiguration());
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
  public function testGetPaymentDescription() {
    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();
    $field_name = $this->randomMachineName();
    $label = $this->randomMachineName();
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->expects($this->atLeastOnce())
      ->method('getLabel')
      ->willReturn($label);

    $definitions = array(
      $field_name => $field_definition,
    );

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
   * @covers ::resumeContextAccess
   *
   * @dataProvider providerTestResumeContextAccess
   */
  public function testResumeContextAccess($expected, $payment_owner_id, $account_id) {
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn($account_id);

    $this->payment->expects($this->atLeastOnce())
      ->method('getOwnerId')
      ->willReturn($payment_owner_id);

    $access = $this->sut->resumeContextAccess($account);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertSame($expected, $access->isAllowed());
  }

  /**
   * Provides data to self::testResumeContextAccess().
   */
  public function providerTestResumeContextAccess() {
    $id_a = mt_rand();
    $id_b = mt_rand();

    return array(
      array(TRUE, $id_a, $id_a),
      array(TRUE, $id_b, $id_b),
      array(FALSE, $id_a, $id_b),
    );
  }

  /**
   * @covers ::doGetResumeContextResponse
   */
  public function testDoGetResumeContextResponse() {
    $response = $this->sut->getResumeContextResponse();

    $this->assertInstanceOf(ResponseInterface::class, $response);
  }

}
