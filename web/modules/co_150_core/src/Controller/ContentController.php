<?php

namespace Drupal\co_150_core\Controller;

use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\node\Entity\Node;
use Drupal\system\Entity\Menu;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\media\Entity\Media;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\webform\Entity\Webform;
use Drupal\Core\Serialization\Yaml;

/**
 * ContentController class manager.
 */
class ContentController {

  /**
   * Init var.
   *
   * @var mixed
   */
  private $machineName;

  /**
   * Init var.
   *
   * @var mixed
   */
  private $contentTitle;

  /**
   * Init var.
   *
   * @var mixed
   */
  private $contentDescription;

  /**
   * Create object.
   */
  public function __construct($machine_name, $content_title, $content_description = '', $content_type = "node") {
    $this->machineName        = $machine_name;
    $this->contentTitle       = $content_title;
    $this->contentDescription = $content_description;
    $this->createContent($content_type);
  }

  /**
   * GetMachineName.
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * SetMachineName.
   */
  public function setMachineName($machine_name) {
    $this->machineName = $machine_name;
  }

  /**
   * Crear el contenido.
   */
  public function createContent($content_type = "node") {
    switch ($content_type) {
      case 'node':
        $this->createNodeContentType($this->machineName, $this->contentTitle, $this->contentDescription);
        break;

      case 'paragraph':
        $this->createParagraphType($this->machineName, $this->contentTitle, $this->contentDescription);
        break;

      case 'block_content':
        $this->createBlockContentType($this->machineName, $this->contentTitle, $this->contentDescription);
        break;

      case 'webform':
        $this->createWebformType($this->machineName, $this->contentTitle, $this->contentDescription);
        break;

      default:
        // Throw new Exception("Se necesita el tipo de contenido como
        // parametro", 1);.
        break;
    }
  }

  /**
   * Create new content type (Node Type)
   */
  public function createNodeContentType($machine_name, $content_name, $description) {
    $nodeType = NodeType::load($machine_name);
    if (!$nodeType) {
      $nodeType = NodeType::create([
        'type' => $machine_name,
        'name' => $content_name,
        'description' => $description,
      ])->save();
    }
  }

  /**
   * Create new Block type (Node Type)
   */
  public function createBlockContentType($machine_name, $content_name, $description) {
    $blockType = BlockContentType::load($machine_name);
    if (!$blockType) {
      BlockContentType::create([
        'id' => $machine_name,
        'label' => $content_name,
        'description' => $description,
      ])->save();
    }
  }

  /**
   * Delete content type (Node Type).
   */
  public static function deleteNodeContentType($machine_name) {
    $nodeType = NodeType::load($machine_name);
    if ($nodeType) {
      $nodeType->delete();
    }
  }

  /**
   * Create new content type (Paragraph Type).
   */
  public function createParagraphType($machine_name, $content_name, $description) {
    // This need the module entity_reference_revisions and paragraphs enabled:
    \Drupal::service('module_installer')->install(['entity_reference_revisions']);
    \Drupal::service('module_installer')->install(['paragraphs']);
    $type = ParagraphsType::load($machine_name);
    if (!$type) {
      ParagraphsType::create([
        'id' => $machine_name,
        'label' => $content_name,
        'description' => $description,
      ])->save();
    }
  }

  /**
   * Create new webform type (Webform Type)
   */
  public function createWebformType($machine_name, $content_name, $description, $settings = []) {
    // This need the module entity_reference_revisions and webform enabled:
    $moduleHandler = \Drupal::service('module_handler');
    if (!($moduleHandler->moduleExists('webform'))) {
      \Drupal::service('module_installer')->install(['webform']);
    }

    if (!($moduleHandler->moduleExists('webform_ui'))) {
      \Drupal::service('module_installer')->install(['webform_ui']);
    }

    $settings += Webform::getDefaultSettings();

    $webformExist = \Drupal::entityTypeManager()->getStorage('webform')->load($machine_name);

    if (!$webformExist) {
      // Create a webform.
      $webform = Webform::create([
        'id' => $machine_name,
        'title' => $content_name,
        'description' => $description,
        'elements' => Yaml::encode([]),
        'settings' => [],
      ]);
      $webform->save();
    }
  }

