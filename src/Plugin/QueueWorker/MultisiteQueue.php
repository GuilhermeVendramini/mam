<?php

namespace Drupal\multisite_manager\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
/**
 * Processes Tasks for Learning.
 *
 * @QueueWorker(
 *   id = "multisite_queue",
 *   title = @Translation("Learning task worker: multisite queue"),
 *   cron = {"time" = 90}
 * )
 */
class MultisiteQueue extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $domains = implode(',' , $data['domains']);
    $action = $data['action'];
    
    foreach ($data['domains'] as $domain) {
      exec('drush ' . $action . ' -l ' . $domain);
    }
    
    $message = t('Cron multisite executed! Domains: @domains - Action: @action', array('@domains' => $domains, '@action' => $action));
    \Drupal::logger('cron')->notice($message);
  }
}