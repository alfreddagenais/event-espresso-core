<?php

//Creates the invoice pdf
class Invoice {

	/**
	 *
	 * @var EE_Registration
	 */
	private $registration;
	/**
	 *
	 * @var EE_Transaction
	 */
	private $transaction;
	private $invoice_settings;
	private $EE;
	public function __construct($url_link = 0) {
		$this->EE = EE_Registry::instance();
		if ( $this->registration = EE_Registry::instance()->load_model( 'Registration' )->get_registration_for_reg_url_link( $url_link)) {
			$this->transaction = $this->registration->transaction();
			
			$payment_settings = EE_Config::instance()->gateway->payment_settings;//get_user_meta($this->EE->CFG->wp_user, 'payment_settings', TRUE);
			$this->invoice_settings = $payment_settings['Invoice'];
		} else {
			EE_Error::add_error( __( 'Your request appears to be missing some required data, and no information for your transaction could be retrieved.', 'event_espresso' ), __FILE__, __FUNCTION__, __LINE__ );	
		}

	}

	public function send_invoice( $download = FALSE ) {

//printr($this->registration);
//printr($this->transaction);
//printr($this->session_data);
//printr($this->invoice_settings);
//exit;
		$template_args = array();
		$EE = EE_Registry::instance();

		$theme = ( isset( $_REQUEST['theme'] ) && $_REQUEST['theme'] > 0 && $_REQUEST['theme'] < 8 ) ? absint( $_REQUEST['theme'] ) : 1;		
		$themes = array(
										1 => "simple.css",
										2 => "bauhaus.css",
										3 => "ejs.css",
										4 => "horizon.css", 
										5 => "lola.css",
										6 => "tranquility.css",
										7 => "union.css"
									);
		$this->invoice_settings['invoice_css'] = $themes[ $theme ];
		//echo '<h1>invoice_css : ' . $this->invoice_settings['invoice_css'] . '</h1>';

		//Get the CSS file
		if (!empty($this->invoice_settings['invoice_css'])) {
			$template_args['invoice_css'] = $this->invoice_settings['invoice_css'];
		} else {
			$template_args['invoice_css'] = 'simple.css';
		}

		if (is_dir(EVENT_ESPRESSO_GATEWAY_DIR . '/invoice')) {
			$template_args['base_url'] = EVENT_ESPRESSO_GATEWAY_URL . 'invoice/lib/templates/';
		} else {
			$template_args['base_url'] = EVENT_ESPRESSO_PLUGINFULLURL . 'gateways/invoice/lib/templates/';
		}
		$primary_attendee = $this->transaction->primary_registration()->attendee();
		
		$template_args['organization'] = stripslashes( $EE->CFG->organization->name );
		$template_args['street'] = empty( $EE->CFG->organization->address_2 ) ? $EE->CFG->organization->address_1 : $EE->CFG->organization->address_1 . '<br>' . $EE->CFG->organization->address_2;
		$template_args['city'] = $EE->CFG->organization->city;
		$template_args['state'] = $this->EE->load_model( 'State' )->get_one_by_ID( $EE->CFG->organization->STA_ID );
		$template_args['country'] = $this->EE->load_model( 'Country' )->get_one_by_ID( $EE->CFG->organization->CNT_ISO );
		$template_args['zip'] = $EE->CFG->organization->zip;
		$template_args['email'] = $EE->CFG->organization->email;
		$template_args['download_link'] = $this->registration->invoice_url();
		$template_args['registration_code'] = $this->registration->reg_code();
		$template_args['registration_date'] = $this->registration->date();
		$template_args['name'] = $primary_attendee->full_name();
		$template_args['attendee_address'] = $primary_attendee->address();
		$template_args['attendee_address2'] = $primary_attendee->address2();
		$template_args['attendee_city'] = $primary_attendee->city();
		$attendee_state = $primary_attendee->state_obj();
		if($attendee_state){
			$attendee_state_name = $attendee_state->name();
		}else{
			$attendee_state_name = '';
		}
		$template_args['attendee_state'] = $attendee_state_name;
		$template_args['attendee_zip'] = $primary_attendee->zip();
		
		$template_args['ship_name'] = $template_args['name'];
		$template_args['ship_address'] = $template_args['attendee_address'];
		$template_args['ship_city'] = $template_args['attendee_city'];
		$template_args['ship_state'] = $template_args['attendee_state'];
		$template_args['ship_zip'] = $template_args['attendee_zip'];
		
		$template_args['total_cost'] = number_format($this->transaction->total(), 2, '.', '');
		$template_args['transaction'] = $this->transaction;
		$template_args['amount_pd'] = $this->transaction->paid();
		$template_args['payments'] = $this->transaction->approved_payments();
		$template_args['net_total'] = '';
		if ($template_args['amount_pd'] != $template_args['total_cost']) {
			//$template_args['net_total'] = $this->espressoInvoiceTotals( __('SubTotal', 'event_espresso'), $this->transaction->total());//$this->session_data['cart']['REG']['sub_total']);
			$tax_items = $this->transaction->tax_items();
			if(!empty($tax_items) ){
				foreach ($tax_items as $tax) {
					$template_args['net_total'] .= $this->espressoInvoiceTotals( $tax->name(), $tax->total());
				}
			}
						
			$difference = $template_args['amount_pd'] - $template_args['total_cost'];
			if ($difference < 0) {
				$text = __('Discount', 'event_espresso');
			} else {
				$text = __('Extra', 'event_espresso');
			}
			$template_args['discount'] = $this->espressoInvoiceTotals( $text, $difference );
		}
		
		$template_args['currency_symbol'] = $EE->CFG->currency->sign;
		$template_args['pdf_instructions'] = wpautop(stripslashes_deep(html_entity_decode($this->invoice_settings['pdf_instructions'], ENT_QUOTES)));

		//require helpers
		$EE->load_helper( 'Formatter' );
		
		//Get the HTML as an object
		$this->EE->load_helper('Template');
		$template_header = EEH_Template::display_template( dirname(__FILE__) . '/templates/invoice_header.template.php', $template_args, TRUE );
		$template_body = EEH_Template::display_template( dirname(__FILE__) . '/templates/invoice_body.template.php', $template_args, TRUE );
		$template_footer = EEH_Template::display_template( dirname(__FILE__) . '/templates/invoice_footer.template.php', $template_args, TRUE );
		
		$copies =  ! empty( $_REQUEST['copies'] ) ? $_REQUEST['copies'] : 1;

		$content = $this->espresso_replace_invoice_shortcodes($template_header);
		for( $x = 1; $x <= $copies; $x++ ) {
			$content .= $this->espresso_replace_invoice_shortcodes($template_body);
		}
		$content .= $this->espresso_replace_invoice_shortcodes($template_footer);

		//Check if debugging or mobile is set
		if (!empty($_REQUEST['html'])) {
			echo $content;
			exit(0);
		}
		$invoice_name = $template_args['organization'] . ' ' . __('Invoice #', 'event_espresso') . $template_args['registration_code'] . __(' for ', 'event_espresso') . $template_args['name'];
		$invoice_name = str_replace( ' ', '_', $invoice_name );
		//Create the PDF
		if(array_key_exists('html',$_GET)){
			echo $content;
		}else{
			define('DOMPDF_ENABLE_REMOTE', TRUE);
			define('DOMPDF_ENABLE_JAVASCRIPT', FALSE);
			define('DOMPDF_ENABLE_CSS_FLOAT', TRUE);
			require_once(EVENT_ESPRESSO_PLUGINFULLPATH . '/tpc/dompdf/dompdf_config.inc.php');
			$dompdf = new DOMPDF();
			$dompdf->load_html($content);
			$dompdf->render();		
			$dompdf->stream($invoice_name . ".pdf", array( 'Attachment' => $download ));
		}
		exit(0);
	}

//Perform the shortcode replacement
	function espresso_replace_invoice_shortcodes( $content ) {

		$EE = EE_Registry::instance();
		//Create the logo
		if (!empty($this->invoice_settings['invoice_logo_url'])) {
			$invoice_logo_url = $this->invoice_settings['invoice_logo_url'];
		} else {
			$invoice_logo_url = $EE->CFG->organization->logo_url;
		}
		if (!empty($invoice_logo_url)) {
			$image_size = getimagesize($invoice_logo_url);
			$invoice_logo_image = '<img class="logo screen" src="' . $invoice_logo_url . '" ' . $image_size[3] . ' alt="logo" /> ';
		} else {
			$invoice_logo_image = '';
		}
		$SearchValues = array(
				"[organization]",
				"[registration_code]",
				"[transaction_id]",
				"[name]",
				"[base_url]",
				"[download_link]",
				"[invoice_logo_image]",
				"[street]",
				"[city]",
				"[state]",
				"[zip]",
				"[email]",
				"[registration_date]",
				"[instructions]",
		);
		$primary_attendee = $this->transaction->primary_registration()->attendee();
		$org_state = $this->EE->load_model( 'State' )->get_one_by_ID( $EE->CFG->organization->STA_ID );
		if($org_state){
			$org_state_name = $org_state->name();
		}else{
			$org_state_name = 'Unknown';
		}
		$ReplaceValues = array(
				stripslashes( $EE->CFG->organization->name ),
				$this->registration->reg_code(),
				$this->transaction->ID(),
				$primary_attendee->full_name(),
				(is_dir(EVENT_ESPRESSO_GATEWAY_DIR . '/invoice')) ? EVENT_ESPRESSO_GATEWAY_URL . 'invoice/lib/templates/' : EVENT_ESPRESSO_PLUGINFULLURL . 'gateways/invoice/lib/templates/',
				$this->registration->invoice_url(),//home_url() . '/?download_invoice=true&amp;id=' . $this->registration->reg_url_link(),
				$invoice_logo_image,
				empty( $EE->CFG->organization->address_2 ) ? $EE->CFG->organization->address_1 : $EE->CFG->organization->address_1 . '<br>' . $EE->CFG->organization->address_2,
				$EE->CFG->organization->city,
				$org_state_name,
				$EE->CFG->organization->zip,
				$EE->CFG->organization->email,
				$this->registration->date(),
				$this->invoice_settings['pdf_instructions']
		);

		return str_replace($SearchValues, $ReplaceValues, $content);
	}

	public function espressoLoadData($items) {
		$lines = $items;
		$data = array();
		foreach ($lines as $line)
			$data[] = explode(';', chop($line));

		return $data;
	}

	

	public function espressoInvoiceTotals($text, $total_cost) {

		$html = '';
		if ($total_cost < 0) {
			$total_cost = (-1) * $total_cost;
		}
		$find = array( ' ' );
		$replace = array( '-' );
		$row_id = strtolower( str_replace( $find, $replace, $text ));
		$html .= '<tr id="'.$row_id.'-tr"><td colspan="4">&nbsp;</td>';
		$html .= '<td class="item_r">' . $text . '</td>';
		$html .= '<td class="item_r">' . $total_cost . '</td>';
		$html .= '</tr>';
		return $html;
	}

}
