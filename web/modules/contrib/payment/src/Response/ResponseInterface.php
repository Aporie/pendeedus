<?php

namespace Drupal\payment\Response;

/**
 * Defines a payment response.
 */
interface ResponseInterface {

  /**
   * Gets the URL the calling code may redirect to after payment execution.
   *
   * @return \Drupal\Core\Url
   */
  public function getRedirectUrl();

  /**
   * Gets the response the calling code must return after payment execution.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function getResponse();

}
