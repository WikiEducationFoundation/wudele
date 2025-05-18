// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static get targets () {
        return ['button', 'menu', 'menuitem'];
    }

    connect() {
        const isOpened = !this.menuTarget.hidden;
        this.buttonTarget.setAttribute('aria-haspopup', 'menu');
        this.buttonTarget.setAttribute('aria-expanded', isOpened);
        this.menuTarget.setAttribute('role', 'menu');
        this.menuitemTargets.forEach((item) => {
            item.setAttribute('role', 'menuitem');
        });
    }

    switchMenu() {
        const isOpened = !this.menuTarget.hidden;

        if (isOpened) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }

    openMenu() {
        this.buttonTarget.setAttribute('aria-expanded', true);
        this.menuTarget.hidden = false;
    }

    closeMenu() {
        this.buttonTarget.setAttribute('aria-expanded', false);
        this.menuTarget.hidden = true;
        this.buttonTarget.focus();
    }

    navigateUp() {
        const [focusedKey, focusedItem] = this.getFocusedItem();

        if (
            !focusedItem ||
            focusedKey === 0
        ) {
            this.menuitemTargets[this.menuitemTargets.length - 1].focus();
        } else {
            this.menuitemTargets[focusedKey - 1].focus();
        }
    }

    navigateDown() {
        const [focusedKey, focusedItem] = this.getFocusedItem();

        if (
            !focusedItem ||
            focusedKey === this.menuitemTargets.length - 1
        ) {
            this.menuitemTargets[0].focus();
        } else {
            this.menuitemTargets[focusedKey + 1].focus();
        }
    }

    getFocusedItem() {
        for (const [key, item] of this.menuitemTargets.entries()) {
            if (item === document.activeElement) {
                return [key, item];
            }
        }

        return [null, null];
    }
}
