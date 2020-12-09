<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\Render\RendererInterface;
use Drupal\payment\Controller\ListPaymentStatuses;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\plugin\PluginOperationsProviderInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\ListPaymentStatuses
 *
 * @group Payment
 */
class ListPaymentStatusesTest extends UnitTestCase {

  /**
   * The payment method plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentStatusManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $renderer;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\ListPaymentStatuses
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->paymentStatusManager = $this->createMock(PaymentStatusManagerInterface::class);

    $this->renderer = $this->createMock(RendererInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new ListPaymentStatuses($this->stringTranslation, $this->renderer, $this->paymentStatusManager);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['plugin.manager.payment.status', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentStatusManager],
      ['renderer', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->renderer],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = ListPaymentStatuses::create($container);
    $this->assertInstanceOf(ListPaymentStatuses::class, $sut);
  }

  /**
   * @covers ::execute
   * @covers ::buildListingLevel
   * @covers ::buildHierarchy
   * @covers ::buildHierarchyLevel
   * @covers ::sort
   */
  function testListing() {
    $plugin_id_a = $this->randomMachineName();
    $plugin_id_b = $this->randomMachineName();

    $definitions = [
      $plugin_id_a => [
        'label' => $this->randomMachineName(),
        'description' => $this->randomMachineName(),
      ],
      $plugin_id_b => [
        'label' => $this->randomMachineName(),
        'description' => $this->randomMachineName(),
        'parent_id' => $plugin_id_a,
      ],
    ];

    $operations_a = [
      'title' => $this->randomMachineName(),
    ];

    $operations_provider_a = $this->createMock(PluginOperationsProviderInterface::class);
    $operations_provider_a->expects($this->once())
      ->method('getOperations')
      ->with($plugin_id_a)
      ->willReturn($operations_a);

    $map = [
      [$plugin_id_a, TRUE, $definitions[$plugin_id_a]],
      [$plugin_id_b, TRUE, $definitions[$plugin_id_b]],
    ];
    $this->paymentStatusManager->expects($this->exactly(count($map)))
      ->method('getDefinition')
      ->willReturnMap($map);
    $this->paymentStatusManager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $map = [
      [$plugin_id_a, $operations_provider_a],
      [$plugin_id_b, NULL],
    ];
    $this->paymentStatusManager->expects($this->exactly(2))
      ->method('getOperationsProvider')
      ->willReturnMap($map);

    $this->stringTranslation->expects($this->any())
      ->method('translate')
      ->will($this->returnArgument(0));

    $build = $this->sut->execute();
    foreach ($build['#header'] as $key => $label) {
      $build['#header'][$key] = (string) $label;
    }

    $expected = [
      '#header' => ['Title', 'Description', 'Operations'],
      '#type' => 'table',
      $plugin_id_a => [
        'label' => [
          '#markup' => $definitions[$plugin_id_a]['label'],
        ],
        'description' => [
          '#markup' => $definitions[$plugin_id_a]['description'],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => $operations_a,
        ],
      ],
      $plugin_id_b => [
        'label' => [
          '#markup' => $definitions[$plugin_id_b]['label'],
        ],
        'description' => [
          '#markup' => $definitions[$plugin_id_b]['description'],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => [],
        ],
      ],
    ];
    $this->assertSame($expected, $build);
  }

}
