<?php
use Piton\Log\DefaultLogger as Logger;

class MaidenClockCommon extends \Maiden\MaidenDefault {

	protected function init() {
		$this->properties = $this->loadJson("properties.json");
	}

	protected function getConfigReplacements(stdClass $environment) {
		return array(
			"ProjectName" => $this->properties->application->name,
			"ProjectPath" => $environment->path,
			"CachePath" => $environment->cachePath,
			"LogPath" => $environment->logPath,
			"DataPath" => $environment->dataPath,
			"IpAddress" => $environment->ipAddress,
			"SiteDomain" => $environment->domain,
			"EmailDeveloper" => $environment->email->developer,
			"EmailSupport" => $environment->email->support,
			"DatabaseHost" => $environment->database->host,
			"DatabasePort" => $environment->database->port,
			"DatabaseName" => $environment->database->name,
			"MemcacheServer" => $environment->memcache->host,
			"DebugMode" => false
		);
	}

	/**
	 * Sets up development environment
	 */
	public function setupDevelopment() {
		$this->setupFolders("development");
		$this->buildConfigFiles($this->properties->development->path, "development");
		$this->addVhostToApache("development");
		$this->reloadApache();
	}

	/**
	 * Builds the project ready for deployement
	 */
	public function build($environmentName, $version) {
		$currentDirectory = getcwd();

		$this->logger->log("Building '$environmentName' {$this->properties->application->name}");

		//$this->clean();
		$buildPath = "build/{$version}/{$this->properties->application->name}";


		if (file_exists($buildPath)) {
			$this->logger->log("Removing existing '{$buildPath}'");
			$this->exec("rm -rf {$buildPath}");
		}

		// Build the project to this location so failures are apparent.
		$tempBuildPath = $buildPath . ".incomplete";
		if (file_exists($tempBuildPath)) {
			$this->logger->log("Removing existing incomplete build '{$tempBuildPath}'");
			$this->exec("rm -rf {$tempBuildPath}");
		}

		// Default deploy branch is master
		$deploymentBranch = "master";
		if (isset($this->properties->scm->deploymentBranch)) {
			$deploymentBranch = $this->properties->scm->deploymentBranch;
		}

		$this->logger->log("Building from version {$version} from '{$deploymentBranch}' in '{$buildPath}'");

		// This ensures that the given version exists as a tag in the repo
		$this->exec("git show {$version}", true, true);

		mkdir($tempBuildPath, 0774, true);
		//$this->exec("rsync -a --exclude='build' --exclude='.git' ./ {$buildPath}");
		$this->exec("git clone -b {$deploymentBranch} . {$tempBuildPath}");
		chdir($tempBuildPath);
		$this->exec("git checkout {$version}");
		$revision = $this->exec("git log --pretty=format:%h -b {$deploymentBranch} | head -n1 ", true, true);
		chdir($currentDirectory);

		// Create templated files
		$this->buildConfigFiles($tempBuildPath, $environmentName);

		$this->replaceTokens(array(
			"VERSION" => $version,
			"REVISION" => $revision,
			"DATE" => date("Y-m-d"),
			"TIME" => date("H:i:s"),
			"ENVIRONMENT" => $environmentName,
			"DEPLOYEDBY" => $this->getSystemUser()
		), "(.*\.php|.*\.js|.*\.css|.*\.json|.*\.tpl)$",
		"./{$tempBuildPath}/{$this->properties->site->path}");

		$this->buildConfigFiles($tempBuildPath, $environmentName);

		$this->logger->log("Moving temp buildpath '{$tempBuildPath}' to '$buildPath'", Logger::LEVEL_DEBUG);
		rename($tempBuildPath, $buildPath);
		$this->logger->log("Build complete");
	}

	protected function getSystemUser() {
		return trim(`whoami`);
	}

	public function tagRevision($version) {
		$this->logger->log("Tagging revision {$version}");
		$this->exec("git tag {$version}");
		$this->exec("git push --tags origin master");
	}

	/**
	 * Deploys a build ready for installation. Will build first if needed.
	 */
	public function deploy($environmentName, $version) {
		$this->logger->log("Deploying to '$environmentName' {$this->properties->application->name}");
		$environment = $this->getEnvironment($environmentName);

		$tempName = sys_get_temp_dir() . "/" .  md5(uniqid());
		$this->remoteExec($environmentName, "mkdir $tempName && cd $tempName && git clone -b {$this->properties->scm->deploymentBranch} {$this->properties->scm->url}");

		$this->remoteExec($environmentName, "cd $tempName && maiden build $environmentName $version");
		$this->remoteExec($environmentName, "cd $tempName && maiden install $environmentName $version");
		$this->remoteExec($environmentName, "rm -rf $tempName");
	}

