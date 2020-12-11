<?php

namespace Drupal\pd_request\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Renderer;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ViewDocumentsRefreshController extends ControllerBase {

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
  public function view() {
    $response = new AjaxResponse();
    $view = Views::getView('user_requests')->buildRenderable('block_1');
    if (!empty($view)) {
      $response->addCommand(new ReplaceCommand('.block-views .views-element-container', $this->renderer->render($view)));
    }

    return $response;

  }

}
