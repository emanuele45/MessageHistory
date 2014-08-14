<?php

/**
 * Message History
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

class MessageHistory_Controller extends Action_Controller
{
	private function __get($name)
	{
		if ($name === 'history')
		{
			$db = database();
			
			$this->history = new MessageHistory($db, $this->msg);
		}
	}

	public function pre_dispatch()
	{
		require_once(SUBSDIR . '/MessageHistory.class.php');
		loadLanguage('MessageHistory');
	}

	public function action_index()
	{
		return $this->action_list();
	}

	public function action_index_api()
	{
		return $this->action_list_api();
	}

	public function action_list()
	{
		global $txt;

		$this->msg = isset($_REQUEST['msg']) ? (int) $_REQUEST['msg'] : 0;

		require_once(SUBSDIR . '/Messages.subs.php');
		$msg = basicMessageInfo($this->msg);

		$list_options = array(
			'id' => 'list_message_history',
			'title' => sprintf($txt['history_title'], $msg['subject']),
			'items_per_page' => 20,
			'base_href' => $scripturl . '?action=MessageHistory;sa=list',
			'default_sort_col' => 'modified_time',
			'get_items' => array(
				'function' => array($this, 'list_getHistory')
			),
			'get_count' => array(
				'function' => array($this, 'list_getHistoryCount')
			),
			'no_items_label' => $txt['history_no_msg'],
			'columns' => array(
				'body_short' => array(
					'header' => array(
						'value' => $txt['message'],
					),
					'data' => array(
						'db' => 'body_short',
					),
					'sort' => array(
						'default' => 'body',
						'reverse' => 'body_asc',
					),
				),
				'modified_name' => array(
					'header' => array(
						'value' => $txt['history_modified_by'],
					),
					'data' => array(
						'db' => 'modified_name',
					),
					'sort' => array(
						'default' => 'name',
						'reverse' => 'name_asc',
					),
				),
				'modified_time' => array(
					'header' => array(
						'value' => $txt['history_modified_on'],
					),
					'data' => array(
						'db' => 'modified_time',
					),
					'sort' => array(
						'default' => 'time',
						'reverse' => 'time_asc',
					),
				),
			),
		);
	}

	public function action_list_api()
	{
		global $context;

		$history = $this->getHistory();

		if ($_REQUEST['api'] === 'json')
		{
			loadTemplate('Json');
			$context['json_data'] = $history;
		}
		else
		{
			loadTemplate('Xml');
			// @todo xml response requires some restructuring of the array
		}
	}

	private function getHistory()
	{
		$this->msg = isset($_REQUEST['msg']) ? (int) $_REQUEST['msg'] : 0;
		$start = isset($_REQUEST['start']) ? (int) $_REQUEST['start'] : 0;
		$sort = isset($_REQUEST['sort']) && in_array($_REQUEST['sort'], array('up', 'down')) ? $_REQUEST['sort'] : 'up';

		return array(
			'msgs' => $this->list_getHistory($start, 10, $sort),
			'count' => $this->list_getHistoryCount()
		);
	}

	private function list_getHistory($start, $per_page, $sort)
	{
		$data = $this->history->msgHistory($start, $per_page, $sort);

		foreach ($data as $key => $val)
		{
			$data[$key]['body_short'] = Util::shorten_text($val['body'], 250, true);
		}

		return $data;
	}

	private function list_getHistoryCount()
	{
		return $this->history->countMsgHistory();
	}
}