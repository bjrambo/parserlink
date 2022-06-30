<?php

class parserlinkModel extends parserlink
{
	function init()
	{
	}

	/**
	 * getInstagram in profile and tag.
	 * @return object|void
	 */
	function getInstagramData()
	{
		$type = Context::get('type');
		$userName = Context::get('username');
		$tag = Context::get('tag');

		if (!$type)
		{
			if ($userName)
			{
				$type = 'user';
			}
			else
			{
				$type = 'tag';
			}
		}

		if ($tag)
		{
			$tag = urldecode($tag);
			$tag = urlencode($tag);
		}

		$config = $this->getConfig();
		$oCacheHandler = $this->getCacheHandler();

		$cache_time_sec = (int)$config->cache_time * 86400;
		if (!$config->cache_time)
		{
			$cache_time_sec = 86400;
		}
		$beforeDataUnixTime = time() - $cache_time_sec;
		switch ($type)
		{
			case 'user':
				$url = "https://www.instagram.com/$userName/?__a=1";
				if ($oCacheHandler)
				{
					if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('parserlink', "url:$url:type:$type:username:$userName"), time() - $cache_time_sec)) !== false)
					{
						$this->add('data', $result);
						return;
					}
				}
				break;
			case 'tag':
				$url = "https://www.instagram.com/explore/tags/$tag/?__a=1";
				if ($oCacheHandler)
				{
					if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('parserlink', "url:$url:type:$type:tag:$tag"), time() - $cache_time_sec)) !== false)
					{
						$this->add('data', $result);
						return;
					}
				}
				break;
			// If type is not have a data, execute the return void.
			default:
				return;
		}

		$args = new stdClass();
		if ($config->use_db_data === 'yes')
		{
			$args->sns_url = $url;
			$output = executeQuery('parserlink.getSnsData', $args);
			if ($output->data)
			{
				if ($output->data->update_time > $beforeDataUnixTime)
				{
					$instaData = $output->data;

					$instaDataJsonDecode = json_decode($instaData->sns_data);
					$mediaData = $instaDataJsonDecode->{$type}->media->nodes;
					$this->add('data', $mediaData);
					if ($oCacheHandler)
					{
						if ($type == 'user')
						{
							$oCacheHandler->put($oCacheHandler->getGroupKey('parserlink', "url:$url:type:$type:username:$userName"), $mediaData, $cache_time_sec);
						}
						else
						{
							$oCacheHandler->put($oCacheHandler->getGroupKey('parserlink', "url:$url:type:$type:tag:$tag"), $mediaData, $cache_time_sec);
						}
					}
					return;
				}
			}
		}

		$response = FileHandler::getRemoteResource($url);

		$data = json_decode($response);

		if ($config->use_db_data === 'yes')
		{
			$args->sns_data = $response;
			$args->update_time = time();
			$args->sns_type = 'instagram';
			/** @var $output 74 line */
			if ($output->data)
			{
				$output = executeQuery('parserlink.updateSnsData', $args);
			}
			else
			{
				$output = executeQuery('parserlink.insertSnsData', $args);
			}
			if (!$output->toBool())
			{
				return;
			}
		}

		$media = $data->{$type}->media->nodes;
		$this->add('data', $media);

		if ($oCacheHandler)
		{
			if ($type == 'user')
			{
				$oCacheHandler->delete($oCacheHandler->getGroupKey('parserlink', "url:$url:type:$type:username:$userName"));
			}
			else
			{
				$oCacheHandler->delete($oCacheHandler->getGroupKey('parserlink', "url:$url:type:$type:tag:$tag"));
			}
		}
		return;
	}

	/**
	 * Check Values in $url.
	 * @param $value
	 * @return string
	 */
	function checkValues($value)
	{
		$value = trim($value);
		if((function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) || (ini_get('magic_quotes_sybase') && (strtolower(ini_get('magic_quotes_sybase'))!="off")) )
		{
			$value = stripslashes($value);
		}
		$value = strtr($value, array_flip(get_html_translation_table(HTML_ENTITIES)));
		$value = strip_tags($value);
		$value = htmlspecialchars($value);
		return $value;
	}


	function extract_tags($html, $tag, $selfclosing = null, $return_the_entire_tag = false, $charset = 'ISO-8859-1')
	{
		if (is_array($tag))
		{
			$tag = implode('|', $tag);
		}

		//If the user didn't specify if $tag is a self-closing tag we try to auto-detect it
		//by checking against a list of known self-closing tags.
		$selfclosing_tags = array(
			'area',
			'base',
			'basefont',
			'br',
			'hr',
			'input',
			'img',
			'link',
			'meta',
			'col',
			'param'
		);
		if (is_null($selfclosing))
		{
			$selfclosing = in_array($tag, $selfclosing_tags);
		}

		//The regexp is different for normal and self-closing tags because I can't figure out
		//how to make a sufficiently robust unified one.
		if ($selfclosing)
		{
			$tag_pattern = '@<(?P<tag>' . $tag . ')			# <tag
			(?P<attributes>\s[^>]+)?		# attributes, if any
			\s*/?>					# /> or just >, being lenient here
			@xsi';
		}
		else
		{
			$tag_pattern = '@<(?P<tag>' . $tag . ')			# <tag
			(?P<attributes>\s[^>]+)?		# attributes, if any
			\s*>					# >
			(?P<contents>.*?)			# tag contents
			</(?P=tag)>				# the closing </tag>
			@xsi';
		}

		$attribute_pattern = '@
		(?P<name>\w+)							# attribute name
		\s*=\s*
		(
			(?P<quote>[\"\'])(?P<value_quoted>.*?)(?P=quote)	# a quoted value
			|							# or
			(?P<value_unquoted>[^\s"\']+?)(?:\s+|$)			# an unquoted value (terminated by whitespace or EOF)
		)
		@xsi';

		//Find all tags
		//Return an empty array if we didn't find anything
		if (!preg_match_all($tag_pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE))
		{
			return array();
		}

		$tags = array();
		foreach ($matches as $match)
		{
			//Parse tag attributes, if any
			$attributes = array();
			if (!empty($match['attributes'][0]))
			{
				if (preg_match_all($attribute_pattern, $match['attributes'][0], $attribute_data, PREG_SET_ORDER))
				{
					//Turn the attribute data into a name->value array
					foreach ($attribute_data as $attr)
					{
						if (!empty($attr['value_quoted']))
						{
							$value = $attr['value_quoted'];
						}
						else if (!empty($attr['value_unquoted']))
						{
							$value = $attr['value_unquoted'];
						}
						else
						{
							$value = '';
						}

						//Passing the value through html_entity_decode is handy when you want
						//to extract link URLs or something like that. You might want to remove
						//or modify this call if it doesn't fit your situation.
						$value = html_entity_decode($value, ENT_QUOTES, $charset);

						$attributes[$attr['name']] = $value;
					}
				}
			}

			$tag = array(
				'tag_name'   => $match['tag'][0],
				'offset'     => $match[0][1],
				'contents'   => !empty($match['contents']) ? $match['contents'][0] : '',
				//empty for self-closing tags
				'attributes' => $attributes,
			);
			if ($return_the_entire_tag)
			{
				$tag['full_tag'] = $match[0][0];
			}

			$tags[] = $tag;
		}

		return $tags;
	}


	function getRemoteResource($url)
	{
		$url = str_replace("&amp;", '&', $url);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
		$return = curl_exec($ch);
		curl_close($ch);
		return $return;
	}

	function getRemoteResourceImageString($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		$image_string = curl_exec($ch);
		curl_close($ch);
		return $image_string;
	}

	function defaultPreviewByUrl($url = null, $img_len = null, $document_srl = null, $type = null)
	{
		$config = $this->getConfig();
		if ($config->use !== 'Y')
		{
			return;
		}

		if (!$url)
		{
			$url = urldecode(Context::get('url'));
		}

		if (!$img_len)
		{
			$img_len = Context::get('img_len');
		}

		if (!$document_srl)
		{
			$document_srl = Context::get('parser_document_srl');
		}
		$module_info = getModel('module')->getModuleInfoByDocumentSrl($document_srl);

		$return_array = array();
		$images = array();

		/** @var  $oParserlinkModel parserlinkModel */
		$oParserlinkModel = getModel('parserlink');

		$url = $oParserlinkModel->checkValues($url);

		$base_url = substr($url, 0, strpos($url, "/", 8));
		$relative_url = substr($url, 0, strrpos($url, "/") + 1);

		// Naver Sections and Daum News Url Re-arrange
		if (strpos($url, 'blog.me') !== false)
		{
			$url = preg_replace('/((?:https?:\/\/))(.*?).blog.me(.*)/i', '$1m.blog.naver.com/$2$3', $url);
		}
		if (strpos($url, '//blog.naver.com') !== false && strpos($url, '//m.blog.naver.com') === false)
		{
			$url = str_replace('//blog.naver.com', '//m.blog.naver.com', $url);
		}
		if (strpos($url, '//cafe.naver.com') !== false && strpos($url, '//m.cafe.naver.com') === false)
		{
			$url = str_replace('//cafe.naver.com', '//m.cafe.naver.com', $url);
		}
		if (strpos($url, '//news.naver.com') !== false && strpos($url, '//m.news.naver.com') === false)
		{
			$url = str_replace(array('//news.naver.com', '/main'), array('//m.news.naver.com', ''), $url);
		}
		if (strpos($url, '//movie.naver.com/movie/preview/preview.nhn?preview') !== false)
		{
			$url = str_replace(array('://', 'movie/preview/preview.nhn?preview_'), array(
				'://m.',
				'm/event/EventView.nhn?'
			), $url);
		}
		if (strpos($url, '//movie.naver.com/movie/preview/apply_win.nhn?apply') !== false)
		{
			$url = str_replace(array('://', 'movie/preview/apply_win.nhn?apply_'), array(
				'://m.',
				'm/event/WinnerView.nhn?'
			), $url);
		}
		if (strpos($url, '//movie.naver.com/movie/board/ticketshare/list.nhn') !== false)
		{
			$url = str_replace(array('://', 'movie/preview/apply_win.nhn?apply_'), array(
				'://m.',
				'm/event/ticketshare/TicketShareList.nhn'
			), $url);
		}
		if (strpos($url, '//media.daum.net') !== false)
		{
			$url = $url . '?f=m';
		}
		if (strpos($url, '//finance.naver.com') !== false || strpos($url, '//info.finance.naver.com') !== false)
		{
			$url = str_replace(array('//info.finance.naver.com', '//finance.naver.com'), '//m.stock.naver.com', $url);
			$_url = explode('/', str_replace('//', '/', $url));
			if ($_url[2] == 'sise')
			{
				if (!$_url[3] || $_url[3] == '')
				{
					if ($url[strlen($url) - 1] == '/')
					{
						$url = $url . 'siseList.nhn?menu=market_sum&sosok=0';
					}
					else
					{
						$url = $url . '/siseList.nhn?menu=market_sum&sosok=0';
					}
				}
				else
				{
					$url = str_replace('sise_index', 'siseIndex', $url);
				}
			}
			else if ($_url[2] == 'world' && $_url[3] && $_url[3] != '')
			{
				if (strpos($_url[3], 'sise.nhn') !== false)
				{
					$url = str_replace('sise.nhn', 'item.nhn', $url);
				}
				else if (strpos($_url[3], 'market_news_view.nhn') !== false)
				{
					$url = str_replace(array(
						'world',
						'market_news_view',
						'=main',
						'office_id=',
						'article_id='
					), array('news', 'read', '=mainnews', 'officeId=', 'articleId='), $url);
				}
			}
			else if ($_url[2] == 'marketindex')
			{
				if ($_url[3] && $_url[3] != '')
				{
					$url = str_replace('exchangeDetail.nhn', 'item.nhn', $url);
				}
			}
			else if ($_url[2] == 'research' && $_url[3] && $_url[3] != '')
			{
				if (strpos($_url[3], 'pro_invest_') !== false)
				{
					$url = str_replace(array('pro_invest_main.nhn', 'pro_invest_read.nhn'), array(
						'expert.nhn',
						'expertColumnRead.nhn'
					), $url);
				}
				else
				{
					if (strpos($_url[3], '_list.nhn') !== false)
					{
						$url = preg_replace('/([a-z_]+)_list\.nhn/i', 'research.nhn?category=$1', $url);
						$url = str_replace('market_info', 'market', $url);
					}
					else if (strpos($_url[3], '_read.nhn') !== false)
					{
						$url = preg_replace('/([a-z_]+)_(read\.nhn)\?/i', '$2?category=$1&', $url);
						$url = str_replace('market_info', 'market', $url);
					}
				}
			}
			else if ($_url[2] == 'news')
			{
				if ($_url[3] && $_url[3] != '')
				{
					$url = str_replace(array('news_read', 'mode=', 'office_id=', 'article_id='), array(
						'read',
						'category=',
						'officeId=',
						'articleId='
					), $url);
				}
			}
		}

		$oCacheHandler = $this->getCacheHandler();
		if ($config->use_db_data === 'yes')
		{
			if (preg_match('/youtube.com/u', $url))
			{
				$sns_type = 'youtube';
			}
			else if (preg_match('/twitter.com/u', $url))
			{
				$sns_type = 'twitter';
			}
			else if (preg_match('/instagram.com/u', $url))
			{
				$sns_type = 'instagram';
			}
			else if (preg_match('/facebook.com/u', $url))
			{
				$sns_type = 'facebook';
			}
			else
			{
				$sns_type = 'default';
			}
			$configSnsEmbedName = $sns_type . '_embed';

			$cache_time_sec = (int)$config->cache_time * 86400;

			if (!$config->cache_time)
			{
				$cache_time_sec = 86400;
			}

			if ($oCacheHandler)
			{
				if (($result = $oCacheHandler->get($oCacheHandler->getGroupKey('parserlink', "url:$url:sns_type:$sns_type:embed:" . $config->{$configSnsEmbedName}), time() - $cache_time_sec)) !== false)
				{
					if ($type == 'extra')
					{
						return $result;
					}
					else
					{
						$this->add('return_array', $result);
						return;
					}
				}
			}
			$beforeDataUnixTime = time() - $cache_time_sec;

			$search_args = new stdClass();
			$search_args->site_url = $url;
			$search_output = executeQuery('parserlink.getParserlinkData', $search_args);
			$search_data = $search_output->data;
			if ($search_data)
			{
				if ($search_data->update_time > $beforeDataUnixTime)
				{
					if ($search_data->embed_type == $config->{$configSnsEmbedName})
					{
						$unserializeData = unserialize($search_data->site_data);
						if ($type == 'extra')
						{
							return $unserializeData;
						}
						else
						{
							$this->add('return_array', $unserializeData);
						}
						if ($oCacheHandler)
						{
							$oCacheHandler->put($oCacheHandler->getGroupKey('parserlink', "url:$url:sns_type:$sns_type:embed:" . $config->{$configSnsEmbedName}), $unserializeData, $cache_time_sec);
						}
						return;
					}
					else if ($sns_type === 'default')
					{
						$unserializeData = unserialize($search_data->site_data);
						$this->add('return_array', $unserializeData);
						if ($type == 'extra')
						{
							return $unserializeData;
						}
						else
						{
							$this->add('return_array', $unserializeData);
						}
						if ($oCacheHandler)
						{
							$oCacheHandler->put($oCacheHandler->getGroupKey('parserlink', "url:$url:sns_type:$sns_type:embed:" . $config->{$configSnsEmbedName}), $unserializeData, $cache_time_sec);
						}
						return;
					}
				}
			}
		}
		// Get Data
		$string = $oParserlinkModel->getRemoteResource($url);
		// Daum Blog & Cafe Url Re-arrange
		if (strpos($url, 'http://blog.daum.net') !== false || strpos($url, 'http://cafe.daum.net') !== false)
		{
			if (strpos($url, 'http://blog.daum.net') !== false && strpos($url, 'blogid') === false)
			{
				$_url = '';
				$_frame = $oParserlinkModel->extract_tags($string, 'frame');
				$_url = trim($_frame[0]['attributes']['src']);
				$url = 'http://blog.daum.net' . $_url;
			}
			if (strpos($url, 'http://cafe.daum.net') !== false && strpos($url, '_c21_') === false)
			{
				foreach ($oParserlinkModel->extract_tags($string, 'meta') as $node)
				{
					if (strtolower($node['attributes']['property']) == 'og:url')
					{
						$url = trim($node['attributes']['content']);
						break;
					}
				}
			}
			$string = $oParserlinkModel->getRemoteResource($url);
		}
		$string = str_replace(array('\n', '\r', '\t', '&nbsp;', '</span>', '</div>'), '', $string);
		$string = preg_replace('/(<(div|span)\s[^>]+\s?>)/', '', $string);
		$string = preg_replace('/<!--(.*?)-->/is', '', $string);
		if (mb_detect_encoding($string, "UTF-8") != "UTF-8")
		{
			$string = utf8_encode($string);
		}

		// Parse Title
		$return_array['title'] = '';
		$nodes = $oParserlinkModel->extract_tags($string, 'meta');
		foreach ($nodes as $node)
		{
			if (strtolower($node['attributes']['property']) == 'og:title')
			{
				$return_array['title'] = trim($node['attributes']['content']);
			}
			else if (strtolower($node['attributes']['name']) == 'twitter:title')
			{
				$return_array['title'] = trim($node['attributes']['content']);
			}

			if (strtolower($node['attributes']['property'] == 'og:url'))
			{
				$return_array['url'] = trim($node['attributes']['content']);
			}
		}
		if (!$return_array['title'])
		{
			$nodes = $oParserlinkModel->extract_tags($string, 'title');
			$return_array['title'] = trim($nodes[0]['contents']);
		}

		// Parse Description
		$return_array['description'] = '';
		$nodes = $oParserlinkModel->extract_tags($string, 'meta');
		foreach ($nodes as $node)
		{
			if (strtolower($node['attributes']['property']) == 'og:description')
			{
				$return_array['description'] = trim($node['attributes']['content']);
				if ($return_array['description'])
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['name']) == 'twitter:description')
			{
				$return_array['description'] = trim($node['attributes']['content']);
				if ($return_array['description'])
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['name']) == 'description')
			{
				$return_array['description'] = trim($node['attributes']['content']);
			}
		}

		// Parse Open Graph or Twittercard Images First
		$return_array['images'] = '';

		foreach ($nodes as $node)
		{
			$img = trim($node['attributes']['content']);
			$ext = trim(pathinfo($img, PATHINFO_EXTENSION));
			if (strtolower($node['attributes']['property']) == 'naverblog:profile_image')
			{
				$images[] = array(
					"img"    => $img,
				);
				if (count($images))
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['property']) == 'og:image')
			{
				$images[] = array(
					"img"    => $img,
				);
				if (count($images))
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['name']) == 'twitter:image:src')
			{
				$images[] = array(
					"img"    => $img,
				);
				if (count($images))
				{
					break;
				}
			}
		}

		if (count($images))
		{
			$return_array['images'] = array_values($images);
		}
		else
		{
			// Parse Base
			$base_override = false;
			$base_regex = '/<base[^>]*' . 'href=[\"|\'](.*)[\"|\']/Ui';
			preg_match_all($base_regex, $string, $base_match, PREG_PATTERN_ORDER);
			if (strlen($base_match[1][0]) > 0)
			{
				$base_url = $base_match[1][0];
				$base_override = true;
			}

			// Parse Images
			$images_array = $oParserlinkModel->extract_tags($string, 'img');
			// Naver Cafe Images
			if (strpos($url, 'cafe.naver.com') !== false)
			{
				if (strpos($url, '?') === false)
				{
					preg_match_all('/(?:(?:https?):\/\/)(.+cafe.naver.com)\/([a-z0-9]+)\/([0-9]+)/i', $url, $matches);
					if ($matches[3])
					{
						$img = trim($images_array[1]['attributes']['src']);
					}
					else
					{
						$img = trim($images_array[0]['attributes']['src']);
					}
				}
				else
				{
					if (strpos($url, 'articleid') !== false)
					{
						$img = trim($images_array[1]['attributes']['src']);
					}
					else
					{
						$img = trim($images_array[0]['attributes']['src']);
					}
				}
				$ext = trim(pathinfo($img, PATHINFO_EXTENSION));
				$images[] = array(
					"img"    => $img,
				);
			}
			// Other Images
			else
			{
				for ($i = 0; $i <= sizeof($images_array); $i++)
				{
					$img = trim(@$images_array[$i]['attributes']['src']);
					$width = preg_replace("/[^0-9.]/", '', $images_array[$i]['attributes']['width']);
					$height = preg_replace("/[^0-9.]/", '', $images_array[$i]['attributes']['height']);

					$ext = trim(pathinfo($img, PATHINFO_EXTENSION));

					if ($img && $ext != 'gif')
					{
						if (substr($img, 0, 7) == 'http://')
						{
							
						}
						else if (substr($img, 0, 1) == '/' || $base_override)
						{
							$img = $base_url . $img;
						}
						else
						{
							$img = $relative_url . $img;
						}

						if ($width == '' && $height == '')
						{
							$dir = _XE_PATH_ . 'files/parserlink/tmp/' . getNumberingPath($document_srl);
							$file_name = $document_srl . '.jpg';
							if (!FileHandler::isDir($dir))
							{
								FileHandler::makeDir($dir);
							}

							$path = $dir . $file_name;
							$result = FileHandler::getRemoteFile($img, $path);
							if($result)
							{
								$details = @getimagesize($path);
								if (is_array($details))
								{
									list($width, $height, $type, $attr) = $details;
								}
								else
								{
									FileHandler::removeFile($path);
									continue;
								}
								FileHandler::removeFile($path);
							}
						}
						$width = intval($width);
						$height = intval($height);

						if ($width > 159 || $height > 159)
						{
							if (($width > 0 && $height > 0 && (($width / $height) < 3) && (($width / $height) > .2)) && strpos($img, 'logo') === false)
							{
								$images[] = array(
									"img"    => $img,
								);
							}
						}

					}
					if ($img_len != 0 && count($images) == $img_len)
					{
						break;
					}
				}
			}
			$return_array['images'] = array_values($images);
		}
		$testVal = $return_array['images'];

		$saveFile = $return_array['images'][0]['img'];

		$imageSaveOutput = $this->getRemoteSaveImage($saveFile, $document_srl);

		
		$return_array['total_images'] = count($return_array['images']);
		if ($config->use_db_data === 'yes')
		{
			$args = new stdClass();
			$args->site_url = $url;
			$args->module_srl = $module_info->module_srl;
			$args->document_srl = $document_srl;
			$args->site_data = serialize($return_array);
			$args->update_time = time();
			$args->sns_type = $sns_type;
			$args->embed_type = $config->{$configSnsEmbedName};
			if ($search_data)
			{
				$updateOutout = executeQuery('parserlink.updateParserlinkData', $args);
				if (!$updateOutout->toBool())
				{
					return $updateOutout;
				}
			}
			else
			{
				$insertOutput = executeQuery('parserlink.insertParserlinkData', $args);
				if (!$insertOutput->toBool())
				{
					return $insertOutput;
				}
			}
			if ($type == 'extra')
			{
				return $return_array;
			}

			// Clear cache from url.
			if ($oCacheHandler)
			{
				$oCacheHandler->delete($oCacheHandler->getGroupKey('parserlink', "url:$url:sns_type:$sns_type:embed:" . $config->{$configSnsEmbedName}));
			}
		}

		$this->add('return_array', $return_array);
	}
	
	public function getRemoteSaveImage($remoteUri, $document_srl)
	{
		preg_match_all("/jpe?g|png|gif|bmp|mp4/i", $remoteUri, $imgMatches, PREG_SET_ORDER);
		
		$dirPath = RX_BASEDIR . 'files/cache/parserlink/img/'.getNumberingPath($document_srl);
		$filePath = RX_BASEDIR . 'files/cache/parserlink/img/'.getNumberingPath($document_srl) . '/' . getNextSequence() . $imgMatches[0][0];

		$image_file_url = Context::getRequestUri().'files/cache/parserlink/img/'.getNumberingPath($document_srl);
		
		if(FileHandler::exists($filePath))
		{
			return $image_file_url;
		}
		if(!FileHandler::isDir($dirPath))
		{
			FileHandler::makeDir($dirPath);
		}

		$fp = fopen($filePath, 'w');
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $remoteUri);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.0.0 Safari/537.36");
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_exec($ch);
		fclose($fp);
		curl_close($ch);
	}
}
/* End of file */
