#Co 150 Core

This is a module with a lot of functions.


## How to add Content Type and fields.



```php
  use Drupal\co_150_core\Controller\ContentController;

  /**
   * @param string $machine_name
   * @param string $content_title
   * @param string $content_description
   * @param string $content_type (default: node)
   */
  $option_img_paragraph_type =  new ContentController('par_opc_img', 'Opción de respuesta (Imagen)', 'Opcion de respuesta para preguntas tipo imagen', 'node');
/**
   * ADD A FIELD TO A CONTENT TYPE
   * @param string          $field_name                     machine name of the field
   * @param string|array    $field_type                     type of field ()
   * @param string          $label                          Human readable name of the field
   * @param string          $description                    Description of the field
   * @param integer         $cardinality                    Unlimited (-1) or Limited (int > 0)
   * @param string          $entity_type                    The type of the entity (paragraph, node, etc..)
   * @param bool            $required                       Is required or not
   * @param array           $settings_config                Configuration field config settings
   * @param array           $settings_storage               Configuration field storage settings
   * @param string          $defualt_value                  Default value
   *
   * @return void
   */
  $option_img_paragraph_type->addField('opt_img', 'image', 'Imagen de respuesta');
```
