<div class="app_faq_wrap" id="app_faq_wrap"></div>

<script type="text/javascript">
jQuery(document).ready(function($){
	$('ul li b').each(function(){
		n = parseInt( $(this).closest('ul').attr('id').replace('q','') ) - 0;
		$('.app_faq_wrap').append( '<a href="#q'+n+'">' + $(this).html() + '</b></a><br />');
		$(this).closest('ul').after( '<a href="#app_faq_wrap">Go to Top</b></a>')
	});
	$('.wrap ul').css('position','relative').css('padding-top','20px').css('font-size','14px');
	$('.wrap ul ul').css('list-style-type','square');
	$('.wrap ul ul li').css('margin-left','15px');
	$('#app_faq_wrap a').css('line-height','2em');
});
</script>

<ul id='q0'>
<li>
</li>
</ul>
<ul id='q1'>
	<li>
	<?php _e('<b>How can I restart the tutorial?</b>', 'appointments');?>
	<br />
	<?php _e('To restart tutorial about settings click here:', 'appointments');?>
	<?php
	$link = add_query_arg( array( "tutorial"=>"restart1" ), admin_url("admin.php?page=app_settings") );
	?>
	<a href="<?php echo $link ?>" ><?php _e( 'Settings Tutorial Restart', 'appointments' ) ?></a>
	<br />
	<?php _e('To restart tutorial about entering and editing Appointments click here:', 'appointments');?>
	<?php
	$link = add_query_arg( array( "tutorial"=>"restart2" ), admin_url("admin.php?page=app_settings") );
	?>
	<a href="<?php echo $link ?>" ><?php _e( 'Appointments Creation and Editing Tutorial Restart', 'appointments' ) ?></a>
	</li>
</ul>
<ul id='q2'>
	<li>
	<?php _e('<b>What is the importance of Time Base and how should I set it?</b>', 'appointments');?>
	<br />
	<?php _e('<i>Time Base</i> is the most important parameter of Appointments+. It is the minimum time that you can select for your appointments. If you set it too high then you may not be possible to optimize your appointments. If you set it too low, your schedule will be too crowded and you may have difficulty in managing your appointments. You should enter here the duration of the shortest service you are providing. Please also note that service durations can only be multiples of the time base. So if you need 30 and 45 minutes services, you should select 15 minutes as the time base.', 'appointments');?>
	</li>
</ul>

<ul id='q3'>
	<li>
	<?php _e('<b>I don\'t see the time base that I need. For example I need 240 minutes appointments. How can I do that?</b>', 'appointments');?>
	<br />
	<?php _e('You can add one more time base using <i>Additional time base</i> setting. You must select this setting in <i>time base</i> setting to be effective.', 'appointments');?>
	</li>
</ul>

<ul id='q4'>
	<li>
	<?php _e('<b>What is the complete process for an appointment?</b>', 'appointments');?>
	<br />
	<?php _e('With the widest settings, client will do the followings on the front page:', 'appointments');?>
		<ul>
		<li>
		<?php _e('Select a service', 'appointments');?>
		</li>
		<li>
		<?php _e('Select a service provider', 'appointments');?>
		</li>
		<li>
		<?php _e('Select a free time on the schedule', 'appointments');?>
		</li>
		<li>
		<?php _e('Login (if required)', 'appointments');?>
		</li>
		<li>
		<?php _e('Enter the required fields (name, email, phone, address, city) and confirm the selected appointment', 'appointments');?>
		</li>
		<li>
		<?php _e('Click Paypal payment button (if required)', 'appointments');?>
		</li>
		<li>
		<?php _e('Redirected to a Thank You page after Paypal payment', 'appointments');?>
		</li>
		</ul>
	</li>
</ul>
<ul id='q5'>
	<li>
	<?php _e('<b>Is it necessary to have at least one service?</b>', 'appointments');?>
	<br />
	<?php _e('Yes. Appointments+ requires at least one service to be defined. Please note that a default service should have been already installed during installation. If you delete it, and no other service remains, then you will get a warning message. In this case plugin may not function properly.', 'appointments');?>
	</li>
</ul>
<ul id='q6'>
	<li>
	<?php _e('<b>Is it necessary to define service providers?</b>', 'appointments');?>
	<br />
	<?php _e('No. You may as well be working by yourself, doing your own business. Plugin will work properly without any service provider, i.e worker, defined. In this case Appointments+ assumes that there is ONE service provider working, giving all the services.', 'appointments');?>
	</li>
