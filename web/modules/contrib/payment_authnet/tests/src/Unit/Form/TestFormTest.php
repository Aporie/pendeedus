<?php

namespace Drupal\Tests\payment_authnet\Unit\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\payment_authnet\Entity\AuthnetProfileInterface;
use Drupal\payment_authnet\Form\TestForm;
use Drupal\Tests\Core\Form\FormTestBase;
use net\authorize\api\constants\ANetEnvironment;
use net\authorize\api\contract\v1\MerchantAuthenticationType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

/**
 * @coversDefaultClass \Drupal\payment_authnet\Form\TestForm
 *
 * @group payment_authnet
 */
class TestFormTest extends FormTestBase {

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * The class under test.
   *
   * @var \Drupal\payment_authnet\Form\TestForm
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $this->container->set('string_translation', $this->getStringTranslationStub());

    $storage_controller = $this->createMock(EntityStorageInterface::class);
    $storage_controller
      ->expects($this->any())
      ->method('loadMultiple')
      ->willReturn(['p1' => $this->getAuthnetProfile('Authnet Profile 1'), 'p2' => $this->getAuthnetProfile('Authnet Profile 2')]);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager
      ->expects($this->any())
      ->method('getStorage')
      ->with('authnet_profile')
      ->willReturn($storage_controller);
    $this->container->set('entity_type.manager', $entityTypeManager);

    $this->container->set('datetime.time', $this->getMockBuilder(TimeInterface::class)->getMock());

    $flash_bag = new FlashBag();
    $killSwitch = new KillSwitch();
    $this->container->set('messenger', new Messenger($flash_bag, $killSwitch));

    \Drupal::setContainer($this->container);

    $this->sut = new TestForm($entityTypeManager, $this->container->get('messenger'));
  }

  /**
   * Helper method to get authnet profile.
   *
   * @param string $label
   *   Authnet profile label.
   *
   * @return \Drupal\payment_authnet\Entity\AuthnetProfileInterface|\PHPUnit\Framework\MockObject\MockObject
   *   Authnet profile mock.
   */
  private function getAuthnetProfile($label) {
    $authnetProfile = $this->createMock(AuthnetProfileInterface::class);
    $authnetProfile->expects($this->any())
      ->method('label')
      ->willReturn($label);
    $authnetProfile->expects($this->any())
      ->method('getApiKey')
      ->willReturn($this->randomMachineName());
    $authnetProfile->expects($this->any())
      ->method('getApiEnvironment')
      ->willReturn(ANetEnvironment::SANDBOX);

    $merchantAuthentication = new MerchantAuthenticationType();
    $merchantAuthentication->setName($this->randomMachineName());
    $merchantAuthentication->setTransactionKey($this->randomMachineName());
    $authnetProfile->expects($this->any())
      ->method('getMerchantAuthentication')
      ->willReturn($merchantAuthentication);
    return $authnetProfile;
  }

  /**
   * @covers ::create
   * @covers ::__construct
   */
  public function testCreate() {
    $sut = TestForm::create($this->container);
    $this->assertInstanceOf(TestForm::class, $sut);
  }

  /**
   * Tests the getFormId() method.
   *
   * @covers ::getFormId
   */
  public function testGetFormId() {
    $form_state = new FormState();
    $form_id = $this->formBuilder->getFormId($this->sut, $form_state);

    $this->assertSame('payment_authnet_test', $form_id);
    $this->assertSame(TestForm::class, get_class($form_state->getFormObject()));
  }

  /**
   * @covers ::buildForm
   *
   * @return array
   *   The form array returned by buildForm().
   */
  public function testBuildForm() {
    $form_array = [];
    $form_state_expected = new FormState();
    $expected_form = $this->sut->buildForm($form_array, $form_state_expected);

    $form_state = new FormState();
    $form = $this->formBuilder->buildForm($this->sut, $form_state);
    $this->assertFormElement($expected_form, $form, 'profileId');
    $this->assertFormElement($expected_form, $form, 'actions');
    $actual = ['p1' => 'Authnet Profile 1', 'p2' => 'Authnet Profile 2'];
    $this->assertArraySubset($actual, $form['profileId']['#options']);
    $this->assertEquals(TRUE, $form['profileId']['#required']);

    return $form;
  }

  /**
   * @covers ::submitForm
   *
   * @depends testBuildForm
   */
  public function testSubmitFormWithInvalidCredentials(array $form) {
    $form_state = new FormState();
    $form_state->setValue('profileId', 'p1');
    $this->sut->submitForm($form, $form_state);
    $errors = $this->sut->messenger()->messagesByType('error');
    $this->assertCount(1, $errors);
    $error = reset($errors);
    $this->assertContains('User authentication failed due to invalid authentication values.', $error);
  }

  /**
   * @covers ::submitForm
   *
   * @depends testBuildForm
   */
  public function testSubmitFormWithValidResponse(array $form) {
    $this->markTestIncomplete('@todo: Simulate valid response from authorize.net.');
  }

}
