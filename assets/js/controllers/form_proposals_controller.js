// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['proposalsContainer', 'proposalPrototype']

    static values = {
        index: Number,
        removeLabel: String,
    }

    connect () {
        this.refreshLabels();
    }

    addProposal () {
        const proposal = this.proposalPrototypeTarget.content.firstElementChild.cloneNode(true);
        proposal.innerHTML = proposal.innerHTML.replace(/__name__/g, this.indexValue);

        this.proposalsContainerTarget.appendChild(proposal);

        this.indexValue++;

        this.refreshLabels();
    }

    removeProposal (event) {
        const target = event.target;
        const proposal = target.closest('[data-item="proposal"]');

        proposal.remove();

        this.refreshLabels();
    }

    refreshLabels () {
        const labels = this.proposalsContainerTarget.querySelectorAll('label');
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
