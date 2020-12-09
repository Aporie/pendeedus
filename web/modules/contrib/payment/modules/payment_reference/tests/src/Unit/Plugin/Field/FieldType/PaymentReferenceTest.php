<?php

namespace Drupal\Tests\payment_reference\Unit\Plugin\Field\FieldType;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\payment\QueueInterface;
use Drupal\payment_reference\Plugin\Field\FieldType\PaymentReference;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\payment_reference\Plugin\Field\FieldType\PaymentReference
 *
 * @group Payment Reference Field
 */
class PaymentReferenceTest extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The payment queue.
   *
   * @var \Drupal\payment\QueueInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $queue;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment_reference\Plugin\Field\FieldType\PaymentReference
   */
  protected $sut;
  /**
   * The field's target_id typed data property.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $targetId;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->queue = $this->createMock(QueueInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->targetId = $this->createMock(TypedDataInterface::class);

    $container = new ContainerBuilder();
    $container->set('module_handler', $this->moduleHandler);
    $container->set('payment_reference.queue', $this->queue);
    $container->set('string_translation', $this->stringTranslation);
    \Drupal::setContainer($container);

    $this->sut = $this->getMockBuilder(PaymentReference::class)
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();
    $this->sut->expects($this->any())
      ->method('get')
      ->with('target_id')
      ->willReturn($this->targetId);
  }

  /**
   * @covers ::defaultStorageSettings
   */
  public function testDefaultStorageSettings() {
    $settings = $this->sut->defaultStorageSettings();
    $this->assertIsArray($settings);
  }

  /**
   * @covers ::defaultFieldSettings
   */
  public function testDefaultFieldSettings() {
    $settings = $this->sut->defaultFieldSettings();
    $this->assertIsArray($settings);
  }

  /**
   * @covers ::schema
   */
  public function testSchema() {
    $field_storage_definition = $this->createMock(FieldStorageDefinitionInterface::class);

    $schema = $this->sut->schema($field_storage_definition);

    $this->assertIsArray($schema);
    $this->arrayHasKey('columns', $schema);
    $this->assertIsArray($schema['columns']);
    $this->arrayHasKey('indexes', $schema);
    $this->assertIsArray($schema['indexes']);
    $this->arrayHasKey('foreign keys', $schema);
    $this->assertIsArray($schema['foreign keys']);
  }

  /**
   * @covers ::storageSettingsForm
   */
  public function testStorageSettingsForm() {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $has_data = TRUE;
    $this->assertSame([], $this->sut->storageSettingsForm($form, $form_state, $has_data));
  }

}
