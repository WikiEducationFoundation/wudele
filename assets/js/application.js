import * as Turbo from '@hotwired/turbo';
import { Application } from '@hotwired/stimulus';

import FormProposalsController from './controllers/form_proposals_controller.js';

const application = Application.start();
application.register('form-proposals', FormProposalsController);
