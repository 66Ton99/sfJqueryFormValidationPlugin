<?php $sf_jq_rules = $sf_data->getRaw('sf_jq_rules') ?>

jQuery(function($){
  
  $('#<?php echo $sf_jq_rules->getFirstFieldHtmlId() ?>').parents('form').validate({
    rules: <?php echo ($sf_jq_rules->generateRules()) ?>,
    messages: <?php echo ($sf_jq_rules->generateMessages()) ?>,
    onkeyup: false,
    wrapper: 'ul class=error_list',
    errorElement: 'li',
    errorPlacement: function(error, element) 
    {
     if(element.parents('.radio_list').is('*') || element.parents('.checkbox_list').is('*'))
     {
       error.prependTo( element.parent().parent().parent() );
     }
     else
     {
       error.prependTo( element.parent() );
     }
     //, submitHandler: function(form) {}
   }
  
  });
  
  <?php foreach($sf_jq_rules->getPostValidators() as $pv): ?>
      <?php echo $pv . "\n" ?>
  <?php endforeach ?>

});

/* for some reason the jQuery Validate plugin does not incluce a generic regex method */
jQuery.validator.addMethod(
  "regex",
  function(value, element, regexp) {
      if (regexp.constructor != RegExp)
          regexp = new RegExp(regexp);
      else if (regexp.global)
          regexp.lastIndex = 0;
      return this.optional(element) || regexp.test(value);
  },
  "Invalid."
);