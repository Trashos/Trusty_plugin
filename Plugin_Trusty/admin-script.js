jQuery(document).ready(function($) {
    // Insérez le lien "Afficher les détails" personnalisé sous le nom de votre plugin
    var pluginName = 'Votre nom de plugin'; // Remplacez par le nom exact de votre plugin
    var pluginRow = $('tr[data-slug="votre-slug-de-plugin"]'); // Remplacez par le slug exact de votre plugin
    var detailsLink = $('<a href="#">Afficher les détails</a>');
    pluginRow.find('.plugin-title').append(detailsLink);
  
    // Créez la fenêtre pop-up avec les détails de votre plugin
    var detailsDialog = $('<div id="votre-plugin-details" title="Détails du plugin" style="display:none;"></div>');
    detailsDialog.html('<p>Insérez ici les détails de votre plugin.</p>');
    $('body').append(detailsDialog);
  
    // Ouvrez la fenêtre pop-up lorsque l'utilisateur clique sur le lien "Afficher les détails"
    detailsLink.on('click', function(e) {
      e.preventDefault();
      detailsDialog.dialog({
        modal: true,
        width: 600,
        buttons: {
          Fermer: function() {
            $(this).dialog('close');
          }
        }
      });
    });
  });
  