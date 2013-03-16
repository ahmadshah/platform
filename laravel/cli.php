<?php namespace Laravel;

/**
 * Part of the Fuel framework.
 *
 * @package    Fuel
 * @version    1.0
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 * @link       http://fuelphp.com
 */

use UnexpectedValueException;

class CLI {

	/**
	 * Indicate that Laravel\CLI should be ignored.
	 * 
	 * @var boolean
	 */
	public static $enabled = true;

	/**
	 * Indicate Laravel\CLI is started
	 * 
	 * @var boolean
	 */
	protected static $initiated = false;

	/**
	 * PHP readline support
	 * 
	 * @var boolean
	 */
	public static $readline_support = false;

	/**
	 * List of foreground output color
	 * 
	 * @var array
	 */
	protected static $foreground_colors = array(
		'black'        => '0;30',
		'dark_gray'    => '1;30',
		'blue'         => '0;34',
		'dark_blue'    => '1;34',
		'light_blue'   => '1;34',
		'green'        => '0;32',
		'light_green'  => '1;32',
		'cyan'         => '0;36',
		'light_cyan'   => '1;36',
		'red'          => '0;31',
		'light_red'    => '1;31',
		'purple'       => '0;35',
		'light_purple' => '1;35',
		'light_yellow' => '0;33',
		'yellow'       => '1;33',
		'light_gray'   => '0;37',
		'white'        => '1;37',
	);

	/**
	 * List of background output color
	 * 
	 * @var array
	 */
	protected static $background_colors = array(
		'black'      => '40',
		'red'        => '41',
		'green'      => '42',
		'yellow'     => '43',
		'blue'       => '44',
		'magenta'    => '45',
		'cyan'       => '46',
		'light_gray' => '47',
	);

	/**
	 * Start CLI
	 * 
	 * @return void
	 */
	protected static function start() 
	{
		if (static::$initiated) return ;

		// Readline is an extension for PHP that makes interactive with PHP much more bash-like
		// http://www.php.net/manual/en/readline.installation.php
		static::$readline_support = extension_loaded('readline');

		static::$initiated = true;
	}

	/**
	 * Returns the option with the given name.	You can also give the option
	 * number.
	 *
	 * Named options must be in the following formats:
	 * php index.php user -v --v -name=John --name=John
	 *
	 * @param  mixed    $name   the name of the option (int if unnamed)
	 * @return string
	 */
	protected static function option($name, $default = null)
	{
		return get_cli_option($name, $default);
	}

	/**
	 * Get input from the shell, using readline or the standard STDIN
	 *
	 * Named options must be in the following formats:
	 * php index.php user -v --v -name=John --name=John
	 * 
	 * @param   mixed   $name   the name of the option (int if unnamed)
	 * @return  string
	 */
	protected static function input($prefix = '')
	{
		if (static::$readline_support)
		{
			return readline($prefix);
		}

		echo $prefix;

		return fgets(STDIN);
	}

