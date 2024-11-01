import * as Turbo from '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';

import CollectionController from './controllers/collection_controller.js';
import ModalController from './controllers/modal_controller.js';
import ModalOpenerController from './controllers/modal_opener_controller.js';
import SlotsApplierController from './controllers/slots_applier_controller.js';

const application = Application.start();
application.register('collection', CollectionController);
application.register('modal', ModalController);
application.register('modal-opener', ModalOpenerController);
application.register('slots-applier', SlotsApplierController);
