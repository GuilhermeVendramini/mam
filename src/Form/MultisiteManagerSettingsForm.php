<?php

namespace Drupal\mam\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;

/**
 * Class MultisiteManagerSettingsForm.
 *
 * @package Drupal\mam\Form
 */
class MultisiteManagerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mam_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mam.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mam.settings');
    $form['drush'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drush'),
      '#description' => $this->t('Drush command installation.'),
      '#maxlength' => 100,
      '#size' => 100,
      '#default_value' => $config->get('drush'),
      '#required' => TRUE
    ];
    $form['custom_command'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Custom command'),
      '#description' => $this->t('Put your custom command here in YAML structure. Example:</br>
        Custom group:</br>
        &nbsp;cm1: Command 1</br>
        &nbsp;cm2: Command 2'
    ),
      '#rows' => 30,
      '#default_value' => $config->get('custom_command'),
    ];

    $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    try {
      Yaml::decode($form_state->getValue('custom_command'));
    }
    catch (InvalidDataTypeException $e) {
      $form_state->setErrorByName('custom_command', $this->t('The custom command failed with the following message: %message', ['%message' => $e->getMessage()]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('mam.settings')
      ->set('drush', $form_state->getValue('drush'))
      ->set('custom_command', $form_state->getValue('custom_command'))
      ->save();
  }

}
