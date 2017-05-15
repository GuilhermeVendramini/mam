<?php

namespace Drupal\multisite_manager\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Site\Settings;
use Drupal\Core\CronInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Serialization\Yaml;

/**
 * Class MultisiteManagerForm.
 *
 * @package Drupal\multisite_manager\Form
 */
class MultisiteManagerForm extends FormBase {

  /**
   * The cache.default cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * The database object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The CronInterface object.
   *
   * @var \Drupal\Core\CronInterface
   */
  protected $cron;

  /**
   * What kind of queue backend are we using?
   *
   * @var string
   */
  protected $queueType;

  /**
   * The current domain when in a Domain Entity
   *
   * @var string
   */
  protected $currentDomain;

  /**
   * The current domain id when in a Domain Entity
   *
   * @var string
   */
  protected $currentDomainId;

  /**
   * Config from multisite manager config
   *
   * @var \Drupal::config('multisite_manager.settings')
   */
  protected $config;

  
  /**
   * Constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue factory service to get new/existing queues for use.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(QueueFactory $queue_factory, Connection $database, CronInterface $cron, Settings $settings, CacheBackendInterface $cache_backend) {
    $this->queueFactory = $queue_factory;
    $this->queueType = $settings->get('queue_default', 'queue.database');
    $this->database = $database;
    $this->cron = $cron;
    $this->cacheBackend = $cache_backend;
    $this->config = \Drupal::config('multisite_manager.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('queue'), $container->get('database'), $container->get('cron'), $container->get('settings'), $container->get('cache.default'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multisite_manager_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $domain = NULL, $domain_id = NULL) {
    $this->currentDomain = $domain;
    $this->currentDomainId = $domain_id;
    $actions = $this->getOptionsQueue();
    
    $form['status_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Action status'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];
    if (count($actions) > 0) {
      $form['status_fieldset']['action_status'] = [
        '#type' => 'tableselect',
        '#options' => $actions,
        '#header' => [
          $this->t('Domains'),
          $this->t('Action'),
          $this->t('Expire'),
          $this->t('Created'),
        ],
      ];
      $form['status_fieldset']['delete_items'] = [
        '#type' => 'submit',
        '#validate' => ['::deleteActionValidate'],
        '#value' => $this->t('Delete selected items'),
        '#submit' => ['::submitDeleteItems'],
      ];
    } else {
      $form['status_fieldset']['status'] = [
        '#type' => 'markup',
        '#markup' => $this->t('There are no items in the queue.'),
      ];
    }
    $form['action_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Action'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];
    $form['action_fieldset']['domains'] = [
      '#type' => 'tableselect',
      '#header' => [
        'domain' => [
          'data' => $this->t('Domain'),]],
      '#default_value' =>  array_combine([$this->currentDomain], [$this->currentDomain]),
      '#options' => $this->getOptionsDomain($this->currentDomain),
      '#access' => $this->currentDomain ? FALSE : TRUE,
      '#empty' => $this->t('No domains created yet.'),
    ];
    $form['action_fieldset']['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#options' => $this->getOptionsAction(),
    ];
    $form['action_fieldset']['details_module'] = [
      '#type' => 'details',
      '#title' => $this->t('Modules'),
      '#open' => TRUE,
      '#required' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="action"]' => [
            ['value' => 'pmu'],
            ['value' => 'en'],
          ],
        ],
      ],
    ];
    $header = [
        'name' => $this->t('Name'),
        'version' => $this->t('Version'),
        'package' => $this->t('Package'),];
    if($this->currentDomain) {
      $header['status'] = $this->t('Status');
    }
    $form['action_fieldset']['details_module']['modules'] = [
      '#type' => 'tableselect',
      '#options' =>  $this->getModules(),
      '#header' => $header,
      '#prefix' => 'Note: After run cron, clean the cache to see the modules status change'
    ];
    $form['action_fieldset']['custom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom drush command'),
      '#description' => $this->t('Put only your command without "dush". Ex.: "user-block 1"'),
      '#states' => [
        'visible' => [
          ':input[name="action"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $form['action_fieldset']['claim_time'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Claim time'),
      '#description' => $this->t('Put the time in cesonds. Ex.: "60" for 1 minute, "3600" for one hour. This time is only valid if cron runs during this time period.'),
    ];
    $form['action_fieldset']['submit'] = [
        '#type' => 'submit',
        '#validate' => ['::actionValidate'],
        '#value' => $this->t('Add action'),
    ];

    return $form;
  }

  /**
   * Retrieves the options action.
   */
  public function getOptionsAction() {
    $actions = [
      'Site' => [
        'cr' => $this->t('Clear cache'),
        'cron' => $this->t('Run cron'),
        'sset system.maintenance_mode 1' => $this->t('Put site into maintenance mode'),
        'sset system.maintenance_mode 0' => $this->t('Retire site maintenance mode'),
      ],
      'Modules' => [
        'en' => $this->t('Enable module'),
        'pmu' => $this->t('Uninstall module'),
      ],
      'Custom' => [
        'custom' => $this->t('Custom drush command'),
      ],
    ];

    $custom_actions = Yaml::decode($this->config->get('custom_command'));
    if(count($custom_actions) > 0) {
      foreach ($custom_actions as $key_action => $action) {
        $actions[$key_action] = $action;
      }
    }
    return $actions;
  }

