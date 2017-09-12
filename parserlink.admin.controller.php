<?php
class parserlinkAdminController extends parserlink
{
	function init()
	{
	}

	function procParserlinkAdminInsertConfig()
	{
		/* @var $oModuleModel moduleModel */
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('module');

		if(!$config)
		{
			$config = new stdClass();
		}

		$obj = Context::getRequestVars();
		$config_vars = array(
			'use',
			'object_target',
			'skin',
			'exception',
			'print_align',
			'loading_image',
			'title_length',
			'print_domain',
			'content_length',
			'image_length',
			'internal_link',
			'external_link',
			'link_text',
			'facebook_embed',
			'twitter_embed',
			'instagram_embed',
			'youtube_embed',
			'youtube_width',
			'use_mid_list',
			'use_cache',
			'use_db_data',
			'cache_time',
		);

		foreach ($config_vars as $val)
		{
			if($obj->{$val})
			{
				$config->{$val} = $obj->{$val};
			}
		}

		$output = self::setConfig($config);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispParserlinkAdminConfig'));
		}
	}

	function procParserlinkAdminDeleteAllData()
	{
		$output = executeQuery('parserlink.deleteParserlinkAllData');
		if(!$output->toBool())
		{
			return $output;
		}

		getController('parserlink')->clearCache();

		$this->setMessage('모든 데이터를 삭제 하였습니다.');

		if(Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedire ctUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispParserlinkAdminDataList'));
		}
	}
}
/* End of file */
