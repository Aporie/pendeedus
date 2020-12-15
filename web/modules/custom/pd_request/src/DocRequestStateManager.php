<?php

namespace Drupal\pd_request;

use Drupal\eck\Entity\EckEntity;
use Drupal\workflow\Entity\WorkflowTransition;

class DocRequestStateManager {

  const STATE_PENDING = 'doc_request_pending';

  const STATE_NOT_AVAILABLE = 'doc_request_not_available';

  const STATE_COMPLETED = 'doc_request_completed';

  const STATE_ADDITIONAL_FEE = 'doc_request_additional_fee';

  const STATE_ACCEPTED_FEE = 'doc_request_accepted_fee';

  const STATE_DISPUTED = 'doc_request_disputed';

  const STATE_CHARGED = 'doc_request_charged';

  /** @var EckEntity */
  protected $doc_request;

  public function __construct(EckEntity $doc_request) {
    $this->doc_request = $doc_request;
  }

  public function processUpdate() {
    if ($new_sid = $this->getNewState()) {
      $this->setState($new_sid);
    }
  }

  protected function getNewState() {
    $doc_request = $this->doc_request;

    // If inserted, skip.
    if (empty($doc_request->original)) {
      return NULL;
    }

    // If is set to not available, set Not Available.
    if ($doc_request->get('field_not_available')->getString()) {
      return self::STATE_NOT_AVAILABLE;
    }

    // If additional fee.
    if ($doc_request->get('field_additional_fee')->getString()) {
      return self::STATE_ADDITIONAL_FEE;
    }

    if ($doc_request->get('field_documents')->getString()) {
      return self::STATE_COMPLETED;
    }

    return self::STATE_PENDING;
  }

  public function setState($new_sid, $comment = '') {
    $field_name = 'field_workflow';
    $doc_request = $this->doc_request;

    $doc_request->set($field_name, $new_sid);

    return;

    $current_sid = $doc_request->get($field_name)->value;

    if ($current_sid == $new_sid) {
      return;
    }

    $transition = WorkflowTransition::create([
      $current_sid,
      'field_name' => $field_name,
    ]);
    $transition->setTargetEntity($doc_request);
    $transition->setValues($new_sid, \Drupal::currentUser()
      ->id(), \Drupal::time()
      ->getRequestTime(), $comment, TRUE);
    $transition->force(TRUE);
  }
}
