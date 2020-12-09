<?php

namespace Drupal\payment_authnet\Entity\AuthnetProfile;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Lists authnet_profile entities.
 */
class AuthnetProfileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['apiId'] = $this->t('API Login ID');
    $header['apiTransactionKey'] = $this->t('Transaction Key');
    $header['apiKey'] = $this->t('API Key');
    $header['sandboxMode'] = $this->t('Sandbox Mode');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\payment_authnet\Entity\AuthnetProfile */
    $row['label'] = $entity->label();
    $row['apiId'] = $entity->getApiId();
    $row['apiTransactionKey'] = $entity->getApiTransactionKey();
    $row['apiKey'] = $entity->getApiKey();
    $row['sandboxMode'] = $entity->getSandboxMode() ? $this->t('SANDBOX') : $this->t('PRODUCTION');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['#empty'] = $this->t('There are no Authorize.net profiles configured yet.');

    return $build;
  }

}
