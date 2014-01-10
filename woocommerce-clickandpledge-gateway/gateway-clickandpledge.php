<?php
/*
Plugin Name: WooCommerce Click & Pledge Gateway
Plugin URI: http://manual.clickandpledge.com/
Description: With Click & Pledge, Accept all major credit cards directly on your WooCommerce website with a seamless and secure checkout experience.. <a href="http://manual.clickandpledge.com/" target="_blank">Click Here</a> to get a Click & Pledge account.
Version: 1.0
Author: Click & Pledge
Author URI: http://www.clickandpledge.com
*/

add_action('plugins_loaded', 'woocommerce_clickandpledge_init', 0);

function woocommerce_clickandpledge_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }

	require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/classes/clickandpledge-request.php');
	//require_once(WP_PLUGIN_DIR . "/" . plugin_basename( dirname(__FILE__)) . '/classes/clickandpledge-response.php');
	
	/**
 	* Gateway class
 	**/
	class WC_Gateway_ClickandPledge extends WC_Payment_Gateway {
		var $AccountID;
		var $AccountGuid;
		var $liveurl = 'http://manual.clickandpledge.com/';
		var $testurl = 'http://manual.clickandpledge.com/';
		var $testmode;
	
		function __construct() { 
			
			$this->id				= 'clickandpledge';
			$this->method_title 	= __('Click & Pledge', 'woothemes');
			$this->icon 			= WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . '/images/CP_Secured.jpg';
						
			// Load the form fields
			$this->init_form_fields();
			
			// Load the settings.
			$this->init_settings();
			
			// Get setting values
			$this->title 			= $this->settings['title'];
			$this->description 		= $this->settings['description'];
			$this->enabled 			= $this->settings['enabled'];
			$this->AccountID 	= $this->settings['AccountID'];
			$this->AccountGuid 	= $this->settings['AccountGuid'];
			$this->testmode 		= $this->settings['testmode'];
			$this->Periodicity = array();
			$this->RecurringMethod = array();
			$this->available_cards = array();
			
			//Available Credit Cards
			if((isset($this->settings['Visa']) && ($this->settings['Visa'] == 'yes') )){
				$this->available_cards['Visa']		= 'Visa';
			}
			if((isset($this->settings['American_Express']) && ($this->settings['American_Express'] == 'yes') )){
				$this->available_cards['American Express']		= 'American Express';
			}
			if((isset($this->settings['Discover']) && ($this->settings['Discover'] == 'yes') )){
				$this->available_cards['Discover']		= 'Discover';
			}
			if((isset($this->settings['MasterCard']) && ($this->settings['MasterCard'] == 'yes') )){
				$this->available_cards['MasterCard']		= 'MasterCard';
			}
			
			if(!count($this->available_cards)) {
				$this->available_cards['Visa']		= 'Visa';
				$this->available_cards['American Express']	= 'American Express';
				$this->available_cards['Discover']	= 'Discover';
				$this->available_cards['MasterCard']	= 'MasterCard';
			}
			
			$this->isRecurring 		= (isset($this->settings['isRecurring']) && ($this->settings['isRecurring'] == 'yes')) ? true : false;
			
			if((isset($this->settings['week']) && ($this->settings['week'] == 'yes') )) {
				$this->Periodicity['week']		= 'week';
			}
			if((isset($this->settings['2_Weeks']) && ($this->settings['2_Weeks'] == 'yes'))) {
				$this->Periodicity['2 Weeks']		= '2 Weeks';
			}
			if((isset($this->settings['Month']) && ($this->settings['Month'] == 'yes'))) {
				$this->Periodicity['Month']		= 'Month';
			}
			if((isset($this->settings['2_Months']) && ($this->settings['2_Months'] == 'yes'))) {
				$this->Periodicity['2 Months']		= '2 Months';
			}
			if((isset($this->settings['Quarter']) && ($this->settings['Quarter'] == 'yes') )) {
				$this->Periodicity['Quarter']		= 'Quarter';
			}
			if((isset($this->settings['6_Months']) && ($this->settings['6_Months'] == 'yes') )){
				$this->Periodicity['6 Months']		= '6 Months';
			}
			if((isset($this->settings['Year']) && ($this->settings['Year'] == 'yes') )){
				$this->Periodicity['Year']		= 'Year';
			}
			
			if(!count($this->Periodicity)) { //If Nothing select in admin
				$this->Periodicity['week']		= 'week';
				$this->Periodicity['2 Weeks']	= '2 Weeks';
				$this->Periodicity['Month']		= 'Month';
				$this->Periodicity['2 Months']	= '2 Months';
				$this->Periodicity['Quarter']	= 'Quarter';
				$this->Periodicity['6 Months']	= '6 Months';
				$this->Periodicity['Year']		= 'Year';
				
			}
			//Recurring Methods
			if((isset($this->settings['Installment']) && ($this->settings['Installment'] == 'yes') )){
				$this->RecurringMethod['Installment']		= 'Installment';
			}
			if((isset($this->settings['Subscription']) && ($this->settings['Subscription'] == 'yes') )){
				$this->RecurringMethod['Subscription']		= 'Subscription';
			}
			if(!count($this->RecurringMethod)) {	//If Nothing select in admin
				$this->RecurringMethod['Installment']	= 'Installment';
				$this->RecurringMethod['Subscription']	= 'Subscription';
			}
			$this->indefinite 		= (isset($this->settings['indefinite']) && $this->settings['indefinite'] == 'yes') ? true : false;
			//print_r($this->settings);
			// Hooks
			add_action( 'admin_notices', array( &$this, 'ssl_check') );			
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		
		/**
	 	* Check if SSL is enabled and notify the user if SSL is not enabled
	 	**/
		function ssl_check() {
	     
		if (get_option('woocommerce_force_ssl_checkout')=='no' && $this->enabled=='yes') :
		
			echo '<div class="error"><p>'.sprintf(__('Click & Pledge is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout is not secure! Please enable SSL and ensure your server has a valid SSL certificate - Click & Pledge will only work in test mode.', 'woothemes'), admin_url('admin.php?page=woocommerce')).'</p></div>';
		
		endif;
		}
		
		/**
	     * Initialize Gateway Settings Form Fields
	     */
	    function init_form_fields() {
	    
	    	$this->form_fields = array(
				'title' => array(
								'title' => __( 'Title', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
								'default' => __( 'Credit Card / Debit Card', 'woothemes' )
							), 
				'enabled' => array(
								'title' => __( 'Enable/Disable', 'woothemes' ), 
								'label' => __( 'Enable Click & Pledge', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => '', 
								'default' => 'no'
							), 
				'description' => array(
								'title' => __( 'Description', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'This controls the description which the user sees during checkout.', 'woothemes' ), 
								'default' => 'Pay with your Credit Card.'
							),  
				'testmode' => array(
								'title' => __( 'Click & Pledge Test', 'woothemes' ), 
								'label' => __( 'Enable Click & Pledge Test', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Process transactions in Test Mode via the Click & Pledge Test account (www.clickandpledge.com).', 'woothemes' ), 
								'default' => 'no'
							), 
				'AccountID' => array(
								'title' => __( 'Account ID', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Get your "Account ID" from Click & Pledge.', 'woothemes' ), 
								'default' => ''
							), 
				'AccountGuid' => array(
								'title' => __( 'API Account GUID', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Get your "API Account GUID" from Click & Pledge.', 'woothemes' ), 
								'default' => '',
								'maxlength' => 200
							),
				
				'AcceptedCreditCards' => array(
								'title' => __( 'Accepted Credit Cards', 'woothemes' ), 
								'type' => 'text',
								'css'     => 'display:none;',
								'disabled' => true,
								'description' => __( '', 'woothemes' ), 
							),
				'Visa' => array(
								'title' => __( 'Visa', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'American_Express' => array(
								'title' => __( 'American Express', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'Discover' => array(
								'title' => __( 'Discover', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),			
				'MasterCard' => array(
								'title' => __( 'MasterCard', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
							
				'OrganizationInformation' => array(
								'title' => __( 'Organization information to be used.', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'Organization information to be used. Maximum: 200 characters', 'woothemes' ), 
								'default' => '',
								'maxlength' => 200
							),
				'ThankYouMessage' => array(
								'title' => __( 'The Thank You message appearing after the salutation.', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'The Thank You message appearing after the salutation. Maximum: 500 characters', 'woothemes' ), 
								'default' => '',
								'maxlength' => 500
							),
				'TermsCondition' => array(
								'title' => __( 'The terms & conditions to be added at the bottom of the receipt.', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'The terms & conditions to be added at the bottom of the receipt. Maximum: 1500 characters', 'woothemes' ), 
								'default' => '',
								'maxlength' => 1500
							),
				'isRecurring' => array(
								'title' => __( 'Is Recurring?', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'It will enables the Recurring payments for transactions.', 'woothemes' ), 
								'default' => false,
							),
				
				'Periodicity' => array(
								'title' => __( 'Supported recurring periods', 'woothemes' ), 
								'type' => 'text',
								'css'     => 'display:none;',
								'disabled' => true,
								'description' => __( '', 'woothemes' ), 
							),
				'week' => array(
								'title' => __( 'week', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'2_Weeks' => array(
								'title' => __( '2 Weeks', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'Month' => array(
								'title' => __( 'Month', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'2_Months' => array(
								'title' => __( '2 Months', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'Quarter' => array(
								'title' => __( 'Quarter', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'6_Months' => array(
								'title' => __( '6 Months', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'Year' => array(
								'title' => __( 'Year', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
							
				'RecurringMethod' => array(
								'title' => __( 'Recurring Method', 'woothemes' ), 
								'type' => 'text',
								'css'     => 'display:none;',
								'disabled' => true,
								'description' => __( '', 'woothemes' ), 
							),
				'Installment' => array(
								'title' => __( 'Installment', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Installment (example: Split $1000 into 10 payments of $100 each)', 'woothemes' ), 
								'default' => false,
							),
				'Subscription' => array(
								'title' => __( 'Subscription', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Subscription (example: Pay $10 every month for 20 times)', 'woothemes' ), 
								'default' => false,
							),
				
				'indefinite' => array(
								'title' => __( 'Enable Indefinite Recurring', 'woothemes' ), 
								'label' => __( 'Enable Indefinite Recurring', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Process transactions indefinitely.', 'woothemes' ), 
								'default' => 'no'
							), 
				);
	    }
	    
	    /**
		 * Admin Panel Options 
		 * - Options for bits like 'title' and availability on a country-by-country basis
		 */
		function admin_options() {
	    	?>
	    	<h3><?php _e( 'Click & Pledge', 'woothemes' ); ?></h3>
	    	<p><?php _e( 'Click & Pledge works by adding credit card fields on the checkout and then sending the details to Click & Pledge for verification.', 'woothemes' ); ?></p>
	    	<table class="form-table">
	    		<?php $this->generate_settings_html(); ?>
			</table><!--/.form-table-->
			<script>
			
			jQuery(document).ready(function(){
				if(jQuery('#woocommerce_clickandpledge_isRecurring').is(':checked')) {
					jQuery('#woocommerce_clickandpledge_Periodicity').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_week').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_2_Weeks').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Month').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_2_Months').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Quarter').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_6_Months').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Year').closest('tr').show();
						
					jQuery('#woocommerce_clickandpledge_RecurringMethod').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Installment').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Subscription').closest('tr').show();
						
					jQuery('#woocommerce_clickandpledge_indefinite').closest('tr').show();
				} else {
					jQuery('#woocommerce_clickandpledge_Periodicity').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_week').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_2_Weeks').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Month').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_2_Months').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Quarter').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_6_Months').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Year').closest('tr').hide();
						
					jQuery('#woocommerce_clickandpledge_RecurringMethod').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Installment').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Subscription').closest('tr').hide();
						
					jQuery('#woocommerce_clickandpledge_indefinite').closest('tr').hide();
				}
				
				jQuery('#woocommerce_clickandpledge_isRecurring').click(function(){
					if(jQuery('#woocommerce_clickandpledge_isRecurring').is(':checked')) {
						jQuery('#woocommerce_clickandpledge_Periodicity').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_week').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_2_Weeks').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Month').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_2_Months').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Quarter').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_6_Months').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Year').closest('tr').show();
						
						jQuery('#woocommerce_clickandpledge_RecurringMethod').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Installment').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Subscription').closest('tr').show();
						
						jQuery('#woocommerce_clickandpledge_indefinite').closest('tr').show();
					} else {
						jQuery('#woocommerce_clickandpledge_Periodicity').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_week').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_2_Weeks').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Month').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_2_Months').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Quarter').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_6_Months').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Year').closest('tr').hide();
						
						jQuery('#woocommerce_clickandpledge_RecurringMethod').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Installment').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Subscription').closest('tr').hide();
												
						jQuery('#woocommerce_clickandpledge_indefinite').closest('tr').hide();
					}
				});
			});
			</script>
	    	<?php
	    }
				
		/**
	     * Get the users country either from their order, or from their customer data
	     */
		function get_country_code() {
			global $woocommerce;
			
			if(isset($_GET['order_id'])) {
			
				$order = new WC_Order($_GET['order_id']);
	
				return $order->billing_country;
				
			} elseif ($woocommerce->customer->get_country()) {
				
				return $woocommerce->customer->get_country();
			
			}
			
			return NULL;
		}
	
		/**
	     * Payment form on checkout page
	     */
		function payment_fields() {
			$user_country = $this->get_country_code();
			
			if(empty($user_country)) :
				echo __('Select a country to see the payment form', 'woothemes');
				return;
			endif;
						
			//$available_cards = $this->avaiable_countries[$user_country];
			$available_cards = $this->available_cards;
			?>
			<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE/SANDBOX ENABLED', 'woothemes'); ?></p><?php endif; ?>
			<?php if ($this->description) : ?><p><?php echo $this->description; ?></p><?php endif; ?>
			<fieldset>
				<p class="">
					<label for="clickandpledge_cart_number"><?php echo __("Name on Card", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text required" name="clickandpledge_name_on_card" placeholder="Name on Card"/>
				</p>
				<div class="clear"></div>
				
				<p class="form-row form-row-first">
					<label for="clickandpledge_cart_number"><?php echo __("Credit Card number", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text required" name="clickandpledge_card_number" placeholder="Credit Card number"/>
				</p>
				<p class="form-row form-row-last">
					<label for="clickandpledge_cart_type"><?php echo __("Card type", 'woocommerce') ?> <span class="required">*</span></label>
					<select id="clickandpledge_card_type" name="clickandpledge_card_type" onchange="toggle_csc();">
						<?php foreach ($available_cards as $card) : ?>
									<option value="<?php echo $card ?>"><?php echo $card; ?></options>
						<?php endforeach; ?>
					</select>
				</p>
				<div class="clear"></div>
				
				<p class="form-row form-row-first">
					<label for="cc-expire-month"><?php echo __("Expiration Date", 'woocommerce') ?> <span class="required">*</span></label>
					<select name="clickandpledge_card_expiration_month" id="cc-expire-month">
						<option value=""><?php _e('Month', 'woocommerce') ?></option>
						<?php
							$months = array();
							for ($i = 1; $i <= 12; $i++) {
							    $timestamp = mktime(0, 0, 0, $i, 1);
							    $months[date('m', $timestamp)] = date('F', $timestamp);
							}
							foreach ($months as $num => $name) {
					            printf('<option value="%s">%s</option>', $num, $name);
					        }
					        
						?>
					</select>
					<select name="clickandpledge_card_expiration_year" id="cc-expire-year">
						<option value=""><?php _e('Year', 'woocommerce') ?></option>
						<?php
							$years = array();
							for ($i = date('Y'); $i <= date('Y') + 15; $i++) {
							    printf('<option value="%u">%u</option>', $i, $i);
							}
						?>
					</select>
				</p>
				<p class="form-row form-row-last">
					<label for="clickandpledge_card_csc"><?php _e("Card Verification (CVV)", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" id="clickandpledge_card_csc" name="clickandpledge_card_csc" maxlength="4" style="width:59px" placeholder="cvv"/>
					<script>
					jQuery('#clickandpledge_card_csc').keypress(function(e) {
						var a = [];
						var k = e.which;

						for (i = 48; i < 58; i++)
							a.push(i);

						if (!(a.indexOf(k)>=0))
							e.preventDefault();
					});
					</script>
					<span class="help clickandpledge_card_csc_description"></span>
				</p>
				<div class="clear"></div>
				
				<?php if($this->isRecurring) { ?>
				<p class="">
					<label for="clickandpledge_cart_type">
					<input type="checkbox" name="clickandpledge_isRecurring" id="clickandpledge_isRecurring">&nbsp;
					<?php echo __("I want to contribute this amount every", 'woocommerce') ?> </label>
				</p>
				<div class="clear"></div>
				
				<p class="" id="clickandpledge_Periodicity_p">					
					<select id="clickandpledge_Periodicity" name="clickandpledge_Periodicity">
						<?php foreach ($this->Periodicity as $p) : ?>
									<option value="<?php echo $p ?>"><?php echo $p; ?></options>
						<?php endforeach; ?>
					</select>
					&nbsp;
					<input type="text" class="input-text" id="clickandpledge_Installment" name="clickandpledge_Installment" maxlength="3" style="width:49px" /><span class="required" id="clickandpledge_Installment_req">*</span>
					<script>
					jQuery('#clickandpledge_Installment').keypress(function(e) {
						var a = [];
						var k = e.which;

						for (i = 48; i < 58; i++)
							a.push(i);

						if (!(a.indexOf(k)>=0))
							e.preventDefault();
					});
					</script>
					<?php
					if(isset($this->settings['indefinite']) && $this->settings['indefinite'] == 'yes') {
					?>
					&nbsp;
					<input type="checkbox" name="clickandpledge_indefinite" id="clickandpledge_indefinite">In definite Recurring&nbsp;
					<?php } ?>
				</p>
				<div class="clear"></div>
				
				<?php if(count($this->RecurringMethod) > 1) { ?>
				<p class="" id="clickandpledge_RecurringMethod_p">
					<label for="clickandpledge_card_csc"><?php _e("Recurring Method", 'woocommerce') ?> <span class="required">*</span></label>
					<select id="clickandpledge_RecurringMethod" name="clickandpledge_RecurringMethod">
						<?php foreach ($this->RecurringMethod as $r) : ?>
									<option value="<?php echo $r ?>"><?php echo $r; ?></options>
						<?php endforeach; ?>			
					</select>
				</p>
				<?php } else {
				//print_r($this->RecurringMethod);
				?>
				<?php foreach ($this->RecurringMethod as $r) : ?>
					<input type="hidden" name="clickandpledge_RecurringMethod" value="<?php echo $r;?>">
				<?php endforeach; ?>	
				<?php
				} ?>
				
				<div class="clear"></div>
				
				<script type="text/javascript">
				if(jQuery('#clickandpledge_isRecurring').is(':checked')) {
						jQuery('#clickandpledge_Periodicity_p').show();
						jQuery('#clickandpledge_RecurringMethod_p').show();
					} else {
						jQuery('#clickandpledge_Periodicity_p').hide();
						jQuery('#clickandpledge_RecurringMethod_p').hide();
					}
				jQuery('#clickandpledge_isRecurring').click(function(){
					if(jQuery('#clickandpledge_isRecurring').is(':checked')) {
						jQuery('#clickandpledge_Periodicity_p').show();
						jQuery('#clickandpledge_RecurringMethod_p').show();
					} else {
						jQuery('#clickandpledge_Periodicity_p').hide();
						jQuery('#clickandpledge_RecurringMethod_p').hide();
					}
				});
				
				jQuery('#clickandpledge_indefinite').click(function(){
					if(jQuery('#clickandpledge_indefinite').is(':checked')) {
						jQuery('#clickandpledge_Installment').val('');						
						jQuery('#clickandpledge_Installment_req').html('');
						jQuery('#clickandpledge_Installment').attr('readonly', true);
					} else {
						jQuery('#clickandpledge_Installment_req').html('*');
						jQuery('#clickandpledge_Installment').attr('readonly', false);
					}
				});
				</script>
				
				<?php } ?>
			</fieldset>
			<script type="text/javascript">				
				function toggle_csc() {
					var card_type = jQuery("#clickandpledge_card_type").val();
					var csc = jQuery("#clickandpledge_card_csc").parent();
			
					if(card_type == "Visa" || card_type == "MasterCard" || card_type == "Discover" || card_type == "American Express" ) {
						csc.fadeIn("fast");
					} else {
						csc.fadeOut("fast");
					}
					
					if(card_type == "Visa" || card_type == "MasterCard" || card_type == "Discover") {
						jQuery('.clickandpledge_card_csc_description').text("<?php _e('3 digits usually found on the back of the card.', 'woocommerce'); ?>");
					} else if ( cardType == "American Express" ) {
						jQuery('.clickandpledge_card_csc_description').text("<?php _e('4 digits usually found on the front of the card.', 'woocommerce'); ?>");
					} else {
						jQuery('.clickandpledge_card_csc_description').text('');
					}
				}
			
				jQuery("#clickandpledge_card_type").change(function(){
					toggle_csc();
				}).change();
			
			</script>
			<?php
		}
		
		/**
	     * Process the payment
	     */
		function process_payment($order_id) {
			global $woocommerce;
			
			$order = new WC_Order( $order_id );
				
			// Validate plugin settings
			
			if (!$this->validate_settings()) :
				$cancelNote = __('Order was cancelled due to invalid settings (check your API credentials and make sure your currency is supported).', 'woothemes');
				$order->add_order_note( $cancelNote );
		
				$woocommerce->add_error(__('Payment was rejected due to configuration error.', 'woothemes'));
				return false;
			endif;
	
			// Send request to clickandpledge
			try {
				$url = $this->liveurl;
				if ($this->testmode == 'yes') :
					$url = $this->testurl;
				endif;
	
				$request = new clickandpledge_request($url);
				
				$posted_settings = array();
				$posted_settings['AccountID'] = $this->AccountID;
				$posted_settings['AccountGuid'] = $this->AccountGuid;				
				$posted_settings['Total'] = $order->order_total;
				$posted_settings['OrderMode'] = $this->testmode;
				
				$response = $request->send($posted_settings, $_POST, $order);
			
			} catch(Exception $e) {
				$woocommerce->add_error(__('There was a connection error', 'woothemes') . ': "' . $e->getMessage() . '"');
				return;
			}
	
			if ($response['status'] == 'Success') :
				$order->add_order_note( __('Click & Pledge payment completed', 'woothemes') . ' (Transaction ID: ' . $response['TransactionNumber'] . ')' );
				$order->payment_complete();
	
				$woocommerce->cart->empty_cart();
					
				// Return thank you page redirect
				return array(
					'result' 	=> 'success',
					'redirect'	=> add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))))
				);
			else :
				$cancelNote = __('Click & Pledge payment failed', 'woothemes') . ' (Transaction ID: ' . $response['TransactionNumber'] . '). ' . __('Payment was rejected due to an error', 'woothemes') . ': "' . $response['error'] . '". ';
	
				$order->add_order_note( $cancelNote );
				
				$woocommerce->add_error(__('Payment error', 'woothemes') . ': ' . $response['error'] . '('.$response['ResultCode'].')');
			endif;
		}
	
	function cc_check($number) {

	  // Strip any non-digits (useful for credit card numbers with spaces and hyphens)
	  $number=preg_replace('/\D/', '', $number);

	  // Set the string length and parity
	  $number_length=strlen($number);
	  $parity=$number_length % 2;

	  // Loop through each digit and do the maths
	  $total=0;
	  for ($i=0; $i<$number_length; $i++) {
		$digit=$number[$i];
		// Multiply alternate digits by two
		if ($i % 2 == $parity) {
		  $digit*=2;
		  // If the sum is two digits, add them together (in effect)
		  if ($digit > 9) {
			$digit-=9;
		  }
		}
		// Total up the digits
		$total+=$digit;
	  }

	  // If the total mod 10 equals 0, the number is valid
	  return ($total % 10 == 0) ? TRUE : FALSE;

	}
		/**
	     * Validate the payment form
	     */
		function validate_fields() {
			global $woocommerce;
												
			$name_on_card 	= isset($_POST['clickandpledge_name_on_card']) ? $_POST['clickandpledge_name_on_card'] : '';
			$billing_country 	= isset($_POST['billing_country']) ? $_POST['billing_country'] : '';
			$card_type 			= isset($_POST['clickandpledge_card_type']) ? $_POST['clickandpledge_card_type'] : '';
			$card_number 		= isset($_POST['clickandpledge_card_number']) ? $_POST['clickandpledge_card_number'] : '';
			$card_csc 			= isset($_POST['clickandpledge_card_csc']) ? $_POST['clickandpledge_card_csc'] : '';
			$card_exp_month		= isset($_POST['clickandpledge_card_expiration_month']) ? $_POST['clickandpledge_card_expiration_month'] : '';
			$card_exp_year 		= isset($_POST['clickandpledge_card_expiration_year']) ? $_POST['clickandpledge_card_expiration_year'] : '';
			$isRecurring = 	isset($_POST['clickandpledge_isRecurring']) ? $_POST['clickandpledge_isRecurring'] : '';
				
			// Check card security code
			if(empty($name_on_card)) {
				$woocommerce->add_error(__('Name on Card (wrong length)', 'woothemes'));
				return false;
			}
			if(empty($card_number)) {
				$woocommerce->add_error(__('Credit Card Number is invalid (wrong length)', 'woothemes'));
				return false;
			}
			if(strlen($card_number) < 13) {
				$woocommerce->add_error(__('Credit Card Number is invalid (wrong length)', 'woothemes'));
				return false;
			}
			if(strlen($card_number) > 19) {
				$woocommerce->add_error(__('Credit Card Number is invalid (wrong length)', 'woothemes'));
				return false;
			}
			if(empty($card_number)) {
				$woocommerce->add_error(__('Credit Card Number is invalid (wrong length)', 'woothemes'));
				return false;
			}
			if(!$this->cc_check($card_number)) {
				$woocommerce->add_error(__('Credit Card Number is invalid', 'woothemes'));
				return false;
			}
			
			if(!ctype_digit($card_csc)) {
				$woocommerce->add_error(__('Card Verification (CVV) is invalid (only digits are allowed)', 'woothemes'));
				return false;
			}
	
			if((strlen($card_csc) != 3 && in_array($card_type, array('Visa', 'MasterCard', 'Discover'))) || (strlen($card_csc) != 4 && $card_type == 'American Express')) {
				$woocommerce->add_error(__('Card Verification (CVV) is invalid (wrong length)', 'woothemes'));
				return false;
			}
	
			// Check card expiration data
			if(!ctype_digit($card_exp_month) || !ctype_digit($card_exp_year) ||
				 $card_exp_month > 12 ||
				 $card_exp_month < 1 ||
				 $card_exp_year < date('Y') ||
				 $card_exp_year > date('Y') + 20
			) {
				$woocommerce->add_error(__('Card Expiration Date is invalid', 'woothemes'));
				return false;
			}
	
			if($isRecurring) {
				if(empty($_POST['clickandpledge_Periodicity'])) {
						$woocommerce->add_error(__('Periodicity is invalid. Please select', 'woothemes'));
						return false;
					}
					
				if(!$_POST['clickandpledge_indefinite']) {
					if(empty($_POST['clickandpledge_Installment'])) {
						$woocommerce->add_error(__('Installment is invalid (wrong length)', 'woothemes'));
						return false;
					}
					if(!ctype_digit($_POST['clickandpledge_Installment'])) {
						$woocommerce->add_error(__('Installment is invalid (only digits are allowed. Allowed values 2 through 999)', 'woothemes'));
						return false;
					}
					if(strlen($_POST['clickandpledge_Installment']) > 3) {
						$woocommerce->add_error(__('Installment is invalid (Allowed values 2 through 999)', 'woothemes'));
						return false;
					}
				}
			}
	
			return true;
		}
		
		/**
	     * Validate plugin settings
	     */
		function validate_settings() {
			$currency = get_option('woocommerce_currency');
	
			if (!in_array($currency, array('USD', 'EUR', 'CAD', 'GBP'))) {
				return false;
			}
	
			if (!$this->AccountID || !$this->AccountGuid) {
				return false;
			}
	
			return true;
		}
		
		/**
	     * Get user's IP address
	     */
		function get_user_ip() {			
			 $ipaddress = '';
			 if ($_SERVER['HTTP_CLIENT_IP'])
				 $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
			 else if($_SERVER['HTTP_X_FORWARDED_FOR'])
				 $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
			 else if($_SERVER['HTTP_X_FORWARDED'])
				 $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
			 else if($_SERVER['HTTP_FORWARDED_FOR'])
				 $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
			 else if($_SERVER['HTTP_FORWARDED'])
				 $ipaddress = $_SERVER['HTTP_FORWARDED'];
			 else
				 $ipaddress = $_SERVER['REMOTE_ADDR'];

			 return $ipaddress; 
		}

	} // end woocommerce_clickandpledge
	
	/**
 	* Add the Gateway to WooCommerce
 	**/
	function add_clickandpledge_gateway($methods) {
		$methods[] = 'WC_Gateway_ClickandPledge';
		return $methods;
	}
	
	add_filter('woocommerce_payment_gateways', 'add_clickandpledge_gateway' );
} 
