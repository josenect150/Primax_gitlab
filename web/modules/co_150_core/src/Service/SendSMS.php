<?php

namespace Drupal\co_150_core\Service;

use Drupal\Core\Controller\ControllerBase;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

/**
 * Returns responses for Modulo 150 Core routes.
 */
class SendSMS extends ControllerBase {

  /**
   * Send SMS.
   */
  public static function send(array $numbers, array $msgReplaces = [], $msg = NULL) {
    $config = \Drupal::service('config.factory')->getEditable('co_150_core.settings');

    $form_data = [
      'numbers' => $numbers,
      'prefix' => $config->get('sms_prefix'),
      "credentials" => [
        "user" => $config->get('sms_user'),
        "password" => $config->get('sms_password'),
      ],
      "message" => self::replaceMessage($msg ?? $config->get('sms_message'), $msgReplaces),
    ];

    $curl = curl_init();
    $curl_opts = [
      CURLOPT_URL => $config->get('sms_url'),
      CURLOPT_POST => TRUE,
      CURLOPT_RETURNTRANSFER => TRUE,
      CURLOPT_HTTPHEADER => ['Content-Type:application/json'],
      CURLOPT_POSTFIELDS => json_encode($form_data),
    ];

    curl_setopt_array($curl, $curl_opts);

    $response = @curl_exec($curl);
    $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    curl_close($curl);

    unset($form_data['credentials']);
    $logger = [
      'responseCode' => $response_code,
      'response' => $response,
      'url' => $curl_opts[CURLOPT_URL],
      'formData' => $form_data,
    ];
    \Drupal::logger('SMS 150% Service')->info('<pre><code>' . print_r($logger, TRUE) . '</code></pre>');

    return $logger;
  }

  /**
   * Replace message data.
   */
  public static function replaceMessage($message, $replaces) {
    return str_replace(array_keys($replaces), array_values($replaces), $message);
  }

  /**
   * Send a message from custom twilio account.
   *
   * If prefiz is empty, $toNumber must contain the prefix.
   */
  public static function sendCustomAccount($sid, $token, $fromNumber, $message, $toNumber, $prefix = '') {
    $twilioSms = new Client($sid, $token);
    $numberToSend = "$prefix$toNumber";
    try {
      $response = $twilioSms->messages->create(
        // The number you'd like to send the message to.
        $numberToSend,
        [
            // A Twilio phone number you purchased at twilio.com/console.
          'from' => $fromNumber,
            // The body of the text message you'd like to send.
          'body' => $message,
        ]
      );
      $data = [
        'error' => FALSE,
        'fromNumber' => $fromNumber,
        'toNumber' => $numberToSend,
        'body' => $message,
        'response' => $response,
      ];
      \Drupal::logger('SMS Custom 150% Service')->info(
        'Message Send <pre>@data</pre>',
        [
          '@data' => print_r($data, TRUE),
        ]
      );
      unset($data['response']);
      return $data;
    }
    catch (TwilioException $e) {
      $data = [
        [
          'error' => TRUE,
          'msgError' => $e->getMessage(),
          'fromNumber' => $fromNumber,
          'toNumber' => $numberToSend,
          'body' => $message,
        ],
      ];
      \Drupal::logger('SMS Custom 150% Service')->error(
        'Error sending message: <pre>@data</pre>',
        [
          '@data' => print_r($data, TRUE),
        ]
      );
      return $data;

    }
  }

}
