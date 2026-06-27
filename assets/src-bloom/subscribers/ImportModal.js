import { useState, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { Button, FormTokenField, Modal, SelectControl, Spinner } from '@wordpress/components';

// DB fields the user can map to
const DB_FIELDS = [
    { value: 'skip', label: __('— Skip column —', 'cps-bloom') },
    { value: 'email', label: __('Email *', 'cps-bloom') },
    { value: 'first_name', label: __('First name', 'cps-bloom') },
    { value: 'last_name', label: __('Last name', 'cps-bloom') },
    { value: 'platform', label: __('Platform', 'cps-bloom') },
    { value: 'source', label: __('Source', 'cps-bloom') },
    { value: 'timezone', label: __('Timezone', 'cps-bloom') },
    { value: 'created_at', label: __('Date created', 'cps-bloom') },
    { value: 'updated_at', label: __('Date Updated', 'cps-bloom') },
    { value: 'tags', label: __('Tags (comma-separated)', 'cps-bloom') },
];

// Try to auto-detect a DB field from a CSV header string
function autoDetect(header) {
    const h = header.toLowerCase().replace(/[\s_\-]/g, '');
    if (h.includes('email')) return 'email';
    if (h.includes('firstname') || h === 'first') return 'first_name';
    if (h.includes('lastname') || h === 'last') return 'last_name';
    if (h.includes('platform')) return 'platform';
    if (h.includes('source')) return 'source';
    if (h.includes('timezone')) return 'timezone';
    if (h.includes('tag') || h === 'tag') return 'tags';
    if (h.includes('date') || h.includes('created') || h.includes('joined') || h === 'optintime' || h === 'lastactive') return 'created_at';
    if (h.includes('updated') || h === 'lastchanged' || h === 'unsubtime') return 'updated_at';
    return 'skip';
}

// Parse just the header row from a CSV file (client-side, no upload)
function parseHeaders(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const text = e.target.result;
            // Take the first line only
            const firstLine = text.split(/\r?\n/)[0] ?? '';
            // Simple CSV split (handles quoted fields)
            const headers = firstLine.split(',').map((h) =>
                h.trim().replace(/^["']|["']$/g, '').replace(/^\uFEFF/, '')
            );
            resolve(headers);
        };
        reader.onerror = () => reject(new Error('Could not read file.'));
        // Only read the first 4KB — enough for a header row
        reader.readAsText(file.slice(0, 4096));
    });
}

// ---------------------------------------------------------------------------
// Steps
// ---------------------------------------------------------------------------

// Step 1: File upload
function StepUpload({ onNext }) {
    const inputRef = useRef(null);
    const [file, setFile] = useState(null);
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [dragging, setDragging] = useState(false);

    async function handleFile(f) {
        if (!f) return;
        const ext = f.name.split('.').pop().toLowerCase();
        if (ext !== 'csv') {
            setError(__('Only CSV files are supported.', 'cps-bloom'));
            return;
        }
        setError('');
        setFile(f);
        setLoading(true);
        try {
            const headers = await parseHeaders(f);
            if (!headers.length || (headers.length === 1 && !headers[0])) {
                setError(__('Could not read headers from this file.', 'cps-bloom'));
                setFile(null);
                return;
            }
            onNext(f, headers);
        } catch {
            setError(__('Failed to read the file.', 'cps-bloom'));
            setFile(null);
        } finally {
            setLoading(false);
        }
    }

    function onDrop(e) {
        e.preventDefault();
        setDragging(false);
        const f = e.dataTransfer.files?.[0];
        if (f) handleFile(f);
    }

    return (
        <div className="blm-import__step">
            <p className="blm-import__intro">
                {__('Upload a CSV file. The first row must be a header row. Email is required.', 'cps-bloom')}
            </p>

            <div
                className={`blm-import__dropzone${dragging ? ' is-dragging' : ''}`}
                onDragOver={(e) => { e.preventDefault(); setDragging(true); }}
                onDragLeave={() => setDragging(false)}
                onDrop={onDrop}
                onClick={() => inputRef.current?.click()}
                role="button"
                tabIndex={0}
                onKeyDown={(e) => e.key === 'Enter' && inputRef.current?.click()}
            >
                <span className="blm-import__dropzone-icon">📂</span>
                <p>{__('Click to choose a file, or drag and drop here', 'cps-bloom')}</p>
                <p className="blm-import__dropzone-hint">{__('CSV only', 'cps-bloom')}</p>
                <input
                    ref={inputRef}
                    type="file"
                    accept=".csv,text/csv"
                    style={{ display: 'none' }}
                    onChange={(e) => handleFile(e.target.files?.[0])}
                />
            </div>

            {loading && (
                <div className="blm-import__loading">
                    <Spinner />
                    <span>{__('Reading file…', 'cps-bloom')}</span>
                </div>
            )}

            {error && <p className="blm-import__error">{error}</p>}
        </div>
    );
}

