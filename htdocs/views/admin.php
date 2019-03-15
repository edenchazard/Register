<?=$this->partial("./i/header.php"); ?>
		<div ng-app='rgApp'>
			<div id='admin-content'>
				<ul id='menu' ng-controller="menuController">
					<li id='menu-site-title'>ATTENDANCE</li>
					<li class="menu-item" ng-class="tabClass(item)" ng-repeat="item in items" tab="tab">
						<a href="{{item.link}}" ng-click="setSelectedTab(item)" ng-bind="item.label"></a>
					</li>
					<li id='menu-right' class='menu-item'>
						<a href='/logout'><i class="fas fa-power-off"></i></a>
					</li>
				</ul>
				<div ng-view class='app'></div>

<script type='text/ng-template' id='add-user.html'>
	<div id='info-panel'>To add a new user, simply fill in the form.</div>
	<div id='status' ng-bind="status" ng-if="error != -1"></div>
	<div ng-if="error != 2" class='page-content'>
		<form class="mini-form" role="form">
			<div class="form-group">
				<label>Full Name</label>
				<div class="form-input">
					<input type='text' ng-model="formData.name" placeholder='Full Name...' required />
				</div>
			</div>
			<div class="form-group">
				<label>UID #</label>
				<div class="form-input">
					<input type='text' ng-model="formData.uid" disabled />
				</div>
			</div>
			<div class="form-group">
				<label>Group</label>
				<div class="form-input">
					<select ng-model="formData.group" ng-options="group.name for group in groups"></select>
				</div>
			</div>
			<div class='form-group'>
				<button type='submit' class='btn btn-primary' ng-click="add()">Add user</button>
			</div>
		</form>
	</div>
</script>




<script type='text/ng-template' id='manage-user.html'>
	<div id='info-panel'>Update a user's group, reset their UID or change their status.</div>
	<div id='status' ng-bind="status" ng-if="status != ''"></div>
	<div class='page-content'>
		<div>
			Editing: {{user.name}}
		</div>

		<form class="mini-form" ng-if="error == -1">
			<div class='panel panel-primary'>
				<div class="panel-heading">Reset Unique ID</div>
				<div class="panel-body">
					Reset their unique ID to the one uploaded to the system.
					<div class="form-group">
						<label>Currently</label>
						<div class='form-input'>
							<input type='text' value="{{user.uid}}" disabled />
						</div>
					</div>
					<div class="form-group">
						<label for="uniqueID">New</label>
						<div class="form-input">
							<input type='text' ng-model="formData.uid" disabled />
						</div>
					</div>
					<div class="form-group"> 
						<button type='submit'  class='btn btn-primary' ng-click="changeUID()">Change</button>
					</div>
				</div>
			</div>

			<div class='panel panel-primary'>
				<div class="panel-heading">Change group</div>
				<div class="panel-body">
					Change this user's group, you can also deactivate them by setting their group to Deactivated.
					<div class="form-group">
						<label for="group">Group</label>
						<div class="form-input">
							<select ng-model="formData.group" ng-options="group.name for group in groups"></select>
						</div>
					</div>
					<div class="form-group"> 
						<button type='submit' class='btn btn-primary' ng-click="changeGroup()">Change</button>
					</div>
				</div>
			</div>

			<div class='panel panel-primary'>
				<div class="panel-heading">Alter Status</div>
				<div class="panel-body">
					Manually sign this user in or out.
					<div ng-if="user.sitename != null">
						<div>Currently signed in at <strong>{{user.sitename}}</strong></div>
					</div>
					<div ng-if="user.sitename == null">
						<p class="text-info">Currently not signed in.</p>
						<div class='form-group'>
							<label>Site:</label>
							<div class="form-input">
								<select ng-model="formData.site" ng-options="site.name for site in sites">
									<option value="" selected="selected">Choose</option>
								</select>
							</div>
						</div>
					</div>
					<div class="form-group"> 
						<button type='submit' class='btn btn-primary' ng-click="changeStatus()">{{ user.sitename == null ? 'Sign-in' : 'Sign out'}}</button>
					</div>
				</div>
			</div>
		</form>
	</div>
