<?php
if (in_array('sfJqueryFormVal', sfConfig::get('sf_enabled_modules'))) {
  $this->dispatcher->connect('routing.load_configuration', array('sfJqueryFormValRouting', 'addRoute'));
}