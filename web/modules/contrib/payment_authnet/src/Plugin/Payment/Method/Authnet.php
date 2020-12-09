<?php

namespace Drupal\payment_authnet\Plugin\Payment\Method;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\EventDispatcherInterface;
use Drupal\payment\OperationResult;
use Drupal\payment\Payment;
use Drupal\payment\Plugin\Payment\LineItem\PaymentLineItemInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Drupal\payment_authnet\AdditionalFieldsTrait;
use Drupal\payment_authnet\Entity\AuthnetProfile;
use Drupal\payment_authnet\Exception\PaymentAuthnetException;
use Drupal\payment_authnet\Exception\PaymentAuthnetResponseException;
use net\authorize\api\contract\v1\CustomerAddressType;
use net\authorize\api\contract\v1\CreditCardType;
use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\contract\v1\CustomerDataType;
use net\authorize\api\contract\v1\GetTransactionDetailsRequest;
use net\authorize\api\contract\v1\LineItemType;
use net\authorize\api\contract\v1\OrderType;
use net\authorize\api\contract\v1\PaymentType;
use net\authorize\api\contract\v1\TransactionDetailsType;
use net\authorize\api\contract\v1\TransactionRequestType;
use net\authorize\api\contract\v1\TransactionResponseType;
use net\authorize\api\contract\v1\UserFieldType;
use net\authorize\api\controller\CreateTransactionController;
use net\authorize\api\controller\GetTransactionDetailsController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A payment method using Authnet.
 *
 * Plugins that extend this class must have the following keys in their plugin
 * definitions:
 * - entity_id: (string) The ID of the payment method entity the plugin is for.
 * - execute_status_id: (string) The ID of the payment status plugin to set at
 *   payment execution.
 * - capture: (boolean) Whether or not payment capture is supported.
 * - capture_status_id: (string) The ID of the payment status plugin to set at
 *   payment capture.
 * - refund: (boolean) Whether or not payment refunds are supported.
 * - refund_status_id: (string) The ID of the payment status plugin to set at
 *   payment refund.
 *
 * @PaymentMethod(
 *   deriver = "Drupal\payment_authnet\Plugin\Payment\Method\AuthnetDeriver",
 *   id = "payment_authnet",
 *   operations_provider = "\Drupal\payment_authnet\Plugin\Payment\Method\AuthnetOperationsProvider",
 *   execute_status_id = "payment_authorized",
 *   capture = 1,
 *   capture_status_id = "payment_success",
 *   refund = 1,
 *   refund_status_id = "payment_refunded",
 * )
 */
class Authnet extends PaymentMethodBase implements ContainerFactoryPluginInterface, AuthnetInterface {

  use AdditionalFieldsTrait;

  /**
   * The payment status manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * The AnetAPI payment object (contains info about credit card).
   *
   * @var \net\authorize\api\contract\v1\PaymentType
   */
  protected $AnetApiPayment;

  /**
   * The AnetAPI transaction request object (contains info about transaction).
   *
   * @var \net\authorize\api\contract\v1\TransactionRequestType
   */
  protected $transactionRequest;

  /**
   * The AnetAPI transaction request Type.
   *
   * @var string
   */
  protected $transactionType;

  /**
   * The AnetAPI transaction request Type.
   *
   * @var \net\authorize\api\contract\v1\TransactionDetailsType
   */
  protected $transactionDetails;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Custom Fields Values.
   *
   * @var array
   */
  protected $customFieldsValues;

  /**
   * Authnet Profile.
   *
   * @var \Drupal\payment_authnet\Entity\AuthnetProfileInterface
   */
  protected $AuthnetProfile;

