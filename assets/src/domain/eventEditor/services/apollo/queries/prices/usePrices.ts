import { Price, PricesList } from '../../types';
import { useCacheQuery } from '@dataServices/apollo/queries';
import { getCacheIds } from '@appServices/predicates';
import { useMemoStringify } from '@appServices/hooks';
import usePriceQueryOptions from './usePriceQueryOptions';

/**
 * A custom react hook for retrieving all the prices from cache
 * limited to the ids passed in `include`
 */
const usePrices = (): Price[] => {
	const options = usePriceQueryOptions();
	const { data } = useCacheQuery<PricesList>(options);

	const nodes = data?.espressoPrices?.nodes || [];

	const cacheIds = getCacheIds(nodes);

	return useMemoStringify(nodes, cacheIds);
};

export default usePrices;