<?php

namespace Drupal\whatsapp_cloud_api_150\Controller;

use Drupal\co_150_core\Service\SendSMS;
use Symfony\Component\HttpFoundation\Request;
use Drupal\co_150_core\Service\EndPointMailer;
use Drupal\co_150_core\Service\General;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;


use Drupal\whatsapp_cloud_api_150\Controller\WhatsappCloudApi150Controller;

/**
 * Returns responses for DO Premios Soberano routes.
 */
class AdvisesWhatsappCloudApi150Controller extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The database connection.
   *
   * @var Drupal\Core\Session\AccountInterface
   */

  protected $account;

  /**
   * The database connection.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */

  protected $requestStack;

  /**
   * The database connection.
   *
   * @var Drupal\Core\File\FileSystem
   */

  protected $fileSystem;

  /**
   * The database connection.
   *
   * @var Drupal\Core\Logger\LoggerChannelFactoryInterface
   */

  protected $loggerFactory;

  /**
   * The controller constructor.
   */
  public function __construct(Connection $connection, ConfigFactoryInterface $config_factory, RequestStack $requestStack, AccountInterface $account, FileSystem $fileSystem, LoggerChannelFactoryInterface $loggerFactory) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
    $this->requestStack = $requestStack;
    $this->fileSystem = $fileSystem;
    $this->loggerFactory = $loggerFactory;
    $this->account = $account;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('file_system'),
      $container->get('logger.factory')
    );
  }

  /**
   * Usuarios que requieren asesor.
   */
  public function usersForAdvises() {

    $stateAdvises = (string) $this->configFactory->get('whatsapp_cloud_api_150.settings')->get('stateAdvises') ?? '';
    $type = $this->requestStack->getCurrentRequest()->query->get('type');
    $type = ($type == 'next') ? '>' : (($type == 'old') ? '<' : NULL);
    $id = $this->requestStack->getCurrentRequest()->query->get('id');
    $query = $this->connection->select('whatsapp_cloud_api_150_conversations', 'conv');
    $query->addExpression('id', 'id');
    $query->addExpression('conversation_id', 'phone');
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(conversation_last_timestamp), '%Y-%m-%d %H:%i:%s')", 'date');
    $query->condition('conversation_step', $stateAdvises);
    if (!empty($id) && !empty($type)) {
      $query->condition('id', $id, $type);
    }
    $selecConversationStep = $query->execute()->fetchAll();
    $selecConversationStep = array_reverse($selecConversationStep);
    return new JsonResponse([
      'status' => 'SUCCESS',
      'data' => $selecConversationStep,
    ], 200);
  }

  /**
   * Data message user.
   */
  public function dataMessageUserPhone() {
    $phone = $this->requestStack->getCurrentRequest()->query->get('phone');
    $id = $this->requestStack->getCurrentRequest()->query->get('id');
    $type = $this->requestStack->getCurrentRequest()->query->get('type');
    $type = ($type == 'next') ? '>' : (($type == 'old') ? '<' : NULL);
    $query = $this->connection->select('whatsapp_cloud_api_150_history', 'conv');
    $query->fields('conv', ['id', 'waid', 'message', 'type']);
    $query->addExpression("DATE_FORMAT(FROM_UNIXTIME(message_timestamp), '%Y-%m-%d %H:%i:%s')", 'date');
    $query->addExpression("IF(type REGEXP 'chatbot|chatasesor', 'chatbot', 'user')", 'user_message');
    $query->condition('waid', $phone);
    if (!empty($id) && !empty($type)) {
      $query->condition('id', $id, $type);
    }
    $query->range(0, 20);
    $query->orderBy('id', 'DESC');
    $selecDataConversation = $query->execute()->fetchAll();
    $selecDataConversation = array_reverse($selecDataConversation);

    return new JsonResponse([
      'status' => 'SUCCESS',
      'data' => $selecDataConversation,
    ], 200);
  }

  /**
   * Envio de mensaje desde Asesor.
   */
  public function sendMessageToWhatsapp(Request $request) {
    $data = json_decode($request->getContent(), TRUE);

    $stateAdvises = (string) $this->configFactory->get('whatsapp_cloud_api_150.settings')->get('stateAdvises') ?? 'advise';
    $query = $this->connection->select('whatsapp_cloud_api_150_conversations', 'conv')->fields('conv', []);
    $query->condition('conversation_step', $stateAdvises);
    $query->condition('conversation_id', $data['phone']);
    $selecConversationStep = $query->execute()->fetch();
    if ($selecConversationStep && !empty($data['message'])) {
      $classSendWhatsApp = new WhatsappCloudApi150Controller($this->requestStack, $this->configFactory, $this->account, $this->connection, $this->fileSystem, $this->loggerFactory);
      $dataSend = $classSendWhatsApp->sendMessageToWhatsappAdvises($data['phone'], $data['message']);
      if (!isset($dataSend['error'])) {
        $id = $this->connection->select('whatsapp_cloud_api_150_history', 'data')->fields('data', ['id'])
          ->condition('waid', $data['phone'])
          ->range(0, 1)
          ->orderBy('id', 'DESC')
          ->execute()->fetchField();
      }
      else {
        return new JsonResponse([
          'status' => 'ERROR_META',
        ], 200);
      }
    }
    else {
      return new JsonResponse([
        'status' => 'ERROR_USER',
      ], 200);
    }

    return new JsonResponse([
      'status' => 'SUCCESS',
      'data_id' => $id,
    ], 200);
  }

   /**
   * Cerrar conversación por parte del asesor.
   */
  public function closedConcversationAdvise() {
    $phone = $this->requestStack->getCurrentRequest()->query->get('phone');

    $stateAdvises = (string) $this->configFactory->get('whatsapp_cloud_api_150.settings')->get('stateAdvises') ?? 'advise';
    $messageClosed = (string) $this->configFactory->get('whatsapp_cloud_api_150.settings')->get('msgAgentEnd') ?? '';

    $stateClosedAdvises = (string) $this->configFactory->get('whatsapp_cloud_api_150.settings')->get('closedstateAdvises') ?? 'closed_advise';
    $query = $this->connection->select('whatsapp_cloud_api_150_conversations', 'conv')->fields('conv', []);
    $query->condition('conversation_step', $stateAdvises);
    $query->condition('conversation_id', $phone);
    $selecConversationStep = $query->execute()->fetch();
    if ($selecConversationStep) {
      $classSendWhatsApp = new WhatsappCloudApi150Controller($this->requestStack, $this->configFactory, $this->account, $this->connection, $this->fileSystem, $this->loggerFactory);
      $classSendWhatsApp->updateStep($selecConversationStep->id, $stateClosedAdvises, $stateAdvises);
      $classSendWhatsApp->sendMessageToWhatsappAdvises($phone, $messageClosed);
    }
    else {
      return new JsonResponse([
        'status' => 'ERROR_USER',
      ], 200);
    }

    return new JsonResponse([
      'status' => 'SUCCESS',
    ], 200);
  }

   /**
   * renderizar twig conversación por parte del asesor.
   */
     public function pageAdvise()
     {
         $page = [
             '#theme' => 'conversationAgent',
             '#data' => [
             ],
             '#cache' => [
                 'max-age' => 0
             ]
         ];
         return $page;
     }

}
