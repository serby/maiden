<?php
namespace Maiden;
/**
 * Keeps a database updated by running all the deltas in a given directory on a given database.
 *
 * @author Paul Serby <paul.serby@clock.co.uk>
 * @copyright Clock Limited 2011
 * @license http://opensource.org/licenses/bsd-license.php New BSD License
  */
class PostgresUpdater {

	/**
	 * Logger for output
	 *
	 * @var \Piton\Log\DefaultLogger
	 */
	protected $logger;

	/**
	 * The PDO database connection string
	 *
	 * @var string
	 */
	protected $dsn;

	/**
	 * Path to the SQL delta files
	 *
	 * @var string
	 */
	protected $deltaPath;

	/**
	 * Username to authenticate with.
	 *
	 * @var string
	 */
	protected $username;

	/**
	 * Password to authenticate with.
	 *
	 * @var string
	 */
	protected $password;

	public function __construct(\Piton\Log\DefaultLogger $logger, $deltaPath, $dsn, $user, $password) {
		$this->logger = $logger;
		$this->deltaPath = $deltaPath;
		$this->dsn = $dsn;
		$this->user = $user;
		$this->password = $password;
	}

	protected function run($statements) {
		if ($this->connection->exec($statements) === false) {
			$error = $this->connection->errorInfo();
			throw new \Exception("Failed: " . print_r($error, true));
		}
	}

	protected function parseFile($filePath) {
		$filename = basename($filePath);
		if ($this->isInChangelog($filename)) {
			$this->logger->log("Skipping '{$filePath}'");
			return false;
		}

		$this->logger->log("Processing '{$filePath}'");

		$contents = file_get_contents($filePath);


		if ($pos = strpos($contents, "--//@UNDO")) {
			$contents = substr($contents, 0, $pos);
		}

		return $contents;
	}

	protected function isInChangelog($filePath) {
		$sql = <<<SQL
CREATE TABLE "DatabaseChangelog" ("Revision" serial NOT NULL,"Filename" text NOT NULL,"Created" timestamp without time zone default now(), CONSTRAINT "DatabaseChangelog_pkey" PRIMARY KEY ("Revision"));
SQL;
		$this->connection->exec($sql);

	$sql = <<<SQL
SELECT * FROM "DatabaseChangelog" WHERE "Filename" = '$filePath';
SQL;

		return $this->connection->query($sql)->rowCount();
	}

	protected function writeChangelog($filePath) {
		$filePath = $this->connection->quote($filePath);
		$sql = <<<SQL
INSERT INTO "DatabaseChangelog" ("Filename") VALUES ({$filePath});
SQL;
		$this->connection->exec($sql);
	}

	/**
	 * Update the database
	 *
	 * @return null
	 */
	public function update() {
		$this->connection = new \PDO($this->dsn, $this->user, $this->password);

		$files = array();
		foreach (new \DirectoryIterator($this->deltaPath) as $fileInfo) {
			if ($fileInfo->isDot()) {
				continue;
			}
			if (preg_match("/.*\.sql$/", $fileInfo->getFilename())) {
				$files[] = $fileInfo->getPathname();
			}
		}
		sort($files);
		$this->logger->log("Processing Deltas");
		foreach ($files as $file) {
			if ($statements = $this->parseFile($file)) {
				$this->run($statements);
				$filename = basename($file);
				$this->writeChangelog($filename);
			}
		}

		$this->connection = null;
	}
}
