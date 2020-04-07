import { useEffect } from 'react';
import { useStatus, TypeName } from '../../../../../application/services/apollo/status';

const useSetGlobalStatusFlags = (): void => {
	const { setIsLoaded, isLoaded } = useStatus();

	useEffect(() => {
		// Set loaded status for all entities
		for (const entity in TypeName) {
			if (!isLoaded(TypeName[entity])) {
				setIsLoaded(TypeName[entity], true);
			}
		}
	}, []);
};

export default useSetGlobalStatusFlags;