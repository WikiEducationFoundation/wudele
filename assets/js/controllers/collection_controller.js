// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container', 'prototype']

    static values = {
        index: Number,
        removeLabel: String,
    }

    connect () {
        this.refreshLabels();
    }

    addElement () {
        const element = this.prototypeTarget.content.firstElementChild.cloneNode(true);
        element.innerHTML = element.innerHTML.replace(/__name__/g, this.indexValue);

        this.containerTarget.appendChild(element);

        this.indexValue++;

        this.refreshLabels();
    }

    removeElement (event) {
        const target = event.target;
        const element = target.closest('[data-item="element"]');

        element.remove();

        this.refreshLabels();
    }

    refreshLabels () {
        const labels = this.containerTarget.querySelectorAll('label');
        labels.forEach((label, index) => {
            // Update the labels with the correct number.
            let labelPattern = label.dataset.labelPattern;

            if (!labelPattern) {
                // First time we refresh the labels, we save the content of
                // labels as patterns.
                labelPattern = label.innerHTML;
                label.dataset.labelPattern = labelPattern;
            }

            label.innerHTML = labelPattern.replace(/__number__/, index + 1);
        });
    }
}
