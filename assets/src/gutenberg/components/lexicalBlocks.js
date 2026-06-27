import { __ } from '@wordpress/i18n';
import { LexicalBlock } from '../../lexical/LexicalBlock';
import { boxValues, defaultSpacing, resolveFontStack } from '../../utils';

export const Header = ({ header, design, setHeaderContent, selectedSection, onClick }) => {
    return (
        <div className='lexical-block-wrapper header'
            data-type='header'
            style={{
                borderColor: (selectedSection === 'header' ? '#999' : ''),
                textAlign: header?.settings?.alignment || 'center',
                fontSize: header?.settings?.fontSize,
                color: header?.settings?.textColor,
                backgroundColor: header?.settings?.background,
                padding: boxValues(header?.settings?.padding || defaultSpacing('header')),
            }}
            onClick={onClick}
        >
            {(header?.logo && !header?.logoUrl) ?
                <h1>{'{{site_logo}}'}</h1>
                :
                (
                    header?.logoUrl ? (
                        <img
                            tabIndex={-1}
                            src={header?.logoUrl} style={{ width: `${header?.logoWidth || 60}%`, height: 'auto' }} />
                    ) : (
                        <>
                            <>
                                <LexicalBlock
                                    blockId="header"
                                    initialHTML={header?.title?.html}
                                    savedState={header?.title?.json}
                                    selected={selectedSection}
                                    placeholder="{{site_name}}"
                                    style={{ fontSize: header?.settings?.titleSize, padding: 0, margin: 0, fontFamily: resolveFontStack(header?.settings?.fontFamily) }}
                                    onChange={({ html, json }) => setHeaderContent({ ...header, title: { html, json } })}
                                />
                            </>
                            {header?.settings?.showDescription && (
                                <LexicalBlock
                                    blockId="header-desc"
                                    initialHTML={header?.description?.html}
                                    savedState={header?.description?.json}
                                    selected={`${selectedSection}-desc`}
                                    style={{ border: '1px dashed' }}
                                    placeholder={__('Enter description...', 'cps-bloom-mailer')}
                                    onChange={({ html, json }) => setHeaderContent({ ...header, description: { html, json } })}
                                />
                            )}
                        </>
                    )
                )
            }
        </div>
    );
};

export const Footer = ({ footer, design, setFooterContent, selectedSection, onClick }) => {
    return (
        <div
            className='lexical-block-wrapper footer'
            data-type='footer'
            style={{
                borderColor: (selectedSection === 'footer' ? '#999' : ''),
                fontFamily: design?.fontFamily,
                textAlign: footer?.settings?.alignment || 'center',
                fontSize: footer?.settings?.fontSize,
                color: footer?.settings?.textColor,
                backgroundColor: footer?.settings?.background,
                padding: boxValues(footer?.settings?.padding || defaultSpacing('footer')),
                '--footer--link--color': footer?.settings?.linkColor,
            }}
            onClick={onClick}
        >
            <LexicalBlock
                blockId={'footer'}
                blockType={'footer'}
                initialHTML={footer?.content?.html}
                savedState={footer?.content?.json}
                selected={selectedSection}
                placeholder={__('Enter footer text...', 'cps-bloom-mailer')}
                onChange={({ html, json }) => setFooterContent({ ...footer, content: { html, json } })}
            />
        </div>
    );
};