/**
 * External imports
 */
import { useEffect } from '@wordpress/element';
import {
	EntityList,
	EntityPagination,
	twoColumnAdminFormLayout,
	useEntityListFilterState,
	useEntityPagination,
} from '@eventespresso/components';
import { __, _x, sprintf } from '@eventespresso/i18n';

/**
 * Internal dependencies
 */
import AddNewDateEntityButton from './add-new-date-entity-button';
import {
	DatesListFilterBar,
	useDatesListFilterState,
	useDatesListFilterStateSetters,
	useFilteredDatesList,
} from './filter-bar';
import { EditorDateEntitiesGridView } from './grid-view';
import { EditorDateEntitiesListView } from './list-view';
import useEventEditorEventDates
	from '../../hooks/use-event-editor-event-dates';
import EditAllTicketAssignmentsButton
	from '../../ticket-assignments-manager/edit-all-ticket-assignments-button';

const {
	FormWrapper,
	FormSaveCancelButtons,
} = twoColumnAdminFormLayout;

/**
 * EditorDateEntitiesList
 *
 * displays a paginated list of event dates with a filter bar
 * for controlling how and what event dates are displayed
 *
 * @param {Object} otherProps
 * @return {Object} rendered event dates list
 */
const EditorDateEntitiesList = ( { ...otherProps } ) => {
	const listId = 'event-editor-dates-list';
	const { eventDates } = useEventEditorEventDates();
	const eventDatesLoaded = Array.isArray( eventDates ) &&
		eventDates.length >
		0;
	const {
		showDates,
		datesSortedBy,
		displayDates,
	} = useDatesListFilterState( { listId } );
	const {
		view,
		perPage,
		...entityListFilters
	} = useEntityListFilterState( { listId } );
	const filteredDates = useFilteredDatesList( {
		listId,
		showDates,
		datesSortedBy,
		displayDates,
		dateEntities: eventDates,
		...entityListFilters,
	} );
	const {
		currentPage,
		setCurrentPage,
		paginatedEntities,
	} = useEntityPagination( perPage, filteredDates );
	// update the date ids in state whenever the filters change
	const { setFilteredDates } = useDatesListFilterStateSetters( listId );
	useEffect( () => {
		if ( Array.isArray( paginatedEntities ) ) {
			const eventDateIds = paginatedEntities.map(
				( dateEntity ) => dateEntity.id
			);
			setFilteredDates( eventDateIds );
		}
	}, [ currentPage, perPage, showDates, datesSortedBy, eventDates.length ] );
	return (
		<FormWrapper>
			<DatesListFilterBar
				listId={ listId }
				view={ view }
				perPage={ perPage }
				showDates={ showDates }
				datesSortedBy={ datesSortedBy }
				displayDates={ displayDates }
				{ ...entityListFilters }
			/>
			<EntityPagination
				listId={ listId }
				currentPage={ currentPage }
				entitiesPerPage={ perPage }
				totalCount={ filteredDates.length }
				setCurrentPage={ setCurrentPage }
			/>
			<EntityList
				{ ...otherProps }
				entities={ paginatedEntities }
				EntityGridView={ EditorDateEntitiesGridView }
				EntityListView={ EditorDateEntitiesListView }
				view={ view }
				showDate={ displayDates }
				loading={ ! eventDatesLoaded }
				loadingNotice={ sprintf(
					_x(
						'loading event dates%s',
						'loading event dates...',
						'event_espresso'
					),
					String.fromCharCode( 8230 )
				) }
				noResultsText={ __(
					'no results found (try changing filters)',
					'event_espresso'
				) }
			/>
			<FormSaveCancelButtons
				submitButton={ <AddNewDateEntityButton /> }
				cancelButton={
					<EditAllTicketAssignmentsButton
						eventDates={ paginatedEntities }
					/>
				}
			/>
		</FormWrapper>
	);
};

export default EditorDateEntitiesList;