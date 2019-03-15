"use strict";
/*
var o = {};
window.addEventListener('load', function(){

	window.onscroll = function() {sticky()};

	o.navbar = document.getElementById("editpanel");
	o.sticky = o.navbar.offsetTop;

});
function sticky() {
	if (window.pageYOffset >= o.sticky) {
		o.navbar.classList.add("sticky")
	} else {
		o.navbar.classList.remove("sticky");
	}
}*/
/* ANGULAR */

/*
	helper functions
*/
function validName(str){
	return (str.length > 0 && str.length <= 30);
}

function validUID(str){
	return /^\d+$/.test(str);
}

function s(){
	var str = arguments[0];
	for(var i = 1; i < arguments.length; ++i){
		str = str.replace("{"+(i-1)+"}", arguments[i]);
	}
	return str;
}





/*
	error messages
*/
var err_messages ={
	// -1 = OK, no errors
	// 0 - 19 = Error Notification
	1: "Sorry, a system error has occurred.",
	2: "This feature requires a UID to be registered in the database. Please scan one in.",
	3: "Invalid name format. Names must be between 2 and 30 characters.",
	4: "Bad user id.",
	5: "No UID",
	6: "Bad site ID",
	// 20 and onwards = Success notification
	21: "User added to register list.",
	22: "signed in",
	23: "signed out"
};





/*
	angular set up
*/
var app = angular.module('rgApp', ['ngRoute']);

app.config(function($routeProvider, $httpProvider) {
    $httpProvider.interceptors.push('responseObserver');

	$routeProvider
		.when('/add-user', { templateUrl: 'add-user.html', controller: 'adminAddUserController'})
		.when('/manage-users', { templateUrl: 'manage-users.html', controller: 'adminManageUsersController' })
		.when('/manual-sign-in', { templateUrl: 'manual-sign-in.html', controller: "adminManualSignInController" })
		.when('/manage/:id', { templateUrl: 'manage-user.html', controller: "adminManageUserController" })
		.otherwise('/manage-users');
});

app.factory('responseObserver', function responseObserver($q, $window) {
    return {
        'responseError': function(errorResponse) {
            switch (errorResponse.status){
				//Unauthorised, redirect to portal
				case 403:
					$window.location.href =  "/login?reason=timeout";
					break;
            }
            return $q.reject(errorResponse);
        }
    };
});








/*
	angular controllers
*/
app.controller('menuController', ['$scope', '$http', '$window', '$location',
	function($scope, $http, $window, $location){
	$scope.info = { };
	$scope.items =[
		{ link: '#!/manage-users', label: 'MANAGE USERS' },
		{ link: '#!/add-user', label: 'ADD USER' }
	];
	
	$http.get('/api/info').then(
		function(res){
			var data = res.data;
			$scope.info = data.info;
			$scope.status = err_messages[data.errno];
			$scope.error = data.errno;
		}
	);

	// Fix for starting active tab
	$scope.selectedTab = (function(){
		var url = "#!"+$location.path();

		for(var i = 0; i < $scope.items.length; ++i){
			if($scope.items[i].link == url){
				return $scope.items[i];
			}
		}

		return $scope.items[0];
	})();

	$scope.setSelectedTab = function(tab){
		$scope.selectedTab = tab;
	};

	$scope.tabClass = function(tab){
		return ($scope.selectedTab == tab ? 'menu-item-selected' : '');
	};
}]);








