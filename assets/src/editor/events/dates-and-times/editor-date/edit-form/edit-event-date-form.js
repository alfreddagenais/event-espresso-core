/**
 * External imports
 */
import { isEmpty } from 'lodash';
import { Component } from '@wordpress/element';
import {
	withFormHandler,
	twoColumnAdminFormLayout,
} from '@eventespresso/components';
import { __ } from '@eventespresso/i18n';
import { dateTimeModel } from '@eventespresso/model';
import { isModelEntityOfModel } from '@eventespresso/validators';

/**
 * Internal dependencies
 */
import { eventDateEntityFormInputs } from './event-date-entity-form-inputs';

/**
 * @function
 * @param {Object} eventDate model object defining the Event Date
 */
class EditEventDateForm extends Component {
	render() {
		const {
			eventDate,
			submitButton,
			cancelButton,
			currentValues = {},
			initialValues = {},
			newObject = false,
		} = this.props;
		// edit forms for existing objects must have initial values
		if (
			( ! newObject && isEmpty( initialValues ) ) ||
			isEmpty( currentValues )
		) {
			return null;
		}
		const {
			FormInput,
			FormSection,
			FormWrapper,
			FormSaveCancelButtons,
			FormInfo,
		} = twoColumnAdminFormLayout;

		// entity properties we don't want to be editable
		const exclude = [
			'EVT_ID',
			'sold',
			'reserved',
			'order',
			'parent',
			'deleted',
		];
		const formRows = eventDateEntityFormInputs(
			eventDate,
			exclude,
			currentValues,
			FormInput
		);
		if ( Array.isArray( formRows ) ) {
			formRows.unshift(
				<FormInfo
					key="formInfo"
					formInfo={
						__(
							'all fields marked with an asterisk are required',
							'event_espresso'
						)
					}
					dismissable={ false }
				/>
			);
		}
		const { MODEL_NAME: DATETIME } = dateTimeModel;
		return isModelEntityOfModel( eventDate, DATETIME ) ? (
			<FormWrapper>
				<FormSection
					htmlId={
						`ee-event-date-editor-${ eventDate.id }-form-section`
					}
					children={ formRows }
				/>
				<FormSaveCancelButtons
					htmlClass={ `ee-event-date-editor-${ eventDate.id }` }
					submitButton={ submitButton }
					cancelButton={ cancelButton }
				/>
			</FormWrapper>
		) : null;
	}
}

/**
 * Enhanced EditEventDateForm with FormHandler
 */
export default withFormHandler( EditEventDateForm );