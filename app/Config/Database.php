<?php namespace Config;

/**
 * Database Configuration
 *
 * @package Config
 */

class Database extends \CodeIgniter\Database\Config
{
	/**
	 * The directory that holds the Migrations
	 * and Seeds directories.
	 *
	 * @var string
	 */
	public $filesPath = APPPATH . 'Database/';

	/**
	 * Lets you choose which connection group to
	 * use if no other is specified.
	 *
	 * @var string
	 */
	public $defaultGroup = 'default';

	/**
	 * The default database connection.
	 *
	 * @var array
	 */
	public $default = [
		'DSN'      => '',
		'hostname' => 'postgres',       // Docker service name
		'username' => '',               // loaded from .env
		'password' => '',               // loaded from .env
		'database' => '',               // loaded from .env
		'DBDriver' => 'Postgre',        // was MySQLi
		'DBPrefix' => '',
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'cacheOn'  => false,
		'cacheDir' => '',
		'charset'  => 'utf8',
		'DBCollat' => '',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 5432,             // was 3306
	];

	/**
	 * Archive database connection (Tier 2 — Phase 10).
	 *
	 * @var array
	 */
	public $archive = [
		'DSN'      => '',
		'hostname' => 'postgres_archive',
		'username' => '',
		'password' => '',
		'database' => 'rooibok_archive',
		'DBDriver' => 'Postgre',
		'DBPrefix' => '',
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'cacheOn'  => false,
		'cacheDir' => '',
		'charset'  => 'utf8',
		'DBCollat' => '',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 5432,
	];

	/**
	 * This database connection is used when
	 * running PHPUnit database tests.
	 *
	 * @var array
	 */
	public $tests = [
		'DSN'      => '',
		'hostname' => '127.0.0.1',
		'username' => '',
		'password' => '',
		'database' => ':memory:',
		'DBDriver' => 'SQLite3',
		'DBPrefix' => 'db_',
		'pConnect' => false,
		'DBDebug'  => (ENVIRONMENT !== 'production'),
		'cacheOn'  => false,
		'cacheDir' => '',
		'charset'  => 'utf8',
		'DBCollat' => '',
		'swapPre'  => '',
		'encrypt'  => false,
		'compress' => false,
		'strictOn' => false,
		'failover' => [],
		'port'     => 5432,
	];

	//--------------------------------------------------------------------

	public function __construct()
	{
		parent::__construct();

		// Load database credentials from environment variables
		if ($host = getenv('DB_HOST')) {
			$this->default['hostname'] = $host;
		}
		if ($name = getenv('DB_NAME')) {
			$this->default['database'] = $name;
		}
		if ($user = getenv('DB_USER')) {
			$this->default['username'] = $user;
		}
		if ($pass = getenv('DB_PASS')) {
			$this->default['password'] = $pass;
		}

		// Archive DB uses same credentials
		if ($user = getenv('DB_USER')) {
			$this->archive['username'] = $user;
		}
		if ($pass = getenv('DB_PASS')) {
			$this->archive['password'] = $pass;
		}

		// Ensure that we always set the database group to 'tests' if
		// we are currently running an automated test suite, so that
		// we don't overwrite live data on accident.
		if (ENVIRONMENT === 'testing')
		{
			$this->defaultGroup = 'tests';

			if ($group = getenv('DB'))
			{
				if (is_file(TESTPATH . 'travis/Database.php'))
				{
					require TESTPATH . 'travis/Database.php';

					if (! empty($dbconfig) && array_key_exists($group, $dbconfig))
					{
						$this->tests = $dbconfig[$group];
					}
				}
			}
		}
	}

	//--------------------------------------------------------------------

}
