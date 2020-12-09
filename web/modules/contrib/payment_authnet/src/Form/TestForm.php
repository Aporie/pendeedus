<?php

namespace Drupal\payment_authnet\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\payment_authnet\Exception;
use Drupal\payment_authnet\Exception\PaymentAuthnetException;
use Drupal\payment_authnet\Exception\PaymentAuthnetApiException;
use net\authorize\api\contract\v1\ANetApiResponseType;
use net\authorize\api\contract\v1\AuthenticateTestRequest;
use net\authorize\api\contract\v1\GetMerchantDetailsRequest;
use net\authorize\api\contract\v1\GetMerchantDetailsResponse;
use net\authorize\api\controller\AuthenticateTestController;
use net\authorize\api\controller\GetMerchantDetailsController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to configure maintenance settings for this site.
 */
class TestForm extends FormBase {

  /**
   * The list of Authnet Profiles.
   *
   * @var \Drupal\payment_authnet\Entity\AuthnetProfileInterface[]
   */
  protected $authnetProfiles;

  /**
   * Constructs a new TestForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Instance of entity type manager.
   * @param \MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    $this->authnetProfiles = $entity_type_manager->getStorage('authnet_profile')->loadMultiple();
    $this->setMessenger($messenger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('messenger'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'payment_authnet_test';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = ['' => $this->t('- Select profile -')];
    foreach ($this->authnetProfiles as $id => $authnet_profile) {
      $options[$id] = $authnet_profile->label();
    }
    $profile_ids = array_keys($this->authnetProfiles);

    $form['profileId'] = [
      '#title' => $this->t('Authorize.net profile'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => count($profile_ids) ? reset($profile_ids) : '',
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Test'),
    ];

    return $form;
  }

  /**
   * Helper function to send Authorize.net test request to verify credentials.
   *
   * @param string $profileId
   *   Authnet Profile machine name to validate.
   *
   * @return \net\authorize\api\contract\v1\AuthenticateTestResponse
   *   Authorize.net test response result.
   *
   * @throws Drupal\payment_authnet\Exception\PaymentAuthnetApiException
   */
  protected function sendTestRequest($profileId) {
    $profile = $this->authnetProfiles[$profileId];
    // Common setup for API credentials.
    $refId = $profile->getApiKey() . \Drupal::time()->getRequestTime();

    $testRequest = new AuthenticateTestRequest();
    $testRequest->setMerchantAuthentication($profile->getMerchantAuthentication());
    $testRequest->setRefId($refId);

    $controller = new AuthenticateTestController($testRequest);
    $response = $controller->executeWithApiResponse($profile->getApiEnvironment());

    if ($response->getRefId() != $refId) {
      throw new PaymentAuthnetApiException($this->t('Invalid Reference ID in response.'));
    }
    if ($response->getMessages()->getResultCode() != 'Ok') {
      throw new PaymentAuthnetApiException($this->getExceptionMessage($response));
    }
    return $response;
  }

  /**
   * Gets translated Exception message for paymentAuthnetApiException.
   *
   * @param \net\authorize\api\contract\v1\ANetApiResponseType $response
   *   Authnet API response.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translatable markup with formatted Exception message.
   */
  protected function getExceptionMessage(ANetApiResponseType $response) {
    return $this->t('Invalid Response: @resultCode. @message (@code)', [
      '@resultCode' => $response->getMessages()->getResultCode(),
      '@message' => $response->getMessages()->getMessage()[0]->getText(),
      '@code' => $response->getMessages()->getMessage()[0]->getCode(),
    ]);
  }

  /**
   * Get Merchant details.
   *
   * @param string $profileId
   *   Authnet Profile machine name to validate.
   *
   * @return \net\authorize\api\contract\v1\GetMerchantDetailsResponse
   *   Merchant details.
   */
  protected function getMerchantDetails($profileId) {
    $profile = $this->authnetProfiles[$profileId];

    $request = new GetMerchantDetailsRequest();
    $request->setMerchantAuthentication($profile->getMerchantAuthentication());
    $controller = new GetMerchantDetailsController($request);

    $response = $controller->executeWithApiResponse($profile->getApiEnvironment());
    if ($response->getMessages()->getResultCode() != 'Ok') {
      throw new PaymentAuthnetApiException($this->getExceptionMessage($response));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $profileId = $form_state->getValue('profileId');

    try {
      $testRequestResponse = $this->sendTestRequest($profileId);
      $this->messenger()->addStatus($this->t('Response returned @resultCode. @message (@code).', [
        '@resultCode' => $testRequestResponse->getMessages()->getResultCode(),
        '@message' => $testRequestResponse->getMessages()->getMessage()[0]->getText(),
        '@code' => $testRequestResponse->getMessages()->getMessage()[0]->getCode(),
      ]));

      $merchantDetails = $this->getMerchantDetails($profileId);
      $this->messenger()->addStatus($this->t('Merchant Name: @name', ['@name' => $merchantDetails->getMerchantName()]));
      $this->messenger()->addStatus($this->t('Gateway Id: @gateway_id', ['@gateway_id' => $merchantDetails->getGatewayId()]));
      $this->outputContactDetails($merchantDetails);
      foreach ($merchantDetails->getProcessors() as $processor) {
        $this->messenger()->addStatus($this->t('&nbsp;&nbsp;->Name: @name', ['@name' => $processor->getName()]));
      }

      $this->outputCurrencies($merchantDetails);
    }
    catch (PaymentAuthnetException $e) {
      $this->messenger()->addError($e->getMessage());
    }
    catch (Exception $e) {
      \Drupal::logger('payment_authnet')->error($e->getMessage());
    }

  }

  /**
   * Adds Drupal messages with contact details data.
   *
   * @param \net\authorize\api\contract\v1\GetMerchantDetailsResponse $merchantDetails
   *   Merchant details.
   */
  protected function outputContactDetails(GetMerchantDetailsResponse $merchantDetails) {
    foreach ($merchantDetails->getContactDetails() as $contact) {
      $this->messenger()->addStatus($this->t('&nbsp;&nbsp;-> Contact details: @first_name @last_name, @email', [
        '@first_name' => $contact->getFirstName(),
        '@last_name' => $contact->getLastName(),
        '@email' => $contact->getEmail(),
      ]));
    }
  }

  /**
   * Adds Drupal messages with currencies data.
   *
   * @param \net\authorize\api\contract\v1\GetMerchantDetailsResponse $merchantDetails
   *   Merchant details.
   */
  protected function outputCurrencies(GetMerchantDetailsResponse $merchantDetails) {
    foreach ($merchantDetails->getCurrencies() as $currency) {
      $currencies = \Drupal::entityTypeManager()->getStorage('currency')->loadByProperties(['currencyCode' => $currency]);
      if (count($currencies)) {
        $currencyEntity = reset($currencies);
        $this->messenger()->addStatus($this->t('&nbsp;&nbsp;->Currency: <a href=":href">:label</a>', [
          ':href' => $currencyEntity->toUrl()->toString(),
          ':label' => $currencyEntity->label(),
        ]));
      }
      else {
        $this->messenger()->addError($this->t('Currency @code used for this Merchant not found on the site.', [
          '@code' => $currency,
        ]));
      }
    }
  }

}
