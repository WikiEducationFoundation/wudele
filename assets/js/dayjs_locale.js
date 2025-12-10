// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

import dayjsLocaleFr from 'dayjs/locale/fr';
import dayjsLocaleIt from 'dayjs/locale/it';
import dayjsLocaleOc from 'dayjs/locale/oc-lnc';
import dayjsPluginLocalizedFormat from 'dayjs/plugin/localizedFormat';

export function setDayjsLocale (dayjs) {
    dayjs.extend(dayjsPluginLocalizedFormat);

    const lang = document.documentElement.lang;

    if (lang.startsWith('fr')) {
        dayjs.locale('fr');
    } else if (lang.startsWith('it')) {
        dayjs.locale('it');
    } else if (lang.startsWith('oc')) {
        dayjs.locale('oc-lnc');
    } else {
        dayjs.locale('en');
    }
}
