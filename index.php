<?php

require __DIR__ . '/vendor/autoload.php';

use \LINE\LINEBot\SignatureValidator as SignatureValidator;

//load php file
include('function.php');

// load config
$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

// initiate app
$configs =  [
	'settings' => ['displayErrorDetails' => true],
];
$app = new Slim\App($configs);

/* ROUTES */
$app->get('/', function ($request, $response) {
	return "Lanjutkan!";
});

$app->post('/', function ($request, $response)
{
	// get request body and line signature header
	$body 	   = file_get_contents('php://input');
	$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'];

	// log body and signature
	file_put_contents('php://stderr', 'Body: '.$body);

	// is LINE_SIGNATURE exists in request header?
	if (empty($signature)){
		return $response->withStatus(400, 'Signature not set');
	}

	// is this request comes from LINE?
	if($_ENV['PASS_SIGNATURE'] == false && ! SignatureValidator::validateSignature($body, $_ENV['CHANNEL_SECRET'], $signature)){
		return $response->withStatus(400, 'Invalid signature');
	}

	// init bot
	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

	$data = json_decode($body, true);
	foreach ($data['events'] as $event){

		if ($event['type'] == 'message'){
			$uid = $event['source']['userId'];
			if($event['message']['type'] == 'text')	{
				$text = $event['message']['text'];
				if($uid == ""){
					$array = explode(" ",$text);
					if($array[0] == "/t"){
						$result = $bot->replyText($event['replyToken'], generate_emo(str_replace("/t ","",$text)));
						return $result->getHTTPStatus() . ' ' . $result->getRawBody();
					}
				}else{
					$result = $bot->replyText($event['replyToken'], generate_emo($text));
					return $result->getHTTPStatus() . ' ' . $result->getRawBody();
				}
			}
		}elseif($event['type'] == 'follow'){
			$response = $bot->getProfile($event['source']['userId']);
			if ($response->isSucceeded()) {
			    $profile = $response->getJSONDecodedBody();
			    $uid = $event['source']['userId'];
			    $name = $profile['displayName'];
			    $param = "Thank you " . $profile['displayName'] . " for following me :D\nType anything to translate into emoji character\n\nBug report : @cacadosman23";
			    $result = $bot->replyText($event['replyToken'], $param);
			}
			return $result->getHTTPStatus() . ' ' . $result->getRawBody();
		}
	}

});

$app->run();
