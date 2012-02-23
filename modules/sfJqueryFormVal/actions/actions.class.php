<?php

class sfJqueryFormValActions extends sfActions
{
  public function preExecute()
  {
    sfConfig::set('sf_web_debug', false);
  }
  
  public function executeIndex(sfWebRequest $request)
  {
    // get the name of the form object we are building the validation for
    $form = $request->getParameter('form');
    
    // make sure that the form class specified actually exists
    // and it is really a symfony form
    $this->forward404Unless($this->isValidSfFormName($form));
    
    // errors have to be disabled because any kind of warning message will break
    // the outputted javascript
    // error_reporting('E_NONE'); // Very bad practice
    
    // create an instance of the sfJqueryFormValidationRules object
    $this->sf_jq_rules = new sfJqueryFormValidationRules(new $form);
    
    // add embedded forms
    $embedded_form = $request->getParameter('embedded_form');
    if (is_array($embedded_form)) {
      foreach($embedded_form as $name => $form) {
        if ($this->isValidSfFormName($form))  {
          $this->sf_jq_rules->addEmbeddedForm($name, new $form);
        }
      }
    }
  }
  
  private function isValidSfFormName($form_class_name)
  {
    return class_exists($form_class_name) && is_subclass_of($form_class_name, 'sfForm');
  }
  
  public function executeRemote(sfWebRequest $request)
  {
    
    $paramsArr = $request->getParameterHolder()->getAll();
    if (empty($paramsArr['form']) || empty($paramsArr['validator'])) {
      return sfView::NONE;
    }
    extract($paramsArr);
    $params = array();
    foreach ($paramsArr as $val) {
      if (is_array($val)) {
        $params = $val;
        break;
      }
    }
    $formObj = new $form();
    switch ($validator) {
      case 'sfValidatorDoctrineUnique':
      case 'sfValidatorPropelUnique':
        $validatorObj = new $validator(array('model' => $formObj->getModelName(), 'column' => array_keys($params)));
        $this->result = 'true';
        try {
          $validatorObj->clean($params);
        } catch (Exception $e) {
          $this->result = 'false';
        }
        $this->renderText($this->result);
        break;
    }
    return sfView::NONE;
  }  
}
