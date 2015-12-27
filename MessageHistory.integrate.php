<?php

class Message_History_Integrate
{
	public static function before_modify_post(&$messages_columns, &$update_parameters, &$msgOptions, &$topicOptions, &$posterOptions, &$messageInts)
	{
		global $modSettings, $user_info;

		if (empty($msgOptions['modify_time']))
			return;

		$db = database();

		require_once(SUBSDIR . '/MessageHistory.class.php');
		$history = new MessageHistory($db, $msgOptions['id']);
		$history->storeMsg($msgOptions['modify_time'], $user_info['id'], $msgOptions['modify_name']);
	}

	public static function display_message_list($messages, $posters)
	{
		$modified = self::getModified($messages);

		if (!empty($modified))
		{
			loadTemplate('MessageHistory');
			loadJavascriptFile('//ajax.googleapis.com/ajax/libs/angularjs/1.2.23/angular.min.js');
			loadJavascriptFile('MessageHistory.js');
			loadCSSFile('MessageHistory.css');
			Template_Layers::getInstance()->addAfter('messages_history', 'messages_informations');
		}
	}

	public static function prepare_display_context(&$output, &$message)
	{
		if (isset($output['modified']['last_edit_text']))
			$output['modified']['last_edit_text'] = '<a href ng-click="history.loadHistory(' . $output['id'] . ')">' . $output['modified']['last_edit_text'] . '</a>';
	}

	private static function getModified($messages)
	{
		$db = database();

		$request = $db->query('', '
			SELECT id_msg, modified_time
			FROM {db_prefix}messages
			WHERE id_msg IN ({array_int:messages})
			LIMIT {int:count_msgs}',
			array(
				'messages' => $messages,
				'count_msgs' => count($messages),
			)
		);

		$modified = array();
		while ($row = $db->fetch_assoc($request))
		{
			if (!empty($row['modified_time']))
				$modified[] = $row['id_msg'];
		}
		$db->free_result($request);

		return $modified;
	}
}

