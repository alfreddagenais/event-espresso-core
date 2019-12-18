import useEventId from '../../queries/events/useEventId';
import useDatetimeQueryOptions from '../../queries/datetimes/useDatetimeQueryOptions';
import useOnCreateDatetime from './useOnCreateDatetime';
import useOnUpdateDatetime from './useOnUpdateDatetime';
import useOnDeleteDatetime from './useOnDeleteDatetime';

/**
 *
 */
const useDatetimeMutator = () => {
	const eventId = useEventId();

	const options = useDatetimeQueryOptions();

	const onCreateDatetime = useOnCreateDatetime();
	const onUpdateDatetime = useOnUpdateDatetime();
	const onDeleteDatetime = useOnDeleteDatetime();

	const createVariables = (mutationType, input) => {
		const mutationInput = {
			clientMutationId: `${mutationType}_DATETIME`,
			...input,
		};

		if (mutationType === 'CREATE') {
			mutationInput.eventId = eventId; // required for createDatetime
		}

		return {
			input: mutationInput,
		};
	};

	const mutator = (mutationType, input) => {
		const variables = createVariables(mutationType, input);
		/**
		 * @todo update optimisticResponse
		 */
		let optimisticResponse;

		const onUpdate = ({ proxy, entity: datetime }) => {
			// Read the existing data from cache.
			const { espressoDatetimes: datetimes = {} } = proxy.readQuery(options);
			const { tickets = [] } = input;

			switch (mutationType) {
				case 'CREATE':
					onCreateDatetime({ proxy, datetimes, datetime, tickets });
					break;
				case 'UPDATE':
					onUpdateDatetime({ datetime, tickets });
					break;
				case 'DELETE':
					onDeleteDatetime({ proxy, datetimes, datetime });
					break;
			}
		};

		return { variables, optimisticResponse, onUpdate };
	};

	return mutator;
};

export default useDatetimeMutator;