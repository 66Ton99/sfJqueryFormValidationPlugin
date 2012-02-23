<?php

class sfJqueryFormValidationFilter extends sfFilter
{
  public function execute($filterChain) 
  {
    $filterChain->execute();
    $action = $this->getContext()->getActionStack()->getLastEntry()->getActionInstance();
    foreach ($action->getVarHolder()->getAll() as $name => $value) {
      if ($value instanceof sfForm && 
          (sfConfig::get('app_sf_jquery_form_validation_default') !== 'disabled' || 
          in_array(get_class($value), sfConfig::get('app_sf_jquery_form_validation_forms'))))
      {
        
        $url_params = sfJqueryFormValidationRules::getUrlParams();
        $url_params['form'] = get_class($value);
        
        $embedded_forms = array();
        foreach ($value->getEmbeddedForms() as $name => $embedded_form) {
          $url_params['embedded_form'][$name] = get_class($embedded_form);
        }
        if (sizeof($embedded_forms) > 0) {
          $url_params['embedded_form'] = $embedded_forms;
        }
        
        use_dynamic_javascript(url_for($url_params)); 
      }
    }
  }
}