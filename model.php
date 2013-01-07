<?php

abstract class Precedent extends Eloquent {

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
	* Detective Caches 
	*
	* @var array $object_cached
	*/
	public static $object_cached = array();
	
	/** 
	 * Get uniqe key
	 *
	 * @param  integer
	 * @param  string
	 */
	public function key_cache($id, $array = array())
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
	public function _find($id, $columns = array('*'))
	{		
		$ckey = $this->key_cache($id, $columns);
		
		if ( ! $result = array_get(static::$object_cached, $ckey))
		{		
			if (static::$cache === true)
			{
				if ( ! $result = Cache::get($ckey))
				{
					$result = $this->query()->where(static::$key, '=', $id)->first($columns);
					if ($result)
					{
						Cache::put($ckey, $result, static::$cache_ttl);
					}
				}			
			}
			else
			{
				$result = $this->query()->where(static::$key, '=', $id)->first($columns);
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
		
}