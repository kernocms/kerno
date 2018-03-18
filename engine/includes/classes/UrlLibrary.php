<?php

/*
 * Copyright (C) 2009-2018 Kerno CMS
 *
 * Description: urlLibrary - manages a list of possible actions that are supported by different plugins
 *
 * @author Vitaly Ponomarev
 *
*/


/*
 First: each plugin registers it's list of supported commands and
 list & types of accepted commands.

 Each plugin may register a list of it's supported commands with specified params


 params = array with supported params
		'vars'			=> array ( <VARIABLES> )
			'<NAME>'	=> array ( <PARAMS> )
				'matchRegex'	= matching REGEX
				'descr'			= description
		'descr'			= description

*/

class urlLibrary {

	// Constructor
    public function __construct() {
		global $config;

		$this->CMD = [];
		$this->configLoaded = false;
		$this->fatalError = false;
		$this->configFileName = confroot . 'urlconf.php';
	}

	// Load config from DISK
	function loadConfig() {

		// Check if config already loaded
		if ($this->configLoaded) {
			return true;
		}

		// Try to read config file
		if (is_file($this->configFileName)) {
			// Include REC
			include $this->configFileName;
			if (!isset($urlLibrary)) {
				$this->fatalError = 1;

				return false;
			}
			$this->CMD = $urlLibrary;
		}
		$this->configLoaded = true;

		return true;
	}

	// Save config to DISK
	function saveConfig() {

		// No save if config file is not loaded
		if (!$this->configLoaded)
			return false;

		// Try to write config file
		if (($f = fopen($this->configFileName, 'w')) === false) {
			// Error
			$this->fatalError = true;

			return false;
		}

		fwrite($f, '<?php' . "\n" . '$urlLibrary = ' . var_export($this->CMD, true) . ';');
		fclose($f);

		return true;
	}

	// Register supported commands
	function registerCommand($plugin, $cmd, $params) {

		if (!$this->loadConfig()) {
			return false;
		}

		$this->CMD[$plugin][$cmd] = $params;

		return true;
	}

	// Remove recently registered command
	function removeCommand($plugin, $cmd) {

		if (!$this->loadConfig()) {
			return false;
		}

		// Check if command exists
		if (isset($this->CMD[$plugin][$cmd])) {
			unset($this->CMD[$plugin][$cmd]);

			// Check if there're no more commands for this plugin
			if (is_array($this->CMD[$plugin]) && (!count($this->CMD[$plugin]))) {
				unset($this->CMD[$plugin]);
			}
		}

		return true;
	}

	// Fetch command data
	function fetchCommand($plugin, $cmd) {

		return isset($this->CMD[$plugin][$cmd]) ? $this->CMD[$plugin][$cmd] : false;
	}

	// Extract line with most matching language
	function extractLangRec($data, $pl = '') {

		global $config;

		if (!is_array($data))
			return false;

		if ($pl == '')
			$pl = $config['default_lang'];

		if (isset($data[$pl]))
			return $data[$pl];

		return isset($data['english']) ? $data['english'] : $data[0];
	}
}