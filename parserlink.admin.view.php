<?php
class parserlinkAdminView extends parserlink
{
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(strtolower(str_replace('dispParserlinkAdmin', '', $this->act)));
	}

	function dispParserlinkAdminConfig()
	{
		$config = self::getConfig();

		/* @var $oModuleModel moduleModel */
		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);

		$mid_list = $oModuleModel->getMidList();

		Context::set('mid_list', $mid_list);
		Context::set('skin_list', $skin_list);
		Context::set('config', $config);
		Context::set('object_cache_available', preg_match('/^(apc|file|memcache|redis|wincache|xcache|sqlite)/', Context::getDBInfo()->use_object_cache));
	}
}
/* End of file */
