/**
 * Vvveb
 *
 * Copyright (C) 2021  Ziadin Givan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */
 


import {ServerComponent} from '../server-component.js';

let template = 
`<div data-v-component-user>
<form action method="post" enctype="multipart/form-data" data-v-url="user/login" data-v-vvveb-action="login" data-v-vvveb-on="submit">
	
	<input type="hidden" name="csrf" data-v-csrf>
	
	<div class="login-form" data-v-if-not="component.user_id">
		
		<div class="mb-3">
			<label class="form-label" for="input-email">E-Mail Address</label>
			<input type="email" name="email" value placeholder="E-Mail Address" id="input-email" class="form-control" required>
		</div>
		
		<div class="mb-3">
			<label class="form-label" for="input-password">Password</label>
			<input type="password" minlength="4" autocorrect="off" autocomplete="current-password" name="password" value="" placeholder="Password" id="input-password" class="form-control" required>
		</div>
		
		<button type="submit" value="Login" class="btn btn-primary @@if (typeof btnClass !== "undefined"){	@@btnClass	}">
			
			<span class="loading d-none">
				<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true">
				</span>
				<span>Authenticating</span>...
			</span>

			<span class="button-text">
				Login <i class="la la-arrow-right float-end ms-2"></i>
			</span>
		
		</button>	
		<div class="my-2"></div>
		<a href="/user/reset" data-v-url="user/reset/index" class="my-2">Forgotten Password</a>
		
		<div class="my-2"></div>
		<!--
		<a href="#">
			<span class="btn btn-secondary btn-sm">
				  <i class="lab la-google la-lg"></i>
			</span>
		</a>
		<a href="#">
			<span class="btn btn-secondary btn-sm">
			  <i class="lab la-facebook la-lg"></i>
			</span>
		</a> -->
		<hr>
		<span>Don’t have an account?</strong>
		<a href="/user/signup" data-v-url="user/signup/index">Register Account</a>						
					
	</div>							
	
	
	<div class="user-form" data-v-if="component.user_id">
		<div>Welcome <b data-v-display_name>John Doe</b></div>

		  <ul class="m-2 list-unstyled">
			<li><a href="user" data-v-url="user/index">My account</a></li>
			<li><a href="user/comments" data-v-url="user/comments/index">Comments</a></li>
			<li><a href="user/orders" data-v-url="user/orders/index">Orders</a></li>
			<li><a href="user/downloads" data-v-url="user/downloads/index">Downloads</a></li>
			<li><a href="user/profile" data-v-url="user/profile/index">Profile</a></li>
		  </ul>									


		<input type="hidden" name="logout">
		
		<button type="submit" value="logout" class="btn btn-primary @@if (typeof btnClass !== "undefined"){	@@btnClass	}">
			
			<span class="loading d-none">
				<span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true">
				</span>
				<span>Loading ...</span>...
			</span>

			<span class="button-text">
				Logout
			</span>
		
		</button>	
	</div>
</form>	
</div>
`;

class UserComponent extends ServerComponent{
	constructor () {
		super();

		this.name = "User";
		this.attributes = ["data-v-component-user"],
		//this.userServerTemplate = true,

		this.image ="icons/user.svg";
		this.html = template;
		
		this.properties = [{
			name: "Menu to display",
			group:"automatic",
			key: "order",
			col:12,
			inline:false,
			htmlAttr:"data-v-user_id",
			inputtype: SelectInput,
			data: {
				options: [{
					value: "1",
					text: "Default"
				}, {
					value: "2",
					text: "Date added 1"
				}, {
					value: "3",
					text: "Date added"
				}, {
					value: "4",
					text: "Date modified"
				}, {
					value: "5",
					text: "Sales"
				}]
			}
		}];
	}

    init(node)
	{
		$('.mb-3[data-group]').attr('style','display:none !important');
		
		let source = node.dataset.vSource;
		if (!source) {
			source = "automatic";
		} 
		
		$('.mb-3[data-group="'+ source + '"]').attr('style','');
	}
}

let userComponent = new UserComponent;

export {
  userComponent
};