// Step 2: Column mapper
function StepMap({ file, headers, onBack, onNext, audience }) {
    const { statuses, lists, tags } = audience;
    const [defaults, setDefaults] = useState({ status: '', lists: [], tags: [] });

    const initialMap = () => {
        const m = {};
        headers.forEach((h) => { m[h] = autoDetect(h); });
        return m;
    };

    const [map, setMap] = useState(initialMap);
    const [error, setError] = useState('');

    function setField(header, value) {
        setMap((prev) => ({ ...prev, [header]: value }));
    }

    // Convert selected IDs back to names for the field value
    const tagsValue = (tags || []).map(id => tags.find(item => item.id === id)?.name).filter(Boolean);
    const listsValue = (lists || []).map(id => lists.find(item => item.id === id)?.name).filter(Boolean);

    function validate() {
        const mapped = Object.values(map);
        if (!mapped.includes('email')) {
            setError(__('You must map at least one column to Email.', 'cps-bloom'));
            return false;
        }
        // Warn on duplicate non-skip mappings
        const nonSkip = mapped.filter((v) => v !== 'skip');
        const dupes = nonSkip.filter((v, i) => nonSkip.indexOf(v) !== i);
        if (dupes.length) {
            setError(
                sprintf(
                    __('Each field can only be mapped once. Duplicate: %s', 'cps-bloom'),
                    [...new Set(dupes)].join(', ')
                )
            );
            return false;
        }
        setError('');
        return true;
    }

    function handleNext() {
        if (validate()) onNext({ map, defaults });
    }

    return (
        <div className="blm-import__step">
            <p className="blm-import__intro">
                {sprintf(
                    __('Map your CSV columns to subscriber fields. Found %d column(s).', 'cps-bloom'),
                    headers.length
                )}
            </p>

            <div className="blm-import__map-table">
                <div className="blm-import__map-header">
                    <span>{__('CSV column', 'cps-bloom')}</span>
                    <span>{__('Maps to', 'cps-bloom')}</span>
                </div>
                {headers.map((header) => (
                    <div key={header} className={`blm-import__map-row${map[header] !== 'skip' ? ' row-mapped' : ''}`}>
                        <span className="blm-import__map-col">{header}</span>
                        <SelectControl
                            __next40pxDefaultSize
                            value={map[header]}
                            options={DB_FIELDS}
                            onChange={(v) => setField(header, v)}
                            __nextHasNoMarginBottom
                        />
                    </div>
                ))}
            </div>

            <p className="blm-import__tags-hint">
                {__('Tags: comma-separated values in one cell, e.g. newsletter, vip, sale', 'cps-bloom')}
            </p>

            <div className="blm-import__defaults">
                <SelectControl
                    label={__('New Subscriber Status', 'cps-bloom')}
                    value={defaults.status}
                    options={statuses}
                    __next40pxDefaultSize
                    __nextHasNoMarginBottom
                    onChange={(value) =>
                        setDefaults((prev) => ({ ...prev, status: value }))
                    }
                />

                <div className="blm-import__audience">
                    <FormTokenField
                        label={'Select Lists'}
                        value={listsValue}
                        suggestions={(lists || []).map(t => t.name)}
                        maxSuggestions={20}
                        onChange={(tokens) => {
                            const ids = tokens.map(token => lists.find(item => item.name === token)?.id).filter(Boolean);
                            setDefaults((prev) => ({ ...prev, lists: ids }))
                        }}
                        placeholder="Lists"
                        __experimentalExpandOnFocus
                        __next40pxDefaultSize
                    />
                    <FormTokenField
                        label={'Select Tags'}
                        value={tagsValue}
                        suggestions={(tags || []).map(t => t.name)}
                        maxSuggestions={20}
                        onChange={(tokens) => {
                            const ids = tokens.map(token => tags.find(item => item.name === token)?.id).filter(Boolean);
                            setDefaults((prev) => ({ ...prev, tags: ids }))
                        }}
                        placeholder="Tags"
                        __experimentalExpandOnFocus
                        __next40pxDefaultSize
                    />
                    {/* <SelectControl
                        label={__('New Subscriber List', 'cps-bloom')}
                        value={defaults.list}
                        options={lists}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        onChange={(value) =>
                            setDefaults((prev) => ({ ...prev, list: value }))
                        }
                    /> */}

                    {/* <SelectControl
                        label={__('New Subscriber Tag', 'cps-bloom')}
                        value={defaults.tag}
                        options={tags}
                        __next40pxDefaultSize
                        __nextHasNoMarginBottom
                        onChange={(value) =>
                            setDefaults((prev) => ({ ...prev, tag: value }))
                        }
                    /> */}
                </div>
            </div>

            {error && <p className="blm-import__error">{error}</p>}

            <div className="blm-import__footer">
                <Button variant="tertiary" onClick={onBack}>
                    {__('Back', 'cps-bloom')}
                </Button>
                <Button variant="primary" onClick={handleNext}>
                    {__('Import', 'cps-bloom')}
                </Button>
            </div>
        </div>
    );
}

