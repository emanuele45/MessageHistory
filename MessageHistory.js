/**
 * Message History
 *
 * @author  emanuele
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 *
 * @version 0.0.1
 */

(function(){
	var app = angular.module('messagehistory', []);

	app.controller('MessageHistory', ['$http', '$sce', '$scope', '$document', function($http, $sce, $scope, $document) {
		var element = $(document.getElementById('messagehistory_container'));
		var history = this;
		var clicked = false;
		history.msg = 0;
		history.msgs = {};
		history.show = false;

		/**
		 * This makes possible to click on any place of the page outside the
		 * controller and close the messages container
		 */
		$document.bind('click', function(event) {
			var isClickedElementChildOfPopup = element.find(event.target).length > 0;

			if (isClickedElementChildOfPopup || clicked)
			{
				if (clicked)
					clicked = false;
				return;
			}

			history.hide();
			$scope.$apply();
		});

		/**
		 * Function that fetches the previous versions of the message from the server
		 * @todo extract the fetching itself, so that it can be reused for pagination
		 */
		this.loadHistory = function(id) {
			history.show = false;
			if (id !== 0)
			{
				$http.get(elk_scripturl + "?action=MessageHistory;sa=list;xml;api=json;msg=" + id)
					.success(function(data) {
						history.msgs = data.msgs;
						history.show = true;
					});
			}
		};

		/**
		 * Returns if the overlay should be visible or not
		 */
		this.isVisible = function() {
			return history.show;
		};

		/**
		 * Takes care of hiding the overlay setting this.show to false
		 */
		this.hide = function() {
			history.show = false;
		};

		/**
		 * Returns an unsafe string (used for the body
		 * @todo Could be useful for the name as well in conjunction with the colored names addons
		 */
		this.unsafeString = function(string) {
			return $sce.trustAsHtml(string);
		}
	}]);
})();