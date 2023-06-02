<?php

namespace Drupal\co_150_core\Service;

/**
 * EndPointMailer class manager.
 */
class EndPointMailer {

  /**
   * Create obj.
   */
  public function __construct(
    $clientID,
    $clientSecret,
    $scope = NULL,
    $urlSend = "https://mc78m9n2j8v0ylqf2-1ymhlncsq4.rest.marketingcloudapis.com/messaging/v1/messageDefinitionSends/e58d2bd8-2b7e-ec11-b83a-48df37d68329/send",
    $urlAuth = "https://mc78m9n2j8v0ylqf2-1ymhlncsq4.auth.marketingcloudapis.com/v2/token"
  ) {
    $this->token        = NULL;
    $this->urlAuth      = $urlAuth;
    $this->urlSend      = $urlSend;
    $this->scope        = $scope;
    $this->clientID     = $clientID;
    $this->clientSecret = $clientSecret;
  }

  /**
   * SendEmail.
   *
   * Send Email By marketing, you can use the next code.
   *
   *    $email = 'XXX.YYY@150porciento.com';
   *    $data = [
   *        "name_amigo" =>  $name,
   *        "Rappi_Code" =>  $code
   *    ];
   *    $class->sendEmail($email, $data);
   */
  public function sendEmail($to_email, $subscriber_attributes) {
    if ($this->token == NULL) {
      $this->token = $this->getToken();
      if ($this->token->token == NULL) {
        return FALSE;
      }
    }
    $result = [];

    $subscriber_attributes_new = [];
    $subscriber_attributes_new['EmailAddress'] = $to_email;
    $subscriber_attributes_new += $subscriber_attributes;
    $body = [
      'To' => [
        'Address' => $to_email,
        'SubscriberKey' => $to_email,
        'ContactAttributes' => [
          'SubscriberAttributes' => $subscriber_attributes_new,
        ],
      ],
    ];

    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
        'Authorization' => $this->token->token_type . ' ' . $this->token->token,
      ],
      'body' => json_encode($body),
    ];

    try {
      $client = \Drupal::httpClient();
      $request = $client->request('POST', $this->urlSend, $options);

      $result = json_decode($request->getBody()->getContents());
      $result = print_r([
        'result' => $result,
        'body' => $body,
        'options' => $options,
        'requestcode' => $request->getStatusCode(),
      ], 1);
      if ($request->getStatusCode() === 200 || $request->getStatusCode() === 202) {
        \Drupal::logger('EndPointMailer sendEmail')->info("Se envió el email con éxito , mensaje: <pre>" . $result . "</pre> ");
      }
      else {
        \Drupal::logger('EndPointMailer sendEmail')->info("Se envió el email con (Sin respuesta 200/202), mensaje: <pre>" . $result . "</pre> ");
      }
    }
    catch (\Exception $e) {
      $result = print_r([
        'message' => $e->getMessage(),
        'body' => $body,
        'options' => $options,
      ], TRUE);
      \Drupal::logger('EndPointMailer sendEmail')->error("No se pudo conectar con el servicio, mensaje: <pre>" . $result . "</pre> ");
    }
    return $result;
  }

  /**
   * GetToken.
   */
  public function getToken() {
    $response = new \stdClass();
    $response->token = NULL;
    $response->token_type = NULL;
    $response->message = NULL;

    $body = [
      'grant_type'    => 'client_credentials',
      "client_id"     => $this->clientID,
      "client_secret" => $this->clientSecret,
    ];

    $options = [
      'headers' => [
        'Content-Type' => 'application/json',
      ],
      'body' => json_encode($body),
    ];

    try {
      $client = \Drupal::httpClient();
      $request = $client->request('POST', $this->urlAuth, $options);
      $result = json_decode($request->getBody()->getContents());
      \Drupal::logger('EndPointMailer getToken')->info("Datos obtenidos: <pre>" . print_r([
        'result' => $result,
        'body' => $body,
        'options' => $options,
      ], 1) . "</pre> ");
      if ($request->getStatusCode() === 200) {
        $response->token      = $result->access_token;
        $response->token_type = $result->token_type ?? 'Bearer';
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('EndPointMailer getToken')->error("Error al intentar obtener el token: <pre>" . print_r([
        'message' => $e->getMessage(),
        'options' => $options,
      ], TRUE) . "</pre> ");
      $response->message = $e->getMessage();
    }
    return $response;
  }

}
