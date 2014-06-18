<?php

class Input
{
  const SELECT    = 'select';
  const TEXT      = 'text';
  const CHECKBOX  = 'checkbox';
  const RADIO     = 'radio';

  static $instancebin;

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
      $this->label = static::forHumans($options['name']);

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

  static function forHumans($str)
  {
    $words = [];

    foreach (explode('-', $str) as $word) {
      $words[] = ucfirst($word);
    }

    return implode(' ', $words);
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
    }
  }

  static function get($name)
  {
    return static::$instancebin[$name];
  }

  static function make($options)
  {
    $me = get_called_class();
    return static::$instancebin[$options['name']] = new $me($options);
  }

  static function like($similar, $options)
  {
    $instance = static::get($similar);
    if ($instance)
      $clone = clone $instance;
    else
      return -1;

    foreach ($options as $property => $value) {
      $clone->$property = $value;
    }

    return static::$instancebin[$options['name']] = $clone;
  }
}
