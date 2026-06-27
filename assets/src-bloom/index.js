import { createRoot } from '@wordpress/element';
import './style.scss';

// const id = document.getElementById('cps-bloom-contacts');
// if (id) {
//     const root = createRoot(id);
//     root.render(<Subscribers />);
// }

const SubsRoot = document.getElementById('cps-bloom-contacts');
if (SubsRoot) {
    import(/* webpackChunkName: "contacts" */ './contacts').then(({ default: Contacts }) => {
        const root = createRoot(SubsRoot);
        root.render(<Contacts />);
    });
}