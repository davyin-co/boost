(function ($, Drupal) {
  Drupal.behaviors.boost = {
    attach: function attach(context, settings) {
      $(context).find('span[data-boost-placeholder-id]').once('boost').each(function(index,element){
        var pid = $(element).attr('data-boost-placeholder-id');
        if(settings.boostPlaceholderIds[pid]){
          var endpoint = Drupal.url('boost/replace?' + pid);
          Drupal.ajax({ url: endpoint, method: 'GET' }).execute();
        }
      });
    }
  };
})(jQuery, Drupal);