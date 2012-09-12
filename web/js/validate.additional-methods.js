/**
 * Additional validation methods
 * Add this file to the page where plugin should be used
 *
 * @package    sfJqueryFormValidationPlugin
 * @subpackage JS
 * @author     Alexandr Shuboff <a.shuboff@gmail.com>
 */

// for some reason the jQuery Validate plugin does not include these methods:
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

jQuery.validator.addMethod(
  "min",
  function(value, element, minValue) {
      return parseFloat(value) >= parseFloat(minValue);
  },
  "Invalid (min)."
);

jQuery.validator.addMethod(
  "max",
  function(value, element, maxValue) {
      return parseFloat(value) <= parseFloat(maxValue);
  },
  "Invalid (max)."
);

// revised method for "digits" rule
jQuery.validator.addMethod(
  "digits",
  function(value, element) {
      return this.optional(element) || /^[-]?\d+$/.test(value);
  },
  "Invalid (max)."
);

// revised method for "number" rule
jQuery.validator.addMethod(
  "number",
  function(value, element) {
    return this.optional(element) || /^-?(?:\d+)(?:\.\d+)?$/.test(value);
  },
  ''
);
