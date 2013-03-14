<?php namespace Laravel\Auth\Drivers;

use Laravel\Hash;
use Laravel\Config;
use Laravel\Database as DB;

class Fluent extends Driver {

	/**
	 * Get the current user of the application.
	 *
	 * If the user is a guest, null should be returned.
	 *
	 * @param  int  $id
	 * @return mixed|null
	 */
	public function retrieve($id)
	{
		if (filter_var($id, FILTER_VALIDATE_INT) !== false)
		{
			return DB::table(Config::get('auth.table'))->find($id);
		}
	}

	/**
	 * Attempt to log a user into the application.
	 *
	 * @param  array $arguments
	 * @return void
	 */
	public function attempt($arguments = array())
	{
		$user = $this->table()->where(function($query) use ($arguments)
		{
			$username = (array) Config::get('auth.username');
			
			if (count($username) === 1) 
			{
				$query->where($username[0], '=', $arguments['username']);
			}
			else
			{
				$query->where(function($q) use ($username, $arguments)
				{
					$first = array_shift($username);
					$q->where($first, '=', $arguments['username']);

					foreach ($username as $un)
					{
						$q->or_where($un, '=', $arguments['username']);
					}
				});
			}

			foreach(array_except($arguments, array('username', 'password', 'remember')) as $column => $val)
			{
				$query->where($column, '=', $val);
			}
		})->first();

		// If the credentials match what is in the database we will just
		// log the user into the application and remember them if asked.
		$password = $arguments['password'];

		$password_field = Config::get('auth.password', 'password');

		if ( ! is_null($user) and Hash::check($password, $user->{$password_field}))
		{
			return $this->login($user->id, array_get($arguments, 'remember'));
		}

		return false;
	}

	/**
	 * Get the user from the database table.
	 *
	 * @param  array  $arguments
	 * @return mixed
	 */
	protected function table()
	{
		$table = Config::get('auth.table');

		return DB::table($table);
	}

}