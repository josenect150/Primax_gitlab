<?php

namespace Drupal\whatsapp_cloud_api_150\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Whatsapp Cloud Api 150% routes.
 */
class WhatsappCloudApi150Controller extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config_factory, AccountInterface $account, Connection $connection, FileSystem $fileSystem, LoggerChannelFactoryInterface $logger_factory) {
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
    $this->account = $account;
    $this->connection = $connection;
    $this->file_system = $fileSystem;
    $config = $this->configFactory->get('whatsapp_cloud_api_150.settings');
    $this->bearerToken = $config->get('authToken');
    $this->phoneId = $config->get('phoneId');
    $this->graphVersion = $config->get('cloudVersion');
    $this->messageStartAgent = $config->get('msgAgentStart');
    $this->msgAgentOff = $config->get('msgAgentOff');

    $this->loggerFactory = $logger_factory;
    $this->logger = $logger_factory->get('Whatsapp Cloud Api 150%');;

    $this->url = "https://graph.facebook.com/$this->graphVersion/$this->phoneId/messages";
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('file_system'),
      $container->get('logger.factory')
    );
  }

  /**
   * Verify the Token.
   */
  public function verifyToken() {
    $config = $this->configFactory->get('whatsapp_cloud_api_150.settings');
    $stack = $this->requestStack->getCurrentRequest()->query;
    // 'a346AD$%HRZg457@#%AERH';
    $myToken = $config->get('verifyTokenString');
    $hubChallenge = $stack->get('hub_challenge');
    $hubMode = $stack->get('hub_mode');
    $hubToken = $stack->get('hub_verify_token');
    if ($hubMode == 'subscribe') {
      if ($hubToken == $myToken) {
        return new Response($hubChallenge, 200, ['Content-Type' => 'text/plain']);
      }
    }
    return new JsonResponse(['error' => TRUE], 400);
  }

  /**
   * Update user step.
   */
  public function updateStep($conversationRecordId, $newStep, $lastStep) {
    $this->connection->update('whatsapp_cloud_api_150_conversations')->fields([
      'conversation_step' => $newStep ?? '',
      'conversation_last_step' => $lastStep,
      'conversation_last_timestamp' => time(),
    ])
      ->condition('id', $conversationRecordId)
      ->execute();
  }

  /**
   * Save Message in history table.
   */
  public function saveHistoryMessage($waid, $type, $message, $messageId, $timeReceived) {
    $insertData = $updateData = [
      'waid' => $waid,
      'type' => $type,
      'message' => $message,
      'message_id' => $messageId,
      'message_timestamp' => $timeReceived,
      'created' => time(),
    ];
    unset($updateData['created']);
    $this->connection->merge('whatsapp_cloud_api_150_history')
      ->insertFields($insertData)
      ->updateFields($updateData)
      ->key('message_id', $messageId)
      ->execute();
  }

  /**
   * Verify the Token.
   */
  public function processWebhook() {

    try {
      $stack = json_decode(\Drupal::request()->getContent(), TRUE);
      $config = $this->configFactory->get('whatsapp_cloud_api_webhook.settings');
      $value = $stack['entry'][0]['changes'][0]['value'] ?? FALSE;
      $message = $value['messages'] ?? '';
      $statuses = $value['statuses'] ?? '';

      $currentStep = $currentStepIdentification = $selecConversationStep = null;
      $fromPhone = '';
      if (!empty($message)) {
        // Verificar en qué paso de la conversación se encuentra.
        $waid = $value['contacts'][0]['wa_id'];
        $receiveMessageId = $message[0]['id'];
        $selecConversationStep = $this->connection->select('whatsapp_cloud_api_150_conversations', 'conv')->fields('conv')
          ->condition('conversation_id', $waid)
          ->condition('conversation_step', 'finish', '<>')
          ->condition('conversation_step', 'end', '<>')
          ->execute()->fetch();
        if (!$selecConversationStep) {
          $this->connection->insert('whatsapp_cloud_api_150_conversations')->fields([
            'conversation_id' => $waid,
            'conversation_step' => 'start',
            'conversation_last_step' => 'start',
            'conversation_last_timestamp' => time(),
            'created' => time(),
          ])->execute();
          $selecConversationStep = $this->connection->select('whatsapp_cloud_api_150_conversations', 'conv')->fields('conv')
            ->condition('conversation_id', $waid)
            ->condition('conversation_step', 'finish', '<>')
            ->condition('conversation_step', 'end', '<>')
            ->execute()->fetch();
        }
        $currentStepIdentification = $selecConversationStep->conversation_step;
        $currentStep = $this->getStepData($currentStepIdentification);
        $fromPhone = $message[0]['from'] ?? '';

        $timeReceived = time(); // To ensure to save this message first.
        $timeReceived -= 2;
        $responseMessageType = $message[0]['type'];
        if ($responseMessageType == 'text') {
          $typeOfMsg = 'text';
          $receiveMessage = trim($message[0]['text']['body']);
        } else if ($responseMessageType == 'interactive') {
          $typeOfMsg = $message[0]['interactive']['type'];
          $receiveMessage = trim($message[0]['interactive'][$typeOfMsg]['title']);
          if ($typeOfMsg == 'list_reply') {
            $receiveMessage .= "\n" . trim($message[0]['interactive'][$typeOfMsg]['description']);
          }
        }
        // Guardar el mensaje enviado por el usuario.
        $this->saveHistoryMessage($waid, $typeOfMsg, $receiveMessage, $receiveMessageId, $timeReceived);
      }

      if (!empty($statuses)) {
        // Nos dice si se recibio el mensaje o no.
        $statuses = $statuses[0];
        // From phone.
        $recipient_id = $statuses['recipient_id'];
        // Sent | delivered | read.
        $status = $statuses['status'] ?? 'N/A';
        return new JsonResponse(['error' => FALSE, 'body' => $status], 200);
      }

      if ($currentStepIdentification == NULL || $currentStep == NULL || $selecConversationStep == NULL) {
        $errorMsg = "No se puede obtener el paso actual del usuario? ($currentStepIdentification)";
        $this->logger->error($errorMsg);
        throw new \Exception($errorMsg, 500);
      } else if ($currentStepIdentification == 'start') {
        // Esta en el primer paso, debemos ver cuál es el siguiente paso y
        // enviarselo de una vez cambiando el estado de la conversación.
        $nextStepData = $this->getNextStepFromStep($currentStepIdentification, 'msg');
        $this->sendMessageToWhatsapp($fromPhone, $currentStep, $nextStepData, $selecConversationStep);
        return new JsonResponse(['error' => FALSE, 'nextStepData' => $nextStepData], 200);
      } else {
        // Otro paso diferente al inicial.
        $expectedResponses = explode(',', $currentStep->response_type);
        $expectedResponses = array_flip($expectedResponses);
        if (isset($expectedResponses["reply_button"])) {
          $responseMessageType = $message[0]['type'];
          // el paso actual espera una respuesta al presionar un botón.
          if ($responseMessageType == 'interactive') {
            // El mensaje que se recibio es un mensaje de respuesta a un botón.
            $interactie = $message[0]['interactive'];
            $typeInteractive = $interactie['type'];
            if ($typeInteractive == 'button_reply') {
              $replyButtonId = $interactie['button_reply']['id'];
              for ($i = 1; $i <= 3; $i++) {
                $buttonId = $currentStep->{"button_{$i}_id"};
                $buttonToStepIdentification = $currentStep->{"button_{$i}_to_step"};
                if ($buttonId == $replyButtonId) {
                  $nextStepData = $this->getStepData($buttonToStepIdentification);
                  $this->sendMessageToWhatsapp($fromPhone, $currentStep, $nextStepData, $selecConversationStep);
                  return new JsonResponse(['error' => FALSE, 'nextStepData other expected Reply Button' => $nextStepData], 200);
                }
              }
            }
          }
          $currentStep->msg = "Por favor, selecciona un botón.\n\n" . $currentStep->msg;
          // El mensaje que se recibio no es un mensaje de respuesta a un botón.
          // Debemos enviar un mensaje de error.
          $errorMsg = "El paso actual espera una respuesta al presionar un botón, pero se recibio algo diferente.";
          $this->logger->error($errorMsg);
          $logSendMessage = $this->sendMessageToWhatsapp($fromPhone, $currentStep, $currentStep, $selecConversationStep);
          if (isset($logSendMessage['error'])) {
            return new JsonResponse(['error' => TRUE, 'error' => $logSendMessage], 400);
          }
          return new JsonResponse(['error' => FALSE, 'msg' => $errorMsg], 200);
        } else if (isset($expectedResponses['list_reply'])) {
          $responseMessageType = $message[0]['type'];
          // el paso actual espera una respuesta al presionar un botón.
          if ($responseMessageType == 'interactive') {
            // El mensaje que se recibio es un mensaje de respuesta a un botón.
            $interactie = $message[0]['interactive'];
            $typeInteractive = $interactie['type'];
            if ($typeInteractive == 'list_reply') {
              $replyListId = $interactie['list_reply']['id'];
              for ($i = 1; $i <= 10; $i++) {
                $listId = $currentStep->{"list_{$i}_id"};
                $listToStepIdentification = $currentStep->{"list_{$i}_to_step"};
                if ($listId == $replyListId) {
                  $nextStepData = $this->getStepData($listToStepIdentification);
                  $this->sendMessageToWhatsapp($fromPhone, $currentStep, $nextStepData, $selecConversationStep);
                  return new JsonResponse(['error' => FALSE, 'nextStepData other espected listreply' => $nextStepData], 200);
                }
              }
            }
          }
          $currentStep->msg = "Por favor, selecciona un item de la lista.\n\n" . $currentStep->msg;
          // El mensaje que se recibio no es un mensaje de respuesta a una lista.
          // Debemos enviar un mensaje de error.
          $errorMsg = "El paso actual espera una respuesta al seleccionar de una lista, pero se recibio algo diferente.";
          $this->logger->error($errorMsg);
          $logSendMessage = $this->sendMessageToWhatsapp($fromPhone, $currentStep, $currentStep, $selecConversationStep);
          if (isset($logSendMessage['error'])) {
            return new JsonResponse(['error' => TRUE, 'error' => $logSendMessage], 400);
          }
          return new JsonResponse(['error' => FALSE, 'msg' => $errorMsg], 200);
        } else if (isset($expectedResponses["agent"])) {
          // El usuario acaba de enviar un mensaje, debemos notificarle al agente para que responda.
          return new JsonResponse(['error' => FALSE, 'nextStepData other expected agent' => TRUE], 200);
        } else if (isset($expectedResponses["none"])) {
          // No espera ningun mensaje de respuesta.
          // Buscamos el siguiente paso y lo enviamos.
          $nextStepData = $this->getStepData($currentStep->to_step);
          if ($currentStep->to_step != 'end') {
            // Hay otro paso, enviamos el mensaje y actualizamos el estado.
            $this->sendMessageToWhatsapp($fromPhone, $currentStep, $nextStepData, $selecConversationStep);
          }
          // Si es igual a 'end', es el paso final, solo actualicemos el
          // estado de la conversación.
          $this->updateStep($selecConversationStep->id, $nextStepData->identification, $currentStepIdentification);
          return new JsonResponse(['error' => FALSE, 'nextStepData other expected none' => $nextStepData], 200);
        }
      }

      $errorPrint = print_r($value, TRUE);
      $errorMsg = "Ocurrio un error al procesar el mensaje (Paso $currentStepIdentification): <pre>$errorPrint</pre>";
      $this->logger->error($errorMsg);
      throw new \Exception($errorMsg, 500);
    } catch (\Throwable $th) {
      return new JsonResponse(['error' => TRUE, 'msg' => $th->getMessage()], 500);
    }
  }

  /**
   * Send a message to user using the whatsapp api.
   */
  public function sendMessageToWhatsapp($toPhone, $currentStep, $stepDataToShow, $selecConversationStep, $replyMessageId = NULL) {
    $messageType = $stepDataToShow->type;
    $message = $stepDataToShow->msg;
    $payload = [
      'messaging_product' => 'whatsapp',
      'to' => $toPhone,
    ];
    if (!is_null($replyMessageId)) {
      $payload['context']['message_id'] = $replyMessageId;
    }

    if ($messageType == 'reply_button') {
      for ($i = 1; $i <= 3; $i++) {
        $buttonId = $stepDataToShow->{"button_{$i}_id"};
        $buttonMsg = $stepDataToShow->{"button_{$i}_msg"};
        if ($buttonId && $buttonMsg) {
          $buttons[] = [
            "type" => "reply",
            "reply" => [
              "id" => $buttonId,
              "title" => $buttonMsg,
            ]
          ];
        }
      }
      $this->sendReplyButtonMessage($message, $buttons, $payload);
    } else if ($messageType == 'msg_type') {
      $this->sendTextMessage($message, $payload);
    } else if ($messageType == 'list_type') {
      $listItems = [];
      for ($i = 1; $i <= 10; $i++) {
        $listItemId = $stepDataToShow->{"list_{$i}_id"};
        $listItemMsg = $stepDataToShow->{"list_{$i}_msg"};
        $listItemDesc = $stepDataToShow->{"list_{$i}_desc"};
        if ($listItemMsg && $listItemId) {
          $dataItem = [
            'id' => $listItemId,
            "title" => $listItemMsg,
          ];
          if ($listItemDesc) {
            $dataItem['description'] = $listItemDesc;
          }
          $listItems[] = $dataItem;
        }
      }
      $listMessageOpen = $stepDataToShow->list_open_message;
      $this->sendListMessage($message, $listMessageOpen, $listItems, $payload);
    } else if ($messageType == 'agent') {
      // Horario Lunes a viernes.
      $startHoursDateLV = (new DrupalDateTime($this->config('whatsapp_cloud_api_150.settings')->get('hoursAgentLVStart')));
      $startHourLV = $startHoursDateLV->format("H:i:s");
      $endHoursDateLV = (new DrupalDateTime($this->config('whatsapp_cloud_api_150.settings')->get('hoursAgentLVEnd')));
      $endHourLV = $endHoursDateLV->format("H:i:s");
      // Horario Sabado.
      $startHoursDateS = (new DrupalDateTime($this->config('whatsapp_cloud_api_150.settings')->get('hoursAgentSStart')));
      $startHourS = $startHoursDateS->format("H:i:s");
      $endHoursDateS = (new DrupalDateTime($this->config('whatsapp_cloud_api_150.settings')->get('hoursAgentSEnd')));
      $endHourS = $endHoursDateS->format("H:i:s");
      // Hora actual.
      $nowHour = (new DrupalDateTime())->format("H:i:s");
      // Obtener el día actual.
      $day = (new DrupalDateTime())->format("N");

      $inTime = FALSE;
      if ($day >= 1 && $day <= 5) {
        // Si es lunes a viernes, verificar el horario.
        if ($nowHour >= $startHourLV && $nowHour <= $endHourLV) {
          // Si está dentro del horario.
          $inTime = TRUE;
        } else {
          // Si está fuera del horario.
          $inTime = FALSE;
        }
      } else if ($day == 6) {
        // Si es sábado.
        if ($nowHour >= $startHourS && $nowHour <= $endHourS) {
          // Si está dentro del horario.
          $inTime = TRUE;
        } else {
          // Si está fuera del horario.
          $inTime = FALSE;
        }
      }
      if ($inTime) {
        $message = $this->messageStartAgent;
        $this->sendTextMessage($message ?? 'No hay un mensaje asignado para iniciar la conversación con el agente', $payload);
        $phoneUser = $payload['to'];
        $phoneAgent = $this->config('whatsapp_cloud_api_150.settings')->get('phoneAgent');
        $payload['to'] = $phoneAgent;
        if(!empty($payload['to'])){
          $this->sendTextMessage('Solicitud de Agente ' . $phoneUser , $payload);
        }
      }
      else {
        $message = $this->msgAgentOff;
        $this->sendTextMessage($message ?? 'No hay un mensaje asignado para avisar que no es horario para contactar a un agente.', $payload);
        // Necesitamos finalizar la conversación
        $this->updateStep($selecConversationStep->id, 'end', 'agent-off');
        return;
      }
    }
    $expectedResponses = explode(',', $stepDataToShow->response_type);
    $expectedResponses = array_flip($expectedResponses);
    if (isset($expectedResponses["none"])) {
      // No espera ningun mensaje de respuesta.
      // Buscamos el siguiente paso y lo enviamos.
      $nextStepData = $this->getStepData($stepDataToShow->to_step);
      if ($stepDataToShow->to_step != 'end') {
        // Hay otro paso, enviamos el mensaje y actualizamos el estado.
        $this->sendMessageToWhatsapp($toPhone, $stepDataToShow, $nextStepData, $selecConversationStep);
      }
      // Si es igual a 'end', es el paso final, solo actualicemos el
      // estado de la conversación.
      $this->updateStep($selecConversationStep->id, $nextStepData->identification, $stepDataToShow->identification);
    } else {
      // En caso de que espere otra respuesta, actualizamos el estado de la conversación.
      if ($stepDataToShow->identification == $currentStep->identification) {
        // Pero si es el mismo paso, no actualizamos el estado.
        return;
      }
      $this->updateStep($selecConversationStep->id, $stepDataToShow->identification, $currentStep->identification);
    }
  }

  /**
   * Funcion para enviar un mensaje de tipo lista.
   */
  public function sendListMessage($message, $buttonMessage, $items, $payload) {
    $payload['type'] = 'interactive';
    $payload['interactive'] = [
      'type' => 'list',
      'body' => [
        'text' => $message
      ],
      'action' => [
        'button' => $buttonMessage,
        'sections' => [
          [
            'rows' => $items
          ]
        ],
      ],
    ];
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer $this->bearerToken",
      ],
      'body' => json_encode($payload),
    ];
    $log = \Drupal::logger(__FUNCTION__);
    $url = $this->url;
    $resultLog = self::sendPostRequest($url, $options, $payload, $log);
    if (!isset($resultLog['error'])) {
      // Si no hay error, guardamos el mensaje.
      $this->saveHistoryMessage($payload['to'], 'chatbot-text', $message, $resultLog['result']->messages[0]->id, time());
    }
    return $resultLog;
  }

  /**
   * Send a text message to user using the whatsapp api.
   */
  public function sendTextMessage($message, $payload) {
    $payload['type'] = 'text';
    $payload['text'] = [
      'body' => $message
    ];
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer $this->bearerToken",
      ],
      'body' => json_encode($payload),
    ];

    $log = \Drupal::logger(__FUNCTION__);
    $url = $this->url;
    $resultLog = self::sendPostRequest($url, $options, $payload, $log);
    if (!isset($resultLog['error'])) {
      // Si no hay error, guardamos el mensaje.
      $this->saveHistoryMessage($payload['to'], 'chatbot-text', $message, $resultLog['result']->messages[0]->id, time());
    }
    return $resultLog;
  }

  /**
   * Send a reply button message to user using the whatsapp api.
   */
  public function sendReplyButtonMessage($message, $buttons, $payload) {
    $payload['type'] = 'interactive';
    $payload['interactive'] = [
      'type' => 'button',
      'body' => [
        'text' => $message
      ],
      "action" => [
        "buttons" => $buttons,
      ],
    ];
    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer $this->bearerToken",
      ],
      'body' => json_encode($payload),
    ];

    $log = \Drupal::logger(__FUNCTION__);
    $url = $this->url;
    $resultLog = self::sendPostRequest($url, $options, $payload, $log);
    if (!isset($resultLog['error'])) {
      // Si no hay error, guardamos el mensaje.
      $this->saveHistoryMessage($payload['to'], 'chatbot-reply_button', $message, $resultLog['result']->messages[0]->id, time());
    }
    return $resultLog;
  }

  /**
   * Send post request to whatsapp api.
   */
  public static function sendPostRequest($url, $options, $payload, $log) {
    try {
      $client = \Drupal::httpClient();
      $request = $client->request('POST', $url, $options);
      $requestResult = json_decode($request->getBody()->getContents());
      $resultLog = [
        'url' => $url,
        'options' => $options,
        'result' => $requestResult,
        'body' => $payload,
        'requestcode' => $request->getStatusCode(),
      ];
      if ($request->getStatusCode() === 200 || $request->getStatusCode() === 202) {
        $log->info("Se envio el mensaje: <pre>" . print_r($resultLog, TRUE) . "</pre> ");
      } else {
        $log->error("Ocurrio un error al enviar el mensaje: <pre>" . print_r($resultLog, TRUE) . "</pre> ");
      }
    } catch (\Exception $e) {
      $resultLog = [
        'url' => $url,
        'options' => $options,
        'body' => $payload,
        'error' => $e->getMessage()
      ];
      $log->error("Ocurrio un error al enviar la petición: <pre>" . print_r($resultLog, TRUE) . "</pre> ");
    }
    return $resultLog;
  }

  /**
   * Get the next step data from current step identification.
   */
  public function getNextStepFromStep($currentStepIdentification, $type) {
    $currentStep = $this->getStepData($currentStepIdentification);
    if ($currentStep) {
      if ($currentStepIdentification == 'start') {
        $nextStepIdentification = $currentStep->to_step;
      }
      $nextStep = $this->connection->select('whatsapp_cloud_api_150_messages', 'step')->fields('step')
        ->condition('identification', $nextStepIdentification)
        ->execute()->fetch();
    }
    return $nextStep ?? FALSE;
  }

  /**
   * Get the data of step identification.
   */
  public function getStepData($currentStepIdentification) {
    $currentStep = $this->connection->select('whatsapp_cloud_api_150_messages', 'step')->fields('step')
      ->condition('identification', $currentStepIdentification)
      ->execute()->fetch();

    return $currentStep;
  }

  public function saveFlow() {
    $stackData = \Drupal::request()->getContent();
    $fromEncode = mb_detect_encoding($stackData, ['ISO-8859-1', 'ISO-8859-5', 'UTF-8']);
    $unpacked = mb_convert_encoding($stackData, "UTF-8", $fromEncode);
    $stack = json_decode($unpacked);

    $exportData = $stack->exportData;
    $dataElements = $stack->dataElements;

    $now = date('Y-m-d H:i:s');
    $drawFlow = $exportData->drawflow->Home->data;
    foreach ($drawFlow as $dataflow) {
      $stepName = $dataflow->name;
      if ($stepName == 'start') {
        // Es el paso que inicia el flujo, nos dice cuál debe ser el primer mensaje.
        $firstConnectionNodeId = $dataflow->outputs->output_1->connections[0]->node;
        if (!isset($drawFlow->{$firstConnectionNodeId})) {
          continue;
        }
        $firstConnectionData = $drawFlow->{$firstConnectionNodeId};
        $firstConnectionStepName = $firstConnectionData->name;
        if (!isset($dataElements->{$firstConnectionStepName})) {
          // Por alguna razón no esta el elemento conectado al inicio.
          continue;
        }
        $insertData = $updateData = [];
        $insertData['type'] = 'start';
        $insertData['identification'] = 'start';
        $insertData['to_step'] = $updateData['to_step'] = $firstConnectionStepName;
        $insertData['response_type'] = 'none';
        $insertData['created_at'] = $now;
        $insertData['updated_at'] = $updateData['updated_at'] = $now;
        $this->connection->merge('whatsapp_cloud_api_150_messages')
          ->insertFields($insertData)
          ->updateFields($updateData)
          ->key('type', 'start')
          ->execute();
      } else if ($stepName == 'end') {
        $insertData = $updateData = [];
        $insertData['type'] = 'end';
        $insertData['identification'] = 'end';
        $insertData['response_type'] = 'none';
        $insertData['created_at'] = $now;
        $insertData['updated_at'] = $updateData['updated_at'] = $now;
        $this->connection->merge('whatsapp_cloud_api_150_messages')
          ->insertFields($insertData)
          ->updateFields($updateData)
          ->key('type', 'end')
          ->execute();
      } else if ($stepName == 'agent') {
        $insertData = $updateData = [];
        $insertData['type'] = 'agent';
        $insertData['identification'] = 'agent';
        $insertData['response_type'] = 'agent';
        $insertData['created_at'] = $now;
        $insertData['updated_at'] = $updateData['updated_at'] = $now;
        $this->connection->merge('whatsapp_cloud_api_150_messages')
          ->insertFields($insertData)
          ->updateFields($updateData)
          ->key('type', 'agent')
          ->execute();
      } else {
        if (!isset($dataElements->{$stepName})) {
          // Por alguna razón no esta en los elementos.
          continue;
        }
        $stepElement = $dataElements->{$stepName};

        $type = $stepElement->type;
        if ($type == 'reply_button') {
          $connections  = $dataflow->outputs;

          $insertReplyBtnData = [];
          $insertReplyBtnData['type'] = 'reply_button';
          $insertReplyBtnData['identification'] = $stepName;
          $insertReplyBtnData['msg'] = $stepElement->message;

          // Hacer que los botones esten vacios
          for ($i = 1; $i <= 3; $i++) {
            $insertReplyBtnData["button_$i" . "_id"] = '';
            $insertReplyBtnData["button_$i" . "_msg"] = '';
            $insertReplyBtnData["button_$i" . "_to_step"] = '';
          }

          $count = 0;
          foreach ($connections as $toStepFromBtn) {
            $toNodeStepNameFromBtn = '';
            if (isset($toStepFromBtn->connections[0])) {
              $toNodeIdFromBtn = $toStepFromBtn->connections[0]->node;
              $toNodeDataFromBtn = $drawFlow->{$toNodeIdFromBtn};
              $toNodeStepNameFromBtn = $toNodeDataFromBtn->data->identification;
            }
            if (isset($stepElement->btns[$count])) {
              $stepElement->btns[$count]->btn_toStep = $toNodeStepNameFromBtn ?? '';
              $countIndex = $count + 1;
              $insertReplyBtnData["button_$countIndex" . "_id"] = $stepElement->btns[$count]->btn_id;
              $insertReplyBtnData["button_$countIndex" . "_msg"] = $stepElement->btns[$count]->btn_msg;
              $insertReplyBtnData["button_$countIndex" . "_to_step"] = $stepElement->btns[$count]->btn_toStep ?? '';
            }
            $count++;
          }

          $insertReplyBtnData['response_type'] = 'reply_button';
          $insertReplyBtnData['created_at'] = $now;
          $insertReplyBtnData['updated_at'] = $now;

          $updateReplyBtnData = $insertReplyBtnData;
          unset($updateReplyBtnData['created_at']);
          $this->connection->merge('whatsapp_cloud_api_150_messages')
            ->insertFields($insertReplyBtnData)
            ->updateFields($updateReplyBtnData)
            ->key('identification', $stepName)
            ->execute();
        } else if ($type == 'msg_type') {
          $toNodeId  = $dataflow->outputs->output_1->connections[0]->node ?? false;
          $toNodeStepName = '';
          if ($toNodeId) {
            $toNodeData = $drawFlow->{$toNodeId} ?? false;
            $toNodeStepName = $toNodeData->data->identification ?? '';
          }

          $insertMsgType = [];
          $insertMsgType['type'] = 'msg_type';
          $insertMsgType['identification'] = $stepName;
          $insertMsgType['msg'] = $stepElement->message;

          $insertMsgType['response_type'] = $stepElement->expectedResponse;
          $insertMsgType['to_step'] = $toNodeStepName;
          $insertMsgType['check_msg_regex'] = $stepElement->regex;
          $insertMsgType['check_unique_data'] = $stepElement->mustUnique;
          $insertMsgType['check_unique_data_table'] = $stepElement->uniqueTable;
          $insertMsgType['check_unique_data_column'] = $stepElement->uniqueColumn;

          $insertMsgType['created_at'] = $now;
          $insertMsgType['updated_at'] = $now;
          $updateMsgType = $insertMsgType;
          unset($updateMsgType['created_at']);

          $this->connection->merge('whatsapp_cloud_api_150_messages')
            ->insertFields($insertMsgType)
            ->updateFields($updateMsgType)
            ->key('identification', $stepName)
            ->execute();
        } else if ($type == 'list_type') {
          $listConnections  = $dataflow->outputs;

          $insertListType = [];
          $insertListType['type'] = 'list_type';
          $insertListType['identification'] = $stepName;
          $insertListType['msg'] = $stepElement->message;

          // Hacer que todas las opciones de la lista esten vacias.
          for ($i = 1; $i <= 10; $i++) {
            $insertListType["list_$i" . "_id"] = '';
            $insertListType["list_$i" . "_msg"] = '';
            $insertListType["list_$i" . "_desc"] = '';
          }

          $count = 0;
          foreach ($listConnections as $toStepFromList) {
            $toNodeStepNameFromList = '';
            if (isset($toStepFromList->connections[0])) {
              $toNodeIdFromList = $toStepFromList->connections[0]->node;
              $toNodeDataFromList = $drawFlow->{$toNodeIdFromList};
              $toNodeStepNameFromList = $toNodeDataFromList->data->identification;
            }
            if (isset($stepElement->options[$count])) {
              $stepElement->options[$count]->list_toStep = $toNodeStepNameFromList ?? '';
              $countIndex = $count + 1;
              $insertListType["list_$countIndex" . "_id"] = $stepElement->options[$count]->id;
              $insertListType["list_$countIndex" . "_msg"] = $stepElement->options[$count]->msg;
              $insertListType["list_$countIndex" . "_desc"] = $stepElement->options[$count]->desc ?? '';
              $insertListType["list_$countIndex" . "_to_step"] = $stepElement->options[$count]->list_toStep ?? '';
            }
            $count++;
          }

          $insertListType['list_open_message'] = $stepElement->lostOpenButton;
          $insertListType['response_type'] = 'list_reply';
          $insertListType['created_at'] = $now;
          $insertListType['updated_at'] = $now;
          $updateListType = $insertListType;
          unset($updateListType['created_at']);

          $this->connection->merge('whatsapp_cloud_api_150_messages')
            ->insertFields($insertListType)
            ->updateFields($updateListType)
            ->key('identification', $stepName)
            ->execute();
        }
      }
    }
    $configEditable = $this->configFactory->getEditable('whatsapp_cloud_api_150.settings');
    $configEditable->set('to_import_drawflow', serialize($exportData))->save();
    $configEditable->set('to_get_elements', serialize($dataElements))->save();
    return new JsonResponse(['error' => FALSE], 200);
  }

  /**
   * Send a message to user Advises.
   */
  public function sendMessageToWhatsappAdvises($toPhone, $message) {
    $payload = [
      'messaging_product' => 'whatsapp',
      'to' => $toPhone,
      'type' =>  'text',
      'text' => [
        'body' => $message
      ],
    ];

    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => "Bearer $this->bearerToken",
      ],
      'body' => json_encode($payload),
    ];
    $log = \Drupal::logger(__FUNCTION__);
    $url = $this->url;
    $resultLog = self::sendPostRequest($url, $options, $payload, $log);
    if (!isset($resultLog['error'])) {
      // Si no hay error, guardamos el mensaje.
      $log->warning('Guardando mensaje en el historial texto. <pre>' . print_r([$payload['to'], 'chatbot', $message, $resultLog['result']->messages[0]->id, time()], TRUE) . '</pre>');
      $this->saveHistoryMessage($payload['to'], 'chatasesor-text', $message, $resultLog['result']->messages[0]->id, time());
    }
    return $resultLog;
  }
}