</ul>
<ul id='q7'>
	<li>
	<?php _e('<b>Is it necessary to use Services and Service Providers shortcodes?</b>', 'appointments');?>
	<br />
	<?php _e('No. If you do not use these shortcodes then your client will not be able to select a service and Appointments+ will pick the service with the smallest ID or the one selected with "service" parameter of the schedule shortcode. We have already noted that a service provider definition is only optional.', 'appointments');?>
	</li>
</ul>
<ul id='q8'>
	<li>
	<?php _e('<b>Does Appointments+ provide some widgets?</b>', 'appointments');?>
	<br />
	<?php _e('Yes. Appointments+ has Services and Service Providers widgets which provides a list of service or service providers with links to their description/bio pages and a Monthly Calendar widget that redirects user to the selected appointment page when a free day is clicked. Note: Service and service provider items not having assigned description/bio pages are not displayed.', 'appointments');?>
	</li>
</ul>
<ul id='q9'>
	<li>
	<?php _e('<b>Can I use the shortcodes in any page as I wish?</b>', 'appointments');?>
	<br />
	<?php _e('Some shortcodes have only meaning if they are used in combination with some others. For example the Services shortcode will not have a function unless you have a Schedule on the same page. They are defined as separate shortcodes so that you can customize them on your pages. Except for My Appointments and Schedule shortcodes, only one instance of a shortcode is allowed on the same page.', 'appointments');?>
	</li>
</ul>
<ul id='q10'>
	<li>
	<?php _e('<b>Can I have schedules showing more than two weeks or months on the same page?</b>', 'appointments');?>
	<br />
	<?php printf( __('Yes. Use "add" parameter of schedule shortcode to add additional schedules. There is no limit for the number of schedules that you can use on the same page. See %s tab for details.', 'appointments'), '<a href="'.admin_url('admin.php?page=app_settings&tab=shortcodes').'">'.__('Shortcodes', 'appointments') .'</a>');?>
	</li>
</ul>
<ul id='q11'>
	<li>
	<?php _e('<b>Does the client need to be registered to the website to apply for an appointment?</b>', 'appointments');?>
	<br />
	<?php _e('You can set whether this is required with <i>Login Required</i> setting. You can ask details (name, email, phone, address, city) about the client before accepting the appointment, thus you may not need user registrations. These data are saved in a cookie and autofilled when they apply for a new appointment, so your regular clients do not need to refill them.', 'appointments');?>
	</li>
</ul>
<ul id='q12'>
	<li>
	<?php _e('<b>How are the appointments confirmed?</b>', 'appointments');?>
	<br />
	<?php _e('If you have selected <i>Payment Required</i> field as Yes, then an appointment is automatically confirmed after a succesful Paypal payment and confirmation of Paypal IPN. If you selected Payment Required as No, then confirmation can be done manually, or automatically depending on Auto Confirm setting.', 'appointments');?>
	</li>
</ul>
<ul id='q13'>
	<li>
	<?php _e('<b>How can I manually confirm an appointment?</b>', 'appointments');?>
	<br />
	<?php printf( __('Using the %s, find the appointment based on user name and change the status after you click <i>See Details and Edit</i> link. Note that this link will be visible only after you take the cursor over the record. Please also note that you can edit all the appointment data here.', 'appointments'), '<a href="'. admin_url("admin.php?page=appointments").'" target="_blank">'.__('Appointments admin page', 'appointments'). '</a>');?>
	</li>
</ul>
<ul id='q14'>
	<li>
	<?php _e('<b>Can I enter a manual appointment from admin side?</b>', 'appointments');?>
	<br />
	<?php printf( __('Yes. You may as well be having manual appointments, e.g. by phone. Just click <i>Add New</i> link on top of the %s and enter the fields and save the record. Please note that NO checks (Is that time frame free? Are we working that day? etc...) are done when you are entering a manual appointment. Consider entering or checking appointments from the front end to prevent mistakes.', 'appointments'), '<a href="'. admin_url("admin.php?page=appointments").'" target="_blank">'.__('Appointments admin page', 'appointments'). '</a>');?>
	</li>
