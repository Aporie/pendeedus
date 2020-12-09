<?php

namespace Drupal\Tests\payment\Unit\Plugin\views\argument_validator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Plugin\views\argument_validator\ViewPaymentsByOwner;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\payment\Plugin\views\argument_validator\ViewPaymentsByOwner
 *
 * @group Payment
 */
class ViewPaymentsByOwnerTest extends UnitTestCase {

  /**
   * The current user
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The plugin definition.
   *
   * @var mixed[]
   */
  protected $pluginDefinition = [];

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Plugin\views\argument_validator\ViewPaymentsByOwner
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->currentUser = $this->createMock(AccountInterface::class);

    $entity_type_bundle_info = $this->createMock(EntityTypeBundleInfoInterface::class);

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $this->pluginDefinition = [
      'entity_type' => $this->randomMachineName(),
    ];
    $this->sut = new ViewPaymentsByOwner($configuration, $plugin_id, $this->pluginDefinition, $this->entityTypeManager, $entity_type_bundle_info);
    $this->sut->setCurrentUser($this->currentUser);
    $options = [
      'access' => FALSE,
      'bundles' => [],
      'multiple' => TRUE,
      'operation' => NULL,
    ];
    $view_executable = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->getMock();
    $display = $this->getMockBuilder(DisplayPluginBase::class)
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $this->sut->init($view_executable, $display, $options);
  }

  /**
   * @covers ::validateArgument
   */
  public function xtestValidateArgumentWithoutValidEntities() {
    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->expects($this->atLeastOnce())
      ->method('loadMultiple')
      ->willReturn([]);

    $this->entityTypeManager->expects($this->atLeastOnce())
      ->method('getStorage')
      ->with($this->pluginDefinition['entity_type'])
      ->willReturn($entity_storage);

    $argument = mt_rand();

    $this->assertFalse($this->sut->validateArgument($argument));
  }

  /**
   * @covers ::validateArgument
   *
   * @dataProvider providerValidateArgument
   */
  public function testValidateArgument($expected_validity, $argument, $current_user_id, array $permissions) {
    $entity = $this->createMock(EntityInterface::class);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->expects($this->atLeastOnce())
      ->method('loadMultiple')
      ->willReturn([
        7 => $entity,
        9 => $entity,
      ]);

    $this->entityTypeManager->expects($this->atLeastOnce())
      ->method('getStorage')
      ->with($this->pluginDefinition['entity_type'])
      ->willReturn($entity_storage);

    $this->currentUser->expects($this->any())
      ->method('id')
      ->willReturn($current_user_id);
    $map = [];
    foreach ($permissions as $permission) {
      $map[] = [$permission, TRUE];
    }
    $this->currentUser->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap($map);

    $this->assertSame($expected_validity, $this->sut->validateArgument($argument));
  }

  /**
   * Provides data to self::testValidateArgument().
   */
  public function providerValidateArgument() {
    return [
      // Permissions to view own paymens only.
      [TRUE, '7', 7, ['payment.payment.view.own']],
      [FALSE, '7+9', 7, ['payment.payment.view.own']],
      [FALSE, '7,9', 7, ['payment.payment.view.own']],
      [FALSE, '9', 7, ['payment.payment.view.own']],

      // Permissions to view any payment.
      [TRUE, '7', 7, ['payment.payment.view.any']],
      [TRUE, '7+9', 7, ['payment.payment.view.any']],
      [TRUE, '7,9', 7, ['payment.payment.view.any']],

      // Permissions to view own and any payments.
      [TRUE, '7', 7, ['payment.payment.view.any', 'payment.payment.view.own']],
      [TRUE, '7+9', 7, ['payment.payment.view.any', 'payment.payment.view.own']],
      [TRUE, '7,9', 7, ['payment.payment.view.any', 'payment.payment.view.own']],
    ];
  }

}
