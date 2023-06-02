<?php

namespace Drupal\co_150_core\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\co_150_core\Core\Modal;

/**
 * Provides route responses for the Example module.
 */
class TableCustom extends ControllerBase {

  /**
   * Index.
   */
  public function index($table) {
    try {
      $stack = \Drupal::service('request_stack');
      $perPage = $stack->getCurrentRequest()->query->get('perPage') ?? '20';

      $this->table = $table;
      $con = Database::getConnection();
      $fieldsDB = $con->query("DESCRIBE `{$this->table}`")->fetchAll();
      $fields = [];
      foreach ($fieldsDB as $key => $value) {
        $fields[] = $value->Field;
      }
      $fields[] = 'options';

      $idPrincipal = array_search('id', $fields) === FALSE ? $fields[0] : 'id';

      $query = \Drupal::database()->select($this->table, 'u');
      $query->fields('u')->orderBy($idPrincipal, 'DESC');
      $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($perPage);
      $results = $pager->execute()->fetchAll();
      $rows = [];

      // Fields Database.
      foreach ($results as $result) {
        $row = [];
        foreach ($fields as $key => $value) {
          if ($value == 'options') {
            // .json_encode(((array)$result)).
            $row[] = new FormattableMarkup("<button onclick='editUserData(" . json_encode(((array) $result)) . ",`" . $this->table . "`)'>Editar</button><button onclick='deleteUserData(" . ((array) $result)["id"] . ",`" . $this->table . "`)'>Eliminar</button>", []);
            continue;
          }
          if ($value == 'created_at') {
            if (is_numeric(((array) $result)[$value])) {
              $row[] = date('r', ((array) $result)[$value]);
              continue;
            }
          }
          $row[] = ((array) $result)[$value];
        }
        $rows[] = $row;
      }

      foreach ($fields as $key => $value) {
        $header[$value] = $value;
      }

      $build['button_delete_js'] = [
        '#type' => 'markup',
        '#markup' => $this->scriptDeleteUser(),
      ];

      $build['button_excel'] = [
        '#type' => 'markup',
        '#markup' => '<p><a href="/admin/table-custom/' . $this->table . '/export" target="_blank" class="button button-action button--primary" >Descargar</a></p>',
      ];

      $build['table'] = [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => t('No content has been found.'),
      ];

      $build['pager'] = [
        '#type' => 'pager',
      ];

      return $build;
      // code...
    }
    catch (\Throwable $th) {
      return new JsonResponse('Tabla no encontrada');
    }
  }

  /**
   * ScriptDeleteUser.
   */
  public function scriptDeleteUser() {
    $modal = new Modal();

    return new FormattableMarkup($modal->modal('Editar Elemento', '<div id="modal-html"></div>') . '
      <script>
        window.deleteUserData = (id, table) => {
            const answer = window.confirm("Desea eliminar el registro?");
            if (answer) {
                fetch(`/admin/table-custom/${table}/delete/${id}`)
                .then(response => response.json())
                .then(data => location.reload());
            }
        }

        window.updateUserData = (id, table) => {
            const answer = window.confirm("Desea eliminar el registro?");
            if (answer) {
                fetch(`/admin/table-custom/${table}/delete/${id}`)
                .then(response => response.json())
                .then(data => location.reload());
            }
        }

        window.editUserData = (data, table) => {
          let parent = document.getElementById("modal-html");
          parent.innerHTML = "";
          for (const property in data) {
            createInput(property, data[property])
          }
          let button = document.createElement("button");
          button.innerHTML = "Actualizar";
          button.setAttribute("onclick", "updateUserData(`"+table+"`)");
          parent.appendChild(button);
          modal.style.display = "block";
      }

      function createInput(name, value){
          input = document.createElement("input");
          input.setAttribute("type", "text");
          input.setAttribute("name", name);
          input.setAttribute("value", value);
          if(name == "id"){
            input.setAttribute("disabled", "disabled");
          }
          label = document.createElement("label");
          label.setAttribute("for", name);
          label.innerText = name.toUpperCase();
          let parent = document.getElementById("modal-html");
          parent.appendChild(label);
          parent.appendChild(input);
          parent.appendChild(document.createElement("br"));
      }
      window.updateUserData = async (table) => {
        let data = {}
        const inputs = document.querySelectorAll("#modal-html input")
        inputs.forEach( item => data[item.name] = item.value)
        const answer = window.confirm("Desea editar el registro?");
        if (answer) {
          const rawResponse = await fetch(`/admin/table-custom/${table}/edit/${data["id"]}`, {
            method: "POST",
            headers: { "Accept": "application/json", "Content-Type": "application/json"},
            body: JSON.stringify(data)
          });
          const content = await rawResponse.json();
          console.log(content);
          location.reload()
        }
      }
      </script>
      ', []);
  }

  /**
   * Delete.
   */
  public function delete($table, $id) {
    try {
      $this->table = $table;
      $query = \Drupal::database()->delete($this->table);
      $query->condition('id', $id);
      $query->execute();
      return new JsonResponse('Eliminado');
    }
    catch (\Throwable $th) {
      return new JsonResponse('Registro no encontrado');
    }
  }

  /**
   * Edit.
   */
  public function edit($table, $id) {
    try {
      $content = file_get_contents("php://input");
      $data = json_decode($content, TRUE);
      $this->table = $table;
      // Update Register.
      if ($data['id'] == $id) {
        \Drupal::database()->update($this->table)->fields($data)->condition('id', $id)->execute();
      }
      else {
        return new JsonResponse('Registro no Actualizado');
      }
      return new JsonResponse('Actualizado');
    }
    catch (\Throwable $th) {
      return new JsonResponse('Registro no encontrado');
    }
  }

}
