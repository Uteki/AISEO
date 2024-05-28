(function ($) {
  Drupal.behaviors.openPopup = {
    attach: function (context, settings) {
      $('.open-popup-button').once('open-popup').click(function () {
        // AJAX-Anfrage, um das Keyword-Import-Formular zu laden
        $.ajax({
          url: '/path/to/keyword-import-form', // Ersetze dies mit dem tatsächlichen Pfad zum Keyword-Import-Formular
          method: 'GET',
          success: function (data) {
            // Erstelle das Popup-Fenster
            var popup = $('<div class="keyword-import-popup">').html(data);
            $('body').append(popup);

            // Öffne das Popup mit einer Modallibrary oder benutzerdefiniertem Code
            // Hier ist ein Beispiel mit der Modallibrary "Magnific Popup"
            popup.magnificPopup({
              type: 'inline',
              preloader: false,
              focus: '#edit-keyword-import-form', // ID des Keyword-Import-Formulars
              callbacks: {
                close: function () {
                  popup.remove(); // Entferne das Popup-Fenster nach dem Schließen
                }
              }
            }).magnificPopup('open');
          }
        });
      });
    }
  };
})(jQuery);
