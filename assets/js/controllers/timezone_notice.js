// This file is part of Wudele, a fork of Pollaris.
// Copyright 2026 Wiki Education Foundation
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

// Prepends to the time zone notice a sentence giving the detected browser
// time zone, in which the poll times are displayed.
export default class extends Controller {
    static values = { template: String }

    connect() {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if (!timezone || !this.templateValue) return;

        const localSentence = this.templateValue.replace('__timezone__', timezone);

        this.element.textContent = `${localSentence} ${this.element.textContent.trim()}`;
    }
}