</ul>
<ul id='q15'>
	<li>
	<?php _e('<b>I don\'t want front end appointments, I want to enter them only manually from admin side. What should I do?</b>', 'appointments');?>
	<br />
	<?php _e('If you don\'t want your schedule to be seen either, then simply do not add Schedule shortcode in your pages, or set that page as "private" for admin use. But if you want your schedule to be seen publicly, then just use Schedule shortcode, but no other shortcodes else.', 'appointments');?>
	</li>
</ul>
<ul id='q16'>
	<li>
	<?php _e('<b>I don\'t want my break times and holidays to be seen by the clients. How can I do that?</b>', 'appointments');?>
	<br />
	<?php _e('Select css background color for busy and not possible fields to be the same (for example white). Select <i>Show Legend</i> setting as No. Now, visitors can only see your free times and apply for those; they cannot distinguish if you are occupied or not working for the rest.', 'appointments');?>
	</li>
</ul>

<ul id='q17'>
	<li>
	<?php _e('<b>How can I prevent a second appointment by a client until I confirm his first appointment?</b>', 'appointments');?>
	<br />
	<?php _e('Enter a huge number, e.g. 10000000, in <i>Minimum time to pass for new appointment</i> field. Please note that this is not 100% safe and there is no safe solution against this unless you require payment to accept an appointment.', 'appointments');?>
	</li>
</ul>

<ul id='q18'>
	<li>
	<?php _e('<b>I have several service providers (workers) and each of them has different working hours, break hours and holidays. Does Appointments+ support this?</b>', 'appointments');?>
	<br />
	<?php _e('Yes. For each and every service provider you can individually set working, break hours and exceptions (holidays and additional working days). To do so, use the <i>Working Hours</i> and <i>Exceptions</i> tabs and select the service provider you want to make the changes from the service provider dropdown menu, make necessary changes and save. Plase note that when a service provider is added, his working schedule is set to the business working schedule. Thus, you only need to edit the variations of his schedule.', 'appointments');?>
	</li>
</ul>
<ul id='q19'>
	<li>
	<?php _e('<b>How can I set start day of the week and adjust date and time formats?</b>', 'appointments');?>
	<br />
	<?php printf(__('Appointments+ follows Wordpress date and time settings. If you change them from %s page, plugin will automatically adapt them.', 'appointments'), '<a href="'.admin_url('options-general.php').'" target="_blank">'.__('General Settings','appointments').'</a>');?>
	</li>
</ul>
<ul id='q20'>
	<li>
	<?php _e('<b>What does service capacity mean? Can you give an example?</b>', 'appointments');?>
	<br />
	<?php _e('It is the capacity of a service (e.g. because of technical reasons) independent of number of service providers giving that service. Imagine a dental clinic with three dentists working, each having their examination rooms, but there is only one X-Ray unit. Then, X-Ray Service has a capacity 1, and examination service has 3. Please note that you should only define capacity of X-Ray service 1 in this case. The other services whose capacity are left as zero will be automatically limited to the number of dentists giving that particular service. Because for those, limitation comes from the service providers, not from the service itself. Capacity field is for limiting the workforce, not for increasing it. See the FAQ in Advanced section to increase your available workforce and thus number of available appointments per time slot.', 'appointments');?>
	</li>
</ul>
<ul id='q21'>
	<li>
	<?php _e('<b>I have defined several services and service providers. For a particular service, there is no provider assigned. What happens?</b>', 'appointments');?>
	<br />
	<?php _e('For that particular service, clients cannot apply for an appointment because there will be no free time slot. Just delete services you are not using.', 'appointments');?>
	</li>
</ul>

<ul id='q22'>
	<li>
	<?php _e('<b>I am giving a service only on certain days of the week, different than my normal working days. Is it possible to set this in Appointments+?</b>', 'appointments');?>
	<br />
	<?php _e('Yes. Create a "dummy" service provider and assign that particular service only to it. Then set its working days as those days.', 'appointments');?>
	</li>
</ul>

<ul id='q23'>
	<li>
	<?php _e('<b>How can I permanently delete appointment records?</b>', 'appointments');?>
	<br />
	<?php printf( __('To avoid any mistakes, appointment records can only be deleted from Removed area of %s. First change the status of the appointment to "removed" and then delete it selecting the Removed area.', 'appointments'), '<a href="'. admin_url("admin.php?page=appointments&type=removed").'" target="_blank">'.__('Appointments admin page', 'appointments'). '</a>');?>
	</li>
</ul>

