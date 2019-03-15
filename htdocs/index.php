<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once "./i/config.php";
require_once "./i/mysqli.php";
require_once "./i/funcs.php";
require_once "./i/register.class.php";

$klein = new \Klein\Klein();

// Pages
$klein->respond('/?', function($request, $response, $service, $app) {
   	$service->render('./views/home.php', array(
		'title' 		=> "Home",
		'is_signed_in'	=> Register::is_signed_in(),
	));
});

$klein->respond('/admin', function ($request, $response, $service, $app){
	// off limits to those not signed in
	if(!Register::is_signed_in()){
		$response->redirect('/login?reason=timeout')->send();
	}
	$service->render('./views/admin.php', array(
		'title' 		=> "Administrator panel",
		'is_signed_in'	=> true
	));
});

$klein->respond('/login', function ($request, $response, $service, $app){
	// page should be off limits to those who are logged in
	if(Register::is_signed_in()){
		$response->redirect("/admin")->send();
	}

	if($request->method('post')){
		$post = $request->paramsPost();
		if(!empty($post->get('username')) && !empty($post->get('password'))){
			if(Register::attempt_sign_in($post->get('username'), $post->get('password'))){
				$response->redirect("/admin")->send();
			}
			else{
				$service->invalid_creds = true;
			}
		}
	}
	else{
		$get = $request->paramsGet();
		$reason = $get->get('reason');
		if(!empty($reason)){
			$service->reason = $reason;
		}
		else{
			$service->reason = false;
		}
	}
	$service->render('./views/login.php', array(
		'title'		=> "Login"
	));
});

$klein->respond('/logout', function ($request, $response, $service, $app){
	Register::log_out();
	$service->render('./views/logout.php', array(
		'title' 	=> "Logout"
	));
});

// Reports
$klein->with('/reports', function() use ($klein) {
	$klein->respond(function($request, $response, $service, $app) use ($klein){
		require_once "./i/report.class.php";

		if(!Register::is_signed_in()){
			$klein->abort(403);
		}

		$app->report = new Report();
	});

	// todo
	$klein->get('/activity/user/[i:userid].pdf', function ($request, $response, $service, $app){
		$app->report->generate_report(array(
			'type'		=> "USER ACTIVITY",
			'title'		=> date("Y-m-d H:i:s"),
			'userid'	=> $request->userid,
			'account'	=> Register::get_session()
		));
	});

	$klein->get('/list/site/[i:siteid].pdf', function ($request, $response, $service, $app){
		$app->report->generate_report(array(
			'type'		=> "LIST",
			'title'		=> date("Y-m-d H:i:s"),
			'siteid'	=> $request->siteid,
			'account'	=> Register::get_session()
		));
	});
});

