angular.module('Index', ['ngCookies'])

.controller('MainController', ['$scope', '$http', '$cookies', 'getLevels', function($scope, $http, $cookies, getLevels){
	
	$scope.date_fr = new Date();
	$scope.date_fr.setHours(0);
	$scope.date_fr.setMinutes(0);
	$scope.date_fr.setSeconds(0);
	$scope.date_to = $scope.date_fr;
	var el = document.getElementById.bind(document);
	
	$scope.cols = [];
	var i,j,chunk = 3;
	for (i=0,j = getLevels.length; i<j; i+=chunk)
	{
		$scope.cols.push(getLevels.slice(i,i+chunk));
	}
	
	var arr_levels;
  $scope.conv_lvls_to_arr = function()
	{
		arr_levels = [];
		angular.forEach(getLevels, function(obj)
		{
		  if(obj.selected)arr_levels.push(obj.level);
		});
	}
	
	$scope.limit_fetch = angular.isDefined($cookies.limit_fetch) ? Number($cookies.limit_fetch) : 300;
	
	var data_post = {};
	
	$scope.fetch = function()
	{
		angular.element(el('serch_text')).addClass('loading');
		$scope.msg = false;
		$cookies.limit_fetch = $scope.limit_fetch;
		
		var search_text = $scope.search_text;
		search_text = angular.isDefined(search_text) ? search_text : '';

		$scope.conv_lvls_to_arr();
		
		try
		{
			data_post = angular.toJson(
			{
				"date_fr": ($scope.date_fr.getTime()/1000),
				"date_to": ($scope.date_to.getTime()/1000 + (24*3600)),
				"limit_fetch": $scope.limit_fetch,
				"levels": arr_levels,
				"search_text": encodeURIComponent(search_text)
			});
		}
		catch(err)
		{
			angular.element(el('serch_text')).removeClass('loading');
			$scope.msg =
			{
				'class': 'warning',
				html: '<strong>Warning:</strong> ' + err.message
			};
			return;
		}

		$http
		({
			method: 'POST'
			,url: ''
			,data: 'json=' + data_post
			,cache: false
			,headers: {"X-Requested-With": "XMLHttpRequest", "Content-Type" : "application/x-www-form-urlencoded; charset=UTF-8"}
		})
		.success(function(data)
		{
			angular.element(el('serch_text')).removeClass('loading');
			try
			{
				data = angular.fromJson(data);
			}
			catch(e)
			{
				$scope.msg =
				{
					'class': 'warning',
					html: '<strong>Warning:</strong> Server response is not JSON.'
				};
				return;
			}
			
			if(data.msg)
				$scope.msg =
				{
					'class': data.msg.class,
					html: data.msg.html
				};
			
			if(angular.isUndefined(data.logs) || data.logs == '')
			{
				$scope.logs = [];
				$scope.stat_lvls = [];
				if(angular.isUndefined(data.msg))
				{
					$scope.msg =
					{
						'class': 'info',
						html: '<strong>Info:</strong> not found logs with the specified parameters'
					};
				}
				return;
			}
			else
			{
				$scope.logs = data.logs;
			}

			$scope.stat_lvls = [];
			for(var i = 0; i < $scope.logs.length; i++)
			{
				var level = $scope.logs[i].level;
				if(angular.isDefined($scope.stat_lvls[level]))
					$scope.stat_lvls[level]++;
				else
					$scope.stat_lvls[level] = 1;
			}
			
			/**
			 * Невеличкий костиль
			 */
			var symbol_percent = search_text.indexOf('%');
			if(symbol_percent != -1)
				$scope.search_text = search_text.substr(0, symbol_percent);
		})
		.error(function(data, status)
		{
			$scope.logs = [];
			angular.element(el('serch_text')).removeClass('loading');
			if(status == 401)
			{
				if(angular.isDefined(data.auth_url))
					window.location = data.auth_url;
				else
					$scope.msg =
					{
						'class': 'warning',
						html: '<strong>Warning:</strong> Authentication required.'
					};
			}
			else if(status === 500)
				$scope.msg =
				{
					'class': 'warning',
					html: '<strong>Warning:</strong> Request failed.'
				};
		});
	}
	
	$scope.f_date = function(val)
	{
		if($scope.search.$invalid)
			return;
		
		var date_fr = $scope.date_fr.getTime()/1000; // Convert to seconds
		var date_to = $scope.date_to.getTime()/1000 + (24*3600); // Convert to seconds and plus one day
		return (val.time >= date_fr && val.time <= date_to);
	}
	
	$scope.f_levels = function(val)
	{
		var res = false;
		for(var i = 0; i < arr_levels.length; i++)
		{
			if(val.level == arr_levels[i])
			{
				res = true;
				break;
			}
		}
		return res;
	}
	
	$scope.msg_close = function(){ $scope.msg = false; }
}])

.directive('resizable', ['$window', function($window) {
	return function($scope)
	{
		$scope.initializeWindowSize = function()
		{
			$scope.windowWidth  = $window.innerWidth;
			$scope.limit_to = $scope.windowWidth * .12;
		};
	  
		angular.element($window).bind("resize", function()
		{
			$scope.initializeWindowSize();
			$scope.$apply();
		});
	  
		$scope.initializeWindowSize();
	}
}])

.factory('getLevels', function(){
	return 	[
		{level: 'EMERGENCY', label: 'danger', selected: true},
		{level: 'ALERT', label: 'danger', selected: true},
		{level: 'CRITICAL', label: 'danger', selected: true},
		{level: 'ERROR', label: 'danger', selected: true},
		{level: 'WARNING', label: 'warning', selected: true},
		{level: 'NOTICE', label: 'default', selected: true},
		{level: 'INFO', label: 'info', selected: true}
	];
})

.filter('badge_level', ['getLevels', function(getLevels) {
    return function(val) {
		var res = '';
        for(var i=0; i < getLevels.length; i++)
		{
			if(val == getLevels[i].level)
			{
				res = getLevels[i].label;
				break;
			}
		}
		return res;
    };
}])

.filter('unsafe', ['$sce', function($sce) {
    return function(val) {
        return $sce.trustAsHtml(val);
    };
}])

;