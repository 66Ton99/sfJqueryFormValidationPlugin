<?php
  
class sfJqueryFormValidationRules
{
  private $forms = array();
  private $rules = array();
  private $messages = array();
  private $first_field_id = null;
  private $form_name = null;
  private static $url_params = array(
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
  
  public static function getUrlParams()
  {
    return self::$url_params;
  }
  
  private static $keymap = array(
     'min_length' => 'minlength',
     'max_length' => 'maxlength',
  );
  
  public function __construct(sfForm $form)
  {
    
    // if an alternative date method has been specified, update the static widget array
    if(strlen(sfConfig::get('app_sf_jquery_form_validation_date_method')) > 0)
    {
      self::$widgets['sfValidatorDate']['rules'] = array(sfConfig::get('app_sf_jquery_form_validation_date_method') => true);
      self::$widgets['sfValidatorDate']['keymap'] = array('date_format' => 'bad_format');
      self::$widgets['sfValidatorDate']['msgmap'] = array('date_format' => sfConfig::get('app_sf_jquery_form_validation_date_method'));
    }
          
    $this->processValidationRules($form->getName(), $form);
    $this->form_name = $form->getName();
    
    $this->forms[] = $form;
  }
  
  public function addEmbeddedForm($name, sfForm $form)
  {
    $this->processValidationRules($name, $form, true);
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
    return $this->first_field_id;
  }
  
  public function processValidationRules($name, sfForm $form, $is_embedded = false)
  {
    foreach($form->getValidatorSchema()->getFields() as $fieldname => $objField)
    {
      
      // ignore the csrf field
      if($fieldname == '_csrf_token') continue;
      
      // get the correct html "name" for this field
      $validation_name = $this->createValidationName($name, $fieldname, $is_embedded);
    
      $this->processRules($validation_name, $objField);
      $this->processMessages($validation_name, $objField);
    }

  }
  
  /**
   * Process rules
   *
   * @param string $validation_name
   * @param sfValidatorBase $objField
   *
   * @return void
   */
  private function processRules($validation_name, sfValidatorBase $objField)
  {
    if ($objField instanceof sfValidatorSchema) {
      foreach ($objField->getFields() as $subValidatorName => $subObjField) {
        $this->processRules($subValidatorName, $subObjField);
      }
      return;
    }
    
    $field_options = $objField->getOptions();
    
    // process the common rules for all widets
    if($field_options['required'])
    {
     $this->addRule($validation_name, 'required', true);
    }
    if(isset($field_options['max_length']))
    {
      $this->addRule($validation_name, 'maxlength', $field_options['max_length']);
    }
    if(isset($field_options['min_length']))
    {
     $this->addRule($validation_name, 'minlength', $field_options['min_length']);
    }
    
    // TODO - add support for sfValidatorAnd and sfValidatorOr
    //if(get_class($objField) == 'sfValidatorAnd');
      
    // now add widget specific rules
    foreach(self::$widgets as $widget_name => $properties)
    {
       if($widget_name == get_class($objField))
       {
          foreach($properties['rules'] as $key => $val)
          {
            
            // if there's a dynamic placehold in the value, do a replace for the real value
             if(preg_match('/%(.*)%/', $val, $matches) > 0)
             {
               // remove the slash because it breaks the javascript regex syntax
               // (hopefully removing the slash doesn't break anything else in the future)
               $val = str_replace('/', '', $field_options[$matches[1]]);
             }
             
             // if there is value replacements for this field, action them now
            if(!$original_key = $this->getOriginalFieldKey($widget_name, $key))
            {
              $original_key = $key;
            }
            if(isset($field_options[$original_key]))
            {
              if(isset($properties['valmap'][$original_key][$field_options[$original_key]]))
              {
                $val = $properties['valmap']['mime_types'][$field_options[$original_key]];
              }                
            }      
             
             // add the validation rule
             $this->addRule($validation_name, $key, $val);
             
          }
       }
    }
  }
  
  private function processMessages($validation_name, sfValidatorBase $objField)
  {
    foreach($objField->getOptions() as $key => $val)
    {
      $this->addMessage($validation_name, $this->outputMessageKey($key, $objField), $this->parseMessageVal($key, $objField));
    }
  }
  
  private function parseMessageKey($key, sfValidatorBase $objField)
  {
    $class = get_class($objField);
    if(isset(self::$widgets[$class]['keymap'][$key]))
    {
      $key = self::$widgets[$class]['keymap'][$key];
    }
    elseif(isset(self::$keymap[$key]))
    {
      $key = self::$keymap[$key];
    }
    return $key;
  }
  
  private function outputMessageKey($key, sfValidatorBase $objField)
  {
    $class = get_class($objField);
    if(isset(self::$widgets[$class]['msgmap'][$key]))
    {
      $key = self::$widgets[$class]['msgmap'][$key];
    }
    elseif(isset(self::$keymap[$key]))
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

    if ($objField instanceof sfValidatorSchema) {
      $retrunVal = '';
      foreach ($objField->getFields() as $subKey => $subObjField) {
        $retrunVal .= $this->parseMessageVal($subKey, $subObjField);
      }
      return $retrunVal;
    }
    
    $field_options = $objField->getOptions();
    $messages = $objField->getMessages();
    $val = "";
    
    // if the field options for this item is empty, don't include it
    if (empty($field_options[$key])) return "";
//    var_dump(get_class($objField));
//    var_dump($field_options);
    if (is_array($field_options[$key])) {
      // TODO sfValidatorBoolean
//      foreach ($field_options[$key] as $key => $val) {
//        if (empty($val)) {
//          continue;
//        } else {
//          var_dump($val);die('OK');
//        }
//      }
      return "";
    }
    
    if(!(isset($messages[$key]) || isset($messages[$this->parseMessageKey($key, $objField)]))) return "";
    
    // find the actual error message
    $mapped_key = $this->parseMessageKey($key, $objField);
    if(isset($messages[$key]))
    {
      $val = $messages[$key];
    }
    else if(isset($messages[$mapped_key]))
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
    if(strpos($val, '%value%') !== false)
    {
      $val = '[[{ return \'' . str_replace('%value%', "' + $(elem).val() + '", $val) . '\';}]]';
    }
    
    if(strpos($val, '%min_length%') !== false)
    {
      $val = str_replace('%min_length%', $field_options['min_length'], $val);
    }     

    if(strpos($val, '%max_length%') !== false)
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
    if(strlen($value) > 0)
      $this->messages[$validation_name][$rule] = $value;
  }
  
  private function createValidationName($form_name, $fieldname, $is_embedded)
  {

    $field_html_name_prefix = $is_embedded ? $this->form_name . "[$form_name]" : $form_name;
    $field_html_id_prefix = $is_embedded ? $this->form_name . "_$form_name" : $form_name;

      if(strlen($form_name) > 0)
      {
        $validation_name = $field_html_name_prefix . "[$fieldname]";
        $field_html_id = $field_html_id_prefix . '_' . $fieldname;
      }
      else 
      {
        $validation_name = ($is_embedded ? "_$this->form_name" : '') . $fieldname;
        $field_html_id = ($is_embedded ? "$this->form_name_" : '') . $fieldname;
      }
      
      if($this->first_field_id == null)
      {
        $this->first_field_id = $field_html_id;
      }  
      return $validation_name;
  }
  
  public function getPostValidators()
  {
    $post_validators = array();
    foreach($this->forms as $form)
    {
      if($pv = $form->getValidatorSchema()->getPostValidator())
      {
        $post_validators = array_merge($post_validators, $this->parsePostValidator($form, $pv));
      }
    }
    //dev::pr($post_validators, true);
    return sizeof($post_validators) > 0 ? $post_validators : null;
  }
  
  private function parsePostValidator(sfForm $form, sfValidatorBase $validator)
  {
    
    
    $options = $validator->getOptions();
    $messages = $validator->getMessages();
    
    $rules = array();
    $formName = $form->getName();
    $validatorName = get_class($validator);
    switch($validatorName)
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
  }
  
  private function getOriginalFieldKey($widget_name, $key)
  {
    if($keymap = self::$widgets[$widget_name]['keymap'])
    {
      foreach(self::$widgets[$widget_name]['keymap'] as $orig_key => $val)
      {
        if($key == $val)
        {
          return $orig_key;
        }
      }
    }
    return false;
  }
  
}