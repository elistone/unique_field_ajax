(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.unique_field_ajax = {
    attach: function (context, settings) {
      var input_selector = drupalSettings.unique_field_ajax.id;
      var typingTimer;
      var doneTypingInterval = 1500;
        $(input_selector).on('keyup', function (e) {
          clearTimeout(typingTimer);
          if ($(this).val) {
            var trigid = $(this);
            typingTimer = setTimeout(function(){
              trigid.triggerHandler('finishedinput');
            }, doneTypingInterval);
          }
        });
    }
  };
})(jQuery, Drupal, drupalSettings);
