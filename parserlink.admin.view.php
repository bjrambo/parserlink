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
		Context::set('object_cache', config('cache.type'));
		Context::set('is_rhymix', defined('RX_BASEDIR'));
	}

	function dispParserlinkAdminDataList()
	{
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_order = 'update_time';
		$args->list_count = '20';
		$args->page_count = '10';
		$output = executeQueryArray('parserlink.getParserlinkDataList', $args);
		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('parserlink', $output->data);
		Context::set('page_navigation', $output->page_navigation);
	}
	function dispParserlinkAdminSnsDataList()
	{
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_order = 'update_time';
		$args->list_count = '20';
		$args->page_count = '10';
		$output = executeQueryArray('parserlink.getParserlinkSnsDataList', $args);
		Context::set('total_count', $output->page_navigation->total_count);
		Context::set('total_page', $output->page_navigation->total_page);
		Context::set('page', $output->page);
		Context::set('parserlink', $output->data);
		Context::set('page_navigation', $output->page_navigation);
	}
}
/* End of file */
