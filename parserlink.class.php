<?php
class parserlink extends ModuleObject
{
	private static $config_cache = NULL;

	private static $triggers = array(
		array('moduleHandler.init', 'parserlink', 'controller', 'triggerBeforeModuleHandlerInit', 'before'),
		array('moduleHandler.init', 'parserlink', 'controller', 'triggerAfterModuleHandlerInit', 'after'),
		array('display', 'parserlink', 'controller', 'triggerAfterDisplay', 'after'),
	);

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
		/* @var $oModuleModel moduleModel */
		$oModuleModel = getModel('module');

		foreach(self::$triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		return false;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		foreach(self::$triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new Object();
	}
}
/* End of file */
