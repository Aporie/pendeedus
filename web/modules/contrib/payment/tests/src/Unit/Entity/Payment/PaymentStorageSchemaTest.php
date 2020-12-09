<?php

namespace Drupal\Tests\payment\Unit\Entity\Payment;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\payment\Entity\Payment\PaymentStorageSchema;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment\Entity\Payment\PaymentStorageSchema
 *
 * @group Payment
 */
class PaymentStorageSchemaTest extends UnitTestCase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\ContentEntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * The storage field definitions.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface|\PHPUnit\Framework\MockObject\MockObject[]
   */
  protected $fieldStorageDefinitions;

  /**
   * The storage handler.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Entity\Payment\PaymentStorageSchema
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $entity_type_id_key = $this->randomMachineName();
    $entity_type_id = $this->randomMachineName();

    $this->database = $this->getMockBuilder(Connection::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->fieldStorageDefinitions = array(
      $entity_type_id_key => $this->createMock(FieldDefinitionInterface::class),
    );

    $this->entityTypeManager = $this->getMockBuilder(EntityTypeManager::class)->disableOriginalConstructor()->getMock();

    $this->entityFieldManager = $this->getMockBuilder(EntityFieldManager::class)->disableOriginalConstructor()->getMock();
    $this->entityFieldManager->expects($this->atLeastOnce())
      ->method('getActiveFieldStorageDefinitions')
      ->with($entity_type_id)
      ->willReturn($this->fieldStorageDefinitions);

    $this->entityType = $this->createMock(ContentEntityTypeInterface::class);
    $this->entityType->expects($this->atLeastOnce())
      ->method('id')
      ->willReturn($entity_type_id);

    $this->entityTypeManager->expects($this->atLeastOnce())
      ->method('getActiveDefinition')
      ->with($entity_type_id)
      ->willReturn($this->entityType);

    $this->storage = $this->getMockBuilder(SqlContentEntityStorage::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->sut = new PaymentStorageSchema($this->entityTypeManager, $this->entityType, $this->storage, $this->database, $this->entityFieldManager);
  }

  /**
   * @covers ::alterEntitySchemaWithNonFieldColumns
   */
  public function testAlterEntitySchemaWithNonFieldColumns() {
    $schema = array(
      'payment' => array(
        'fields' => [],
        'foreign keys' => [],
      ),
    );
    $method = new \ReflectionMethod($this->sut, 'alterEntitySchemaWithNonFieldColumns');
    $method->setAccessible(TRUE);
    $method->invokeArgs($this->sut, array(&$schema));
    $this->assertIsArray($schema);
    $this->assertArrayHasKey('payment', $schema);
    $this->assertIsArray($schema['payment']);
    $this->assertArrayHasKey('fields', $schema['payment']);
    foreach ($schema['payment']['fields'] as $field) {
      $this->assertIsArray($field);
      $this->assertArrayHasKey('type', $field);
    }
    $this->assertArrayHasKey('foreign keys', $schema['payment']);
  }

}
