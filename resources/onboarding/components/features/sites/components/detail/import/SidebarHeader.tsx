import { useRouter } from '@tanstack/react-router';
import { __ } from '@wordpress/i18n';
import { ArrowLeft } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '../../../../../ui/Tooltip';

type Props = {
	title: string;
	subtitle: string;
};

const SidebarHeader = ({ title, subtitle }: Props) => {
	const router = useRouter();

	return (
		<div className="px-6 pt-6">
			<div className="pb-6 border-0 border-b border-solid border-[#E3E3E3]">
				<div className="flex justify-between items-center">
					<h3 className="text-[20px] leading-7 m-0">{__(title, 'themegrill-demo-importer')}</h3>
					<Tooltip>
						<TooltipTrigger asChild>
							<ArrowLeft
								size={24}
								color="#909090"
								strokeWidth={2}
								onClick={() =>
									router.navigate({
										to: '/',
										search: {
											search: undefined,
											builder: undefined,
											category: undefined,
										},
										replace: true,
									})
								}
								className="cursor-pointer"
							/>
						</TooltipTrigger>
						<TooltipContent side="bottom" sideOffset={-4}>
							{__('Back to Starter Templates', 'themegrill-demo-importer')}
						</TooltipContent>
					</Tooltip>
				</div>
				<p className="text-[14px] text-[#6B6B6B] leading-[21px] m-0 mt-[6px] ">
					{__(subtitle, 'themegrill-demo-importer')}
				</p>
			</div>
		</div>
	);
};

export default SidebarHeader;
