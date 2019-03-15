<?= $this->partial("./i/header.php"); ?>
	<?= $this->partial("./i/menu.php"); ?>
		<div id='login-content'>
			<div class='panel panel-info'>
				<div class="panel-heading">Admin</div>
				<div class="panel-body">Please login to access the admin panel for your organisation.</div>
			</div>
			<form method='post' role="form" action=''>
				<div class="form-group">
					<label for="username">Username:</label>
					<input name='username' type='text' class="form-control" placeholder='Username...' />
				</div>
				<div class="form-group">
					<label for="password">Password:</label>
					<input name='password' type="password" class="form-control" />
				</div>
				<div class='form-group'>
					<input type='submit' class='btn btn-primary' value='Login' />
				</div>
			</form>
			<?php if($this->invalid_creds) {?>
			<div id='status' class='alert alert-danger'>Invalid username/password</div>
			<?php } if($this->reason == 'timeout'){ ?>
			<div id='status' class='alert alert-danger'>Your session has timed out.</div>
			<?php } ?>
		</div>

<?= $this->partial("./i/footer.php"); ?>