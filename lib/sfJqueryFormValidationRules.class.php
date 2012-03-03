<?php

class sfJqueryFormValidationRules
{
  private $forms = array();

  private $rules = array();

  private $messages = array();

  private $firstFieldId = null;

  private $formName = null;

  private $postValidators = array();

  private static $urlParams = array(
    'module' => 'sfJqueryFormVal',
    'action' => 'index'
  );

  private static $widgets = array(
    'sfValidatorEmail' => array(
      'rules' => array('email' => true),
      'keymap' =>  array('pattern' => 'invalid'),
      'msgmap' =>  array('pattern' => 'email'),
    ),

    'sfValidatorFile' => array(
      'rules' => array('accept' => true),
      'keymap' => array('mime_types' => 'accept'),
      'msgmap' =>  array('mime_types' => 'accept'),
      'valmap' => array(
        'mime_types' => array(
          'web_images' => 'jpg|jpeg|png|gif'
        ),
      ),
    ),

    'sfValidatorRegex' => array(
      'rules' => array('regex' => '%pattern%'),
      'keymap' =>  array('pattern' => 'invalid'),
      'msgmap' =>  array('pattern' => 'regex'),
    ),

    'sfValidatorUrl' => array(
      'rules' => array('url' => true),
      'keymap' =>  array('pattern' => 'invalid'),
      'msgmap' =>  array('pattern' => 'url'),
    ),

    'sfValidatorInteger' => array(
      'rules' => array('digits' => true),
      'keymap' =>  array('pattern' => 'invalid'),
      'msgmap' =>  array('pattern' => 'digits'),
    ),

    'sfValidatorDate' => array(
      'rules' => array('date' => true),
      'keymap' =>  array('date_format' => 'bad_format'),
      'msgmap' =>  array('date_format' => 'date'),
    )
  );

  private static $keymap = array(
     'min_length' => 'minlength',
     'max_length' => 'maxlength',
  );

  public static function getUrlParams()
  {
    return self::$urlParams;
  }

  public function __construct(sfForm $form)
  {
    // if an alternative date method has been specified, update the static widget array
    if(strlen(sfConfig::get('app_sf_jquery_form_validation_date_method')) > 0)
    {
      self::$widgets['sfValidatorDate']['rules'] = array(sfConfig::get('app_sf_jquery_form_validation_date_method') => true);
      self::$widgets['sfValidatorDate']['keymap'] = array('date_format' => 'bad_format');
      self::$widgets['sfValidatorDate']['msgmap'] = array('date_format' => sfConfig::get('app_sf_jquery_form_validation_date_method'));
    }

    $this->form = $form;
    $this->formName = $form->getName();
    $this->processValidationRules($this->formName, $form->getValidatorSchema());
  }

  /**
   * @param sfForm $form
   * @param sfJqueryFormValidationRules $sfJqueryRules
   * @param string $parentName
   */
  protected function addEmbeddedForms(sfForm $form, $parentName = false)
  {
    foreach ($form->getEmbeddedForms() as $name => $embeddedForm)
    {
      $name = false !== $parentName
        ? $parentName . '][' . $name
        : $name;
      if ('sfForm' == get_class($embeddedForm) && count($form->getEmbeddedForms()))
      {
        $this->addEmbeddedForms($embeddedForm, $name);
      }
      else
      {
        $this->addEmbeddedForm($name, $embeddedForm);
      }
    }
  }

  public function addEmbeddedForm($name, sfForm $form)
  {
//    $this->processValidationRules($name, $form, true);
    $this->forms[] = $form;
  }

  public function generateRules()
  {
    return sizeof($this->rules) > 0 ? json_encode($this->rules) : '{}';
  }

  public function generateMessages()
  {
    $message = sizeof($this->messages) > 0 ? json_encode($this->messages) : '{}';
    // this is a nasty hack to return a javascript function as an unquoted value
    // see line 247 for the matching hackery
    $message = str_replace('"[[', 'function(a, elem)', $message);
    $message = str_replace(']]"', '', $message);
    $message = str_replace('\" +', '" +', $message);
    $message = str_replace(' + \"', ' + "', $message);
    return $message;
  }

  public function getFirstFieldHtmlId()
  {
    return $this->firstFieldId;
  }