app.controller('adminManageUsersController',
	['$scope', '$http', '$window', '$location', '$interval',
	function($scope, $http, $window, $location, $interval){
		$scope.users = [];
		$scope.deactivated = [];
		$scope.status = "";
		$scope.error = -1;

		//$scope.loading = false;
		$scope.formData ={
			autoUpdate: true,
			visible: false,
			sign: ""
		};

		$scope.sites = [{ id: "0", name: "" }];
		$scope.sites_clean = [];

		// Fetches new data from the db
		$scope.getData = function(){
			//$scope.loading = true;
			$http.get("/api/users-and-groups").then(function(res){
				var data = res.data;

				// add customary data for program
				for(var i = 0; i < data.groups.length; ++i){
					// Checkbox model for groups
					var group = data.groups[i];

					group.s = false;
					for(var o = 0; o < group.users.length; ++o){
						group.users[o].s = false;
					}
				}

				$scope.users = data.groups;
				$scope.deactivated = data.d_users;
				console.log($scope.users);
				//$scope.loading = false;
				// Add error checking
			});
		};

		// Setup
		(function(){
			$http.get("/api/sites").then(function(res){
				var data = res.data;
				$scope.sites = $scope.sites.concat(data.sites);
				$scope.sites_clean = data.sites;
				$scope.search = $scope.sites[0];
				// Add error checking
			});

			// Interval switches on/off dependent upon the value
			// of autoupdate
			$scope.$watch('formData.autoUpdate', function(newValue, oldValue, scope){
				if(newValue){
					// Update every ten seconds
					$scope.interval = $interval($scope.getData, 1000 * 30);
				}
				else{
					$interval.cancel($scope.interval);
				}

				// TODO cookies
			}, true);

			// Prevent timer from firing after changing tabs
			$scope.$on('$routeChangeStart', function($event, next, current) {
				$interval.cancel($scope.interval);
			});

			// Disable auto-update when in edit mode
			$scope.$watch('formData.visible', function(newVal, oldVal){
				if(newVal == true){
					$scope.formData.autoUpdate = false;
				}
			}, true);

			// Get initial state
			$scope.getData();
		})();
		
		$scope.openActivityReport = function(user){
			$window.open(s("/reports/activity/user/{0}.pdf", user.id));
		};

		$scope.pdf = function(){
			$window.open(s("/reports/list/site/{0}.pdf", $scope.search.id));
		};

		$scope.manage = function(user){
			$location.path(s('/manage/{0}', user.id));
		};

		$scope.checkAll = function(gid){
			// find the group
			for(var i = 0; i < $scope.users.length; ++i){
				var group = $scope.users[i];

				if(group.id == gid){
					// Check all users
					var state = group.s;

					for(var o = 0; o < group.users.length; ++o){
						group.users[o].s = state;
					}
					break;
				}
			}
		}

		$scope.getChecked = function(){
			var ids = [];
			for(var i = 0; i < $scope.users.length; ++i){
				var group = $scope.users[i];

				// Check all users
				var state = group.s;

				for(var o = 0; o < group.users.length; ++o){
					var user = group.users[o];
					if(user.s == true){
						ids.push(user.id);
					}	
				}
			}

			return ids;
		};

		$scope.editSignIn = function(){
			// You need to specify a site when signing in
			if($scope.formData.sign.id == undefined){
				$scope.status = s("You need to specify a site when signing in.");
				return;
			}

			var ids = $scope.getChecked();

			if(ids.length == 0){
				$scope.status = s("No users selected.");
				return;
			}

			$http.patch(s('/api/edit/in/site/{0}/ids={1}', $scope.formData.sign.id, ids.join(','))).then(
				function(res){
					var data = res.data;

					if(data.errno == -1){
						$scope.getData();
						$scope.status = s("Users have been signed in to {0}.", $scope.formData.sign.name);
					}
					else{
						$scope.status = err_messages[data.errno];
						$scope.error = data.errno;
					}
				}
			);
		};

		$scope.editSignOut = function(){
			var ids = $scope.getChecked();

			if(ids.length == 0){
				$scope.status = s("No users selected.");
				return;
			}
			///api/edit/out/site/{0}/ids={1}
			$http.patch(s('/api/edit/out/ids={0}', ids.join(','))).then(
				function(res){
					var data = res.data;

					if(data.errno == -1){
						$scope.getData();

						// // site stored in the db
						// if($scope.formData.sign.id == 0){
						$scope.status = s("Users have been signed out.");
						// }
						// specific site
						//else{
						//	$scope.status = s("Users have been signed out from {0}.", $scope.formData.sign.name);
						//}
					}
					else{
						$scope.status = err_messages[data.errno];
						$scope.error = data.errno;
					}
				}
			);
		}
	}
]);








