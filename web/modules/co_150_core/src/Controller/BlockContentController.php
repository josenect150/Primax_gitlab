<?php

namespace Drupal\co_150_core\Controller;

use Drupal\block\Entity\Block;
use Drupal\block_content\Entity\BlockContent;

/**
 * BlockContentController class manager.
 */
class BlockContentController extends ContentController {

  /**
   * Machine name.
   *
   * @var mixed
   */
  private $machineName;

  /**
   * Create object.
   */
  public function __construct($machine_name, $content_title, $content_description) {
    $this->machineName = $machine_name;
    parent::__construct($machine_name, $content_title, $content_description, 'block_content');
  }

  /**
   * Add a field to a paragraph content.
   */
  public function addBlockField($field_name, $field_type, $label, $description = '', $cardinality = 1, $required = FALSE, $settings_config = NULL, $settings_storage = NULL, $defualt_value = '') {
    parent::addField($field_name, $field_type, $label, $description, $cardinality, 'block_content', $required, $settings_config, $settings_storage, $defualt_value);
  }

  /**
   * Add Media Image field.
   */
  public function addBlockMediaImage($field_machine_name, $field_label, $field_desc = '', $cardinality = -1, $required = FALSE) {
    $this->addField(
      $field_machine_name,
      'media',
      $field_label,
      $field_desc,
      $cardinality,
      'block_content',
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
   * Create_block_content_data.
   */
  public function createBlockContentData($title, $region, $data_content = []) {
    $default_theme = \Drupal::config('system.theme')->get('default');

    $data_content['info'] = $title;
    $data_content['type'] = $this->machineName;

    $data_placed['id'] = strtolower(str_replace(' ', '', $title));

    // Si el bloque en la region existe.
    if ($placed_block = Block::load($data_placed['id'])) {
      // Lo eliminamos.
      $placed_block->delete();
    }

    $block_content = \Drupal::entityTypeManager()->getStorage('block_content')->loadByProperties([
      'info' => $title,
    ]);
    // Si el contenido del bloque existe.
    if ($block_content) {
      $block_content = reset($block_content);
      BlockContent::load($block_content->id())->delete();
    }

    $block_content = BlockContent::create($data_content);
    $block_content->save();

    $placed_block = Block::create([
      'id' => $data_placed['id'],
      'theme' => $default_theme,
      'weight' => 0,
      'status' => TRUE,
      'region' => $region,
      'plugin' => 'block_content:' . $block_content->uuid(),
      'settings' => [
        'label' => $title,
        'provider' => 'block_content',
        'status' => TRUE,
        'view_mode' => 'full',
        'label_display' => FALSE,
      ],
      'visibility' => [],
    ]);
    $placed_block->save();
  }

}