  /**
   * Create validation rules and messages
   *
   * @param string $name
   * @param sfValidatorSchema $validatorSchema
   * @param string $parentName
   */
  public function processValidationRules($name, sfValidatorSchema $validatorSchema, $parentName = null)
  {
    foreach ($validatorSchema->getFields() as $fieldname => $validator)
    {
      // ignore the csrf field
      if ('_csrf_token' === $fieldname)
      {
        continue;
      }

      $isEmbedded = null !== $parentName;

      if ($validator instanceof sfValidatorSchema)
      {
        $subFieldName = $isEmbedded
          ? $parentName . '][' . $fieldname
          : $fieldname;

        $this->processValidationRules($subFieldName, $validator, $fieldname);
      }
      else
      {
        // get the correct html "name" for this field
        $validation_name = $this->createValidationName($name, $fieldname, $isEmbedded);

        /** @var $validator sfValidatorBase */
        $this->processRules($validation_name, $validator);
        $this->processMessages($validation_name, $validator);
      }
    }
  }

  /**
   * Process rules
   *
   * @param string $validationName
   * @param sfValidatorBase $objField
   *
   * @return void
   */
  private function processRules($validationName, sfValidatorBase $objField, $parentName = false)
  {
    $fieldOptions = $objField->getOptions();

    foreach ($fieldOptions as $option => $value)
    {
      if (null === $value)
      {
        continue;
      }
      // process the common rules for all widgets
      switch ($option)
      {
        case 'required':
          if ($value)
          {
            $this->addRule($validationName, 'required', true);
          }
          break;

        case 'max_length':
        case 'min_length':
          $this->addRule($validationName, str_replace('_', '', $option), $value);
          break;

        case 'min':
        case 'max':
          $this->addRule($validationName, $option, $value);
          break;

        case '':
          break;
      }
    }

    // TODO - add support for sfValidatorAnd and sfValidatorOr
    //if(get_class($objField) == 'sfValidatorAnd');

    // now add widget specific rules
    foreach (self::$widgets as $widgetName => $properties)
    {
      if ($widgetName == get_class($objField))
      {
        foreach ($properties['rules'] as $key => $val)
        {
          // if there's a dynamic placehold in the value, do a replace for the real value
          if (preg_match('/%(.*)%/', $val, $matches) > 0)
          {
            // remove the slash because it breaks the javascript regex syntax
            // (hopefully removing the slash doesn't break anything else in the future)
            $val = str_replace('/', '', $fieldOptions[$matches[1]]);
          }

           // if there is value replacements for this field, action them now
          $originalKey = $this->getOriginalFieldKey($widgetName, $key) ?: $key;

          if (isset($fieldOptions[$originalKey]))
          {
            if (isset($properties['valmap'][$originalKey][$fieldOptions[$originalKey]]))
            {
              $val = $properties['valmap']['mime_types'][$fieldOptions[$originalKey]];
            }
          }
          // add the validation rule
          $this->addRule($validationName, $key, $val);
        }
      }
    }
  }

  private function processMessages($validationName, sfValidatorBase $objField)
  {
    foreach ($objField->getOptions() as $key => $val)
    {
      $this->addMessage($validationName, $this->outputMessageKey($key, $objField), $this->parseMessageVal($key, $objField));
    }
  }

  private function parseMessageKey($key, sfValidatorBase $objField)
  {
    $class = get_class($objField);
    if (isset(self::$widgets[$class]['keymap'][$key]))
    {
      $key = self::$widgets[$class]['keymap'][$key];
    }
    elseif (isset(self::$keymap[$key]))
    {
      $key = self::$keymap[$key];
    }
    return $key;
  }

  private function outputMessageKey($key, sfValidatorBase $objField)
  {
    $class = get_class($objField);
    if (isset(self::$widgets[$class]['msgmap'][$key]))
    {
      $key = self::$widgets[$class]['msgmap'][$key];
    }
    elseif (isset(self::$keymap[$key]))
    {
      $key = self::$keymap[$key];
    }
    return $key;
  }

  /**
   * Parse message value
   *
   * @param string $key
   * @param sfValidatorBase $objField
   *
   * @return string
   */
  private function parseMessageVal($key, sfValidatorBase $objField)
  {
//    if ($objField instanceof sfValidatorSchema)
//    {
//      $retrunVal = '';
//      foreach ($objField->getFields() as $subKey => $subObjField)
//      {
//        $retrunVal .= $this->parseMessageVal($subKey, $subObjField);
//      }
//      return $retrunVal;
//    }

    $field_options = $objField->getOptions();
    $messages = $objField->getMessages();
    $val = "";

    // if the field options for this item is empty, don't include it
    if (!isset($field_options[$key]) || false === $field_options[$key])
    {
      return '';
    }
//    var_dump(get_class($objField));
//    var_dump($field_options);
    if (is_array($field_options[$key]))
    {
      // TODO sfValidatorBoolean
      // TODO sfValidatorChoice
//      foreach ($field_options[$key] as $key => $val) {
//        if (empty($val)) {
//          continue;
//        } else {
//          var_dump($val);die('OK');
//        }
//      }
      return "";
    }

    if (!(isset($messages[$key]) || isset($messages[$this->parseMessageKey($key, $objField)])))
    {
      return "";
    }

    // find the actual error message
    $mapped_key = $this->parseMessageKey($key, $objField);
    if (isset($messages[$key]))
    {
      $val = $messages[$key];
    }
    elseif (isset($messages[$mapped_key]))
    {
      $val = $messages[$mapped_key];
    }
    else
    {
      return;
    }

    // add slashes to ensure correct json output
    $val = addslashes($val);

    // replace any placeholder values
    // this is a nasty hack (see line 88 for the matching hackery)
    if (strpos($val, '%value%') !== false)
    {
      $val = '[[{ return \'' . str_replace('%value%', "' + $(elem).val() + '", $val) . '\';}]]';
    }
    if (strpos($val, '%min_length%') !== false)
    {
      $val = str_replace('%min_length%', $field_options['min_length'], $val);
    }
    if (strpos($val, '%max_length%') !== false)
    {
      $val = str_replace('%max_length%', $field_options['max_length'], $val);
    }
    return $val;
  }

