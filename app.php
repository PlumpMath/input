<?php

class Input extends Factory
{
  const SELECT    = 'select';
  const TEXT      = 'text';
  const CHECKBOX  = 'checkbox';
  const RADIO     = 'radio';
  const NONE      = 'none';

  public $value = null;

  public $name;
  public $label;
  public $type;
  public $options;

  static $assignables = [
    'name',
    'label',
    'type',
    'options'
  ];

  public $template = null;
  public $validator = null;

  public function __construct($options)
  {
    foreach (static::$assignables as $assignable)
      $this->$assignable = $options[$assignable];

    if ( ! isset($options['label']))
      $this->label = NameRater::forHumans($options['name']);

    if (isset($options['default'])) {
      $this->value = $options['default'];
    }

    if (isset($options['ensure'])) {
      $head = $chain = $next = null;

      foreach ($options['ensure'] as $promise => $params) {
        $next = new ValidatorNode(ValidatorTest::get($promise), $params);
        $head = $head || $next;
        if ( ! is_null($chain))
          $chain->yes($next);
        $chain = $next;
      }
    }
  }

  public function validate()
  {
    try {
      $passes = $this->validator->run();
    } catch (ValidationException $e) {
      var_dump($e->getMessage());
      $passes = false;
    }

    return $passes;
  }

  public function ensure($name, $params = null)
  {
    $node = new ValidatorNode(ValidatorTest::get($name), $params);
    $node->belongsTo($this);
    
    if ( ! is_null($validator)) {
      $tail = $validator->traverse();
      $tail->yes($node);
    } else {
      $this->validator = $node;
    }

    return $this;
  }

  public function setValue($value)
  {
    return $this->value = $value;
  }

  static function __callStatic($name, $arguments)
  {
    switch ($name) {
      case 'text':
        return static::make(array_merge($arguments[0], ['type' => static::TEXT]));
        break;
      case 'radio':
        return static::make(array_merge($arguments[0], ['type' => static::RADIO]));
        break;
      case 'checkbox':
        return static::make(array_merge($arguments[0], ['type' => static::CHECKBOX]));
        break;
      case 'select':
        $instance = static::make(array_merge($arguments[0], ['type' => static::SELECT]));
        $instance->ensure('in options', $instance->options);
        return $instance;
        break;
      case 'none':
        return static::make(array_merge($arguments[0], ['type' => static::NONE]));
        break;
    }
  }
}
