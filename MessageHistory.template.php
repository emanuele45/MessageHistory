<?php

/**
 * Message History
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

function template_messages_history_above()
{
	echo '
	<div ng-app="messagehistory" ng-controller="MessageHistory as history">';
}

function template_messages_history_below()
{
	echo '
		<div class="messagehistory_back" ng-click="history.hide()" ng-show="history.isVisible()">
		</div>
		<div class="messagehistory_container" ng-show="history.isVisible()">
			<ul>
				<li class="message" ng-repeat="message in history.msgs">
				<span class="modifier">
					<span class="name">{{message.modified_name}}</span>
					<img class="avatar" ng-src="{{message.avatar.href}}" />
				</span>
				Message:
				<div ng-bind-html="history.unsafeString(message.body)"></div><br>
				</li>
		</div>
	</div>';
}
