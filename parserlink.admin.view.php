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

		$oModuleModel = getModel('module');
		$skin_list = $oModuleModel->getSkins($this->module_path);

		Context::set('skin_list',$skin_list);
		Context::set('config', $config);
	}
}
/* End of file */
