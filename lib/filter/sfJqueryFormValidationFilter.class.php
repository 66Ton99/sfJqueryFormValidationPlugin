<?php

/**
 * @package    sfJqueryFormValidationPlugin
 * @subpackage lib
 */
class sfJqueryFormValidationFilter extends sfFilter
{
  public function execute($filterChain)
  {
    $action = $this->getContext()->getActionStack()->getLastEntry()->getActionInstance();
    foreach ($action->getVarHolder()->getAll() as $name => $value)
    {
      if ($value instanceof sfForm &&
          (sfConfig::get('app_sf_jquery_form_validation_default') !== 'disabled' ||
          in_array(get_class($value), sfConfig::get('app_sf_jquery_form_validation_forms'))))
      {
        $url_params['sf_route'] = 'sf_jquery_form_validation';
        $url_params['form'] = get_class($value);

//        $embedded_forms = array();
        foreach ($value->getEmbeddedForms() as $name => $embedded_form)
        {
          if (count($embedded_form->getEmbeddedForms()))
          {
            // forms were embedded by embedRelation method
            // and embedDynamicRelation (in case the sfDoctrineDynamicFormRelationsPlugin plugin is used)
            foreach ($embedded_form->getEmbeddedForms() as $subform)
            {
              $url_params['embedded_form'][$name] = get_class($subform);
              //break;
            }
          }
          else
          {
            $url_params['embedded_form'][$name] = get_class($embedded_form);
          }
        }
//        if (sizeof($embedded_forms) > 0) {
//          $url_params['embedded_form'] = $embedded_forms;
//        }
        use_dynamic_javascript(url_for($url_params), 'last');
      }
    }
    $filterChain->execute();
  }
}