  /**
   * The cache object associated with the 'data' bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\payment\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Utility\Token $token
   *   The token API.
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface $payment_status_manager
   *   The payment status manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache object associated with the 'data' bin.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher, Token $token, PaymentStatusManagerInterface $payment_status_manager, EntityFieldManagerInterface $entity_field_manager, CacheBackendInterface $cache) {
    // Token service is required for additional fields default configuration.
    $this->token = $token;
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $event_dispatcher, $token, $payment_status_manager);
    $this->entityFieldManager = $entity_field_manager;
    $this->AuthnetProfile = AuthnetProfile::load($this->pluginDefinition['profile']);
    $this->AnetApiPayment = new PaymentType();
    $this->transactionType = self::AUTH_ONLY_TRANSACTION;
    $this->refId = $this->AuthnetProfile->getApiKey() . '_' . \Drupal::time()->getRequestTime();
    $this->transactionDetails = new TransactionDetailsType();
    $this->transactionRequest = new TransactionRequestType();
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('payment.event_dispatcher'),
      $container->get('token'),
      $container->get('plugin.manager.payment.status'),
      $container->get('entity_field.manager'),
      $container->get('cache.data')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $elements = parent::buildConfigurationForm($form, $form_state);

    // Add credit card fields.
    $elements['credit_card_number'] = [
      '#type' => 'creditfield_cardnumber',
      '#title' => $this->t('Credit Card Number'),
      '#description' => $this->t('Your 16 digits credit card number.'),
      '#maxlength' => 16,
      '#size' => 16,
      '#required' => TRUE,
    ];

    $elements['expiration_date'] = [
      '#type' => 'creditfield_expiration',
      '#title' => $this->t('Expiration Date'),
      '#required' => TRUE,
    ];

    $elements['credit_card_cvv'] = [
      '#type' => 'creditfield_cardcode',
      '#title' => $this->t('CVV Code'),
      '#maxlength' => 4,
      '#size' => 4,
      '#description' => $this->t('Your 3 or 4 digit security code on the back of your card.'),
      '#required' => TRUE,
    ];
    return $elements + $this->addAdditionalFields();
  }

  /**
   * Save field value in customFieldValues.
   *
   * @param array $fs_values_field
   *   List of field values.
   * @param string $field_name
   *   Field name.
   *
   * @see \Drupal\payment_authnet\Plugin\Payment\Method\Authnet::submitConfigurationForm()
   */
  protected function setCustomFieldValue(array $fs_values_field, $field_name) {
    if (count($fs_values_field) > 1) {
      foreach ($fs_values_field as $key => $field) {
        $this->customFieldsValues[$field_name][$key] = reset($field);
      }
    }
    else {
      $first_value = reset($fs_values_field);
      $this->customFieldsValues[$field_name] = reset($first_value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Create the payment data for a credit card.
    $card = $form_state->getValues()['container']['plugin_form'];
    $this->setCreditCard($card['credit_card_number'], $card['expiration_date'], $card['credit_card_cvv']);

    // Save last 4 digits of credit card and expiration date in database.
    // This is useful for refund operations.
    unset($card['credit_card_cvv']);
    $card['credit_card_number'] = substr($card['credit_card_number'], -4);
    $this->setConfiguration($card);

    // Temporarily save other form field values.
    // @todo Find a better way to process form field values.
    // @see https://www.drupal.org/project/payment/issues/2841035
    // @todo Save vaules from address field in appropriate places.
    $fs_values = $form_state->getValues();
    foreach ($this->entityFieldManager->getFieldDefinitions('payment', $this->getPayment()->bundle()) as $field_name => $field_definition) {
      if (!$field_definition->getFieldStorageDefinition()->isBaseField() && isset($fs_values[$field_name])) {
        $this->setCustomFieldValue($fs_values[$field_name], $field_name);
      }
    }
    if (isset($card['billing_information'])) {
      $this->prepareBillingData($card['billing_information']);
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * Prepares customer address type object for AnetApi.
   *
   * @param array $billing_information
   *   Array with Billing information to set.
   *
   * @return \net\authorize\api\contract\v1\CustomerAddressType
   *   Instance of Customer Address Type object.
   */
  protected function prepareBillingData(array $billing_information = []) {
    if (!isset($this->billTo)) {
      $this->billTo = new CustomerAddressType();
    }
    foreach ($billing_information as $key => $value) {
      $method_name = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
      if (method_exists($this->billTo, $method_name)) {
        $this->billTo->$method_name($value);
      }
    }
    return $this->billTo;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'transactionId' => NULL,
      'transactionIds' => [],
      'credit_card_number' => NULL,
      'expiration_date' => NULL,
      'transaction_status' => NULL,
      'transaction_type' => NULL,
      'card_type' => NULL,
      // 'P' means CVV (card code verification) was not processed.
      // See https://developer.authorize.net/api/reference for more details.
      'card_code_response' => 'P',
      'response_code' => NULL,
      'cavv_response' => NULL,
      'email' => NULL,
      // Usually this is payment ID, but not always for refund operations.
      'invoice_number' => NULL,
      // This value will be > 0 for payments which had been refunded.
      'refunded_amount' => 0,
    ] + $this->additionalFieldsDefaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedCurrencies() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecuteStatusId() {
    return $this->pluginDefinition['execute_status_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelStatusId() {
    return $this->pluginDefinition['cancel_status_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelZeroAmount() {
    return $this->pluginDefinition['cancel_zero_amount'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCaptureStatusId() {
    return $this->pluginDefinition['capture_status_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCapture() {
    return $this->pluginDefinition['capture'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRefundStatusId() {
    return $this->pluginDefinition['refund_status_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCloneRefunded() {
    return $this->pluginDefinition['clone_refunded'];
  }

  /**
   * {@inheritdoc}
   */
  public function getRefund() {
    return $this->pluginDefinition['refund'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPartialRefundStatusId() {
    return $this->pluginDefinition['partial_refund_status_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPartialRefund() {
    return $this->pluginDefinition['refund'] && $this->pluginDefinition['partial_refund'];
  }

  /**
   * Gets Authorize.net PaymentType object.
   *
   * @return \net\authorize\api\contract\v1\PaymentType
   *   Authorize.net paymnet type object.
   */
  protected function getAnetApiPayment() {
    return $this->AnetApiPayment;
  }

  /**
   * Sets Authorize.net Transaction Type.
   *
   * Sets $this->transactionType. Possible values:
   * - self::AUTH_ONLY_TRANSACTION
   * - self::PRIOR_AUTH_CAPTURE_TRANSACTION
   * - self::AUTH_CAPTURE_TRANSACTION
   * - self::REFUND_TRANSACTION.
   *
   * @param string $operation
   *   Payment Status ID to identify the operation that needs to occur.
   *   Possible values: payment_authorized, payment_success, payment_refunded,
   *   payment_partially_refunded.
   *
   * @see payment_authnet.module
   * @see https://developer.authorize.net/api/reference/#payment-transactions
   * @see \net\authorize\api\contract\v1\TransactionRequestType::setTransactionType()
   *
   * @return $this
   */
  protected function setTransactionType($operation) {
    $transaction_details = $this->getTransactionDetails();

    // Check if operation accepts money transfer.
    if (in_array($operation, ['payment_success', 'payment_money_transferred'])) {
      $this->transactionType = $transaction_details->getTransactionType() == self::AUTH_ONLY_TRANSACTION
        ? self::PRIOR_AUTH_CAPTURE_TRANSACTION
        : self::AUTH_CAPTURE_TRANSACTION;
    }

    // Check if status ancestor accepts money transfer.
    $payment_status = $this->getPayment()->getPaymentStatus();
    $ancestors = $payment_status->getAncestors();
    if ('payment_partially_refunded' != $payment_status && in_array('payment_money_transferred', $ancestors)) {
      $this->transactionType = $transaction_details->getTransactionType() == self::AUTH_ONLY_TRANSACTION
        ? self::PRIOR_AUTH_CAPTURE_TRANSACTION
        : self::AUTH_CAPTURE_TRANSACTION;
    }

    // Check if this is void operation.
    if ('payment_cancelled' == $operation && !in_array($transaction_details->getTransactionStatus(), [
      'settledSuccessfully',
      'refundSettledSuccessfully',
    ])) {
      $this->transactionType = self::VOID_TRANSACTION;
    }
    // Check if this is refund operation.
    elseif (in_array($operation, ['payment_refunded', 'payment_partially_refunded'])
      && in_array($transaction_details->getTransactionType(), [
        'authCaptureTransaction',
        'authOnlyTransaction',
        'refundTransaction',
      ])
      && in_array($transaction_details->getTransactionStatus(), [
        'settledSuccessfully',
        'refundSettledSuccessfully',
      ])) {
      $this->transactionType = self::REFUND_TRANSACTION;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionType() {
    if (!$this->transactionType) {
      throw new \LogicException($this->t('Transaction type is not set'));
    }
    return $this->transactionType;
  }

  /**
   * Creates the Authorize.net Transaction and sets needed props.
   *
   * @see \Drupal\payment_authnet\Plugin\Payment\Method\Authnet::getAnetApiTransaction()
   *
   * @return $this
   */
  protected function setAnetApiTransactionRequest() {
    $this->transactionRequest->setTransactionType($this->getTransactionType());
    $this->transactionRequest->setAmount($this->getPayment()->getAmount());
    $this->transactionRequest->setOrder($this->prepareAnetApiOrder());
    $this->transactionRequest->setLineItems($this->getAnetApiLineItems());
    $this->transactionRequest->setCustomer($this->prepareAnetApiCustomerData());
    $this->transactionRequest->setBillTo($this->prepareBillingData());
    $transaction_id = $this->getTransactionId();
    // For authorized cards we don't need payment info but need Transaction ID.
    if ($transaction_id) {
      $this->transactionRequest->setRefTransId($transaction_id);
      // For refund operations we also need last 4 digits of credit card number
      // and expiration date.
      if ($this->getTransactionType() == self::REFUND_TRANSACTION) {
        $credit_card_number = $this->getConfiguration('credit_card_number');
        $expiration_date = $this->getConfiguration('expiration_date');
        $this->setCreditCard($credit_card_number, $expiration_date);
        $this->transactionRequest->setPayment($this->getAnetApiPayment());
      }
    }
    // For new payments set Payment data (authorize or authorize and capture
    // operations).
    else {
      $this->transactionRequest->setPayment($this->getAnetApiPayment());
    }

    return $this;
  }

  /**
   * Creates a new Authorize.net CreditCardType object.
   *
   * Creates and sets a Authorize.net CreditCardType object for
   * $this->AnetApiPayment.
   *
   * @param string $card_number
   *   Credit card number (4 or 20 digits).
   * @param string $expiration_date
   *   Credit card expiration date in format YYYY-MM.
   * @param string $cvv
   *   Optional CVV code (3 or 4 digits) (required for first transactions).
   *
   * @return $this
   */
  protected function setCreditCard($card_number, $expiration_date, $cvv = NULL) {
    $creditCard = new CreditCardType();
    $creditCard->setCardNumber($card_number);
    $creditCard->setExpirationDate($expiration_date);
    $creditCard->setCardCode($cvv);

    $this->AnetApiPayment->setCreditCard($creditCard);

    return $this;
  }

  /**
   * Returns the list of line items for ANetApi Transaction.
   *
   * @return \net\authorize\api\contract\v1\LineItemType[]
   *   Array of objects.
   */
  protected function getAnetApiLineItems() {
    $line_items = [];
    foreach ($this->getPayment()->getLineItems() as $payment_line_item) {
      $line_item = new LineItemType();
      $line_item->setItemId($payment_line_item->getPluginId());
      $line_item->setName(empty($payment_line_item->getName()) ? $payment_line_item->getPluginId() : $payment_line_item->getName());
      $line_item->setQuantity($payment_line_item->getQuantity());
      $line_item->setDescription($payment_line_item->getDescription());
      $line_item->setUnitPrice($payment_line_item->getAmount());
      $line_items[] = $line_item;
    }
    return $line_items;
  }

  /**
   * Prepares the customer's identifying information for Authnet transactions.
   *
   * @return \net\authorize\api\contract\v1\CustomerDataType
   *   Customer Data for Anet Transaction.
   */
  protected function prepareAnetApiCustomerData() {
    $customerData = new CustomerDataType();
    $customerData->setType(self::CUSTOMER_DATA_TYPE);
    $customerData->setId(strval($this->getPayment()->getOwner()->id()));
    $customerData->setEmail($this->getPayment()->getOwner()->getEmail());
    $this->setConfiguration(['email' => $this->getPayment()->getOwner()->getEmail()]);
    return $customerData;
  }

  /**
   * Creates order information.
   *
   * @see \Drupal\payment_authnet\Plugin\Payment\Method\Authnet::getAnetApiTransactionRequest()
   *
   * @return \net\authorize\api\contract\v1\OrderType
   *   Authnet API Order object.
   */
  protected function prepareAnetApiOrder() {
    $order = new OrderType();
    $order->setInvoiceNumber($this->getPayment()->id());

    return $order;
  }

  /**
   * Creates Authorize.net transaction.
   *
   * @see \Drupal\payment_authnet\Plugin\Payment\Method\Authnet::doExecutePayment()
   *
   * @return \net\authorize\api\contract\v1\TransactionRequestType
   *   Authnet API Transaction Request object.
   */
  protected function getAnetApiTransactionRequest() {
    $this->setAnetApiTransactionRequest();

    // Add some merchant defined fields. These fields won't be stored with
    // the transaction, but will be echoed back in the response.
    // @todo Find the more accurate way to get field values.
    if (isset($this->customFieldsValues) && is_array($this->customFieldsValues)) {
      foreach ($this->customFieldsValues as $field_name => $field_value) {
        $merchantDefinedField = new UserFieldType();
        $merchantDefinedField->setName($field_name);
        $merchantDefinedField->setValue(is_string($field_value) ? $field_value : Json::encode($field_value));
        $this->transactionRequest->addToUserFields($merchantDefinedField);
      }
    }
    return $this->transactionRequest;
  }

  /**
   * Assemble the complete transaction request.
   *
   * @return \net\authorize\api\contract\v1\TransactionResponseType
   *   Transaction Response.
   *
   * @throws Drupal\payment_authnet\Exception\PaymentAuthnetResponseException
   */
  protected function sendRequest() {
    $request = new CreateTransactionRequest();
    $request->setMerchantAuthentication($this->AuthnetProfile->getMerchantAuthentication());
    $request->setRefId($this->getRefId());
    $request->setTransactionRequest($this->getAnetApiTransactionRequest());
    // Create the controller and get the response.
    $controller = new CreateTransactionController($request);
    $response = $controller->executeWithApiResponse($this->AuthnetProfile->getApiEnvironment());

    if ($response->getRefId() != $this->getRefId()) {
      throw new PaymentAuthnetResponseException($this->t('Invalid Reference ID in response. Error code: @code, Error message: @message', [
        '@code' => $response->getMessages()->getResultCode(),
        '@message' => $response->getMessages()->getMessage()[0]->getText(),
      ]));
    }
    $tresponse = $response->getTransactionResponse();
    // Check to see if the API request was not successful.
    if ($response->getMessages()->getResultCode() != 'Ok') {
      if ($tresponse->getErrors() != NULL) {
        $message = $tresponse->getErrors()[0]->getErrorText();
        $code = $tresponse->getErrors()[0]->getErrorCode();
      }
      else {
        $message = $response->getMessages()->getResultCode() . ': ' . $response->getMessages()->getMessage()[0]->getText();
        $code = 0;
      }
      throw new PaymentAuthnetResponseException($message, $code);
    }

    return $tresponse;
  }

  /**
   * Helper function used for doXXXXPayment() functions.
   *
   * @param string $operation
   *   Payment Status ID to identify the operation that needs to occur.
   *   Possible values: payment_authorized, payment_success, payment_refunded.
   *
   * @return bool
   *   TRUE if operation was successful, FALSE otherwise.
   */
  protected function processResults($operation) {
    $this->setTransactionType($operation);
    try {
      /** @var \net\authorize\api\contract\v1\TransactionResponseType $response */
      $response = $this->sendRequest();
      // Since the API request was successful, look for a transaction response
      // and parse it to display the results of authorizing the card.
      switch ($response->getResponseCode()) {
        case 1:
          $message_type = $this->messenger()::TYPE_STATUS;
          break;

        case 4:
          $message_type = $this->messenger()::TYPE_WARNING;
          break;

        default:
          $message_type = $this->messenger()::TYPE_ERROR;
          break;
      }
      $messages = $response->getMessages();
      if (count($messages)) {
        $this->messenger()->addMessage($messages[0]->getDescription(), $message_type);
      }
      else {
        \Drupal::logger('payment_authnet')->log($message_type, print_r($response, 1));
      }

      $this->saveTransactionResponseData($response);
      return TRUE;
    }
    catch (PaymentAuthnetException $e) {
      $this->messenger()->addError($e->getMessage());
      \Drupal::logger('payment_authnet')->error($e->getMessage() . ' (' . $e->getCode() . ")|" . $e->getTraceAsString());
    }
    catch (Exception $e) {
      \Drupal::logger('payment_authnet')->error($e->getMessasge());
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionDetails($refresh = FALSE) {
    $transaction_id = $this->getTransactionId();
    if ((!$refresh && $this->transactionDetails->getRefTransId()) || empty($transaction_id)) {
      return $this->transactionDetails;
    }

    $transaction_details = &drupal_static('payment_authnet_transaction_details', []);
    if (isset($transaction_details[$transaction_id])) {
      return $transaction_details[$transaction_id];
    }
    $cached_data = $this->cache->get($this->getTransactionDetailsCacheKey());
    if (!$refresh && $cached_data) {
      $transaction_details[$transaction_id] = $cached_data->data;
      return $transaction_details[$transaction_id];
    }
    $request = new GetTransactionDetailsRequest();
    $request->setMerchantAuthentication($this->AuthnetProfile->getMerchantAuthentication());
    $request->setRefId($this->getRefId());
    $request->setTransId($transaction_id);

    // Create the controller and get the response.
    try {
      $controller = new GetTransactionDetailsController($request);
      $response = $controller->executeWithApiResponse($this->AuthnetProfile->getApiEnvironment());
      if ($response->getRefId() != $this->getRefId()) {
        $errorMessages = $response->getMessages()->getMessage();
        throw new PaymentAuthnetResponseException($this->t('Authorize.net error for payment @payment_id: Invalid Reference ID in response. @code: @message (@status)', [
          '@payment_id' => $this->getPayment()->id(),
          '@code' => $response->getMessages()->getResultCode(),
          '@message' => $response->getMessages()->getMessage()[0]->getText(),
          '@status' => $errorMessages[0]->getCode(),
        ]));
      }
      if ($response->getMessages()->getResultCode() != 'Ok') {
        $errorMessages = $response->getMessages()->getMessage();
        throw new PaymentAuthnetResponseException($this->t('Authorize.net error for payment @payment_id: @message (@status)', [
          '@payment_id' => $this->getPayment()->id(),
          '@message' => $errorMessages[0]->getText(),
          '@status' => $errorMessages[0]->getCode(),
        ]));
      }

      $this->transactionDetails = $response->getTransaction();
    }
    catch (PaymentAuthnetResponseException $e) {
      $this->messenger()->addError($e->getMessage(), FALSE);
    }

    $transaction_details[$transaction_id] = $this->transactionDetails;
    $this->updateTransactionDetailsData($this->transactionDetails);
    $this->cache->set($this->getTransactionDetailsCacheKey(), $this->transactionDetails, $this->getCacheExpirationTime(), ['payment:' . $this->getPayment()->id()]);
    return $this->transactionDetails;
  }

  /**
   * Get Transaction details cache key.
   *
   * @return string
   *   Cache key.
   */
  protected function getTransactionDetailsCacheKey() {
    return 'payment_authnet_transaction_details:' . $this->getTransactionId() . ':' . $this->getConfiguration('transaction_status', 'unknown');
  }

  /**
   * Get Transaction Details Cache Expiration timestamp.
   *
   * Depending on transaction cache expiration time may vary.
   *
   * @return string
   *   Unix timestamp or -1 when transaction details should expire.
   */
  protected function getCacheExpirationTime() {
    $current_timestamp = \Drupal::time()->getCurrentTime();
    $transaction_status = $this->getConfiguration('transaction_status', 'unknown');
    $cache_max_age = \Drupal::config('system.performance')->get('cache.page.max_age');

    switch ($transaction_status) {
      // These statuses doesn't require to be updated at all.
      case 'authorizedPendingCapture':
      case 'declined':
      case 'expired':
      case 'returnedItem':
      case 'refundSettledSuccessfully':
      case 'settledSuccessfully':
      case 'voided':
        $result = Cache::PERMANENT;
        break;

      // These statuses need to be checked from time to time.
      case 'approvedReview':
      case 'capturedPendingSettlement':
      case 'refundPendingSettlement':
      case 'underReview':
        $result = min([$current_timestamp + 60 * 60, $current_timestamp + $cache_max_age]);

      default:
        $result = $current_timestamp + $cache_max_age;
        break;
    }

    return $result;
  }

  /**
   * Update AnetApi response data in configuration (save in database).
   *
   * @param \net\authorize\api\contract\v1\TransactionDetailsType $response
   *   Transaction details type response.
   */
  protected function updateTransactionDetailsData(TransactionDetailsType $response) {
    $data = $this->getConfiguration();
    $data['transaction_status'] = $response->getTransactionStatus();
    $data['transaction_type'] = $response->getTransactionType();
    if ($response->getPayment()) {
      $data['card_type'] = $response->getPayment()->getCreditCard()->getCardType();
    }
    if ($response->getCustomer()) {
      $data['email'] = $response->getCustomer()->getEmail();
    }
    $data['card_code_response'] = $response->getCardCodeResponse();
    $data['response_code'] = $response->getResponseCode();
    $data['cavv_response'] = (string) $response->getCAVVResponse();
    if ($response->getOrder()) {
      $data['invoice_number'] = $response->getOrder()->getInvoiceNumber();
    }
    if ($data === $this->getConfiguration()) {
      return;
    }
    $this->setConfiguration($data);
    if (!$this->getPayment()->isNew()) {
      $this->getPayment()->save();
    }
  }

  /**
   * Save AnetApi response data into plugin configuration.
   *
   * @param \net\authorize\api\contract\v1\TransactionResponseType $response
   *   Transaction details response object.
   */
  protected function saveTransactionResponseData(TransactionResponseType $response) {
    $data = [
      'transactionId' => $response->getTransId(),
      'card_type' => $response->getAccountType(),
      'card_code_response' => $response->getCvvResultCode(),
      'response_code' => $response->getResponseCode(),
      'cavv_response' => (string) $response->getCavvResultCode(),
    ];

    $data['transactionIds'] = $this->getConfiguration('transactionIds') ?: [];
    // We need to keep history of transactions in order to support partial
    // refunds.
    $data['transactionIds'][(string) $data['transactionId']] = $this->getTransactionType();
    $this->setConfiguration($data);
  }

  /**
   * {@inheritdoc}
   */
  protected function doExecutePayment() {
    if ($this->processResults($this->getExecuteStatusId())) {
      $this->getPayment()->setPaymentStatus($this->paymentStatusManager->createInstance($this->getExecuteStatusId()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function doCapturePayment() {
    if ($this->processResults($this->getCaptureStatusId())) {
      $this->getPayment()->setPaymentStatus($this->paymentStatusManager->createInstance($this->getCaptureStatusId()));
      $this->getPayment()->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function doCapturePaymentAccess(AccountInterface $account) {
    $payment_status = $this->getPayment()->getPaymentStatus()->getPluginId();
    return $this->getCapture()
      && !in_array($payment_status, [
        $this->getCaptureStatusId(),
        $this->getRefundStatusId(),
        $this->getPartialRefundStatusId(),
        $this->getCancelStatusId(),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function doRefundPayment() {
    // Try to perform refund payment, and if unsuccess, do nothing.
    if (!$this->processResults($this->getRefundStatusId())) {
      return;
    }

    $payment_unchanged = \Drupal::entityTypeManager()->getStorage('payment')->loadUnchanged($this->getPayment()->id());
    // Get payment/clone.
    $payment = $this->getCloneRefunded() ? $this->getPayment()->createDuplicate() : $this->getPayment();
    // Save refund amount into original payment.
    $configuration = $this->getConfiguration();
    $refunded_amount = $this->getConfiguration('refunded_amount', 0);
    $configuration['refunded_amount'] = $refunded_amount + $payment->getAmount();

    // If refund operation creates new payment entity.
    if ($this->getCloneRefunded()) {
      $config_unchanged = $payment_unchanged->getPaymentMethod()->getConfiguration();
      $config_unchanged['refunded_amount'] = $configuration['refunded_amount'];
      $payment_unchanged->getPaymentMethod()->setConfiguration($config_unchanged);
      // Save original payment with refund amount and replace it by clone.
      $payment_unchanged->save();
      $payment->set('created', \Drupal::time()->getRequestTime());
      // Remove transaction status to reset cache.
      $configuration['transaction_status'] = $configuration['transaction_type'] = '';
      $this->setConfiguration($configuration);
      // Set clone as main payment.
      $payment->setPaymentMethod($this);
      // Invert amount for payment line items. Useful for refund operations.
      $payment->setLineItems(array_map(function (PaymentLineItemInterface $line_item) {
        return $line_item->setAmount(0 - $line_item->getAmount());
      }, $payment->getLineItems()));
      $this->setPayment($payment);
    }

    if (abs($payment->getAmount()) == $payment_unchanged->getAmount()) {
      $payment->setPaymentStatus($this->paymentStatusManager->createInstance($this->getRefundStatusId()));
    }
    elseif ($this->getPartialRefund()) {
      $this->updateLineItemsAfterPartialRefund($payment_unchanged);
    }
    // Refresh Authnet transactionDetails.
    $this->getTransactionDetails(TRUE);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function doRefundPaymentAccess(AccountInterface $account) {
    return
      $this->getRefund()
      && $this->getPayment()->getPaymentStatus()->getPluginId() != $this->getRefundStatusId()
      && $this->getConfiguration('credit_card_number')
      && $this->getConfiguration('expiration_date')
      && $this->getConfiguration('refunded_amount', 0) < $this->getPayment()->getAmount()
      && $this->getTransactionId(TRUE)
      && in_array($this->getTransactionDetails()->getTransactionStatus(), [
        'settledSuccessfully',
        'refundSettledSuccessfully',
      ]);
  }

  /**
   * Updates Line items after Partial Refund.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment_unchanged
   *   Unchanged payment, loaded from database.
   */
  protected function updateLineItemsAfterPartialRefund(PaymentInterface $payment_unchanged) {
    $this->getPayment()->setPaymentStatus($this->paymentStatusManager->createInstance($this->getPartialRefundStatusId()));
    // Do nothing if refund operation creates new payment entity.
    if ($this->getCloneRefunded()) {
      return;
    }
    $payment = $this->getPayment();
    $line_items = $payment->getLineItems();
    $line_items_unchanged = $payment_unchanged->getLineItems();

    foreach ($line_items_unchanged as $line_item_name => $line_item_unchanged) {
      // Return non-refunded line item back to payment.
      // @see \Drupal\payment_authnet\PaymentRefundFormAlter::submitForm()
      if (!isset($line_items[$line_item_name])) {
        $payment->setLineItem($line_item_unchanged);
      }
      // Calculate the amount what left non-refunded for each line item.
      elseif ($line_item_unchanged->getAmount() - $line_items[$line_item_name]->getAmount() > 0) {
        $line_item_unchanged->setAmount($line_item_unchanged->getAmount() - $line_items[$line_item_name]->getAmount());
        $payment->setLineItem($line_item_unchanged);
      }
    }
  }

  /**
   * Gets appropriate Transaction ID.
   *
   * For refund operations only "SettledSuccessfully" transaction ID is valid,
   * so try to search it in history.
   *
   * @param bool $for_refund
   *   Must be TRUE to get valid transaction ID for refund operations.
   *
   * @return string
   *   Transaction ID.
   */
  public function getTransactionId($for_refund = FALSE) {
    $is_refund = $for_refund ?: self::REFUND_TRANSACTION == $this->getTransactionType();
    // For refund transactions search for last
    // self::PRIOR_AUTH_CAPTURE_TRANSACTION or
    // self::AUTH_CAPTURE_TRANSACTION.
    if ($is_refund) {
      $transactions = array_reverse($this->getConfiguration('transactionIds', []), TRUE);
      $transactionId = array_search(self::PRIOR_AUTH_CAPTURE_TRANSACTION, $transactions)
        ?: array_search(self::AUTH_CAPTURE_TRANSACTION, $transactions);
    }

    return !empty($transactionId) ? $transactionId : $this->getConfiguration('transactionId');
  }

  /**
   * {@inheritdoc}
   */
  public function updatePaymentStatusAccess(AccountInterface $account) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function getSettablePaymentStatuses(AccountInterface $account, PaymentInterface $payment) {
    return [
      'payment_pending',
      'payment_success',
      'payment_authorized',
      'payment_refunded',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration($key = NULL, $default_value = NULL) {
    if ($key) {
      return empty($this->configuration[$key]) ? $default_value : $this->configuration[$key];
    }
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefId() {
    return $this->refId;
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment() {
    if (!$this->getPayment()) {
      throw new \LogicException('Trying to void a non-existing payment. A payment must be set trough self::setPayment() first.');
    }
    $payment = $this->getPayment();

    \Drupal::service('payment_authnet.event_dispatcher')->preVoidPayment($payment);
    // Try to cancel(void) payment, and if unsuccess, do nothing.
    if ($this->processResults($this->getCancelStatusId())) {
      if ($this->getCancelZeroAmount()) {
        $line_item_manager = Payment::lineItemManager();
        // Duplicate line items with inverted amount. Keep existing line items
        // for history.
        $line_items = $payment->getLineItems();
        $index = count($line_items);
        foreach ($line_items as $line_item) {
          $configuration = $line_item->getConfiguration();
          $configuration['name'] = $line_item->getPluginId() . $index > 0 ? $index : '';
          $index++;
          $configuration['amount'] = 0 - $configuration['amount'];
          $new_line_item = $line_item_manager->createInstance($line_item->getPluginId(), $configuration);
          $payment->setLineItem($new_line_item);
        }
      }

      $payment->setPaymentStatus($this->paymentStatusManager->createInstance($this->getCancelStatusId()));
      $this->getPayment()->save();
    }

    return $this->getPaymentVoidResult();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPaymentAccess(AccountInterface $account) {
    if (!$this->getPayment()) {
      throw new \LogicException('Trying to check access for a non-existing payment. A payment must be set trough self::setPayment() first.');
    }
    return $this->getPayment()->getPaymentStatus()->getPluginId() != $this->getCancelStatusId()
      && $this->getConfiguration('refunded_amount', 0) < $this->getPayment()->getAmount()
      && $this->getTransactionId()
      && !in_array($this->getTransactionDetails()->getTransactionStatus(), [
        'settledSuccessfully',
        'refundSettledSuccessfully',
        'voided',
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentVoidResult() {
    return new OperationResult();
  }

}