  /**
   * Add_field_group.
   */
  public function addFieldGroup($machine_name, $field_label, $children_fields, $formatter = 'closed', $context = 'form', $entity_type = 'node', $mode = 'default', $region = 'content', $parent_name = 'group_tabs', $weight = 0, $format_type = 'tab') {

    $bundle = $this->machineName;
    $start = "group_";
    // Check if the field_name start or no with the field word.
    if (stripos($machine_name, $start) === 0) {
      // Start with the field, do nothing.
    }
    else {
      // Not start with field, concat at beginning.
      $machine_name = $start . $machine_name;
    }

    $group_data                  = new \stdClass();
    $group_data->group_name      = $machine_name;
    $group_data->context         = $context;
    $group_data->entity_type     = $entity_type;
    $group_data->bundle          = $bundle;
    $group_data->mode            = $mode;
    $group_data->label           = $field_label;
    $group_data->region          = $region;
    $group_data->parent_name     = $parent_name;
    $group_data->weight          = $weight;
    $group_data->children        = $children_fields;
    $group_data->format_type     = $format_type;
    $group_data->format_settings = [
      'classes'           => '',
      'show_empty_fields' => FALSE,
      'id'                => '',
      'formatter'         => $formatter,
      'description'       => '',
      'required_fields'   => TRUE,
    ];
    \Drupal::service('module_installer')->install(['field_group']);
    field_group_group_save($group_data);
  }

  /**
   * Add a field to a content type.
   */
  public function addField($field_name, $field_type, $label, $description = '', $cardinality = 1, $entity_type = "node", $required = FALSE, $settings_config = NULL, $settings_storage = NULL, $defualt_value = '') {
    if (is_array($field_type) && count($field_type) == 2) {
      $type_storage      = $field_type[0];
      $type_form_display = $field_type[1];
    }
    else {
      switch ($field_type) {
        case 'textarea':
          $type_storage      = "string_long";
          $type_form_display = "string_textarea";
          break;

        case 'integer':
          $type_storage      = "integer";
          $type_form_display = "number";
          break;

        case 'float':
          $type_storage      = "float";
          $type_form_display = "number";
          break;

        case 'string':
          $type_storage      = "string";
          $type_form_display = "string_textfield";
          break;

        case 'html':
          $type_storage      = "text_long";
          $type_form_display = "text_textarea";
          break;

        case 'paragraph':
          $type_storage      = "entity_reference_revisions";
          $type_form_display = "entity_reference_paragraphs";
          break;

        case 'taxonomy':
          $type_storage      = "taxonomy_term_reference";
          $type_form_display = "options_select";
        case 'entity':
          $type_storage      = "entity_reference";
          $type_form_display = "options_select";
          break;

        case 'media':
          // Enable necessary modules.
          \Drupal::service('module_installer')->install(['media']);
          \Drupal::service('module_installer')->install(['media_library']);
          $type_storage      = "entity_reference";
          $type_form_display = "media_library_widget";
          break;

        case 'image':
          $type_storage      = "image";
          $type_form_display = "image_image";
          break;

        case 'checkbox':
          $type_storage      = "boolean";
          $type_form_display = "boolean_checkbox";
          break;

        case 'str_list':
          $type_storage      = "list_string";
          $type_form_display = "options_select";
          break;

        case 'link':
          $type_storage      = "link";
          $type_form_display = "link_default";
          break;

        case 'linkattr':
          // Neccesarry for this.
          \Drupal::service('module_installer')->install(['link_attributes']);
          $type_storage      = "link";
          $type_form_display = "link_attributes";
          break;

        case 'file':
          $type_storage      = "file";
          $type_form_display = "file_generic";
          break;

        case 'comment':
          $type_storage      = "comment";
          $type_form_display = "comment_default";
          if ($defualt_value == '') {
            // Si es comentario, el $default_value si no se pasa, es necesario
            // que sea de tipo array vacio.
            $defualt_value = [];
          }
          break;

        case 'time_range':
          // Neccesarry for this.
          \Drupal::service('module_installer')->install(['time_range']);
          $type_storage      = "daterange";
          $type_form_display = "time_range";
          break;

        case 'date':
          $type_storage      = "datetime";
          $type_form_display = "datetime_default";
          break;

        case 'data_range':
          // Neccesarry for this.
          \Drupal::service('module_installer')->install(['time_range']);
          $type_storage      = "daterange";
          $type_form_display = "daterange_default";
          break;

        default:
          throw new \Exception("No se encuentra el tipo de campo '$field_type'. Puedes seleccionar una combinación custom haciendo uso de un arreglo", 1);
      }
    }
    $bundle = $this->machineName;
    $start = "field_";
    // Check if the field_name start or no with the field word.
    if (stripos($field_name, $start) === 0) {
      // Start with the field, do nothing.
    }
    else {
      // Not start with field, concat at beginning.
      $field_name = $start . $field_name;
    }

    $this->createFieldStorage($entity_type, $field_name, $type_storage, $cardinality, $settings_storage);
    $this->createFieldConfig($entity_type, $bundle, $field_name, $label, $description, $required, $defualt_value, $settings_config);
    $this->createFieldFormDisplay($entity_type, $bundle, $field_name, $type_form_display);
  }

