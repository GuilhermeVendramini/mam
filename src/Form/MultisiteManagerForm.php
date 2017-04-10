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

/**
 * Class MultisiteManagerForm.
 *
 * @package Drupal\multisite_manager\Form
 */
class MultisiteManagerForm extends FormBase {

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
   * Constructor.
   *
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   Queue factory service to get new/existing queues for use.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(QueueFactory $queue_factory, Connection $database, CronInterface $cron, Settings $settings) {
    $this->queueFactory = $queue_factory;
    $this->queueType = $settings->get('queue_default', 'queue.database');
    $this->database = $database;
    $this->cron = $cron;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('queue'), $container->get('database'), $container->get('cron'), $container->get('settings'));
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
  public function buildForm(array $form, FormStateInterface $form_state) {
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
        '#header' => array(
          $this->t('Domains'),
          $this->t('Action'),
          $this->t('Expire'),
          $this->t('Created'),
        ),
      ];
      $form['status_fieldset']['delete_items'] = [
        '#type' => 'submit',
        '#validate' => ['::deleteActionValidate'],
        '#value' => $this->t('Delete selected items'),
        '#submit' => array(array($this, 'submitDeleteItems')),
      ];
    } else {
      $form['status_fieldset']['status'] = array(
        '#type' => 'markup',
        '#markup' => $this->t('There are no items in the queue.'),
      );
    }
    $form['action_fieldset'] = [
      '#type' => 'details',
      '#title' => $this->t('Action'),
      '#open' => TRUE,
      '#required' => TRUE,
    ];
    $form['action_fieldset']['domains'] = [
      '#type' => 'tableselect',
      '#header' => array(
        'domain' => array(
        'data' => $this->t('Domains'),
      )),
      '#options' => $this->getOptionsDomain(),
    ];
    $form['action_fieldset']['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#options' => $this->getOptionsAction(),
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
      'cr' => $this->t('Clear cache'),
      'cron' => $this->t('Run cron'),
      'sset system.maintenance_mode 1' => $this->t('Put site into maintenance mode'),
      'sset system.maintenance_mode 0' => $this->t('Take out site maintenance mode'),
      'custom' => $this->t('Custom drush command')];

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
    $domains = $form_state->getValue('domains');
    $claim_time = $form_state->getValue('claim_time') ?: 0;

    $data['domains'] = $domains;
    $data['action'] = $action;

    $queue = \Drupal::queue('multisite_queue');
    $queue->createQueue();
    $queue->createItem($data);
    $queue->claimItem($claim_time);
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

  /**
   * Retrieves the options processed.
   */
  public function getOptionsQueue() {
    $items = $this->retrieveQueue('multisite_queue');
    $result = array_map(array($this, 'processQueueItemForTable'), $items);

    $queues = array();
    foreach ($result as $value) {
      $queues[$value['item_id']] = array($value['domains'], $value['action'], $value['expire'], $value['created']);
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

    // This example requires the default queue implementation to work,
    // so we bail if some other queue implementation has been installed.
    if (!$this->doesQueueUseDb()) {
      return $items;
    }

    // Make sure there are queue items available. The queue will not create our
    // database table if there are no items.
    if ($this->queueFactory->get($queue_name)->numberOfItems() >= 1) {
      $result = $this->database->query('SELECT item_id, data, expire, created FROM {' . DatabaseQueue::TABLE_NAME . '} WHERE name = :name ORDER BY item_id',
        [':name' => 'multisite_queue'],
        ['fetch' => \PDO::FETCH_ASSOC]
      );
      foreach ($result as $item) {
        $items[] = $item;
      }
    }

    return $items;
  }

  /**
   * Check if we are using the default database queue.
   *
   * @return bool
   *   TRUE if we are using the default database queue implementation.
   */
  protected function doesQueueUseDb() {
    return $this->queueType == 'queue.database';
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
    $domians = implode(',', array_filter($items['domains'], 'is_string'));
    $actions = $this->getOptionsAction();
    $action = $actions[$items['action']]->__toString() . ' (' .$items['action'] . ')';
    $item['created'] = date('r', $item['created']);
    $item['domains'] = $domians;
    $item['action'] =  $action;

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
      
      drupal_set_message(t('Items deleted!'));
    } catch (Exception $e) {
      drupal_set_message(t('Error deleting items @error', array('@error' => $e)), 'error');
    }

    $form_state->setRebuild();
  }

}
