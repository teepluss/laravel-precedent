<?php

use Laravel\Messages;

abstract class Precedent extends Eloquent {

	/**
	 * Precedent Errors
	 *
	 * @var Laravel\Messages
	 */
	public $errors;

	/**
	 * Cache enabled
	 *
	 * @var boolean
	 */
	public static $cache = false;

	/**
	 * Cache driver
	 *
	 * For nommaly the driver is inherit from config.
	 *
	 * @var string
	 */
	public static $cache_driver = null;

	/**
	 * Cache live in minutes
	 *
	 * @var integer
	 */
	public static $cache_ttl = 15;

   /**
	* Precedent Caches
	*
	* @var array
	*/
	public static $object_cached = array();

	/**
	 * Precedent validate rules
	 *
	 * @var array
	 */
	public static $rules = array();

	/**
	 * Precedent Validation Messages
	 *
	 * @var array
	 */
	public static $messages = array();

	/**
	 * Create new Precedent instance
	 *
	 * @param array   $attributes
	 * @param boolean $exists
	 */
	public function __construct($attributes = array(), $exists = false)
	{
		// initialize empty messages object
		$this->errors = new Messages();

		parent::__construct($attributes, $exists);
	}

	/**
	 * Get uniqe key
	 *
	 * @param  integer $id
	 * @param  string  $array
	 */
	public static function key_cache($id)
	{
		return Str::lower(get_called_class()).'_'.$id;
	}

	/**
	 * Override fire_event to remove cache, if cache enabled.
	 *
	 * @param  string  $event
	 * @return void
	 */
	protected function fire_event($event)
	{
		parent::fire_event($event);

		// if cache enabled
		if (static::$cache === true)
		{
			// events to detect
			if (in_array($event, array('updated', 'saved', 'deleted')))
			{
				$ckey = static::key_cache($this->id);				

				// remove exists cache
				Cache::forget($ckey);
			}
		}
	}

	/**
	 * Validate the Model
	 *
	 * runs the validator and binds any errors to the model
	 *
	 * @param  array  $data
	 * @param  array  $withs
	 * @param  array  $rules
	 * @param  array  $messages
	 * @return boolean
	 */
	public function valid($data = array(), $withs = array(), $rules = array(), $messages = array())
	{
		$valid = true;

		if ( ! empty($rules) || ! empty(static::$rules))
		{
			// If empty rules, so get from static.
			if (empty($rules))
			{
				$rules = static::$rules;

				// Merge validation rules from related.
				if (count($withs)) foreach ($withs as $with)
				{
					if (class_exists($with))
					{
						$rules = array_merge($rules, $with::$rules);
					}
				}
			}

			// If empty messages, so get from static.
			if (empty($messages))
			{
				$messages = static::$messages;

				// Merge validation messages from related.
				if (count($withs)) foreach ($withs as $with)
				{
					if (class_exists($with))
					{
						$messages = array_merge($messages, $with::$messages);
					}
				}
			}

			// If the model exists, this is an update.
			if ($this->exists)
			{

				$_data = array();
				foreach ($data as $key => $value)
				{
				    if ( ! array_key_exists($key, $this->original) or $value != $this->original[$key])
				    {
				        $_data[$key] = $value;
				    }
				}

				// Then just validate the fields that are being updated.
				$rules = array_intersect_key($rules, $_data);
			}


			// Construct the validator
			$validator = Validator::make($data, $rules, $messages);

			// Validate.
			$valid = $validator->valid();

			// If the model is valid, unset old errors.
			// Otherwise set the new ones.
			if ($valid)
			{
				$this->errors->messages = array();
			}
			else
			{
				$this->errors = $validator->errors;
			}
		}

		return $valid;
	}

	/**
	 * Get the query for a many-to-many relationship.
	 *
	 * This method will add some rack feature on eloquent
	 * sync method can be add with attributes.
	 *
	 * @param  string        $model
	 * @param  string        $table
	 * @param  string        $foreign
	 * @param  string        $other
	 * @return Has_Many_And_Belongs_To
	 */
	public function has_many_and_belongs_to($model, $table = null, $foreign = null, $other = null)
	{
		return new \Has_Many_And_Belongs_To($this, $model, $table, $foreign, $other);
	}

	/**
	 * Magic Method for setting Precedent attributes.
	 *
	 * ignores unchanged attibutes delegates to Eloquent
	 *
	 * @param  string $key
	 * @param  string|num|bool|etc $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		// only update an attribute if there's a change
		if (!array_key_exists($key, $this->attributes) || $value !== $this->$key)
		{
			parent::__set($key, $value);
		}
	}
	
	/**
	 * Call static method.
	 *
	 * Embeded cache with find method.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return Object
	 */
	public static function __callStatic($method, $parameters)
	{
		if (strcmp($method, 'find') === 0 and ! isset($parameters[1]))
		{
			$id = $parameters[0];
			
			$ckey = static::key_cache($id);

			if ( ! $result = array_get(static::$object_cached, $ckey))
			{
				if (static::$cache === true)
				{					
					if ( ! $result = Cache::get($ckey))
					{
						$result = parent::__callStatic('find', $parameters);
						 
						if ( ! is_null($result))
						{						
							Cache::put($ckey, $result, static::$cache_ttl);
						}
					}
				}
				else
				{
					$result = parent::__callStatic('find', $parameters); 
				}

				array_set(static::$object_cached, $ckey, $result);
			}
			
			return $result;
		}
	
		return parent::__callStatic($method, $parameters); 
	}

}