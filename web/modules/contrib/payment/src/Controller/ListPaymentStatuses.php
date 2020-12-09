<?php

namespace Drupal\payment\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "list payment methods" route.
 */
class ListPaymentStatuses extends ControllerBase {

  /**
   * The payment status plugin manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface $payment_status_manager
   *   The payment status manager.
   */
  public function __construct(TranslationInterface $string_translation, RendererInterface $renderer, PaymentStatusManagerInterface $payment_status_manager) {
    $this->paymentStatusManager = $payment_status_manager;
    $this->renderer = $renderer;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('renderer'), $container->get('plugin.manager.payment.status'));
  }

  /**
   * Lists all payment statuses.
   *
   * @return array
   *   A render array.
   */
  public function execute() {
    return [
      '#header' => [$this->t('Title'), $this->t('Description'), $this->t('Operations')],
      '#type' => 'table',
    ] + $this->buildListingLevel($this->buildHierarchy(), 0);
  }

  /**
   * Helper function for self::listing() to build table rows.
   *
   * @param array[] $hierarchy
   *   Keys are plugin IDs, and values are arrays of the same structure as this
   *   parameter. The depth is unlimited.
   * @param integer $depth
   *   The depth of $hierarchy's top-level items as seen from the original
   *   hierarchy's root (this function is recursive), starting with 0.
   *
   * @return array
   *   A render array.
   */
  protected function buildListingLevel(array $hierarchy, $depth) {
    $rows = [];
    foreach ($hierarchy as $plugin_id => $children) {
      $definition = $this->paymentStatusManager->getDefinition($plugin_id);
      $operations_provider = $this->paymentStatusManager->getOperationsProvider($plugin_id);
      $indentation = [
        '#theme' => 'indentation',
        '#size' => $depth,
      ];
      $rows[$plugin_id] = [
        'label' => [
          '#markup' => $this->renderer->render($indentation) . $definition['label'],
        ],
        'description' => [
          '#markup' => $definition['description'],
        ],
        'operations' => [
          '#type' => 'operations',
          '#links' => $operations_provider ? $operations_provider->getOperations($plugin_id) : [],
        ],
      ];
      $rows = array_merge($rows, $this->buildListingLevel($children, $depth + 1));
    }

    return $rows;
  }

  /**
   * Returns a hierarchical representation of payment statuses.
   *
   * @param string[]|null $limit_plugin_ids
   *   An array of plugin IDs to limit the statuses to, or NULL to allow all.
   *
   * @return array[]
   *   A possibly infinitely nested associative array. Keys are plugin IDs and
   *   values are arrays of similar structure as this method's return value.
   */
  protected function buildHierarchy(array $limit_plugin_ids = NULL) {
    static $hierarchy = NULL;

    if (is_null($hierarchy)) {
      $parents = [];
      $children = [];
      $definitions = $this->paymentStatusManager->getDefinitions();
      if (is_array($limit_plugin_ids)) {
        $definitions = array_intersect_key($definitions, array_flip($limit_plugin_ids));
      }
      uasort($definitions, array($this, 'sort'));
      foreach ($definitions as $plugin_id => $definition) {
        if (!empty($definition['parent_id'])) {
          $children[$definition['parent_id']][] = $plugin_id;
        }
        else {
          $parents[] = $plugin_id;
        }
      }
      $hierarchy = $this->buildHierarchyLevel($parents, $children);
    }

    return $hierarchy;
  }

  /**
   * Helper function for self::hierarchy().
   *
   * @param string[] $parent_plugin_ids
   *   An array with IDs of plugins that are part of the same hierarchy level.
   * @param string[] $child_plugin_ids
   *   Keys are plugin IDs. Values are arrays with those plugin's child
   *   plugin IDs.
   *
   * @return array[]
   *   The return value is identical to that of self::hierarchy().
   */
  protected function buildHierarchyLevel(array $parent_plugin_ids, array $child_plugin_ids) {
    $hierarchy = [];
    foreach ($parent_plugin_ids as $plugin_id) {
      $hierarchy[$plugin_id] = isset($child_plugin_ids[$plugin_id]) ? $this->buildHierarchyLevel($child_plugin_ids[$plugin_id], $child_plugin_ids) : [];
    }

    return $hierarchy;
  }

  /**
   * Implements uasort() callback to sort plugin definitions by label.
   */
  protected function sort(array $definition_a, array $definition_b) {
    return strcmp($definition_a['label'], $definition_b['label']);
  }

}
