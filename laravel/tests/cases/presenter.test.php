<?php

use Laravel\Fluent;
use Laravel\Presenter;

class PresenterTest extends PHPUnit_Framework_TestCase {

	/**
	 * Test the Presenter constructor.
	 *
	 * @group laravel
	 */
	public function testAttributesAreSetByConstructor()
	{
		$fluent    = new Fluent(array('name' => 'Taylor', 'age' => 25));
		$presenter = new Presenter($fluent);

		$this->assertEquals($fluent->name, $presenter->name);
		$this->assertEquals($fluent->age, $presenter->age);
	}

	/**
	 * Test the Presenter::__call magic method.
	 *
	 * @group laravel
	 */
	public function testCallReturnValidAttributes()
	{
		$fluent    = new Fluent(array('name' => 'Taylor', 'age' => 25));
		$presenter = UserStubPresenter::make($fluent);

		$this->assertEquals(strtoupper($fluent->name), $presenter->get_name());
		$this->assertEquals(strtoupper($fluent->name), $presenter->get_name);
	}

	/**
	 * Test the PresenterCollection.
	 *
	 * @group laravel
	 */
	public function testCollectionReturnValidAttributes()
	{
		$fluents    = array(
			new Fluent(array('name' => 'Taylor', 'age' => 25)),
			new Fluent(array('name' => 'Otwell', 'age' => 25)),
		);

		$presenters = UserStubPresenter::make($fluents);
	
		foreach ($presenters as $key => $presenter)
		{
			$this->assertEquals(strtoupper($fluents[$key]->name), $presenter->get_name());
			$this->assertEquals(strtoupper($fluents[$key]->name), $presenter->get_name);
		}
	}
}

class UserStubPresenter extends Laravel\Presenter 
{
	public static $name = 'user';

	public function get_name()
	{
		return strtoupper($this->user->name);
	}
}