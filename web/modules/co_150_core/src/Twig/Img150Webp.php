<?php

namespace Drupal\co_150_core\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Img150Webp class manager.
 */
class Img150Webp extends AbstractExtension {

  /**
   * GetFunctions.
   */
  public function getFunctions() {
    return [
      new TwigFunction('co_150_core_image_to_webp', [$this, 'imageurlToWebp']),
    ];
  }

  /**
   * ImageurlToWebp.
   */
  public function imageurlToWebp($image_uri) {
    return co_150_core_img_to_webp($image_uri);
  }

}
