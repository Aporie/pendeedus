<?php

namespace Drupal\pd_coverage;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\commerce_price\Price;
use Drupal\pd_request\Entity\DocRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service for request page.
 */
class CoverageAreaPriceManager implements CoverageAreaPriceManagerInterface {

   /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCoverageIdByArea($county_id) {
    $uid = in_array('employee', $this->currentUser->getRoles()) ? 1 : $this->currentUser->id();
    $query = $this->entityTypeManager->getStorage('area_coverage')->getQuery();
    
    return $query->condition('uid', $uid)->condition('field_area', $county_id)->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getDocRequestAmount(DocRequest $doc_request, $coverage_type_id = NULL) {
    if ($coverage_type_id) {
      $coverage_type = Term::load($coverage_type_id)->get('name')->value;
      $county_id = $doc_request->get('field_area')->getValue()[0]['target_id'];
      $coverage_ids = $this->getCoverageIdByArea($county_id);
      if ($coverage_id = reset($coverage_ids)) {
        $coverage = $this->entityTypeManager->getStorage('area_coverage')->load($coverage_id);
        $coverage_item_id = $coverage->get('field_' . str_replace('-', '_', strtolower($coverage_type)))->getValue()[0]['target_id'];
        $coverage_item = $this->entityTypeManager->getStorage('coverage_item')->load($coverage_item_id);
        if ($amount = $coverage_item->get('field_amount')->getValue()[0]['value']) {
          $doc_request->setPrice(new Price($amount, 'USD'));
          $doc_request->save();
          
          return $amount;
        }
      }
    }

    return FALSE;
  }

}