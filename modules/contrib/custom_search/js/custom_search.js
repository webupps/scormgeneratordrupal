(function ($) {
  'use strict';
  Drupal.behaviors.custom_search = {
    attach: function (context) {

      if (typeof drupalSettings.custom_search == "undefined" || (typeof drupalSettings.custom_search != "undefined" && !drupalSettings.custom_search.solr)) {

        // Check if the search box is not empty on submit
        $('.block-custom-search form', context).submit(function () {
          var box = $(this).find('input.custom_search-keys');
          if (box.val() !== 'undefined' && box.val() === '') {
            $(this).find('input.custom_search-keys').addClass('error');
            return false;
          }
        });
        $('form#search-form', context).submit(function () {
          var $this = $(this);
          // If basic search is hidden, copy or value to the keys
          if ($this.find('#edit-keys').parents('div.hidden').attr('class') === 'hidden') {
            $this.find('#edit-keys').val($this.find('#edit-or').val());
            $this.find('#edit-or').val('');
          }
          return true;
        });
      }

      // Displays Popup.
      var $parentForm;
      $('input.custom_search-keys', context).bind('click focus', function (e) {
        $parentForm = $(this).parents('form');
        // check if there's something in the popup and displays it
        var popup = $parentForm.find('fieldset.custom_search-popup');
        if (popup.find('input,select').length && !popup.hasClass('opened')) {
          popup.fadeIn().addClass('opened');
        }
        e.stopPropagation();
      });
      $(document).bind('click focus', function () {
        $('fieldset.custom_search-popup').hide().removeClass('opened');
      });
      var popup = $('fieldset.custom_search-popup:not(.custom_search-processed)', context).addClass("custom_search-processed");
      popup.click(function (e) {
        e.stopPropagation();
      });
      popup.append('<a class="custom_search-popup-close" href="#">' + Drupal.t('Close') + '</a>');
      $('a.custom_search-popup-close').click(function (e) {
        $('fieldset.custom_search-popup.opened').hide().removeClass('opened');
        e.preventDefault();
      });

      // Handle checkboxes
      $('.custom-search-selector input:checkbox', context).each(function () {
        var el = $(this);
        if (el.val() === 'c-all') {
          el.change(function () {
            $(this).parents('.custom-search-selector').find('input:checkbox[value!=c-all]').attr('checked', false);
          });
        }
        else {
          if (el.val().substr(0,2) === 'c-') {
            el.change(function () {
              $('.custom-search-selector input:checkbox').each(function () {
                if ($(this).val().substr(0,2) === 'o-') {
                  $(this).attr('checked', false);
                }
              });
              $(this).parents('.custom-search-selector').find('input:checkbox[value=c-all]').attr('checked', false);
            });
          }
          else {
            el.change(function () {
              $(this).parents('.custom-search-selector').find('input:checkbox[value!=' + el.val() + ']').attr('checked', false);
            });
          }
        }
      });

    }
  };
})(jQuery);
