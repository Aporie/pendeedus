<?php

namespace Drupal\pd_coverage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eck\Entity\EckEntity;
use Drupal\inline_entity_form\ElementSubmit;
use Drupal\taxonomy\Entity\Term;

/**
 * Class CoverageAreaSelectorForm.
 */
class CoverageAreaSelectorForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'coverage_area_selector_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (isset($trigger['#name']) && $trigger['#name'] == 'county') {
      $this->cleanInput($form_state);
    }

    $form['selector'] = [
      '#type' => 'container',
      '#prefix' => '<div id="coverage-selector">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
    ];

    $input = $form_state->getUserInput();

    $states = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('areas', 0, 1, FALSE);
    $options = [];
    foreach ($states as $state) {
      $options[$state->tid] = $state->name;
    }
    $form['selector']['state'] = array(
      '#description' => '',
      '#type' => 'select',
      '#options' => $options,
      '#empty_option' => t('- State -'),
      '#ajax' => [
        'callback' => '::ajaxLoadCounties',
        // don't forget :: when calling a class method.
        //'callback' => [$this, 'myAjaxCallback'], //alternative notation
        'disable-refocus' => FALSE,
        // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'change',
        'wrapper' => 'county-wrapper',
        // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'none',
        ],
      ],
    );

    $form['selector']['county'] = array(
      '#description' => '',
      '#type' => 'select',
      '#empty_option' => t('- County -'),
      '#ajax' => [
        'callback' => '::ajaxLoadCoverageForm',
        // don't forget :: when calling a class method.
        //'callback' => [$this, 'myAjaxCallback'], //alternative notation
        'disable-refocus' => FALSE,
        // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'change',
        'wrapper' => 'coverage-form',
        // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#prefix' => '<div id="county-wrapper">',
      '#suffix' => '</div>',
    );

    // Define selected state and count, and set default values if necessary.
    $selected_state = $selected_county = NULL;
    if (!empty($input)) {
      if ($input['form_id'] == 'area_coverage_area_coverage_form') {
        $selected_county = $input['county'];
        $selected_state = Term::load($selected_county)->parent->getString();

        // When coverage area is submitted, let's set default values in this form.
        $form['selector']['state']['#default_value'] = $selected_state;
        $form['selector']['county']['#default_value'] = $selected_county;
      }
      elseif ($input['form_id'] == 'coverage_area_selector_form') {
        $selected_state = isset($input['state']) ? $input['state'] : NULL;
        $selected_county = isset($input['county']) ? $input['county'] : NULL;
      }
    }

    // Define county options based on selected state.
    $counties = $selected_state ? \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadTree('areas', $selected_state, 1, FALSE) : [];
    $options = [];
    $copy_options = [];
    foreach ($counties as $county) {
      $options[$county->tid] = $county->name;
      if ($selected_county != $county->tid) {
        $copy_options[$county->tid] = ['name' => $county->name];
      }
    }
    $form['selector']['county']['#options'] = $options;

    $form['#wrapper'] = $wrapper = 'area_coverage';

    $form[$wrapper] = [
      '#type' => 'container',
      '#prefix' => '<div id="coverage-form">',
      '#suffix' => '</div>',
    ];

    /** @var Term $term */
    if ($selected_county && $term = Term::load($selected_county)) {
      $storage = \Drupal::entityTypeManager()->getStorage('area_coverage');

      $area_ids = \Drupal::service('pd_coverage.coverage_area_price_manager')->getCoverageIdByArea($term->id());

      if ($area_ids) {
        $entity = $storage->load(reset($area_ids));
        $op = 'edit';
      }
      else {
        $entity = $storage->create([
          'type' => 'area_coverage',
          'field_area' => $term->id(),
        ]);
        $op = 'add';
      }

      // Pass parameter to the form.
      $entity->isCoverageForm = TRUE;

      $form[$wrapper]['entity'] = [
        '#type' => 'inline_entity_form',
        '#entity_type' => 'area_coverage',
        '#bundle' => 'area_coverage',
        // If the #default_value is NULL, a new entity will be created.
        '#default_value' => $entity,
        '#op' => $op,
        '#prefix' => '<div><h2>' . t('Products') . '</h2>',
        '#suffix' => '</div>',
      ];

      $form[$wrapper]['copy_over'] = [
        '#type' => 'tableselect',
        '#header' => ['name' => 'County'],
        '#options' => $copy_options,
        '#multiple' => TRUE,
        '#prefix' => '<div><h2>' . t('Copy this pricing over to:') . '</h2>',
        '#suffix' => '</div>',
      ];

      $form[$wrapper]['actions'] = ['#type' => 'actions'];
      $form[$wrapper]['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#submit' => ['::submitForm'],
      ];
      ElementSubmit::addCallback($form[$wrapper]['actions']['submit'], $form);
    }
    else {
      $form[$wrapper]['message'] = [
        '#markup' => '<div id="coverage-form">' . t('Please select a state and county to edit coverage details.') . '</div>',
      ];
    }
    
    return $form;
  }

  public function ajaxLoadCounties(array &$form, FormStateInterface $form_state) {
    return $form['selector']['county'];
  }

  public function ajaxLoadCoverageForm(array &$form, FormStateInterface $form_state) {
    return $form[$form['#wrapper']];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    \Drupal::messenger()->addMessage('The changes have been saved.');

    if ($copy_tids = array_filter($form_state->getValue('copy_over'))) {

      foreach ($copy_tids as $copy_tid) {
        $storage = \Drupal::entityTypeManager()->getStorage('area_coverage');
        
        if ($area_ids = \Drupal::service('pd_coverage.coverage_area_price_manager')->getCoverageIdByArea($copy_tid)) {
          $entity = $storage->load(reset($area_ids));
          $op = 'edit';
        }
        else {
          $entity = $storage->create([
            'type' => 'area_coverage',
            'field_area' => $copy_tid,
            'uid' => in_array('employee', \Drupal::currentUser()->getRoles()) ? 1 : \Drupal::currentUser()->id(),
          ]);
          $op = 'add';
        }

        $this->copyOver($form_state, $entity, $op);

      }
    }


    $this->cleanInput($form_state);


    $form_state->setRebuild();
  }

  protected function copyOver(FormStateInterface $form_state, EckEntity $entity, $op) {
    $copy_form_state = new FormState();

    $input = $form_state->getUserInput();
    $copy_form_state->setValues(['entity' => $input['entity']]);

    $copy_form_state->addBuildInfo('args', [$entity, $op]);
    \Drupal::formBuilder()
      ->submitForm('\\Drupal\\pd_coverage\\Form\\CoverageAreaCopyForm', $copy_form_state);
  }

  protected function cleanInput(FormStateInterface $form_state) {
    // Remove previous entity and copy values.
    $input = $form_state->getUserInput();
    unset($input['entity']);
    unset($input['copy_over']);
    $form_state->setUserInput($input);
  }

}