<ul id='q24'>
	<li>
	<?php _e('<b>What happens if a client was applying for an appointment but at the same time another client booked the same time slot?</b>', 'appointments');?>
	<br />
	<?php _e('Appointments+ checks the availability of the appointment twice: First when client clicks a free box and then when he clicks the confirmation button. If that time slot is taken by another client during these checks, he will be acknowledged that that time frame is not avaliable any more. All these checks are done in real time by ajax calls, so duplicate appointments are not possible.', 'appointments');?>
	</li>
</ul>

<ul id='q25'>
	<li>
	<?php _e('<b>What does the Built-in Cache do? Can I still use other caching plugins?</b>', 'appointments');?>
	<br />
	<?php _e('Appointments+ comes with a built-in specific cache. It functions only on appointment pages and caches the content part of the page only. It is recommended to enable it especially if you have a high traffic appointment page. You can continue to use other general purpose caching plugins, namely W3T, WP Super Cache, Quick Cache. The other object or page caching plugins are not tested.', 'appointments');?>
	</li>
</ul>

<ul id='q26'>
	<li>
	<?php _e('<b>I have just installed Appointments+ and nothing happens as I click a free time slot on the Make an Appointment page. What can be the problem?</b>', 'appointments');?>
	<br />
	<?php _e('If you have created the appointment page manually, check if you have added "app_confirmation" shortcode which is always required to complete an appointment. If this is not the case, you most likely have a javascript error on the page. This may be coming from a faulty theme or plugin. To confirm the javascript error, open the page using Google Chrome or Firefox and then press Ctrl+Shift+j. In the opening window if you see any warnings or errors, then switch to the default theme to locate the issue. If errors disappear, then you need to check and correct your theme files. If they don\'t disappear, then deactivate all your plugins and re-activate them one by one, starting from Appointments+ and check each time as you activate a plugin.', 'appointments');?>
	</li>
</ul>

<ul id='q27'>
	<li>
	<?php _e('<b>How is the plugin supposed to work by the way?</b>', 'appointments');?>
	<br />
	<?php printf( __('Please visit our %s.', 'appointments'), '<a href="http://appointmentsplus.org/" target="_blank">'.__('Demo website', 'appointments' ).'</a>');?>
	</li>
</ul>

<ul id='q28'>
	<li>
	<?php _e('<b>How does integration with Membership work? Are there any special considerations?</b>', 'appointments');?>
	<br />
	<?php _e('Membership member levels can be let exempt from advance payments/deposits. Also you can apply discounts for the selected membership levels. There are no special considerations: Appointments+ will be managing them automatically.', 'appointments');?>

	</li>
</ul>

<ul id='q29'>
	<li>
	<?php _e('<b>How does integration with MarketPress work? Are there any special considerations?</b>', 'appointments');?>
	<br />
	<?php _e('If you select <i>Integrate with MarketPress</i> which is visible after <i>Payment Required</i> is set as Yes, any MarketPress product page having Appointments+ shortcodes will be regarded as an "Appointment Product Page". Those pages are automatically modified and you are not supposed to be doing anything special. For your information, here is how the integration works:', 'appointments');?>
	<br />
	<ul>
	<li>
	<?php _e('An Appointment will be regarded as a digital product, therefore shipping information is not asked if ordered alone.', 'appointments');?>
	</li>
	<li>
	<?php _e('Like any other digital product, quantity of an appointment is always fixed to 1, but client can add as many appointments as he wishes with different variations, that is, with different date and time.', 'appointments');?>
	</li>
	<li>
	<?php _e('Download link that is normally added to confirmation email for digital product orders is removed.', 'appointments');?>
	</li>
	<li>
	<?php _e('Appointments in the cart are shown as "Appointment Product Page Title: Appointment ID (Appointment date and time)".', 'appointments');?>
	</li>
	<li>
	<?php _e('"Add to Cart" and "Buy Now" buttons on the Appointment Product page are not visible until client confirms the appointment.', 'appointments');?>
	</li>
	<li>
	<?php _e('"Add to Cart" and "Buy Now" buttons are only possible for a full appointment product page, therefore on products list page, an Appointments+ product will always have a "Choose Option" button. No price will be shown. For the same reason, please use Single Product shortcode with only content="full" setting.', 'appointments');?>
	</li>
	<li>
	<?php _e('Paypal button of Appointments+ is invisible and thus its own Paypal Standard Payments option is disabled. Client will use the payment gateways MarketPress is providing. You can use all MarketPress payment gateways.', 'appointments');?>
	</li>
	<li>
	<?php _e('Quantity and Variation fields on the product page are always invisible.', 'appointments');?>
	</li>
	<li>
	<?php _e('Price of the appointment on the cart is the deposit price, if a deposit field is set. Otherwise it is the full price.', 'appointments');?>
	</li>
	<li>
	<?php _e('If an appointment product is manually removed from the cart by the client, its record will also be removed from the appointments table.', 'appointments');?>
	</li>
	<li>
	<?php _e('An appointment product can be automatically removed from the cart if "Disable pending appointments after" setting is set and client does not finalize the purchase during that time. Thus you may consider to add a warning note that transaction should be completed within the selected time.', 'appointments');?>
	</li>
	<li>
	<?php _e('If this happens while client is paying and client does pay, however, that appointment will be taken out from removed status and it will be marked as paid.', 'appointments');?>
	</li>
	<li>
	<?php _e('On the admin product management page if it is an Appointments+ Product, variations, SKU, price column fields will display "-".', 'appointments');?>
	</li>
	<li>
	<?php _e('Transactions are shown in MarketPress, but related appointment record is updated, that is, status is changed to "paid".', 'appointments');?>
	</li>
	<li>
	<?php _e('If Manual Payment gateway is activated and client uses that method, appointment will be in "pending" status until you manually confirm it.', 'appointments');?>
	</li>
	</ul>
	</li>
