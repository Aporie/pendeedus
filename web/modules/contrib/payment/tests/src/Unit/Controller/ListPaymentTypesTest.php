<?php

namespace Drupal\Tests\payment\Unit\Controller;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\payment\Controller\ListPaymentTypes;
use Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface;
use Drupal\plugin\PluginOperationsProviderInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Controller\ListPaymentTypes
 *
 * @group Payment
 */
class ListPaymentTypesTest extends UnitTestCase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The payment type plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Type\PaymentTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentTypeManager;

  /**
   * The string translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Controller\ListPaymentTypes
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->paymentTypeManager= $this->createMock(PaymentTypeManagerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new ListPaymentTypes($this->moduleHandler, $this->paymentTypeManager, $this->currentUser, $this->stringTranslation);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['current_user', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->currentUser],
      ['module_handler', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->moduleHandler],
      ['plugin.manager.payment.type', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentTypeManager],
      ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->stringTranslation],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $sut = ListPaymentTypes::create($container);
    $this->assertInstanceOf(ListPaymentTypes::class, $sut);
  }

  /**
   * @covers ::execute
   */
  public function testExecute() {
    $definitions = [
      'foo' => [
        'label' => $this->randomMachineName(),
        'description' => $this->randomMachineName(),
      ],
      'bar' => [
        'label' => $this->randomMachineName(),
      ],
      'payment_unavailable' => [],
    ];

    $operations_foo = [
      'baz' => [
        'title' => $this->randomMachineName(),
      ],
    ];

    $operations_provider_foo = $this->createMock(PluginOperationsProviderInterface::class);
    $operations_provider_foo->expects($this->once())
      ->method('getOperations')
      ->with('foo')
      ->willReturn($operations_foo);

    $this->paymentTypeManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $map = [
      ['foo', $operations_provider_foo],
      ['bar', NULL],
    ];
    $this->paymentTypeManager->expects($this->exactly(2))
      ->method('getOperationsProvider')
      ->willReturnMap($map);

    $this->moduleHandler->expects($this->any())
      ->method('moduleExists')
      ->with('field_ui')
      ->willReturn(TRUE);

    $map = [
      ['administer payment fields', TRUE],
      ['administer payment form display', TRUE],
      ['administer payment display', TRUE],
    ];
    $this->currentUser->expects($this->atLeastOnce())
      ->method('hasPermission')
      ->willReturnMap($map);

    $build = $this->sut->execute();
    $expected_build = [
      '#empty' => 'There are no available payment types.',
      '#header' => ['Type', 'Description', 'Operations'],
      '#type' => 'table',
      'foo' => [
        'label' => [
          '#markup' => $definitions['foo']['label'],
        ],
        'description' => [
          '#markup' => $definitions['foo']['description'],
        ],
        'operations' => [
          '#links' => $operations_foo + [
            'configure' => [
              'url' => new Url('payment.payment_type', [
                'bundle' => 'foo',
              ]),
              'title' => 'Configure',
            ],
            'manage-fields' => [
              'title' => 'Manage fields',
              'url' => new Url('entity.payment.field_ui_fields', [
                'bundle' => 'foo',
              ]),
            ],
            'manage-form-display' => [
              'title' => 'Manage form display',
              'url' => new Url('entity.entity_form_display.payment.default', [
                'bundle' => 'foo',
              ]),
            ],
            'manage-display' => [
              'title' => 'Manage display',
              'url' => new Url('entity.entity_view_display.payment.default', [
                'bundle' => 'foo',
              ]),
            ],
          ],
          '#type' => 'operations',
        ],
      ],
      'bar' => [
        'label' => [
          '#markup' => $definitions['bar']['label'],
        ],
        'description' => [
          '#markup' => NULL,
        ],
        'operations' => [
          '#links' => [
            'configure' => [
              'url' => new Url('payment.payment_type', [
                'bundle' => 'bar',
              ]),
              'title' => 'Configure',
            ],
            'manage-fields' => [
              'title' => 'Manage fields',
              'url' => new Url('entity.payment.field_ui_fields', [
                'bundle' => 'bar',
              ]),
            ],
            'manage-form-display' => [
              'title' => 'Manage form display',
              'url' => new Url('entity.entity_form_display.payment.default', [
                'bundle' => 'bar',
              ]),
            ],
            'manage-display' => [
              'title' => 'Manage display',
              'url' => new Url('entity.entity_view_display.payment.default', [
                'bundle' => 'bar',
              ]),
            ],
          ],
          '#type' => 'operations',
        ],
      ],
    ];
    $this->assertEquals($expected_build, $build);
  }

}
