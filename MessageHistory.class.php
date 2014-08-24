<?php

/**
 * Message History
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

class MessageHistoryHooks
{
	public static function before_modify_post(&$messages_columns, &$update_parameters, &$msgOptions, &$topicOptions, &$posterOptions, &$messageInts)
	{
		global $modSettings, $user_info;

		if (empty($msgOptions['modify_time']))
			return;

		$db = database();

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

class MessageHistory
{
	private $db = null;
	private $id_msg = 0;
	private $time = 0;
	private $by = 0;
	private $body = '';

	public function __construct($db, $msg)
	{
		$this->db = $db;
		$this->id_msg = (int) $msg;
	}

	public function storeMsg($time, $by, $name)
	{
		$this->time = $time;
		$this->name = $name;
		$this->by = $by;

		$this->getMsg();
		$this->saveInHistory();
	}

	public function msgHistory($start, $limit, $sort = 'up')
	{
		return $this->readHistory($start, $limit, $sort);
	}

	public function countMsgHistory()
	{
		return $this->countHistory();
	}

	private function countHistory()
	{
		$request = $this->db->query('', '
			SELECT COUNT(*)
			FROM {db_prefix}messages_history
			WHERE id_msg = {int:message}',
			array(
				'message' => $this->id_msg
			)
		);

		list ($count) = $this->db->fetch_row($request);
		$this->db->free_result($request);

		return $count;
	}

	private function readHistory($start, $limit, $sort)
	{
		$sortables = array(
			'time' => 'modified_time DESC',
			'time_asc' => 'modified_time ASC',
			'name' => 'modified_name DESC',
			'name_asc' => 'modified_name ASC',
			'body' => 'body DESC',
			'body_asc' => 'body ASC',
		);

		$request = $this->db->query('', '
			SELECT mh.body, mh.modified_time, mh.modified_id,
				IFNULL(mem.member_name, mh.modified_name) AS modified_name, mem.avatar,
				IFNULL(a.id_attach, 0) AS id_attach, a.filename, a.attachment_type, mem.email_address
			FROM {db_prefix}messages_history AS mh
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = mh.modified_id)
				LEFT JOIN {db_prefix}attachments AS a ON (a.id_member = mh.modified_id AND a.id_member != 0)
				LEFT JOIN {db_prefix}messages AS m ON (mh.id_msg = m.id_msg)
			WHERE mh.id_msg = {int:message}
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			array(
				'message' => $this->id_msg,
				'start' => $start,
				'limit' => $limit,
				'sort' => isset($sortables[$sort]) ? $sortables[$sort] : $sortables['time']
			)
		);

		$msgs = array();
		while ($row = $this->db->fetch_assoc($request))
		{
			$row['body'] = censorText($row['body']);
			$row['body'] = parse_bbc($row['body']);
			$row['avatar'] = determineAvatar($row);
			$msgs[] = $row;
		}
		$this->db->free_result($request);

		return $msgs;
	}

	private function saveInHistory()
	{
		$this->db->insert('',
			'{db_prefix}messages_history',
			array(
				'id_msg' => 'int',
				'body' => 'string',
				'modified_time' => 'int',
				'modified_name' => 'string-255',
				'modified_id' => 'int',
			),
			array(
				$this->id_msg,
				$this->body,
				$this->time,
				$this->name,
				$this->by,
			),
			array('id_history')
		);
	}

	private function getMsg()
	{
		$request = $this->db->query('', '
			SELECT body
			FROM {db_prefix}messages
			WHERE id_msg = {int:message}',
			array(
				'message' => $this->id_msg
			)
		);

		list ($this->body) = $this->db->fetch_row($request);
		$this->db->free_result($request);
	}
}