import * as Turbo from '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';

import CollectionController from './controllers/collection_controller.js';

const application = Application.start();
application.register('collection', CollectionController);
