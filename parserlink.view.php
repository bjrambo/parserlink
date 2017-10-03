<?php

class parserlinkView extends parserlink
{
	/**
	 * @return object|void
	 */
	function dispDefaultPreviewByUrl()
	{
		$config = $this->getConfig();
		if ($config->use !== 'Y')
		{
			return;
		}

		$url = urldecode(Context::get('url'));
		$img_len = Context::get('img_len');

		$document_srl = Context::get('parser_document_srl');
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
			$url = str_replace(array('://', 'movie/preview/preview.nhn?preview_'), array('://m.', 'm/event/EventView.nhn?'), $url);
		}
		if (strpos($url, '//movie.naver.com/movie/preview/apply_win.nhn?apply') !== false)
		{
			$url = str_replace(array('://', 'movie/preview/apply_win.nhn?apply_'), array('://m.', 'm/event/WinnerView.nhn?'), $url);
		}
		if (strpos($url, '//movie.naver.com/movie/board/ticketshare/list.nhn') !== false)
		{
			$url = str_replace(array('://', 'movie/preview/apply_win.nhn?apply_'), array('://m.', 'm/event/ticketshare/TicketShareList.nhn'), $url);
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
					$url = str_replace(array('world', 'market_news_view', '=main', 'office_id=', 'article_id='), array('news', 'read', '=mainnews', 'officeId=', 'articleId='), $url);
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
					$url = str_replace(array('pro_invest_main.nhn', 'pro_invest_read.nhn'), array('expert.nhn', 'expertColumnRead.nhn'), $url);
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
					$url = str_replace(array('news_read', 'mode=', 'office_id=', 'article_id='), array('read', 'category=', 'officeId=', 'articleId='), $url);
				}
			}
		}

		$oCacheHandler = $this->getCacheHandler();
		if($config->use_db_data === 'yes')
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
					echo $result;
					exit();
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
						echo $unserializeData;
						if ($oCacheHandler)
						{
							$oCacheHandler->put($oCacheHandler->getGroupKey('parserlink', "url:$url:sns_type:$sns_type:embed:" . $config->{$configSnsEmbedName}), $unserializeData, $cache_time_sec);
						}
						exit();
					}
					else if ($sns_type === 'default')
					{
						$unserializeData = unserialize($search_data->site_data);
						echo $unserializeData;
						if ($oCacheHandler)
						{
							$oCacheHandler->put($oCacheHandler->getGroupKey('parserlink', "url:$url:sns_type:$sns_type:embed:" . $config->{$configSnsEmbedName}), $unserializeData, $cache_time_sec);
						}
						exit();
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
				$images[] = array("img" => $img, 'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($oParserlinkModel->getRemoteResourceImageString($img)));
				if (count($images))
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['property']) == 'og:image')
			{
				$images[] = array("img" => $img, 'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($oParserlinkModel->getRemoteResourceImageString($img)));
				if (count($images))
				{
					break;
				}
			}
			else if (strtolower($node['attributes']['name']) == 'twitter:image:src')
			{
				$images[] = array("img" => $img, 'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($oParserlinkModel->getRemoteResourceImageString($img)));
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
				$images[] = array("img" => $img, 'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($oParserlinkModel->getRemoteResourceImageString($img)));
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
								$images[] = array("img" => $img, 'base64' => 'data:image/' . $ext . ';base64,' . base64_encode($oParserlinkModel->getRemoteResourceImageString($img)));
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
		$return_array = json_encode($return_array);

		if($config->use_db_data === 'yes')
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

			// Clear cache from url.
			if ($oCacheHandler)
			{
				$oCacheHandler->delete($oCacheHandler->getGroupKey('parserlink', "url:$url:sns_type:$sns_type:embed:" . $config->{$configSnsEmbedName}));
			}
		}
		echo $return_array;
		exit();
	}
}
