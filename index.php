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
	foreach ($data['events'] as $event)
	{
		if ($event['type'] == 'message')
		{
			$uid = $event['source']['userId'];
			$rid = $event['source']['roomId'];
			$gid = $event['source']['groupId'];
			if($event['message']['type'] == 'text')
			{
				if($rid != "" || $gid != ""){
					$myfile = fopen("chatlogs_group.txt", "a") or die("Unable to open file!");
					$txt = $event['message']['text'] . " <> ";
					fwrite($myfile,$txt . "\n");
					fclose($myfile);
				}elseif($uid != ""){
					$response = $bot->getProfile($event['source']['userId']);
					if ($response->isSucceeded()) {
							$profile = $response->getJSONDecodedBody();
							$uid = $event['source']['userId'];
							$name = $profile['displayName'];
							$myfile = fopen("chatlogs.txt", "a") or die("Unable to open file!");
							$txt = $name . " | " . $uid . " | " . $event['message']['text'] . " <> ";
							fwrite($myfile,$txt . "\n");
							fclose($myfile);
					}
				}

				// send same message as reply to user
				$result = $bot->replyText($event['replyToken'], generate_emo($event['message']['text']));

				// or we can use pushMessage() instead to send reply message
				// $textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($event['message']['text']);
				// $result = $bot->pushMessage($event['source']['userId'], $textMessageBuilder);

				return $result->getHTTPStatus() . ' ' . $result->getRawBody();
			}
		}
			elseif($event['type'] == 'follow')
		{
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

// $app->get('/push/{to}/{message}', function ($request, $response, $args)
// {
// 	$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient($_ENV['CHANNEL_ACCESS_TOKEN']);
// 	$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => $_ENV['CHANNEL_SECRET']]);

// 	$textMessageBuilder = new \LINE\LINEBot\MessageBuilder\TextMessageBuilder($args['message']);
// 	$result = $bot->pushMessage($args['to'], $textMessageBuilder);

// 	return $result->getHTTPStatus() . ' ' . $result->getRawBody();
// });

/* JUST RUN IT */
$app->run();
