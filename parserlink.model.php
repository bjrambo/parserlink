<?php
class parserlinkModel extends parserlink
{
	function init()
	{
	}

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
}
/* End of file */
