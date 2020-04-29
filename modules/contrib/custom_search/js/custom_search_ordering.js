(function ($, window) {

  'use strict';

  /**
   * Move an element in the elements table from one region to another via select list.
   *
   * This behavior is dependent on the tableDrag behavior, since it uses the
   * objects initialized in that behavior to update the row.
   */
  Drupal.behaviors.customSearchElementDrag = {
    attach: function (context, settings) {
      // tableDrag is required and we should be on the blocks admin page.
      if (typeof Drupal.tableDrag === 'undefined' || typeof Drupal.tableDrag.elements === 'undefined') {
        return;
      }

      var table = $('#elements');
      var tableDrag = Drupal.tableDrag.elements; // Get the blocks tableDrag object.

      // Add a handler for when a row is swapped, update empty regions.
      tableDrag.row.prototype.onSwap = function (swappedRow) {
        checkEmptyRegions(table, this);
      };

      // Add a handler so when a row is dropped, update fields dropped into new regions.
      tableDrag.onDrop = function () {
        var dragObject = this;
        var $rowElement = $(dragObject.rowObject.element);
        // Use "region-message" row instead of "region" row because
        // "region-{region_name}-message" is less prone to regexp match errors.
        var regionRow = $rowElement.prevAll('tr.region-message').get(0);
        var regionName = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
        var regionField = $rowElement.find('select.order-region');
        if ($rowElement.prev('tr').is('.region-message')) {
          var weightField = $rowElement.find('select.order-weight');
          var oldRegionName = weightField[0].className.replace(/([^ ]+[ ]+)*order-weight-([^ ]+)([ ]+[^ ]+)*/, '$2');

          if (!regionField.is('.order-region-' + regionName)) {
            regionField.removeClass('order-region-' + oldRegionName).addClass('order-region-' + regionName);
            weightField.removeClass('order-weight-' + oldRegionName).addClass('order-weight-' + regionName);
            regionField.val(regionName);
          }
        }
      };

      // Add the behavior to each region select list.
      $(context).find('select.order-region').once('order-region', function () {
        $(this).on('change', function (event) {
          // Make our new row and select field.
          var row = $(this).closest('tr');
          var select = $(this);
          tableDrag.rowObject = new tableDrag.row(row);
          // Find the correct region and insert the row as the last in the region.
          table.find('.region-title-' + select[0].value).nextUntil('.region-title').last().after(row);

          // Modify empty regions with added or removed fields.
          checkEmptyRegions(table, row);
          // Remove focus from selectbox.
          select.trigger('blur');
        });
      });

      var checkEmptyRegions = function (table, rowObject) {
        table.find('tr.region-message').each(function () {
          var $this = $(this);
          // If the dragged row is in this region, but above the message row, swap it down one space.
          if ($this.prev('tr').get(0) === rowObject.element) {
            // Prevent a recursion problem when using the keyboard to move rows up.
            if ((rowObject.method !== 'keyboard' || rowObject.direction === 'down')) {
              rowObject.swap('after', this);
            }
          }
          // This region has become empty.
          if ($this.next('tr').is(':not(.draggable)') || $this.next('tr').length === 0) {
            $this.removeClass('region-populated').addClass('region-empty');
          }
          // This region has become populated.
          else if ($this.is('.region-empty')) {
            $this.removeClass('region-empty').addClass('region-populated');
          }
        });
      };
    }
  };

})(jQuery, window);
