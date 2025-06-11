import React from 'react';
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
	DialogTrigger,
} from '../../controls/Dialog';

type Props = {
	header: React.ReactNode;
	content: React.FunctionComponent;
	footer: React.FunctionComponent | null;
	notice?: React.ReactNode;
	extraContent?: React.ReactNode;
	buttonTitle: string;
	additionalStyles?: string;
	textColor?: string;
	disabled?: boolean;
};

const ImportDialogSkeleton = ({
	header,
	content: Content,
	footer: Footer,
	notice,
	extraContent,
	buttonTitle,
	additionalStyles,
	textColor,
	disabled,
}: Props) => {
	return (
		<Dialog>
			<DialogTrigger asChild>
				<button
					type="button"
					className={`bg-[#2563EB] rounded-[2px] px-[16px] py-[8px] border border-solid border-[#2563EB] cursor-pointer flex items-center disabled:opacity-50 disabled:cursor-not-allowed ${additionalStyles ? additionalStyles : ''}`}
					disabled={disabled}
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="16"
						height="16"
						viewBox="0 0 16 16"
						fill="none"
					>
						<path
							d="M12.5799 12.1533C12.4313 12.1541 12.2866 12.1051 12.1689 12.0142C12.0513 11.9233 11.9674 11.7957 11.9306 11.6516C11.8938 11.5075 11.9063 11.3553 11.9659 11.2191C12.0256 11.0829 12.1291 10.9706 12.2599 10.9C12.7382 10.6379 13.0931 10.1967 13.2466 9.67334C13.3263 9.41452 13.3527 9.1422 13.324 8.8729C13.2954 8.6036 13.2123 8.34292 13.0799 8.10667C12.904 7.78115 12.643 7.50943 12.3249 7.32043C12.0067 7.13142 11.6433 7.03221 11.2733 7.03334H10.6066C10.4552 7.03813 10.3067 6.99122 10.1855 6.90034C10.0643 6.80945 9.97772 6.68002 9.93993 6.53334C9.82019 6.0624 9.6087 5.61972 9.31757 5.23066C9.02644 4.8416 8.66139 4.51382 8.24336 4.2661C7.82532 4.01837 7.36251 3.85557 6.88145 3.78703C6.40038 3.71849 5.91052 3.74555 5.43993 3.86667C4.96899 3.98641 4.52631 4.19791 4.13725 4.48904C3.7482 4.78017 3.42042 5.14521 3.17269 5.56324C2.92497 5.98128 2.76217 6.44409 2.69363 6.92516C2.62509 7.40622 2.65215 7.89608 2.77326 8.36667C2.92277 8.9302 3.19863 9.45228 3.57993 9.89334C3.69647 10.026 3.7556 10.1995 3.74435 10.3757C3.7331 10.552 3.65239 10.7165 3.51993 10.8333C3.38725 10.9499 3.21375 11.009 3.03752 10.9978C2.86129 10.9865 2.69672 10.9058 2.57993 10.7733C2.05747 10.1769 1.68088 9.46706 1.47993 8.7C1.1113 7.43628 1.25311 6.07837 1.8749 4.91808C2.49668 3.75779 3.54881 2.88771 4.80521 2.49482C6.0616 2.10192 7.42198 2.21756 8.59403 2.81689C9.76608 3.41622 10.6563 4.45141 11.0733 5.7H11.2733C12.0256 5.7019 12.7559 5.95373 13.3495 6.41592C13.943 6.87811 14.3662 7.52444 14.5524 8.25332C14.7387 8.9822 14.6774 9.75231 14.3783 10.4426C14.0791 11.1328 13.5591 11.7041 12.8999 12.0667C12.8023 12.122 12.6922 12.1519 12.5799 12.1533ZM10.6666 10.2533C10.5417 10.1292 10.3727 10.0595 10.1966 10.0595C10.0205 10.0595 9.85151 10.1292 9.7266 10.2533L8.6666 11.3333V8C8.6666 7.82319 8.59636 7.65362 8.47134 7.5286C8.34631 7.40357 8.17674 7.33334 7.99993 7.33334C7.82312 7.33334 7.65355 7.40357 7.52853 7.5286C7.4035 7.65362 7.33326 7.82319 7.33326 8V11.3333L6.29326 10.2533C6.23097 10.1915 6.15709 10.1427 6.07587 10.1095C5.99464 10.0763 5.90767 10.0595 5.81993 10.06C5.73219 10.0595 5.64522 10.0763 5.56399 10.1095C5.48277 10.1427 5.40889 10.1915 5.3466 10.2533C5.28323 10.3144 5.23259 10.3875 5.19759 10.4682C5.1626 10.549 5.14394 10.6359 5.1427 10.7239C5.14146 10.8119 5.15767 10.8993 5.19037 10.981C5.22308 11.0627 5.27164 11.1372 5.33326 11.2L7.51993 13.38C7.58137 13.44 7.6538 13.4875 7.73326 13.52C7.81398 13.5562 7.90145 13.575 7.98993 13.575C8.07841 13.575 8.16588 13.5562 8.2466 13.52C8.32606 13.4875 8.3985 13.44 8.45993 13.38L10.6666 11.2C10.7291 11.138 10.7787 11.0643 10.8125 10.9831C10.8464 10.9018 10.8638 10.8147 10.8638 10.7267C10.8638 10.6387 10.8464 10.5515 10.8125 10.4703C10.7787 10.389 10.7291 10.3153 10.6666 10.2533Z"
							fill={textColor ? textColor : 'white'}
						/>
					</svg>
					<span className={`text-${textColor ? textColor : 'white'} ml-[8px] font-[600]`}>
						{buttonTitle}
					</span>
				</button>
			</DialogTrigger>
			<DialogContent
				style={{ zIndex: 50000 }}
				className="border-solid border-[#F4F4F4] px-0 py-0 gap-0 max-w-[300px] sm:max-w-[600px]"
			>
				{notice}
				<DialogHeader className="border-0 border-b border-solid border-[#f4f4f4] px-[40px] py-[20px]">
					<DialogTitle className="my-0 text-[18px] text-[#383838]">{header}</DialogTitle>
				</DialogHeader>
				<div className="px-[40px] pt-[20px] pb-[48px] overflow-x-hidden overflow-y-scroll sm:overflow-hidden">
					<Content />
				</div>
				{Footer && (
					<DialogFooter className="border-0 border-t border-solid border-[#f4f4f4] p-[16px] sm:py-[16px] sm:px-[40px] flex items-center justify-between flex-row sm:justify-between">
						<Footer />
					</DialogFooter>
				)}
				{extraContent}
			</DialogContent>
		</Dialog>
	);
};

export default ImportDialogSkeleton;
