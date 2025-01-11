// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['copyable', 'button'];
    }

    copy () {
        let text;
        if (this.copyableTarget.hasAttribute('value')) {
            text = this.copyableTarget.getAttribute('value').trim();
        } else {
            text = this.copyableTarget.textContent.trim();
        }

        navigator.clipboard.writeText(text);

        const oldButtonTargetText = this.buttonTarget.innerText;
        this.buttonTarget.innerText = this.element.dataset.labelCopied;

        setTimeout(() => {
            this.buttonTarget.innerText = oldButtonTargetText;
        }, 2000);
    }
};
