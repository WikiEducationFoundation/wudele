import * as Turbo from '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';

import CalendarController from './controllers/calendar_controller.js';
import CollectionController from './controllers/collection_controller.js';
import CopyToClipboardController from './controllers/copy_to_clipboard_controller.js';
import DatesFormController from './controllers/dates_form_controller.js';
import FormLeaveConfirmationController from './controllers/form_leave_confirmation_controller.js';
import LocalesController from './controllers/locales_controller.js';
import ModalController from './controllers/modal_controller.js';
import ModalOpenerController from './controllers/modal_opener_controller.js';
import NotificationController from './controllers/notification_controller.js';
import PollPasswordController from './controllers/poll_password_controller.js';
import PollViewController from './controllers/poll_view_controller.js';
import ProtectedButtonController from './controllers/protected_button_controller.js';
import SlotsApplierController from './controllers/slots_applier_controller.js';

const application = Application.start();
application.register('calendar', CalendarController);
application.register('collection', CollectionController);
application.register('copy-to-clipboard', CopyToClipboardController);
application.register('dates-form', DatesFormController);
application.register('form-leave-confirmation', FormLeaveConfirmationController);
application.register('locales', LocalesController);
application.register('modal', ModalController);
application.register('modal-opener', ModalOpenerController);
application.register('notification', NotificationController);
application.register('poll-password', PollPasswordController);
application.register('poll-view', PollViewController);
application.register('protected-button', ProtectedButtonController);
application.register('slots-applier', SlotsApplierController);

// Make sure to visit the response when receiving the `turbo:frame-missing` event.
// This happens most of the time on redirection after submitting a form in a modal.
// Otherwise, "Content missing" would be displayed within the modal.
document.addEventListener('turbo:frame-missing', (event) => {
    event.preventDefault();
    event.detail.visit(event.detail.response);
});