  private function addRule($validation_name, $rule, $value)
  {
    $this->rules[$validation_name][$rule] = $value;
  }

  private function addMessage($validation_name, $rule, $value)
  {
    if (strlen($value) > 0)
    {
      $this->messages[$validation_name][$rule] = $value;
    }
  }

  private function createValidationName($form_name, $fieldname, $is_embedded)
  {
    $field_html_name_prefix = $is_embedded ? $this->formName . '[' . $form_name . ']' : $form_name;
    $field_html_id_prefix = $is_embedded ? $this->formName . '_' . $form_name : $form_name;

    if (strlen($form_name) > 0)
    {
      $validation_name = $field_html_name_prefix . '[' . $fieldname . ']';
      $field_html_id = $field_html_id_prefix . '_' . $fieldname;
    }
    else
    {
      $validation_name = ($is_embedded ? '_' . $this->formName : '') . $fieldname;
      $field_html_id = ($is_embedded ? $this->formName . '_' : '') . $fieldname;
    }

    if ($this->firstFieldId == null)
    {
      $this->firstFieldId = $field_html_id;
    }
    return $validation_name;
  }

  public function getPostValidators($form = null, $isRecursion = false)
  {
    if (empty($this->postValidators) || $isRecursion)
    {
      if (null === $form)
      {
        $form = $this->form;
      }
      $this->addPostValidator($form);

      foreach ($form->getEmbeddedForms() as $subForm)
      {
        $this->getPostValidators($subForm, true);
      }
    }
    //dev::pr($post_validators, true);
    return sizeof($this->postValidators) > 0 ? $this->postValidators : array();
  }

  private function addPostValidator($form)
  {
    if ($postValidator = $form->getValidatorSchema()->getPostValidator())
    {
      $this->postValidators = array_merge($this->postValidators, $this->parsePostValidator($form, $postValidator));
    }
  }

  private function parsePostValidator(sfForm $form, sfValidatorBase $validator)
  {
    $options = $validator->getOptions();
    $messages = $validator->getMessages();

    $rules = array();
    $formName = $form->getName();
    $validatorName = get_class($validator);
    switch ($validatorName)
    {
      case 'sfValidatorAnd':
        $return = array();
        foreach ($validator->getValidators() as $v) {
          $return = array_merge($return, (array)$this->parsePostValidator($form, $v));
        }
        return $return;

      case 'sfValidatorDoctrineUnique':
      case 'sfValidatorPropelUnique':
        $return = array();
        foreach ($options['column'] as $column) {
          extract(sfJqueryFormValidationRules::getUrlParams());
          $rules['remote'] = sfContext::getInstance()->getController()->genUrl(
            "{$module}/remote?form=" . get_class($form) . "&validator={$validatorName}");
          $rules['messages'] = array(
            'remote' => $messages['invalid'],
          );
          $return[] = "$('#{$formName}_{$column}').rules('add', " . json_encode($rules) . ");";
        }
        return $return;

      case 'sfValidatorSchemaCompare':
        if($options['operator'] == '==' || $options['operator'] == '===') {
          $rules['equalTo'] = "#{$formName}_{$options['left_field']}";
          $rules['messages'] = array(
            'equalTo' => $messages['invalid'],
          );
          return "$('#{$formName}_{$options['right_field']}').rules('add', " . json_encode($rules) . ");";
        }
        break;
    }
    return array();
  }

  private function getOriginalFieldKey($widget_name, $key)
  {
    if ($keymap = self::$widgets[$widget_name]['keymap'])
    {
      foreach (self::$widgets[$widget_name]['keymap'] as $orig_key => $val)
      {
        if ($key == $val)
        {
          return $orig_key;
        }
      }
    }
    return false;
  }
}
