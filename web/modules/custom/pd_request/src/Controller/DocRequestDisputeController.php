<?php

namespace Drupal\pd_request\Controller;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\pd_request\Entity\DocRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DocRequestDisputeController extends ControllerBase {

  /**
   * Defines variables to be used later.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Display the doc request view.
   *
   * @return array
   *   Return a renderable array.
   */
  public function view(Request $request, DocRequest $doc_request) {
    $response = new AjaxResponse();
    $view_builder = $this->entityTypeManager->getViewBuilder('doc_request');
    $renderable_array = $view_builder->view($doc_request, 'dispute');

    $response->addCommand(new OpenModalDialogCommand('Dispute your request', $renderable_array, ['width' => '700']));
    
    return $response;

  }

}
