<?php
class parserlink extends ModuleObject
{
	private static $config_cache = NULL;

	private static $triggers = array(
		array('moduleHandler.init', 'parserlink', 'controller', 'triggerAfterModuleHandlerInit', 'after'),
	);

	private static $delete_triggers = array(
		array('moduleHandler.init', 'parserlink', 'controller', 'triggerBeforeModuleHandlerInit', 'before'),
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

		if(!$config->use)
		{
			$config->use = 'N';
		}

		if ($config->use_db_data !== 'yes')
		{
			$config->use_cache = 'no';
			$config->cache_time = 0;
		}

		self::$config_cache = $config;

		return self::$config_cache;
	}

	protected function setConfig($config)
	{
		/* @var $oModuleController moduleController */
		$oModuleController = getController('module');

		if ($config->use_db_data !== 'yes')
		{
			$config->use_cache = 'no';
			$config->cache_time = 0;
		}

		$result = $oModuleController->insertModuleConfig($this->module, $config);
		if ($result->toBool())
		{
			self::$config_cache = $config;
		}

		return $result;
	}


	function moduleInstall()
	{
		return $this->makeObject();
	}

	function checkUpdate()
	{
		/* @var $oModuleModel moduleModel */
		$oModuleModel = getModel('module');

		foreach(self::$triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		foreach (self::$delete_triggers as $trigger)
		{
			if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		return false;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		/** @var  $oModuleController moduleController */
		$oModuleController = getController('module');
		foreach(self::$triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		foreach(self::$delete_triggers as $trigger)
		{
			if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return $this->makeObject();
	}

	protected function getCacheHandler()
	{
		static $oCacheHandler = null;
		if($oCacheHandler === null)
		{
			if (self::getConfig()->use_cache !== 'yes')
			{
				$oCacheHandler = false;
			}
			else
			{
				$oCacheHandler = CacheHandler::getInstance('object');

				if (!$oCacheHandler->isSupport())
				{
					$oCacheHandler = false;
				}
			}
		}

		return $oCacheHandler;
	}

	/**
	 * Create new Object for php7.2
	 * @param int $code
	 * @param string $msg
	 * @return BaseObject|Object
	 */
	public function makeObject($code = 0, $msg = 'success')
	{
		return class_exists('BaseObject') ? new BaseObject($code, $msg) : new Object($code, $msg);
	}
}
/* End of file */
