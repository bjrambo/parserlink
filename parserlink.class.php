<?php
class parserlink extends ModuleObject
{
	private static $config_cache = NULL;

	protected function getConfig()
	{
		if(self::$config_cache !== NULL)
		{
			return self::$config_cache;
		}

		/* @var $oModuleModel moduleModel */
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('parserlink');

		if(!$config)
		{
			$config = new stdClass();
		}

		self::$config_cache = $config;

		return self::$config_cache;
	}

	protected function setConfig($config)
	{
		/* @var $oModuleController moduleController */
		$oModuleController = getController('module');
		$result = $oModuleController->insertModuleConfig($this->module, $config);
		if ($result->toBool())
		{
			self::$config_cache = $config;
		}

		return $result;
	}


	function moduleInstall()
	{
		return new Object();
	}

	function checkUpdate()
	{
		return false;
	}

	function moduleUpdate()
	{
		return new Object();
	}
}
/* End of file */
