<?php

namespace Drupal\default_content_deploy;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\hal\LinkManager\LinkManagerInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * A service for handling import of default content.
 *
 * The importContent() method is almost duplicate of
 *   \Drupal\default_content\Importer::importContent with injected code for
 *   content update. We are waiting for better DC code structure in a future.
 */
class Importer {

  /**
   * Deploy manager.
   *
   * @var \Drupal\default_content_deploy\DeployManager
   */
  protected $deployManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Scanned files.
   *
   * @var object[]
   */
  private $files;

  /**
   * Directory to import.
   *
   * @var string
   */
  private $folder;

  /**
   * Data to import.
   *
   * @var array
   */
  private $dataToImport = [];

  /**
   * Is remove changes of an old content.
   *
   * @var bool
   */
  protected $forceOverride;

  /**
   * The Entity repository manager.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The cache data.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Memoization for references that have already been discovered.
   *
   * @var array
   */
  protected $discoveredReferences = [];

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The link manager service.
   *
   * @var \Drupal\hal\LinkManager\LinkManagerInterface
   */
  protected $linkManager;

  /**
   * The file system scanner.
   *
   * @var \Drupal\default_content\ScannerInterface
   */
  protected $scanner;

  /**
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected $accountSwitcher;

  /**
   * Constructs the default content deploy manager.
   *
   * @param \Symfony\Component\Serializer\Serializer $serializer
   *   The serializer service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\hal\LinkManager\LinkManagerInterface $link_manager
   *   The link manager service.
   * @param \Drupal\Core\Session\AccountSwitcherInterface $account_switcher
   *   The account switcher.
   * @param \Drupal\default_content_deploy\DeployManager $deploy_manager
   *   Deploy manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The Entity repository manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache data.
   */
  public function __construct(Serializer $serializer, EntityTypeManagerInterface $entity_type_manager, LinkManagerInterface $link_manager, AccountSwitcherInterface $account_switcher, DeployManager $deploy_manager, ModuleHandlerInterface $module_handler, EntityRepositoryInterface $entity_repository, CacheBackendInterface $cache) {
    $this->serializer = $serializer;
    $this->entityTypeManager = $entity_type_manager;
    $this->linkManager = $link_manager;
    $this->accountSwitcher = $account_switcher;
    $this->deployManager = $deploy_manager;
    $this->moduleHandler = $module_handler;
    $this->entityRepository = $entity_repository;
    $this->cache = $cache;
  }

  /**
   * Is remove changes of an old content.
   *
   * @param bool $is_override
   *
   * @return \Drupal\default_content_deploy\Importer
   */
  public function setForceOverride(bool $is_override) {
    $this->forceOverride = $is_override;
    return $this;
  }

  /**
   * Set directory to import.
   *
   * @param string $folder
   *   The content folder.
   *
   * @return \Drupal\default_content_deploy\Importer
   */
  public function setFolder(string $folder) {
    $this->folder = $folder;
    return $this;
  }

  /**
   * Get directory to import.
   *
   * @return string
   *   The content folder.
   *
   * @throws \Exception
   */
  protected function getFolder() {
    return $this->folder ?: $this->deployManager->getContentFolder();
  }

  /**
   * Get Imported data result.
   *
   * @return array
   */
  public function getResult() {
    return $this->dataToImport;
  }

  /**
   * Import data from JSON and create new entities, or update existing.
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function prepareForImport() {
    $this->cache->delete('hal:links:relations');
    $this->files = $this->scan($this->getFolder());

    foreach ($this->files as $file) {
      $uuid = str_replace('.json', '', $file->name);

      if (!isset($this->dataToImport[$uuid])) {
        $this->decodeFile($file);
      }
    }

    return $this;
  }

  /**
   * Returns a list of file objects.
   *
   * @param string $directory
   *   Absolute path to the directory to search.
   *
   * @return object[]
   *   List of stdClass objects with name and uri properties.
   */
  public function scan($directory) {
    // Use Unix paths regardless of platform, skip dot directories, follow
    // symlinks (to allow extensions to be linked from elsewhere), and return
    // the RecursiveDirectoryIterator instance to have access to getSubPath(),
    // since SplFileInfo does not support relative paths.
    $flags = \FilesystemIterator::UNIX_PATHS;
    $flags |= \FilesystemIterator::SKIP_DOTS;
    $flags |= \FilesystemIterator::CURRENT_AS_SELF;
    $directory_iterator = new \RecursiveDirectoryIterator($directory, $flags);
    $iterator = new \RecursiveIteratorIterator($directory_iterator);
    $files = [];

    /* @var \SplFileInfo $file_info */
    foreach ($iterator as $file_info) {
      // Skip directories and non-json files.
      if ($file_info->isDir() || $file_info->getExtension() != 'json') {
        continue;
      }

      $file = new \stdClass();
      $file->name = $file_info->getFilename();
      $file->uri = $file_info->getPathname();
      $files[$file->uri] = $file;
    }

    return $files;
  }

