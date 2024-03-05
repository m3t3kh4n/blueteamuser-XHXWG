<?php
	//load classes init method
	add_action('init', array('PMProGateway_peach', 'init'));

	/**
	 * PMProGateway_gatewayname Class
	 */
	class PMProGateway_peach extends PMProGateway
	{
		function PMProGateway($gateway = NULL)
		{
			$this->gateway = $gateway;
			return $this->gateway;
		}										

		/**
		 * Run on WP init
		 *
		 * @since 1.8
		 */
		static function init()
		{
			//make sure Peach is a gateway option
			add_filter('pmpro_gateways', array('PMProGateway_peach', 'pmpro_gateways'));

			//add fields to payment settings
			add_filter('pmpro_payment_options', array('PMProGateway_peach', 'pmpro_payment_options'));
			add_filter('pmpro_payment_option_fields', array('PMProGateway_peach', 'pmpro_payment_option_fields'), 10, 2);

			//add some fields to edit user page (Updates)
			add_action('pmpro_after_membership_level_profile_fields', array('PMProGateway_peach', 'user_profile_fields'));
			add_action('profile_update', array('PMProGateway_peach', 'user_profile_fields_save'));

			//updates cron
			add_action('pmpro_activation', array('PMProGateway_peach', 'pmpro_activation'));
			add_action('pmpro_deactivation', array('PMProGateway_peach', 'pmpro_deactivation'));
			add_action('pmpro_cron_peach_subscription_updates', array('PMProGateway_peach', 'pmpro_cron_peach_subscription_updates'));

			//code to add at checkout if Peach is the current gateway
			$gateway = pmpro_getOption("gateway");
			if($gateway == "peach")
			{
				/*add_action('pmpro_checkout_preheader', array('PMProGateway_peach', 'pmpro_checkout_preheader'));*/
				add_filter('pmpro_checkout_order', array('PMProGateway_peach', 'pmpro_checkout_order'));
				add_filter('pmpro_include_billing_address_fields', array('PMProGateway_peach', 'pmpro_include_billing_address_fields'));
				add_filter('pmpro_include_cardtype_field', array('PMProGateway_peach', 'pmpro_include_billing_address_fields'));
				
				//add_filter('pmpro_required_billing_fields', array( 'PMProGateway_peach', 'pmpro_required_billing_fields' ) );
				
				add_filter('pmpro_checkout_confirmed', array('PMProGateway_peach', 'pmpro_checkout_confirmed'), 10, 2);
				add_filter('pmpro_billing_show_payment_method', '__return_false' );
				//add_filter('pmpro_include_payment_information_fields', '__return_false');
				
				//add_filter( 'pmpro_pages_custom_template_path', 'peach_pmpro_pages_custom_template_path', 10, 2 );
			}
			
			$default_gateway = pmpro_getOption( 'gateway' );
			$current_gateway = pmpro_getGateway();
			if ( ( $default_gateway == "peach" || $current_gateway == "peach" ) && empty( $_REQUEST['review'] ) ) {
				//add_filter('pmpro_checkout_before_change_membership_level', array('PMProGateway_peach', 'pmpro_checkout_before_change_membership_level'), 10, 2);
			}
		}

		/**
		 * Make sure Peach is in the gateways list
		 *
		 * @since 1.8
		 */
		static function pmpro_gateways($gateways)
		{
			if(empty($gateways['peach']))
				$gateways['peach'] = __('Peach Payments', 'pmpro');

			return $gateways;
		}

		/**
		 * Get a list of payment options that the Peach gateway needs/supports.
		 *
		 * @since 1.8
		 */
		static function getGatewayOptions()
		{
			$options = array(
				'sslseal',
				'nuclear_HTTPS',
				'gateway_environment',
				'currency',
				'use_ssl',
				'tax_state',
				'tax_rate',
				'accepted_credit_cards',
				'peach_accesstoken',
				'peach_secrettoken',
				'peach_3dsecure',
				'peach_recurringid',
				'peach_webhookkey',
				'peach_billingaddress'
			);

			return $options;
		}

		/**
		 * Set payment options for payment settings page.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_options($options)
		{
			//get example options
			$peach_options = PMProGateway_peach::getGatewayOptions();

			//merge with others.
			$options = array_merge($peach_options, $options);

			return $options;
		}

		/**
		 * Display fields for example options.
		 *
		 * @since 1.8
		 */
		static function pmpro_payment_option_fields($values, $gateway)
		{
			?>
            <tr class="pmpro_settings_divider gateway gateway_peach" <?php if ( $gateway != "peach" ) { ?>style="display: none;"<?php } ?>>
                <td colspan="2">
                    <hr />
                    <h2 class="pmpro_peach_legacy_keys"><?php esc_html_e( 'Peach API Settings', 'paid-memberships-pro' ); ?></h2>
                </td>
            </tr>
            <tr class="gateway pmpro_peach_legacy_keys gateway_peach" <?php if ( $gateway != "peach" ) { ?>style="display: none;"<?php } ?>>
                <th scope="row" valign="top">
                    <label for="peach_accesstoken"><?php esc_html_e( 'Access Token', 'paid-memberships-pro' ); ?>:</label>
                </th>
                <td>
                    <input type="text" id="peach_accesstoken" name="peach_accesstoken" value="<?php echo esc_attr( $values['peach_accesstoken'] ) ?>" class="regular-text code" />
                    <p class="description">This is the key generated within the Peach Payments Console under Development > Access Token.</p>
                </td>
            </tr>
            <tr class="gateway pmpro_peach_legacy_keys gateway_peach" <?php if ( $gateway != "peach" ) { ?>style="display: none;"<?php } ?>>
                <th scope="row" valign="top">
                    <label for="peach_secrettoken"><?php esc_html_e( 'Secret Token', 'paid-memberships-pro' ); ?>:</label>
                </th>
                <td>
                    <input type="text" id="peach_secrettoken" name="peach_secrettoken" value="<?php echo esc_attr( $values['peach_secrettoken'] ) ?>" autocomplete="off" class="regular-text code pmpro-admin-secure-key" />
                    <p class="description">This is the key generated within the Peach Payments Dashboard (Only if non-card payment method types have been enabled).</p>
                </td>
            </tr>
            <tr class="gateway pmpro_peach_legacy_keys gateway_peach" <?php if ( $gateway != "peach" ) { ?>style="display: none;"<?php } ?>>
                <th scope="row" valign="top">
                    <label for="peach_3dsecure"><?php esc_html_e( '3DSecure Channel ID', 'paid-memberships-pro' ); ?>:</label>
                </th>
                <td>
                    <input type="text" id="peach_3dsecure" name="peach_3dsecure" value="<?php echo esc_attr( $values['peach_3dsecure'] ) ?>" autocomplete="off" class="regular-text code pmpro-admin-secure-key" />
                    <p class="description">The Entity ID that you received from Peach Payments.</p>
                </td>
            </tr>
            <tr class="gateway pmpro_peach_legacy_keys gateway_peach" <?php if ( $gateway != "peach" ) { ?>style="display: none;"<?php } ?>>
                <th scope="row" valign="top">
                    <label for="peach_recurringid"><?php esc_html_e( 'Recurring Channel ID', 'paid-memberships-pro' ); ?>:</label>
                </th>
                <td>
                    <input type="text" id="peach_recurringid" name="peach_recurringid" value="<?php echo esc_attr( $values['peach_recurringid'] ) ?>" class="regular-text code" />
                    <p class="description">This field is only required if you want to receive recurring payments. You will receive this from Peach Payments.</p>
                </td>
            </tr>
            <tr class="gateway pmpro_peach_legacy_keys gateway_peach" <?php if ( $gateway != "peach" ) { ?>style="display: none;"<?php } ?>>
                <th scope="row" valign="top">
                    <label for="peach_webhookkey"><?php esc_html_e( 'Card Webhook Decryption Key', 'paid-memberships-pro' ); ?>:</label>
                </th>
                <td>
                    <input type="text" id="peach_webhookkey" name="peach_webhookkey" value="<?php echo esc_attr( $values['peach_webhookkey'] ) ?>" class="regular-text code" />
                    <p class="description">You’ll receive this key from Peach Payments after your webhook is enabled.<br>To enable the webhook, please email <a href="mailto:support@peachpayments.com">support@peachpayments.com</a> to set up <a href="https://peach8.semantica.co.za/" target="_blank" rel="nofollow">https://peach8.semantica.co.za/</a> on your account.</p>
                </td>
            </tr>
            <tr class="pmpro_settings_divider gateway gateway_peach" <?php if ( $gateway != "peach" ) { ?>style="display: none;"<?php } ?>>
                <td colspan="2">
                    <hr />
                    <h2><?php esc_html_e( 'Other Peach Settings', 'paid-memberships-pro' ); ?></h2>
                </td>
            </tr>
            <tr class="gateway gateway_peach" <?php if ( $gateway != "peach" ) { ?>style="display: none;"<?php } ?>>
                <th scope="row" valign="top">
                    <label for="peach_billingaddress"><?php esc_html_e( 'Show Billing Address Fields', 'paid-memberships-pro' ); ?>:</label>
                </th>
                <td>
                    <select id="peach_billingaddress" name="peach_billingaddress">
                        <option value="0"
                                <?php if ( empty( $values['peach_billingaddress'] ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'No', 'paid-memberships-pro' ); ?></option>
                        <option value="1"
                                <?php if ( ! empty( $values['peach_billingaddress'] ) ) { ?>selected="selected"<?php } ?>><?php esc_html_e( 'Yes', 'paid-memberships-pro' ); ?></option>
                    </select>
                    <p class="description"><?php echo wp_kses_post( __( "Peach Payments require billing address fields.", 'paid-memberships-pro' ) ); ?></p>
                </td>
            </tr>
            <?php
		}
		
		public static function pmpro_include_billing_address_fields( $include ) {
			//check settings RE showing billing address
			if ( ! pmpro_getOption( "peach_billingaddress" ) ) {
				$include = false;
			}
	
			return $include;
		}

		/**
		 * Filtering orders at checkout.
		 *
		 * @since 1.8
		 */
		static function pmpro_checkout_order($morder)
		{
			return $morder;
		}

		/**
		 * Code to run after checkout
		 *
		 * @since 1.8
		 */
		static function pmpro_after_checkout($user_id, $morder)
		{
			global $gateway;
	
			if ( $gateway == "peach" ) {
				if ( self::$is_loaded && ! empty( $morder ) && ! empty( $morder->Gateway ) && ! empty( $morder->Gateway->customer ) && ! empty( $morder->Gateway->customer->id ) ) {
					update_user_meta( $user_id, "pmpro_peach_customerid", $morder->Gateway->customer->id );
				}
			}
		}
		
		/**
		 * Review and Confirmation code.
		 */
		static function pmpro_checkout_confirmed($pmpro_confirmed, $morder){
			global $pmpro_msg, $pmpro_msgt, $pmpro_level, $current_user, $pmpro_review, $pmpro_paypal_token, $discount_code, $bemail;
			
			if($_REQUEST && $_REQUEST['submit-checkout'] == '1' && isset($morder)){
				$checkout_page_id = pmpro_getOption('checkout_page_id');
				$checkout_page = get_permalink($checkout_page_id).'?level='.$_REQUEST['level'];
				$ssl_verifypeer = 'true';
				$url = "https://eu-prod.oppwa.com";
				$success_code = '000.000.000';
				$paymentType = 'DB';
				//$paymentType = 'PA';
				if(pmpro_getOption( 'gateway_environment' ) === 'sandbox'){
					$ssl_verifypeer = 'false';
					$url = "https://eu-test.oppwa.com";
					$success_code = '000.100.110';
				}
				
				$amount = number_format($morder->InitialPayment, 2);
				
				$checkoutUrl = $url.'/v1/payments';
				$data = "entityId=" .pmpro_getOption('peach_3dsecure').
					"&amount=" .$amount.
					"&currency=" .pmpro_getOption('currency').
					"&paymentBrand=" .strtoupper($_REQUEST['CardType']).
					"&paymentType=" .$paymentType.
					"&card.number=" .$_REQUEST['AccountNumber'].
					"&card.holder=" .
					"&card.expiryMonth=" .$_REQUEST['ExpirationMonth'].
					"&card.expiryYear=" .$_REQUEST['ExpirationYear'].
					"&card.cvv=" .$_REQUEST['CVV'];
			
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $checkoutUrl);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							   'Authorization:Bearer '.pmpro_getOption( 'peach_accesstoken' )));
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $ssl_verifypeer);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$responseData = curl_exec($ch);
				if(curl_errno($ch)) {
					return curl_error($ch);
				}
				curl_close($ch);
				$response = json_decode($responseData);
				if($response->result->code != $success_code){
					
				}
				echo '<pre>'.print_r($response, true).'</pre>';
				
			}else{
				echo '<pre>'.print_r('No Request', true).'</pre>';
			}
			
			return false;
			
		}
		
		/**
		 * Don't require address fields if they are set to hide.
		 */
		public static function pmpro_required_billing_fields( $fields ) {
			$remove = array( 'CardType', 'AccountNumber', 'ExpirationMonth', 'ExpirationYear', 'CVV' );
			foreach ( $remove as $field ) {
				unset( $fields[ $field ] );
			}
			return $fields;
		}

		/**
		 * Fields shown on edit user page
		 *
		 * @since 1.8
		 */
		static function user_profile_fields($user)
		{
		}

		/**
		 * Process fields from the edit user page
		 *
		 * @since 1.8
		 */
		static function user_profile_fields_save($user_id)
		{
		}

		/**
		 * Cron activation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_activation()
		{
			wp_schedule_event(time(), 'daily', 'pmpro_cron_peach_subscription_updates');
		}

		/**
		 * Cron deactivation for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_deactivation()
		{
			wp_clear_scheduled_hook('pmpro_cron_peach_subscription_updates');
		}

		/**
		 * Cron job for subscription updates.
		 *
		 * @since 1.8
		 */
		static function pmpro_cron_peach_subscription_updates()
		{
		}

		
		function process(&$order)
		{
			//check for initial payment
			if(floatval($order->InitialPayment) == 0)
			{
				//auth first, then process
				if($this->authorize($order))
				{						
					$this->void($order);										
					if(!pmpro_isLevelTrial($order->membership_level))
					{
						//subscription will start today with a 1 period trial (initial payment charged separately)
						$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
						$order->TrialBillingPeriod = $order->BillingPeriod;
						$order->TrialBillingFrequency = $order->BillingFrequency;													
						$order->TrialBillingCycles = 1;
						$order->TrialAmount = 0;
						
						//add a billing cycle to make up for the trial, if applicable
						if(!empty($order->TotalBillingCycles))
							$order->TotalBillingCycles++;
					}
					elseif($order->InitialPayment == 0 && $order->TrialAmount == 0)
					{
						//it has a trial, but the amount is the same as the initial payment, so we can squeeze it in there
						$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";														
						$order->TrialBillingCycles++;
						
						//add a billing cycle to make up for the trial, if applicable
						if($order->TotalBillingCycles)
							$order->TotalBillingCycles++;
					}
					else
					{
						//add a period to the start date to account for the initial payment
						$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $order->BillingFrequency . " " . $order->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
					}
					
					$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
					return $this->subscribe($order);
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Authorization failed.", "pmpro");
					return false;
				}
			}
			else
			{
				//charge first payment
				if($this->charge($order))
				{							
					//set up recurring billing					
					if(pmpro_isLevelRecurring($order->membership_level))
					{						
						if(!pmpro_isLevelTrial($order->membership_level))
						{
							//subscription will start today with a 1 period trial
							$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";
							$order->TrialBillingPeriod = $order->BillingPeriod;
							$order->TrialBillingFrequency = $order->BillingFrequency;													
							$order->TrialBillingCycles = 1;
							$order->TrialAmount = 0;
							
							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						elseif($order->InitialPayment == 0 && $order->TrialAmount == 0)
						{
							//it has a trial, but the amount is the same as the initial payment, so we can squeeze it in there
							$order->ProfileStartDate = date("Y-m-d") . "T0:0:0";														
							$order->TrialBillingCycles++;
							
							//add a billing cycle to make up for the trial, if applicable
							if(!empty($order->TotalBillingCycles))
								$order->TotalBillingCycles++;
						}
						else
						{
							//add a period to the start date to account for the initial payment
							$order->ProfileStartDate = date("Y-m-d", strtotime("+ " . $this->BillingFrequency . " " . $this->BillingPeriod, current_time("timestamp"))) . "T0:0:0";
						}
						
						$order->ProfileStartDate = apply_filters("pmpro_profile_start_date", $order->ProfileStartDate, $order);
						if($this->subscribe($order))
						{
							return true;
						}
						else
						{
							if($this->void($order))
							{
								if(!$order->error)
									$order->error = __("Unknown error: Payment failed.", "pmpro");
							}
							else
							{
								if(!$order->error)
									$order->error = __("Unknown error: Payment failed.", "pmpro");
								
								$order->error .= " " . __("A partial payment was made that we could not void. Please contact the site owner immediately to correct this.", "pmpro");
							}
							
							return false;								
						}
					}
					else
					{
						//only a one time charge
						$order->status = "success";	//saved on checkout page											
						return true;
					}
				}
				else
				{
					if(empty($order->error))
						$order->error = __("Unknown error: Payment failed.", "pmpro");
					
					return false;
				}	
			}	
		}
		
		/*
			Run an authorization at the gateway.

			Required if supporting recurring subscriptions
			since we'll authorize $1 for subscriptions
			with a $0 initial payment.
		*/
		function authorize(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//code to authorize with gateway and test results would go here

			//simulate a successful authorization
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("authorized");													
			return true;					
		}
		
		/*
			Void a transaction at the gateway.

			Required if supporting recurring transactions
			as we void the authorization test on subs
			with a $0 initial payment and void the initial
			payment if subscription setup fails.
		*/
		function void(&$order)
		{
			//need a transaction id
			if(empty($order->payment_transaction_id))
				return false;
			
			//code to void an order at the gateway and test results would go here

			//simulate a successful void
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("voided");					
			return true;
		}	
		
		/*
			Make a charge at the gateway.

			Required to charge initial payments.
		*/
		function charge(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//code to charge with gateway and test results would go here

			//simulate a successful charge
			$order->payment_transaction_id = "TEST" . $order->code;
			$order->updateStatus("success");					
			return true;						
		}
		
		/*
			Setup a subscription at the gateway.

			Required if supporting recurring subscriptions.
		*/
		function subscribe(&$order)
		{
			//create a code for the order
			if(empty($order->code))
				$order->code = $order->getRandomCode();
			
			//filter order before subscription. use with care.
			$order = apply_filters("pmpro_subscribe_order", $order, $this);
			
			//code to setup a recurring subscription with the gateway and test results would go here

			//simulate a successful subscription processing
			$order->status = "success";		
			$order->subscription_transaction_id = "TEST" . $order->code;				
			return true;
		}	
		
		/*
			Update billing at the gateway.

			Required if supporting recurring subscriptions and
			processing credit cards on site.
		*/
		function update(&$order)
		{
			//code to update billing info on a recurring subscription at the gateway and test results would go here

			//simulate a successful billing update
			return true;
		}
		
		/*
			Cancel a subscription at the gateway.

			Required if supporting recurring subscriptions.
		*/
		function cancel(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//code to cancel a subscription at the gateway and test results would go here

			//simulate a successful cancel			
			$order->updateStatus("cancelled");					
			return true;
		}	
		
		/*
			Get subscription status at the gateway.

			Optional if you have code that needs this or
			want to support addons that use this.
		*/
		function getSubscriptionStatus(&$order)
		{
			//require a subscription id
			if(empty($order->subscription_transaction_id))
				return false;
			
			//code to get subscription status at the gateway and test results would go here

			//this looks different for each gateway, but generally an array of some sort
			return array();
		}

		/*
			Get transaction status at the gateway.

			Optional if you have code that needs this or
			want to support addons that use this.
		*/
		function getTransactionStatus(&$order)
		{			
			//code to get transaction status at the gateway and test results would go here

			//this looks different for each gateway, but generally an array of some sort
			return array();
		}
	}
	
	function peach_pmpro_pages_custom_template_path( $templates, $page_name ) {
		$url = str_replace('classes/','',plugin_dir_path( __FILE__ ));		
		$templates[] = $url . 'templates/' . $page_name . '.php';	
		
		return $templates;
	}