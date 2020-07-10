import { useEffect } from 'react';
import { useRelations } from '../../../../../application/services/apollo/relations';
import './data';

const useSetRelationalData = (): void => {
	const { initialize } = useRelations();

	useEffect(() => {
		initialize(window?.eventEspressoData?.eventEditor?.relations);
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, []);
};

export default useSetRelationalData;