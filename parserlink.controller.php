<?php
class parserlinkController extends parserlink
{
	function triggerBeforeModuleHandlerInit()
	{
		$config = self::getConfig();

		$template_path = sprintf("%sskins/%s/", $this->module_path, $config->skin);
		if(!is_dir($template_path)||!$config->skin)
		{
			$config->skin = 'default';
			$template_path = sprintf("%sskins/%s/", $this->module_path, $config->skin);
		}

		Context::addCSSFile($template_path . '/css/default.css');
	}

	function triggerAfterModuleHandlerInit()
	{
		$document_srl = Context::get('document_srl');

		/* @var $oDocumentModel documentModel */
		$oDocumentModel = getModel('document');

		if (!$document_srl)
		{
			return new Object();
		}

		$oDocument = $oDocumentModel->getDocument($document_srl);

		if(!$oDocument->document_srl)
		{
			return new Object();
		}

		$config = self::getConfig();
		$template_path = sprintf("%sskins/%s/", $this->module_path, $config->skin);
		if(!is_dir($template_path)||!$config->skin)
		{
			$config->skin = 'default';
			$template_path = sprintf("%sskins/%s/", $this->module_path, $config->skin);
		}
		$oTemplate = TemplateHandler::getInstance();
		$template_output = $oTemplate->compile($template_path, 'index.html');
		$template_output = preg_replace('/\r\n|\r|\n|\t/', '', $template_output);

		$object_target = in_array($config->object_target, array('document', 'comment', 'all')) ? $config->object_target : 'document';
		$exception = preg_replace('/\r\n|\r|\n|\t|\s/', '', $config->exception);

		$print_align = in_array($config->print_align, array('center', 'left', 'right')) ? $config->print_align : 'center';
		$loading_image = $config->loading_image ? $config->loading_image : 'Y';
		$title_length = (int)preg_replace('/[^0-9]*/s', '', $config->title_length);
		$print_domain = $config->print_domain ? $config->print_domain : 'Y';
		$content_length = (int)preg_replace('/[^0-9]*/s', '', $config->content_length);
		$image_length = (int)preg_replace('/[^0-9]*/s', '', $config->image_length);
		$image_length = is_numeric($image_length) ? $image_length : 0;

		$internal_link = in_array($config->internal_link, array('_self', '_blank')) ? $config->internal_link : '_self';
		$external_link = in_array($config->external_link, array('_blank', '_self')) ? $config->external_link : '_blank';
		$link_text = $config->link_text ? (int)$config->link_text : 0;

		$facebook_embed = $config->facebook_embed ? (int)$config->facebook_embed : 0;
		$twitter_embed = $config->twitter_embed ? (int)$config->twitter_embed : 0;
		$instagram_embed = $config->instagram_embed ? (int)$config->instagram_embed : 0;
		$youtube_embed = $config->youtube_embed ? (int)$config->youtube_embed : 0;
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
			var ap_parser_link_text = {$link_text};
			var ap_parser_facebook_embed = {$facebook_embed};
			var ap_parser_twitter_embed = {$twitter_embed};
			var ap_parser_instagram_embed = {$instagram_embed};
			var ap_parser_youtube_embed = {$youtube_embed};
			var ap_parser_youtube_max = '{$youtube_width}';
			var ap_parser_output = '{$template_output}';
		</script>");

		Context::loadFile(array('./modules/parserlink/tpl/js/ap_parser.js', 'body', '', null));
	}

	function triggerAfterDisplay()
	{

	}
}