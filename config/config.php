<?php
if (in_array('sfJqueryFormValidation', sfConfig::get('sf_enabled_modules'))) {
  $this->dispatcher->connect('routing.load_configuration', array('sfJqueryFormValidationRouting', 'addRoute'));
}
