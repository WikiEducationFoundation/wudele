// This file is part of Wudele, a fork of Pollaris.
// Copyright 2026 Wiki Education Foundation
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

// Displays a proposal time in the time zone of the browser. When the local
// calendar date differs from the date under which the poll displays the
// slot (data-poll-date), the short local date is appended so that the slot
// is not mistaken for one on the displayed day.
export default class extends Controller {
    connect() {
        if (this.element.dataset.localized) return;

        const iso = this.element.getAttribute('datetime');

        if (!iso) return;

        const date = new Date(iso);

        if (isNaN(date)) return;

        const lang = document.documentElement.lang;

        let text = new Intl.DateTimeFormat(lang, {
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);

        const pollDate = this.element.dataset.pollDate;

        if (pollDate) {
            // en-CA always formats as YYYY-MM-DD, matching data-poll-date.
            const localDate = new Intl.DateTimeFormat('en-CA', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }).format(date);

            if (localDate !== pollDate) {
                const localDay = new Intl.DateTimeFormat(lang, {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'short',
                }).format(date);

                text = `${text} (${localDay})`;
            }
        }

        this.element.title = new Intl.DateTimeFormat(lang, {
            dateStyle: 'full',
            timeStyle: 'short',
        }).format(date);

        this.element.textContent = text;
        this.element.dataset.localized = 'true';
    }
}
