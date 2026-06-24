import { __ } from '@wordpress/i18n';
import { Button, Modal, __experimentalGrid as Grid, __experimentalHeading as Heading, __experimentalText as Text, Spinner } from '@wordpress/components';

const Templates = ({ templates, loading, loadDesign, closeModal }) => {
    return (
        <Modal isFullScreen={true} title="Choose a Design" onRequestClose={closeModal}>
            {Object.keys(templates || {}).length === 0 ? (
                <Text variant="muted">
                    {loading === 'get-templates' ? (
                        <><Spinner />{__('Getting templates.', 'cps-bloom-mailer')}</>
                    ) : (
                        __('No templates found.', 'cps-bloom-mailer')
                    )}
                </Text>
            ) : (
                <Grid templateColumns='repeat(auto-fill, minmax(300px, 1fr))' gap={10}>
                    {Object.values(templates).map((template) => (
                        <Grid
                            key={template?.id}
                            alignment="bottom"
                            columns={1}
                            gap={5}
                            justify="center"
                            className="cps-template-grid"
                        >
                            <Heading level={2}>{template?.title}</Heading>
                            <div 
                            style={{
                                width: '100%',
                                height: '320px', // 400 * 0.8
                                overflow: 'hidden',
                                border: '1px solid #ddd',
                                borderRadius: '4px',
                                cursor: 'pointer',
                            }}
                                onClick={(e) => {
                                    loadDesign(template?.id);
                                    closeModal();
                                }}
                            >
                                <iframe
                                    srcDoc={template?.html}
                                    title={template?.title}
                                    scrolling="no"
                                    style={{
                                        width: '125%',
                                        height: '500px',
                                        border: 'none',
                                        transform: 'scale(0.8)',
                                        transformOrigin: 'top left',
                                        pointerEvents: 'none',
                                    }}
                                />
                            </div>
                            <Text variant="muted" style={{ fontSize: '13px' }}>
                                {template?.description}
                            </Text>
                            <Button
                                variant="secondary"
                                onClick={(e) => {
                                    loadDesign(template?.id);
                                    closeModal();
                                }}
                            >
                                {__('Use Design', 'cps-bloom-mailer')}
                            </Button>
                        </Grid>
                    ))}
                </Grid>
            )}
        </Modal>
    );
};
export default Templates;