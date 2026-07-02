// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['row', 'time', 'text']

    apply (event) {
        event.preventDefault();

        // Load all the slots collections in dates
        const dateCollections = document.querySelectorAll('[data-item="date-collection"]');
        const slotsRowsSelector = '[data-item="element"]';

        dateCollections.forEach((dateCollection) => {
            // Load the Stimulus "collection" controller of this element
            const collectionController = this.application.getControllerForElementAndIdentifier(dateCollection, 'collection');

            // Then, iterate over the different rows to add to the different
            // collections. A row is a (time, label) pair; either can be empty.
            this.rowTargets.forEach((row) => {
                const time = row.querySelector('input[type="time"]')?.value || '';
                const text = row.querySelector('input[type="text"]')?.value || '';

                if (!time && !text) {
                    return;
                }

                // Load the existing rows and check that the values don't
                // already exist.
                let slotsRows = dateCollection.querySelectorAll(slotsRowsSelector);

                const valueExists = Array.from(slotsRows).some((slotsRow) => {
                    const rowTime = slotsRow.querySelector('input[type="time"]')?.value || '';
                    const rowText = slotsRow.querySelector('input[type="text"]')?.value || '';

                    return rowTime === time && rowText === text;
                });

                if (valueExists) {
                    return;
                }

                // Then, add a new row and set its inputs to the row values.
                collectionController.addElement();

                slotsRows = dateCollection.querySelectorAll(slotsRowsSelector);

                if (slotsRows.length === 0) {
                    // There is no row, but it should never happen since we
                    // added an element just above.
                    return;
                }

                const lastRow = slotsRows[slotsRows.length - 1];
                const lastTime = lastRow.querySelector('input[type="time"]');
                const lastText = lastRow.querySelector('input[type="text"]');

                if (lastTime) {
                    lastTime.value = time;
                }

                if (lastText) {
                    lastText.value = text;
                }
            });
        });
    }
}
