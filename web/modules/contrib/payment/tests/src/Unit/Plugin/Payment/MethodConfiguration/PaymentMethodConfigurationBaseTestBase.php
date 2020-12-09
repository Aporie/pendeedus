<?php

namespace Drupal\Tests\payment\Unit\Plugin\Payment\MethodConfiguration;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Provides a base for tests that cover classes that extend
 * \Drupal\idepaymental\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase
 */
abstract class PaymentMethodConfigurationBaseTestBase extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The plugin's definition.
   *
   * @var mixed[]
   */
  protected $pluginDefinition;

  /**
   * The string translator.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $stringTranslation;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $this->stringTranslation = $this->getStringTranslationStub();

    $this->pluginDefinition = [
      'description' => $this->randomMachineName(),
      'label' => $this->randomMachineName(),
    ];
  }

}
