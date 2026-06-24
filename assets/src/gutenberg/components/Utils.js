/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SnackbarList } from '@wordpress/components';
import { useRef, useState, useCallback, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { parse } from '@wordpress/blocks';

export const Notices = () => {
	const notices = useSelect(
		(select) =>
			select('core/notices')
				.getNotices()
				.filter((notice) => notice.type === 'snackbar'),
		[]
	);
	const { removeNotice } = useDispatch('core/notices');
	return (
		<SnackbarList
			className="edit-site-notices"
			notices={notices}
			onRemove={removeNotice}
		/>
	);
}

export const useBlockHistory = (initial) => {
	const [history, setHistory] = useState(() => ({
		past: [],
		present: initial ? parse(initial) : [],
		future: [],
	}));

	const onInput = useCallback((newBlocks) => {
		setHistory(h => ({ ...h, present: newBlocks }));
	}, []);

	const onChange = useCallback((newBlocks) => {
		setHistory(h => ({
			past: [...h.past, h.present],
			present: newBlocks,
			future: [],
		}));
	}, []);

	return { history, setHistory, onInput, onChange };
}

export const useAutosave = (campaignId, saveFn, delay = 15000) => {
	const timer = useRef(null);

	const scheduleAutosave = useCallback(() => {
		if (!campaignId) return;
		clearTimeout(timer.current);
		timer.current = setTimeout(() => saveFn({ silent: true }), delay);
	}, [campaignId, saveFn, delay]);

	useEffect(() => () => clearTimeout(timer.current), []);

	return scheduleAutosave;
}