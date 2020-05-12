import React, { useEffect, useRef } from 'react';
import * as typeformEmbed from '@typeform/embed';

import './styles.scss';

type SurveyContentProps = {
	onSubmit: VoidFunction;
};

const info = window?.eejsdata?.data?.exitModalInfo;

const ExitSurveyContent: React.FC<SurveyContentProps> = ({ onSubmit }) => {
	const typeFormEl = useRef();
	const typeFormUrl = info?.typeFormUrl;

	useEffect(() => {
		typeformEmbed.makeWidget(typeFormEl.current, typeFormUrl, {
			onSubmit: function () {
				onSubmit();
			},
			hideScrollbars: true,
		});
	}, []);

	return <div ref={typeFormEl}></div>;
};

export default ExitSurveyContent;