	/**
	 * Asks the user for input.  This can have either 1 or 2 arguments.
	 *
	 * Usage:
	 *
	 * // Waits for any key press
	 * CLI::prompt();
	 *
	 * // Takes any input
	 * $color = CLI::prompt('What is your favorite color?');
	 *
	 * // Takes any input, but offers default
	 * $color = CLI::prompt('What is your favourite color?', 'white');
	 *
	 * // Will only accept the options in the array
	 * $ready = CLI::prompt('Are you ready?', array('y','n'));
	 *
	 * @param   string
	 * @param   mixed
	 * @return  string  the user input
	 */
	protected static function prompt()
	{
		$args      = func_get_args();

		$options   = array();
		$output    = '';
		$default   = null;

		// How many we got
		$arg_count = count($args);

		// Is the last argument a boolean? True means required
		$required  = end($args) === true;

		// Reduce the argument count if required was passed, we don't care about that anymore
		$required === true and --$arg_count;

		// This method can take a few crazy combinations of arguments, so lets work it out
		switch ($arg_count)
		{
			case 2:
				// E.g: $ready = CLI::prompt('Are you ready?', array('y','n'));
				if (is_array($args[1])) list($output, $options) = $args;

				// E.g: $color = CLI::prompt('What is your favourite color?', 'white');
				elseif (is_string($args[1])) list($output, $default) = $args;

			break;

			case 1:

				// No question (probably been asked already) so just show options
				// E.g: $ready = CLI::prompt(array('y','n'));
				if (is_array($args[0])) $options = $args[0];

				// Question without options
				// E.g: $ready = CLI::prompt('What did you do today?');
				elseif (is_string($args[0])) $output = $args[0];

			break;
		}

		// If a question has been asked with the read
		if ($output !== '')
		{
			$extra_output = '';

			if ($default !== null)
			{
				$extra_output = ' [ Default: "'.$default.'" ]';
			}
			elseif ($options !== array())
			{
				$extra_output = ' [ '.implode(', ', $options).' ]';
			}

			static::fwrite(STDOUT, $output.$extra_output.': ');
		}

		// Read the input from keyboard.
		$input = trim(static::input()) ?: $default;

		// No input provided and we require one (default will stop this being called)
		if (empty($input) and $required === true)
		{
			static::write('This is required.');
			static::enter();

			$input = forward_static_call_array(array(__CLASS__, 'prompt'), $args);
		}

		// If options are provided and the choice is not in the array, tell them to try again
		if ( ! empty($options) and ! in_array($input, $options))
		{
			static::write('This is not a valid option. Please try again.');
			static::enter();

			$input = forward_static_call_array(array(__CLASS__, 'prompt'), $args);
		}

		return $input;
	}

	/**
	 * Outputs a string to the cli.	 If you send an array it will implode them
	 * with a line break.
	 * 									
	 * @param  mixed    $text           the text to output, or array of lines
	 * @param  string   $foreground     foreground color
	 * @param  string   $background     background color
	 * @param  bool     $new_line       add PHP_EOL statement to current text
	 * @return void
	 */
	protected static function write($text = '', $foreground = null, $background = null, $new_line = true)
	{
		if (is_array($text)) $text = implode(PHP_EOL, $text);
		
		if ($foreground or $background)
		{
			$text = static::color($text, $foreground, $background);
		}

		if ($new_line) $text .= PHP_EOL;

		static::fwrite(STDOUT, $text);
	}

	/**
	 * Outputs an error to the CLI using STDERR instead of STDOUT
	 *
	 * @param  mixed    $text           the text to output, or array of errors
	 * @param  string   $foreground     foreground color
	 * @param  string   $background     background color
	 * @param  bool     $new_line       add PHP_EOL statement to current text
	 * @return void
	 */
	protected static function error($text = '', $foreground = 'light_red', $background = null, $new_line = true)
	{
		if (is_array($text)) $text = implode(PHP_EOL, $text);

		if ($foreground OR $background)
		{
			$text = static::color($text, $foreground, $background);
		}

		if ($new_line) $text .= PHP_EOL;

		static::fwrite(STDERR, $text);
	}

	/**
	 * Outputs a string to the cli.	 If you send an array it will implode them
	 * with a line break.
	 * 									
	 * @param  mixed    $text           the text to output, or array of lines
	 * @param  string   $foreground     foreground color
	 * @param  string   $background     background color
	 * @return void
	 */
	protected static function write_inline($text = '', $foreground = null, $background = null)
	{
		static::write($text, $foreground, $background, false);
	}

	/**
	 * Outputs an error to the CLI using STDERR instead of STDOUT
	 *
	 * @param  mixed    $text           the text to output, or array of errors
	 * @param  string   $foreground     foreground color
	 * @param  string   $background     background color
	 * @return void
	 */
	protected static function error_inline($text = '', $foreground = 'light_red', $background = null)
	{
		static::error($text, $foreground, $background, false);
	}

