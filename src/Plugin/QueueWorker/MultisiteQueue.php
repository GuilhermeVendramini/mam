<?php

namespace Drupal\mam\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
/**
 * Processes Tasks for Learning.
 *
 * @QueueWorker(
 *   id = "multisite_queue",
 *   title = @Translation("Learning task worker: multisite queue"),
 *   cron = {"time" = 120}
 * )
 */
class MultisiteQueue extends QueueWorkerBase {
  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $domain = $data['domain'];
    $action = $data['action'];
    $drush = \Drupal::config('mam.settings')->get('drush');

    exec($drush . ' ' . $action . ' -l ' . $domain . ' 2>&1');    
    
    $message = t('Cron multisite executed! Domain: @domain - Action: @action', array('@domain' => $domain, '@action' => $action));
    \Drupal::logger('cron')->notice($message);
  }
}