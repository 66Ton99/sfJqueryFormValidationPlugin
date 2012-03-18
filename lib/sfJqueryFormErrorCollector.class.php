<?php

/**
 * Form errors collector
 *
 * @package    sfJqueryFormValidationPlugin
 * @subpackage lib
 * @author     Alexandr Shuboff <a.shuboff@gmail.com>
 */
class sfJqueryFormErrorCollector
{
  /**
   * Form object
   *
   * @var sfForm
   */
  protected $form = null;

  /**
   * Form error messages
   *
   * @var array
   */
  protected $errors = null;

  /**
   * Constructor
   *
   * @param sfForm $form
   */
  public function __construct(sfForm $form)
  {
    $this->form = $form;
  }

  /**
   * Return array of the form errors
   *
   * @param string $formName
   * @return array
   */
  public function getErrors($formName = null)
  {
    if (null === $this->errors)
    {
      $formName = $this->getFormName($formName);
      $this->collectErrors($formName);
    }

    return $this->errors;
  }

  /**
   * Return JSON-encoded form errors
   *
   * @param string $formName
   * @return string
   */
  public function getJsonErrors($formName = null)
  {
    $result['errors'] = $this->getErrors($this->getFormName($formName));

    if (empty($result))
    {
      $result['success'] = true;
      unset($result['errors']);

      if (!empty($redirectUrl))
      {
        $result['redirectUrl'] = $redirectUrl;
      }
    }

    return json_encode($result);
  }

  /**
   * Collect form errors into internal array
   *
   * @param $name
   * @param sfValidatorErrorSchema $errorSchema
   */
  protected function collectErrors($name, $errorSchema = null)
  {
    if (null === $errorSchema)
    {
      $errorSchema = $this->form->getErrorSchema();
    }
    foreach ($errorSchema as $key => $error)
    {
      /** @var $error sfValidatorError */
//      $errorKey = $name . '_' . $key;
      $errorField = $name . '[' . $key . ']';
      if ($error instanceof sfValidatorErrorSchema)
      {
        $this->collectErrors($errorField, $error);
      }
      else
      {
        $this->errors[$errorField] = $this->form->getWidgetSchema()->getFormFormatter()
          ->translate($error->getMessage(), $error->getArguments());
      }
    }
  }

  /**
   * Return form name
   *
   * @param string $formName
   * @return string
   */
  protected function getFormName($formName = null)
  {
    if (null === $formName)
    {
      $formName = $this->form->getName();
    }
    return $formName;
  }
}
