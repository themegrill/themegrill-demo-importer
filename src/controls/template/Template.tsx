import React, { useState } from 'react';
import ImportButton from '../import/ImportButton';
import SingleTemplate from './SingleTemplate';

const Template = () => {
	const [selections, setSelections] = useState<boolean[]>([false, false, false, false]);

	return (
		<div className="h-[370px] sm:h-[302px] w-full bg-white p-[25px] sm:p-[32px] shadow absolute bottom-0 ">
			<div className="mb-[24px] flex flex-wrap justify-between items-center">
				<div>
					<h4 className="text-[22px] m-0 mb-[8px] text-[#383838]">Optigo</h4>
					<p className="text-[#7a7a7a] text-[14px] mt-4 sm:m-0">
						6 Templates (You can select pages manually by clicking on templates.)
					</p>
				</div>
				<div className="mr-[70px] flex flex-wrap gap-[16px]">
					<ImportButton buttonTitle="Import All" />
					<ImportButton buttonTitle="Import Selected Pages" />
				</div>
			</div>
			<div className="flex gap-[16px] w-full overflow-x-auto tg-overlay-template pb-[20px]">
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
				<SingleTemplate />
			</div>
		</div>
	);
};

export default Template;
