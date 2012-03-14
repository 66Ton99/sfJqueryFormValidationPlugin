<?php

class sfJqueryFormErrorGenerator
{
  /**
   * @var sfForm
   */
  protected $form = null;

  /**
   * @var array
   */
  protected $errors = array();

  public function __construct(sfForm $form)
  {
    $this->form = $form;
  }

  public function getJsonErrors()
  {
    if (empty($this->errors))
    {
      $this->generateErrors();
    }

    foreach ($this->errors as $key => $error)
    {

    }
  }

  protected function generateErrors()
  {
    foreach ($this->form->getErrorSchema() as $key => $error)
    {
      /** @var $error sfValidatorError */

    }
  }
}
