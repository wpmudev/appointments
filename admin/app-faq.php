<?php
$questions = array(
	0 => __( '-- Select a question --', 'appointments' ),
	1 => __( "How can I restart the tutorial?", 'appointments' ),
	2 => __( "What is the importance of Time Base and how should I set it?", 'appointments' ),
	3 => __( "I don't see the time base that I need. For example I need 240 minutes appointments. How can I do that?", 'appointments' ),
	4 => __( "What is the complete process for an appointment?", 'appointments' ),
	5 => __( "Is it necessary to have at least one service?", 'appointments' ),
	6 => __( "Is it necessary to define service providers?", 'appointments' ),
	7 => __( "Is it necessary to use Services and Service Providers shortcodes?", 'appointments' ),
	8 => __( "Does Appointments+ provide some widgets?", 'appointments' ),
	9 => __( "Can I use the shortcodes in any page as I wish?", 'appointments' ),
	10 => __( "Can I have schedules showing more than two weeks or months on the same page?", 'appointments' ),
	11 => __( "Does the client need to be registered to the website to apply for an appointment?", 'appointments' ),
	12 => __( "How are the appointments confirmed?", 'appointments' ),
	13 => __( "How can I manually confirm an appointment?", 'appointments' ),
	14 => __( "Can I enter a manual appointment from admin side?", 'appointments' ),
	15 => __( "I don't want front end appointments, I want to enter them only manually from admin side. What should I do?", 'appointments' ),
	16 => __( "I don't want my break times and holidays to be seen by the clients. How can I do that?", 'appointments' ),
	17 => __( "How can I prevent a second appointment by a client until I confirm his first appointment?", 'appointments' ),
	18 => __( "I have several service providers (workers) and each of them has different working hours, break hours and holidays. Does Appointments+ support this?", 'appointments' ),
	19 => __( "How can I set start day of the week and adjust date and time formats?", 'appointments' ),
	20 => __( "What does service capacity mean? Can you give an example?", 'appointments' ),
	21 => __( "I have defined several services and service providers. For a particular service, there is no provider assigned. What happens?", 'appointments' ),
	22 => __( "I am giving a service only on certain days of the week, different than my normal working days. Is it possible to set this in Appointments+?", 'appointments' ),
	23 => __( "How can I permanently delete appointment records?", 'appointments' ),
	24 => __( "What happens if a client was applying for an appointment but at the same time another client booked the same time slot?", 'appointments' ),
	25 => __( "What does the Built-in Cache do? Can I still use other caching plugins?", 'appointments' ),
	26 => __( "I have just installed Appointments+ and nothing happens as I click a free time slot on the Make an Appointment page. What can be the problem?", 'appointments' ),
	27 => '',
	28 => __( "How is the plugin supposed to work by the way?", 'appointments' ),
	29 => __( "How does integration with Membership work? Are there any special considerations?", 'appointments' ),
	30 => __( "What does DUMMY service provider mean? How can I get use of it?", 'appointments' ),
	31 => __( "How can I view my planner as a service provider in calendar view?", 'appointments' ),
	32 => __( "My working hours cover the midnight and exceeds to the other day. For example from 8pm Monday to 2am Tuesday. Is it possible to set this?", 'appointments' ),
	33 => __( "What are the prerequisites to use Google Calendar API?", 'appointments' ),
	34 => __( "Why do I need this Google Calendar API key file anyway? Isn't there any other way?", 'appointments' ),
	35 => __( "But I am using another application which does not need Google Calendar API key file. How does that application work then?", 'appointments' ),
	36 => __( "Google Calendar Integration is not working and/or I am getting some errors. What can be the reasons and how can I solve them?", 'appointments' ),
	37 => __( "How can I let my service providers freely edit appointments?", 'appointments' ),
	38 => __( "How can I let my clients cancel their own appointments?", 'appointments' ),
	39 => __( "Can I create my own page templates?", 'appointments' ),
	40 => __( "I have customized the front.css file. How can I prevent it being overwritten by plugin updates?", 'appointments' ),
	41 => __( "Is it possible not to ask payment or deposit for certain users?", 'appointments' ),
	42 => __( "How can I force the schedules start at a non standard time, for example 9:15?", 'appointments' ),
	43 => __( 'I want to accept more than one appointment applications for each time slot. Entering higher numbers in "capacity" field in Services tab does not work. Why?', 'appointments' ),
	44 => __( "How can I use HTML in emails?", 'appointments' ),
	45 => __( "I have a time base of 10 minutes. I have services up to 480 minutes. How can I achieve this?", 'appointments' ),
	46 => '',
	47 => __( "How can I show hours instead of minutes in the front end when my services last more than an hour?", 'appointments' )
);
?>
<!--script type="text/javascript">
jQuery(document).ready(function($){
	$('ul li b').each(function(){
		n = parseInt( $(this).closest('ul').attr('id').replace('q','') ) - 0;
		$('.app_faq_wrap').append( '<a href="#q'+n+'">' + $(this).html() + '</b></a>');
		$(this).closest('ul').after( '<a href="#app_faq_wrap">Go to Top</b></a>')
	});
	$('.wrap ul').css('position','relative').css('padding-top','20px').css('font-size','14px');
	$('.wrap ul ul').css('list-style-type','square');
	$('.wrap ul ul li').css('margin-left','15px');
	$('#app_faq_wrap a').css('line-height','2em');
});
</script-->

