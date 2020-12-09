<?php

namespace Drupal\Tests\payment\Unit\Entity\Payment {

  use Drupal\Core\Datetime\DateFormatter;
  use Drupal\Core\Entity\EntityStorageInterface;
  use Drupal\Core\Entity\EntityTypeInterface;
  use Drupal\Core\Entity\Query\QueryInterface;
  use Drupal\Core\Extension\ModuleHandlerInterface;
  use Drupal\Core\Routing\RedirectDestinationInterface;
  use Drupal\Core\StringTranslation\TranslatableMarkup;
  use Drupal\Core\Url;
  use Drupal\currency\Entity\CurrencyInterface;
  use Drupal\payment\Entity\Payment\PaymentListBuilder;
  use Drupal\payment\Entity\PaymentInterface;
  use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
  use Drupal\payment\Plugin\Payment\Status\PaymentStatusInterface;
  use Drupal\Tests\UnitTestCase;
  use Drupal\user\UserInterface;

  /**
   * @coversDefaultClass \Drupal\payment\Entity\Payment\PaymentListBuilder
   *
   * @group Payment
   */
  class PaymentListBuilderTest extends UnitTestCase {

    /**
     * The currency storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $currencyStorage;

    /**
     * The date formatter.
     *
     * @var \Drupal\Core\Datetime\DateFormatter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $dateFormatter;

    /**
     * The entity storage.
     *
     * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityStorage;

    /**
     * The entity type.
     *
     * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityType;

    /**
     * The module handler.
     *
     * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $moduleHandler;

    /**
     * The redirect destination.
     *
     * @var \Drupal\Core\Routing\RedirectDestinationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $redirectDestination;

    /**
     * The string translator.
     *
     * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $stringTranslation;

    /**
     * The class under test.
     *
     * @var \Drupal\payment\Entity\Payment\PaymentListBuilder
     */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      $this->currencyStorage = $this->createMock(EntityStorageInterface::class);

      $this->dateFormatter = $this->getMockBuilder(DateFormatter::class)
        ->disableOriginalConstructor()
        ->getMock();

      $this->entityStorage = $this->createMock(EntityStorageInterface::class);

      $this->entityType = $this->createMock(EntityTypeInterface::class);

      $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

      $this->redirectDestination = $this->createMock(RedirectDestinationInterface::class);

      $this->stringTranslation = $this->getStringTranslationStub();

      $this->sut = new PaymentListBuilder($this->entityType, $this->entityStorage);
      $this->sut->setStringTranslation($this->stringTranslation);
      $this->sut->setRedirectDestination($this->redirectDestination);
      $this->sut->setModuleHandler($this->moduleHandler);
      $this->sut->setDateFormatter($this->dateFormatter);
      $this->sut->setCurrencyStorage($this->currencyStorage);
    }

    /**
     * @covers ::buildHeader
     */
    function testBuildHeader() {
      $header = $this->sut->buildHeader();
      foreach ($header as $cell) {
        $this->assertIsArray($cell);
        $this->assertInstanceOf(TranslatableMarkup::class, $cell['data']);
        if (array_key_exists('class', $cell)) {
          $this->assertIsArray($cell['class']);
        }
      }
    }

    /**
     * @covers ::buildOperations
     */
    public function testBuildOperations() {
      $this->moduleHandler->expects($this->any())
        ->method('invokeAll')
        ->willReturn([]);

      $payment = $this->createMock(PaymentInterface::class);

      $expected_build = array(
        '#type' => 'operations',
        '#links' => [],
        '#attached' => array(
          'library' => array('core/drupal.ajax'),
        )
      );
      $this->assertSame($expected_build, $this->sut->buildOperations($payment));
    }

    /**
     * @covers ::buildRow
     *
     * @dataProvider providerTestBuildRow
     *
     * @depends testBuildOperations
     */
    function testBuildRow($payment_currency_exists) {
      $payment_changed_time = time();
      $payment_changed_time_formatted = $this->randomMachineName();
      $payment_currency_code = $this->randomMachineName();
      $payment_amount = mt_rand();
      $payment_amount_formatted = $this->randomMachineName();

      $payment_status_definition = array(
        'label' => $this->randomMachineName(),
      );

      $payment_status = $this->createMock(PaymentStatusInterface::class);
      $payment_status->expects($this->any())
        ->method('getPluginDefinition')
        ->willReturn($payment_status_definition);

      $owner = $this->createMock(UserInterface::class);

      $payment_method_label = $this->randomMachineName();
      $payment_method_definition = [
        'label' => $payment_method_label,
      ];
      $payment_method = $this->createMock(PaymentMethodInterface::class);
      $payment_method->expects($this->atLeastOnce())
        ->method('getPluginDefinition')
        ->willReturn($payment_method_definition);

      $payment = $this->createMock(PaymentInterface::class);
      $payment->expects($this->any())
        ->method('getAmount')
        ->willReturn($payment_amount);
      $payment->expects($this->any())
        ->method('getChangedTime')
        ->willReturn($payment_changed_time);
      $payment->expects($this->any())
        ->method('getCurrencyCode')
        ->willReturn($payment_currency_code);
      $payment->expects($this->any())
        ->method('getOwner')
        ->willReturn($owner);
      $payment->expects($this->any())
        ->method('getPaymentMethod')
        ->willReturn($payment_method);
      $payment->expects($this->any())
        ->method('getPaymentStatus')
        ->willReturn($payment_status);

      $currency = $this->createMock(CurrencyInterface::class);
      $currency->expects($this->once())
        ->method('formatAmount')
        ->with($payment_amount)
        ->willReturn($payment_amount_formatted);

      $map = array(
        array($payment_currency_code, $payment_currency_exists ? $currency : NULL),
        array('XXX', $payment_currency_exists ? NULL : $currency),
      );
      $this->currencyStorage->expects($this->atLeastOnce())
        ->method('load')
        ->willReturnMap($map);

      $this->dateFormatter->expects($this->once())
        ->method('format')
        ->with($payment_changed_time)
        ->willReturn($payment_changed_time_formatted);

      $this->moduleHandler->expects($this->any())
        ->method('invokeAll')
        ->willReturn([]);

      $build = $this->sut->buildRow($payment);
      unset($build['data']['operations']['data']['#attached']);
      $expected_build = array(
        'data' => array(
          'updated' => $payment_changed_time_formatted,
          'status' => $payment_status_definition['label'],
          'amount' => $payment_amount_formatted,
          'payment_method' => $payment_method_label,
          'owner' => array(
            'data' => array(
              '#theme' => 'username',
              '#account' => $owner,
            )
          ),
          'operations' => array(
            'data' => array(
              '#type' => 'operations',
              '#links' => [],
            ),
          ),
        ),
      );
      $this->assertSame($expected_build, $build);
    }