app.controller('adminAddUserController', ['$scope', '$http', function($scope, $http){
	$scope.status = "";
	$scope.error = -1;
	$scope.groups = [];
	$scope.formData ={
		name: "",
		group: null,
		uid: null
	};
	

	// Setup
	(function(){
		var cb = Date.now();
		// Fetch the registered UID
		$http.get('/api/add-user-content').then(function(res){
			var data = res.data;
			$scope.error = data.errno;
			if(data.errno == -1){
				$scope.formData.uid = data.uid;
				$scope.groups = data.groups;
				// set the default option to the first in the list
				$scope.formData.group = $scope.groups[0];
			}
			else{
				$scope.status = err_messages[data.errno];
			}
		});
	})();

	$scope.add = function(){
		if(!validName($scope.formData.name)){
			var errno = 3;
			$scope.status = err_messages[errno];
			$scope.error = errno;
			return;
		}

		$http.post(s('/api/add-user/name/{0}/group/{1}', $scope.formData.name, $scope.formData.group.id)).then(
			function(res){
				var data = res.data;
				
				if(data.errno == -1){
					$scope.status = s("{0} has been added to the group {1}", $scope.formData.name, $scope.formData.group.name);
				}
				else{
					$scope.status = err_messages[data.errno];
				}
				$scope.error = data.errno;
				console.log($scope.error);
			}
		);
	};
}]);





app.controller('adminManageUserController', ['$scope', '$http', '$routeParams',
	function($scope, $http, $routeParams){
	$scope.user = null;
	$scope.site = null;
	$scope.groups = [];
	$scope.sites = [];
	$scope.error = -1;
	$scope.status = "";
	
	$scope.formData ={
		group: null,
		site: null,
		uid: null
	};

	// Setup
	(function(){
		var cb = Date.now();

		// Fetch all data
		$http.get(s('/api/manage-user-content/user/{0}', $routeParams.id)).then(function(res){
			var data = res.data;
			$scope.error = res.data.errno;

			if(res.data.errno == -1){
				$scope.user 		= data.userdata;
				$scope.formData.uid = data.uid;
				$scope.groups		= data.groups;
				$scope.sites		= data.sites;

				//todo
				// set the default option to the first in the list
				$scope.formData.group = $scope.groups[0];
			}
			else{
				$scope.status = err_messages[res.data.errno];
			}
		});
	})();

	$scope.changeGroup = function(){
		$http.patch(s("/api/user/{0}/chgroup/{1}", $scope.user.id, $scope.formData.group.id)).then(function(res){
			$scope.error = res.data.errno;
			if(res.data.errno == -1){
				$scope.status = s("{0} has been assigned to {1}.", $scope.user.name, $scope.formData.group.name);
			}
			else{
				$scope.status = err_messages[res.data.errno];
			}
		});
	}

	$scope.changeStatus = function(){
		var logged_in = !($scope.user.timeIn == null);

		if(!logged_in){
			// You need to specify a site when signing in
			if($scope.formData.site == null){
				$scope.status = s("You need to specify a site when signing in.");
				return;
			}
		}

		if(logged_in){
			var url = s('/api/user/{0}/chstatus', $scope.user.id);
		}
		else{
			var url = s('/api/user/{0}/chstatus/{1}', $scope.user.id, $scope.formData.site.id);
		}
	
		$http.patch(url).then(function(res){
			console.log(res);
			var data = res.data;
			switch(data.errno){
				case 22:
					$scope.status = s("{0} has been signed into {1}.", $scope.user.name, $scope.formData.site.name);
					$scope.user.sitename = $scope.formData.site.name;
					break;
				case 23:
					$scope.status = s("{0} has been signed out.", $scope.user.name);
					$scope.user.sitename = null;
					break;
				default:
					$scope.error = data.errno;
					$scope.status = err_messages[$scope.errno];
					break;
			}
		});
	}
	
	$scope.changeUID = function(){
		// If another person has used 
		$http.patch(s('/api/user/{0}/resetuid', $scope.user.id)).then(function(res){
			$scope.error = res.data.errno;
			if($scope.error == -1){
				$scope.status = "Unique ID changed to "+res.data.uid;
			}
			else{
				$scope.status = err_messages[$scope.error];
			}
		});
	};
}]);