</ul>

<ul id='q30'>
	<li>
	<?php _e('<b>What does DUMMY service provider mean? How can I get use of it?</b>', 'appointments');?>
	<br />
	<?php _e('A Dummy service provider can be an imaginary or real user intended to enrich your business. It behaves exactly like a normal user, except that all emails are sent to a preselected real user from General settings tab. With this feature you can arrange your working schedules better and imitate several workers behaviour without losing the communication with the client. If you are combining several dummy providers into one real user, you can set service capacity to disallow more appointments than you can handle.', 'appointments');?>
	</li>
</ul>

<ul id='q31'>
	<li>
	<?php _e('<b>How can I view my planner as a service provider in calendar view?</b>', 'appointments');?>
	<br />
	<?php _e('Create a private or password protected page and include the schedule shortcode like this: <code>[app_monthly_schedule worker="1"]</code>. Replace 1 with your user ID which is the same as worker/provider ID. You can also use app_schedule shortcode for a weekly planner instead, or you can use both. You may consider adding app_pagination and app_my_appointments shortcodes too.', 'appointments');?>
	</li>
</ul>

<ul id='q32'>
	<li>
	<?php _e('<b>My working hours cover the midnight and exceeds to the other day. For example from 8pm Monday to 2am Tuesday. Is it possible to set this?</b>', 'appointments');?>
	<br />
	<?php _e('Yes. Set your working hours as 24am to 24am (00:00 to 00:00) and your break hours as 2am to 8pm (02:00 to 20:00).', 'appointments');?>
	</li>
</ul>

<ul id='q33'>
	<li>
	<?php _e('<b>What are the prerequisites to use Google Calendar API?</b>', 'appointments');?>
	<br />
	<?php _e('PHP V5.3 or above, php extentions curl, JSON, http_build_query are required to use Google Calendar API. Also you need to have FTP access to your website in order to upload the private key file. This file cannot be uploaded using WordPress media upload function.', 'appointments');?>
	</li>
</ul>

<ul id='q34'>
	<li>
	<?php _e('<b>Why do I need this Google Calendar API key file anyway? Isn\'t there any other way?</b>', 'appointments');?>
	<br />
	<?php _e('As of now, there is no other way and it is unlikely to be another way in the future. This key file serves as an electronic signature which proves that you grant access to your Google Calendar account.', 'appointments');?>
	</li>
</ul>

<ul id='q35'>
	<li>
	<?php _e('<b>But I am using another application which does not need Google Calendar API key file. How does that application work then?</b>', 'appointments');?>
	<br />
	<?php _e('Such applications make the synchronisation when you are online. It is easy to receive your consent when your are online. Your being logged in is sufficient, but necessary. Here, we need your consent, thus electronic signature, even if you are offline, so that appointments can be submitted to your Google Calendar any time. For the same reason, each service provider who wants to synchronize his/her appointments with Google Calendar should carry out the required setup steps.', 'appointments');?>
	</li>
