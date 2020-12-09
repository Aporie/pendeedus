<?php

namespace Drupal\payment;

/**
 * Defines an operation result.
 *
 * When operations can be initialized programmatically, but may require human
 * interaction to be completed, this interface provides calling code with
 * information to continue the human part of the operation.
 */
interface OperationResultInterface {

  /**
   * Gets whether the operation is completed.
   *
   * @return bool
   *   Whether the operation is completed. When FALSE is returned,
   *   self::getCompletionResponse() MUST return a response.
   */
  public function isCompleted();

  /**
   * Gets the response to complete the operation.
   *
   * @return \Drupal\payment\Response\ResponseInterface|null
   *   A response (only if self::isInProgress() returns TRUE) or NULL if the
   *   operation cannot be completed (anymore).
   */
  public function getCompletionResponse();

}
