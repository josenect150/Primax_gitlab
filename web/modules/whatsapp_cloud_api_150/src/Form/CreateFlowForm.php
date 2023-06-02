<?php

namespace Drupal\whatsapp_cloud_api_150\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;

/**
 * Configure the flow.
 */
class CreateFlowForm extends ConfigFormBase {

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
    $form['message_type_list'] = [
      '#type' => 'fieldset',
      '#title' => 'Mensaje tipo Lista Desplegable',
    ];
    $form['message_type_list']['list_identification'] = [
      '#type' => 'textfield',
      '#title' => 'Identificación del paso',
      '#required' => TRUE,
      '#description' => 'ID para identificar el paso.',
    ];
    $form['message_type_list']['list_message'] = [
      '#type' => 'textarea',
      '#title' => 'Mensaje',
      '#required' => TRUE,
    ];
    $form['message_type_list']['list_open_message'] = [
      '#type' => 'textfield',
      '#title' => 'Mensaje para abrir la lista',
      '#required' => TRUE,
      '#description' => 'Max 20 caracteres',
      '#maxlength' => 20,
    ];
    for ($i = 1; $i <= 10; $i++) {
      $form['message_type_list']['list_' . $i . '_id'] = [
        '#type' => 'textfield',
        '#title' => 'Identificador opción #' . $i,
      ];
      $form['message_type_list']['list_' . $i . '_msg'] = [
        '#type' => 'textfield',
        '#title' => 'Mensaje opción #' . $i,
        '#description' => 'Max 24 caracteres',
        '#maxlength' => 24,
      ];
      $form['message_type_list']['list_' . $i . '_desc'] = [
        '#type' => 'textfield',
        '#title' => 'Descripción opción #' . $i,
        '#description' => 'Max 72 caracteres',
        '#maxlength' => 72,
      ];

      $form['message_type_list']['list_' . $i . '-separador'] = [
        '#type' => 'html_tag',
        '#tag' => 'hr',
        '#value' => new FormattableMarkup('', []),
      ];
    }

    $form['message_type_button'] = [
      '#type' => 'fieldset',
      '#title' => 'Mensaje tipo Reply Button',
    ];
    $form['message_type_button']['button_identification'] = [
      '#type' => 'textfield',
      '#title' => 'Identificación del paso',
      '#required' => TRUE,
      '#description' => 'id para identificar el paso.',
    ];
    $form['message_type_button']['button_message'] = [
      '#type' => 'textarea',
      '#title' => 'Mensaje',
      '#required' => TRUE,
    ];
    $form['message_type_button']['button_1_id'] = [
      '#type' => 'textfield',
      '#title' => 'Identificador botón #1',
    ];
    $form['message_type_button']['button_1_msg'] = [
      '#type' => 'textfield',
      '#title' => 'Mensaje botón #1',
      '#description' => 'Max 20 caracteres',
      '#maxlength' => 20,
    ];
    $form['message_type_button']['button_2_id'] = [
      '#type' => 'textfield',
      '#title' => 'Identificador botón #2',
    ];
    $form['message_type_button']['button_2_msg'] = [
      '#type' => 'textfield',
      '#title' => 'Mensaje botón #2',
      '#description' => 'Max 20 caracteres',
      '#maxlength' => 20,
    ];
    $form['message_type_button']['button_3_id'] = [
      '#type' => 'textfield',
      '#title' => 'Identificador botón #3',
    ];
    $form['message_type_button']['button_3_msg'] = [
      '#type' => 'textfield',
      '#title' => 'Mensaje botón #3',
      '#description' => 'Max 20 caracteres',
      '#maxlength' => 20,
    ];


    $form['message_type_msg'] = [
      '#type' => 'fieldset',
      '#title' => 'Mensaje tipo Text',
    ];
    $form['message_type_msg']['msg_identification'] = [
      '#type' => 'textfield',
      '#title' => 'Identificación del paso',
      '#required' => TRUE,
      '#description' => 'id para identificar el paso.',
    ];
    $form['message_type_msg']['msg_message'] = [
      '#type' => 'textarea',
      '#title' => 'Mensaje',
      '#required' => TRUE,
    ];
    $form['message_type_msg']['msg_expected_response'] = [
      '#type' => 'select',
      '#title' => 'Respuesta esperada',
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#options' => [
        'none' => 'No esperar respuesta, pasar al siguiente paso',
        'msg' => 'Esperar una respuesta de solo texto',
        // 'audio' => 'Esperar una respuesta con audio',
        // 'image' => 'Esperar una respuesta con imágen',
      ],
      '#default_value' => ['msg'],
    ];
    $form['message_type_msg']['msg_regex'] = [
      '#type' => 'textfield',
      '#title' => 'Mensaje: Expresión regular',
      '#description' => 'Expresión regular que debe seguir el mensaje que el usuario envie. Vacio si no se validará',
    ];
    $form['message_type_msg']['msg_unique'] = [
      '#type' => 'checkbox',
      '#title' => 'Mensaje: Valor único?',
    ];
    $form['message_type_msg']['msg_unique_table'] = [
      '#type' => 'textfield',
      '#title' => 'Mensaje: Nombre de la tabla donde se buscara el valor único',
    ];
    $form['message_type_msg']['msg_unique_column'] = [
      '#type' => 'textfield',
      '#title' => 'Mensaje: Nombre de la columna en la tabla donde se buscara el valor único',
    ];

    $form['editor'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => new FormattableMarkup('<div id="drawflow"></div>', []),
    ];

    $form['save_modal'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => new FormattableMarkup('
      <div id="saveModal" class="modal">
        <div class="modal-content">
          <span class="closeModal">&times;</span>
          <h2>Elemento</h2>
          <div id="btns_select_type">
            <button id="msg_type" class="button">Mensaje</button>
            <button id="reply_button_type" class="button">Reply Button</button>
            <button id="list_type" class="button">Lista desplegable</button>
            <button id="agent_type" class="button">Hablar con Asesor</button>
            <button id="end_type" class="button">Fin del flujo</button>
          </div>
          <div id="fieldSetData"></div>
          <button id="save_type" class="button">Guardar</button>
        </div>
      </div>', []),
    ];

    $form['edit_modal'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => new FormattableMarkup('
      <div id="editModal" class="modal">
        <div class="modal-content">
          <span class="closeEditModal">&times;</span>
          <h2>Editar Elemento</h2>
          <div id="fieldEditData"></div>
          <button id="update_data" class="button">Actualizar</button>
        </div>
      </div>', []),
    ];

    $toImportFlow = unserialize($this->config('whatsapp_cloud_api_150.settings')->get('to_import_drawflow') ?? '');
    $toGetElements = unserialize($this->config('whatsapp_cloud_api_150.settings')->get('to_get_elements') ?? '');

    $form['#attached']['library'][] = 'whatsapp_cloud_api_150/drawflow';
    $form['#attached']['drupalSettings']['whatsapp']['dataToImport'] = $toImportFlow;
    $form['#attached']['drupalSettings']['whatsapp']['dataElements'] = $toGetElements;

    $form['save_flow'] = [
      '#type' => 'button',
      '#value' => 'Guardar Flujo',
      '#id' => 'save_flow',
      '#attributes' => [
        'class' => ['button--primary']
      ]
    ];
    return $form;
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
      ->set('verifyTokenString', $form_state->getValue('verifyTokenString'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}
