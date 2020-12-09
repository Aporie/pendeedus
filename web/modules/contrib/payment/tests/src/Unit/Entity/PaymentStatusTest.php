<?php

namespace Drupal\Tests\payment\Unit\Entity;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\payment\Entity\PaymentStatus;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Entity\PaymentStatus
 *
 * @group Payment
 */
class PaymentStatusTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Entity\PaymentStatus
   */
  protected $sut;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $typedConfigManager;

  /**
   * {@inheritdoc}
   *
   * @covers ::setEntityTypeManager
   * @covers ::setTypedConfig
   */
  public function setUp(): void {
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->entityTypeId = $this->randomMachineName();

    $this->typedConfigManager = $this->createMock(TypedConfigManagerInterface::class);

    $this->sut = new PaymentStatus([], $this->entityTypeId);
    $this->sut->setEntityTypeManager($this->entityTypeManager);
    $this->sut->setTypedConfig($this->typedConfigManager);
  }

  /**
   * @covers ::id
   * @covers ::setId
   */
  public function testId() {
    $id = strtolower($this->randomMachineName());
    $this->assertSame($this->sut, $this->sut->setId($id));
    $this->assertSame($id, $this->sut->id());
  }

  /**
   * @covers ::setLabel
   * @covers ::label
   */
  public function testLabel() {
    $entity_type = $this->createMock(ConfigEntityTypeInterface::class);
    $entity_type->expects($this->atLeastOnce())
      ->method('getKey')
      ->with('label')
      ->willReturn('label');

    $this->entityTypeManager->expects($this->atLeastOnce())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->willReturn($entity_type);

    $label = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setLabel($label));
    $this->assertSame($label, $this->sut->label());
  }

  /**
   * @covers ::getParentId
   * @covers ::setParentId
   */
  public function testGetParentId() {
    $id = strtolower($this->randomMachineName());
    $this->assertSame($this->sut, $this->sut->setParentId($id));
    $this->assertSame($id, $this->sut->getParentId());
  }

  /**
   * @covers ::getDescription
   * @covers ::setDescription
   */
  public function testGetDescription() {
    $description = $this->randomMachineName();
    $this->assertSame($this->sut, $this->sut->setDescription($description));
    $this->assertSame($description, $this->sut->getDescription());
  }

  /**
   * @covers ::entityTypeManager
   */
  public function testEntityTypeManager() {
    $method = new \ReflectionMethod($this->sut, 'entityTypeManager');
    $method->setAccessible(TRUE);
    $this->assertSame($this->entityTypeManager, $method->invoke($this->sut));
  }

  /**
   * @covers ::getTypedConfig
   */
  public function testGetTypedConfig() {
    $method = new \ReflectionMethod($this->sut, 'getTypedConfig');
    $method->setAccessible(TRUE);
    $this->assertSame($this->typedConfigManager, $method->invoke($this->sut));
  }

}
