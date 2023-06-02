<?php

namespace Drupal\co_150_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Modulo 150 Core settings for this site.
 */
class SendSmsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'co_150_core_send_cms';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['co_150_core.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['sms_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $this->config('co_150_core.settings')->get('sms_url'),
    ];
    $form['sms_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefijo'),
      '#default_value' => $this->config('co_150_core.settings')->get('sms_prefix'),
    ];
    $form['sms_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensaje'),
      '#default_value' => $this->config('co_150_core.settings')->get('sms_message'),
    ];
    $form['sms_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Usuario'),
      '#default_value' => $this->config('co_150_core.settings')->get('sms_user'),
    ];
    $form['sms_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Contraseña'),
      '#default_value' => $this->config('co_150_core.settings')->get('sms_password'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('co_150_core.settings')
      ->set('sms_url', $form_state->getValue('sms_url'))
      ->set('sms_prefix', $form_state->getValue('sms_prefix'))
      ->set('sms_message', $form_state->getValue('sms_message'))
      ->set('sms_user', $form_state->getValue('sms_user'))
      ->set('sms_password', $form_state->getValue('sms_password'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
