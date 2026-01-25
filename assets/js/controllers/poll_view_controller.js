// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return [
            'listButton',
            'tableButton',
            'listView',
            'tableView',
            'voteForm',
            'listVoteSlot',
            'tableVoteSlot'
        ];
    }

    connect() {
        const preferredView = this.getPreferredView();

        if (preferredView === 'list') {
            this.displayList();
        } else {
            this.displayTable();
        }
    }

    getPreferredView() {
        const preferredView = localStorage.getItem('preferred-poll-view');

        if (preferredView === 'list' || preferredView === 'table') {
            return preferredView;
        }

        return window.innerWidth <= 600 ? 'list' : 'table';
    }

    displayList() {
        localStorage.setItem('preferred-poll-view', 'list');

        this.listVoteSlotTargets.forEach((slot) => {
            const proposal = slot.dataset.proposal;
            const voteForm = this.getVoteFormForProposal(proposal);
            if (voteForm) {
                slot.appendChild(voteForm);
            }
        });

        this.listButtonTarget.style.display = 'none';
        this.listViewTarget.hidden = false;

        this.tableButtonTarget.style.display = 'inline-block';
        this.tableViewTarget.hidden = true;
    }

    displayTable() {
        localStorage.setItem('preferred-poll-view', 'table');

        this.tableVoteSlotTargets.forEach((slot) => {
            const proposal = slot.dataset.proposal;
            const voteForm = this.getVoteFormForProposal(proposal);
            if (voteForm) {
                slot.appendChild(voteForm);
            }
        });

        this.listButtonTarget.style.display = 'inline-block';
        this.listViewTarget.hidden = true;

        this.tableButtonTarget.style.display = 'none';
        this.tableViewTarget.hidden = false;
    }

    getVoteFormForProposal(proposal) {
        return this.voteFormTargets.find((form) => {
            return form.dataset.proposal === proposal;
        });
    }
}
