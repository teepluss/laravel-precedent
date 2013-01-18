<?php

use Laravel\Messages;

abstract class Precedent extends Eloquent {

	 /**
	* Detective Errors
	*
	* @var Laravel\Messages $errors
	*/
	public $errors;

	/**
	* Enable Cache
	*
	* @var bool $cache
	*/
	public static $cache = false;

	/**
	 * Cache driver
	 *
	 * For nommaly the driver is inherit from config.
	 *
	 * @var string $cache_driver
	 */
	public static $cache_driver = null;

	/**
	* Cache live in minutes
	*
	* @var integer $cache_ttl
	*/
	public static $cache_ttl = 15;

   /**
	* Precedent Caches
	*
	* @var array $object_cached
	*/
	public static $object_cached = array();

	/**
	* Precedent Validation Rules
	*
	* @var array $rules
	*/
	public static $rules = array();

	/**
	* Precedent Validation Messages
	*
	* @var array $messages
	*/
	public static $messages = array();

	/**
	* Create new Precedent instance
	*
	* @param  array $attributes
	* @return void
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
	public function key_cache($id)
	{
		return Str::lower(get_class($this)).'_'.$id;
	}

	/**
	 * Find a model by its primary key with improve cache in object array.
	 *
	 * @param  string  $id
	 * @param  array   $columns
	 * @return Model
	 */
	public static function find($id)
	{
		$model = new static(array(), true);

		$ckey = $model->key_cache($id);

		// Object cache on every request even not cache.
		if ( ! $result = array_get(static::$object_cached, $ckey))
		{
			if (static::$cache === true)
			{
				if ( ! $result = Cache::get($ckey))
				{
					$result = $model->query()->where(static::$key, '=', $id)->first();

					Cache::put($ckey, $result, static::$cache_ttl);
				}
			}
			else
			{
				$result = $model->query()->where(static::$key, '=', $id)->first();
			}

			array_set(static::$object_cached, $ckey, $result);
		}

		return $result;
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
				$ckey = $this->key_cache($this->id);

				// remove exists cache
				Cache::forget($ckey);
			}
		}
	}

	/**
	* Validate the Model
	*    runs the validator and binds any errors to the model
	*
	* @param  array  $rules
	* @param  array  $messages
	* @return bool
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
						$rules = array_merge($rules, $with::$messages);
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
	* Magic Method for setting Precedent attributes.
	*    ignores unchanged attibutes delegates to Eloquent
	*
	* @param  string  $key
	* @param  string|num|bool|etc  $value
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

}