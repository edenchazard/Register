
		<nav class="navbar navbar-default">
			<div class="container-fluid">
				<div class="navbar-header">
					<a class="navbar-brand" href="#">Attendance</a>
				</div>
				<ul class="nav navbar-nav">
					<li class="nav-item"><a class="nav-link" href='/'>Home</a></li>
					<?php if($this->is_signed_in){ ?>
					<li class="nav-item"><a class="nav-link" href='/admin'>Admin</a></li>
					<?php } else { ?>
					<li class="nav-item"><a class="nav-link" href='/login'>Login</a></li>
					<?php } ?>
				</ul>
			</div>
		</nav>
