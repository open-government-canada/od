/**
 * @file
 * Attaches fiveleaf rating.
 */

(function ($, Drupal) {
  Drupal.behaviors.fiveLeafRating = {
    attach: function (context, settings) {
      $('body').find('.fiveleaf').each(function () {
        var $this = $(this);
        var $select = $this.find('select');
        var value = $select.data('default-value');
        if (!value) {
          value = -1;
        }

        var options = {
          theme: ($select.data('style') == 'default') ? 'css-leafs' : $select.data('style'),
          showSelectedRating: true,
          initialRating: value,
          allowEmpty: true,
          readonly: ($select.attr('disabled')) ? true : false,
          onSelect: function (value, text) {
            $this.find('select').barrating('readonly', true);
            $this.find('[data-drupal-selector=edit-submit]').click();
            $this.find('a').addClass('disabled');

            $('body').find('.fiveleaf').not($this).find('.on-select').each(function (index, value) {
              $(this).find('input[id^="edit-cancel"]').trigger('click');
            });

            $this.find('form').addClass('on-select');
          },
        };
        $this.find('form:not(.on-select) select').once('processed').barrating('show', options);
        $this.find('form:not(.on-select) [type=submit]').hide();
      });
    },
  };
})(jQuery, Drupal);
