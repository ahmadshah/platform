<?php namespace Laravel;

use BadMethodCallException;

class Presenter
{
	public $attributes = null;
	public static $name = 'resource';

	/**
	 * Create a new Presenter Collection
	 *
	 * @static
	 * @access public
	 * @param  mixed  $collection
	 * @return array
	 */
	public static function make($collection)
	{
		if ( ! is_object($collection)) return Presenter\Collection::make(get_called_class(), $collection);

		return new static($collection);
	}

	/**
	 * Create a new Presenter instance
	 *
	 * <code>
	 *		Create a new Presenter container with attributes
	 *	 	$user = new Presenter(User::find($id));
	 * </code>
	 *
	 * @access public
	 * @param  mixed $attributes
	 * @return void
	 */
	public function __construct($attributes = null)
	{
		$attributes and $this->attributes = $attributes;

		// Alias the attributes name for nicer, more meaningful access
		// Example: $this->entry, $this->post, $this->user
		$this->{static::$name} = $this->attributes;
	}

	/**
	 * Dynamically retrieve the value of an resource.
	 */
	public function __get($key)
	{
		if (method_exists($this, $key)) 
		{
			return $this->{$key}();
		} 
		else 
		{
			return $this->attributes->$key;
		}
	}

	/**
	 * Handle dynamic calls to the resource.
	 */
	public function __call($key, $args)
	{
		if (method_exists($this->attributes, $key)) 
		{
			return call_user_func_array(array($this->attributes, $key), $args);
		}
		
		throw new BadMethodCallException('Presenter: '.get_called_class().'::'.$key.' method does not exist');
	}

	public function __toString()
	{
		return $this->attributes->__toString();
	}
}