<?php

/**
 * @author Ton Sharp <Forma-PRO@66ton99.org.ua>
 *
 * @property string $form
 * @property array $embeddedForm
 */
class validatorComponent extends sfComponent
{

  /**
   * (non-PHPdoc)
   * @see sfComponent::execute()
   */
  public function execute($request) {
    // create an instance of the sfJqueryFormValidationRules object
    $this->sfJqRules = new sfJqueryFormValidationRules(new $this->form);

    if (is_array($this->embeddedForms)) {
      foreach($this->embeddedForms as $name => $form) {
        if ($form instanceof sfForm)  {
          $this->sfJqRules->addEmbeddedForm($name, new $form);
        }
      }
    }
  }
}