  /**
   * Import to entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function import() {
    $files = $this->dataToImport;

    if (PHP_SAPI === 'cli') {
      $root_user = $this->entityTypeManager->getStorage('user')->load(1);
      $this->accountSwitcher->switchTo($root_user);
    }

    foreach ($files as $file) {
      if ($file['status'] != 'skip') {
        // Gets url from file for set to Link manager.
        $link = $file['data']['_links']['type']['href'];
        $url_data = parse_url($link);
        $host = "{$url_data['scheme']}://{$url_data['host']}";
        $host = (!isset($url_data['port'])) ? $host : "{$host}:{$url_data['port']}";

        $entity_type = $file['entity_type_id'];
        $this->linkManager->setLinkDomain($host);
        $class = $this->entityTypeManager->getDefinition($entity_type)->getClass();
        $this->preDenormalize($file);

        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $this->serializer->denormalize($file['data'], $class, 'hal_json', ['request_method' => 'POST']);
        $entity->enforceIsNew($file['is_new']);
        $entity->save();
      }
    }

    $this->linkManager->setLinkDomain(FALSE);
    if (PHP_SAPI === 'cli') {
      $this->accountSwitcher->switchBack();
    }
  }

  /**
   * Prepare file to import.
   *
   * @param $file
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  protected function decodeFile($file) {
    // Check that this file has not been decoded already.
    if (array_key_exists($file->name, $this->discoveredReferences)) {
      return $this;
    }

    // Get parsed data.
    $parsed_data = file_get_contents($file->uri);;

    // Decode.
    $decode = $this->serializer->decode($parsed_data, 'hal_json');
    $references = $this->getReferences($decode);

    // Record that we have checked references of current file.
    $this->discoveredReferences[$file->name] = $file;
    if ($references) {
      foreach ($references as $reference) {
        $this->decodeFile($reference);
      }
    }

    // Prepare data for import.
    $link = $decode['_links']['type']['href'];
    $data_to_import = [
      'data' => $decode,
      'entity_type_id' => $this->getEntityTypeByLink($link),
    ];

    $this->preAddToImport($data_to_import);
    $this->addToImport($data_to_import);

    return $this;
  }

  /**
   * Here we can edit data`s value before importing.
   *
   * @param $data
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function preAddToImport(&$data) {
    $decode = $data['data'];
    $uuid = $decode['uuid'][0]['value'];
    $entity_type_id = $data['entity_type_id'];
    $entity = $this->entityRepository->loadEntityByUuid($entity_type_id, $uuid);
    $entity_type_object = $this->entityTypeManager->getDefinition($entity_type_id);

    // Keys of entity.
    $key_id = $entity_type_object->getKey('id');
    $key_revision_id = $entity_type_object->getKey('revision');

    if ($entity) {
      $is_new = FALSE;
      $status = 'update';

      // Replace ID entity.
      $decode[$key_id][0]['value'] = $entity->id();

      // Skip if the Changed time the same or less in the file.
      if (method_exists($entity, 'getChangedTime')) {
        $changed_time_entity = $entity->getChangedTime();
        $changed_time_file = strtotime($decode['changed'][0]['value']);

        if (!$this->forceOverride && $changed_time_file <= $changed_time_entity) {
          $status = 'skip';
        }
      }
    }
    else {
      $status = 'create';
      $is_new = TRUE;

      // Ignore ID for creating a new entity.
      unset($decode[$key_id]);
    }

    // Ignore revision and id of entity.
    unset($decode[$key_revision_id]);

    $data['is_new'] = $is_new;
    $data['status'] = $status;
    $data['data'] = $decode;

    return $this;
  }

  /**
   * This event is triggered before decoding to an entity.
   *
   * @param $file
   *
   * @return $this
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function preDenormalize(&$file) {
    $this->updateTargetRevisionId($file['data']);

    return $this;
  }

  /**
   * Adding prepared data for import.
   *
   * @param $data
   *
   * @return $this
   */
  protected function addToImport($data) {
    $uuid = $data['data']['uuid'][0]['value'];
    $this->dataToImport[$uuid] = $data;

    return $this;
  }

  /**
   * Get all reference by entity array content.
   *
   * @param array $content
   *
   * @return array
   */
  private function getReferences(array $content) {
    $references = [];

    if (isset($content['_embedded'])) {
      foreach ($content['_embedded'] as $link) {
        foreach ($link as $reference) {
          $uuid = $reference['uuid'][0]['value'];
          $path = $this->getPathToFileByName($uuid);

          if ($path) {
            $references[] = $this->files[$path];
          }
        }
      }
    }

    return $references;
  }

  /**
   * Get path to file by Name.
   *
   * @param $name
   *
   * @return false|int|string
   */
  private function getPathToFileByName($name) {
    $array_column = array_column($this->files, 'name', 'uri');
    return array_search($name . '.json', $array_column);
  }

  /**
   * Get Entity type ID by link.
   *
   * @param $link
   *
   * @return string|string[]
   */
  private function getEntityTypeByLink($link) {
    $type = $this->linkManager->getTypeInternalIds($link);

    if ($type) {
      $entity_type_id = $type['entity_type'];
    }
    else {
      $components = array_reverse(explode('/', $link));
      $entity_type_id = $components[1];
      $this->cache->invalidate('hal:links:types');
    }

    return $entity_type_id;
  }

  /**
   * If this entity contains a reference field with target revision is value,
   * we should to update it.
   *
   * @param $decode
   *
   * @return $this
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function updateTargetRevisionId(&$decode) {
    if (isset($decode['_embedded'])) {
      foreach ($decode['_embedded'] as $link_key => $link) {
        if (array_column($link, 'target_revision_id')) {
          foreach ($link as $ref_key => $reference) {
            $url = $reference['_links']['type']['href'];
            $uuid = $reference['uuid'][0]['value'];
            $entity_type = $this->getEntityTypeByLink($url);
            $entity = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);

            // Update the Target revision id if child entity exist on this site.
            if ($entity) {
              $revision_id = $entity->getRevisionId();
              $decode['_embedded'][$link_key][$ref_key]['target_revision_id'] = $revision_id;
            }
          }
        }
      }
    }

    return $this;
  }

}
