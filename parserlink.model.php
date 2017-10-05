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

		if(!$type)
		{
			if($userName)
			{
				$type = 'user';
			}
			else
			{
				$type = 'tag';
			}
		}

		if($tag)
		{
			$tag = urldecode($tag);
			$tag = urlencode($tag);
		}

		$config = $this->getConfig();
		$oCacheHandler = $this->getCacheHandler();

		$cache_time_sec = (int)$config->cache_time * 86400;
		if(!$config->cache_time)
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
		if($config->use_db_data === 'yes')
		{
			$args->sns_url = $url;
			$output = executeQuery('parserlink.getSnsData', $args);
			if($output->data)
			{
				if($output->data->update_time > $beforeDataUnixTime)
				{
					$instaData = $output->data;

					$instaDataJsonDecode = json_decode($instaData->sns_data);
					$mediaData = $instaDataJsonDecode->{$type}->media->nodes;
					$this->add('data', $mediaData);
					if ($oCacheHandler)
					{
						if($type == 'user')
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

		if($config->use_db_data === 'yes')
		{
			$args->sns_data = $response;
			$args->update_time = time();
			$args->sns_type = 'instagram';
			/** @var $output 74 line */
			if($output->data)
			{
				$output = executeQuery('parserlink.updateSnsData', $args);
			}
			else
			{
				$output = executeQuery('parserlink.insertSnsData', $args);
			}
			if(!$output->toBool())
			{
				return;
			}
		}

		$media = $data->{$type}->media->nodes;
		$this->add('data', $media);

		if ($oCacheHandler)
		{
			if($type == 'user')
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
		if (get_magic_quotes_gpc())
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
				'tag_name' => $match['tag'][0],
				'offset' => $match[0][1],
				'contents' => !empty($match['contents']) ? $match['contents'][0] : '',
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
}
/* End of file */
