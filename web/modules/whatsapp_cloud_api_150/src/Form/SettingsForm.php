<?php

namespace Drupal\whatsapp_cloud_api_150\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Configure Whatsapp Cloud Api 150% settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'whatsapp_cloud_api_150_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['whatsapp_cloud_api_150.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['authToken'] = [
      '#type' => 'textarea',
      '#title' => 'Identificador de autorización',
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('authToken'),
    ];
    $form['verifyTokenString'] = [
      '#type' => 'textfield',
      '#title' => 'Identificador de verificación (Webhook)',
      '#description' => $this->t('Es una contraseña que se pone en la configuración de Meta'),
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('verifyTokenString'),
    ];
    $form['phoneId'] = [
      '#type' => 'textfield',
      '#title' => 'Identificador del número de teléfono',
      '#description' => $this->t('No es un prefijo, lo proporciona Meta'),
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('phoneId'),
    ];
    $form['cloudVersion'] = [
      '#type' => 'textfield',
      '#title' => 'Versión Whatsapp Graph Api',
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('cloudVersion') ?? 'v16.0',
    ];

    $form['webhook_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => 'Enlace webook',
      '#default_value' => Url::fromUserInput('/whats150/whatsapp')->setAbsolute()->toString(),
    ];

    $form['stateAdvises'] = [
      '#type' => 'textfield',
      '#title' => 'Estado para Conversaciones del Agente(Asesor)',
      '#description' => $this->t('ID de los pasos creados en el Flujo'),
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('stateAdvises'),
    ];
    $form['closedstateAdvises'] = [
      '#type' => 'textfield',
      '#title' => 'Estado Cerrado para Conversaciones con el Agente(Asesor)',
      '#description' => $this->t('ID de los pasos creados en el Flujo'),
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('closedstateAdvises'),
    ];

    $form['hoursAgentLV'] = [
      '#type' => 'fieldset',
      '#title' => 'Horario de atención del Agente(Asesor) Lunes a Viernes',
    ];
    $form['hoursAgentLV']['hoursAgentLVStart'] = [
      '#type' => 'datetime',
      '#title' => 'Hora de inicio',
      '#date_date_element' => 'none', // hide date element
      '#date_time_element' => 'time',
      '#required' => TRUE,
      '#default_value' => new DrupalDateTime($this->config('whatsapp_cloud_api_150.settings')->get('hoursAgentLVStart')),
    ];
    $form['hoursAgentLV']['hoursAgentLVEnd'] = [
      '#type' => 'datetime',
      '#title' => 'Hora de fin',
      '#date_date_element' => 'none', // hide date element
      '#date_time_element' => 'time',
      '#required' => TRUE,
      '#default_value' => new DrupalDateTime($this->config('whatsapp_cloud_api_150.settings')->get('hoursAgentLVEnd')),
    ];
    $form['hoursAgentS'] = [
      '#type' => 'fieldset',
      '#title' => 'Horario de atención del Agente(Asesor) Sábado',
    ];
    $form['hoursAgentS']['hoursAgentSStart'] = [
      '#type' => 'datetime',
      '#title' => 'Hora de inicio',
      '#date_date_element' => 'none', // hide date element
      '#date_time_element' => 'time',
      '#required' => TRUE,
      '#default_value' => new DrupalDateTime($this->config('whatsapp_cloud_api_150.settings')->get('hoursAgentSStart')),
    ];
    $form['hoursAgentS']['hoursAgentSEnd'] = [
      '#type' => 'datetime',
      '#title' => 'Hora de fin',
      '#date_date_element' => 'none', // hide date element
      '#date_time_element' => 'time',
      '#required' => TRUE,
      '#default_value' => new DrupalDateTime($this->config('whatsapp_cloud_api_150.settings')->get('hoursAgentSEnd')),
    ];

    $form['msgAgent'] = [
      '#type' => 'fieldset',
      '#title' => 'Mensajes automaticos',
    ];
    $form['msgAgent']['msgAgentStart'] = [
      '#type' => 'textarea',
      '#title' => 'Mensaje de inicio de la conversación',
      '#description' => 'Mensaje que se envía cuando el usuario inicia una conversación con el Agente(Asesor)',
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('msgAgentStart'),
    ];
    $form['msgAgent']['msgAgentEnd'] = [
      '#type' => 'textarea',
      '#title' => 'Mensaje de fin de la conversación',
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('msgAgentEnd'),
    ];
    $form['msgAgent']['msgAgentOff'] = [
      '#type' => 'textarea',
      '#title' => 'Mensaje fuera de horario',
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('msgAgentOff'),
    ];
    $form['msgAgent']['phoneAgent'] = [
      '#type' => 'textfield',
      '#title' => 'Teléfono para notificar solicitud de agente',
      '#default_value' => $this->config('whatsapp_cloud_api_150.settings')->get('phoneAgent'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->config('whatsapp_cloud_api_150.settings')
      ->set('verifyTokenString', $form_state->getValue('verifyTokenString') ?? '')
      ->set('authToken', $form_state->getValue('authToken') ?? '')
      ->set('phoneId', $form_state->getValue('phoneId') ?? '')
      ->set('cloudVersion', $form_state->getValue('cloudVersion'))
      ->set('stateAdvises', $form_state->getValue('stateAdvises'))
      ->set('closedstateAdvises', $form_state->getValue('closedstateAdvises'))
      ->set('hoursAgentLVStart', $form_state->getValue('hoursAgentLVStart')->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT))
      ->set('hoursAgentLVEnd', $form_state->getValue('hoursAgentLVEnd')->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT))
      ->set('hoursAgentSStart', $form_state->getValue('hoursAgentSStart')->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT))
      ->set('hoursAgentSEnd', $form_state->getValue('hoursAgentSEnd')->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT))
      ->set('msgAgentStart', $form_state->getValue('msgAgentStart'))
      ->set('msgAgentEnd', $form_state->getValue('msgAgentEnd'))
      ->set('msgAgentOff', $form_state->getValue('msgAgentOff'))
      ->set('phoneAgent', $form_state->getValue('phoneAgent'))
       ->save();
    parent::submitForm($form, $form_state);
  }
}
