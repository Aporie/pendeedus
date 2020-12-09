<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\Type;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\Plugin\Payment\Type\Unavailable;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\Payment\Type\Unavailable
 *
 * @group Payment
 */
class UnavailableTest extends UnitTestCase {

  /**
   * The event dispatcher.
   *
   * @var \Drupal\payment\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $eventDispatcher;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\Unavailable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [];
    $this->sut = new Unavailable($configuration, $plugin_id, $plugin_definition, $this->eventDispatcher, $this->stringTranslation);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('payment.event_dispatcher', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->eventDispatcher),
      array('string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = [];
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $sut = Unavailable::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(Unavailable::class, $sut);
  }

  /**
   * @covers ::resumeContextAccess
   */
  public function testResumeContextAccess() {
    $account = $this->createMock(AccountInterface::class);

    $access = $this->sut->resumeContextAccess($account);
    $this->assertInstanceOf(AccessResultInterface::class, $access);
    $this->assertTrue($access->isForbidden());
  }

  /**
   * @covers ::doGetResumeContextResponse
   */
  public function testDoGetResumeContextResponse() {
    $this->expectException(NotFoundHttpException::class);
    $payment = $this->createMock(PaymentInterface::class);
    $this->sut->setPayment($payment);

    $this->sut->getResumeContextResponse();
  }

  /**
   * @covers ::getPaymentDescription
   */
  public function testGetPaymentDescription() {
    $this->assertInstanceOf(TranslatableMarkup::class, $this->sut->getPaymentDescription());
  }
}
