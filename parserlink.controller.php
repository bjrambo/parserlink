<?php

class parserlinkController extends parserlink
{
	function triggerAfterModuleHandlerInit()
	{
		$config = self::getConfig();
		if ($config->use !== 'Y')
		{
			return $this->makeObject();
		}

		$document_srl = Context::get('document_srl');
		if (!$document_srl)
		{
			return $this->makeObject();
		}

		/* @var $oDocumentModel documentModel */
		$oDocumentModel = getModel('document');

		/** @var  $oModuleModel moduleModel */
		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByDocumentSrl($document_srl);

		if (is_array($config->use_mid_list) && in_array($module_info->module_srl, $config->use_mid_list))
		{
			return $this->makeObject();
		}

		$oDocument = $oDocumentModel->getDocument($document_srl);

		if (!$oDocument->document_srl)
		{
			return $this->makeObject();
		}

		$template_path = sprintf("%sskins/%s/", $this->module_path, $config->skin);
		if (!is_dir($template_path) || !$config->skin)
		{
			$config->skin = 'default';
			$template_path = sprintf("%sskins/%s/", $this->module_path, $config->skin);
		}
		$oTemplate = TemplateHandler::getInstance();
		$template_output = $oTemplate->compile($template_path, 'index.html');
		$template_output = preg_replace('/\r\n|\r|\n|\t/', '', $template_output);

		$object_target = in_array($config->object_target, array(
			'document',
			'comment',
			'all'
		)) ? $config->object_target : 'document';
		$exception = preg_replace('/\r\n|\r|\n|\t|\s/', '', $config->exception);

		$print_align = in_array($config->print_align, array(
			'center',
			'left',
			'right'
		)) ? $config->print_align : 'center';
		$loading_image = $config->loading_image ? $config->loading_image : 'Y';
		$title_length = (int)preg_replace('/[^0-9]*/s', '', $config->title_length);
		$print_domain = $config->print_domain ? $config->print_domain : 'Y';
		$content_length = (int)preg_replace('/[^0-9]*/s', '', $config->content_length);
		$image_length = (int)preg_replace('/[^0-9]*/s', '', $config->image_length);
		$image_length = is_numeric($image_length) ? $image_length : 0;

		$internal_link = in_array($config->internal_link, array('_self', '_blank')) ? $config->internal_link : '_self';
		$external_link = in_array($config->external_link, array('_blank', '_self')) ? $config->external_link : '_blank';
		$link_text = $config->link_text ? $config->link_text : 'store';

		$facebook_embed = $config->facebook_embed ? $config->facebook_embed : 'embed';
		$twitter_embed = $config->twitter_embed ? $config->twitter_embed : 'embed';
		$instagram_embed = $config->instagram_embed ? $config->instagram_embed : 'embed';
		$youtube_embed = $config->youtube_embed ? $config->youtube_embed : 'embed';
		$youtube_width = $config->youtube_width ? $config->youtube_width : '500px';

		Context::addHtmlHeader("<script>
			var ap_parser_target = '{$object_target}';
			var ap_parser_exception = '{$exception}';
			var ap_parser_print_align = '{$print_align}';
			var ap_parser_loading_image = '{$loading_image}';
			var ap_parser_title_length = {$title_length};
			var ap_parser_print_domain = '{$print_domain}';
			var ap_parser_content_length = {$content_length};
			var ap_parser_image_length = {$image_length};
			var ap_parser_internal_link = '{$internal_link}';
			var ap_parser_external_link = '{$external_link}';
			var ap_parser_link_text = '{$link_text}';
			var ap_parser_facebook_embed = '{$facebook_embed}';
			var ap_parser_twitter_embed = '{$twitter_embed}';
			var ap_parser_instagram_embed = '{$instagram_embed}';
			var ap_parser_youtube_embed = '{$youtube_embed}';
			var ap_parser_youtube_max = '{$youtube_width}';
			var ap_parser_output = '{$template_output}';
			var ap_parser_document_srl = '{$document_srl}'
		</script>");

		Context::loadFile(array('./modules/parserlink/tpl/js/ap_parser.js', 'body', '', null));
		Context::addCSSFile($template_path . '/css/default.css');
	}

	function clearCache()
	{
		$oCacheHandler = $this->getCacheHandler();
		if (!$oCacheHandler)
		{
			return;
		}

		$oCacheHandler->invalidateGroupKey('parserlink');
	}

	function procParserlinkUpdateInstagram()
	{
		$tag = Context::get('tag');
		if (!$tag)
		{
			return false;
		}

		$tag = urldecode($tag);
		$tag = urlencode($tag);

		$url = "https://www.instagram.com/explore/tags/$tag/?__a=1";
		$response = FileHandler::getRemoteResource($url);

		$args = new stdClass();
		$args->sns_url = $url;
		$args->sns_data = $response;
		$args->update_time = time();
		$args->sns_type = 'instagram';
		$output = executeQuery('parserlink.updateSnsData', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'mid', Context::get('mid'), 'document_srl', Context::get('parser_document_srl')));
		}
	}


	/**
	 * @param $obj
	 * @return BaseObject|Object|void
	 */
	public function triggerAfterInsertDocument($obj)
	{
		/** @var documentModel $oDocumentModel */
		$oDocumentModel = getModel('document');
		$config = self::getConfig();
		if ($config->use !== 'Y')
		{
			return $this->makeObject();
		}

		$document_srl = $obj->document_srl;
		if (!$document_srl)
		{
			return;
		}

		$extra_vars = $oDocumentModel->getExtraVars($obj->module_srl, $document_srl);

		foreach ($extra_vars as $key => $extra_var)
		{
			if ($extra_var->eid == 'link_url')
			{
				$idx = strval($key);
				break;
			}

		}

		$url = $obj->{'extra_vars' . $idx};

		$image_length = (int)preg_replace('/[^0-9]*/s', '', $config->image_length);
		$image_length = is_numeric($image_length) ? $image_length : 0;

		$parserData = getModel('parserlink')->defaultPreviewByUrl($url, $image_length, $obj->document_srl, 'extra');
		$args = new stdClass();
		$args->module_srl = $obj->module_srl;
		$args->document_srl = $obj->document_srl;
		$args->site_url = $url;
		$args->site_data = serialize($parserData);
		$args->update_time = date('YmdHis');
		$output = executeQuery('parserlink.insertParserlinkDocument', $args);
		if(!$output->toBool())
		{
			return $output;
		}
	}
}
