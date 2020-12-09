<?php

namespace Drupal\pd_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines AccountEditController class.
 */
class AccountEditController extends ControllerBase {

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
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $buildBuilder;

  /**
   * Class constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxy $current_user, FormBuilder $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pd_core_account_edit';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pd_core.account_edit',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function content() {
    $user = User::load($this->currentUser->id()); 
    // Get user account form.
    $userFormObject = $this->entityTypeManager
      ->getFormObject('user', 'default')
      ->setEntity($user);
    $build['user'] = $this->formBuilder->getForm($userFormObject);
    $build['user']['actions']['submit']['#value'] = $this->t('Update account');
    $build['user']['actions']['submit']['#submit'][] = 'pd_core_redirect_here';

    // Get profile storage.
    $profile_storage = $this->entityTypeManager->getStorage('profile');
    // Get vendor profile.
    if ($user->hasPermission('update own vendor profile')) {
      $vendor_profile = $profile_storage->loadByProperties([
        'uid' => $this->currentUser->id(),
        'type' => 'vendor',
      ]);
      if ($profile = reset($vendor_profile)) {
        $vendorFormObject = $this->entityTypeManager
          ->getFormObject('profile', 'default')
          ->setEntity($profile);
        $build['vendor'] = $this->formBuilder->getForm($vendorFormObject);
        //$build['vendor']['actions']['submit']['#value'] = $this->t('Update profile');
        //$build['vendor']['actions']['submit']['#submit'][] = '::redirectHere';
      }
    }

    // Get documents profile.
    if ($user->hasPermission('update own documents profile')) {
      $documents_profile = $profile_storage->loadByProperties([
        'uid' => $this->currentUser->id(),
        'type' => 'documents',
      ]);
      if (!$profile = reset($documents_profile)) {
        $profile = $profile_storage->create([
          'type' => 'documents',
        ]);
      }
      $documentsFormObject = $this->entityTypeManager
          ->getFormObject('profile', 'default')
          ->setEntity($profile);
      $build['documents'] = $this->formBuilder->getForm($documentsFormObject);
      
      //$build['documents']['actions']['submit']['#value'] = $this->t('Save documents');
      //$build['documents']['actions']['submit']['#submit'][] = '::redirectHere';
    }

    // Defines custom theme.
    $build['#theme'] = 'controller__account_edit';

    return $build;
  }

}
