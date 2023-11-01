Vvveb.ComponentsGroup['Plugins'] = ["contact-form/form"];

Vvveb.Components.extend("_base", "contact-form/form", {
    image: "icons/envelope.svg",
    name: "Contact form",
    attributes: ["data-v-component-plugin-contact-form-form"],
    html: `<div data-v-component-plugin-contact-form-form data-v-save="true" data-v-email="true"  data-v-sendto="" data-v-confirm-email="true" data-v-name="contact-form-appointment">

		<div class="notifications" data-v-notifications>

			<div class="alert alert-danger d-flex alert-dismissable" role="alert" data-v-if="this.errors">

				<div class="icon align-middle me-2">
					<i class="align-middle la la-2x lh-1 la-exclamation-triangle"></i>
				</div>

				<div class="flex-grow-1 align-self-center text-small" >
					<div data-v-notification-error>
						<div data-v-notification-text>
							This is a placeholder for an error message.
						</div>
					</div>
				</div>


				<button type="button" class="btn-close align-middle" data-bs-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">
						<!-- <i class="la la-times"></i> -->
					</span>
				</button>
			</div>

			<div class="alert alert-success d-flex  alert-dismissable d-flex" role="alert" data-v-notification-success>

				<div class="icon align-middle me-2">
					<i class="align-middle la la-2x lh-1 la-check-circle"></i>
				</div>

				<div class="flex-grow-1 align-self-center align-middle" data-v-notification-text>
					This is a placeholder for a success message.
				</div>

				<button type="button" class="btn-close align-middle" data-bs-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">
						<!-- <i class="la la-times"></i> -->
					</span>
				</button>
			</div>


		</div>

	<form action="" method="post">
	  <input type="hidden" class="form-control" placeholder="First name" name="firstname-empty">	

	  <div class="row">
		<div class="col">
		  <input type="text" class="form-control" placeholder="First name" name="firstname" required>
		</div>
		<div class="col">
		  <input type="text" class="form-control" placeholder="Last name" name="lastname" required>
		</div>
	  </div>

	  <div class="row mt-4">
		<div class="col">
		  <input type="email" class="form-control" placeholder="Enter email" name="email" required>
		</div>
	  </div>

	  <div class="row mt-4">
		<div class="col">
		  <input type="text" class="form-control" placeholder="Subject" name="subject" required>
		</div>
	  </div>
	  <div class="row mt-4">
		<div class="col">
		  <textarea class="form-control" name="message" rows="3" placeholder="How can we help?" required></textarea>
		</div>
	  </div>

	 <!-- if these hidden inputs are filled then ignore, robots -->

	 <input type="text" class="form-control d-none" placeholder="Contact form" name="contact-form" >	
	 
	 <input type="text" class="form-control d-none" placeholder="Subject" name="subject-empty" >	
	 
	 <input type="text" class="form-control visually-hidden" placeholder="Last name" name="lastname-empty" tabindex="-1">	
	 

	  <div class="row mt-4">
		<div class="col">
		  <button type="submit" class="btn btn-primary">Send message <i class="la la-long-arrow-alt-right ms-1"></i></button>
		</div>
	  </div>
	</form>
</div>`,
    
    properties: [{
        name: "Name",
        key: "name",
        htmlAttr: "data-v-name",
        inputtype: TextInput
    }, {
        name: "Save to database",
        key: "save",
        htmlAttr: "data-v-save",
        inputtype: CheckboxInput,
		col:6,
		inline:true
    },{
        name: "Send email to site admin",
        key: "email",
        htmlAttr: "data-v-email",
        inputtype: CheckboxInput,
		col:6,
		inline:true
    },{
        name: "Send to",
        key: "sendto",
        htmlAttr: "sendto",
        inputtype: TextInput
    },{
		name:"",
		key: "sendto_warning",
        inline:false,
        col:12,
        inputtype: NoticeInput,
        data: {
			type:'info',
			title:'',
			text:'If send to is empty then the configured contact email set in site settings will be used'
		}
    },{
        name: "Send user confirmation email",
        key: "confirm-email",
        htmlAttr: "data-v-confirm-email",
        inputtype: CheckboxInput,
        col:12,
        inline:true,
    },{
		name:"",
		key: "confirm_warning",
        inline:false,
        col:12,
        inputtype: NoticeInput,
        data: {
			type:'info',
			title:'',
			text:'The form must keep a field named email for user to receive a confirmation'
		}
	}]
});    