  /**
   * Validate add action.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function actionValidate(array &$form, FormStateInterface $form_state) {
    $domains = array_filter($form_state->getValue('domains'), 'is_string');
    if(empty($domains)) {
      $form_state->setErrorByName('domains', $this->t('Please, select a domain to add action.'));
    }

    $action = $form_state->getValue('action');
    $custom = $form_state->getValue('custom');
    if($action == 'custom' && !$custom) {
      $form_state->setErrorByName('custom', $this->t('Please, set a custom drush command.'));
    }

    $modules = array_filter($form_state->getValue('modules'), 'is_string');
    if(($action == 'pmu' || $action == 'en') && !$modules) {
      $form_state->setErrorByName('modules', $this->t('Please, select a module.'));
    }
  }

  /**
   * Validate delete action.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function deleteActionValidate(array &$form, FormStateInterface $form_state) {
    $action = array_filter($form_state->getValue('action_status'), 'is_string');
    if(empty($action)) {
      $form_state->setErrorByName('action_status', $this->t('Please, select an action status to delete.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('action');
    $domains = array_filter($form_state->getValue('domains'), 'is_string');
    $claim_time = $form_state->getValue('claim_time') ?: 0;
    $data['action'] = $action;
    $data['action_key'] = $action;

    /**
     * If action is custom, then action retrieve value from "Custom drush command"
     */
    if($action == 'custom') {
      $data['action'] = $form_state->getValue('custom');
    }

