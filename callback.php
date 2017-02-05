<?php

	/* 輸入申請的Line Developers 資料  */
	$channel_id = "{1497601714}";
	$channel_secret = "{a6b4b1a80d9f25eb0a719fc92cef7d86}";
	$channel_access_token = "{3/cEBpOR0mjAMUtnHKrSrx3N6FnMVNPYfXBIwMO6HNGaljxuxTxZz2fGrmZYFwqfV3dvAWMa7FEGrmOONfbZ7or1wxYgpjbtFMS0Mkk+RftjvYSrUpThxAHGiivf2M662z2zM5P8BSKby0dJiBG3GQdB04t89/1O/w1cDnyilFU=}";

	$myURL = "https://radiant-thicket-36415.herokuapp.com/"


	//  當有人發送訊息給bot時 我們會收到的json
	// 	{
	// 	  "events": 
	// 	  [
	// 		  {
	// 			"replyToken": "nHuyWiB7yP5Zw52FIkcQobQuGDXCTA",
	// 			"type": "message",
	// 			"timestamp": 1462629479859,
	// 			"source": {
	// 				 "type": "user",
	// 				 "userId": "U206d25c2ea6bd87c17655609a1c37cb8"
	// 			 },
	// 			 "message": {
	// 				 "id": "325708",
	// 				 "type": "text",
	// 				 "text": "Hello, world"
	// 			  }
	// 		  }
	// 	  ]
	// 	}
	 
	 
	// 將收到的資料整理至變數
	$receive = json_decode(file_get_contents("php://input"));
	
	// 讀取收到的訊息內容
	$text = $receive->events[0]->message->text;
	
	// 讀取訊息來源的類型 	[user, group, room]
	$type = $receive->events[0]->source->type;
	
	// 由於新版的Messaging Api可以讓Bot帳號加入多人聊天和群組當中
	// 所以在這裡先判斷訊息的來源
	if ($type == "room")
	{
		// 多人聊天 讀取房間id
		$from = $receive->events[0]->source->roomId;
	} 
	else if ($type == "group")
	{
		// 群組 讀取群組id
		$from = $receive->events[0]->source->groupId;
	}
	else
	{
		// 一對一聊天 讀取使用者id
		$from = $receive->events[0]->source->userId;
	}
	
	// 讀取訊息的型態 [Text, Image, Video, Audio, Location, Sticker]
	$content_type = $receive->events[0]->message->type;
	
	// 準備Post回Line伺服器的資料 
	$header = ["Content-Type: application/json", "Authorization: Bearer {" . $channel_access_token . "}"];
	
	// 回覆訊息
	reply($content_type, $text);

	

	function reply($content_type, $message) {
	 
	 	global $header, $from, $receive;
	 	
		$url = "https://api.line.me/v2/bot/message/push";
		
		$data = ["to" => $from, "messages" => array(["type" => "text", "text" => $message])];
		
		switch($content_type) {
		
			case "text" :
				$content_type = "pesan text";
				$data = ["to" => $from, "messages" => array(["type" => "text", "text" => $message])];
				break;
				
			case "image" :
				$content_type = "pesan gambar";
				$message = getObjContent("jpeg");   // 讀取圖片內容
				$data = ["to" => $from, "messages" => array(["type" => "image", "originalContentUrl" => $message, "previewImageUrl" => $message])];
				break;
				
			case "video" :
				$content_type = "影片訊息";
				$message = getObjContent("mp4");   // 讀取影片內容
				$data = ["to" => $from, "messages" => array(["type" => "video", "originalContentUrl" => $message, "previewImageUrl" => $message])];
				break;
				
			case "audio" :
				$content_type = "語音訊息";
				$message = getObjContent("mp3");   // 讀取聲音內容
				$data = ["to" => $from, "messages" => array(["type" => "audio", "originalContentUrl" => $message[0], "duration" => $message[1]])];
				break;
				
			case "location" :
				$content_type = "位置訊息";
				$title = $receive->events[0]->message->title;
				$address = $receive->events[0]->message->address;
				$latitude = $receive->events[0]->message->latitude;
				$longitude = $receive->events[0]->message->longitude;
				$data = ["to" => $from, "messages" => array(["type" => "location", "title" => $title, "address" => $address, "latitude" => $latitude, "longitude" => $longitude])];
				break;
				
			case "sticker" :
				$content_type = "貼圖訊息";
				$packageId = $receive->events[0]->message->packageId;
				$stickerId = $receive->events[0]->message->stickerId;
				$data = ["to" => $from, "messages" => array(["type" => "sticker", "packageId" => $packageId, "stickerId" => $stickerId])];
				break;
				
			default:
				$content_type = "未知訊息";
				break;
	   	}
		
		$context = stream_context_create(array(
		"http" => array("method" => "POST", "header" => implode(PHP_EOL, $header), "content" => json_encode($data), "ignore_errors" => true)
		));
		file_get_contents($url, false, $context);
	}

	function getObjContent($filenameExtension){
		
		global $channel_access_token, $receive;
	
		$objID = $receive->events[0]->message->id;
		$url = 'https://api.line.me/v2/bot/message/'.$objID.'/content';
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Bearer {' . $channel_access_token . '}',
		));
		
		$json_content = curl_exec($ch);
		curl_close($ch);

		if (!$json_content) {
			return false;
		}
		
		$fileURL = './update/'.$objID.'.'.$filenameExtension;
		$fp = fopen($fileURL, 'w');
		fwrite($fp, $json_content);
		fclose($fp);
		
		if ($filenameExtension=="mp3"){
			require_once("../getID3/getid3/getid3.php");
			$getID3 = new getID3;
			$fileData = $getID3->analyze($fileURL);
			//$audioInfo = var_dump($fileData);
			$playSec = floor($fileData["playtime_seconds"]);
			$re = array($myURL.$objID.'.'.$filenameExtension, $playSec*1000);
			return $re;
		}

		return $myURL.$objID.'.'.$filenameExtension;
	}
?>
