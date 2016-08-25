<?php
/**
 * include class and disable error reporting
 */
ini_set('display_errors', 0);
require_once 'php-sdk.php';

//init Facebook class
$Facebook = new FacebookPHP();

//You can set FB Group here
$fb_group_id = 35688476100;

/**
 * [responseImage - render image response]
 *
 * @param string $response [data image]
 *
 * @return string [data image]
 */
function responseImage($response) {
	header('Content-Type: image/jpeg');
	echo $response;
}

/**
 * [responseJson - render json response]
 *
 * @param string $response [json]
 *
 * @return string [json]
 */
function responseJson($response) {
	header('Content-Type: application/json');
	if (!$response) {
		echo '{"data":"empty response"}';
	} else {
		echo $response;
	}
}

/**
 * Init response
 */
if (isset($_GET['init']) && $_GET['init'] != 'null') {
	$response = $Facebook->getGroupFeed($fb_group_id);

	if (!$response) {
		responseJson(false);
	} else {
		responseJson($response);
	}

}

/**
 * Next response
 */
if (isset($_GET['next']) && $_GET['next'] != 'null') {

	$response = $Facebook->getGroupFeed($fb_group_id, $_GET['next']);

	if (!$response) {
		responseJson(false);
	} else {
		responseJson($response);
	}

}

/**
 * Object response
 */
if (isset($_GET['object_id']) && $_GET['object_id'] != 'null') {
	if ($_GET['type'] == 'image') {

		$link = $Facebook->getImageLink($_GET['object_id']);

		if (!$link) {
			responseJson(false);
		} else {
			$response = $Facebook->getImage($link);
			if (!$response) {
				responseJson(false);
			} else {
				responseImage($response);
			}
		}
	}

	if ($_GET['type'] == 'summary') {

		$response = $Facebook->getLikesAndComments($_GET['object_id']);

		if (!$response) {
			responseJson(false);
		} else {
			responseJson($response);
		}
	}
}
