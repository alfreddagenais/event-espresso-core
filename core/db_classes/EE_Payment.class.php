<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package			Event Espresso
 * @ author				Seth Shoultes
 * @ copyright		(c) 2008-2011 Event Espresso  All Rights Reserved.
 * @ license			{@link http://eventespresso.com/support/terms-conditions/}   * see Plugin Licensing *
 * @ link					{@link http://www.eventespresso.com}
 * @ since		 		4.0
 *
 * ------------------------------------------------------------------------
 *
 * Payment class
 *
 * @package			Event Espresso
 * @subpackage	includes/classes/EE_Payment.class.php
 * @author				Brent Christensen
 *
 * ------------------------------------------------------------------------
 */
class EE_Payment extends EE_Base_Class implements EEI_Payment{


    /**
    *	Payment ID
	*
	* 	primary key
	*
	* 	@access	protected
    *	@var int
    */
	protected $_PAY_ID = FALSE;


    /**
    *	Transaction ID
	*
	*	@access	protected
    *	@var int
    */
	protected $_TXN_ID = NULL;


    /**
    *	Payment Status
	*
	*	@access	protected
    *	@var string
    */
	protected $_STS_ID = NULL;


    /**
    *	Payment Timestamp
	*
	*	@access	protected
    *	@var int
    */
	protected $_PAY_timestamp = NULL;


    /**
    *	Payment Method
	*
	*	@access	protected
    *	@var string
    */
	protected $_PMD_source = NULL;


    /**
    *	Payment Amount
	*
	*	@access	protected
    *	@var float
    */
	protected $_PAY_amount = NULL;


    /**
    *	Gateway Response
	*
	*	@access	protected
    *	@var string
    */
	protected $_PAY_gateway_response = NULL;


    /**
    *	Gateway Transaction ID or Cheque #
	*
	*	@access	protected
    *	@var string
    */
	protected $_PAY_txn_id_chq_nmbr = NULL;

 	 	 	
    /**
    *	Purchase Order Number
	*
	*	@access	protected
    *	@var string
    */
	protected $_PAY_po_number = NULL;

 	 	 	
    /**
    *	Extra Accounting Field
	*
	*	@access	protected
    *	@var string
    */
	protected $_PAY_extra_accntng = NULL;


    /**
    *	Payment Details
	*
	*	@access	protected
    *	@var string
    */
	protected $_PAY_details = NULL;
	
	/**
	 * URL where payment shoudl take place if it's offsite
	 * @var string
	 */
	protected $_PAY_redirect_url = NULL;
	
	/**
	 * key-value pairs for a POST form submission
	 * @var array
	 */
	protected $_PAY_redirect_args = array();

	
	
	/**
	 * Transaction this payment was for
	 * @var EE_Transaction
	 */
	protected $_Transaction = NULL;

	
	
	
	/**
	 * Status of this payment
	 * @var EE_Status
	 */
	protected $_Status = NULL;
	
	/**
	 *
	 * @var EE_Payment_Method
	 */
	protected $_Payment_Method = NULL;

	



	/**
	 * 
	 * @param type $props_n_values
	 * @return EE_Payment
	 */
	public static function new_instance( $props_n_values = array() ) {
		$classname = __CLASS__;
		$has_object = parent::_check_for_object( $props_n_values, $classname );
		return $has_object ? $has_object : new self( $props_n_values);
	}


	public static function new_instance_from_db ( $props_n_values = array(), $timezone = NULL ) {
		return new self( $props_n_values, TRUE, $timezone );
	}



	/**
	*		Set Transaction ID
	*
	* 		@access		public
	*		@param		int		$TXN_ID
	*/
	public function set_transaction_id( $TXN_ID = FALSE ) {
		$this->set('TXN_ID',$TXN_ID);
	}
	
	
	
	
	/**
	 * Gets the transaction related to this payment
	 * @return EE_Transaction
	 */
	public function transaction(){
		return $this->get_first_related('Transaction');
	}






	/**
	*		Set Status
	*
	* 		@access		public
	*		@param		string		$STS_ID
	*/
	public function set_status( $STS_ID = FALSE ) {

		$this->set('STS_ID',$STS_ID);
	}





	/**
	*		Set Payment Timestamp
	*
	* 		@access		public
	*		@param		int		$timestamp
	*/
	public function set_timestamp( $timestamp = FALSE ) {

		$this->set('PAY_timestamp',$timestamp);
	}





	/**
	*		Set Payment Method
	*
	* 		@access		public
	*		@param		string		$PMD_source
	*/
	public function set_source( $PMD_source = FALSE ) {
		$this->set('PMD_source',$PMD_source);
	}





	/**
	*		Set Payment Amount
	*
	* 		@access		public
	*		@param		float		$amount
	*/
	public function set_amount( $amount = FALSE ) {
		$this->set('PAY_amount',$amount);
	}







	/**
	*		Set Payment Gateway Response
	*
	* 		@access		public
	*		@param		string		$gateway_response
	*/
	public function set_gateway_response( $gateway_response = FALSE ) {
		$this->set('PAY_gateway_response',$gateway_response);
	}





	/**
	*		Set Gateway Transaction ID
	*
	* 		@access		public
	*		@param		string		$txn_id_chq_nmbr
	*/
	public function set_txn_id_chq_nmbr( $txn_id_chq_nmbr = FALSE ) {
		$this->set('PAY_txn_id_chq_nmbr',$txn_id_chq_nmbr);
	}




	/**
	*		Set Purchase Order Number
	*
	* 		@access		public
	*		@param		string		$po_number
	*/
	public function set_po_number( $po_number = FALSE ) {
		$this->set('PAY_po_number',$po_number);
	}





	/**
	*		Set Extra Accounting Field
	*
	* 		@access		public
	*		@param		string		$extra_accntng
	*/
	public function set_extra_accntng( $extra_accntng = FALSE ) {
		$this->set('PAY_extra_accntng',$extra_accntng);
	}





	/**
	*		Set Payment made via admin flag
	*
	* 		@access		public
	*		@param		string		$via_admin
	*/
	public function set_payment_made_via_admin( $via_admin = FALSE ) {
		if($via_admin){
			$this->set('PMD_source',  EEM_Payment_Method::scope_admin);
		}else{
			$this->set('PMD_source', EEM_Payment_Method::scope_cart);
		}
		
	}





	/**
	*		Set Payment Details
	*
	* 		@access		public
	*		@param		string		$details
	*/
	public function set_details( $details = FALSE ) {
		if ( is_array( $details )) {
			array_walk_recursive( $details, array( $this, '_strip_all_tags_within_array' ));
		} else {
			$details =  wp_strip_all_tags( $details );
		}
		return $this->set('PAY_details',$details);
	}




	/**
	*		get Payment Transaction ID
	* 		@access		public
	*/
	public function TXN_ID() {
		return $this->get('TXN_ID');
	}



	/**
	*		get Payment Status
	* 		@access		public
	*/
	public function status() {
		return $this->get('STS_ID');
	}
	/**
	*		get Payment Status
	* 		@access		public
	*/
	public function STS_ID() {
		return $this->get('STS_ID');
	}



	/**
	*		get Payment Timestamp
	* 		@access		public
	*/
	public function timestamp( $dt_frmt = FALSE, $tm_frmt = FALSE, $date_or_time = NULL ) {
		return $this->get_datetime('PAY_timestamp', $dt_frmt, $tm_frmt, $date_or_time );
	}



	/**
	*		get Payment Source
	* 		@access		public
	*/
	public function source() {
		return $this->get('PMD_source');
	}



	/**
	*		get Payment Amount
	* 		@access		public
	*/
	public function amount() {
		return $this->get('PAY_amount');
	}
	
	public function amount_no_code(){
		return $this->get_pretty('PAY_amount','no_currency_code');
	}




	/**
	*		get Payment Gateway Response
	* 		@access		public
	*/
	public function gateway_response() {
		return $this->get('PAY_gateway_response');
	}



	/**
	*		get Payment Gateway Transaction ID
	* 		@access		public
	*/
	public function txn_id_chq_nmbr() {
		return $this->get('PAY_txn_id_chq_nmbr');
	}



	/**
	*		get Purchase Order Number
	* 		@access		public
	*/
	public function po_number() {
		return $this->get('PAY_po_number');
	}



	/**
	*		get Extra Accounting Field
	* 		@access		public
	*/
	public function extra_accntng() {
		return $this->get('PAY_extra_accntng'); 
	}



	/**
	*		get Payment made via admin source
	* 		@access		public
	*/
	public function payment_made_via_admin() {
		return ($this->get('PMD_source') == EEM_Payment_Method::scope_admin);
	}



	/**
	*		get Payment Details
	* 		@access		public
	*/
	public function details() {
		return $this->get('PAY_details');
	}
	
	
	/**
	 * returns a pretty version of the status, good for displayign to users
	 * @return string
	 */
	public function pretty_status( $show_icons = FALSE ){
		$status = EEM_Status::instance()->localized_status( array( $this->STS_ID() =>  __( 'unkown', 'event_espresso' )), FALSE, 'sentence' );
		$icon = '';
		switch( $this->STS_ID() ){
			case EEM_Payment::status_id_approved:
				$icon = $show_icons ? '<span class="dashicons dashicons-yes ee-icon-size-24 green-text"></span>' : '';
				break;
			case EEM_Payment::status_id_pending:
				$icon = $show_icons ? '<span class="dashicons dashicons-clock ee-icon-size-16 orange-text"></span>' : '';
				break;
			case EEM_Payment::status_id_cancelled:
				$icon = $show_icons ? '<span class="dashicons dashicons-no ee-icon-size-16 lt-grey-text"></span>' : '';
				break;
			case EEM_Payment::status_id_declined:
				$icon = $show_icons ? '<span class="dashicons dashicons-no ee-icon-size-16 red-text"></span>' : '';
				break;
		}
		return  $icon . $status[ $this->STS_ID() ];
	}
	


	
	/**
	 * echoes $this->pretty_status()
	 * @return void
	 */
	public function e_pretty_status( $show_icons = FALSE ){
		echo $this->pretty_status( $show_icons );
	}
	
	
	
	
	/**
	 * Generally determines if teh status of this payment equals
	 * the $STS_ID string
	 * @param string $STS_ID an ID from the esp_status table/
	 * one of the status_id_* on the EEM_Payment model
	 * @return boolean whether the status of this payment equals the status id
	 */
	protected function status_is($STS_ID){
		if($STS_ID == $this->STS_ID()){
			return true;
		}else{
			return false;
		}
	}
	/**
	 * For determining the statsu of teh payment
	 * @return boolean whether the payment is approved or not
	 */
	public function is_approved(){
		return $this->status_is(EEM_Payment::status_id_approved);
	}
	
	
	
	
	/**
	 * For determining the statsu of teh payment
	 * @return boolean whether the payment is pending or not
	 */
	public function is_pending(){
		return $this->status_is(EEM_Payment::status_id_pending);
	}
	
	
	
	
	/**
	 * For determining the statsu of teh payment
	 * @return boolean
	 */
	public function is_cancelled(){
		return $this->status_is(EEM_Payment::status_id_cancelled);
	}
	
	
	
	/**
	 * For determining the statsu of teh payment
	 * @return boolean
	 */
	public function is_declined(){
		return $this->status_is(EEM_Payment::status_id_declined);
	}
	
	
	
	
	/**
	 * For determining the statsu of teh payment
	 * @return boolean
	 */
	public function is_failed(){
		return $this->status_is(EEM_Payment::status_id_failed);
	}
	
	
	/**
	 * Echoes out the payment overview HTML from the gateway used on this payment
	 */
	public function e_gateway_payment_overview_content(){
		echo $this->gateway_payment_overview_content();
	}
	
	/**
	 * Gets the payment overview content from the gateway used on this payment.
	 * @return string
	 */
	public function gateway_payment_overview_content(){
		$gateway_name = $this->gateway();
		$EEM_Gateways = EEM_Gateways::instance();	
		//call its render payment results, feeding it the current payment
		return $EEM_Gateways->get_payment_overview_content($gateway_name,$this);
	}




	/**
	*		Apply a Payment to a Transaction, update all totals, and save payment info to db
	* 		@param		boolean 		$via_admin
	* 		@access		public
	*/
	public function apply_payment_to_transaction( $via_admin = FALSE ) {		
		if( ! $this->ID()){
			$this->save();
		}
		// recalculate and set  total paid
		require_once(EE_MODELS . 'EEM_Payment.model.php');
		$PAY_MODEL = EEM_Payment::instance( $this->_timezone );
		$success = $PAY_MODEL->update_payment_transaction( $this, 'processed' );
		return $success;
	}





	/**
	*		apply wp_strip_all_tags to all elements within an array
	*
	* 		@access		private
	*		@param		mixed		$item
	*		@param		mixed		$key
	*/
	private function _strip_all_tags_within_array( &$item, $key ){
	        wp_strip_all_tags( $item );
	}
	
	
	
	/**
	 * Get the status object of this object
	 * @return EE_Status
	 */
	public function status_obj(){
		return $this->get_first_related('Status');
	}


	/**
	 * Gets all the extra meta info on this payment
	 * @param array $query_params like EEM_Base::get_all
	 * @return EE_Extra_Meta
	 */
	public function extra_meta($query_params = array()){
		return $this->get_many_related('Extra_Meta', $query_params);
	}


	
	/**
	 * Gets the last-used payment method on this transaction
	 * (we COULD just use the last-made payment, but some payment methods, namely
	 * offline ones, dont' create payments)
	 * @return EE_Payment_Method
	 */
	function payment_method(){
		return $this->get_first_related('Payment_Method');
	}
	
	
	/**
	 * Gets redirect_url
	 * @return string
	 */
	function redirect_url() {
		return $this->get('PAY_redirect_url');
	}

	/**
	 * Sets redirect_url
	 * @param string $redirect_url
	 * @return boolean
	 */
	function set_redirect_url($redirect_url) {
		return $this->set('PAY_redirect_url', $redirect_url);
	}
	/**
	 * Gets redirect_args
	 * @return array
	 */
	function redirect_args() {
		return $this->get('PAY_redirect_args');
	}

	/**
	 * Sets redirect_args
	 * @param array $redirect_args
	 * @return boolean
	 */
	function set_redirect_args($redirect_args) {
		return $this->set('PAY_redirect_args', $redirect_args);
	}
	
	/**
	 * Gets the HTML for redirecting the user to an offsite gateway
	 * You can pass it special content to put inside the form, or use
	 * the default inner content (or possibly generate this all yourself using
	 * redirect_url() and redirect_args() or redirect_args_as_inputs()).
	 * Creates a POST request by default, but if no redirect args are specified, creates a GET request instead.
	 * @param string $inside_form_hmtl
	 * @return string html
	 */
	function redirect_form( $inside_form_hmtl = NULL ) {
		$redirect_url = $this->redirect_url();
		if ( ! empty( $redirect_url )) {
			EE_Registry::instance()->load_helper('Formatter');
			$method = $this->redirect_args() ? 'POST' : 'GET';
			if( $inside_form_hmtl === NULL ) {
				$inside_form_hmtl = EEH_Formatter::nl(0) . '<p style="text-align:center;"><br/>';
				$inside_form_hmtl .= EEH_Formatter::nl(1) . __("If you are not automatically redirected to the payment website within 10 seconds...", 'event_espresso');
				$inside_form_hmtl .= EEH_Formatter::nl(0) . '<br/><br/><input type="submit" value="'.  __('Click Here', 'event_espresso').'">';
				$inside_form_hmtl .= EEH_Formatter::nl(-1) . '</p>';
			}
			
			$form = EEH_Formatter::nl(1) . '<form method="' . $method . '" name="gateway_form" action="' . $redirect_url . '">';
			$form .= EEH_Formatter::nl(1) . $this->redirect_args_as_inputs();
			$form .= $inside_form_hmtl;
			$form .= EEH_Formatter::nl(-1) . '</form>' . EEH_Formatter::nl(-1);
			return $form;
		} else {
			return NULL;
		}

	}
	/**
	 * Changes all the name-value pairs of 
	 * @return string
	 */
	function redirect_args_as_inputs(){
		$html = '';
		if( $this->redirect_args() !== NULL && is_array( $this->redirect_args() )) {
			EE_Registry::instance()->load_helper('Formatter');
			foreach($this->redirect_args() as $name => $value){
				$html .= EEH_Formatter::nl(0) . '<input type="hidden" name="' . $name . '" value="' . esc_attr( $value ) . '"/>';
			}
		}
		return $html;
	}
	/**
	 * Returns the currency of the payment.
	 * (At the time of writing, this will always be the currency in the configuration;
	 * however in the future it is anticipated that this will be stored on the payment
	 * object itself)
	 * @return string for the currency code
	 */
	public function currency_code(){
		return EE_Config::instance()->currency->code;
	}

	

}

/* End of file EE_Payment.class.php */
/* Location: /includes/classes/EE_Payment.class.php */
