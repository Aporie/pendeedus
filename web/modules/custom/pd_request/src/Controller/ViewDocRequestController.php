<?php

namespace Drupal\pd_request\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ViewDocRequestController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;


  /**
   * Class constructor.
   */
  public function __construct(Renderer $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer')
    );
  }

  /**
   * Display the doc request view.
   *
   * @return array
   *   Return a renderable array.
   */
  public function view(Request $request) {
    $response = new AjaxResponse();
    $params = $request->query->all();
    $view = Views::getView('doc_requests');
    $view->setDisplay('page_1');
    $view->setExposedInput([
      'field_area_target_id' => $params['county'] != 'All' ? $params['county'] : $params['area'],
      'status' => $params['status'],
    ]);
    $renderable_view = $view->buildRenderable();
    if (!empty($view)) {
      $response->addCommand(new ReplaceCommand('.views-element-container', $this->renderer->render($renderable_view)));
    }

    return $response;

  }

}
