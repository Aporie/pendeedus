<?php

namespace Drupal\payment\Entity\Payment;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists payment entities.
 */
class PaymentListBuilder extends EntityListBuilder implements PaymentListBuilderInterface {

  /**
   * The currency storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The ID of the owner to restrict payments by.
   *
   * @var int|null
   *   The owner ID or null to allow payments of all owners.
   */
  protected $ownerId;

  /**
   * The redirect destination.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $list_builder */
    $list_builder = parent::createInstance($container, $entity_type);
    $list_builder->currencyStorage = $container->get('entity_type.manager')->getStorage('currency');
    $list_builder->dateFormatter = $container->get('date.formatter');
    $list_builder->moduleHandler = $container->get('module_handler');
    $list_builder->redirectDestination = $container->get('redirect.destination');
    $list_builder->stringTranslation = $container->get('string_translation');
    return $list_builder;
  }

  /**
   * Sets the date formatter.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function setDateFormatter(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Sets the currency storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $currency_storage
   *   The currency storage.
   */
  public function setCurrencyStorage(EntityStorageInterface $currency_storage) {
    $this->currencyStorage = $currency_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function restrictByOwnerId($owner_id) {
    $this->ownerId = $owner_id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();
    $header = $this->buildHeader();
    $query->tableSort($header);

    if ($this->ownerId) {
      $query->condition('owner', $this->ownerId);
    }

    return $query
      ->pager($this->limit)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no payments yet.');

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['updated'] = [
      'data' => $this->t('Last updated'),
      'field' => 'changed',
      'sort' => 'DESC',
      'specifier' => 'changed',
    ];
    $header['status'] = [
      'data' => $this->t('Status'),
    ];
    $header['amount'] = [
      'data' => $this-> t('Amount'),
    ];
    $header['payment_method'] = array(
      'data' => $this->t('Payment method'),
      'class' => array(RESPONSIVE_PRIORITY_LOW),
    );
    $header['owner'] = array(
      'data' => $this->t('Payer'),
      'class' => array(RESPONSIVE_PRIORITY_MEDIUM),
    );
    $header['operations'] = [
      'data' => $this->t('Operations'),
    ];

    return $header;
  }

  /**
   * {@inheritdnoc}
   */
  public function buildRow(EntityInterface $payment) {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $row['data']['updated'] = $this->dateFormatter->format($payment->getChangedTime());

    $status_definition = $payment->getPaymentStatus()->getPluginDefinition();
    $row['data']['status'] = $status_definition['label'];

    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = $this->currencyStorage->load($payment->getCurrencyCode());
    if (!$currency) {
      $currency = $this->currencyStorage->load('XXX');
    }
    $row['data']['amount'] = $currency->formatAmount($payment->getAmount());

    $row['data']['payment_method'] = $payment->getPaymentMethod() ? $payment->getPaymentMethod()->getPluginDefinition()['label'] : $this->t('Unavailable');

      $row['data']['owner']['data'] = array(
        '#theme' => 'username',
        '#account' => $payment->getOwner(),
      );

    $operations = $this->buildOperations($payment);
    $row['data']['operations']['data'] = $operations;

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $destination = $this->redirectDestination->get();
    $operations = parent::getDefaultOperations($entity);
    foreach ($operations as &$operation) {
      $operation['query']['destination'] = $destination;
    }

    if ($entity->access('view')) {
      $operations['view'] = array(
        'title' => $this->t('View'),
        'weight' => -10,
        'url' => $entity->toUrl(),
      );
    }
    if ($entity->access('update_status')) {
      $operations['update_status'] = array(
        'title' => $this->t('Update status'),
        'attributes' => array(
          'data-accepts' => 'application/vnd.drupal-modal',
        ),
        'query' => array(
          'destination' => $destination,
        ),
        'url' => $entity->toUrl('update-status-form'),
      );
    }
    if ($entity->access('capture')) {
      $operations['capture'] = array(
          'title' => $this->t('Capture'),
          'attributes' => array(
            'data-accepts' => 'application/vnd.drupal-modal',
          ),
          'query' => array(
            'destination' => $destination,
          ),
          'url' => $entity->toUrl('capture-form'),
        );
    }
    if ($entity->access('refund')) {
      $operations['refund'] = array(
          'title' => $this->t('Refund'),
          'attributes' => array(
            'data-accepts' => 'application/vnd.drupal-modal',
          ),
          'query' => array(
            'destination' => $destination,
          ),
          'url' => $entity->toUrl('refund-form'),
        );
    }
    if ($entity->access('complete')) {
      $operations['complete'] = array(
        'title' => $this->t('Complete'),
        'url' => $entity->toUrl('complete'),
      );
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    $build = parent::buildOperations($entity);
    // @todo Remove this when https://drupal.org/node/2253257 is fixed.
    $build['#attached'] = array(
      'library' => array('core/drupal.ajax'),
    );

    return $build;
  }

}
