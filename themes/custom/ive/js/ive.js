(function($, Drupal, drupalSettings) {
  // Use beahvior and context to execute piece of codes in every case : page loaded, parent
  // @see https: // wwww.drupal.org/doc/8/api/javascript-api-overview
  // External Links...
  Drupal.behaviors.externalLink = {
    attach: function (context, setting) {
      $("a[href^='http']", context).attr('target', '_blank').addClass('external-link');
    }
  }
  // Collapsable blocks...
  Drupal.behaviors.collapsableBlock = {
    attach: function (context, setting) {
      $('.sidebar .block h2', context).click(function() {
        // Visual debugging ;-)
        // alert('Hello !');
        // Debugging with console
        console.log('Hello !');
        $(this).parent().find('.content').slideToggle('fast');
      });
    }
  }
})(jQuery, Drupal, drupalSettings);