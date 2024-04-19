(function ($, Drupal) {
  Drupal.behaviors.hideAltmetricBlock = {
    attach: function (context, settings) {
      // Delay the execution as we depend on altmetrics
      // data rendering.
      setTimeout(function() {
        if ($('#block-dgi-i8-base-altmetricsblock').find('div.altmetric-hidden').length) {
          $('#block-dgi-i8-base-altmetricsblock').hide();
        }
      }, 500);
    }
  };
})(jQuery, Drupal);
