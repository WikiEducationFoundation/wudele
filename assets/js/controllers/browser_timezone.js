// This file is part of Wudele, a fork of Pollaris.
// Copyright 2026 Wiki Education Foundation
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

// Preselects the browser's time zone in a <select> of IANA time zones,
// unless a value is already chosen.
export default class extends Controller {
    connect() {
        if (this.element.value) return;

        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if (!timezone) return;

        const option = this.element.querySelector(`option[value="${CSS.escape(timezone)}"]`);

        if (option) {
            this.element.value = timezone;
        }
    }
}
