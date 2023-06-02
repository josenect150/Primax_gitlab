<?php

namespace Drupal\co_150_core\Controller;

use Drupal\paragraphs\Entity\ParagraphsType;

/**
 * ParagraphController class manager.
 */
class ParagraphController extends ContentController {

  /**
   * Create object.
   */
  public function __construct($machine_name, $content_title, $content_description) {
    parent::__construct($machine_name, $content_title, $content_description, 'paragraph');
  }

  /**
   * Add a field to a paragraph content.
   */
  public function addParagraphField($field_name, $field_type, $label, $description = '', $cardinality = 1, $required = FALSE, $settings_config = NULL, $settings_storage = NULL, $defualt_value = '') {
    parent::addField($field_name, $field_type, $label, $description, $cardinality, 'paragraph', $required, $settings_config, $settings_storage, $defualt_value);
  }

  /**
   * Add Media Type field image related.
   */
  public function addParagraphMediaImage($field_machine_name, $field_label, $field_desc = '', $cardinality = -1, $required = FALSE) {
    $this->addField(
      $field_machine_name,
      'media',
      $field_label,
      $field_desc,
      $cardinality,
      'paragraph',
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
   * Delete the paragraph.
   */
  public static function deleteParagraphType($machine_name) {
    $type = ParagraphsType::load($machine_name);
    if ($type) {
      $type->delete();
    }
  }

}
