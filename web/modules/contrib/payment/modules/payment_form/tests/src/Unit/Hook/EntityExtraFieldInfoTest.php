<?php

namespace Drupal\Tests\payment_form\Unit\Hook;

use Drupal\payment_form\Hook\EntityExtraFieldInfo;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\payment_form\Hook\EntityExtraFieldInfo
 *
 * @group Payment
 */
class EntityExtraFieldInfoTest extends UnitTestCase {

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * The class under test.
   *
   * @var \Drupal\payment_form\Hook\EntityExtraFieldInfo
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->stringTranslation = $this->getStringTranslationStub();

    $this->sut = new EntityExtraFieldInfo($this->stringTranslation);
  }

  /**
   * @covers ::invoke
   */
  public function testInvoke() {
    $fields = $this->sut->invoke();
    $this->assertIsArray($fields);
    $this->assertArrayHasKey('payment_method', $fields['payment']['payment_form']['form']);
  }
}
