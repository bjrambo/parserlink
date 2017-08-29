<?php
class parserlinkModel extends parserlink
{
	function init()
	{
	}

	/**
	 * Get instagram by username.
	 * @return bool
	 */
	function getInstagram()
	{
		$username = Context::get('username');
		if(!$username)
		{
			return false;
		}

		$url = "https://www.instagram.com/$username/?__a=1";
		$response = FileHandler::getRemoteResource($url);

		$data = json_decode($response, true);
		$media = $data['user']['media']['nodes'];
		$this->add('data', $media);
	}

	function getDefaultPreviewByUrl()
	{
		$url = urldecode(Context::get('url'));
		$img_len = Context::get('img_len');
		debugPrint($url);

		$return_array = array();
		$images = array();

		$user_id = urldecode($_REQUEST['user_id']);

		// Get and Return Instagram User Profile
		if ($user_id)
		{
			$profile_url = "https://www.instagram.com/$user_id/?__a=1";
			$response = FileHandler::getRemoteResource($profile_url);

			if ($response === false)
			{
				return;
			}
			$data = json_decode($response, true);
			if ($data === null)
			{
				return;
			}
			$media = $data['user']['media'];

			header('Content-type: application/json');
			echo json_encode($media['nodes']);
			exit;
		}

		$url = $this->checkValues($url);
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
			$url = str_replace(array(
				'//news.naver.com',
				'/main'
			), array(
				'//m.news.naver.com',
				''
			), $url);
		}
		if (strpos($url, '//movie.naver.com/movie/preview/preview.nhn?preview') !== false)
		{
			$url = str_replace(array(
				'://',
				'movie/preview/preview.nhn?preview_'
			), array(
				'://m.',
				'm/event/EventView.nhn?'
			), $url);
		}
		if (strpos($url, '//movie.naver.com/movie/preview/apply_win.nhn?apply') !== false)
		{
			$url = str_replace(array(
				'://',
				'movie/preview/apply_win.nhn?apply_'
			), array(
				'://m.',
				'm/event/WinnerView.nhn?'
			), $url);
		}
		if (strpos($url, '//movie.naver.com/movie/board/ticketshare/list.nhn') !== false)
		{
			$url = str_replace(array(
				'://',
				'movie/preview/apply_win.nhn?apply_'
			), array(
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
			$url = str_replace(array(
				'//info.finance.naver.com',
				'//finance.naver.com'
			), '//m.stock.naver.com', $url);
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
					), array(
						'news',
						'read',
						'=mainnews',
						'officeId=',
						'articleId='
					), $url);
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
					$url = str_replace(array(
						'pro_invest_main.nhn',
						'pro_invest_read.nhn'
					), array(
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
					$url = str_replace(array(
						'news_read',
						'mode=',
						'office_id=',
						'article_id='
					), array(
						'read',
						'category=',
						'officeId=',
						'articleId='
					), $url);
				}
			}
		}

		// Get Data
		$string = $this->getReturn($url);
		// Daum Blog & Cafe Url Re-arrange
		if (strpos($url, 'http://blog.daum.net') !== false || strpos($url, 'http://cafe.daum.net') !== false)
		{
			if (strpos($url, 'http://blog.daum.net') !== false && strpos($url, 'blogid') === false)
			{
				$_url = '';
				$_frame = $this->extract_tags($string, 'frame');
				$_url = trim($_frame[0]['attributes']['src']);
				$url = 'http://blog.daum.net' . $_url;
			}
			if (strpos($url, 'http://cafe.daum.net') !== false && strpos($url, '_c21_') === false)
			{
				foreach ($this->extract_tags($string, 'meta') as $node)
				{
					if (strtolower($node['attributes']['property']) == 'og:url')
					{
						$url = trim($node['attributes']['content']);
						break;
					}
				}
			}
			$string = '';
			$string = $this->getReturn($url);
		}
		$string = str_replace(array(
			'\n',
			'\r',
			'\t',
			'&nbsp;',
			'</span>',
			'</div>'
		), '', $string);
		$string = preg_replace('/(<(div|span)\s[^>]+\s?>)/', '', $string);
		$string = preg_replace('/<!--(.*?)-->/is', '', $string);
		if (mb_detect_encoding($string, "UTF-8") != "UTF-8")
		{
			$string = utf8_encode($string);
		}


		// Parse Title
		$return_array['title'] = '';
		$nodes = $this->extract_tags($string, 'meta');
		foreach ($nodes as $node)
		{
			if (strtolower($node['attributes']['property']) == 'og:title')
			{
				$return_array['title'] = trim($node['attributes']['content']);
				if ($return_array['title'])
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['name']) == 'twitter:title')
			{
				$return_array['title'] = trim($node['attributes']['content']);
			}
		}
		if (!$return_array['title'])
		{
			$nodes = $this->extract_tags($string, 'title');
			$return_array['title'] = trim($nodes[0]['contents']);
		}

		// Parse Description
		$return_array['description'] = '';
		$nodes = $this->extract_tags($string, 'meta');
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
					"img" => $img,
					'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($this->getOuterImageString($img))
				);
				if (count($images))
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['property']) == 'og:image')
			{
				$images[] = array(
					"img" => $img,
					'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($this->getOuterImageString($img))
				);
				if (count($images))
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['name']) == 'twitter:image:src')
			{
				$images[] = array(
					"img" => $img,
					'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($this->getOuterImageString($img))
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
			$images_array = $this->extract_tags($string, 'img');

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
					"img" => $img,
					'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($this->getOuterImageString($img))
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
							;
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
							$details = @getimagesize($img);
							if (is_array($details))
							{
								list($width, $height, $type, $attr) = $details;
							}
						}
						$width = intval($width);
						$height = intval($height);

						if ($width > 159 || $height > 159)
						{
							if (($width > 0 && $height > 0 && (($width / $height) < 3) && (($width / $height) > .2)) && strpos($img, 'logo') === false)
							{
								$images[] = array(
									"img" => $img,
									'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($this->getOuterImageString($img))
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
		$return_array['total_images'] = count($return_array['images']);

		$this->add('obj', $return_array);
	}


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


	function getReturn($url)
	{
		$url = str_replace("&amp;", '&', $url);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		curl_setopt($process, CURLOPT_ENCODING, 'gzip');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0');
		$return = curl_exec($ch);
		curl_close($ch);
		return $return;
	}

	function getOuterImageString($url)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$image_string = curl_exec($ch);
		curl_close($ch);
		return $image_string;
	}
}
/* End of file */