	protected function remoteExec($environmentName, $command) {
		$environment = $this->getEnvironment($environmentName);
		$this->exec("ssh -p {$environment->sshPort} {$environment->host} '$command'");
	}

	/**
	 * Installs the deployed project on environment. Will deploy first if needed.
	 */
	public function install($environmentName, $version) {
		$this->logger->log("Installing '$environmentName' {$this->properties->application->name}");
		$environment = $this->getEnvironment($environmentName);
		$buildPath = "build/{$version}/{$this->properties->application->name}";

		if (!file_exists($buildPath)) {
			$this->fail("Build path not found. You probably need to build the project first.");
		}

		$currentDirectory = getcwd();

		$actualPath = $environment->path . "-{$version}";

		if (strpos($currentDirectory, $environment->path) !== false) {
			$this->fail("Install '{$environment->path}' path clashes with current directory.");
		}

		if (file_exists($actualPath)) {
			$this->fail("It looks like this version has already been installed. Please ensure you are installing the correct version. You may need to delete '$actualPath' if a previous installation failed. Only do this if you know what you are doing!");
		}

		if (file_exists($environment->path) && !is_link($environment->path)) {
			$this->fail("Install path '{$environment->path}' is not a symlink. It is likely that this project isn't installed this way." );
		}

		$this->logger->log("Copying build to '$actualPath'");
		$this->exec("cp -a {$buildPath}/ {$actualPath}/");

		if (file_exists($environment->path)) {
			$this->logger->log("Remoing existing symlink", Logger::LEVEL_DEBUG);
			unlink($environment->path);
		}

		$this->updateDatabase($environmentName, $actualPath);

		$this->logger->log("Creating symlink from '$actualPath' to '{$environment->path}'");
		symlink($actualPath, $environment->path);

		$this->addVhostToApache($environmentName);
		$this->reloadApache();

		$this->logger->log("Install Complete");

		// Put up holding page
		// Take down holding page
	}

	/**
	 * Cleans the build folder and any other created files.
	 */
	public function clean() {
		$this->logger->log("Cleaning");
		$this->logger->log("Deleting build folder", Logger::LEVEL_DEBUG);
		$this->exec("rm -rf build");
	}

	public function updateDatabase($environmentName, $path = null) {
		$this->logger->log("Updating database with deltas");

		if ($path === null) {
			$path = $environment->path;
		}

		$environment = $this->getEnvironment($environmentName);
		$databaseUpdater = new \Maiden\PostgresUpdater(
			$this->logger,
			$path . "/" . $this->properties->database->deltaPath,
			"pgsql:host={$environment->database->host} port={$environment->database->port} dbname={$environment->database->name}",
			"postgres", "");
		$databaseUpdater->update();
	}

	public function addVhostToApache($environmentName) {

		$environment = $this->getEnvironment($environmentName);
		$vhostPath = "{$this->properties->apache->vhostPath}/{$environment->domain}.conf";

		if (file_exists($vhostPath)) {
			$this->logger->log("Removing existing: {$vhostPath}", Logger::LEVEL_WARNING);
			unlink($vhostPath);
		}

		$this->logger->log("Adding vhost to apache: {$vhostPath}");
		symlink("{$environment->path}/{$this->properties->application->vhostPath}", "{$vhostPath}");
	}

	/**
	 * Get the database from the source environment
	 *
	 * @param string $srcEnvName
	 * @param string $destinationEnvironmentName
	 */
	public function copyDatabase($srcEnvName = "production", $currentEnvName = "development") {

		$this->logger->log("Copying database from '$srcEnvName' to '$currentEnvName'");
		$srcEnv = $this->getEnvironment($srcEnvName);
		$currentEnv = $this->getEnvironment($currentEnvName);

		$tempfile = tempnam(sys_get_temp_dir(), "Maid");

		$this->logger->log("Dumping data from '$srcEnvName'", Logger::LEVEL_DEBUG);
		$this->exec("ssh -p {$srcEnv->sshPort} {$srcEnv->host} " .
			"'export PGPASSWORD={$srcEnv->database->password} ; " .
			"pg_dump -U {$srcEnv->database->user} -h {$srcEnv->database->host} " .
			" -p {$srcEnv->database->port} {$srcEnv->database->name}' > $tempfile");
		$this->logger->log("Killing any connection to '$currentEnvName' database '{$currentEnv->database->name}'", Logger::LEVEL_DEBUG);
		$this->exec("sudo pkill -f 'postgres {$currentEnv->database->name}'", false);
		$this->logger->log("Dropping '$currentEnvName' database '{$currentEnv->database->name}'", Logger::LEVEL_DEBUG);
		$this->exec("export PGPASSWORD={$currentEnv->database->password}; " .
			"dropdb  -U {$srcEnv->database->user} -h {$currentEnv->database->host} -p {$currentEnv->database->port} {$currentEnv->database->name}", false);

		$this->logger->log("Creating '$currentEnvName' database '{$currentEnv->database->name}'", Logger::LEVEL_DEBUG);
		$this->exec("export PGPASSWORD={$currentEnv->database->password}; " .
			"createdb  -U {$srcEnv->database->user} -h {$currentEnv->database->host} -p {$currentEnv->database->port} {$currentEnv->database->name}");

		$this->logger->log("Restoring '$currentEnvName' database '{$currentEnv->database->name}'", Logger::LEVEL_DEBUG);
		$this->exec("cat $tempfile | export PGPASSWORD={$currentEnv->database->password}; " .
			"psql  -U {$srcEnv->database->user} -h {$currentEnv->database->host} -p {$currentEnv->database->port} {$currentEnv->database->name}", true, true);

		unlink($tempfile);
	}

