<?php
if (!defined('EVENT_ESPRESSO_VERSION'))
	exit('No direct script access allowed');

function edit_event($event_id = 0) {
	global $wpdb, $org_options, $espresso_premium;
	
	$event = new stdClass;
	
	if (!empty($org_options['full_logging']) && $org_options['full_logging'] == 'Y') {
		espresso_log::singleton()->log(array('file' => __FILE__, 'function' => __FUNCTION__, 'status' => ''));
	}
	//This line keeps the notices from displaying twice
	if (did_action('espresso_admin_notices') == false)
		do_action('espresso_admin_notices');
	
	$sql = "SELECT e.*, ev.id as venue_id
		FROM " . EVENTS_DETAIL_TABLE . " e
		LEFT JOIN " . EVENTS_VENUE_REL_TABLE . " vr ON e.id = vr.event_id
		LEFT JOIN " . EVENTS_VENUE_TABLE . " ev ON vr.venue_id = ev.id
		WHERE e.id = %d";
	$event = $wpdb->get_row($wpdb->prepare($sql, $event_id), OBJECT);
	
	//Debug
	//echo "<pre>".print_r($event,true)."</pre>";
	
	$event->event_name = stripslashes_deep($event->event_name);
	$event->event_desc = stripslashes_deep($event->event_desc);
	$event->phone = stripslashes_deep($event->phone);
	$event->externalURL = stripslashes_deep($event->externalURL);
	$event->early_disc = stripslashes_deep($event->early_disc);
	$event->early_disc_date = stripslashes_deep($event->early_disc_date);
	$event->early_disc_percentage = stripslashes_deep($event->early_disc_percentage);
	$event->event_identifier = stripslashes_deep($event->event_identifier);
	$event->start_time = isset($event->start_time) ? $event->start_time : '';
	$event->end_time = isset($event->end_time) ? $event->end_time : '';
	$status = array();
	$status = event_espresso_get_is_active($event->id);
	$event->conf_mail = stripslashes_deep($event->conf_mail);
	$event->send_mail = stripslashes_deep($event->send_mail);
	
	if (function_exists('event_espresso_edit_event_groupon')) {
		$use_groupon_code = $event->use_groupon_code;
	}
	
	$event->address = stripslashes_deep($event->address);
	$event->address2 = stripslashes_deep($event->address2);
	$event->city = stripslashes_deep($event->city);
	$event->state = stripslashes_deep($event->state);
	$event->zip = stripslashes_deep($event->zip);
	$event->country = stripslashes_deep($event->country);
	$event->submitted = $event->submitted != '0000-00-00 00:00:00' ? (empty($event->submitted) ? '' : event_date_display($event->submitted, get_option('date_format')) ) : 'N/A';
	$google_map_link = espresso_google_map_link(array('address' => $event->address, 'city' => $event->city, 'state' => $event->state, 'zip' => $event->zip, 'country' => $event->country));
	$event->question_groups = unserialize($event->question_groups);
	$event->event_meta = unserialize($event->event_meta);

	$values = array(
			array('id' => 'Y', 'text' => __('Yes', 'event_espresso')),
			array('id' => 'N', 'text' => __('No', 'event_espresso')));

	//If user is an event manager, then show only their events
	if (function_exists('espresso_is_my_event') && espresso_is_my_event($event->id) != true) {
		echo '<h2>' . __('Sorry, you do not have permission to edit this event.', 'event_espresso') . '</h2>';
		return;
	}
	?>
	<!--Update event display-->

	<div id="side-info-column" class="inner-sidebar event-espresso_page_events">
	  <div id="side-sortables" class="meta-box-sortables ui-sortable">
			<div id="submitdiv" class="postbox">
				<div class="handlediv" title="Click to toggle"><br />
				</div>
				<h3 class='hndle'> <span>
	<?php _e('Quick Overview', 'event_espresso'); ?>
					</span> </h3>
				<div class="inside">
					<div class="submitbox" id="submitpost">
						<div id="minor-publishing">
							<div id="minor-publishing-actions" class="clearfix">
								<div id="preview-action"> <a class="preview button" href="<?php echo espresso_reg_url($event->id); ?>" target="_blank" id="event-preview" tabindex="5">
	<?php _e('View Event', 'event_espresso'); ?>
									</a>
									<input type="hidden" name="event-preview" id="event-preview" value="" />
								</div>
								<div id="copy-action"> <a class="preview button" href="admin.php?page=events&amp;action=copy_event&event_id=<?php echo $event->id ?>" id="post-copy" tabindex="4" onclick="return confirm('<?php _e('Are you sure you want to copy ' . $event->event_name . '?', 'event_espresso'); ?>')">
	<?php _e('Duplicate Event', 'event_espresso'); ?>
									</a>
									<input  type="hidden" name="event-copy" id="event-copy" value="" />
								</div>
							</div>
							<!-- /minor-publishing-actions -->

							<div id="misc-publishing-actions">
								<div class="misc-pub-section curtime" id="visibility"> <span id="timestamp">
	<?php _e('Start Date', 'event_espresso'); ?>
										<b> <?php echo event_date_display($event->start_date); ?></b> </span> </div>
								<div class="misc-pub-section">
									<label for="post_status">
	<?php _e('Current Status:', 'event_espresso'); ?>
									</label>
									<span id="post-status-display"> <?php echo $status['display']; ?></span> </div>
								<div class="misc-pub-section" id="visibility"> <img src="<?php echo EVENT_ESPRESSO_PLUGINFULLURL ?>images/icons/group.png" width="16" height="16" alt="<?php _e('View Attendees', 'event_espresso'); ?>" /> <?php echo!empty($number_attendees) ? __('Attendees', 'event_espresso') : '<a href="admin.php?page=attendees&amp;event_admin_reports=list_attendee_payments&amp;event_id=' . $event->id . '">' . __('Attendees', 'event_espresso') . '</a>'; ?>: <?php echo get_number_of_attendees_reg_limit($event->id, 'num_attendees_slash_reg_limit'); ?> </div>
								<div class="misc-pub-section <?php echo (function_exists('espresso_is_admin') && espresso_is_admin() == true && $espresso_premium == true) ? '' : 'misc-pub-section-last'; ?>" id="visibility2"> <a href="admin.php?page=attendees&amp;event_admin_reports=event_newsletter&amp;event_id=<?php echo $event->id ?>" title="<?php _e('Email Event Attendees', 'event_espresso'); ?>"><img src="<?php echo EVENT_ESPRESSO_PLUGINFULLURL ?>images/icons/email_go.png" width="16" height="16" alt="<?php _e('Newsletter', 'event_espresso'); ?>" /></a> <a href="admin.php?page=attendees&amp;event_admin_reports=event_newsletter&amp;event_id=<?php echo $event->id ?>" title="<?php _e('Email Event Attendees', 'event_espresso'); ?>">
								<?php _e('Email Event Attendees', 'event_espresso'); ?>
									</a></div>
								<?php
								if (function_exists('espresso_is_admin') && espresso_is_admin() == true && $espresso_premium == true) {

									echo '<div class="misc-pub-section misc-pub-section-last" id="visibility3">';
									echo '<ul>';
									if (function_exists('espresso_manager_list')) {
										echo '<li>' . espresso_manager_list($event->wp_user) . '</li>';
									}
									$event->wp_user = $event->wp_user == $event->event_meta['originally_submitted_by'] ? $event->wp_user : $event->event_meta['originally_submitted_by'];
									$user_name = espresso_user_meta($event->wp_user, 'user_firstname') != '' ? espresso_user_meta($event->wp_user, 'user_firstname') . ' ' . espresso_user_meta($event->wp_user, 'user_lastname') : espresso_user_meta($event->wp_user, 'display_name');
									$user_company = espresso_user_meta($event->wp_user, 'company') != '' ? espresso_user_meta($event->wp_user, 'company') : '';
									$user_organization = espresso_user_meta($event->wp_user, 'organization') != '' ? espresso_user_meta($event->wp_user, 'organization') : '';
									$user_co_org = $user_company != '' ? $user_company : $user_organization;

									echo '<li><strong>' . __('Submitted By:', 'event_espresso') . '</strong> ' . $user_name . '</li>';
									echo '<li><strong>' . __('Email:', 'event_espresso') . '</strong> ' . espresso_user_meta($event->wp_user, 'user_email') . '</li>';
									echo $user_co_org != '' ? '<li><strong>' . __('Organization:', 'event_espresso') . '</strong> ' . espresso_user_meta($event->wp_user, 'company') . '</li>' : '';
									echo '<li><strong>' . __('Date Submitted:', 'event_espresso') . '</strong> ' . $event->submitted . '</li>';
									echo '</ul>';
									echo '</div>';
								}
								?>
							</div>
							<!-- /misc-publishing-actions -->
						</div>
						<!-- /minor-publishing -->

						<div id="major-publishing-actions" class="clearfix">
									<?php if ($event->recurrence_id > 0) : ?>
								<div id="delete-action"> &nbsp; <a class="submitdelete deletion" href="admin.php?page=events&amp;action=delete_recurrence_series&recurrence_id=<?php echo $event->recurrence_id ?>" onclick="return confirm('<?php _e('Are you sure you want to delete ' . $event->event_name . '?', 'event_espresso'); ?>')">
								<?php _e('Delete all events in this series', 'event_espresso'); ?>
									</a> </div>
									<?php else: ?>
								<div id="delete-action"> <a class="submitdelete deletion" href="admin.php?page=events&amp;action=delete&event_id=<?php echo $event->id ?>" onclick="return confirm('<?php _e('Are you sure you want to delete ' . $event->event_name . '?', 'event_espresso'); ?>')">
								<?php _e('Delete Event', 'event_espresso'); ?>
									</a> </div>
								<?php endif; ?>
							<div id="publishing-action">
	<?php wp_nonce_field('espresso_form_check', 'ee_update_event'); ?>
								<input class="button-primary" type="submit" name="Submit" value="<?php _e('Update Event', 'event_espresso'); ?>" id="save_event_setting" />
							</div>
							<!-- /publishing-action -->
						</div>
						<!-- /major-publishing-actions -->
					</div>
					<!-- /submitpost -->
				</div>
				<!-- /inside -->
			</div>
			<!-- /submitdiv -->

			<?php
			$advanced_options = '';
			if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/event-management/advanced_settings.php')) {
				require_once(EVENT_ESPRESSO_PLUGINFULLPATH . "includes/admin-files/event-management/advanced_settings.php");
			} else {
				//Display Lite version options
				$status = array(array('id' => 'A', 'text' => __('Active', 'event_espresso')), array('id' => 'D', 'text' => __('Deleted', 'event_espresso')));
				$advanced_options = '<p><strong>' . __('Advanced Options:', 'event_espresso') . '</strong></p>'
								. '<p><label>' . __('Is this an active event? ', 'event_espresso') . '</label>' . __(select_input('is_active', $values, $event->is_active)) . '</p>'
								. '<p><label>' . __('Display  description? ', 'event_espresso') . '</label>' . select_input('display_desc', $values, $event->display_desc) . '</p>'
								. '<p><label>' . __('Display  registration form? ', 'event_espresso') . '</label>' . select_input('display_reg_form', $values, $event->display_reg_form) . '</p>';
			}//Display Lite version options - End
			postbox('event-status', 'Event Options', '<p class="inputundersmall"><label for"reg-limit">' . __('Attendee Limit: ', 'event_espresso') . ' </label><input id="reg-limit" name="reg_limit"  size="10" type="text" value="' . $event->reg_limit . '" /><br />' .
							'<span>(' . __('leave blank for unlimited', 'event_espresso') . ')</span></p>' .
							'<p class="clearfix" style="clear: both;"><label for="group-reg">' . __('Allow group registrations? ', 'event_espresso') . '</label> ' . select_input('allow_multiple', $values, $event->allow_multiple, 'id="group-reg"') . '</p>' .
							'<p class="inputundersmall"><label for="max-registrants">' . __('Max Group Registrants: ', 'event_espresso') . '</label> <input type="text" id="max-registrants" name="additional_limit" value="' . $event->additional_limit . '" size="4" />' . '</p>' .
							$advanced_options
			);
			//Featured image section
			if (function_exists('espresso_featured_image_event_admin') && $espresso_premium == true) {
				espresso_featured_image_event_admin($event->event_meta);
			}
			/*
			 * Added for seating chart addon
			 */
			if (defined('ESPRESSO_SEATING_CHART')) {
				$seating_chart_id = 0;
				$seating_chart_event = $wpdb->get_row("select * from " . EVENTS_SEATING_CHART_EVENT_TABLE . " where event_id = $event->id");
				if ($seating_chart_event !== NULL) {
					$seating_chart_id = $seating_chart_event->seating_chart_id;
				}
				?>
				<div style="display: block;" id="seating_chart-options" class="postbox">
					<div class="handlediv" title="Click to toggle"><br />
					</div>
					<h3 class="hndle"><span>
		<?php _e('Seating chart', 'event_espresso'); ?>
						</span></h3>
					<div class="inside">
						<p>
							<select name="seating_chart_id" id="seating_chart_id" style="float:none; width:200px;" class="chzn-select">
								<option value="0" <?php if ($seating_chart_id == 0) {
			echo 'selected="selected"';
		} ?> >None</option>
								<?php
								$seating_charts = $wpdb->get_results("select * from " . EVENTS_SEATING_CHART_TABLE . " order by name");
								foreach ($seating_charts as $seating_chart) {
									?>
									<option value="<?php echo $seating_chart->id; ?>" <?php if ($seating_chart_id == $seating_chart->id) {
							echo 'selected="selected"';
						} ?> ><?php echo $seating_chart->name; ?></option>
			<?php
		}
		?>
							</select>
						</p>
					</div>
				</div>
				<?php
			}
			/*
			 * End
			 */


			###### Modification by wp-developers to introduce attendee pre-approval requirement ##########
			if ($org_options['use_attendee_pre_approval'] == 'Y' && $espresso_premium == true) {
				?>
				<div id="attendee-pre-approval-options" class="postbox">
					<div class="handlediv" title="Click to toggle"><br />
					</div>
					<h3 class="hndle"> <span>
							<?php _e('Attendee pre-approval required?', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<p class="pre-approve">
		<?php
		$pre_approval_values = array(array('id' => '1', 'text' => __('Yes', 'event_espresso')), array('id' => '0', 'text' => __('No', 'event_espresso')));
		echo select_input("require_pre_approval", $pre_approval_values, $event->require_pre_approval);
		?>
						</p>
					</div>
				</div>
				<?php
			}
			########## END #################################

			if (function_exists('espresso_ticket_dd') && $espresso_premium == true) {
				?>
				<div  id="ticket-options" class="postbox">
					<div class="handlediv" title="Click to toggle"><br>
					</div>
					<h3 class="hndle"> <span>
		<?php _e('Custom Tickets', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<p><?php echo espresso_ticket_dd($event->ticket_id); ?></p>
					</div>
				</div>
				<!-- /ticket-options -->
		<?php
	}

	if (function_exists('espresso_certificate_dd') && $espresso_premium == true) {
		?>
				<div  id="certificate-options" class="postbox">
					<div class="handlediv" title="Click to toggle"><br>
					</div>
					<h3 class="hndle"> <span>
		<?php _e('Custom Certificates', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<p><?php echo espresso_certificate_dd($event->certificate_id); ?></p>
					</div>
				</div>
				<!-- /certificate-options -->
		<?php
	}

	if (get_option('events_members_active') == 'true' && $espresso_premium == true) {
		?>
				<div  id="member-options" class="postbox">
					<div class="handlediv" title="Click to toggle"><br>
					</div>
					<h3 class="hndle"> <span>
		<?php _e('Member Options', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<p><?php echo event_espresso_member_only($event->member_only); ?></p>
					</div>
				</div>
				<!-- /member-options -->
				<?php
			}

			if (get_option('event_mailchimp_active') == 'true' && $espresso_premium == true) {
				MailChimpView::event_list_selection();
			}
			?>
	<?php if (function_exists('espresso_fb_createevent') && $espresso_premium == true) { ?>
		<?php
		$eventstable = $wpdb->prefix . "fbevents_events";
		$fb_e_id = $wpdb->get_var("SELECT fb_event_id FROM $eventstable WHERE event_id='{$event->id}'");
		?>
				<div  id="event-meta" class="postbox">
					<div class="handlediv" title="Click to toggle"><br>
					</div>
					<h3 class="hndle"> <span>
						<?php _e('Post to Facebook', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<input type="checkbox" name="espresso_fb" id="espresso_fb" <?php echo ($fb_e_id ? "CHECKED=TRUE" : null); ?>/>
						<?php _e('Post to Facebook', 'event_espresso'); ?>
						<?php if (!empty($fb_e_id)) { ?>
							<a href="http://www.facebook.com/event.php?eid=<?php echo $fb_e_id; ?>"
								 target="_blank">
					<?php _e('Event Page On Facebook', 'event_espresso'); ?>
							</a>
		<?php } ?>
					</div>
				</div>
						<?php } ?>
			<div  id="event-categories" class="postbox">
				<div class="handlediv" title="Click to toggle"><br>
				</div>
				<h3 class="hndle"> <span>
			<?php _e('Event Category', 'event_espresso'); ?>
					</span> </h3>
				<div class="inside"> <?php echo event_espresso_get_categories($event->id); ?> </div>
			</div>
			<!-- /event-category -->

			<?php
			if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/event-management/promotions_box.php')) {
				require_once(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/event-management/promotions_box.php');
			}
			?>
			<!-- /event-promotions -->
						<?php if (get_option('events_groupons_active') == 'true' && $espresso_premium == true) {
							?>
				<div id="groupon-options" class="postbox">
					<div class="handlediv" title="Click to toggle"><br>
					</div>
					<h3 class="hndle"> <span>
		<?php _e('Groupon Options', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<p><?php echo event_espresso_edit_event_groupon($use_groupon_code); ?></p>
					</div>
				</div>
				<!-- /groupon-options -->
			<?php }

			echo espresso_event_question_groups($event->question_groups, $event->event_meta['add_attendee_question_groups'], $event->id) ?>
			<!-- /event-questions -->

	<?php
	if (function_exists('espresso_personnel_cb') && $org_options['use_personnel_manager'] == 'Y' && $espresso_premium == true) {
		?>
				<div id="event-staff" class="postbox">
					<div class="handlediv" title="Click to toggle"><br>
					</div>
					<h3 class="hndle"> <span>
				<?php _e('Event Staff / Speakers', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside"> <?php echo espresso_personnel_cb($event->id, $event->event_meta['originally_submitted_by'], unserialize($event->event_meta['orig_event_staff'])); ?> </div>
				</div>
		<?php
	}
	?>

	  </div>
	  <!-- /side-sortables -->
	</div>
	<!-- /side-info-column -->

	<!-- Left Column -->
	<div id="post-body">
	  <div id="post-body-content">
			<div id="titlediv"> <strong>
						<?php _e('Event Title', 'event_espresso'); ?>
				</strong>
				<div id="titlewrap">
					<label class="screen-reader-text" for="title">
	<?php _e('Event Title', 'event_espresso'); ?>
					</label>
					<input type="text" name="event" size="30" tabindex="1" value="<?php echo $event->event_name; ?>" id="title" autocomplete="off" />
				</div>
				<!-- /titlewrap -->
				<div class="inside">
					<div id="edit-slug-box"> <strong>
	<?php _e('Unique Event Identifier:', 'event_espresso'); ?>
						</strong>
						<input disabled="disabled" type="text" size="30" tabindex="2" name="event_identifier" id="event_identifier" value ="<?php echo $event->event_identifier; ?>" />
	<?php echo '<a href="#" class="button" onclick="prompt(&#39;Event Shortcode:&#39;, \'[SINGLEEVENT single_event_id=&#34;\' + jQuery(\'#event_identifier\').val() + \'&#34;]\'); return false;">' . __('Shortcode') . '</a>' ?> <?php echo '<a href="#" class="button" onclick="prompt(&#39;Short URL:&#39;, \'' . espresso_reg_url($event->id) . '\'); return false;">' . __('Short URL') . '</a>' ?> <?php echo '<a href="#" class="button" onclick="prompt(&#39;Full URL:&#39;, \'' . home_url() . '/?page_id=' . $org_options['event_page_id'] . '&amp;regevent_action=register&amp;event_id=' . $event->id . '\'); return false;">' . __('Full URL') . '</a>' ?> </div>
					<!-- /edit-slug-box -->
				</div>
				<!-- /.inside -->
			</div>
			<!-- /titlediv -->

			<div id="descriptiondivrich" class="postarea"> <strong>
				<?php _e('Event Description', 'event_espresso'); ?>
				</strong>
				<?php
				/*
				  This is the editor used by WordPress. It is very very hard to find documentation for this thing, so I pasted everything I could find below.
				  param: string $content Textarea content.
				  param: string $id Optional, default is 'content'. HTML ID attribute value.
				  param: string $prev_id Optional, default is 'title'. HTML ID name for switching back and forth between visual editors.
				  param: bool $media_buttons Optional, default is true. Whether to display media buttons.
				  param: int $tab_index Optional, default is 2. Tabindex for textarea element.
				 */
				//the_editor($content, $id = 'content', $prev_id = 'title', $media_buttons = true, $tab_index = 2)
				the_editor(espresso_admin_format_content($event->event_desc), $id = 'event_desc'/* , $prev_id = 'title', $media_buttons = true, $tab_index = 3 */);
				?>
				<table id="post-status-info" cellspacing="0">
					<tbody>
						<tr>
							<td id="wp-word-count"></td>
							<td class="autosave-info"><span id="autosave">&nbsp;</span></td>
						</tr>
					</tbody>
				</table>
			</div>
			<!-- /postdivrich -->

			<div id="normal-sortables" class="meta-box-sortables ui-sortable">
				<div style="display: block;" id="event-date-time" class="postbox">
					<div class="handlediv" title="Click to toggle"><br>
					</div>
					<h3 class="hndle"> <span>
	<?php _e('Event Date/Times', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<table width="100%" border="0" cellpadding="5">
							<tr valign="top">
								<td class="a"><fieldset id="add-reg-dates">
										<legend>
	<?php _e('Registration Dates', 'event_espresso'); ?> <?php echo apply_filters('espresso_help', 'reg_date_info'); ?>
										</legend>
										<p>
											<label for="registration_start"> <?php echo __('Registration Start:', 'event_espresso') ?></label>
											<input type="text" class="datepicker" size="15" id="registration_start" name="registration_start"  value="<?php echo $event->registration_start ?>" />
										</p>
										<p>
											<label for="registration_end"><?php echo __('Registration End:', 'event_espresso') ?></label>
											<input type="text" class="datepicker" size="15" id="registration_end" name="registration_end"  value="<?php echo $event->registration_end ?>" />
										</p>
									</fieldset>
									<fieldset>
										<legend>
	<?php _e('Event Dates', 'event_espresso'); ?> <?php echo apply_filters('espresso_help', 'event_date_info'); ?>
										</legend>
										<p>
											<label for="start_date"><?php echo __('Event Start Date', 'event_espresso') ?></label>
											<input type="text" class="datepicker" size="15" id="start_date" name="start_date" value="<?php echo $event->start_date ?>" />
										</p>
										<p>
											<label for="end_date"><?php echo __('Event End Date', 'event_espresso') ?></label>
											<input type="text" class="datepicker" size="15" id="end_date" name="end_date" value="<?php echo $event->end_date ?>" />
										</p>
									</fieldset>
											<?php if ((!isset($org_options['use_event_timezones']) || $org_options['use_event_timezones'] != 'Y') && $espresso_premium == true) { ?>
										<p><span class="run-in">
										<?php _e('Current Time:', 'event_espresso'); ?>
											</span> <span class="current-date"> <?php echo date(get_option('date_format')) . ' ' . date(get_option('time_format')); ?></span> <?php echo apply_filters('espresso_help', 'current_time_info'); ?>
											<a class="change-date-time" href="options-general.php" target="_blank">
		<?php _e('Change timezone and date format settings?', 'event_espresso'); ?>
											</a></p>
												<?php } ?>
												<?php if (isset($org_options['use_event_timezones']) && $org_options['use_event_timezones'] == 'Y' && $espresso_premium == true) { ?>
										<fieldset id="event-timezone">
											<p>
												<label>
										<?php _e('Event Timezone:', 'event_espresso') ?>
												</label>
		<?php echo eventespresso_ddtimezone($event->id) ?></p>
										</fieldset>
											<?php } ?></td>
										<?php // ADD TIME REGISTRATION   ?>
								<td class="b"><fieldset id="add-register-times">
										<legend>
	<?php _e('Registration Times', 'event_espresso'); ?> <?php echo apply_filters('espresso_help', 'reg_date_info'); ?>
										</legend>
											<?php echo event_espresso_timereg_editor($event->id); ?>
									</fieldset>
									<fieldset id="add-event-times">
										<legend>
	<?php _e('Event Times', 'event_espresso'); ?> <?php echo apply_filters('espresso_help', 'event_times_info'); ?>
										</legend>
	<?php echo event_espresso_time_editor($event->id); ?>
									</fieldset>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<?php
				/**
				 * Load the recurring events form if the add-on has been installed.	*
				 */
				if (get_option('event_espresso_re_active') == 1 && $espresso_premium == true) {
					require_once(EVENT_ESPRESSO_RECURRENCE_FULL_PATH . "functions/re_view_functions.php");
					//For now, only the recurring events will show the form
					if ($event->recurrence_id > 0)
						event_espresso_re_form($event->recurrence_id);
				}
				?>
				<div id="event-pricing" class="postbox">
				<?php (get_option('events_members_active') == 'true')? $members_active = 'class="members-active"' : $members_active = ''; ?>
					<div class="handlediv" title="Click to toggle"><br>
					</div>
					<h3 class="hndle"> <span>
	<?php _e('Event Pricing', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<table <?php echo $members_active ?> width="100%" border="0" cellpadding="5">
							<tr valign="top">
								<td id="standard-pricing" class="a"><?php event_espresso_multi_price_update($event->id); //Standard pricing ?></td>
								<?php
								//If the members addon is installed, define member only event settings
								if (get_option('events_members_active') == 'true' && $espresso_premium == true) {
									?>
									<td id="member-pricing" class="b"><?php echo event_espresso_member_only_pricing($event->id); //Show the the member only pricing options.  ?></td>
	<?php } ?>
							</tr>
						</table>
					</div>
				</div>
				<h2>
	<?php _e('Advanced Options', 'event_espresso'); ?>
				</h2>
				<div id="event-location" class="postbox">
					<div class="handlediv" title="Click to toggle"><br />
					</div>
					<h3 class="hndle"> <span>
	<?php _e('Additional Event/Venue Information', 'event_espresso'); ?>
						</span> </h3>
					<div class="inside">
						<table width="100%" border="0" cellpadding="5">
							<tr valign="top">

	<?php
	if (function_exists('espresso_venue_dd') && $org_options['use_venue_manager'] == 'Y' && $espresso_premium == true) {
		$ven_type = 'class="use-ven-manager"';
		?>
									<td <?php echo $ven_type ?>><fieldset id="venue-manager">
											<legend><?php echo __('Venue Information', 'event_espresso') ?></legend>
											<?php if (!espresso_venue_dd()) : ?>
												<p class="info"><b>
												<?php _e('You have not created any venues yet.', 'event_espresso'); ?>
													</b></p>
												<p><a href="admin.php?page=event_venues"><?php echo __('Add venues to the Venue Manager', 'event_espresso') ?></a></p>
									<?php else: ?>
										<?php echo espresso_venue_dd($event->venue_id) ?>
									<?php endif; ?>
										</fieldset></td>
		<?php
	} else {
		$ven_type = 'class="manual-venue"';
		?>
									<td <?php echo $ven_type ?>><fieldset>
											<legend>
													<?php _e('Physical Location', 'event_espresso'); ?>
											</legend>
											<p>
												<label for="phys-addr">
		<?php _e('Address:', 'event_espresso'); ?>
												</label>
												<input size="20" id="phys-addr" tabindex="100"  type="text"  value="<?php echo $event->address ?>" name="address" />
											</p>
											<p>
												<label for="phys-addr-2">
		<?php _e('Address 2:', 'event_espresso'); ?>
												</label>
												<input size="20" id="phys-addr-2" tabindex="101"  type="text"  value="<?php echo $event->address2 ?>" name="address2" />
											</p>
											<p>
												<label for="phys-city">
		<?php _e('City:', 'event_espresso'); ?>
												</label>
												<input size="20" id="phys-city" tabindex="102"  type="text"  value="<?php echo $event->city ?>" name="city" />
											</p>
											<p>
												<label for="phys-state">
		<?php _e('State:', 'event_espresso'); ?>
												</label>
												<input size="20" id="phys-state" tabindex="103"  type="text"  value="<?php echo $event->state ?>" name="state" />
											</p>
											<p>
												<label for="zip-postal">
		<?php _e('Zip/Postal Code:', 'event_espresso'); ?>
												</label>
												<input size="20" id="zip-postal"  tabindex="104"  type="text"  value="<?php echo $event->zip ?>" name="zip" />
											</p>
											<p>
												<label for="phys-country">
												<?php _e('Country:', 'event_espresso'); ?>
												</label>
												<input size="20" id="phys-country" tabindex="105"  type="text"  value="<?php echo $event->country ?>" name="country" />
											</p>
											<p>
		<?php _e('Google Map Link (for email):', 'event_espresso'); ?>
												<br />
		<?php echo $google_map_link; ?> </p>
										</fieldset></td>
									<td <?php echo $ven_type; ?>>

										<fieldset>

											<legend>
													<?php _e('Venue Information', 'event_espresso'); ?>
											</legend>
											<p>
												<label for="ven-title">
		<?php _e('Title:', 'event_espresso'); ?>
												</label>
												<input size="20"id="ven-title" tabindex="106"  type="text"  value="<?php echo stripslashes_deep($event->venue_title) ?>" name="venue_title" />
											</p>
											<p>
												<label for="ven-website">
		<?php _e('Website:', 'event_espresso'); ?>
												</label>
												<input size="20" id="ven-website" tabindex="107"  type="text"  value="<?php echo stripslashes_deep($event->venue_url) ?>" name="venue_url" />
											</p>
											<p>
												<label for="ven-phone">
		<?php _e('Phone:', 'event_espresso'); ?>
												</label>
												<input size="20" id="ven-phone" tabindex="108"  type="text"  value="<?php echo stripslashes_deep($event->venue_phone) ?>" name="venue_phone" />
											</p>
											<p>
												<label for="ven-image">
											<?php _e('Image:', 'event_espresso'); ?>
												</label>
												<input size="20" id="ven-image" tabindex="110"  type="text"  value="<?php echo stripslashes_deep($event->venue_image) ?>" name="venue_image" />
											</p>
											<?php } ?>
								</td>

								<td <?php echo $ven_type ?>><fieldset id="virt-location">
										<legend>
												<?php _e('Virtual Location', 'event_espresso'); ?>
										</legend>
										<p>
											<label for="virt-phone">
	<?php _e('Phone:', 'event_espresso'); ?>
											</label>
											<input size="20" id="virt-phone" type="text" tabindex="111" value="<?php echo $event->phone ?>" name="phone" />
										</p>
										<p>
											<label for="url-event">
	<?php _e('URL of Event:', 'event_espresso'); ?>
											</label>
											<textarea id="url-event" cols="30" rows="4" tabindex="112"  name="virtual_url"><?php echo stripslashes_deep($event->virtual_url) ?></textarea>
										</p>
										<p>
											<label for="call-in-num">
	<?php _e('Call in Number:', 'event_espresso'); ?>
											</label>
											<input id="call-in-num" size="20" tabindex="113"  type="text"  value="<?php echo stripslashes_deep($event->virtual_phone) ?>" name="virtual_phone" />
										</p>
									</fieldset></td>
							</tr>

						</table>
						<p>
							<label for="enable_for_gmap"> <?php _e('Enable event address in Google Maps? ', 'event_espresso') ?></label>  <?php echo select_input('enable_for_gmap', $values, isset($event->event_meta['enable_for_gmap']) ? $event->event_meta['enable_for_gmap'] : '', 'id="enable_for_gmap"') ?>
						</p>
					</div>
				</div>
				<!-- /event-location-->
							<?php if ($espresso_premium == true) { ?>
					<div id="event-meta" class="postbox">
						<div class="handlediv" title="Click to toggle"><br>
						</div>
						<h3 class="hndle"> <span>
		<?php _e('Event Meta', 'event_espresso'); ?>
							</span> </h3>
						<div class="inside">
		<?php event_espresso_meta_edit($event->event_meta); ?>
						</div>
					</div>
	<?php } ?>
				<!-- /event-meta-->
				<div id="confirmation-email" class="postbox">
					<div class="handlediv" title="Click to toggle"><br />
					</div>
					<h3 class="hndle">
						<span>
	<?php _e('Email Confirmation:', 'event_espresso') ?>
						</span>
					</h3>
					<div class="inside">
						<div id="emaildescriptiondivrich" class="postarea">
							<div class="email-conf-opts">
								<p class="inputunder"><label><?php echo __('Send custom confirmation emails for this event?', 'event_espresso') ?> <?php echo apply_filters('espresso_help', 'custom_email_info') ?> </label> <?php echo select_input('send_mail', $values, $event->send_mail); ?> </p>
								<p class="inputunder">
									<label>
										<?php _e('Use a ', 'event_espresso'); ?>
										<a href="admin.php?page=event_emails" target="_blank">
									<?php _e('pre-existing email? ', 'event_espresso'); ?>
										</a>
	<?php echo apply_filters('espresso_help', 'email_manager_info') ?>
									</label>
	<?php echo espresso_db_dropdown('id', 'email_name', EVENTS_EMAIL_TABLE, 'email_name', $event->email_id, 'desc') ?>
								</p>

								<p>
									<em>OR</em>
								</p>
								<p>
										<?php _e('Create a custom email:', 'event_espresso') ?>  <?php echo apply_filters('espresso_help', 'event_custom_emails'); ?>
								</p>
							</div>
							<div class="visual-toggle">
								<p><a class="toggleVisual">
	<?php _e('Visual', 'event_espresso'); ?>
									</a> <a class="toggleHTML">
	<?php _e('HTML', 'event_espresso'); ?>
									</a></p>
							</div>
							<div class="postbox">
								<textarea name="conf_mail" class="theEditor" id="conf_mail"><?php echo espresso_admin_format_content($event->conf_mail); ?></textarea>
								<table id="email-confirmation-form" cellspacing="0">
									<tr>
										<td class="aer-word-count"></td>
										<td class="autosave-info"><span><a class="thickbox" href="#TB_inline?height=300&width=400&inlineId=custom_email_info">
	<?php _e('View Custom Email Tags', 'event_espresso'); ?>
												</a> | <a class="thickbox" href="#TB_inline?height=300&width=400&inlineId=custom_email_example">
	<?php _e('Email Example', 'event_espresso'); ?>
												</a></span></td>
									</tr>
								</table>
							</div>
						</div>
					</div>
				</div>
				<!-- /confirmation-email-->
				<?php
				if (file_exists(EVENT_ESPRESSO_PLUGINFULLPATH . 'includes/admin-files/event-management/edit_event_post.php')) {
					require_once(EVENT_ESPRESSO_PLUGINFULLPATH . "includes/admin-files/event-management/edit_event_post.php");
				}
				?>
			</div>
			<!-- /normal-sortables-->
	  </div>
	  <!-- /post-body-content -->
	<?php include_once('create_events_help.php'); ?>
	</div>
	<?php global $event_thumb; ?>
	<!-- /post-body -->
	<input type="hidden" name="edit_action" value="update">
	<input type="hidden" name="date_submitted" value="<?php echo $event->submitted; ?>">
	<input type="hidden" name="recurrence_id" value="<?php echo $event->recurrence_id; ?>">
	<input type="hidden" name="action" value="edit">
	<input type="hidden" name="event_id" value="<?php echo $event->id ?>">
	<input type="hidden" name="originally_submitted_by" value="<?php echo!empty($event->event_meta['originally_submitted_by']) ? $event->event_meta['originally_submitted_by'] : $event->wp_user ?>">
	<script type="text/javascript" charset="utf-8">

		//<![CDATA[
		jQuery(document).ready(function() {

			postboxes.add_postbox_toggles('events');

			jQuery(".datepicker" ).datepicker({
				changeMonth: true,
				changeYear: true,
				dateFormat: "yy-mm-dd",
				showButtonPanel: true
			}); // close doc.ready

			var header_clicked = false;
			jQuery('#upload_image_button').click(function() {
				formfield = jQuery('#upload_image').attr('name');
				tb_show('', 'media-upload.php?type=image&amp;TB_iframe=1');
				jQuery('p.event-featured-thumb').addClass('old');
				header_clicked = true;
				return false;
			});

	<?php if (function_exists('espresso_featured_image_event_admin') && $espresso_premium == true) { ?>
						window.original_send_to_editor = window.send_to_editor;

						window.send_to_editor = function(html) {
							if(header_clicked) {
								imgurl = jQuery('img',html).attr('src');
								jQuery('#' + formfield).val(imgurl);
								jQuery('#featured-image').append("<p id='image-display'><img class='show-selected-image' src='"+imgurl+"' alt='' /></p>");
								header_clicked = false;
								tb_remove();

							} else {
								window.original_send_to_editor(html);
							}
						}

						// process the remove link in the metabox
						jQuery('#remove-image').click(function(){
							confirm('<?php _e('Do you really want to delete this image? Please remember to update your event to complete the removal.', 'event_espresso'); ?>');
							jQuery("#upload_image").val('');
							jQuery("p.event-featured-thumb").remove();
							jQuery("p#image-display").remove();
							jQuery('#remove-image').remove();
							jQuery("#show_thumb_in_lists, #show_on_calendar, #show_thumb_in_regpage").val('N');
						});
		<?php
	}
	?>
					});
					//]]>
	</script>
	<?php
	espresso_tiny_mce();
}

