/**
 * Extension for jQuery.Validate plugin
 *
 * @package    LeftCo
 * @subpackage JS
 * @author     Alexandr Shuboff <a.shuboff@gmail.com>
 */
(function($) {

$.extend($.fn, {
  // http://docs.jquery.com/Plugins/Validation/validate
  validateExt: function( options ) {
    var validator = $.data(this[0], 'validator');

    if (typeof options == 'object' && validator) {
      options = $.extend(validator.settings, options);
      $.data(this[0], 'validator', null);
    }

    return jQuery(this).validate(options);
  }
});

//$.extend($.validator, {
//  changeSettings: function( options ) {
//    this.settings = $.extend( this.settings, options );
//  }
//});

})(jQuery);
