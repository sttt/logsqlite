angular.module('Index', [])

.controller('MainController', function($scope, $http){
	
	$scope.date = new Date();
	
	$scope.levels = [
		{level: 'WARNING', selected: true},
		{level: 'DEBUG', selected: true},
		{level: 'ERROR', selected: true},
		{level: 'CRITICAL', selected: true},
		{level: 'EMERGENCY', selected: true},
		{level: 'NOTICE', selected: true},
		{level: 'INFO', selected: true},
	];
	
	$scope.cols = [];
	var i,j,chunk = 3;
	for (i=0,j = $scope.levels.length; i<j; i+=chunk)
	{
		$scope.cols.push($scope.levels.slice(i,i+chunk));
	}
	
	$scope.fetch = function()
	{
		var levels = [];
		angular.forEach($scope.levels, function(obj)
		{
		  if (obj.selected) levels.push(obj.level);
		});
		
		$http
		({
			method: 'GET'
			,url: '?date_fr=' + angular.element(Date_from).val()
					+ '&date_to=' + angular.element(Date_to).val()
					+ '&levels=' + angular.toJson(levels)
					+ '&search_text=' + encodeURIComponent(angular.element(search_text).val())
			,cache: false
			,headers: {"X-Requested-With": "XMLHttpRequest",}
		})
		.success(function(data)
		{
			$scope.logs = data;
		})
		.error(function(data, status)
		{
			if(status == 401 && typeof data.auth_url !== 'undefined')
				window.location = data.auth_url;
			return data = data || "Request failed";
		});
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

;