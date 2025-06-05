import React from 'react';
import { SearchResultType } from '../../lib/types';
import SingleDemo from './SingleDemo';

type Props = {
	demos: SearchResultType[];
};

const Demos = ({ demos }: Props) => {
	return (
		<>
			<div className="tg-demos mt-0">
				<div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-[40px]">
					{demos.map((demo) => (
						<SingleDemo key={demo.slug} demo={demo} />
					))}
				</div>
			</div>
		</>
	);
};

export default Demos;
