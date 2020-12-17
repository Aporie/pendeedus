<?php

namespace Drupal\pd_coverage;

use Drupal\pd_request\Entity\DocRequest;

/**
 * Provides an interface for coverage area prices.
 */
interface CoverageAreaPriceManagerInterface {

  /**
   * Get coverage entity id.
   * 
   * @param int $county_id
   *  The county term id.
   * 
   * @return int
   *  The area_coverage entity id.
   */
  public function getCoverageIdByArea($county_id);

  /**
   * Map doc request to amount.
   * 
   * From coverage_type taxonomy term id,
   * retreive coverage_item amount through 
   * area_coverage entity.
   * 
   * @param Drupal\pd_request\Entity\DocRequest $doc_request;
   *  The doc request.
   * @param int $coverage_type_id
   *  The coverage_type taxonomy term id.
   * 
   * @return int
   *  The doc request price amount.
   */
  public function getDocRequestAmount(DocRequest $doc_request, int $coverage_type_id = NULL);
}