// API router
$klein->with('/api', function() use ($klein) {
	$klein->respond(function($request, $response, $service, $app) use ($klein){
		require_once "./i/user.class.php";

		if(!Register::is_signed_in()){
			$klein->abort(403);
		}

		$app->register = new RegisterAccount(Register::get_session());
	});

	$klein->get('/users-and-groups', function($request, $response, $service, $app){
		$data = array(
			'groups'	=> array(),
			'errno' 	=> -1
		);

		$groups = $app->register->get_groups();

		foreach($groups as $group){
			$users = $app->register->get_users_data_in_group($group['id']);

			// Add the new entry to our json
			$data['groups'][] = array(
				'id'			=> $group['id'],
				'group_name' 	=> $group['name'],
				'users' 		=> $users
			);
		}

		$response->json($data);
    });

	$klein->get('/info', function ($request, $response, $service, $app){
		$data = array(
			'info'		=> $app->register->get_data(),
			'errno'		=> -1
		);
		$response->json($data);
	});

	$klein->get('/sites', function ($request, $response, $service, $app) {
		$data = array(
			'sites'		=> $app->register->get_sites(),
			'errno'		=> -1
		);
		$response->json($data);
	});

	$klein->get('/groups', function ($request, $response, $service, $app){
		$data = array(
			'groups'	=> $app->register->get_groups(),
			'errno'		=> -1
		);
		$response->json($data);
	});	

	$klein->get('/uid', function ($request, $response, $service, $app) {
		$data = array();
		$uid = $app->register->registered_uid;

		if($uid == null){
			$data['errno'] = 2;
		}
		else {
			$data['uid'] = $uid;
			$data['errno'] = -1;
		}
		
		$response->json($data);
	});

	$klein->post('/add-user/name/[:username]/group/[:groupid]', function ($request, $response, $service, $app){
		$app->register->add_user($app->register->registered_uid, $request->username, $request->groupid);

		// Clean the UID from the account row
		$app->register->unset_uid();
		$response->json(array("errno" => -1));
	});

	$klein->get('/add-user-content', function ($request, $response, $service, $app) {
		$data = array();
		$uid = $app->register->registered_uid;
		if($uid == null){
			$data['errno'] = 2;
		}
		else {
			$data = array(
				'uid'		=> $uid,
				'groups'	=> $app->register->get_groups(),
				'errno'		=> -1
			);
		}
		$response->json($data);
	});

	$klein->get('/manage-user-content/user/[i:userid]', function ($request, $response, $service, $app) {
		$user = new User($request->userid);
		$data = array(
			'uid'		=> $app->register->registered_uid,
			'sites'		=> $app->register->get_sites(),
			'userdata'	=> $user->get_data(),
			'groups'	=> $app->register->get_groups(),
			'errno'		=> -1
		);
		$response->json($data);
	});

	$klein->patch('/edit/in/site/[i:site]/ids=[:ids]', function($request, $response, $service, $app){
		$ids = explode(',', $request->ids);

		if($app->register->mass_sign_in($ids, $request->site)){
			$err = -1;
		}
		else{
			$err = 0;
		}

		$data = array(
			'errno'	=> $err
		);

		$response->json($data);
	});

	///edit/out/site/[i:site]/ids=[:ids]
	$klein->patch('/edit/out/ids=[:ids]', function($request, $response, $service, $app){
		$ids = explode(',', $request->ids);

		if($app->register->mass_sign_out($ids)){
			$err = -1;
		}
		else{
			$err = 0;
		}

		$data = array(
			'errno'	=> $err
		);

		$response->json($data);
	});
});

// API user routes
$klein->with("/api/user",  function() use ($klein) {
	$klein->respond('/[i:userid]/[*:trailing]', function ($request, $response, $service, $app){
		$app->user = new User($request->userid);
	});

	$klein->patch('/[i:userid]/chgroup/[i:groupid]', function ($request, $response, $service, $app){
		$app->user->change_group($request->groupid);
		$response->json(array("errno" => -1));
	});

	$klein->patch('/[i:userid]/chstatus/[i:siteid]?', function ($request, $response, $service, $app){
		$site = (isset($request->siteid) ? $request->siteid : null);
		$sign = $app->user->sign($site);
		// true = signed in, false = signed out
		switch($sign){
			case SIGN_MASTER_SUCCESS: $i = 24; break;
			case SIGN_LOGGED_IN: $i = 22; break;
			case SIGN_LOGGED_OUT: $i = 23; break;
		}
		$response->json(array('errno' => $i));
	});

	$klein->patch('/[i:userid]/resetuid', function($request, $response, $service, $app){
		$data = array();
		$uid = $app->register->registered_uid;

		//No uid available for use
		if($uid == null){
			$data['errno'] = 1;
		}
		else{
			$app->user->reset_uid($uid);
			$app->register->unset_uid();

			$data = array(
				'uid'	 => $uid,
				"errno"	 => -1
			);
		}
		$response->json($data);
	});
});

//Exception handling
$klein->onError(function ($klein, $msg, $type, $err){
	$msg = $err->getCode()." in ".$err->getFile()." on line ".$err->getLine()." ".$err->getMessage()." Trace: ".$err->getTraceAsString();
	// Log
	e_log($msg, SYS_LOG);
	//show errors in development mode
	$content = "Sorry, an error has occurred.";

	// Inform
	// JSON
	if (strpos($klein->request()->pathname(), '/api') === 0) {
		// just tell the user a system error occurred.
		$klein->response()->json(array("errno" => 1));
	}
	// Everything else
	else
		$klein->response()->body(nl2br($content));
});

$klein->onHttpError(function ($code, $router){
    switch ($code) {
        case 404: $router->response()->body('This page does not exist.'); break;
        case 403: $router->response()->body("Unauthorized."); break;
		case 500: $router->response()->body('505'); break;
        default: $router->response()->body("HTTP Error {$code}");
    }
});

$klein->dispatch();
?>