</ul>

<ul id='q36'>
	<li>
	<?php _e('<b>Google Calendar Integration is not working and/or I am getting some errors. What can be the reasons and how can I solve them?</b>', 'appointments');?>
	<br />
	<?php _e('Always double check your settings and compare those with the tutorial video. Common mistakes: Calendar API is not turned on, service account does not have "make changes to events" authority, wrong calendar is selected.', 'appointments');?>
	<br /><br />
	<?php _e('There are some limitations of Google Calendar itself too: 1) Check that you are NOT using your primary calendar, 2) Check that your account is NOT a business account.', 'appointments');?>
	<br /><br />
	<?php _e('Some errors may not be displayed and can be saved in log file. Check Logs tab for those messages.', 'appointments');?>
	<br /><br />
	<?php _e('For details read instructions and notes in Google Calendar tab carefully.', 'appointments');?>
	</li>
</ul>

<ul id='q37'>
	<li>
	<?php _e('<b>How can I let my service providers freely edit appointments?</b>', 'appointments');?>
	<br />
	<?php _e('Appointment records can only be edited on the admin side appointment page. Use an appropriate role/capability manager plugin and grant "manage_options" capability to your service providers. There are some plugins that let you select particular roles, even particular users/providers for this purpose.', 'appointments');?>
	</li>
</ul>

<ul id='q38'>
	<li>
	<?php _e('<b>How can I let my clients cancel their own appointments?</b>', 'appointments');?>
	<br />
	<?php _e('First of all, you must set "Allow client cancel own appointments" setting as Yes. Then:', 'appointments');?>
	<ul>
		<li>
		<?php _e('A logged in client can cancel his appointments using his profile page.', 'appointments');?>
		</li>
		<li>
		<?php _e('Any client can cancel his own appointment using links in confirmation and/or reminder emails if you use CANCEL placeholder in your email bodies.', 'appointments');?>
		</li>
		<li>
		<?php _e('Any client can cancel his own appointment using the checkbox in my appointments table if you set allow_cancel="1" in my_appointments shortcode.', 'appointments');?>
		</li>

	</ul>
	</li>
</ul>


<br />
<br />
<h2><?php _e( 'Advanced', 'appointments') ?></h2>
<ul>
	<li>
	<?php _e('This part of FAQ requires some knowledge about HTML, php and/or WordPress coding.', 'appointments');?>
	</li>
</ul>

<ul id='q39'>
	<li>
	<?php _e('<b>Can I create my own page templates?</b>', 'appointments');?>
	<br />
	<?php _e('Yes. Using <code>do_shortcode</code> function and loading Appointments+ css and javascript files, you can do this. See sample-appointments-page.php file in /includes directory for a sample.', 'appointments');?>
	</li>
</ul>

<ul id='q40'>
	<li>
	<?php _e('<b>I have customized the front.css file. How can I prevent it being overwritten by plugin updates?</b>', 'appointments');?>
	<br />
	<?php _e('Copy front.css content and paste it in css file of your theme. Add this code inside functions.php of the theme: <code>add_theme_support( "appointments_style" )</code>. Then, integral plugin css file front.css will not be called.', 'appointments');?>
	</li>
</ul>

<ul id='q41'>
	<li>
	<?php _e('<b>Is it possible not to ask payment or deposit for certain users?</b>', 'appointments');?>
	<br />
	<?php _e('Please note that this is quite simple if you are using Membership plugin. If not, referring this filter create a function in your functions.php to set Paypal price as zero for the selected user, user role and/or service: <code>$paypal_price = apply_filters( \'app_paypal_amount\', $paypal_price, $service, $worker, $user_id );</code> This will not make the service free of charge, but user will not be asked for an advance payment.', 'appointments');?>
	</li>
</ul>

<ul id='q42'>
	<li>
	<?php _e('<b>How can I force the schedules start at a non standard time, for example 9:15?</b>', 'appointments');?>
	<br />
	<?php _e('Add these codes inside functions.php of your current theme (9.25 is not a typo; that is because 15/60=0.25): ', 'appointments');?>
	<br />
	<code>
	function new_starting_hour( $start ) {
	<br />&nbsp;&nbsp;&nbsp;&nbsp;return 9.25;
	<br />}
	<br />add_filter( 'app_schedule_starting_hour', 'new_starting_hour' );
	</code>
	</li>
