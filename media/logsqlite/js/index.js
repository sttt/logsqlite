angular.module('Index', ['ngCookies'])

.controller('MainController', function($scope, $http, $cookies, getLevels){
	
	$scope.date_fr = new Date();
	$scope.date_fr.setHours(0);
	$scope.date_fr.setMinutes(0);
	$scope.date_fr.setSeconds(0);
	$scope.date_to = $scope.date_fr;
	
	$scope.$cookies = $cookies;
	$scope.levels = getLevels;
	
    $scope.conv_lvls_to_arr = function()
	{
		$scope.arr_levels = [];
		angular.forEach($scope.levels, function(obj)
		{
		  if(obj.selected)$scope.arr_levels.push(obj.level);
		});
	}
	
	$scope.cols = [];
	var i,j,chunk = 3;
	for (i=0,j = $scope.levels.length; i<j; i+=chunk)
	{
		$scope.cols.push($scope.levels.slice(i,i+chunk));
	}
	
	$scope.fetch = function()
	{
		var limit_fetch = $cookies.limit_fetch = angular.element(limit).val();
		var search_text = $scope.search.search_text.$viewValue;
		search_text = angular.isDefined(search_text) ? search_text : '';
		angular.element(serch_text).addClass('loading');
			
		$scope.conv_lvls_to_arr();
		$http
		({
			method: 'GET'
			,url: '?date_fr=' + $scope.search.date_fr.$viewValue
					+ '&date_to=' + $scope.search.date_to.$viewValue
					+ '&limit=' + limit_fetch
					+ '&levels=' + angular.toJson($scope.arr_levels)
					+ '&search_text=' + encodeURIComponent(search_text)
			,cache: false
			,headers: {"X-Requested-With": "XMLHttpRequest",}
		})
		.success(function(data)
		{
			if( ! angular.isArray(data))
			{
				console.log(data);
				return;
			}
			
			$scope.stat_lvls = [];
			for(var i = 0; i < data.length; i++)
			{
				var level = data[i].level;
				if(angular.isDefined($scope.stat_lvls[level]))
					$scope.stat_lvls[level]++;
				else
					$scope.stat_lvls[level] = 1;
			}
			$scope.logs = data;
			angular.element(serch_text).removeClass('loading');
		})
		.error(function(data, status)
		{
			angular.element(serch_text).removeClass('loading');
			if(status == 401 && angular.isDefined(data.auth_url))
				window.location = data.auth_url;
			return data = data || "Request failed";
		});
	}
	
	$scope.f_date = function(val)
	{
		var time_obj = new Date(val.dateinsert * 1000);
		var date_to = angular.copy($scope.date_to);
		date_to.setDate(date_to.getDate() + 1);
		return (time_obj >= $scope.date_fr && time_obj <= date_to);
	}
	
	$scope.f_levels = function(val)
	{
		var res = false;
		for(var i = 0; i < $scope.arr_levels.length; i++)
		{
			if(val.level == $scope.arr_levels[i])
			{
				res = true;
				break;
			}
		}
		return res;
	}
})

.directive('resizable', function($window) {
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
})

.factory('getLevels', function(){
	return 	[
		{level: 'EMERGENCY', label: 'danger', selected: true},
		{level: 'ALERT', label: 'danger', selected: true},
		{level: 'CRITICAL', label: 'danger', selected: true},
		{level: 'ERROR', label: 'danger', selected: true},
		{level: 'WARNING', label: 'warning', selected: true},
		{level: 'NOTICE', label: 'default', selected: true},
		{level: 'INFO', label: 'info', selected: true},
		{level: 'DEBUG', label: 'info', selected: true},
	];
})

.filter('badge_level', function(getLevels) {
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
})

;