import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { BlockInspector, useSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { ComplementaryArea } from '@wordpress/interface';
import { TabPanel } from '@wordpress/components';
import { FooterPanel, HeaderPanel, EmailPanel } from '../components/Panels';

const SIDEBAR_ID = 'cps-bloom-mailer/block-inspector';
const SCOPE = 'cps-bloom-mailer';

export default function Sidebar({ loading, templates, isTemplate, states, setStates }) {
	const { header, footer, selectedSection } = states;
	const { setHeaderContent, setFooterContent } = setStates;

	const [activeTab, setActiveTab] = useState('email');
	const hasSelectedBlock = useSelect(
		(select) => !!select('core/block-editor').getSelectedBlockClientId(),
		[]
	);

	useEffect(() => {
		if (activeTab === 'block') return;
		const tabButton = document.querySelector('.email-sidebar-tabs button[id$="-block"]');
		if (tabButton) {
			tabButton.click();
		}
	}, [hasSelectedBlock]);

	return (
		<ComplementaryArea
			scope={SCOPE}
			identifier={SIDEBAR_ID}
			isPinnable={false}
			isActiveByDefault={true}
			closeLabel={__('Close inspector', 'cps-bloom-mailer')}
			headerClassName="editor-sidebar__panel-tabs"
			header={
				<TabPanel
					className="email-sidebar-tabs"
					initialTabName={activeTab}
					activeClass="is-active"
					onSelect={(tab) => setActiveTab(tab)}
					tabs={[
						{ name: 'email', title: 'Email' },
						{ name: 'block', title: 'Block' }
					]}
					children={() => null}
				/>
			}
		>
			{activeTab === 'email'
				? (
					<div className="block-editor-block-inspector">
						{(selectedSection === 'header' && header?.enabled) ? (
							<HeaderPanel
								header={header}
								setHeaderContent={setHeaderContent}
							/>
						) : (
							(selectedSection === 'footer' && footer?.enabled) ? (
								<FooterPanel
									footer={footer}
									setFooterContent={setFooterContent}
								/>
							) : (
								<EmailPanel
									loading={loading}
									templates={templates}
									isTemplate={isTemplate}
									states={states}
									setStates={setStates}
								/>
							)
						)}
					</div>
				)
				:
				<BlockInspector />
			}
		</ComplementaryArea>
	);
}
// ── Email / document settings ─────────────────────────────────────────────────

Sidebar.Slot = () => <ComplementaryArea.Slot scope={SCOPE} />;