</ul>

<ul id='q43'>
	<li>
	<?php _e('<b>I want to accept more than one appointment applications for each time slot. Entering higher numbers in "capacity" field in Services tab does not work. Why?</b>', 'appointments');?>
	<br />
	<?php _e('Please note that Appointments+ is designed for one-to-one appointments, that is, one service provider serving a single client at a time and to manage available workforce. Capacity field is for limiting the workforce, not for increasing it. You have two alternatives to achieve this: a) Use dummy service providers b) Add these codes in functions.php and modify as required:', 'appointments');?>
	<br />
	<code>
	function increase_capacity( $capacity, $service_id, $worker_id ) {
	<br />&nbsp;&nbsp;&nbsp;&nbsp;return 10;
	<br />}
	<br />add_filter( 'app_get_capacity', 'increase_capacity', 10, 3 );
	</code>
	</li>
	<?php _e('This filter will NOT work if there is a single provider giving the selected service. Please also note that this is a kind of "hack" and when you have more than one service provider, this function may not work as expected in regards to working hours, as "virtual" providers will not be bound to working hours of existing providers. ', 'appointments');?>
	<br />
</ul>

<ul id='q44'>
	<li>
	<?php _e('<b>How can I use HTML in emails?</b>', 'appointments');?>
	<br />
	<?php _e('Appointments+ uses wp_mail function and HTML is disabled as default. To enable HTML in emails, add these codes inside functions.php:', 'appointments');?>
	<br />
	<code>
	function app_modify_headers( $headers ) {
	<br />&nbsp;&nbsp;&nbsp;&nbsp;return str_replace( 'text/plain', 'text/html', $headers );
	<br />}
	<br />add_filter( 'app_message_headers', 'app_modify_headers' );
	</code>
	</li>
</ul>

<ul id='q45'>
	<li>
	<?php _e('<b>I have a time base of 10 minutes. I have services up to 480 minutes. How can I achieve this?</b>', 'appointments');?>
	<br />
	<?php _e('The following example lets you select services with durations up to 48*10=480 minutes. Add these codes inside functions.php:', 'appointments');?>
	<br />
	<code>
	function increase_service_selections( $n ){
	<br />&nbsp;&nbsp;&nbsp;&nbsp;return 48;
	<br />}
	<br />add_filter( 'app_selectable_durations', 'increase_service_selections' );
	</code>
	</li>
</ul>

<ul id='q46'>
	<li>
	<?php _e('<b>How can I redirect a user after a confirmed appointment to a thank you page when payment is not required?</b>', 'appointments');?>
	<br />
	<?php _e('Add these codes inside functions.php and modify the example.com url as required:', 'appointments');?>
	<br />
	<code>
	function app_redirect( $script ){
	<br />&nbsp;&nbsp;&nbsp;&nbsp;return str_replace("window.location.href=app_location()", "window.location.href='http://example.com'", $script);
	<br />}
	<br />add_filter( 'app_footer_scripts', 'app_redirect' );
	</code>
	</li>
</ul>


<ul id='q47'>
	<li>
	<?php _e('<b>How can I show hours instead of minutes in the front end when my services last more than an hour?</b>', 'appointments');?>
	<br />
	<?php _e('Use this sample and modify as required:', 'appointments');?>
	<br />
	<code>
	function convert_to_hour( $text, $duration ) {
	<br />&nbsp;&nbsp;&nbsp;&nbsp;if ( $duration < 60 ) return $text;
	<br />&nbsp;&nbsp;&nbsp;&nbsp;$hours = floor($duration/60);
	<br />&nbsp;&nbsp;&nbsp;&nbsp;if ( $hours > 1 ) $hour_text = ' hours ';
	<br />&nbsp;&nbsp;&nbsp;&nbsp;else $hour_text = ' hour ';
	<br />&nbsp;&nbsp;&nbsp;&nbsp;$mins = $duration - $hours *60;
	<br />&nbsp;&nbsp;&nbsp;&nbsp;if ( $mins ) $min_text = $mins . ' minutes';
	<br />&nbsp;&nbsp;&nbsp;&nbsp;else $min_text = '';
	<br />&nbsp;&nbsp;&nbsp;&nbsp;return $hours . $hour_text . $min_text;
	<br />}
	<br /> add_filter('app_confirmation_lasts', 'convert_to_hour', 10, 2);
	</code>
	</li>
</ul>