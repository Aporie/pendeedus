<?php

namespace Drupal\payment_authnet\Plugin\Payment\Method;

use Drupal\payment\Plugin\Payment\Method\PaymentMethodCapturePaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodRefundPaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodUpdatePaymentStatusInterface;

/**
 * An interface for Authnet payment method.
 */
interface AuthnetInterface extends PaymentMethodInterface, PaymentMethodCapturePaymentInterface, PaymentMethodRefundPaymentInterface, PaymentMethodUpdatePaymentStatusInterface, AuthnetVoidTransactionInterface {

  // Authnet method to authorize a credit card payment. To actually charge
  // the funds you will need to follow up with a capture transaction.
  const AUTH_ONLY_TRANSACTION = 'authOnlyTransaction';
  // Authnet method to capture funds reserved with a previous
  // authOnlyTransaction transaction request.
  const PRIOR_AUTH_CAPTURE_TRANSACTION = 'priorAuthCaptureTransaction';
  // Authnet method to authorize and capture money transaction type.
  const AUTH_CAPTURE_TRANSACTION = 'authCaptureTransaction';
  // This transaction type is used to refund a customer for a transaction that
  // was successfully settled through the payment gateway.
  const REFUND_TRANSACTION = 'refundTransaction';
  // Cancel/void transaction type to use in request to Authorize.net.
  // Useful for transactions to cancel those which one hasn't been sent
  // for settlment.
  const VOID_TRANSACTION = 'voidTransaction';
  // Authnet Customer Data Type.
  const CUSTOMER_DATA_TYPE = 'individual';

  /**
   * Gets the status to set on payment execution.
   *
   * @return string
   *   The plugin ID of the payment status to set.
   */
  public function getExecuteStatusId();

  /**
   * Gets the status to set on payment capture.
   *
   * @return string
   *   The plugin ID of the payment status to set.
   */
  public function getCaptureStatusId();

  /**
   * Gets whether or not capture is enabled.
   *
   * @return bool
   *   Whether or not to support capture.
   */
  public function getCapture();

  /**
   * Gets the status to set on payment refund.
   *
   * @return string
   *   The plugin ID of the payment status to set.
   */
  public function getRefundStatusId();

  /**
   * Gets whether or not clonning of payment entity for refund is enabled.
   *
   * @return bool
   *   Whether or not to clone payment on refund operations.
   */
  public function getCloneRefunded();

  /**
   * Gets whether or not refund is enabled.
   *
   * @return bool
   *   Whether or not to support refund.
   */
  public function getRefund();

  /**
   * Detects if authorize.net should transfer money right now.
   *
   * @return string
   *   Transaction type for Authorize net to identify will money be charged
   *   right now, or credit card will be just authorized.
   *
   * @throws \LogicException
   */
  public function getTransactionType();

  /**
   * Gets detailed information about a last Authorize.net transaction.
   *
   * @param bool $refresh
   *   If false, request will be cached and next time returned from cache.
   *
   * @return \net\authorize\api\contract\v1\TransactionDetailsType
   *   Instance of TransactionDetailsType.
   *
   * @throws Exception\PaymentAuthnetApiException
   * @throws Exception\PaymentAuthnetResponseException
   */
  public function getTransactionDetails($refresh = FALSE);

  /**
   * Gets the Authnet transaction's refId.
   *
   * @return string
   *   The Authnet refId (string up to 20 characters).
   */
  public function getRefId();

  /**
   * Gets this plugin's configuration.
   *
   * @param string $key
   *   If defined, will return value of specified key.
   * @param mixed $default_value
   *   If key is not found, default value will be returned.
   *
   * @return mixed
   *   An array of this plugin's configuration or value, if key is specified.
   */
  public function getConfiguration($key = NULL, $default_value = NULL);

  /**
   * Gets the status to set on payment partial refund.
   *
   * @return string
   *   The plugin ID of the payment status to set.
   */
  public function getPartialRefundStatusId();

  /**
   * Gets whether or not partial refund is enabled.
   *
   * @return bool
   *   Whether or not to support refund.
   */
  public function getPartialRefund();

  /**
   * Gets the status to set on payment cancel operation.
   *
   * @return string
   *   The plugin ID of the payment status to set.
   */
  public function getCancelStatusId();

}