    /**
     * If action is "pmu" or "en", then add action queue for each module
     */
    if($action == 'pmu' || $action == 'en') {
      $modules = array_filter($form_state->getValue('modules'), 'is_string');
      foreach ($modules as $module) {
        $data['action'] = $action . ' ' . $module . ' -y';
        $this->addActionQueue($domains, $data, $claim_time);
      }
    }else {
      $this->addActionQueue($domains, $data, $claim_time);
    }

  }

  public function addActionQueue(array $domains, array $data, string $claim_time) {
    foreach ($domains as $domain) {
      $data['domain'] = $domain;
      $queue = \Drupal::queue('multisite_queue');
      $queue->createQueue();
      $queue->createItem($data);
      $queue->claimItem($claim_time);
    }
  }

  /**
   * Retrieves the options domain.
   */
  public function getOptionsDomain() {
    $query = $this->database->select('domain_entity', 'm');
    $query->fields('m', ['domain','name','id']);
    $result = $query->execute()->fetchAll();
    
    $domains = [];
    foreach ($result as $domain) {
      $domains[$domain->domain] =  ['domain' => [
        'data' => [
          '#type' => 'link',
          '#title' => $domain->name,
          '#url' => Url::fromUri('internal:/admin/structure/domain-entity/' . $domain->id)]
        ]];
    }

    return $domains;
  }

  public function getModules() {
    $domain = $this->currentDomain;
    $domain_id = $this->currentDomainId;
    $drush = $this->config->get('drush');
    $cid = 'multisite_manager_modules_domain' . $domain_id;
    $data = NULL;
    if ($cache = \Drupal::cache()->get($cid)) {
      $data = $cache->data;
    }
    else {
      $command = $domain ? ' -l ' . $domain : '';
      exec($drush . ' pm-list --type=Module --format=php' . $command . ' 2>&1', $modules);
      if(count($modules) > 0) {
        $data = unserialize($modules[0]);
      }
      \Drupal::cache()->set($cid, $data, Cache::PERMANENT, ['domain_entity:' . $domain_id]);
    }

    return $data;
  }

  /**
   * Retrieves the options processed.
   */
  public function getOptionsQueue() {
    $items = $this->retrieveQueue('multisite_queue');
    $result = array_map(array($this, 'processQueueItemForTable'), $items);

    $queues = array();
    foreach ($result as $value) {
      $queues[$value['item_id']] = array($value['domain'], $value['action'], $value['expire'], $value['created']);
    }

    return $queues;
  }

  /**
   * Retrieves the queue from the database for display purposes only.
   *
   * It is not recommended to access the database directly, and this is only
   * here so that the user interface can give a good idea of what's going on
   * in the queue.
   *
   * @param string $queue_name
   *   The name of the queue from which to fetch items.
   *
   * @return array
   *   An array of item arrays.
   */
  public function retrieveQueue($queue_name) {
    $items = array();

    // Make sure there are queue items available. The queue will not create our
    // database table if there are no items.
    if ($this->queueFactory->get($queue_name)->numberOfItems() >= 1) {
      $result = $this->database->query('SELECT item_id, data, expire, created FROM {' . DatabaseQueue::TABLE_NAME . '} WHERE name = :name AND data LIKE :domain ORDER BY item_id',
        [':name' => 'multisite_queue',
        ':domain' => '%' . $this->currentDomain . '%'],
        ['fetch' => \PDO::FETCH_ASSOC]
      );
      foreach ($result as $item) {
        $items[] = $item;
      }
    }

    return $items;
  }

  /**
   * Helper method to format a queue item for display in a summary table.
   *
   * @param array $item
   *   Queue item array with keys for item_id, expire, created, and data.
   *
   * @return array
   *   An array with the queue properties in the right order for display in a
   *   summary table.
   */
  private function processQueueItemForTable(array $item) {
    if ($item['expire'] > 0) {
      $item['expire'] = $this->t('Claimed: expires %expire', array('%expire' => date('r', $item['expire'])));
    }
    else {
      $item['expire'] = $this->t('Unclaimed');
    }
    
    $items = unserialize($item['data']);
    $domain = $items['domain'];
    $actions = $this->getOptionsAction();

    foreach ($actions as $group) {
      foreach ($group as $group_key => $group_value) {
        $action[$group_key] = $group_value;
      }
    }
 
    $action_info = $action[$items['action_key']] . ' (' .$items['action'] . ')';
    $item['created'] = date('r', $item['created']);
    $item['domain'] = $domain;
    $item['action'] =  $action_info;

    return $item;
  }

  /**
   * Submit function for "Claim and delete" button.
   *
   * @param array $form
   *   Form definition array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   */
  public function submitDeleteItems(array &$form, FormStateInterface $form_state) {
    $status = $form_state->getValue('action_status');
    $items_id = array_filter($status, 'is_string'); 
    
    try {
      $query = $this->database->delete('queue');
      $query->condition('item_id', $items_id, 'IN');
      $query->execute();
      
      drupal_set_message($this->t('Items deleted!'));
    } catch (Exception $e) {
      drupal_set_message($this->t('Error deleting items @error', array('@error' => $e)), 'error');
    }

    $form_state->setRebuild();
  }

}
