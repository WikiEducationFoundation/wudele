// This file is part of Bileto.
// Copyright 2022-2025 Probesys
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['view'];
    }

    static values = {
        first: String,
        second: String,
    }

    connect () {
        this.currentView = this.firstValue;
    }

    switch (event) {
        if (this.currentView === this.firstValue) {
            this.currentView = this.secondValue;
        } else {
            this.currentView = this.firstValue;
        }

        this.viewTargets.forEach((view) => {
            view.hidden = view.dataset.viewValue !== this.currentView;
        });
    }
}