  /**
   * Get the array to save a file as node nada.
   */
  public static function getFileImageArray($file_path, $ext, $alt_text, $title_text = NULL) {
    if ($title_text == NULL) {
      $title_text = $alt_text;
    }
    $original_file = file_get_contents($file_path);
    $new_file = file_save_data($original_file, "public://$alt_text.$ext");
    return [
      'target_id' => $new_file->id(),
      'alt'       => $alt_text,
      'title'     => $title_text,
    ];
  }

  /**
   * Save_media_image.
   */
  public static function saveMediaImage(string $file_path, string $ext, string $alt_text, string $filename = NULL) {
    if (is_null($filename)) {
      $filename = str_replace(' ', '_', $alt_text . time());
    }
    else {
      $filename = str_replace(' ', '_', $filename);
    }
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['name' => $filename]);
    if ($media) {
      $media = reset($media);
      $media->delete();
      $media = FALSE;
    }
    if (!$media) {
      $file_data = file_get_contents($file_path);
      $file = file_save_data($file_data, "public://$filename.$ext");
      $media = Media::create([
        'bundle'            => 'image',
      // Anonymous user.
        'uid'               => 0,
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt'       => $alt_text,
          'title'     => $alt_text,
        ],
      ]);
      $media->setName($filename)->setPublished(TRUE)->save();
    }
    return $media->id();
  }

  /**
   * Add Media Type field image related.
   */
  public function addMediaImage($field_machine_name, $field_label, $field_desc = '', $cardinality = -1, $required = FALSE, $entity_type = 'node') {
    $this->addField(
      $field_machine_name,
      'media',
      $field_label,
      $field_desc,
      $cardinality,
      $entity_type,
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
   * Add Paragraph field related.
   */
  public function addParagraph($field_machine_name, $target_paragraph_machine_name, $field_label, $field_desc = '', $cardinality = -1, $required = FALSE, $entity_type = 'node') {

    $target_bundles = [];
    $target_bundles_drag_drop = [];
    if (is_array($target_paragraph_machine_name)) {
      foreach ($target_paragraph_machine_name as $target) {
        $target_bundles[$target] = $target;
        $target_bundles_drag_drop[$target] = ['enabled' => 1];
      }
    }
    else {
      $target_bundles[$target_paragraph_machine_name] = $target_paragraph_machine_name;
      $target_bundles_drag_drop[$target_paragraph_machine_name] = ['enabled' => 1];
    }
    $this->addField(
      $field_machine_name,
      'paragraph',
      $field_label,
      $field_desc,
      $cardinality,
      $entity_type,
      $required,
      [
        'handler_settings' => [
          'target_bundles' => $target_bundles,
          'target_bundles_drag_drop' => $target_bundles_drag_drop,
        ],
      ],
      ['target_type' => 'paragraph']
    );
  }

  /**
   * Delete the field.
   */
  public function deleteField($field_name, $entity_type = 'node') {
    $bundle = $this->machineName;
    $this->deleteStorageConfig($entity_type, $field_name);
    $this->deleteFieldConfig($entity_type, $bundle, $field_name);
  }

  /**
   * Delete the Field Storage Config.
   */
  public function deleteStorageConfig($entity_type, $field_name) {
    $storage = FieldStorageConfig::loadByName($entity_type, $field_name);
    // Si existe.
    if ($storage) {
      $storage->delete();
    }
  }

  /**
   * Dele the Field Config.
   */
  public function deleteFieldConfig($entity_type, $bundle, $fieldname) {
    $field = FieldConfig::loadByName($entity_type, $bundle, $fieldname);
    if ($field) {
      $field->delete();
    }
  }

  /**
   * Create_field_form_display.
   */
  public function createFieldFormDisplay($entity_type, $bundle, $field_name, $type_form_display) {
    // Manage form display.
    $form_display = EntityFormDisplay::load("$entity_type.$bundle.default");
    if (!$form_display) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
        'status' => TRUE,
      ]);
    }
    $form_display->setComponent($field_name, [
      'type' => $type_form_display,
    ]);
    $form_display->save();
  }

  /**
   * Create_field_config.
   */
  public function createFieldConfig($entity_type, $bundle, $field_name, $label, $description, $required = FALSE, $defualt_value = '', $settings_config = NULL) {
    $field_config = FieldConfig::load("$entity_type.$bundle.$field_name");
    if (!$field_config) {
      $array_config = [
        'field_name'        => $field_name,
        'entity_type'       => $entity_type,
      // Content type.
        'bundle'            => $bundle,
        'label'             => $label,
        'description'       => $description,
        'required'          => $required,
        'default_value'     => $defualt_value,
      ];
      if ($settings_config != NULL) {
        $array_config['settings'] = $settings_config;
      }
      $field_config = FieldConfig::create($array_config);
      $field_config->save();
    }
    return $field_config;
  }

  /**
   * Create_field_storage.
   */
  public function createFieldStorage($entity_type, $field_name, $type_storage, $cardinality = 1, $settings_storage = NULL) {
    $field_storage_config = FieldStorageConfig::load("$entity_type.$field_name");
    if (!$field_storage_config) {
      $arr_storage = [
        'field_name'    => $field_name,
        'entity_type'   => $entity_type,
        'type'          => $type_storage,
        'cardinality'   => $cardinality,
      ];
      if ($settings_storage != NULL) {
        $arr_storage['settings'] = $settings_storage;
      }
      $field_storage_config = FieldStorageConfig::create($arr_storage);
      $field_storage_config->save();
    }
    return $field_storage_config;
  }

  /**
   * Update_node_data_by_alias.
   */
  public static function updateNodeDataByAlias($alias, $data) {
    $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
    $node = NULL;
    if (preg_match('/node\/(\d+)/', $path, $matches)) {
      $node = Node::load($matches[1]);
    }
    if ($node) {
      foreach ($data as $field_name => $value) {
        if (isset($value['is_node'])) {
          $node->set($field_name, $value['data']);
          continue;
        }
        /* else if (isset($value['is_paragraph'])) {
        $data_paragraphs = $value['data'];
        $content_paragraph = [];
        foreach ($data_paragraphs as $data) {
        $content_paragraph[] = Paragraph::create($data);
        }
        $array_content[$field_name] = $content_paragraph;
        } else if (isset($value['is_entity'])) {
        $data_ids = $value['data'];
        $content_paragraph = [];
        foreach ($data_ids as $nid) {
        $array_content[$field_name][] = ['target_id' => $nid];
        }
        }*/ else {
          continue;
}
      }
      $node->save();
      return $node->id();
    }
    return NULL;
  }

  /**
   * Create_node_data.
   */
  public static function createNodeData($title, $data, $alias, $target_content_type) {
    $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
    $node = NULL;
    if (preg_match('/node\/(\d+)/', $path, $matches)) {
      $node = Node::load($matches[1]);
    }
    if (!$node) {
      $array_content = [
        'type'  => $target_content_type,
        'title' => $title,
        'path' => [
          'alias' => $alias,
        ],
      ];
      foreach ($data as $field_name => $value) {
        if (isset($value['is_node'])) {
          $array_content[$field_name] = $value['data'];
          continue;
        }
        elseif (isset($value['is_paragraph'])) {
          $data_paragraphs = $value['data'];
          $content_paragraph = [];
          foreach ($data_paragraphs as $data_par) {
            // Tiene otro paragraph dentro (Ejemplo en install de
            // co_club_colombia_gran_colombia)
            if (isset($data_par['is_paragraph'])) {
              $second_field_name = $data_par['is_paragraph'];
              $second_field_data = $data_par['field_data'];
              unset($data_par['is_paragraph']);
              unset($data_par['field_data']);
              foreach ($second_field_data as $second_par_data) {
                $data_par[$second_field_name][] = Paragraph::create($second_par_data);
              }
            }
            $content_paragraph[] = Paragraph::create($data_par);
          }
          $array_content[$field_name] = $content_paragraph;
          // (Ejemplo en install de co_club_colombia_gran_colombia)
        }
        elseif (isset($value['is_entity'])) {
          $data_ids = $value['data'];
          $content_paragraph = [];
          foreach ($data_ids as $nid) {
            $array_content[$field_name][] = ['target_id' => $nid];
          }
        }
        else {
          continue;
        }
      }

      $node = Node::create($array_content);
      $node->save();
    }
    return $node->id();
  }

  /**
   * Delete Node Content.
   */
  public static function deleteNodeData($title, $alias = NULL) {
    // Search by title.
    $node = NULL;
    if ($title) {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['title' => $title]);
      foreach ($nodes as $nd) {
        $node = $nd;
        break;
      }
    }
    elseif ($alias) {
      $path = \Drupal::service('path_alias.manager')->getPathByAlias($alias);
      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $node = Node::load($matches[1]);
      }
    }
    if ($node) {
      $node->delete();
    }
  }

  /**
   * Create menu if not exist.
   */
  public static function createMenu($id, $label, $description) {
    if (!Menu::load($id)) {
      // Create the menu.
      return Menu::create([
        'id' => $id,
        'label' => $label,
        'description' => $description,
      ])->save();
    }
    return FALSE;
  }

  /**
   * Create menu item if not exist, update or delete.
   *
   * @param array $items
   *   Comment.
   *   [
   *    '<TITULO VIEJO>' => [
   *      'title' => '<TITULO>',
   *      'link' => ['uri' => '<URL>'],
   *      'menu_name' => <MACHINE_NAME_MENU_PARENT>,
   *      'expanded' => FALSE,
   *      'weight' => <WEIGHT>,
   *    ],
   *   ].
   */
  public static function createMenuItem(array $items) {
    foreach ($items as $old_title => $item) {
      $delete = isset($item['delete']);
      $menu_link_content = \Drupal::entityTypeManager()->getStorage('menu_link_content');
      $link = $menu_link_content->loadByProperties(['title' => $old_title]);
      // If enter here, then is to disabled.
      if ((!$link || empty($link)) && isset($item['enabled'])) {
        $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
        $link = $menu_link_manager->getDefinition($old_title);
        $link['enabled'] = $item['enabled'];
        $menu_link_manager->updateDefinition($old_title, $link);
        continue;
      }
      if ($link) {
        $menu_item = reset($link);
        if ($delete) {
          $menu_item->delete();
        }
        else {
          foreach ($item as $attr => $value) {
            $menu_item->{$attr} = $value;
          }
          $menu_item->save();
        }
      }
      // The item not exist by title.
      else {
        if (!$delete) {
          // Si no esta marcado apra eliminar, se crea.
          MenuLinkContent::create($item)->save();
        }
      }
    }
  }

  /**
   * Get Items menu.
   *
   * Load the tree of specific menu and convert to sort array by
   * weight (Load the sub items aka childrens too)
   */
  public static function getItemsMenu($name) {
    $tree = \Drupal::menuTree()->load($name, new MenuTreeParameters());
    $tree_array = self::loadMenu($tree);
    // Sort the array by key. Remember the index is "weight|ID" and only
    // wee need to sort by weight.
    uksort($tree_array, function ($a, $b) {
      $a = explode("|", $a)[0];
      $b = explode("|", $b)[0];
      return $a <=> $b;
    });
    return $tree_array;
  }

  /**
   * Load the structure of the tree in array.
   */
  public static function loadMenu($tree) {
    $menu = [];
    foreach ($tree as $key => $items) {
      if ($items->link->isEnabled()) {

        $menu[$items->link->getWeight() . "|$key"] = [
          'title' => $items->link->getTitle(),
          'url' => $items->link->getUrlObject()->toString(),
          'description' => $items->link->getDescription(),
          'has_children' => $items->hasChildren,
          'children' => self::loadMenu($items->subtree),
          'target' => ($items->link->getUrlObject()->isExternal() ? '_blank' : '_self'),
        ];
      }
    }
    return $menu;
  }

  /**
   * Convert a string from ['UTF-8', 'ISO-8859-1', 'ISO-8859-2'] to 'UTF-8'.
   */
  public static function convertToUtf8($string) {
    $encode  = mb_detect_encoding($string, ['UTF-8', 'ISO-8859-1', 'ISO-8859-2'], TRUE);
    $new_str = mb_convert_encoding($string, 'UTF-8', $encode);
    return $new_str;
  }

  /**
   * Activar los modulos que generalmente piden en SEO.
   */
  public static function activateSeoModules() {
    $modulos = [
      'metatag_routes',
      'token',
      'xmlsitemap',
      'metatag',
      'robotstxt',
      'schema_metatag',
      'schema_organization',
      'schema_qa_page',
      'schema_video_object',
      'schema_web_page',
      'schema_web_site',
      'domain',
      'redirect',
      'redirect_domain',
      'url_redirect',
      'domain_alias',
      'domain_alias',
      'metatag_open_graph',
      'metatag_twitter_cards',
      'metatag_verification',
    ];
    \Drupal::service('module_installer')->install($modulos);
  }

  /**
   * Get the data of media iamge.
   */
  public static function getEntityImageUrl($node, $field, $index = NULL) {
    $field = $node->get($field);
    if ($index) {
      $field = $field[$index];
    }
    $entity = $field->entity;
    if ($entity) {
      $image_field = $entity->get('field_media_image');
      $imgurl = \Drupal::service('file_url_generator')->generateString($image_field->entity->uri->value);
      $imgurl_webp = co_150_core_img_to_webp($imgurl);
      $imgalt = $image_field->alt;
    }
    else {
      return FALSE;
    }
    return [
      'imgurl_raw'  => $imgurl,
      'imgurl_webp' => $imgurl_webp,
      'img_alt'     => $imgalt,
    ];
  }

}