</script>





<script type='text/ng-template' id='manage-users.html'>
	<div id='info-panel'>See each user's status, print a report or manage individual users.</div>
	<div id='toolbar' ng-if="error == -1">
		<span class='toolbar-item'>
			<label>Filter by site:
				<select ng-model="search" ng-options="site.name for site in sites"></select>
			</label>
		</span>
		<span class='toolbar-item'>
			<label>
				<input ng-disabled="formData.visible == true" type="checkbox" ng-model="formData.autoUpdate" />
				<!--<img ng-if="formData.autoUpdate == true" src="../css/loading.gif" />-->
				<span ng-else class="glyphicon glyphicon-refresh"></span> Auto-update
			</label>
		</span>
		<span class='toolbar-item'>
			<button type='submit' class='btn btn-primary' ng-click="pdf()">PDF</button>
		</span>
		<span class='toolbar-item'>
			<label>
				<input type="checkbox" ng-model="formData.visible" />
				<span ng-else class="glyphicon glyphicon-refresh"></span> Edit
			</label>
		</span>
	</div>
	<div id='status' ng-bind="status" ng-if="status != ''"></div>
	<div ng-if="error == -1">
		<table>
			<tbody ng-repeat="group in users">
				<tr>
					<td class='user-list-group-header' colspan='7'>{{group.group_name}}</td>
				</tr>
				<tr class="user-list-header">
					<th class='manage-user-check' ng-if="formData.visible == true">
						<input type="checkbox" ng-model="group.s" ng-click="checkAll(group.id)" />
					</th>
					<!--<th>UID</th>-->
					<th class='manage-user-name'>Name</th>
					<th class='manage-user-sign'>Signed in</th>
					<th class='manage-user-timein'>Date/Time</th>
					<th class='manage-user-site'>Site</th>
					<!--<th>Tools</th>-->
				</tr>
				<!-- Show all users in group -->
				<tr
					ng-class-odd="'user-list-odd'"
					ng-class-even="'user-list-even'"
					ng-repeat="user in filteredUsers = (group.users | filter:(!!search.name || undefined) && {sitename: search.name})">
					<td class='manage-user-check' ng-if="formData.visible == true"><input type="checkbox" ng-model="user.s" /></td>
					<!--<td>{{user.UID}}</td>-->
					<td class='manage-user-name'><a href='#!/manage/{{user.id}}'>{{user.name}}</a></td>
					<td class='manage-user-sign'>{{user.timeIn == null ? "No" : "Yes" }}</td>
					<td class='manage-user-timein'>{{user.timeIn}}</td>
					<td class='manage-user-site'>{{user.sitename}}</td>
					<!--<td>
						<span title="Activity Report" class="glyphicon glyphicon-file" ng-click="openActivityReport(user)"></span>
					</td>-->
				</tr>
				<!-- If there's no users in this group, put a notice. -->
				<tr ng-if="filteredUsers.length == 0">
					<td colspan='8'>No users.</td>
				</tr>
			</tbody>
		</table>
		<div id='editpanel' ng-if="formData.visible == true">
			<div id='editpanel-signin'>
				<!-- blank option to avoid an empty option appearing -->
				<select ng-model="formData.sign" ng-options="site.name for site in sites_clean">
					<option value="" selected="selected">Choose</option>
				</select>
				<button type='submit' class='btn btn-primary' ng-click="editSignIn()">Sign In</button>
			</div>
			<span id='editpanel-or'>OR</span>
			<div id='editpanel-signout'>
				<button type='submit' class='btn btn-primary' ng-click="editSignOut()">Sign Out</button>
			</div>
		</div>
		<!--<h1 title='Show/Hide' ng-click="showInactive = !showInactive">Deactivated <span class="{{showInactive == false ? 'glyphicon glyphicon-plus' : 'glyphicon glyphicon-minus' }}"></span></h1>-->
	</div>
</script>




			</div>
		</div>
<?= $this->partial("./i/footer.php"); ?>