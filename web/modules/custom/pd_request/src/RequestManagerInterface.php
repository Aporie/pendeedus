<?php

namespace Drupal\pd_request;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an interface for request page service.
 */
interface RequestManagerInterface {

  /**
   * Ajax request: refresh request page.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   View html.
   */
  public function documentsRefresh();

}