<style>
	.faq-answers li {
		background:white;
		padding:10px 20px;
		border:1px solid #cacaca;
	}
</style>

<ul id="faq-index">
	<?php foreach ( $questions as $question_index => $question ): ?>
		<li data-answer="<?php echo $question_index; ?>"><a href="#q<?php echo $question_index; ?>"><?php echo $question; ?></a></li>
	<?php endforeach; ?>
</ul>

<ul class="faq-answers">
	<li class="faq-answer" id='q1'>
		<?php _e('To restart tutorial about settings click here:', 'appointments');?>
		<?php
		$link = add_query_arg( array( "tutorial"=>"restart1" ), admin_url("admin.php?page=app_settings") );
		?>
		<a href="<?php echo $link ?>" ><?php _e( 'Settings Tutorial Restart', 'appointments' ) ?></a>

		<?php _e('To restart tutorial about entering and editing Appointments click here:', 'appointments');?>
		<?php
		$link = add_query_arg( array( "tutorial"=>"restart2" ), admin_url("admin.php?page=app_settings") );
		?>
		<a href="<?php echo $link ?>" ><?php _e( 'Appointments Creation and Editing Tutorial Restart', 'appointments' ) ?></a>
	</li>
	<li class="faq-answer" id='q2'>
		<p> <?php _e('<i>Time Base</i> is the most important parameter of Appointments+. It is the minimum time that you can select for your appointments. If you set it too high then you may not be possible to optimize your appointments. If you set it too low, your schedule will be too crowded and you may have difficulty in managing your appointments. You should enter here the duration of the shortest service you are providing. Please also note that service durations can only be multiples of the time base. So if you need 30 and 45 minutes services, you should select 15 minutes as the time base.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q3'>
		<p> <?php _e('You can add one more time base using <i>Additional time base</i> setting. You must select this setting in <i>time base</i> setting to be effective.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q4'>
		<p><?php _e('With the widest settings, client will do the followings on the front page:', 'appointments');?></p>
		<p> <?php _e('Select a service', 'appointments');?> </p>
		<p> <?php _e('Select a service provider', 'appointments');?> </p>
		<p> <?php _e('Select a free time on the schedule', 'appointments');?> </p>
		<p> <?php _e('Login (if required)', 'appointments');?> </p>
		<p> <?php _e('Enter the required fields (name, email, phone, address, city) and confirm the selected appointment', 'appointments');?> </p>
		<p> <?php _e('Click Paypal payment button (if required)', 'appointments');?> </p>
		<p> <?php _e('Redirected to a Thank You page after Paypal payment', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q5'>
		<p> <?php _e('Yes. Appointments+ requires at least one service to be defined. Please note that a default service should have been already installed during installation. If you delete it, and no other service remains, then you will get a warning message. In this case plugin may not function properly.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q6'>
		<p> <?php _e('No. You may as well be working by yourself, doing your own business. Plugin will work properly without any service provider, i.e worker, defined. In this case Appointments+ assumes that there is ONE service provider working, giving all the services.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q7'>
		<p> <?php _e('No. If you do not use these shortcodes then your client will not be able to select a service and Appointments+ will pick the service with the smallest ID or the one selected with "service" parameter of the schedule shortcode. We have already noted that a service provider definition is only optional.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q8'>
		<p> <?php _e('Yes. Appointments+ has Services and Service Providers widgets which provides a list of service or service providers with links to their description/bio pages and a Monthly Calendar widget that redirects user to the selected appointment page when a free day is clicked. Note: Service and service provider items not having assigned description/bio pages are not displayed.', 'appointments');?>s </p>
	</li>
	<li class="faq-answer" id='q9'>
		<p> <?php _e('Some shortcodes have only meaning if they are used in combination with some others. For example the Services shortcode will not have a function unless you have a Schedule on the same page. They are defined as separate shortcodes so that you can customize them on your pages. Except for My Appointments and Schedule shortcodes, only one instance of a shortcode is allowed on the same page.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q10'>
		<p> <?php printf( __('Yes. Use "add" parameter of schedule shortcode to add additional schedules. There is no limit for the number of schedules that you can use on the same page. See %s tab for details.', 'appointments'), '<a href="'.admin_url('admin.php?page=app_settings&tab=shortcodes').'">'.__('Shortcodes', 'appointments') .'</a>');?> </p>
	</li>
	<li class="faq-answer" id='q11'>
		<p> <?php _e('You can set whether this is required with <i>Login Required</i> setting. You can ask details (name, email, phone, address, city) about the client before accepting the appointment, thus you may not need user registrations. These data are saved in a cookie and autofilled when they apply for a new appointment, so your regular clients do not need to refill them.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q12'>
		<p> <?php _e('If you have selected <i>Payment Required</i> field as Yes, then an appointment is automatically confirmed after a succesful Paypal payment and confirmation of Paypal IPN. If you selected Payment Required as No, then confirmation can be done manually, or automatically depending on Auto Confirm setting.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q13'>
		<p> <?php printf( __('Using the %s, find the appointment based on user name and change the status after you click <i>See Details and Edit</i> link. Note that this link will be visible only after you take the cursor over the record. Please also note that you can edit all the appointment data here.', 'appointments'), '<a href="'. admin_url("admin.php?page=appointments").'" target="_blank">'.__('Appointments admin page', 'appointments'). '</a>');?> </p>
	</li>
	<li class="faq-answer" id='q14'>
		<p> <?php printf( __('Yes. You may as well be having manual appointments, e.g. by phone. Just click <i>Add New</i> link on top of the %s and enter the fields and save the record. Please note that NO checks (Is that time frame free? Are we working that day? etc...) are done when you are entering a manual appointment. Consider entering or checking appointments from the front end to prevent mistakes.', 'appointments'), '<a href="'. admin_url("admin.php?page=appointments").'" target="_blank">'.__('Appointments admin page', 'appointments'). '</a>');?> </p>
	</li>
	<li class="faq-answer" id='q15'>
		<p> <?php _e('If you don\'t want your schedule to be seen either, then simply do not add Schedule shortcode in your pages, or set that page as "private" for admin use. But if you want your schedule to be seen publicly, then just use Schedule shortcode, but no other shortcodes else.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q16'>
		<p> <?php _e('Select css background color for busy and not possible fields to be the same (for example white). Select <i>Show Legend</i> setting as No. Now, visitors can only see your free times and apply for those; they cannot distinguish if you are occupied or not working for the rest.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q17'>
		<p> <?php _e('Enter a huge number, e.g. 10000000, in <i>Minimum time to pass for new appointment</i> field. Please note that this is not 100% safe and there is no safe solution against this unless you require payment to accept an appointment.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q18'>
		<p> <?php _e('Yes. For each and every service provider you can individually set working, break hours and exceptions (holidays and additional working days). To do so, use the <i>Working Hours</i> and <i>Exceptions</i> tabs and select the service provider you want to make the changes from the service provider dropdown menu, make necessary changes and save. Please note that when a service provider is added, his working schedule is set to the business working schedule. Thus, you only need to edit the variations of his schedule.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q19'>
		<p> <?php printf(__('Appointments+ follows WordPress date and time settings. If you change them from %s page, plugin will automatically adapt them.', 'appointments'), '<a href="'.admin_url('options-general.php').'" target="_blank">'.__('General Settings','appointments').'</a>');?> </p>
	</li>
	<li class="faq-answer" id='q20'>
		<p> <?php _e('It is the capacity of a service (e.g. because of technical reasons) independent of number of service providers giving that service. Imagine a dental clinic with three dentists working, each having their examination rooms, but there is only one X-Ray unit. Then, X-Ray Service has a capacity 1, and examination service has 3. Please note that you should only define capacity of X-Ray service 1 in this case. The other services whose capacity are left as zero will be automatically limited to the number of dentists giving that particular service. Because for those, limitation comes from the service providers, not from the service itself. Capacity field is for limiting the workforce, not for increasing it. See the FAQ in Advanced section to increase your available workforce and thus number of available appointments per time slot.', 'appointments');?> </p>
	</li>
	<li class="faq-answer" id='q21'>
		<p> <?php _e('For that particular service, clients cannot apply for an appointment because there will be no free time slot. Just delete services you are not using.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q22'>
		<p> <?php _e('Yes. Create a "dummy" service provider and assign that particular service only to it. Then set its working days as those days.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q23'>
		<p> <?php printf( __('To avoid any mistakes, appointment records can only be deleted from Removed area of %s. First change the status of the appointment to "removed" and then delete it selecting the Removed area.', 'appointments'), '<a href="'. admin_url("admin.php?page=appointments&type=removed").'" target="_blank">'.__('Appointments admin page', 'appointments'). '</a>');?> </p>
	</li>

	<li class="faq-answer" id='q24'>
		<p> <?php _e('Appointments+ checks the availability of the appointment twice: First when client clicks a free box and then when he clicks the confirmation button. If that time slot is taken by another client during these checks, he will be acknowledged that that time frame is not avaliable any more. All these checks are done in real time by ajax calls, so duplicate appointments are not possible.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q25'>
		<p> <?php _e('Appointments+ comes with a built-in specific cache. It functions only on appointment pages and caches the content part of the page only. It is recommended to enable it especially if you have a high traffic appointment page. You can continue to use other general purpose caching plugins, namely W3T, WP Super Cache, Quick Cache. The other object or page caching plugins are not tested.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q26'>
		<p> <?php _e('If you have created the appointment page manually, check if you have added "app_confirmation" shortcode which is always required to complete an appointment. If this is not the case, you most likely have a javascript error on the page. This may be coming from a faulty theme or plugin. To confirm the javascript error, open the page using Google Chrome or Firefox and then press Ctrl+Shift+j. In the opening window if you see any warnings or errors, then switch to the default theme to locate the issue. If errors disappear, then you need to check and correct your theme files. If they don\'t disappear, then deactivate all your plugins and re-activate them one by one, starting from Appointments+ and check each time as you activate a plugin.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q28'>
		<p> <?php _e('Membership member levels can be let exempt from advance payments/deposits. Also you can apply discounts for the selected membership levels. There are no special considerations: Appointments+ will be managing them automatically.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q29'>
		<p> <?php _e('If you select <i>Integrate with MarketPress</i> which is visible after <i>Payment Required</i> is set as Yes, any MarketPress product page having Appointments+ shortcodes will be regarded as an "Appointment Product Page". Those pages are automatically modified and you are not supposed to be doing anything special. For your information, here is how the integration works:', 'appointments');?> </p>
		<p> <?php _e('An Appointment will be regarded as a digital product, therefore shipping information is not asked if ordered alone.', 'appointments');?> </p>
		<p> <?php _e('Like any other digital product, quantity of an appointment is always fixed to 1, but client can add as many appointments as he wishes with different variations, that is, with different date and time.', 'appointments');?> </p>
		<p> <?php _e('Download link that is normally added to confirmation email for digital product orders is removed.', 'appointments');?> </p>
		<p> <?php _e('Appointments in the cart are shown as "Appointment Product Page Title: Appointment ID (Appointment date and time)".', 'appointments');?> </p>
		<p> <?php _e('"Add to Cart" and "Buy Now" buttons on the Appointment Product page are not visible until client confirms the appointment.', 'appointments');?> </p>
		<p> <?php _e('"Add to Cart" and "Buy Now" buttons are only possible for a full appointment product page, therefore on products list page, an Appointments+ product will always have a "Choose Option" button. No price will be shown. For the same reason, please use Single Product shortcode with only content="full" setting.', 'appointments');?> </p>
		<p> <?php _e('Paypal button of Appointments+ is invisible and thus its own Paypal Standard Payments option is disabled. Client will use the payment gateways MarketPress is providing. You can use all MarketPress payment gateways.', 'appointments');?> </p>
		<p> <?php _e('Quantity and Variation fields on the product page are always invisible.', 'appointments');?> </p>
		<p> <?php _e('Price of the appointment on the cart is the deposit price, if a deposit field is set. Otherwise it is the full price.', 'appointments');?> </p>
		<p> <?php _e('If an appointment product is manually removed from the cart by the client, its record will also be removed from the appointments table.', 'appointments');?> </p>
		<p> <?php _e('An appointment product can be automatically removed from the cart if "Disable pending appointments after" setting is set and client does not finalize the purchase during that time. Thus you may consider to add a warning note that transaction should be completed within the selected time.', 'appointments');?> </p>
		<p> <?php _e('If this happens while client is paying and client does pay, however, that appointment will be taken out from removed status and it will be marked as paid.', 'appointments');?> </p>
		<p> <?php _e('On the admin product management page if it is an Appointments+ Product, variations, SKU, price column fields will display "-".', 'appointments');?> </p>
		<p> <?php _e('Transactions are shown in MarketPress, but related appointment record is updated, that is, status is changed to "paid".', 'appointments');?> </p>
		<p> <?php _e('If Manual Payment gateway is activated and client uses that method, appointment will be in "pending" status until you manually confirm it.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q30'>
		<p> <?php _e('A Dummy service provider can be an imaginary or real user intended to enrich your business. It behaves exactly like a normal user, except that all emails are sent to a preselected real user from General settings tab. With this feature you can arrange your working schedules better and imitate several workers behaviour without losing the communication with the client. If you are combining several dummy providers into one real user, you can set service capacity to disallow more appointments than you can handle.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q31'>
		<p> <?php _e('Create a private or password protected page and include the schedule shortcode like this: <pre>[app_monthly_schedule worker="1"]</pre>. Replace 1 with your user ID which is the same as worker/provider ID. You can also use app_schedule shortcode for a weekly planner instead, or you can use both. You may consider adding app_pagination and app_my_appointments shortcodes too.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q32'>
		<p> <?php _e('Yes. Set your working hours as 24am to 24am (00:00 to 00:00) and your break hours as 2am to 8pm (02:00 to 20:00). Remember to also allow end of day overwork in General settings.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q33'>
		<p> <?php _e('PHP V5.3 or above, php extentions curl, JSON, http_build_query are required to use Google Calendar API. Also you need to have FTP access to your website in order to upload the private key file. This file cannot be uploaded using WordPress media upload function.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q34'>
		<p> <?php _e('As of now, there is no other way and it is unlikely to be another way in the future. This key file serves as an electronic signature which proves that you grant access to your Google Calendar account.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q35'>
		<p> <?php _e('Such applications make the synchronisation when you are online. It is easy to receive your consent when your are online. Your being logged in is sufficient, but necessary. Here, we need your consent, thus electronic signature, even if you are offline, so that appointments can be submitted to your Google Calendar any time. For the same reason, each service provider who wants to synchronize his/her appointments with Google Calendar should carry out the required setup steps.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q36'>
		<p>
			<?php _e('Always double check your settings and compare those with the tutorial video. Common mistakes: Calendar API is not turned on, service account does not have "make changes to events" authority, wrong calendar is selected.', 'appointments');?>

			<?php _e('There are some limitations of Google Calendar itself too: 1) Check that you are NOT using your primary calendar, 2) Check that your account is NOT a business account.', 'appointments');?>

			<?php _e('Some errors may not be displayed and can be saved in log file. Check Logs tab for those messages.', 'appointments');?>

			<?php _e('For details read instructions and notes in Google Calendar tab carefully.', 'appointments');?>
		</p>
	</li>

	<li class="faq-answer" id='q37'>
		<p> <?php _e('Appointment records can only be edited on the admin side appointment page. Use an appropriate role/capability manager plugin and grant "manage_options" capability to your service providers. There are some plugins that let you select particular roles, even particular users/providers for this purpose.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q38'>
		<p> <?php _e('First of all, you must set "Allow client cancel own appointments" setting as Yes. Then:', 'appointments');?> </p>
		<p><?php _e('A logged in client can cancel his appointments using his profile page.', 'appointments');?> </p>
		<p style="display: none;"> <?php _e('Any client can cancel his own appointment using links in confirmation and/or reminder emails if you use CANCEL placeholder in your email bodies.', 'appointments');?> </p>
		<p> <?php _e('Any client can cancel his own appointment using the checkbox in my appointments table if you set allow_cancel="1" in my_appointments shortcode.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q39'>
		<p> <?php _e('Yes. Using <pre>do_shortcode</pre> function and loading Appointments+ css and javascript files, you can do this. See sample-appointments-page.php file in /includes directory for a sample.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q40'>
		<p> <?php _e('Copy front.css content and paste it in css file of your theme. Add this code inside functions.php of the theme: <pre>add_theme_support( "appointments_style" )</pre>. Then, integral plugin css file front.css will not be called.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q41'>
		<p> <?php _e('Please note that this is quite simple if you are using Membership plugin. If not, referring this filter create a function in your functions.php to set Paypal price as zero for the selected user, user role and/or service: <pre>$paypal_price = apply_filters( \'app_paypal_amount\', $paypal_price, $service, $worker, $user_id );</pre> This will not make the service free of charge, but user will not be asked for an advance payment.', 'appointments');?> </p>
	</li>

	<li class="faq-answer" id='q42'>
		<p> <?php _e('Add these codes inside functions.php of your current theme (9.25 is not a typo; that is because 15/60=0.25): ', 'appointments');?> </p>

	<pre>
	function new_starting_hour( $start ) {
		return 9.25;
	}
	add_filter( 'app_schedule_starting_hour', 'new_starting_hour' );
	</pre>
	</li>

	<li class="faq-answer" id='q43'>
		<p> <?php _e('Please note that Appointments+ is designed for one-to-one appointments, that is, one service provider serving a single client at a time and to manage available workforce. Capacity field is for limiting the workforce, not for increasing it. You have two alternatives to achieve this: a) Use dummy service providers b) Add these codes in functions.php and modify as required:', 'appointments');?></p>

	<pre>
	function increase_capacity( $capacity, $service_id ) {
		return 10;
	}
	add_filter( 'app_get_capacity', 'increase_capacity', 10, 2 );
	add_filter( 'app-is_busy', '__return_false', 11 );
	</pre>

		<?php _e('Please note that this is a kind of "hack" and when you have more than one service provider, this function may not work as expected in regards to working hours, as "virtual" providers will not be bound to working hours of existing providers.', 'appointments');?>

	</li>

	<li class="faq-answer" id='q44'>
		<p> <?php _e('Appointments+ uses wp_mail function and HTML is disabled as default. To enable HTML in emails, add these codes inside functions.php:', 'appointments');?></p>

	<pre>
	function app_modify_headers( $headers ) {
		return str_replace( 'text/plain', 'text/html', $headers );
	}
	add_filter( 'app_message_headers', 'app_modify_headers' );
	</pre>
	</li>

	<li class="faq-answer" id='q45'>
		<p> <?php _e('The following example lets you select services with durations up to 48*10=480 minutes. Add these codes inside functions.php:', 'appointments');?></p>

	<pre>
	function increase_service_selections( $n ){
		return 48;
	}
	add_filter( 'app_selectable_durations', 'increase_service_selections' );
	</pre>
	</li>


	<li class="faq-answer" id='q47'>
		<p><?php _e('Use this sample and modify as required:', 'appointments');?></p>

	<pre>
	function convert_to_hour( $text, $duration ) {
		if ( $duration < 60 ) return $text;
		$hours = floor($duration/60);
		if ( $hours > 1 ) $hour_text = ' hours ';
		else $hour_text = ' hour ';
		$mins = $duration - $hours *60;
		if ( $mins ) $min_text = $mins . ' minutes';
		else $min_text = '';
		return $hours . $hour_text . $min_text;
	}
	 add_filter('app_confirmation_lasts', 'convert_to_hour', 10, 2);
	</pre>
	</li>
</ul>


<script>
	jQuery( document).ready( function( $ ) {
		var selectedQuestion = '';

		function selectQuestion() {
			var q = $( '#' + $(this).val() );
			if ( selectedQuestion.length ) {
				selectedQuestion.hide();
			}
			q.show();
			selectedQuestion = q;
		}

		var faqAnswers = $('.faq-answer');
		var faqIndex = $('#faq-index');
		faqAnswers.hide();
		faqIndex.hide();

		var indexSelector = $('<select/>')
			.attr( 'id', 'question-selector' )
			.addClass( 'widefat' );
		var questions = faqIndex.find( 'li' );
		var advancedGroup = false;
		questions.each( function () {
			var self = $(this);
			var answer = self.data('answer');
			var text = self.text();
			var option;

			if ( answer === 39 ) {
				advancedGroup = $( '<optgroup />' )
					.attr( 'label', "<?php _e( 'Advanced: This part of FAQ requires some knowledge about HTML, PHP and/or WordPress coding.', 'appointments' ); ?>" );

				indexSelector.append( advancedGroup );
			}

			if ( answer !== '' && text !== '' ) {
				option = $( '<option/>' )
					.val( 'q' + answer )
					.text( text );
				if ( advancedGroup ) {
					advancedGroup.append( option );
				}
				else {
					indexSelector.append( option );
				}

			}

		});

		faqIndex.after( indexSelector );
		indexSelector.before(
			$('<label />')
				.attr( 'for', 'question-selector' )
				.text( "<?php _e( 'Select a question', 'appointments' ); ?>" )
				.addClass( 'screen-reader-text' )
		);

		indexSelector.change( selectQuestion );
	});
</script>
