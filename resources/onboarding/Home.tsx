import { useEffect, useMemo } from 'react';
import { localizedDataQueryOptions } from './components/features/api/import.api';
import Content from './components/features/sites/components/listing/main/Content';
import Sidebar from './components/features/sites/components/listing/main/Sidebar';
import { queryClient } from './lib/query-client';
import { useLocalizedData } from './LocalizedDataContext';

declare const require: any;

const Home = () => {
	const { localizedData, setLocalizedData } = useLocalizedData();

	const demos = useMemo(() => localizedData?.data?.demos || [], [localizedData]);
	const builders = useMemo(() => localizedData?.data?.builders || [], [localizedData]);
	const categories = useMemo(() => localizedData?.data?.categories || [], [localizedData]);
	const theme = localizedData?.theme || 'all';

	const loading = useMemo(() => {
		return !localizedData?.data;
	}, [localizedData]);

	const error = useMemo(() => {
		if (localizedData?.error_msg) return localizedData.error_msg;
		if (localizedData?.data && !demos.length && !builders.length && !categories.length) {
			return 'No data available';
		}
		return '';
	}, [localizedData, demos.length, builders.length, categories.length]);

	const handleRefetch = async () => {
		const response = await queryClient.ensureQueryData(
			localizedDataQueryOptions({ refetch: true }),
		);
		setLocalizedData(response);
	};

	useEffect(() => {
		document.body.classList.add('tg-full-overlay-active');
		document.documentElement.classList.remove('wp-toolbar');
	}, []);

	if (error) {
		return (
			<div className="h-screen bg-[#FAFBFC] p-4">
				<div
					className="flex items-center p-4 text-sm text-red-800 border border-solid border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800"
					role="alert"
				>
					<svg
						className="shrink-0 inline w-4 h-4 me-3"
						xmlns="http://www.w3.org/2000/svg"
						fill="currentColor"
						viewBox="0 0 20 20"
					>
						<path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z" />
					</svg>
					<span className="font-medium">{error}</span>
				</div>
			</div>
		);
	}

	return (
		<>
			{loading ? (
				<div className="flex h-screen content-container">
					<div className="w-[350px] px-6 pt-6 py-10 bg-[#FAFBFC] border-0 border-r border-solid border-[#E9E9E9] flex flex-col gap-6 box-border">
						<div className="flex justify-between items-center border-0 border-b border-solid border-[#E3E3E3] pb-6">
							<div className="w-12 h-12 bg-[#E7E8E9] rounded-full flex-shrink-0 animate-pulse"></div>
							<div className="w-6 h-6 bg-[#E7E8E9] rounded animate-pulse"></div>
						</div>
						<div>
							<div className="h-[54px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
							<div className="my-[32px]">
								<div className="w-[139px] h-[18px] bg-[#E7E8E9] rounded-sm animate-pulse"></div>
								<div className="mt-4 h-[48px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="mt-4 h-[48px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="mt-4 h-[48px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
							</div>
							<div className="w-[139px] h-[18px] mb-5 bg-[#E7E8E9] rounded-sm animate-pulse"></div>
							<div className="flex gap-[14px] flex-wrap">
								<div className="h-[40px] w-[54px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="h-[40px] w-[95px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="h-[40px] w-[124px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="h-[40px] w-[104px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="h-[40px] w-[72px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="h-[40px] w-[97px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="h-[40px] w-[117px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
								<div className="h-[40px] w-[101px] bg-[#E7E8E9] rounded-md animate-pulse"></div>
							</div>
						</div>
					</div>
					<div className="flex-1 p-14 sm:p-14 lg:p-[48px] xl:p-[88px] grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-3 gap-10 overflow-y-auto bg-[#fff] content-wrapper">
						{Array.from({ length: 6 }).map((_, outerIndex) => (
							<div
								className="flex flex-col gap-0 border-2 rounded-md border-solid cursor-pointer border-[#EDEDED]"
								key={outerIndex}
							>
								<img
									src={require(`./assets/images/demo-skeleton.jpg`)}
									className="w-full rounded-[2px]"
									style={{ aspectRatio: '.84 / 1' }}
								/>
								<div className="bg-white px-4 py-6 border-0 border-t border-solid border-[#EDEDED] rounded-b-md">
									<div className="h-[11px] w-[113px] bg-[#E7E8E9] rounded-sm animate-pulse"></div>
								</div>
							</div>
						))}
					</div>
				</div>
			) : (
				<div className="flex h-screen content-container">
					<Sidebar builders={builders} categories={categories} theme={theme} />
					<Content demos={demos} />
				</div>
			)}
		</>
	);
};

export default Home;
