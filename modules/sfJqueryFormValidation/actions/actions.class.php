<?php

/**
 * @package    sfJqueryFormValidationPlugin
 * @subpackage action
 */
class sfJqueryFormValidationActions extends sfActions
{

  /**
   * (non-PHPdoc)
   * @see sfAction::preExecute()
   */
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
    $this->forward404Unless($form instanceof sfForm);


    return $this->renderComponent(
      $this->getModuleName(),
      'validator',
      array(
        'form' => $form,
        'embeddedForms' => $request->getParameter('embedded_forms')
      )
    );
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
