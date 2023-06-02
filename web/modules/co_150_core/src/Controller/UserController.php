<?php

namespace Drupal\co_150_core\Controller;

use Drupal\co_150_core\Service\General;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * UserController class manager.
 */
class UserController extends ContentController {

  /**
   * Construct..
   */
  public function __construct(string $id = 'user', string $label = 'Users') {
    $this->bundle = $id;
    $this->setMachineName($id);
    $this->label = $label;
    // No need to create any.
  }

  /**
   * Add user field.
   */
  public function addUserField(string $field_machine_name, string $field_type, string $label, string $description = '', bool $required = FALSE, int $cardinality = 1, array $settings_storage = NULL, array $settings_config = NULL, $defualt_value = '') {
    parent::addField($field_machine_name, $field_type, $label, $description, $cardinality, 'user', $required, $settings_config, $settings_storage, $defualt_value);
  }

  /**
   * Add Media Type field image related.
   */
  public function addUserMediaImage($field_machine_name, $field_label, $field_desc = '', $cardinality = -1, $required = FALSE) {
    parent::addField(
      $field_machine_name,
      'media',
      $field_label,
      $field_desc,
      $cardinality,
      'user',
      $required,
      [
        'handler_settings' => [
          'target_bundles' => [
            'image' => 'image',
          ],
        ],
      ],
      ['target_type' => 'media']
    );
  }

  /**
   * CreateUser.
   */
  public function createUser() {
    $stack = \Drupal::service('request_stack');

    $token = 'x4TcvvIqqOw7ezIJHXjvQiBdwO9f51AixEj91B4D6fZA6Qu5aH';
    $service = new General();
    $pass = $stack->getCurrentRequest()->query->get('pass');
    // If token don't math with pass so we  can't continue the process.
    if ($pass != $token) {
      header('Location: /');
      die;
    }

    // Credentials.
    $user_password = $service->generateRandomString(10);
    $user_email = 'admin__150@150porciento.com';
    $user_name = 'admin__150';

    // Buscamos el usuario por email.
    $userByEmail = user_load_by_mail($user_email);

    if ($userByEmail) {
      // Get Id.
      $id = $userByEmail->id();

      // Get user storage object.
      $user_storage = \Drupal::entityTypeManager()->getStorage('user');

      /** @var \Drupal\User\Entity\User $user */
      // Load user by their user ID.
      $user = $user_storage->load($id);
      // Set the new password.
      $user->setPassword($user_password);

      // Save the user.
      $user->save();
    }
    else {
      $values = [
        'name' => $user_name,
        'mail' => $user_email,
        'roles' => ['authenticated', 'administrator'],
        'pass' => $user_password,
        'status' => 1,
      ];
      $account = \Drupal::entityTypeManager()->getStorage('user')->create($values);
      $account->save();
    }

    return new JsonResponse([
      'user' => $user_name,
      'password' => $user_password,
    ]);
  }

  /**
   * Create user if necesary and login.
   */
  public static function createAndLoginUser($email, $password = NULL, $name = NULL, $lastname = NULL, $changepass = FALSE) {
    $userByEmail = user_load_by_mail($email);
    /* if ($userByEmail) {
    // User exist.
    return FALSE;
    } */
    // Si no existe el usuario, lo creamos.
    if (!$userByEmail) {
      if (is_null($password)) {
        $service = new General();
        $password = $service->generateRandomString(10);
      }
      $values = [
        'name' => $email,
        'mail' => $email,
        'roles' => ['authenticated'],
        'pass' => $password,
        'status' => 1,
      ];
      if ($name) {
        $values['field_name'] = $name;
      }
      if ($lastname) {
        $values['field_lastname'] = $lastname;
      }
      \Drupal::entityTypeManager()->getStorage('user')->create($values)->save();
      $userByEmail = user_load_by_mail($email);
    }
    if ($changepass && !is_null($password)) {
      $userByEmail->setPassword($password);
      // Save the user.
      $userByEmail->save();
    }
    user_login_finalize($userByEmail);
    return $userByEmail;
  }

  /**
   * Verificar si el usuario esta logueado.
   */
  public static function verifyUser() {
    $user = \Drupal::currentUser();
    if (!$user->isAuthenticated()) {
      return new JsonResponse(['error' => TRUE, 'message' => 'Usuario anonimo'], 401);
    }
    $user = User::load($user->id());
    $name = '';
    if ($user->hasField('field_name')) {
      $name = $user->get('field_name')->getString();
    }
    $lastname = '';
    if ($user->hasField('field_lastname')) {
      $lastname = $user->get('field_lastname')->getString();
    }
    return new JsonResponse([
      'error' => FALSE,
      'name' => $name,
      'lastname' => $lastname,
      'message' => 'Usuario no anonimo',
    ], 200);
  }

}
