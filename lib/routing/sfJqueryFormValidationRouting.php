<?php

/**
 * @package    sfJqueryFormValidationPlugin
 * @subpackage lib
 */
class sfJqueryFormValidationRouting
{
  static public function addRoute(sfEvent $event)
  {
    $event->getSubject()->prependRoute('sf_jquery_form_validation',
                                       new sfRoute('/sfJqueryFormValidation/:form',
                                                   array('module' => 'sfJqueryFormValidation', 'action' => 'index')));
    $event->getSubject()->prependRoute('sf_jquery_form_remote',
                                       new sfRoute('/sfJqueryFormValidation/remote/:form/:validator',
                                                   array('module' => 'sfJqueryFormValidation', 'action' => 'remote')));
  }
}
