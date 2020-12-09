<?php

namespace Drupal\Tests\payment\Unit\Entity\PaymentMethodConfiguration;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationAccessControlHandler;
use Drupal\payment\Entity\PaymentMethodConfigurationInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationAccessControlHandler
 *
 * @group Payment
 */
class PaymentMethodConfigurationAccessControlHandlerTest extends UnitTestCase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The class under test.
   *
   * @var \Drupal\payment\Entity\PaymentMethodConfiguration\PaymentMethodConfigurationAccessControlHandler
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    $cache_context_manager = $this->getMockBuilder(CacheContextsManager::class)
      ->disableOriginalConstructor()
      ->getMock();
    $cache_context_manager->expects($this->any())
      ->method('assertValidTokens')
      ->willReturn(TRUE);

    $container = new Container();
    $container->set('cache_contexts_manager', $cache_context_manager);
    \Drupal::setContainer($container);

    $entity_type = $this->createMock(EntityTypeInterface::class);

    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->moduleHandler->expects($this->any())
      ->method('invokeAll')
      ->willReturn([]);

    $this->sut = new PaymentMethodConfigurationAccessControlHandler($entity_type, $this->moduleHandler);
  }

  /**
   * @covers ::createInstance
   * @covers ::__construct
   */
  public function testCreateInstance() {
    $container = $this->createMock(ContainerInterface::class);
    $map = [
      ['module_handler', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->moduleHandler],
    ];
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $entity_type = $this->createMock(EntityTypeInterface::class);

    $handler = PaymentMethodConfigurationAccessControlHandler::createInstance($container, $entity_type);
    $this->assertInstanceOf(PaymentMethodConfigurationAccessControlHandler::class, $handler);
  }

  /**
   * Gets a mock payment method configuration.
   *
   * @return \Drupal\payment\Entity\PaymentMethodConfiguration|\PHPUnit\Framework\MockObject\MockObject
   */
  protected function getMockPaymentMethodConfiguration() {
    $payment_method_configuration = $this->createMock(PaymentMethodConfigurationInterface::class);
    $payment_method_configuration->expects($this->any())
      ->method('getCacheContexts')
      ->willReturn([]);
    $payment_method_configuration->expects($this->any())
      ->method('getCacheTags')
      ->willReturn(['payment_method_configuration']);

    return $payment_method_configuration;
  }

  /**
   * @covers ::checkAccess
   */
  public function testCheckAccessWithoutPermission() {
    $operation = $this->randomMachineName();
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturn(FALSE);

    $payment_method_configuration = $this->getMockPaymentMethodConfiguration();

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertFalse($method->invokeArgs($this->sut, [$payment_method_configuration, $operation, $account])->isAllowed());
  }

  /**
   * @covers ::checkAccess
   */
  public function testCheckAccessWithAnyPermission() {
    $operation = $this->randomMachineName();
    $account = $this->createMock(AccountInterface::class);
    $map = [
      ['payment.payment_method_configuration.' . $operation . '.any', TRUE],
      ['payment.payment_method_configuration.' . $operation . '.own', FALSE],
    ];
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap($map);

    $payment_method_configuration = $this->getMockPaymentMethodConfiguration();

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertTrue($method->invokeArgs($this->sut, [$payment_method_configuration, $operation, $account])->isAllowed());
  }

  /**
   * @covers ::checkAccess
   */
  public function testCheckAccessWithOwnPermission() {
    $owner_id = mt_rand();
    $operation = $this->randomMachineName();
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->any())
      ->method('id')
      ->willReturn($owner_id);
    $map = [
      ['payment.payment_method_configuration.' . $operation . '.any', FALSE],
      ['payment.payment_method_configuration.' . $operation . '.own', TRUE],
    ];
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap($map);

    $payment_method_configuration = $this->getMockPaymentMethodConfiguration();
    $payment_method_configuration->expects($this->at(0))
      ->method('getOwnerId')
      ->willReturn($owner_id);
    $payment_method_configuration->expects($this->at(1))
      ->method('getOwnerId')
      ->willReturn($owner_id + 1);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertTrue($method->invokeArgs($this->sut, [$payment_method_configuration, $operation, $account])->isAllowed());
    $this->assertFalse($method->invokeArgs($this->sut, [$payment_method_configuration, $operation, $account])->isAllowed());
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider providerTestCheckAccessEnable
   */
  public function testCheckAccessEnable($expected, $payment_method_configuration_status, $has_update_permission) {
    $operation = 'enable';
    $account = $this->createMock(AccountInterface::class);
    $map = [
      ['payment.payment_method_configuration.update.any', $has_update_permission],
      ['payment.payment_method_configuration.update.own', FALSE],
    ];
    $account->expects($this->atLeastOnce())
      ->method('hasPermission')
      ->willReturnMap($map);

    $payment_method_configuration = $this->getMockPaymentMethodConfiguration();
    $payment_method_configuration->expects($this->atLeastOnce())
      ->method('status')
      ->willReturn($payment_method_configuration_status);

    $this->setUpLanguage($payment_method_configuration);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertSame($expected, $method->invokeArgs($this->sut, [$payment_method_configuration, $operation, $account])->isAllowed());

  }

  /**
   * Provides data to self::testCheckAccessEnable().
   */
  public function providerTestCheckAccessEnable() {
    return [
      // Enabled with permission.
      [FALSE, TRUE, TRUE],
      // Disabled with permission.
      [TRUE, FALSE, TRUE],
      // Disabled without permission.
      [FALSE, FALSE, FALSE],
    ];
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider providerTestCheckAccessDisable
   */
  public function testCheckAccessDisable($expected, $payment_method_configuration_status, $has_update_permission) {
    $operation = 'disable';
    $account = $this->createMock(AccountInterface::class);
    $map = [
      ['payment.payment_method_configuration.update.any', $has_update_permission],
      ['payment.payment_method_configuration.update.own', FALSE],
    ];
    $account->expects($this->atLeastOnce())
      ->method('hasPermission')
      ->willReturnMap($map);

    $payment_method_configuration = $this->getMockPaymentMethodConfiguration();
    $payment_method_configuration->expects($this->atLeastOnce())
      ->method('status')
      ->willReturn($payment_method_configuration_status);
    $this->setUpLanguage($payment_method_configuration);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertSame($expected, $method->invokeArgs($this->sut, [$payment_method_configuration, $operation, $account])->isAllowed());

  }

  /**
   * Provides data to self::testCheckAccessDisable().
   */
  public function providerTestCheckAccessDisable() {
    return [
      // Disabled with permission.
      [FALSE, FALSE, TRUE],
      // Enabled with permission.
      [TRUE, TRUE, TRUE],
      // Enabled without permission.
      [FALSE, TRUE, FALSE],
    ];
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider providerTestCheckAccessDuplicate
   */
  public function testCheckAccessDuplicate($expected, $has_create_permission, $has_view_permission) {
    $operation = 'duplicate';
    $bundle = $this->randomMachineName();
    $account = $this->createMock(AccountInterface::class);
    $map = [
      ['payment.payment_method_configuration.create.' . $bundle, $has_create_permission],
      ['payment.payment_method_configuration.view.any', $has_view_permission],
    ];
    $account->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap($map);

    $language = $this->createMock(LanguageInterface::class);

    $payment_method_configuration = $this->getMockPaymentMethodConfiguration();
    $payment_method_configuration->expects($this->atLeastOnce())
      ->method('bundle')
      ->willReturn($bundle);
    $payment_method_configuration->expects($this->any())
      ->method('language')
      ->willReturn($language);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkAccess');
    $method->setAccessible(TRUE);
    $this->assertSame($expected, $method->invokeArgs($this->sut, [$payment_method_configuration, $operation, $account])->isAllowed());

  }

  /**
   * Provides data to self::testCheckAccessDuplicate().
   */
  public function providerTestCheckAccessDuplicate() {
    return [
      // No create access.
      [FALSE, FALSE, TRUE],
      // Create access, with view permission.
      [TRUE, TRUE, TRUE],
      // Create access, without view permission.
      [FALSE, TRUE, FALSE],
      // No access.
      [FALSE, FALSE, FALSE],
    ];
  }

  /**
   * @covers ::checkCreateAccess
   */
  public function testCheckCreateAccess() {
    $bundle = $this->randomMachineName();
    $context = [];
    $account = $this->createMock(AccountInterface::class);
    $account->expects($this->once())
      ->method('hasPermission')
      ->with('payment.payment_method_configuration.create.' . $bundle)
      ->willReturn(TRUE);

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('checkCreateAccess');
    $method->setAccessible(TRUE);
    $this->assertTrue($method->invokeArgs($this->sut, [$account, $context, $bundle])->isAllowed());
  }

  /**
   * @covers ::getCache
   */
  public function testGetCache() {
    $account = $this->createMock(AccountInterface::class);
    $cache_id = $this->randomMachineName();
    $operation = $this->randomMachineName();
    $language_code = $this->randomMachineName();

    $class = new \ReflectionClass($this->sut);
    $method = $class->getMethod('getCache');
    $method->setAccessible(TRUE);
    $this->assertNull($method->invokeArgs($this->sut, [$cache_id, $operation, $language_code, $account]));
  }

  /**
   * Sets up the mock definitions for the language() method.
   *
   * @param \PHPUnit\Framework\MockObject\MockObject $payment_method_configuration
   *   A mock entity.
   */
  protected function setUpLanguage(MockObject $payment_method_configuration) {
    $language = $this->createMock(LanguageInterface::class);
    $language->expects($this->any())
      ->method('getId')
      ->willReturn($this->randomMachineName(2));
    $payment_method_configuration->expects($this->any())
      ->method('language')
      ->willReturn($language);
  }

}
