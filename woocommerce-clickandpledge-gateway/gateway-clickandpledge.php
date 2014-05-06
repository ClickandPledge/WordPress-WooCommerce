<?php
/*
Plugin Name: WooCommerce Click & Pledge Gateway
Plugin URI: http://manual.clickandpledge.com/
Description: With Click & Pledge, Accept all major credit cards directly on your WooCommerce website with a seamless and secure checkout experience.<a href="http://manual.clickandpledge.com/" target="_blank">Click Here</a> to get a Click & Pledge account.
Version: 1.2
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
		var $maxrecurrings_Installment;
		var $maxrecurrings_Subscription;
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
			$this->maxrecurrings_Installment 	= $this->settings['maxrecurrings_Installment'];
			$this->maxrecurrings_Subscription 	= $this->settings['maxrecurrings_Subscription'];
			
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
			if((isset($this->settings['JCB']) && ($this->settings['JCB'] == 'yes') )){
				$this->available_cards['JCB']		= 'JCB';
			}
			
			
			if(!count($this->available_cards)) {
				$this->available_cards['Visa']		= 'Visa';
				$this->available_cards['American Express']	= 'American Express';
				$this->available_cards['Discover']	= 'Discover';
				$this->available_cards['MasterCard']	= 'MasterCard';
				$this->available_cards['JCB']	= 'JCB';
			}
			
			$this->isRecurring 		= (isset($this->settings['isRecurring']) && ($this->settings['isRecurring'] == '1')) ? true : false;
			
			if((isset($this->settings['Week']) && ($this->settings['Week'] == 'yes') )) {
				$this->Periodicity['Week']		= 'Week';
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
				$this->Periodicity['Week']		= 'Week';
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
			$paddingleft = 80;
	    	$this->form_fields = array(
				'enabled' => array(
								'title' => __( 'Status', 'woothemes' ), 
								'label' => __( 'Enable Click & Pledge', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => '', 
								'default' => true,
							), 
							
				'testmode' => array(
								'title' => __( 'API Mode', 'woothemes' ), 
								'label' => __( 'Enable Click & Pledge Test', 'woothemes' ), 
								'type' => 'select', 
								'description' => __( 'Process transactions in Test Mode via the Click & Pledge Test account (www.clickandpledge.com).', 'woothemes' ), 
								'default' => 'test',
								'options'     => array(
									  'yes'	=> 'Test Mode',
									  'no'	=> 'Live Mode',
								),
							), 
							
				'title' => array(
								'title' => __( 'Title', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'This controls the title which the user sees during checkout.', 'woothemes' ), 
								'default' => __( 'Credit Card', 'woothemes' )
							), 
				
				'description' => array(
								'title' => __( 'Description', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'The payment description.', 'woothemes' ), 
								'default' => 'Pay with your Credit Card.'
							),  
				
				'AccountID' => array(
								'title' => __( 'C&P Account ID', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Get your "Account ID" from Click & Pledge. [Portal > Account Info > API Information].', 'woothemes' ), 
								'default' => '',
								'class' => 'required',
							), 
				'AccountGuid' => array(
								'title' => __( 'C&P API Account GUID', 'woothemes' ), 
								'type' => 'text', 
								'description' => __( 'Get your "API Account GUID" from Click & Pledge [Portal > Account Info > API Information].', 'woothemes' ), 
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
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Visa</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'American_Express' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">American Express</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'Discover' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Discover</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),			
				'MasterCard' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">MasterCard</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'JCB' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">JCB</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
							
				
				'cnp_email_customer' => array(
								'title' => __( 'Send Receipt to Patron', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => true,
							),
							
				'OrganizationInformation' => array(
								'title' => __( 'Organization information', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'Maximum: 1500 characters, the following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;.  You have <span id="OrganizationInformation_countdown">1500</span> characters left.', 'woothemes' ), 
								'default' => '',
								'maxlength' => 1500,
							),
				'ThankYouMessage' => array(
								'title' => __( 'Thank You message', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'Maximum: 500 characters, the following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;. You have <span id="ThankYouMessage_countdown">500</span> characters left.', 'woothemes' ), 
								'default' => '',
								'maxlength' => 500
							),
				'TermsCondition' => array(
								'title' => __( 'Terms & Conditions', 'woothemes' ), 
								'type' => 'textarea', 
								'description' => __( 'To be added at the bottom of the receipt. Typically the text provides proof that the patron has read & agreed to the terms & conditions. The following HTML tags are allowed:
&lt;P&gt;&lt;/P&gt;&lt;BR /&gt;&lt;OL&gt;&lt;/OL&gt;&lt;LI&gt;&lt;/LI&gt;&lt;UL&gt;&lt;/UL&gt;. <br>Maximum: 1500 characters, You have <span id="TermsCondition_countdown">1500</span> characters left.', 'woothemes' ), 
								'default' => '',
								'maxlength' => 1500
							),
				'isRecurring' => array(
								'title' => __( 'Recurring Transaction', 'woothemes' ), 
								'type' => 'select', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
								'options'     => array(
									  '0'	=> 'Disable',
									  '1'	=> 'Enable',
								),
							),
				
				'RecurringLabel' => array(
								'title' => __( 'Label', 'woothemes' ), 
								'type' => 'text',
								'disabled' => false,
								'description' => __( '', 'woothemes' ), 
								'default' => 'Set this as a recurring payment',
								'css' => 'maxlength:200;',
							),
							
				'Periodicity' => array(
								'title' => __( 'Periods', 'woothemes' ), 
								'type' => 'text',
								'css'     => 'display:none;',
								'disabled' => true,
								'description' => __( 'Supported recurring periods', 'woothemes' ), 
							),
				'Week' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Week</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'2_Weeks' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">2 Weeks</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'Month' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Month</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'2_Months' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">2 Months</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'Quarter' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Quarter</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'6_Months' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">6 Months</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( '', 'woothemes' ), 
								'default' => false,
							),
				'Year' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Year</span>', 'woothemes' ), 
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
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Installment</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Installment (example: Split $1000 into 10 payments of $100 each)', 'woothemes' ), 
								'default' => false,
							),
				'maxrecurrings_Installment' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Installment Max. Recurrings Allowed</span>', 'woothemes' ), 
								'label' => __( 'Restrict user to enter recurrings', 'woothemes' ), 
								'type' => 'text',
								'description' => __( 'Maximum number of payments allowed , range is 2-998.', 'woothemes' ),
								'style' => 'maxlength:3',
							), 
							
				'Subscription' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Subscription</span>', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Subscription (example: Pay $10 every month for 20 times)', 'woothemes' ), 
								'default' => false,
							),
				'maxrecurrings_Subscription' => array(
								'title' => __( '<span style="padding-left:'.$paddingleft.'px;">Subscription Max. Recurrings Allowed</span>', 'woothemes' ), 
								'label' => __( 'Restrict user to enter recurrings', 'woothemes' ), 
								'type' => 'text',
								'description' => __( 'Maximum number of payments allowed , range is 2-999.', 'woothemes' ),
								'style' => 'maxlength:3',
							), 
				
				'indefinite' => array(
								'title' => __( 'Enable Indefinite Recurring', 'woothemes' ), 
								'label' => __( 'Enable Indefinite Recurring', 'woothemes' ), 
								'type' => 'checkbox', 
								'description' => __( 'Recurring transactions will process until cancelled.', 'woothemes' ), 
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
				
				limitText(jQuery('#woocommerce_clickandpledge_OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);
				
				limitText(jQuery('#woocommerce_clickandpledge_ThankYouMessage'),jQuery('#ThankYouMessage_countdown'),500);
				
				limitText(jQuery('#woocommerce_clickandpledge_TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				
				jQuery( "form" ).submit(function( event ) {
					if(jQuery('#woocommerce_clickandpledge_title').val() == '')
					{
						alert('Please enter title');
						jQuery('#woocommerce_clickandpledge_title').focus();
						return false;
					}
					
					if(jQuery('#woocommerce_clickandpledge_AccountID').val() == '')
					{
						alert('Please enter AccountID');
						jQuery('#woocommerce_clickandpledge_AccountID').focus();
						return false;
					}
					
					if(jQuery('#woocommerce_clickandpledge_AccountGuid').val() == '')
					{
						alert('Please enter AccountGuid');
						jQuery('#woocommerce_clickandpledge_AccountGuid').focus();
						return false;
					}
					
					var cards = 0;
					if(jQuery('#woocommerce_clickandpledge_Visa').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#woocommerce_clickandpledge_American_Express').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#woocommerce_clickandpledge_Discover').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#woocommerce_clickandpledge_MasterCard').is(':checked'))
					{
						cards++;
					}
					if(jQuery('#woocommerce_clickandpledge_JCB').is(':checked'))
					{
						cards++;
					}
					
					if(cards == 0) 
					{
						alert('Please select at least  one card');
						jQuery('#woocommerce_clickandpledge_Visa').focus();
						return false;
					}
					if(jQuery('#woocommerce_clickandpledge_isRecurring').val() == 1)
					{
						if(jQuery('#woocommerce_clickandpledge_RecurringLabel').val() == '')
						{
						alert('Please enter Label');
						jQuery('#woocommerce_clickandpledge_RecurringLabel').focus();
						return false;
						}
					}
					
					if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked') && jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').val() != '')
					{
						if(!jQuery.isNumeric((jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').val())))
						{
							alert('Please enter valid number. It will allow numbers only');
							jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').focus();
							return false;
						}
						if(!isInt(jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').val()))
						{
							alert('Please enter integer values only');
							jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').focus();
							return false;
						}
						if(jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').val() < 2)
						{
							alert('Please enter value greater than 1');
							jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').focus();
							return false;
						}
						if(jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').val() > 998)
						{
							alert('Please enter value between 2-998');
							jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').focus();
							return false;
						}
					}
					function isInt(n) {
						return n % 1 === 0;
					}
					if(jQuery('#woocommerce_clickandpledge_Subscription').is(':checked') && jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() != '')
					{
						if(!jQuery.isNumeric((jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val())))
						{
						alert('Please enter valid number. It will allow numbers only');
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').focus();
						return false;
						}
						
						if(!isInt(jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val()))
						{
							alert('Please enter integer values only');
							jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').focus();
							return false;
						}
						
						if(jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() < 2)
						{
							alert('Please enter value greater than 1');
							jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').focus();
							return false;
						}
						if(jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').val() > 999)
						{
							alert('Please enter value between 2-999');
							jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').focus();
							return false;
						}
					}
				});
				
				function limitText(limitField, limitCount, limitNum) {
					if (limitField.val().length > limitNum) {
						limitField.val( limitField.val().substring(0, limitNum) );
					} else {
						limitCount.html (limitNum - limitField.val().length);
					}
				}
				//OrganizationInformation
				jQuery('#woocommerce_clickandpledge_OrganizationInformation').keydown(function(){
					limitText(jQuery('#woocommerce_clickandpledge_OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);
				});
				jQuery('#woocommerce_clickandpledge_OrganizationInformation').keyup(function(){
					limitText(jQuery('#woocommerce_clickandpledge_OrganizationInformation'),jQuery('#OrganizationInformation_countdown'),1500);
				});
				//ThankYouMessage
				jQuery('#woocommerce_clickandpledge_ThankYouMessage').keydown(function(){
					limitText(jQuery('#woocommerce_clickandpledge_ThankYouMessage'),jQuery('#ThankYouMessage_countdown'),500);
				});
				jQuery('#woocommerce_clickandpledge_ThankYouMessage').keyup(function(){
					limitText(jQuery('#woocommerce_clickandpledge_ThankYouMessage'),jQuery('#ThankYouMessage_countdown'),500);
				});
				//TermsCondition
				jQuery('#woocommerce_clickandpledge_TermsCondition').keydown(function(){
					limitText(jQuery('#woocommerce_clickandpledge_TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				});
				jQuery('#woocommerce_clickandpledge_TermsCondition').keyup(function(){
					limitText(jQuery('#woocommerce_clickandpledge_TermsCondition'),jQuery('#TermsCondition_countdown'),1500);
				});
				if(jQuery('#woocommerce_clickandpledge_isRecurring').val() == 1) {
					jQuery('#woocommerce_clickandpledge_Periodicity').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_RecurringLabel').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Week').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_2_Weeks').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Month').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_2_Months').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Quarter').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_6_Months').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Year').closest('tr').show();
						
					jQuery('#woocommerce_clickandpledge_RecurringMethod').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Installment').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_Subscription').closest('tr').show();
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').closest('tr').show();
						
					jQuery('#woocommerce_clickandpledge_indefinite').closest('tr').show();
				} else {
					jQuery('#woocommerce_clickandpledge_Periodicity').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_RecurringLabel').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Week').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_2_Weeks').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Month').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_2_Months').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Quarter').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_6_Months').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Year').closest('tr').hide();
						
					jQuery('#woocommerce_clickandpledge_RecurringMethod').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Installment').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_Subscription').closest('tr').hide();
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').closest('tr').hide();
						
					jQuery('#woocommerce_clickandpledge_indefinite').closest('tr').hide();
				}
				if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked')) {
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').closest('tr').show();
					} else {
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').closest('tr').hide();
					}
				jQuery('#woocommerce_clickandpledge_Installment').click(function(){
					if(jQuery('#woocommerce_clickandpledge_Installment').is(':checked')) {
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').closest('tr').show();
					} else {
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').closest('tr').hide();
					}
				});
				
				if(jQuery('#woocommerce_clickandpledge_Subscription').is(':checked')) {
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').closest('tr').show();
					} else {
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').closest('tr').hide();
					}
				jQuery('#woocommerce_clickandpledge_Subscription').click(function(){
					if(jQuery('#woocommerce_clickandpledge_Subscription').is(':checked')) {
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').closest('tr').show();
					} else {
					jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').closest('tr').hide();
					}
				});
				
				jQuery('#woocommerce_clickandpledge_isRecurring').change(function(){
					if(jQuery('#woocommerce_clickandpledge_isRecurring').val() == 1) {
						jQuery('#woocommerce_clickandpledge_Periodicity').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_RecurringLabel').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Week').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_2_Weeks').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Month').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_2_Months').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Quarter').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_6_Months').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Year').closest('tr').show();
						
						jQuery('#woocommerce_clickandpledge_RecurringMethod').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Installment').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_Subscription').closest('tr').show();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').closest('tr').show();
						
						jQuery('#woocommerce_clickandpledge_indefinite').closest('tr').show();
					} else {
						jQuery('#woocommerce_clickandpledge_Periodicity').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_RecurringLabel').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Week').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_2_Weeks').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Month').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_2_Months').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Quarter').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_6_Months').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Year').closest('tr').hide();
						
						jQuery('#woocommerce_clickandpledge_RecurringMethod').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Installment').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Installment').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_Subscription').closest('tr').hide();
						jQuery('#woocommerce_clickandpledge_maxrecurrings_Subscription').closest('tr').hide();
												
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
			
			//print_r($this->settings);			
			//$available_cards = $this->avaiable_countries[$user_country];
			$available_cards = $this->available_cards;
			//print_r($available_cards);
			?>
			<?php if ($this->testmode=='yes') : ?><p><?php _e('TEST MODE/SANDBOX ENABLED', 'woothemes'); ?></p><?php endif; ?>
			<?php if ($this->description) : ?><p><?php echo $this->description; ?></p><?php endif; ?>
			
			<?php 
			//print_r($available_cards);
			if (count($available_cards) > 0) { ?>
				<p><?php 
				if(in_array('Visa', $available_cards))
					echo "<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/visa.jpg' title='Visa' alt='Visa'/>";
				if(in_array('American Express', $available_cards))
					echo "&nbsp;<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/amex.jpg' title='Visa' alt='Visa'/>";
				if(in_array('Discover', $available_cards))
					echo "&nbsp;<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/discover.jpg' title='American Express' alt='American Express'/>";
				if(in_array('MasterCard', $available_cards))
					echo "&nbsp;<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/mastercard.gif' title='MasterCard' alt='MasterCard'/>";
				if(in_array('JCB', $available_cards))
					echo "&nbsp;<img src='".WP_PLUGIN_URL . "/" . plugin_basename( dirname(__FILE__)) . "/images/JCB.jpg' title='JCB' alt='JCB'/>";
				?></p>
			<?php } ?>
			
			<fieldset>
				<?php 
				if($this->isRecurring) { ?>
				<script type="text/javascript">
				jQuery( document ).ready(function(){		
					if(jQuery('#clickandpledge_isRecurring').is(':checked')) {
						jQuery('#clickandpledge_Periodicity_p').show();
						jQuery('#clickandpledge_RecurringMethod_p').show();
						if(jQuery('#clickandpledge_indefinite').length)
								jQuery('#clickandpledge_indefinite_p').show();
					} else {
						jQuery('#clickandpledge_Periodicity_p').hide();
						jQuery('#clickandpledge_RecurringMethod_p').hide();
						if(jQuery('#clickandpledge_indefinite').length)
								jQuery('#clickandpledge_indefinite_p').hide();
					}				
									
					jQuery('#clickandpledge_indefinite').click(function(){
						if(jQuery('#clickandpledge_indefinite').is(':checked')) {
							jQuery('#clickandpledge_Installment').val('');	
							jQuery('#clickandpledge_Installment_req').hide();						
							//jQuery('#clickandpledge_Installment_req').html('');
							//jQuery('#clickandpledge_Installment').attr('readonly', true);
						} else {
							jQuery('#clickandpledge_Installment_req').show();	
							//jQuery('#clickandpledge_Installment_req').html('<font color="#FF0000">*</font>');
							//jQuery('#clickandpledge_Installment').attr('readonly', false);
						}
					});
				});
				function isIndefinite() {
					if(jQuery('#clickandpledge_indefinite').is(':checked')) {
							jQuery('#clickandpledge_Installment').val('');	
							jQuery('#clickandpledge_Installment_req').hide();
						} else {
							jQuery('#clickandpledge_Installment_req').show();
						}
				}
				function isRecurring() {
					if(jQuery('#clickandpledge_isRecurring').is(':checked')) {					
							jQuery('#clickandpledge_Periodicity_p').show();
							jQuery('#clickandpledge_RecurringMethod_p').show();
							if(jQuery('#clickandpledge_indefinite').length)
								jQuery('#clickandpledge_indefinite_p').show();
						} else {
							jQuery('#clickandpledge_Periodicity_p').hide();
							jQuery('#clickandpledge_RecurringMethod_p').hide();
							if(jQuery('#clickandpledge_indefinite').length)
								jQuery('#clickandpledge_indefinite_p').hide();
						}
				}
				</script>
				<p class="">
					<label for="clickandpledge_cart_type">
					<input type="checkbox" name="clickandpledge_isRecurring" id="clickandpledge_isRecurring" onclick="isRecurring()">&nbsp;
					<?php echo __($this->settings['RecurringLabel'], 'woocommerce') ?> </label>
				</p>
				<div class="clear"></div>
				
				<?php 
				//print_r($this->settings);
				if(count($this->RecurringMethod) > 0) {
				?>
				
				<p class="" id="clickandpledge_RecurringMethod_p" style="display:none;">
					<label for="clickandpledge_card_csc"><?php _e("Recurring Method", 'woocommerce') ?> <span class="required" style="color:red;">*</span></label>
					<select id="clickandpledge_RecurringMethod" name="clickandpledge_RecurringMethod">
						<?php foreach ($this->RecurringMethod as $r) : ?>
									<option value="<?php echo $r ?>"><?php echo $r; ?></options>
						<?php endforeach; ?>			
					</select>
				</p>
				<script>
					jQuery(document).ready(function(){					
						
						jQuery('#clickandpledge_RecurringMethod_p').hide();					
						if(jQuery('#clickandpledge_RecurringMethod').val() == 'Installment') {
								jQuery('#clickandpledge_indefinite_p').hide();
						}
						
						jQuery('#clickandpledge_RecurringMethod').change(function(){
							if(jQuery('#clickandpledge_RecurringMethod').val() == 'Installment') {
								jQuery('#clickandpledge_indefinite_p').hide();
							} else {
								jQuery('#clickandpledge_indefinite_p').show();
							}
						});	
						
					});
				</script>
				<?php } else {				
				?>
				<?php foreach ($this->RecurringMethod as $r) : ?>
					<input type="hidden" name="clickandpledge_RecurringMethod" id="clickandpledge_RecurringMethod" value="<?php echo $r;?>">
				<?php endforeach; ?>
				<script>
					if(jQuery('#clickandpledge_RecurringMethod').val() == 'Installment') {
							jQuery('#clickandpledge_indefinite_p').hide();
					}
				</script>					
				<?php
				} ?>
				<p class="">
				<?php
					if(isset($this->settings['indefinite']) && $this->settings['indefinite'] == 'yes') {
					?>
					<span id="clickandpledge_indefinite_p" style="display:none;">
					&nbsp;
					<input type="checkbox" name="clickandpledge_indefinite" id="clickandpledge_indefinite" onclick="isIndefinite()">Indefinite Recurring&nbsp;
					</span>
					<?php } ?>
				</p>
				<div class="clear"></div>
				
								
				<p class="" id="clickandpledge_Periodicity_p" style="display:none;">					
					Every <select id="clickandpledge_Periodicity" name="clickandpledge_Periodicity">
						<?php foreach ($this->Periodicity as $p) : ?>
									<option value="<?php echo $p ?>"><?php echo $p; ?></options>
						<?php endforeach; ?>
					</select>
					<span class="required" id="clickandpledge_Installment_req">
					&nbsp;for 
					<input type="text" class="input-text required" id="clickandpledge_Installment" name="clickandpledge_Installment" maxlength="3" style="width:49px; margin-right:2px;" /> <font color="#FF0000">*</font> payments</span>
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
					
				</p>
				<div class="clear"></div>
				
				
				
				
				
				<?php } ?>
				
				<p class="">
					<label for="clickandpledge_cart_number"><?php echo __("Name on Card", 'woocommerce') ?> <span class="required" style="color:red;">*</span></label>
					<input type="text" class="input-text required" name="clickandpledge_name_on_card" placeholder="Name on Card" maxlength="50"/>
				</p>
				<div class="clear"></div>
				
				<p class="form-row form-row-first">
					<label for="clickandpledge_cart_number"><?php echo __("Credit Card number", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text required" name="clickandpledge_card_number" placeholder="Credit Card number" style="color:#141412; font-weight:normal;"/>
				</p>
				<p class="form-row form-row-last">
					<label for="clickandpledge_card_csc"><?php _e("Card Verification (CVV)", 'woocommerce') ?> <span class="required">*</span></label>
					<input type="text" class="input-text" id="clickandpledge_card_csc" name="clickandpledge_card_csc" maxlength="4" style="width:59px" placeholder="cvv"/>
					<script>
					function GetCreditCardType(CreditCardNumber)
					{
						var regVisa = "^4[0-9]{12}(?:[0-9]{3})?$";
						var regMaster = "^5[1-5][0-9]{14}$";
						var regExpress = "^3[47][0-9]{13}$";
						var regDiners = "^3(?:0[0-5]|[68][0-9])[0-9]{11}$";
						var regDiscover = "^6(?:011|5[0-9]{2})[0-9]{12}$";
						var regJSB= "^(?:2131|1800|35\\d{3})\\d{11}$";


						if(regVisa.test(CreditCardNumber))
							return "VISA";
						if (regMaster.test(CreditCardNumber))
							return "MASTER";
						if (regExpress.test(CreditCardNumber))
							return "AEXPRESS";
						if (regDiners.test(CreditCardNumber))
							return "DINERS";
						if (regDiscover.test(CreditCardNumber))
							return "DISCOVERS";
						if (regJSB.test(CreditCardNumber))
							return "JSB";
						return "invalid";
					}
					</script>
					<span class="help clickandpledge_card_csc_description"></span>
				</p>
				<!--
				<p class="form-row form-row-last">
					<label for="clickandpledge_cart_type"><?php echo __("Card type", 'woocommerce') ?> <span class="required">*</span></label>
					<select id="clickandpledge_card_type" name="clickandpledge_card_type" onchange="toggle_csc();">
						<?php foreach ($available_cards as $card) : ?>
									<option value="<?php echo $card ?>"><?php echo $card; ?></options>
						<?php endforeach; ?>
					</select>
				</p>
				-->
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
				
				<div class="clear"></div>
				
				
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
					} else if ( card_type == "American Express" ) {
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
				$posted_settings['cnp_email_customer'] = $this->settings['cnp_email_customer'];
				$posted_settings['Total'] = $order->order_total;
				$posted_settings['OrderMode'] = $this->testmode;
				
				$posted_settings['OrganizationInformation'] = $this->settings['OrganizationInformation'];
				$posted_settings['ThankYouMessage'] = $this->settings['ThankYouMessage'];
				$posted_settings['TermsCondition'] = $this->settings['TermsCondition'];
			
				$response = $request->send($posted_settings, $_POST, $order);
			
			} catch(Exception $e) {
				$woocommerce->add_error(__('There was a connection error', 'woothemes') . ': "' . $e->getMessage() . '"');
				return;
			}
	
			if ($response['status'] == 'Success') :
				$order->add_order_note( __('Click & Pledge payment completed', 'woothemes') . ' (Transaction ID: ' . $response['TransactionNumber'] . ')' );
				$order->payment_complete();
				//$order->reduce_order_stock();
				$woocommerce->cart->empty_cart();
					
				// Return thank you page redirect
				return array(
					'result' 	=> 'success',
					'redirect'	=> $this->get_return_url( $order )
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
	
	function CreditCardCompany($ccNum)
	 {
			/*
				* mastercard: Must have a prefix of 51 to 55, and must be 16 digits in length.
				* Visa: Must have a prefix of 4, and must be either 13 or 16 digits in length.
				* American Express: Must have a prefix of 34 or 37, and must be 15 digits in length.
				* Diners Club: Must have a prefix of 300 to 305, 36, or 38, and must be 14 digits in length.
				* Discover: Must have a prefix of 6011, and must be 16 digits in length.
				* JCB: Must have a prefix of 3, 1800, or 2131, and must be either 15 or 16 digits in length.
			*/
	 
			if (ereg("^5[1-5][0-9]{14}$", $ccNum))
					return "MasterCard";
	 
			if (ereg("^4[0-9]{12}([0-9]{3})?$", $ccNum))
					return "Visa";
	 
			if (ereg("^3[47][0-9]{13}$", $ccNum))
					return "American Express";
	 
			if (ereg("^3(0[0-5]|[68][0-9])[0-9]{11}$", $ccNum))
					return "Diners Club";
	 
			if (ereg("^6011[0-9]{12}$", $ccNum))
					return "Discover";
	 
			if (ereg("^(3[0-9]{4}|2131|1800)[0-9]{11}$", $ccNum))
					return "JCB";
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
				
			
			if($_POST['clickandpledge_isRecurring'] == 'on') {
				if(empty($_POST['clickandpledge_Periodicity'])) {
						$woocommerce->add_error(__('Please select Periodicity', 'woothemes'));
						return false;
					}
							
				if(!$_POST['clickandpledge_indefinite']) {
					if(empty($_POST['clickandpledge_Installment'])) {
						if($_POST['clickandpledge_RecurringMethod'] == 'Subscription') {
							if(!empty($this->maxrecurrings_Subscription))
							{
								$woocommerce->add_error(__('Please enter a value between 2-'.$this->maxrecurrings_Subscription, 'woothemes'));
							} else {
								$woocommerce->add_error(__('Please enter a value between 2-999', 'woothemes'));
							}
						} else {
							if(!empty($this->maxrecurrings_Installment))
							{
								$woocommerce->add_error(__('Please enter a value between 2-'.$this->maxrecurrings_Installment, 'woothemes'));
							} else {
							$woocommerce->add_error(__('Please enter a value between 2-998', 'woothemes'));
							}
						}
						return false;
					}
					if(!ctype_digit($_POST['clickandpledge_Installment'])) {
						$woocommerce->add_error(__('Please enter Numbers only in installments', 'woothemes'));
						return false;
					}
					if($_POST['clickandpledge_Installment'] == 1) {
						if($_POST['clickandpledge_RecurringMethod'] == 'Subscription') {
						$woocommerce->add_error(__('Installments should be greater than 2', 'woothemes'));
						} else {
						$woocommerce->add_error(__('Installments should be greater than 2', 'woothemes'));
						}
						return false;
					}
					if(strlen($_POST['clickandpledge_Installment']) > 3) {
						if($_POST['clickandpledge_RecurringMethod'] == 'Subscription') {
						$woocommerce->add_error(__('Please enter a value between 2-999', 'woothemes'));
						} else {
						$woocommerce->add_error(__('Please enter a value between 2-998', 'woothemes'));
						}
						return false;
					}
					
					if($_POST['clickandpledge_RecurringMethod'] == 'Subscription')
					{						
						if(!empty($this->maxrecurrings_Subscription) && $_POST['clickandpledge_Installment'] > $this->maxrecurrings_Subscription  )
						{
							$woocommerce->add_error(__('Please enter a value between 2-'.$this->maxrecurrings_Subscription.' only', 'woothemes'));
							return false;
						}
					}
					
					if($_POST['clickandpledge_RecurringMethod'] == 'Installment')
					{
						if($_POST['clickandpledge_Installment'] == 999  )
						{
							$woocommerce->add_error(__('Please enter a value between 2-998', 'woothemes'));
							return false;
						}
						
						if(!empty($this->maxrecurrings_Installment) && $_POST['clickandpledge_Installment'] > $this->maxrecurrings_Installment  )
						{
							$woocommerce->add_error(__('Please enter a value between 2-'.$this->maxrecurrings_Installment.' only', 'woothemes'));
							return false;
						}
					}
				} 
			}
			
			// Name on card
			if(empty($name_on_card)) {
				$woocommerce->add_error(__('Please enter Name on Card', 'woothemes'));
				return false;
			}			
			if (!ereg("^([a-zA-Z0-9\.\,\#\-\ \']){2,50}$", $name_on_card)) {
				$woocommerce->add_error(__('Please enter the only Alphanumeric and space for Name on Card', 'woothemes'));
				return false;
			}
			
			//Card Number
			if(empty($card_number)) {
				$woocommerce->add_error(__('Please enter Credit Card Number', 'woothemes'));
				return false;
			}
			if(strlen($card_number) < 13) {
				$woocommerce->add_error(__('Invalid Credit Card Number', 'woothemes'));
				return false;
			}
			if(strlen($card_number) > 19) {
				$woocommerce->add_error(__('Invalid Credit Card Number', 'woothemes'));
				return false;
			}
			if(!$this->cc_check($card_number)) {
				$woocommerce->add_error(__('Invalid Credit Card Number', 'woothemes'));
				return false;
			}
			
			//CVV
			if(empty($card_csc)) {
				$woocommerce->add_error(__('Please enter CVV', 'woothemes'));
				return false;
			}			
			if(!ctype_digit($card_csc)) {
				$woocommerce->add_error(__('Please enter Numbers only in Card Verification(CVV)', 'woothemes'));
				return false;
			}	
			if(( strlen($card_csc) < 3 )) {
				$woocommerce->add_error(__('Please enter a number at least 3 or 4 digits in card verification (CVV)', 'woothemes'));
				return false;
			}
			
			//Credit Card Validation					
			$selected_card = $this->CreditCardCompany($card_number);
			if(!in_array($selected_card, $this->available_cards))
			{
				$woocommerce->add_error(__('We are not accepting <b>'.$selected_card.'</b> type cards', 'woothemes'));
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
