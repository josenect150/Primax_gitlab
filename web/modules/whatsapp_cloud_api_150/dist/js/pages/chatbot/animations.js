(function (Drupal, once) {
    'use strict';

    Drupal.behaviors.homeAnimations = {
        attach: function (context, settings) {
            once('home-animations', 'body').forEach(elm => {
                // Código para las animaciones
            });
        }
    };

}(Drupal, once));