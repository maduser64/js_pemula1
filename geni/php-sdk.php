<?php
require_once "../php-plugins/vendor/autoload.php";

use Urlcrypt\Urlcrypt;

class FacebookPHP {

	protected $app_id = '';
	protected $app_secret = '';

	protected $graph_fb = 'https://graph.facebook.com/v2.7/';

	protected $salt = '';

	public $access_token;

	public function __construct() {

		$this->access_token = str_replace("access_token=", "", file_get_contents("https://graph.facebook.com/oauth/access_token?client_id=" . $this->app_id . "&client_secret=" . $this->app_secret . "&grant_type=client_credentials"));
	}

	public function getRequest($url, $params = [], $full = false) {
		if (!$full) {
			$url = $this->graph_fb . $url . '?access_token=' . $this->access_token;

			if (!filter_var($url, FILTER_VALIDATE_URL)) {
				return false;
			}

			$params = http_build_query($params);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url . '&' . $params);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
		} else {

			if (!filter_var($url, FILTER_VALIDATE_URL)) {
				return false;
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($ch);
			curl_close($ch);
		}

		return $response;

	}

	//Params gid = facebook group id;
	public function getGroupFeed($gid, $next_url = null) {

		if ($next_url == null) {

			$params = [
				'limit' => '5',
				'date_format' => 'U',
				'fields' => 'id,caption,created_time,description,from,icon,link,message,message_tags,name,object_id,picture,shares,status_type,story,to,type',
			];

			$request = $this->getRequest($gid . '/feed', $params);

			$request = json_decode($request, true);

			$request['paging']['previous'] = $this->encrypt($request['paging']['previous']);
			$request['paging']['next'] = $this->encrypt($request['paging']['next']);

			$request = json_encode($request);

		} else {

			$next_url = $this->decrypt($next_url);

			$request = $this->getRequest($next_url, [], true);

			if (!$request) {
				return false;
			}

			$request = json_decode($request, true);

			if (isset($request['data']) && $request['data'] != null) {
				$request['paging']['previous'] = $this->encrypt($request['paging']['previous']);
				$request['paging']['next'] = $this->encrypt($request['paging']['next']);

				$request = json_encode($request);
			} else {
				$request = json_encode($request);
			}

		}

		return $request;
	}

	public function getImageLink($object_id) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->graph_fb . $object_id . '?fields=images&access_token=' . $this->access_token);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$response = curl_exec($ch);
		curl_close($ch);

		if (!$response) {
			$response = false;
		} else {
			$response = json_decode($response, true);
			$response = $response['images'][0]['source'];
		}

		return $response;
	}

	public function getImage($link) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $link);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$response = curl_exec($ch);
		curl_close($ch);

		return $response;
	}

	public function getLikesAndComments($id) {
		$url = $this->graph_fb . $id . '?fields=likes.limit(0).summary(true),comments.limit(0).summary(true)&access_token=' . $this->access_token;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		$response = curl_exec($ch);
		curl_close($ch);

		if ($response) {
			$response = json_decode($response, true);
			$array_response = ['likes' => $response['likes']['summary']['total_count'], 'comments' => $response['comments']['summary']['total_count']];
			$response = json_encode($array_response);
		}

		return $response;
	}

	//encrypt and decrypt
	protected function encrypt($string) {
		Urlcrypt::$key = $this->salt;
		return Urlcrypt::encrypt($string);
	}
	protected function decrypt($data) {
		Urlcrypt::$key = $this->salt;
		return Urlcrypt::decrypt($data);
	}
}