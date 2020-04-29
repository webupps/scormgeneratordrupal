(function ($, Drupal) {

  "use strict";

  /**
   * Filters the skin listing tables by a text input search string.
   *
   * Text search input: input.skin-filter-text
   * Target table:      input.skin-filter-text[data-table]
   * Source text:       .skin-table-filter-text-source
   */
  Drupal.behaviors.skinTableFilterByText = {
    attach: function (context, settings) {
      var $input = $('input.skin-filter-text').once('skin-filter-text');
      var $table = $($input.attr('data-table'));
      var $rows;

      function filterSkinList(e) {
        var query = $(e.target).val().toLowerCase();

        function showSkinRow(index, row) {
          var $row = $(row);
          var $sources = $row.find('.skin-table-filter-text-source');
          var textMatch = $sources.text().toLowerCase().indexOf(query) !== -1;
          $row.closest('tr').toggle(textMatch);
        }

        // Filter if the length of the query is at least 2 characters.
        if (query.length >= 2) {
          $rows.each(showSkinRow);
        }
        else {
          $rows.show();
        }
      }

      if ($table.length) {
        $rows = $table.find('tbody tr');
        $input.on('keyup', filterSkinList);
      }
    }
  };

}(jQuery, Drupal));
