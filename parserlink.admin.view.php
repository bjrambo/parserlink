<?php
class parserlinkAdminView extends parserlink
{
	function init()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile(str_replace('dispParserlinkAdmin', '', $this->act));
	}


}
/* End of file */
