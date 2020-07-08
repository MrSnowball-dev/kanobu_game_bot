<?php
ini_set('display_errors', 1);
include 'config.php';
header('Content-Type: text/html; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$api = 'https://api.telegram.org/bot'.$tg_bot_token;

$input = file_get_contents('php://input');
$output = json_decode($input, TRUE); //сюда приходят все запросы по вебхука

//телеграмные события
$chat_id = isset($output['message']['chat']['id']) ? $output['message']['chat']['id'] : 'chat_id_empty'; //отделяем id чата, откуда идет обращение к боту
$chat = isset($output['message']['chat']['title']) ? $output['message']['chat']['title'] : 'chat_title_empty';
$chat_type = isset($output['message']['chat']['type']) ? $output['message']['chat']['type'] : 'chat_type_empty';
$new_chat_title = isset($output['message']['new_chat_title']) ? $output['message']['new_chat_title'] : 'new_chat_title_empty';
$message = isset($output['message']['text']) ? $output['message']['text'] : 'message_text_empty'; //сам текст сообщения
$user = isset($output['message']['from']['username']) ? $output['message']['from']['username'] : 'origin_user_empty';
$user_language_code = isset($output['message']['from']['language_code']) ? $output['message']['from']['language_code'] : 'no_language_set';
$user_id = isset($output['message']['from']['id']) ? $output['message']['from']['id'] : 'origin_user_id_empty';
$message_id = isset($output['message']['message_id']) ? $output['message']['message_id'] : 'message_id_empty';
$dice = isset($output['message']['dice']) ? $output['message']['dice'] : 'dice_empty';
$dice_emoji = isset($output['message']['dice']['emoji']) ? $output['message']['dice']['emoji'] : 'dice_emoji_empty';
$dice_result = isset($output['message']['dice']['value']) ? $output['message']['dice']['value'] : 'dice_value_empty';

$inline = isset($output['inline_query']) ? $output['inline_query'] : 'inline_query_empty';
$inline_user_id = isset($output['inline_query']['from']['id']) ? $output['inline_query']['from']['id'] : 'inline_user_id_empty';
$inline_user_first_name = isset($output['inline_query']['from']['first_name']) ? $output['inline_query']['from']['first_name'] : 'inline_user_first_name_empty';
$inline_username = isset($output['inline_query']['from']['username']) ? "@".$output['inline_query']['from']['username'] : $inline_user_first_name;
$query_id = isset($inline['id']) ? $inline['id'] : 'inline_query_id_empty';
$query = isset($inline['query']) ? $inline['query'] : 'inline_query_empty';

$callback_query = isset($output['callback_query']) ? $output['callback_query'] : 'callback_query_empty'; //сюда получаем все, что приходит от inline клавиатуры
$callback_id = isset($callback_query['id']) ? $callback_query['id'] : 'callback_id_empty';
$callback_data = isset($callback_query['data']) ? $callback_query['data'] : 'callback_data_empty'; //ответ от клавиатуры идет сюда
$callback_chat_id = isset($callback_query['chat_instance']) ? $callback_query['chat_instance'] : 'callback_chat_id_empty'; //id чата, где был вызов клавиатуры
$callback_user_id = isset($callback_query['from']['id']) ? $callback_query['from']['id'] : 'callback_user_id_empty'; //id юзера, что нажал на клаву
$callback_user_first_name = isset($callback_query['from']['first_name']) ? $callback_query['from']['first_name'] : 'callback_user_first_name_empty';
$callback_username = isset($callback_query['from']['username']) ? "@".$callback_query['from']['username'] : $callback_user_first_name;
$callback_message_id = isset($callback_query['inline_message_id']) ? $callback_query['inline_message_id'] : 'callback_message_id_empty'; //id того сообщения, в котором нажата кнопка клавиатуры

echo "Init successful.\n";

$start_texts = array('Я в деле!', 'Поехали!', 'Го!', 'Раскатаю на изи');
$end_texts = array('Матч-реванш!', 'Играем до трёх!', 'Ещё разок!', 'Подожди, я настроюсь...', 'Так нечестно!', 'ДА КАК ТАК?!');
//----------------------------------------------------------------------------------------------------------------------------------//


$markdownify_array = [
	//In all other places characters '_‘, ’*‘, ’[‘, ’]‘, ’(‘, ’)‘, ’~‘, ’`‘, ’>‘, ’#‘, ’+‘, ’-‘, ’=‘, ’|‘, ’{‘, ’}‘, ’.‘, ’!‘ must be escaped with the preceding character ’\'.
	'>' => "\>",
	'#' => "\#",
	'+' => "\+",
	'-' => "\-",
	'=' => "\=",
	'|' => "\|",
	'{' => "\{",
	'}' => "\}",
	'.' => "\.",
	'!' => "\!",
	'_' => "\_",
	'*' => "\*",
	'[' => "\[",
	']' => "\]",
	'(' => "\(",
	')' => "\)",
	'~' => "\~",
	'`' => "\`",
];

$markdownify_array_user = [
	'_' => '\_'
];

if ($message == '/start') {
	$setup_keyboard = ['inline_keyboard' => [
		[['text' => 'Начать игру', 'switch_inline_query' => '']]
	]];
	sendMessage($chat_id, "Хотите сыграть в Камень Ножницы Бумагу?\n\nНажмите на кнопку чтобы выбрать чат с собеседником:", $setup_keyboard);
}

if ($query_id !== 'inline_query_id_empty') {
	$knb_keyboard = ['inline_keyboard' => [
		[['text' => $start_texts[array_rand($start_texts)], 'callback_data' => 'stage_1:'.$inline_user_id.':'.$inline_username]]
	]];
	sendNewGame($query_id, strtr($inline_username, $markdownify_array_user)." хочет сыграть в Камень Ножницы Бумагу\!\n\nНажми на кнопку чтобы присоединиться:", $knb_keyboard);
}

$callback_data = explode(':', $callback_data);
switch ($callback_data[0]) {
	case 'callback_data_empty':
	break;

	case 'stage_1':
		if ($callback_user_id == $callback_data[1]) {
			$knb_keyboard = ['inline_keyboard' => [
				[['text' => $start_texts[array_rand($start_texts)], 'callback_data' => 'stage_1:'.$callback_user_id.':'.$callback_username]]
			]];
			updateMessage($callback_message_id, strtr($callback_username, $markdownify_array)." хочет сыграть в Камень Ножницы Бумагу\!\n\nНажми на кнопку чтобы присоединиться:", $knb_keyboard, "Вы не можете играть сами с собой, какой в этом смысл?");
			break;
		}
		$db = mysqli_connect($db_host, $db_username, $db_pass, $db_schema);
		mysqli_set_charset($db, 'utf8mb4');
		mysqli_query($db, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
		if (mysqli_connect_errno()) error_log("Failed to connect to MySQL: " . mysqli_connect_error());
			else echo "MySQL connect successful.\n";

		mysqli_query($db, "INSERT INTO history (game_id, player_1, player_1_username, player_2, player_2_username) values ('".$callback_message_id."', ".$callback_data[1].", '".$callback_data[2]."', ".$callback_user_id.", '".$callback_username."')");

		$game_keyboard = ['inline_keyboard' => [
			[['text' => '✊', 'callback_data' => 'stage_2:✊:'.$callback_user_id], ['text' => '✋', 'callback_data' => 'stage_2:✋:'.$callback_user_id], ['text' => '✌', 'callback_data' => 'stage_2:✌:'.$callback_user_id]]
		]];

		updateMessage($callback_message_id, "Отлично\!\n\nИграют ".strtr($callback_data[2], $markdownify_array)." и ".strtr($callback_username, $markdownify_array)."\n\nОжидаю хода\.\.\.", $game_keyboard);

		mysqli_close($db);
	break;
	
	case 'stage_2':
		$db = mysqli_connect($db_host, $db_username, $db_pass, $db_schema);
		mysqli_set_charset($db, 'utf8mb4');
		mysqli_query($db, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
		if (mysqli_connect_errno()) error_log("Failed to connect to MySQL: " . mysqli_connect_error());
			else echo "MySQL connect successful.\n";

		$where_to_put_play = mysqli_fetch_assoc(mysqli_query($db, "SELECT * FROM history WHERE game_id='".$callback_message_id."'"));
		if ($callback_username == $where_to_put_play['player_1_username'] || $callback_username == $where_to_put_play['player_2_username']) {
			if ($where_to_put_play['player_1'] == $callback_user_id) {
				mysqli_query($db, "UPDATE history set play_1='".$callback_data[1]."' WHERE game_id='".$callback_message_id."'");
			} elseif ($where_to_put_play['player_2'] == $callback_user_id) {
				mysqli_query($db, "UPDATE history set play_2='".$callback_data[1]."' WHERE game_id='".$callback_message_id."'");
			}
			
			$game_keyboard = ['inline_keyboard' => [
				[['text' => '✊', 'callback_data' => 'stage_3:✊:'.$callback_user_id], ['text' => '✋', 'callback_data' => 'stage_3:✋:'.$callback_user_id], ['text' => '✌', 'callback_data' => 'stage_3:✌:'.$callback_user_id]]
			]];
			
			updateMessage($callback_message_id, "Отлично\!\n\nИграют ".strtr($where_to_put_play['player_1_username'], $markdownify_array)." и ".strtr($where_to_put_play['player_2_username'], $markdownify_array)."\n\n".strtr($callback_username, $markdownify_array)." сделал ход, жду ответа", $game_keyboard);
		
		} else {
			$game_keyboard = ['inline_keyboard' => [
				[['text' => '✊', 'callback_data' => 'stage_2:✊:'.$callback_user_id], ['text' => '✋', 'callback_data' => 'stage_2:✋:'.$callback_user_id], ['text' => '✌', 'callback_data' => 'stage_2:✌:'.$callback_user_id]]
			]];
	
			updateMessage($callback_message_id, "Отлично\!\n\nИграют ".strtr($where_to_put_play['player_1_username'], $markdownify_array)." и ".strtr($where_to_put_play['player_2_username'], $markdownify_array)."\n\nОжидаю хода\.\.\.", $game_keyboard, "Ходить могут только игроки ".$where_to_put_play['player_1_username']." и ".$where_to_put_play['player_2_username']);

			mysqli_close($db);
			break;
		}
		mysqli_close($db);
	break;

	case 'stage_3':
		$db = mysqli_connect($db_host, $db_username, $db_pass, $db_schema);
		mysqli_set_charset($db, 'utf8mb4');
		mysqli_query($db, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
		if (mysqli_connect_errno()) error_log("Failed to connect to MySQL: " . mysqli_connect_error());
			else echo "MySQL connect successful.\n";

		$where_to_put_play = mysqli_fetch_assoc(mysqli_query($db, "SELECT * FROM history WHERE game_id='".$callback_message_id."'"));
		if ($callback_username == $where_to_put_play['player_1_username'] || $callback_username == $where_to_put_play['player_2_username']) {
			if (($callback_user_id == $where_to_put_play['player_1'] && $where_to_put_play['play_1'] !== NULL) || ($callback_user_id == $where_to_put_play['player_2'] && $where_to_put_play['play_2'] !== NULL)) {
				$game_keyboard = ['inline_keyboard' => [
					[['text' => '✊', 'callback_data' => 'stage_3:✊:'.$callback_user_id], ['text' => '✋', 'callback_data' => 'stage_3:✋:'.$callback_user_id], ['text' => '✌', 'callback_data' => 'stage_3:✌:'.$callback_user_id]]
				]];
				
				updateMessage($callback_message_id, "Отлично\!\n\nИграют ".strtr($where_to_put_play['player_1_username'], $markdownify_array)." и ".strtr($where_to_put_play['player_2_username'], $markdownify_array)."\n\n".strtr($callback_username, $markdownify_array)." сделал ход, жду ответа", $game_keyboard, "Вы уже ходили!");
				break;
			}

			if ($where_to_put_play['player_1'] == $callback_user_id) {
				mysqli_query($db, "UPDATE history set play_1='".$callback_data[1]."' WHERE game_id='".$callback_message_id."'");
			} elseif ($where_to_put_play['player_2'] == $callback_user_id) {
				mysqli_query($db, "UPDATE history set play_2='".$callback_data[1]."' WHERE game_id='".$callback_message_id."'");
			}
	
			$results = mysqli_fetch_assoc(mysqli_query($db, "SELECT play_1, play_2, player_1_username, player_2_username FROM history where game_id='".$callback_message_id."'"));
			$winner = NULL;
	
			if ($results['play_1'] == '✊') { 
				if ($results['play_2'] == '✋') {
					$winner = 2;
				} 
				if ($results['play_2'] == '✌') {
					$winner = 1;
				}
			}

			if ($results['play_1'] == '✋') {
				if ($results['play_2'] == '✊') {
					$winner = 1;
				} 
				if ($results['play_2'] == '✌') {
					$winner = 2;
				}
			}

			if ($results['play_1'] == '✌') {
				if ($results['play_2'] == '✊') {
					$winner = 2;
				} 
				if ($results['play_2'] == '✋') {
					$winner = 1;
				} 
			}
	
			
			$game_keyboard = ['inline_keyboard' => [
				[['text' => $end_texts[array_rand($end_texts)], 'switch_inline_query_current_chat' => '']]
			]];
			
			if ($winner == NULL) {
				mysqli_query($db, "UPDATE history set result='tie' where game_id='".$callback_message_id."'");
				updateMessage($callback_message_id, "Игра окончена\!\n\nНичья, ".strtr($results['player_1_username'], $markdownify_array)." и ".strtr($results['player_2_username'], $markdownify_array)." выбрали ".$results['play_1'], $game_keyboard);
			} else {
				if ($winner == 1) {
					mysqli_query($db, "UPDATE history set result='player_1_won' where game_id='".$callback_message_id."'");
					updateMessage($callback_message_id, "Игра окончена\!\n\nВыиграл ".strtr($results['player_1_username'], $markdownify_array)." c ".$results['play_1']." против ".strtr($results['player_2_username'], $markdownify_array)." и его ".$results['play_2'], $game_keyboard);
				} else {
					mysqli_query($db, "UPDATE history set result='player_2_won' where game_id='".$callback_message_id."'");
					updateMessage($callback_message_id, "Игра окончена\!\n\nВыиграл ".strtr($results['player_2_username'], $markdownify_array)." c ".$results['play_2']." против ".strtr($results['player_1_username'], $markdownify_array)." и его ".$results['play_1'], $game_keyboard);
				}
			}
			mysqli_close($db);
			break;
		} else {
			$game_keyboard = ['inline_keyboard' => [
				[['text' => '✊', 'callback_data' => 'stage_3:✊:'.$callback_user_id], ['text' => '✋', 'callback_data' => 'stage_3:✋:'.$callback_user_id], ['text' => '✌', 'callback_data' => 'stage_3:✌:'.$callback_user_id]]
			]];
			
			if (is_null($where_to_put_play['play_1'])) {
				updateMessage($callback_message_id, "Отлично\!\n\nИграют ".strtr($where_to_put_play['player_1_username'], $markdownify_array)." и ".strtr($where_to_put_play['player_2_username'], $markdownify_array)."\n\n".strtr($where_to_put_play['player_2_username'], $markdownify_array)." сделал ход, жду ответа", $game_keyboard, "Ходить могут только игроки ".$where_to_put_play['player_1_username']." и ".$where_to_put_play['player_2_username']);
			} elseif (is_null($where_to_put_play['play_2'])) {
				updateMessage($callback_message_id, "Отлично\!\n\nИграют ".strtr($where_to_put_play['player_1_username'], $markdownify_array)." и ".strtr($where_to_put_play['player_2_username'], $markdownify_array)."\n\n".strtr($where_to_put_play['player_1_username'], $markdownify_array)." сделал ход, жду ответа", $game_keyboard, "Ходить могут только игроки ".$where_to_put_play['player_1_username']." и ".$where_to_put_play['player_2_username']);
			}
			
			break;
		}
		mysqli_free_result($where_to_put_play);
		mysqli_close($db);
	break;
}

//----------------------------------------------------------------------------------------------------------------------------------//

//отправка форматированного сообщения
function sendMessage($chat_id, $message, $inline_keyboard = NULL) {
	if ($inline_keyboard === NULL) {
		file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&parse_mode=MarkdownV2');
	} else {
		file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&reply_markup='.json_encode($inline_keyboard).'&parse_mode=MarkdownV2');
	}
}

//редактирование сообщения
function updateMessage($message_id, $new_message, $inline_keyboard, $alert_text = NULL)
{
	if ($inline_keyboard === NULL) {
		file_get_contents($GLOBALS['api'].'/editMessageText?inline_message_id='.urlencode($message_id).'&text='.urlencode($new_message).'&parse_mode=MarkdownV2');
	} else {
		file_get_contents($GLOBALS['api'].'/editMessageText?inline_message_id='.urlencode($message_id).'&text='.urlencode($new_message).'&reply_markup='.json_encode($inline_keyboard).'&parse_mode=MarkdownV2');
		if ($alert_text === NULL) {
			file_get_contents($GLOBALS['api'].'/answerCallbackQuery?callback_query_id='.$GLOBALS['callback_id']);
		} else {
			file_get_contents($GLOBALS['api'].'/answerCallbackQuery?callback_query_id='.$GLOBALS['callback_id'].'&text='.urlencode($alert_text));
		}
	}
}

function sendNewGame($query_id, $info_message, $keyboard) {

	$result = [[
		'type' => 'article',
		'id' => '1',
		'title' => 'Камень Ножницы Бумага',
		'input_message_content' => ['message_text' => $info_message, 'parse_mode' => 'MarkdownV2'],
		'reply_markup' => $keyboard,
		'description' => 'Начать игру'
	]];
	file_get_contents($GLOBALS['api'].'/answerInlineQuery?inline_query_id='.$query_id.'&results='.json_encode($result).'&cache_time=0');
}

//отправка приветствия
function sendReply($chat_id, $message, $message_id_to_reply) {
	file_get_contents($GLOBALS['api'].'/sendMessage?chat_id='.$chat_id.'&text='.urlencode($message).'&parse_mode=MarkdownV2'.'&reply_to_message_id='.$message_id_to_reply);
}

//удаление служебного сообщения
function deleteMessage($chat_id, $message_id) {
	file_get_contents($GLOBALS['api'].'/deleteMessage?chat_id='.$chat_id.'&message_id='.$message_id);
}

//покидание чата
function leaveChat($chat_id) {
	file_get_contents($GLOBALS['api'].'/leaveChat?chat_id='.$chat_id);
}

echo "End script."
?>