	public function copyBinaryData($srcEnvName = "production", $currentEnvName = "development", $maxSize = false) {

		$this->logger->log("Copying binary data from '$srcEnvName' to '$currentEnvName'");
		$srcEnv = $this->getEnvironment($srcEnvName);
		$currentEnv = $this->getEnvironment($currentEnvName);

		// This will limit the size of files to send
		$maxSizeCondition = $maxSize === false ? "" : "--max-size=$maxSize";

		$this->$environmentNameexec("rsync --delete $maxSizeCondition -ave 'ssh -p {$srcEnv->sshPort}' {$srcEnv->host}:{$srcEnv->dataPath}/ {$currentEnv->dataPath}/", false);

		$this->logger->log("Coppy Complete");
	}

	protected function getEnvironment($environmentName) {
		if (!isset($this->properties->{$environmentName})) {
			throw new \Exception("No such environment '$environmentName'");
		}

		return $this->properties->{$environmentName};
	}

	protected function setupFolders($environmentName) {
		$environment = $this->getEnvironment($environmentName);
		$this->logger->log("Setting up application folders");
		// Check is data folder exist
		// Create data folders
		// Check if cache folders exist
		// Create cache folders
	}

	public function buildConfigFiles($path, $environmentName) {
		$environment = $this->getEnvironment($environmentName);
		$this->logger->log("Building config files from templates");
		$replacements = $this->getConfigReplacements($environment);

		$this->createFromTemplate($replacements, $path . "/" .
			$this->properties->application->bootstrapPath);

		$this->createFromTemplate($replacements, $path . "/" .
			$this->properties->application->vhostPath);
	}

	protected function createFromTemplate(array $replacements, $actualPath) {

		$templatePath = $actualPath . ".template";

		$this->logger->log("Creating template from '$templatePath'", Logger::LEVEL_DEBUG);

		if (!file_exists($templatePath)) {
			throw new Exception("Template file '{$templatePath}' doesn't exist");
		}

		// Create a copy in a temp location.
		$tempName = tempnam(sys_get_temp_dir(), "Maid");
		copy($templatePath, $tempName);

		// Replace tokens
		$fileReplacer = new \Piton\Manipulate\FileLineContentReplacer(new \Piton\Manipulate\PhpTokenReplacer());
		$fileReplacer->replace($replacements, $tempName);
		$this->logger->log("Replacing tokens", Logger::LEVEL_DEBUG);

		// Remove the existing file
		if (file_exists($actualPath)) {
			$this->logger->log("Removing '$actualPath' before recreating.", Logger::LEVEL_DEBUG);
			unlink($actualPath);
		}

		rename($tempName, $actualPath);
		$this->logger->log("Creating file '$actualPath'", Logger::LEVEL_DEBUG);

		return $this;
	}

	/**
	 * Replace tokens
	 */
	protected function replaceTokens(array $replacements, $filePattern, $path) {

		$this->logger->log("Replacing tokens in '$filePattern' at path '$path'");

		$iterator = new RegexIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)),
		 "/{$filePattern}/i");

		$fileReplacer = new \Piton\Manipulate\FileContentReplacer(new \Piton\Manipulate\PhpTokenReplacer());

		$count = count($iterator);
		foreach ($iterator as $filePath) {
			$this->logger->log("Replacing in: $filePath", Logger::LEVEL_DEBUG);
			$fileReplacer->replace($replacements, $filePath);
		}

		$this->logger->log("Replace complete in " . $count . " files");

	}

	/**
	 * Reload Apache on this environment
	 */
	public function reloadApache() {
		$this->logger->log("Reloading Apache");
		exec("sudo invoke-rc.d apache2 reload");
	}
}
