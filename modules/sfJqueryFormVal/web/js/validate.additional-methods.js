/**
 * Additional validation methods
 * Add this file to the page where plugin should be used
 *
 * @package    LeftCo
 * @subpackage JS
 * @author     Alexandr Shuboff <a.shuboff@gmail.com>
 */

/* for some reason the jQuery Validate plugin does not include a generic regex method */
jQuery.validator.addMethod(
  "regex",
  function(value, element, regexp) {
      if (regexp.constructor != RegExp)
          regexp = new RegExp(regexp);
      else if (regexp.global)
          regexp.lastIndex = 0;
      return this.optional(element) || regexp.test(value);
  },
  "Invalid (regex)."
);