    /**
     * Provides data to self::testBuildRow().
     */
    public function providerTestBuildRow() {
      return array(
        array(TRUE),
        array(FALSE),
      );
    }

    /**
     * @covers ::load
     * @covers ::render
     * @covers ::getEntityIds
     *
     * @depends testBuildHeader
     */
    public function testRender() {
      $query = $this->createMock(QueryInterface::class);
      $query->expects($this->atLeastOnce())
        ->method('pager')
        ->willReturnSelf();

      $this->entityStorage->expects($this->atLeastOnce())
        ->method('getQuery')
        ->willReturn($query);
      $this->entityStorage->expects($this->once())
        ->method('loadMultiple')
        ->willReturn([]);

      $build = $this->sut->render();
      unset($build['table']['#header']);
      $expected_build = array(
        '#type' => 'table',
        '#title' => NULL,
        '#rows' => [],
        '#cache' => [
          'contexts' => NULL,
          'tags' => NULL,
        ],
      );
      $this->assertInstanceOf(TranslatableMarkup::class, $build['table']['#empty']);
      unset($build['table']['#empty']);
      $this->assertEquals($expected_build, $build['table']);
    }

    /**
     * @covers ::getDefaultOperations
     */
    public function testGetDefaultOperationsWithoutAccess() {
      $method = new \ReflectionMethod($this->sut, 'getDefaultOperations');
      $method->setAccessible(TRUE);

      $payment = $this->createMock(PaymentInterface::class);

      $operations = $method->invoke($this->sut, $payment);
      $this->assertEmpty($operations);
    }

    /**
     * @covers ::getDefaultOperations
     */
    public function testGetDefaultOperationsWithAccess() {
      $method = new \ReflectionMethod($this->sut, 'getDefaultOperations');
      $method->setAccessible(TRUE);

      $url_canonical = new Url($this->randomMachineName());
      $url_edit_form = new Url($this->randomMachineName());
      $url_delete_form = new Url($this->randomMachineName());
      $url_update_status_form = new Url($this->randomMachineName());
      $url_capture_form = new Url($this->randomMachineName());
      $url_refund_form = new Url($this->randomMachineName());
      $url_complete = new Url($this->randomMachineName());

      $payment = $this->createMock(PaymentInterface::class);
      $map = [
        ['view', NULL, FALSE, TRUE],
        ['update', NULL, FALSE, TRUE],
        ['delete', NULL, FALSE, TRUE],
        ['update_status', NULL, FALSE, TRUE],
        ['capture', NULL, FALSE, TRUE],
        ['refund', NULL, FALSE, TRUE],
        ['complete', NULL, FALSE, TRUE],
      ];
      $payment->expects($this->atLeast(count($map)))
        ->method('access')
        ->willReturnMap($map);
      $payment->expects($this->any())
        ->method('hasLinkTemplate')
        ->willReturn(TRUE);
      $map = [
        ['canonical', [], $url_canonical],
        ['edit-form', [], $url_edit_form],
        ['delete-form', [], $url_delete_form],
        ['update-status-form', [], $url_update_status_form],
        ['capture-form', [], $url_capture_form],
        ['refund-form', [], $url_refund_form],
        ['complete', [], $url_complete],
      ];
      $payment->expects($this->atLeast(count($map)))
        ->method('toUrl')
        ->willReturnMap($map);

      $destination = $this->randomMachineName();

      $this->redirectDestination->expects($this->atLeastOnce())
        ->method('get')
        ->willReturn($destination);

      $operations = $method->invoke($this->sut, $payment);
      ksort($operations);
      $expected_operations = ['view', 'edit', 'delete', 'update_status', 'capture', 'refund', 'complete'];
      sort($expected_operations);
      $this->assertSame($expected_operations, array_keys($operations));
      foreach ($operations as $name => $operation) {
        $this->assertInstanceof(TranslatableMarkup::class, $operation['title']);
        $this->assertInstanceof(Url::class, $operation['url']);
        if (array_key_exists('weight', $operation)) {
          $this->assertIsInt($operation['weight']);
        }
      }
    }

  }

}

namespace {

  if (!defined('RESPONSIVE_PRIORITY_LOW')) {
    define('RESPONSIVE_PRIORITY_LOW', 'priority-low');
  }
  if (!defined('RESPONSIVE_PRIORITY_MEDIUM')) {
    define('RESPONSIVE_PRIORITY_MEDIUM', 'priority-medium');
  }

}
