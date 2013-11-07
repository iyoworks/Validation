<?php namespace Iyoworks\Validation;

abstract class BaseValidator
{
	/**
	 * @var \Illuminate\Validation\Factory
	 */
	public static $factory;

	/**
	 * @var \Iyoworks\Repository\Validation\Validator
	 */
	protected $runner;

	/**
	 * @var \Illuminate\Support\MessageBag
	 */
	protected $errors;

	/**
	* @var array
	*/
	protected $data = [];

	/**
	* @var array
	*/
	protected $rules = [];

	/**
	* @var array
	*/
	protected $messages = [];

	/**
	* @var boolean
	*/
	protected $mode = false;

	/**
	* @var string
	*/
	const 	MODE_INSERT = 'preValidateOnInsert';

	/**
	* @var string
	*/
	const 	MODE_UPDATE = 'preValidateOnUpdate';

	/**
	* @var string
	*/
	const 	MODE_DELETE = 'preValidateOnDelete';

	/**
	 * if enabled all rules will be used, even if corresponding data attribute is absent
	 * @var boolean
	 */
	protected $strict = false;

	/**
	 * Called before validation
	 * @return void
	 */
	protected function preValidate() {}

	/**
	 * Called when mode is insert and after runner has been created
	 * @return void
	 */
	protected function preValidateOnInsert() {}

	/**
	 * Called when mode is update and after runner has been created
	 * @return void
	 */
	protected function preValidateOnUpdate() {}

	/**
	 * Called when mode is delete and after runner has been created
	 * @return void
	 */
	protected function preValidateOnDelete() {}

	/**
	 * Run the validator
	 * @param  mixed $data
	 * @return bool  
	 */
	public function isValid($data)
	{
		$this->data = $data;
		
		$this->preValidate();	
		
		//if a mode has been set, call the corresponding function
		if($this->mode) $this->{$this->mode}();

		//check if I only validate necessary attributes
		$_rules = !$this->strict ? array_intersect_key($this->rules, $this->data) : $this->rules;

		// construct the runner	
		$this->runner = static::$factory->make($this->data, $_rules, $this->messages);

		//determine if any errors occured
		if(!$this->runner->passes())
		{
			$this->errors = $this->runner->messages();
			return false;
		}
		
		return true;
	}

	/**
	 * Set mode to insert
	 * @return \Iyoworks\Repository\BaseValidator
	 */
	public function validForInsert($data)
	{
		$this->mode = static::MODE_INSERT;
		return $this->isValid($data);
	}

	/**
	 * Set mode to update
	 * @return \Iyoworks\Repository\BaseValidator
	 */
	public function validForUpdate($data)
	{
		$this->mode = static::MODE_UPDATE;
		return $this->isValid($data);
	}

	/**
	 * Set mode to delete
	 * @return \Iyoworks\Repository\BaseValidator
	 */
	public function validForDelete($data)
	{
		$this->mode = static::MODE_DELETE;
		return $this->isValid($data);
	}

	/**
	 * Set the ID or value for a unique rule
	 * 	$this->setUnique('name', 'id') - get the name rule and append $this->data['id'] to it
	 * 	$this->setUnique('name', 4, true) - get the name rule and append 4 to it
	 * 	NOTE: rule should have table column already appended to it.
	 * @param string  $key       data attribute name
	 * @param mixed  $value     
	 * @param boolean $useActual use the actual $value
	 * @param string  $rkey      rule to apply changes to
	 */
	public function setUnique($key, $value = null, $useActual = false, $rkey = 'unique')
	{
		$rule = $this->rules[$key];
		$start = strpos($rule, $rkey);
		$end = strpos($rule, '|', $start) ?: strlen($rule);
		$uniqueRuleOrig = $uniqueRule = substr ($rule , strpos($rule, $rkey), $end-$start);
		if (!$useActual and $value)
			$value = $this->get($key);
		elseif( !$useActual and !$value)
			$value = $this->get('id');
		$uniqueRule = rtrim($uniqueRule, ',').','. $value;
		$rule = str_replace($uniqueRuleOrig, $uniqueRule, $rule);
		$this->rules[$key] = $rule;
		return $this;
	}

	/**
	 * Get the errors
	 * @return  \Illuminate\Support\MessageBag|mixed
	 */
	public function errors()
	{
		if (is_null($this->errors))
			$this->errors = new \Illuminate\Support\MessageBag;
		return $this->errors;
	}

	/**
	 * Get the errors
	 * @return mixed
	 */
	public function errorMsg()
	{
		return implode(' ', $this->errors()->all());
	}

	/**
	 * Get the validator instance
	 * @return \Illuminate\Validation\Validator
	 */
	public function getRunner()
	{
		return $this->runner;
	}
	
	/**
	 * Merge data into the existing data set 
	 * @param  mixed $key     
	 * @param  mixed $value
	 * @return \Iyoworks\Repository\BaseValidator
	 */
	protected function addData($data, $value = null)
	{
		if($value) 
			$this->data[$data] = $value;
		else 
			$this->data = array_merge_recursive($this->data, (array) $data);
		return $this;
	}

	/**
	 * Set a value 
	 * @param  string $key     
	 * @param  mixed $value
	 * @return \Iyoworks\Repository\BaseValidator
	 */
	public function set($key, $value)
	{
		array_set($this->data, $key, $value);
		return $this;
	}

	/**
	 * Get a value from data
	 * @param  string $key     
	 * @param  mixed $default
	 * @return mixed
	 */
	public function get($key, $default = null)
	{
		return array_get($this->data, $key, $default);
	}

	/**
	 * Get the messages
	 * @return array
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	/**
	 * Get the rules
	 * @return array
	 */
	public function getRules()
	{
		return $this->rules;
	}

	/**
	 * Get a the data container
	 * @return array
	 */	
	public function getData()
	{
		return $this->data;
	}

	/**
	* Clear the data container
	* @return \Iyoworks\Repository\BaseValidator
	*/
	public function resetData()
	{
		$this->data = [];
		return $this;
	}

	/**
	 * Set strict mode
	 * @return \Iyoworks\Repository\BaseValidator
	 */
	public function strict()
	{
		$this->strict = true;
		return $this;
	}

	/**
	 * UnSet strict mode
	 * @return \Iyoworks\Repository\BaseValidator
	 */
	public function relaxed()
	{
		$this->strict = false;
		return $this;
	}

	/**
	 * Get the mode
	 * @return string
	 */
	public function getMode()
	{
		return $this->mode;
	}
}