	/**
	 * Beeps a certain number of times.
	 * 
	 * @param  integer $num    the number of times to beep
	 * @return void
	 */
	protected static function beep($num = 1)
	{
		echo str_repeat("\x07", $num);
	}

	/**
	 * Waits a certain number of seconds, optionally showing a wait message and
	 * waiting for a key press.
	 * 
	 * @param  integer $seconds     number of seconds
	 * @param  bool    $countdown   show a countdown or not
	 * @return void
	 */
	protected static function wait($seconds = 0, $countdown = false)
	{
		if ($countdown === true)
		{
			$time = $seconds;

			while ($time > 0)
			{
				static::fwrite(STDOUT, $time.'... ');
				sleep(1);
				$time--;
			}

			static::write();
			return;
		}
		
		if ($seconds > 0)
		{
			sleep($seconds);
		}
		else
		{
			static::write('Press any key to continue...');
		}
	}


	/**
	 * if operating system === windows
	 *
	 * @return bool
	 */
	protected static function is_windows()
	{
		return 'win' === strtolower(substr(php_uname("s"), 0, 3));
	}

	/**
	 * Enter a number of empty lines
	 * 
	 * @param  integer	$num    Number of lines to output
	 * @return void
	 */
	protected static function enter($num = 1)
	{
		// Do it once or more, write with empty string gives us a new line
		for ($i = 0; $i < $num; $i++) static::write();
	}

	/**
	 * Clears the screen of output
	 *
	 * @return void
	 */
	protected static function clear()
	{
		static::is_windows()

			// Windows is a bit crap at this, but their terminal is tiny so shove this in
			? static::enter(40)

			// Anything with a flair of Unix will handle these magic characters
			: static::fwrite(STDOUT, chr(27)."[H".chr(27)."[2J");
	}

	/**
	 * Returns the given text with the correct color codes for a foreground and
	 * optionally a background color.
	 * 
	 * @param  string   $text           the text to color
	 * @param  string   $foreground     the foreground color
	 * @param  string   $background     the background color
	 * @return string                   the color coded string
	 */
	protected static function color($text, $foreground, $background = null)
	{
		if (static::is_windows() and ! Request::server('ANSICON'))
		{
			return $text;
		}

		if ( ! array_key_exists($foreground, static::$foreground_colors))
		{
			throw new UnexpectedValueException("Invalid CLI foreground color: {$foreground}");
		}

		if ( $background !== null and ! array_key_exists($background, static::$background_colors))
		{
			throw new UnexpectedValueException("Invalid CLI background color: {$background}");
		}

		$string = "\033[".static::$foreground_colors[$foreground]."m";

		if ($background !== null)
		{
			$string .= "\033[".static::$background_colors[$background]."m";
		}

		$string .= $text."\033[0m";

		return $string;
	}

	/**
	* Spawn Background Process
	*
	* Launches a background process (note, provides no security itself, $call must be sanitised prior to use)
	* 	
	* @param  string    $call   the system call to make
	* @return void
	* @author raccettura
	* @link   http://robert.accettura.com/blog/2006/09/14/asynchronous-processing-with-php/
	*/
	protected static function spawn($call, $output = '/dev/null')
	{
		// Windows
		if (static::is_windows()) pclose(popen('start /b '.$call, 'r'));

		// Some sort of UNIX
		else pclose(popen($call.' > '.$output.' &', 'r'));
	}

	/**
	 * Echo or use STDOUT based on static::$enabled value.
	 *
	 * @static
	 * @access protected
	 * @param  string   $type
	 * @param  string   $text
	 * @return void
	 */
	protected static function fwrite($type, $text)
	{
		if (static::$enabled) 
		{
			fwrite($type, $text);
		}
		else 
		{
			echo $text;
		}
	}

	/**
	 * Magic method to ensure Laravel\CLI is only accessible from PHP CLI
	 */
	public static function __callStatic($method, $parameters)
	{
		if (Request::cli())
		{
			static::start();

			return forward_static_call_array(array('static', $method), $parameters);
		}
	}
}