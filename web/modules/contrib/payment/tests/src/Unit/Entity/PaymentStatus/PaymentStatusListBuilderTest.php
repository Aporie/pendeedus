<?php

namespace Drupal\Tests\payment\Unit\Entity\PaymentStatus;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\payment\Entity\PaymentStatus\PaymentStatusListBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Entity\PaymentStatus\PaymentStatusListBuilder
 *
 * @group Payment
 */
class PaymentStatusListBuilderTest extends UnitTestCase {

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
   * The class under test.
   *
   * @var \Drupal\payment\Entity\PaymentStatus\PaymentStatusListBuilder
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->entityStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityType = $this->createMock(EntityTypeInterface::class);

    $this->sut = new PaymentStatusListBuilder($this->entityType, $this->entityStorage);
  }

  /**
   * @covers ::render
   */
  function testRender() {
    $this->expectException(\Exception::class);
    $this->sut->render();
  }

}
