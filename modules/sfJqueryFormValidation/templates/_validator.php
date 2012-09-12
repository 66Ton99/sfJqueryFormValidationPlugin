<?php $sfJqRules = getRawValue($sfJqRules) ?>

jQuery(function() {
  var forms = jQuery('form');
  var form = jQuery('#<?php echo $sfJqRules->getFirstFieldHtmlId() ?>').parents('form');// TODO check
  form.validate({
    rules: <?php echo ($sfJqRules->generateRules()) ?>,
    messages: <?php echo ($sfJqRules->generateMessages()) ?>,
    onkeyup: false,
    wrapper: 'ul class=error_list',
    errorElement: 'li',
    // errorLabelContainer: form.siblings('div.error_box'),
    errorPlacement: function(error, element)
    {
      forms.trigger('sfJqueryFormValidation.onValidationError', [form, error, element]);
      if (element.parents('.radio_list').is('*') || element.parents('.checkbox_list').is('*'))
      {
       error.prependTo( element.parent().parent().parent() );
      }
      else
      {
       error.prependTo( element.parent() );
      }
    }
    <?php if (sfConfig::get('app_sf_jquery_form_validation_ajax_post', false)) {?>,
      submitHandler: function(form) {
        var form = jQuery(form);
        var validator = form.validate();
        forms.trigger('sfJqueryFormValidation.prePost', [form]);
        jQuery.ajax({
          url: form.attr('action'),
          type: 'POST',
          timeout: 10000, // 10 secs
          dataType: 'json',
          data: form.serialize(),
          beforeSend: function(jqXHR, settings) {
            forms.trigger('sfJqueryFormValidation.onBeforeSend', [form, jqXHR, settings]);
          },
          error: function(jqXHR, textStatus, errorThrown) {
            forms.trigger('sfJqueryFormValidation.onError', [form, jqXHR, textStatus, errorThrown]);
          },
          success: function(data, textStatus, jqXHR) {
            forms.trigger('sfJqueryFormValidation.onSuccess', [form, data, textStatus, jqXHR]);
          },
          complete: function(jqXHR, textStatus) {
            forms.trigger('sfJqueryFormValidation.onComplete', [form, jqXHR, textStatus]);
          }
        });
        forms.trigger('sfJqueryFormValidation.afterPost', [form]);
      }
    <?php }?>

  });

  <?php foreach ($sfJqRules->getPostValidators() as $pv): ?>
      <?php echo $pv . "\n"; ?>
  <?php endforeach; ?>

});