// Step 3: Result
function StepResult({ result, onClose }) {
    const { imported, updated, skipped, skipped_rows } = result;

    return (
        <div className="blm-import__step">
            <div className="blm-import__result-summary">
                <div className="blm-import__result-stat blm-import__result-stat--imported">
                    <span className="blm-import__result-num">{imported}</span>
                    <span>{__('Imported', 'cps-bloom')}</span>
                </div>
                <div className="blm-import__result-stat blm-import__result-stat--updated">
                    <span className="blm-import__result-num">{updated}</span>
                    <span>{__('Updated', 'cps-bloom')}</span>
                </div>
                <div className="blm-import__result-stat blm-import__result-stat--skipped">
                    <span className="blm-import__result-num">{skipped}</span>
                    <span>{__('Skipped', 'cps-bloom')}</span>
                </div>
            </div>

            {skipped_rows?.length > 0 && (
                <div className="blm-import__skipped">
                    <h3>{__('Skipped rows', 'cps-bloom')}</h3>
                    <table className="blm-import__skipped-table">
                        <thead>
                            <tr>
                                <th>{__('Row', 'cps-bloom')}</th>
                                <th>{__('Value', 'cps-bloom')}</th>
                                <th>{__('Reason', 'cps-bloom')}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {skipped_rows.map((r, i) => (
                                <tr key={i}>
                                    <td>{r.row}</td>
                                    <td>{r.value || '—'}</td>
                                    <td>{r.reason}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            <div className="blm-import__footer">
                <Button variant="primary" onClick={onClose}>
                    {__('Done', 'cps-bloom')}
                </Button>
            </div>
        </div>
    );
}

// ---------------------------------------------------------------------------
// Modal shell
// ---------------------------------------------------------------------------

const STEP_TITLES = [
    __('Import subscribers — Upload file', 'cps-bloom'),
    __('Import subscribers — Map columns', 'cps-bloom'),
    __('Import subscribers — Complete', 'cps-bloom'),
];

export default function ImportModal({ onClose, onComplete, audience }) {
    const [step, setStep] = useState(0); // 0 upload | 1 map | 2 result
    const [file, setFile] = useState(null);
    const [headers, setHeaders] = useState([]);
    const [result, setResult] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [error, setError] = useState('');

    function handleUploadNext(f, h) {
        setFile(f);
        setHeaders(h);
        setStep(1);
    }

    async function handleMapNext({ map, defaults }) {
        setUploading(true);
        setError('');

        const formData = new FormData();
        formData.append('file', file);
        formData.append('map', JSON.stringify(map));
        formData.append('defaults', JSON.stringify(defaults));

        try {
            const result = await apiFetch({
                path: 'cps/v1/bloom/subscribers/import',
                method: 'POST',
                body: formData,
            });

            console.log(result);
            setResult(result);
            setStep(2);
        } catch (err) {
            const message = err?.message || err?.code || __('Import failed.', 'cps-bloom');
            setError(message);
        } finally {
            setUploading(false);
        }
    }

    function handleDone() {
        onComplete(result);
    }

    return (
        <Modal
            title={STEP_TITLES[step]}
            onRequestClose={onClose}
            className="blm-import-modal"
            shouldCloseOnClickOutside={false}
        >
            {/* Step indicator */}
            <div className="blm-import__steps">
                {['Upload', 'Map columns', 'Done'].map((label, i) => (
                    <span
                        key={i}
                        className={`blm-import__step-dot${i === step ? ' is-active' : ''}${i < step ? ' is-done' : ''}`}
                    >
                        {i < step ? '✓' : i + 1} {label}
                    </span>
                ))}
            </div>

            {error && <p className="blm-import__error">{error}</p>}

            {uploading && (
                <div className="blm-import__loading">
                    <Spinner />
                    <span>{__('Importing…', 'cps-bloom')}</span>
                </div>
            )}

            {!uploading && step === 0 && (
                <StepUpload onNext={handleUploadNext} />
            )}

            {!uploading && step === 1 && (
                <StepMap
                    file={file}
                    headers={headers}
                    audience={audience}
                    onBack={() => setStep(0)}
                    onNext={handleMapNext}
                />
            )}

            {!uploading && step === 2 && result && (
                <StepResult result={result} onClose={handleDone} />
            )}
        </Modal>
    );
}