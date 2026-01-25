// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['scroller', 'leftObserver', 'rightObserver']

    connect () {
        const leftIntersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                this.element.dataset.shadowLeft = !entry.isIntersecting;
            });
        }, {
            root: this.scrollerTarget,
            rootMargin: "0px 0px 0px -125px",
            threshold: 1,
        });

        leftIntersectionObserver.observe(this.leftObserverTarget);

        const rightIntersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                this.element.dataset.shadowRight = !entry.isIntersecting;
            });
        }, {
            root: this.scrollerTarget,
            threshold: 1,
        });

        rightIntersectionObserver.observe(this.rightObserverTarget);
    }
}
