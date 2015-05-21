<?php if ( ! defined('EVENT_ESPRESSO_VERSION')) exit('No direct script access allowed');
/**
 * Event Attendees shortcode class
 *
 * @package			Event Espresso
 * @subpackage		/shortcodes/
 * @author			Darren Ethier
 * @since           4.6.29
 *
 * ------------------------------------------------------------------------
 */
class EES_Espresso_Event_Attendees  extends EES_Shortcode {


	/**
	 * run - initial module setup
	 *
	 * @access    public
	 * @param       WP $WP
	 * @return    void
	 */
	public function run( WP $WP ) {}


	/**
	 * 	set_hooks - for hooking into EE Core, modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks() {
	}

	/**
	 * 	set_hooks_admin - for hooking into EE Admin Core, modules, etc
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public static function set_hooks_admin() {
	}



	/**
	 * 	process_shortcode - ESPRESSO_EVENT_ATTENDEES - Returns a list of attendees to an event.
	 *
	 *
	 *
	 * 	[ESPRESSO_EVENT_ATTENDEES] - defaults to attendees for earliest active event, or earliest upcoming event.
	 * 	[ESPRESSO_EVENT_ATTENDEES event_id=123] - attendees for specific event.
	 * 	[ESPRESSO_EVENT_ATTENDEES datetime_id=245] - attendees for a specific datetime.
	 * 	[ESPRESSO_EVENT_ATTENDEES ticket_id=123] - attendees for a specific ticket.
	 * 	[ESPRESSO_EVENT_ATTENDEES status=all] - specific registration status (use status id) or all for all attendees
	 *                                          regardless of status.  Note default is to only return approved attendees
	 * 	[ESPRESSO_EVENT_ATTENDEES show_gravatar=true] - default is to not return gravatar.  Otherwise if this is set
	 *                                                  then return gravatar for email address given.
	 *
	 *  Note: because of the relationship between event_id, ticket_id, and datetime_id. If more than one of those params
	 *  is included then preference is given to the following:
	 *  - event_id is used whenever its present and any others are ignored.
	 *  - if no event_id then datetime is used whenever its present and any others are ignored.
	 *  - otherwise ticket_id is used if present.
	 *
	 *  @access 	public
	 *  @param 	    array 	$attributes
	 *  @return 	string
	 */
	public function process_shortcode( $attributes = array() ) {

		//load helpers
		EE_Registry::instance()->load_helper( 'Event_View' );
		EE_Registry::instance()->load_helper( 'Template' );

		// merge in any attributes passed via fallback shortcode processor
		$attributes = array_merge( (array) $attributes, (array) $this->_attributes );

		//set default attributes
		$default_shortcode_attributes = array(
			'event_id' => NULL,
			'datetime_id' => NULL,
			'ticket_id' => NULL,
			'status' => 'RAP',
			'show_gravatar' => false
		);

		// allow the defaults to be filtered
		$default_shortcode_attributes = apply_filters( 'EES_Espresso_Event_Attendees__process_shortcode__default_shortcode_atts', $default_shortcode_attributes );
		// grab attributes and merge with defaults, then extract
		$attributes = array_merge( $default_shortcode_attributes, $attributes );

		$template_args = array(
			'contacts' => array(),
			'event' => null,
			'datetime' => null,
			'ticket' => null,
			'show_gravatar' => $attributes['show_gravatar']
		);

		//start setting up the query for the contacts
		$query = array();

		//what event?
		if ( empty( $attributes['event_id'] ) && empty( $attributes['datetime_id'] ) && empty( $attributes['ticket_id'] ) ) {
			//seems like is_espresso_event_single() isn't working as expected. So using alternate method.
			if ( is_single() && is_espresso_event() ) {
				$event = EEH_Event_View::get_event();
				if ( $event instanceof EE_Event ) {
					$template_args['event'] = $event;
					$query[0]['Registration.EVT_ID'] = $event->ID();
				}
			} else {
				//try getting the earliest active event if none then get the
				$event = EEM_Event::instance()->get_active_events( array( 'limit' => 1, 'order_by' => array( 'Datetime.DTT_EVT_start' => 'ASC' ) ) );
				$event = empty( $event ) ? EEM_Event::instance()->get_upcoming_events( array( 'limit' => 1, 'order_by' => array( 'Datetime.DTT_EVT_start' => 'ASC') ) ) : null;
				$event = reset( $event );
				if ( $event instanceof EE_Event ) {
					$query[0]['Registration.EVT_ID'] = $event->ID();
					$template_args['event'] = $event;
				}
			}
		} else {
			$event = EEM_Event::instance()->get_one_by_ID( $attributes['event_id'] );
			if ( $event instanceof EE_Event ) {
				$query[0]['Registration.EVT_ID'] = $attributes['event_id'];
				$template_args['event']          = $event;
			}
		}

		//datetime?
		if ( ! empty( $attributes['datetime_id'] ) && empty( $attributes['event_id'] ) ) {
			$datetime = EEM_Datetime::instance()->get_one_by_ID( $attributes['datetime_id'] );
			if ( $datetime instanceof EE_Datetime ) {
				$query[0]['Registration.Event.Datetime.DTT_ID'] = $attributes['datetime_id'];
				$template_args['datetime'] = $datetime;
				$template_args['event'] = $datetime->event();
			}
		}

		//ticket?
		if ( ! empty( $attributes['ticket_id'] ) && empty( $attributes['event_id'] ) && empty( $attributes['datetime_id'] ) ) {
			$ticket = EEM_Ticket::instance()->get_one_by_ID( $attributes['ticket_id'] );
			if ( $ticket instanceof EE_Ticket ) {
				$query[0]['Registration.TKT_ID'] = $attributes['ticket_id'];
				$template_args['ticket'] = $ticket;
				$template_args['event'] = $ticket->first_datetime() instanceof EE_Datetime ? $ticket->first_datetime()->event() : null;
			}
		}

		//status
		$query[0]['Registration.STS_ID'] = $attributes['status'];
		$query['group_by'] = array( 'ATT_ID' );

		//get contacts!
		$template_args['contacts'] = EEM_Attendee::instance()->get_all( $query );


		//all set let's load up the template and return.
		return EEH_Template::locate_template( 'loop-espresso_attendees-shortcode.php', $template_args, true, true );

	}


} //end EES_Espresso_Event_Attendees