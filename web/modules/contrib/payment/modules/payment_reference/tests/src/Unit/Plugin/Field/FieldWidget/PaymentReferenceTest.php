<?php

namespace Drupal\Tests\payment_reference\Unit\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment_reference\PaymentFactoryInterface;
use Drupal\payment_reference\Plugin\Field\FieldWidget\PaymentReference;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\payment_reference\Plugin\Field\FieldWidget\PaymentReference
 *
 * @group Payment Reference Field
 */
class PaymentReferenceTest extends UnitTestCase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The configuration the config factory returns.
   *
   * @see self::setUp
   *
   * @var array
   */
  protected $configFactoryConfiguration = [];

  /**
   * A user account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldDefinition;

  /**
   * The payment reference factory.
   *
   * @var \Drupal\payment_reference\PaymentFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $paymentFactory;

  /**
   * The class under test.
   *
   * @var \Drupal\payment_reference\Plugin\Field\FieldWidget\PaymentReference
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->fieldDefinition = $this->createMock(FieldDefinitionInterface::class);

    $this->configFactoryConfiguration = array(
      'payment_reference.payment_type' => array(
        'limit_allowed_plugins' => TRUE,
        'allowed_plugin_ids' => array($this->randomMachineName()),
        'plugin_selector_id' => $this->randomMachineName(),
      ),
    );

    $this->configFactory = $this->getConfigFactoryStub($this->configFactoryConfiguration);

    $this->currentUser = $this->createMock(AccountInterface::class);

    $this->paymentFactory = $this->createMock(PaymentFactoryInterface::class);

    $this->sut = new PaymentReference($this->randomMachineName(), [], $this->fieldDefinition, [], [], $this->configFactory, $this->currentUser, $this->paymentFactory);
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  function testCreate() {
    $container = $this->createMock(ContainerInterface::class);
    $map = array(
      array('config.factory', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->configFactory),
      array('current_user', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->currentUser),
      array('payment_reference.payment_factory', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->paymentFactory),
    );
    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $configuration = array(
      'field_definition' => $this->fieldDefinition,
      'settings' => [],
      'third_party_settings' => [],
    );
    $plugin_definition = [];
    $plugin_id = $this->randomMachineName();
    $sut = PaymentReference::create($container, $configuration, $plugin_id, $plugin_definition);
    $this->assertInstanceOf(PaymentReference::class, $sut);
  }

  /**
   * @covers ::formElement
   */
  public function testFormElement() {
    $entity_type_id = $this->randomMachineName();
    $bundle = $this->randomMachineName();
    $field_name = $this->randomMachineName();
    $user_id = mt_rand();
    $required = TRUE;

    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->atLeastOnce())
      ->method('bundle')
      ->willReturn($bundle);
    $entity->expects($this->atLeastOnce())
      ->method('getEntityTypeId')
      ->willReturn($entity_type_id);

    $this->fieldDefinition->expects($this->once())
      ->method('getName')
      ->willReturn($field_name);
    $this->fieldDefinition->expects($this->once())
      ->method('isRequired')
      ->willReturn($required);

    $payment = $this->createMock(PaymentInterface::class);

    $this->paymentFactory->expects($this->once())
      ->method('createPayment')
      ->with($this->fieldDefinition)
      ->willReturn($payment);

    $this->currentUser->expects($this->exactly(1))
      ->method('id')
      ->willReturn($user_id);

    $items = $this->getMockBuilder(FieldItemList::class)
      ->disableOriginalConstructor()
      ->getMock();
    $items->expects($this->atLeastOnce())
      ->method('getEntity')
      ->willReturn($entity);

    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $build = $this->sut->formElement($items, 3, [], $form, $form_state);
    $expected_build = array(
      'target_id' => array(
        '#default_value' => NULL,
        '#limit_allowed_plugin_ids' => $this->configFactoryConfiguration['payment_reference.payment_type']['allowed_plugin_ids'],
        '#plugin_selector_id' => $this->configFactoryConfiguration['payment_reference.payment_type']['plugin_selector_id'],
        '#prototype_payment' => $payment,
        '#queue_category_id' => $entity_type_id . '.' . $bundle . '.' . $field_name,
        '#queue_owner_id' => (int) $user_id,
        '#required' => $required,
        '#type' => 'payment_reference',
      ),
    );
    $this->assertSame($expected_build, $build);
  }

  /**
   * @covers ::massageFormValues
   */
  public function testMassageFormValues() {
    $field_name = $this->randomMachineName();
    $payment_id = mt_rand();

    $this->fieldDefinition->expects($this->atLeastOnce())
      ->method('getName')
      ->willReturn($field_name);

    $form_state = $this->createMock(FormStateInterface::class);
    $form[$field_name]['widget']['target_id']['#value'] = $payment_id;
    $values = [];

    $expected_value = array(
      'target_id' => $payment_id,
    );
    $this->assertSame($expected_value, $this->sut->massageFormValues($values, $form, $form_state));
  }

}
