(function(window, BX) {
    'use strict';

    if (!BX || !BX.namespace) {
        return;
    }

    BX.namespace('BX.Rexp');

    function localize(code, fallback) {
        var value = BX && BX.message ? BX.message(code) : '';
        return value || fallback || code;
    }


    function getVueApi() {
        if (BX.Vue3 && BX.Vue3.BitrixVue && typeof BX.Vue3.BitrixVue.createApp === 'function') {
            return BX.Vue3.BitrixVue;
        }
        if (BX.Vue3 && typeof BX.Vue3.createApp === 'function') {
            return BX.Vue3;
        }
        return null;
    }

    function normalizeComparable(value) {
        if (Array.isArray(value)) {
            return value.map(normalizeComparable).join('|');
        }
        return String(value == null ? '' : value).trim().toLowerCase();
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') {
            return window.CSS.escape(value);
        }
        return String(value).replace(/[^a-zA-Z0-9_-]/g, '_');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function normalizeSelectorId(value, type) {
        var id = String(value == null ? '' : value).trim();
        if (id.indexOf(':') >= 0) {
            id = id.split(':').pop();
        }
        if (type === 'department') {
            var match = id.match(/\d+$/);
            return match ? match[0] : id.replace(/\D+/g, '');
        }
        return id;
    }

    function normalizeMultipleValue(value) {
        if (Array.isArray(value)) {
            return value;
        }
        if (value == null || value === '') {
            return [];
        }
        return String(value).split(',').filter(Boolean);
    }

    function fieldSupportsMultiple(type) {
        return ['checkbox_list', 'enumeration', 'file', 'user', 'department', 'entity_selector', 'userfield'].indexOf(String(type || '')) >= 0;
    }


    function toBool(value) {
        if (value === true || value === 1) { return true; }
        if (value === false || value === 0 || value === null || value === undefined) { return false; }
        var normalized = String(value).toLowerCase();
        return normalized === 'y' || normalized === 'yes' || normalized === 'true' || normalized === '1';
    }

    function isValidHexColor(value) {
        return /^#[0-9a-f]{6}$/i.test(String(value || '').trim());
    }

    function safeHexColor(value, fallback) {
        value = String(value || '').trim();
        return isValidHexColor(value) ? value : fallback;
    }

    function clampIntString(value, min, max, fallback) {
        var number = parseInt(String(value || '').replace(/[^0-9-]/g, ''), 10);
        if (!isFinite(number)) {
            number = parseInt(String(fallback || min), 10);
        }
        number = Math.max(min, Math.min(max, number));
        return String(number);
    }

    function safeWidth(value, fallback) {
        value = String(value || '').trim();
        if (value === '100%') { return value; }
        return clampIntString(value, 320, 1440, fallback || '760');
    }

    function safeEnum(value, allowed, fallback) {
        value = String(value || '').trim();
        return allowed.indexOf(value) >= 0 ? value : fallback;
    }

    function trimText(value, maxLength) {
        value = String(value == null ? '' : value).trim();
        return value.length > maxLength ? value.slice(0, maxLength) : value;
    }

    function sanitizeCssClassList(value) {
        return String(value || '')
            .replace(/[^a-zA-Z0-9_ -]/g, '')
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 6)
            .join(' ');
    }

    function sanitizePublicUrl(value) {
        value = String(value || '').trim();
        if (!value) {
            return '';
        }
        if (/[\u0000-\u001F<>"']/.test(value)) {
            return '';
        }
        if (/^(javascript|data|vbscript):/i.test(value)) {
            return '';
        }
        if (/^https:\/\/[^\s]+$/i.test(value)) {
            return value;
        }
        if (/^\/(?!\/)[^\s]*$/i.test(value)) {
            return value;
        }
        return '';
    }


    var REXP_ICON_CLASSES = {
        success: 'ui-btn-icon-success',
        done: 'ui-btn-icon-done',
        info: 'ui-btn-icon-info',
        alert: 'ui-btn-icon-alert',
        fail: 'ui-btn-icon-fail',
        task: 'ui-btn-icon-task',
        list: 'ui-btn-icon-list',
        page: 'ui-btn-icon-page',
        mail: 'ui-btn-icon-mail',
        chat: 'ui-btn-icon-chat',
        business: 'ui-btn-icon-business',
        'business-confirm': 'ui-btn-icon-business-confirm',
        'business-warning': 'ui-btn-icon-business-warning'
    };
    function normalizeIconCode(value, fallback) {
        value = String(value == null ? '' : value).trim();
        if (REXP_ICON_CLASSES[value]) {
            return value;
        }
        return fallback || 'info';
    }

    function isOriginalColorIcon(icon) {
        icon = normalizeIconCode(icon, 'info');
        return icon === 'success' || icon === 'fail';
    }

    function renderBitrixIcon(icon) {
        icon = normalizeIconCode(icon, 'info');
        var iconClass = REXP_ICON_CLASSES[icon] || REXP_ICON_CLASSES.info;
        if (isOriginalColorIcon(icon)) {
            return '<span class="rexp-public-form__bitrix-icon ui-btn ' + iconClass + ' is-original-color" aria-hidden="true"></span>';
        }
        return '<span class="rexp-public-form__bitrix-icon ui-btn ' + iconClass + ' is-mask-color" aria-hidden="true"><span class="rexp-form-icon-mask rexp-form-icon-mask--' + escapeHtml(icon) + '"></span></span>';
    }

    function normalizeSidebarNotes(notes, fallbackNote) {
        var source = Array.isArray(notes) ? notes.slice(0, 4) : [];
        if (!source.length && fallbackNote && (fallbackNote.enabled || fallbackNote.text)) {
            source = [fallbackNote];
        }
        return source.map(function(note, index) {
            note = note && typeof note === 'object' ? note : {};
            return {
                uid: trimText(note.uid || ('note_' + (index + 1)), 40) || ('note_' + (index + 1)),
                enabled: toBool(note.enabled),
                title: trimText(note.title || (index === 0 ? localize('REXP_FORM_RUNTIME_JS_001') : localize('REXP_FORM_RUNTIME_JS_002')), 80) || (index === 0 ? localize('REXP_FORM_RUNTIME_JS_003') : localize('REXP_FORM_RUNTIME_JS_004')),
                text: trimText(note.text || '', 600),
                placement: safeEnum(note.placement || note.position || 'right', ['top', 'right', 'bottom', 'left'], 'right'),
                position: safeEnum(note.position || note.placement || 'right', ['left', 'right'], 'right'),
                variant: safeEnum(note.variant || 'info', ['info', 'warning', 'success', 'danger'], 'info'),
                icon: normalizeIconCode(note.icon, 'info'),
                width: clampIntString(note.width || '220', 160, 360, '220')
            };
        });
    }

    function sanitizeLayout(layout, base) {
        base = base || {};
        layout.colors.primary = safeHexColor(layout.colors.primary, '#2fc6f6');
        layout.colors.background = safeHexColor(layout.colors.background, '#f5f7fb');
        layout.colors.card = safeHexColor(layout.colors.card, '#ffffff');
        layout.colors.text = safeHexColor(layout.colors.text, '#172b4d');

        layout.theme = safeEnum(layout.theme, ['light', 'dark', 'corporate', 'soft', 'emerald', 'mono', 'rexpress'], 'light');
        layout.width = safeWidth(layout.width, '760');
        layout.density = safeEnum(layout.density, ['compact', 'normal', 'comfortable'], 'normal');

        layout.typography.font = safeEnum(layout.typography.font, ['system', 'arial', 'georgia', 'inter', 'onest'], 'system');
        layout.typography.size = clampIntString(layout.typography.size, 13, 20, '15');
        layout.shape.radius = clampIntString(layout.shape.radius, 0, 40, '14');
        layout.shape.fieldRadius = clampIntString(layout.shape.fieldRadius, 0, 24, '10');

        layout.surface.fieldStyle = safeEnum(layout.surface.fieldStyle, ['outline', 'filled', 'underline'], 'outline');
        layout.surface.shadow = safeEnum(layout.surface.shadow, ['none', 'soft', 'deep'], 'soft');
        layout.surface.border = safeEnum(layout.surface.border, ['none', 'subtle', 'accent'], 'subtle');

        layout.container.align = safeEnum(layout.container.align, ['left', 'center', 'right'], 'center');
        layout.container.padding = safeEnum(layout.container.padding, ['compact', 'normal', 'wide'], 'normal');
        layout.container.backgroundMode = safeEnum(layout.container.backgroundMode, ['cover', 'contain', 'repeat'], 'cover');
        layout.container.backgroundOverlay = safeEnum(layout.container.backgroundOverlay, ['none', 'light', 'dark'], 'light');
        layout.container.backgroundImageUrl = sanitizePublicUrl(layout.container.backgroundImageUrl);

        layout.media.icon = normalizeIconCode(layout.media.icon, 'info');
        layout.media.imageUrl = sanitizePublicUrl(layout.media.imageUrl);
        layout.media.imageMode = safeEnum(layout.media.imageMode, ['cover', 'contain'], 'cover');
        layout.media.imageHeight = clampIntString(layout.media.imageHeight, 80, 360, '140');

        layout.icons.enabled = toBool(layout.icons.enabled);
        layout.icons.formEnabled = toBool(layout.icons.formEnabled);
        layout.icons.noteEnabled = toBool(layout.icons.noteEnabled);
        layout.icons.successEnabled = toBool(layout.icons.successEnabled);
        layout.icons.shape = safeEnum(layout.icons.shape, ['none', 'circle', 'rounded', 'square'], 'rounded');
        layout.icons.background = safeHexColor(layout.icons.background, '#e8f7ff');
        layout.icons.color = safeHexColor(layout.icons.color, '#525c69');
        layout.icons.size = safeEnum(layout.icons.size, ['sm', 'md', 'lg', 'xl'], 'md');

        layout.submitButton.style = safeEnum(layout.submitButton.style, ['crm', 'primary', 'outline'], 'crm');
        layout.submitButton.align = safeEnum(layout.submitButton.align, ['left', 'center', 'right', 'stretch'], 'left');
        layout.submitButton.textColor = safeEnum(layout.submitButton.textColor, ['dark', 'light'], 'dark');
        layout.submitButton.size = safeEnum(layout.submitButton.size, ['sm', 'default', 'lg'], 'default');

        layout.sidebarNote.enabled = toBool(layout.sidebarNote.enabled);
        layout.sidebarNote.title = trimText(layout.sidebarNote.title, 80) || localize('REXP_FORM_RUNTIME_JS_005');
        layout.sidebarNote.text = trimText(layout.sidebarNote.text, 600);
        layout.sidebarNote.position = safeEnum(layout.sidebarNote.position, ['left', 'right'], 'right');
        layout.sidebarNote.variant = safeEnum(layout.sidebarNote.variant, ['info', 'warning', 'success', 'danger'], 'info');
        layout.sidebarNote.icon = normalizeIconCode(layout.sidebarNote.icon, 'alert');
        layout.sidebarNote.width = clampIntString(layout.sidebarNote.width, 160, 360, '220');
        layout.sidebarNotes = normalizeSidebarNotes(layout.sidebarNotes, layout.sidebarNote);
        if (layout.sidebarNotes.length) { layout.sidebarNote = Object.assign({}, layout.sidebarNote, layout.sidebarNotes[0]); }

        layout.responsive.enabled = toBool(layout.responsive.enabled);
        layout.responsive.breakpoint = safeEnum(layout.responsive.breakpoint, ['480', '640', '760'], '760');
        layout.responsive.fullWidth = toBool(layout.responsive.fullWidth);
        layout.responsive.compact = toBool(layout.responsive.compact);
        layout.responsive.sideNoteMobile = safeEnum(layout.responsive.sideNoteMobile, ['top', 'bottom', 'hide'], 'top');
        layout.responsive.imageMobile = safeEnum(layout.responsive.imageMobile, ['show', 'hide'], 'show');

        layout.postSubmit.mode = safeEnum(layout.postSubmit.mode, ['message', 'hideForm', 'hide_form', 'redirect', 'reload'], 'message');
        layout.postSubmit.title = trimText(layout.postSubmit.title, 90) || localize('REXP_FORM_RUNTIME_JS_006');
        layout.postSubmit.icon = normalizeIconCode(layout.postSubmit.icon, 'success');
        layout.postSubmit.imageUrl = sanitizePublicUrl(layout.postSubmit.imageUrl);
        layout.postSubmit.message = trimText(layout.postSubmit.message, 800);
        layout.postSubmit.buttonText = trimText(layout.postSubmit.buttonText, 80);
        layout.postSubmit.redirectUrl = sanitizePublicUrl(layout.postSubmit.redirectUrl);
        layout.postSubmit.showAgainButton = toBool(layout.postSubmit.showAgainButton);

        layout.advanced.cssClass = sanitizeCssClassList(layout.advanced.cssClass);
        layout.advanced.animation = safeEnum(layout.advanced.animation, ['none', 'fade', 'slide'], 'none');
        return layout;
    }

    function ensureLayout(layout) {
        layout = layout && typeof layout === 'object' ? layout : {};
        var colors = Object.assign({primary: '#2fc6f6', background: '#f5f7fb', card: '#ffffff', text: '#172b4d'}, layout.colors || {});
        var typography = Object.assign({font: 'system', size: '15'}, layout.typography || {});
        var shape = Object.assign({radius: '14', fieldRadius: '10'}, layout.shape || {});
        var surface = Object.assign({fieldStyle: 'outline', shadow: 'soft', border: 'subtle'}, layout.surface || {});
        var container = Object.assign({align: 'center', padding: 'normal', backgroundImageUrl: '', backgroundMode: 'cover', backgroundOverlay: 'light'}, layout.container || {});
        var media = Object.assign({icon: 'info', imageUrl: '', imageMode: 'cover', imageHeight: '140'}, layout.media || {});
        var icons = Object.assign({enabled: true, formEnabled: true, noteEnabled: true, successEnabled: true, shape: 'rounded', background: '#e8f7ff', color: '#525c69', size: 'md'}, layout.icons || {});
        var submitButton = Object.assign({style: 'crm', align: 'left', textColor: 'dark', size: 'default'}, layout.submitButton || {});
        var sidebarNote = Object.assign({enabled: false, title: localize('REXP_FORM_RUNTIME_JS_007'), text: '', position: 'right', variant: 'info', icon: 'alert', width: '220'}, layout.sidebarNote || {});
        var sidebarNotes = normalizeSidebarNotes(layout.sidebarNotes, sidebarNote);
        var responsive = Object.assign({enabled: true, breakpoint: '760', fullWidth: true, compact: true, sideNoteMobile: 'top', imageMobile: 'show'}, layout.responsive || {});
        var postSubmit = Object.assign({mode: 'message', title: localize('REXP_FORM_RUNTIME_JS_008'), icon: 'success', imageUrl: '', message: '', buttonText: '', redirectUrl: '', showAgainButton: false}, layout.postSubmit || {});
        var advanced = Object.assign({cssClass: '', animation: 'none'}, layout.advanced || {});
        return sanitizeLayout(Object.assign({type: 'design', columns: 12, theme: 'light', width: '760', density: 'normal'}, layout, {colors: colors, typography: typography, shape: shape, surface: surface, container: container, media: media, icons: icons, submitButton: submitButton, sidebarNote: sidebarNote, sidebarNotes: sidebarNotes, postSubmit: postSubmit, responsive: responsive, advanced: advanced}));
    }

    BX.Rexp.FormRuntime = BX.Rexp.FormRuntime || {};

    BX.Rexp.FormRuntime.mount = function(selector, options) {
        var node = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!node) {
            return;
        }

        var vueApi = getVueApi();
        if (!vueApi) {
            node.innerHTML = localize('REXP_FORM_RUNTIME_JS_009');
            return;
        }

        if (node.__rexpFormRuntimeApp) {
            node.__rexpFormRuntimeApp.unmount();
            node.__rexpFormRuntimeApp = null;
        }

        var app = vueApi.createApp({
            data: function() {
                return {
                    controller: options.controller || 'rexp:form.Runtime',
                    formCode: options.formCode || '',
                    mode: options.mode || 'create',
                    entryId: Number(options.entryId || 0),
                    loading: true,
                    submitting: false,
                    form: null,
                    values: {},
                    files: {},
                    errors: [],
                    success: '',
                    selectors: {},
                    selectorMountTimer: null,
                    applyingRules: false
                };
            },
            computed: {
                schema: function() {
                    return this.form && this.form.schema ? this.form.schema : {fields: [], sections: [], settings: {}};
                },
                fields: function() {
                    return Array.isArray(this.schema.fields) ? this.schema.fields : [];
                },
                sections: function() {
                    return Array.isArray(this.schema.sections) ? this.schema.sections : [];
                },
                settings: function() {
                    return this.schema.settings || {};
                },
                layout: function() {
                    return ensureLayout(this.schema.layout || {});
                },
                formClass: function() {
                    var custom = this.layout.advanced && this.layout.advanced.cssClass ? sanitizeCssClassList(this.layout.advanced.cssClass).split(/\s+/).filter(Boolean) : [];
                    var responsive = this.layout.responsive || {};
                    var responsiveClasses = responsive.enabled === false ? [] : ['rexp-public-form--mobile-note-' + (responsive.sideNoteMobile || 'top'), 'rexp-public-form--mobile-image-' + (responsive.imageMobile || 'show')].concat(responsive.fullWidth ? ['rexp-public-form--mobile-full'] : []).concat(responsive.compact ? ['rexp-public-form--mobile-compact'] : []);
                    return ['rexp-public-form', 'rexp-public-form--theme-' + (this.layout.theme || 'light'), 'rexp-public-form--density-' + (this.layout.density || 'normal'), 'rexp-public-form--field-' + ((this.layout.surface && this.layout.surface.fieldStyle) || 'outline'), 'rexp-public-form--shadow-' + ((this.layout.surface && this.layout.surface.shadow) || 'soft'), 'rexp-public-form--border-' + ((this.layout.surface && this.layout.surface.border) || 'subtle'), 'rexp-public-form--align-' + ((this.layout.container && this.layout.container.align) || 'center'), 'rexp-public-form--pad-' + ((this.layout.container && this.layout.container.padding) || 'normal'), 'rexp-public-form--bg-' + ((this.layout.container && this.layout.container.backgroundOverlay) || 'light'), 'rexp-public-form--anim-' + ((this.layout.advanced && this.layout.advanced.animation) || 'none'), 'rexp-public-form--icon-shape-' + ((this.layout.icons && this.layout.icons.shape) || 'rounded'), 'rexp-public-form--icon-size-' + ((this.layout.icons && this.layout.icons.size) || 'md')].concat(responsiveClasses).concat(custom);
                },
                formStyle: function() {
                    var layout = this.layout;
                    var fontMap = {system: 'var(--ui-font-family-primary, Arial, sans-serif)', onest: 'Onest, var(--ui-font-family-primary, Arial, sans-serif)', arial: 'Arial, sans-serif', georgia: 'Georgia, serif', inter: 'Inter, Arial, sans-serif'};
                    var width = String(layout.width || '760');
                    if (/^\d+$/.test(width)) { width += 'px'; }
                    return {'--rexp-form-primary': layout.colors.primary || '#2fc6f6', '--rexp-form-bg': layout.colors.background || '#f5f7fb', '--rexp-form-card': layout.colors.card || '#ffffff', '--rexp-form-text': layout.colors.text || '#172b4d', '--rexp-form-radius': String(layout.shape.radius || '14') + 'px', '--rexp-form-field-radius': String(layout.shape.fieldRadius || '10') + 'px', '--rexp-form-font': fontMap[layout.typography.font] || fontMap.system, '--rexp-form-font-size': String(layout.typography.size || '15') + 'px', '--rexp-form-note-width': String((layout.sidebarNote && layout.sidebarNote.width) || '220') + 'px', '--rexp-form-image-height': String((layout.media && layout.media.imageHeight) || '140') + 'px', '--rexp-form-page-image': layout.container && layout.container.backgroundImageUrl ? 'url(' + layout.container.backgroundImageUrl + ')' : 'none', '--rexp-form-width': width, '--rexp-form-icon-bg': (layout.icons && layout.icons.background) || '#e8f7ff', '--rexp-form-icon-color': (layout.icons && layout.icons.color) || '#525c69'};
                },
                headerImageStyle: function() {
                    if (!this.layout.media || !this.layout.media.imageUrl) { return {}; }
                    return {backgroundImage: 'url(' + this.layout.media.imageUrl + ')', backgroundSize: (this.layout.media.imageMode === 'contain' ? 'contain' : 'cover')};
                },
                sideNotes: function() {
                    return normalizeSidebarNotes(this.layout.sidebarNotes, this.layout.sidebarNote || {}).filter(function(note) { return note.enabled && note.text; });
                },
                warningGroups: function() {
                    var groups = {top: [], right: [], bottom: [], left: []};
                    this.sideNotes.forEach(function(note) {
                        var placement = safeEnum(note.placement || note.position || 'right', ['top', 'right', 'bottom', 'left'], 'right');
                        groups[placement].push(note);
                    });
                    return groups;
                },
                iconsEnabled: function() { return !this.layout.icons || this.layout.icons.enabled !== false; },
                showFormIcon: function() { return this.iconsEnabled && (!this.layout.icons || this.layout.icons.formEnabled !== false) && this.layout.media && this.layout.media.icon; },
                showSideNoteIcon: function() { return this.iconsEnabled && (!this.layout.icons || this.layout.icons.noteEnabled !== false); },
                showSuccessIcon: function() { return this.iconsEnabled && (!this.layout.icons || this.layout.icons.successEnabled !== false) && this.layout.postSubmit && this.layout.postSubmit.icon; },
                submitButtonClass: function() {
                    var button = this.layout.submitButton || {};
                    return ['rexp-public-form__submit-row', 'is-' + (button.align || 'left'), 'is-style-' + (button.style || 'crm'), 'is-text-' + (button.textColor || 'dark'), 'is-size-' + (button.size || 'default')];
                },
                successIcon: function() { return normalizeIconCode(this.layout.postSubmit && this.layout.postSubmit.icon, 'success'); },
                successTitle: function() { return (this.layout.postSubmit && this.layout.postSubmit.title) || localize('REXP_FORM_RUNTIME_JS_010'); },
                successImageStyle: function() { var url = this.layout.postSubmit && this.layout.postSubmit.imageUrl ? sanitizePublicUrl(this.layout.postSubmit.imageUrl) : ''; return url ? {backgroundImage: 'url(' + url + ')'} : {}; },
                successButtonText: function() { return (this.layout.postSubmit && this.layout.postSubmit.buttonText) || ''; },
                successRedirectUrl: function() { return this.layout.postSubmit && this.layout.postSubmit.redirectUrl ? sanitizePublicUrl(this.layout.postSubmit.redirectUrl) : ''; },
                successShowAgain: function() { return !!(this.layout.postSubmit && this.layout.postSubmit.showAgainButton); },
                title: function() {
                    return this.form && this.form.name ? this.form.name : (options.title || localize('REXP_FORM_RUNTIME_JS_011'));
                },
                submitText: function() {
                    return this.submitting ? localize('REXP_FORM_RUNTIME_JS_012') : (this.settings.submitText || localize('REXP_FORM_RUNTIME_JS_013'));
                },
                fieldStateMap: function() {
                    var map = {};
                    this.fields.forEach(function(field) {
                        if (!field || !field.code) {
                            return;
                        }
                        map[field.code] = {
                            visible: true,
                            required: !!(field.required || field.mandatory),
                            disabled: !!(field.disabled || field.readonly),
                            clearOnHide: false,
                            hasValueOverride: false,
                            value: null
                        };
                    });
                    this.applyReadonlyRules(map);
                    return map;
                },
                sectionBlocks: function() {
                    var self = this;
                    var blocks = [];
                    var used = {};

                    this.sections.forEach(function(section) {
                        if (!section || !section.uid) {
                            return;
                        }
                        var sectionFields = self.fields.filter(function(field) {
                            return String(field.sectionUid || '') === String(section.uid || '') && self.isFieldVisible(field);
                        });
                        if (!sectionFields.length) {
                            return;
                        }
                        used[String(section.uid)] = true;
                        blocks.push({
                            uid: section.uid,
                            title: section.title || '',
                            description: section.description || '',
                            fields: sectionFields
                        });
                    });

                    var freeFields = this.fields.filter(function(field) {
                        var sectionUid = String(field.sectionUid || '');
                        return (!sectionUid || !used[sectionUid]) && self.isFieldVisible(field);
                    });
                    if (freeFields.length) {
                        blocks.push({uid: 'free', title: '', description: '', fields: freeFields});
                    }

                    return blocks;
                }
            },
            created: function() {
                this.loadForm();
            },
            watch: {
                values: {
                    handler: function() {
                        if (!this.applyingRules) {
                            this.applyMutatingRules();
                        }
                    },
                    deep: true
                }
            },
            mounted: function() {
                this.scheduleSelectorMount();
            },
            updated: function() {
                this.scheduleSelectorMount();
            },
            beforeUnmount: function() {
                if (this.selectorMountTimer) {
                    clearTimeout(this.selectorMountTimer);
                }
            },
            methods: {
                sideNoteClass: function(note) {
                    note = note || {};
                    return ['rexp-public-form__side-note', 'is-' + (note.placement || note.position || 'right'), 'is-' + (note.variant || 'info')];
                },
                iconHtml: function(icon) { return renderBitrixIcon(icon); },
                resetSuccess: function() { this.success = ''; this.errors = []; },
                request: function(action, data) {
                    return BX.ajax.runAction(this.controller + '.' + action, {data: data || {}}).then(function(response) {
                        return response.data || {};
                    });
                },
                loadForm: function() {
                    var self = this;
                    this.loading = true;
                    this.errors = [];
                    this.request('getForm', {
                        code: this.formCode,
                        entryId: this.entryId,
                        mode: this.mode || 'create'
                    }).then(function(data) {
                        self.form = data.item || null;
                        self.values = self.normalizeInitialValues(self.form && self.form.initialValues ? self.form.initialValues : {});
                        self.loading = false;
                    }).catch(function(error) {
                        self.loading = false;
                        self.errors = self.extractErrors(error, localize('REXP_FORM_RUNTIME_JS_014'));
                    });
                },
                normalizeInitialValues: function(source) {
                    var values = Object.assign({}, source || {});
                    this.fields.forEach(function(field) {
                        if (!field || !field.code) {
                            return;
                        }
                        if (values[field.code] === undefined) {
                            values[field.code] = fieldSupportsMultiple(field.type) && field.multiple ? [] : '';
                        }
                        if ((field.type === 'checkbox_list' || (fieldSupportsMultiple(field.type) && field.multiple)) && !Array.isArray(values[field.code])) {
                            values[field.code] = normalizeMultipleValue(values[field.code]);
                        }
                    });
                    return values;
                },
                fieldItems: function(field) {
                    return Array.isArray(field.items) ? field.items : [];
                },
                optionValue: function(item) {
                    return String(item && (item.value != null ? item.value : (item.id != null ? item.id : '')));
                },
                optionLabel: function(item) {
                    return String(item && (item.label || item.title || item.value || item.id || ''));
                },
                isFieldVisible: function(field) {
                    return !!(field && field.code && (!this.fieldStateMap[field.code] || this.fieldStateMap[field.code].visible !== false));
                },
                isFieldRequired: function(field) {
                    return !!(field && field.code && this.fieldStateMap[field.code] && this.fieldStateMap[field.code].required);
                },
                isFieldDisabled: function(field) {
                    return !!(field && field.code && this.fieldStateMap[field.code] && this.fieldStateMap[field.code].disabled);
                },
                isFieldMultiple: function(field) {
                    return !!(field && field.multiple && fieldSupportsMultiple(field.type));
                },
                fieldView: function(field) {
                    return field && field.ui && field.ui.view ? String(field.ui.view) : '';
                },
                isEnumSelect: function(field) {
                    return !!(field && (field.type === 'select' || (field.type === 'enumeration' && (this.fieldView(field) === 'select' || this.fieldView(field) === 'tag_input' || !this.isFieldMultiple(field)))));
                },
                isEnumRadio: function(field) {
                    return !!(field && (field.type === 'radio' || (field.type === 'enumeration' && this.fieldView(field) === 'radio')));
                },
                isEnumCheckbox: function(field) {
                    return !!(field && (field.type === 'checkbox_list' || (field.type === 'enumeration' && this.fieldView(field) === 'checkbox')));
                },
                isSelectorField: function(field) {
                    return !!(field && (field.type === 'user' || field.type === 'entity_selector' || (field.type === 'enumeration' && this.fieldView(field) === 'entity_selector')));
                },
                isDepartmentField: function(field) {
                    return !!(field && (field.type === 'department' || field.relationType === 'department'));
                },
                canUseEntitySelector: function() {
                    return !!(BX.UI && BX.UI.EntitySelector && BX.UI.EntitySelector.TagSelector);
                },
                selectorType: function(field) {
                    if (!field) { return ''; }
                    if (field.relationType) { return field.relationType; }
                    if ((field.type === 'entity_selector' || field.type === 'enumeration') && field.selectorEntities && field.selectorEntities.length) { return field.selectorEntities[0]; }
                    if (field.source && Array.isArray(field.source.entities) && field.source.entities.length) { return field.source.entities[0]; }
                    return field.type || '';
                },
                inputType: function(type) {
                    if (['number', 'integer', 'double', 'money'].indexOf(type) >= 0) { return 'number'; }
                    if (type === 'date') { return 'date'; }
                    if (type === 'datetime') { return 'datetime-local'; }
                    if (type === 'email') { return 'email'; }
                    if (type === 'phone') { return 'tel'; }
                    if (type === 'url') { return 'url'; }
                    return 'text';
                },
                fieldClass: function(field) {
                    var type = field && field.type ? String(field.type) : 'string';
                    var result = ['rexp-public-form__field', 'rexp-public-form__field--' + cssEscape(type)];
                    if (this.isSelectorField(field)) { result.push('rexp-public-form__field--selector'); }
                    if (this.isDepartmentField(field)) { result.push('rexp-public-form__field--department'); }
                    if (this.isFieldRequired(field)) { result.push('is-required'); }
                    if (this.isFieldDisabled(field)) { result.push('is-disabled'); }
                    return result;
                },
                onInput: function(field, value) {
                    if (!field || !field.code) {
                        return;
                    }
                    this.values[field.code] = value;
                    this.applyMutatingRules();
                },
                onCheckbox: function(field, event) {
                    this.onInput(field, event.target.checked ? 1 : 0);
                },
                onFile: function(field, event) {
                    if (!field || !field.code) {
                        return;
                    }
                    this.files[field.code] = event.target.files || [];
                },
                scheduleSelectorMount: function() {
                    var self = this;
                    if (this.selectorMountTimer) {
                        clearTimeout(this.selectorMountTimer);
                    }
                    this.selectorMountTimer = setTimeout(function() {
                        self.initEntitySelectors();
                    }, 0);
                },
                initEntitySelectors: function() {
                    var self = this;
                    if (!BX.UI || !BX.UI.EntitySelector || !BX.UI.EntitySelector.TagSelector) {
                        return;
                    }

                    var rootNode = this.$el && typeof this.$el.querySelectorAll === 'function' ? this.$el : node;
                    if (!rootNode || typeof rootNode.querySelectorAll !== 'function') {
                        return;
                    }

                    Array.prototype.slice.call(rootNode.querySelectorAll('[data-selector-field]')).forEach(function(container) {
                        var code = container.getAttribute('data-selector-field');
                        var type = container.getAttribute('data-selector-type');
                        if (!code || self.selectors[code] || !self.isFieldVisible({code: code})) {
                            return;
                        }

                        var isMultiple = container.getAttribute('data-selector-multiple') === 'Y';
                        var field = self.fields.filter(function(item) { return item && item.code === code; })[0] || {};
                        var sourceEntities = field.source && Array.isArray(field.source.entities) ? field.source.entities : (Array.isArray(field.selectorEntities) ? field.selectorEntities : []);
                        var entities = type === 'department'
                            ? [{id: 'structure-node'}]
                            : (sourceEntities.length
                                ? sourceEntities.map(function(entityId) { return {id: entityId}; })
                                : [{id: 'user'}]);

                        var selector = new BX.UI.EntitySelector.TagSelector({
                            id: 'rexp-form-runtime-' + code,
                            multiple: isMultiple,
                            dialogOptions: {
                                context: 'REXP_FORM_RUNTIME',
                                entities: entities,
                                recentTabOptions: type === 'department' ? {visible: false, stub: false} : undefined,
                                recentItemsLimit: type === 'department' ? 0 : undefined,
                                showAvatars: type !== 'department',
                                compactView: type === 'department'
                            },
                            events: {
                                onTagAdd: function(event) {
                                    var tag = event.getData().tag;
                                    var current = isMultiple ? normalizeMultipleValue(self.values[code]) : [];
                                    var id = normalizeSelectorId(tag.getId(), type);
                                    if (isMultiple) {
                                        if (current.indexOf(id) < 0) {
                                            current.push(id);
                                        }
                                        self.values[code] = current;
                                    } else {
                                        self.values[code] = id;
                                    }
                                },
                                onTagRemove: function(event) {
                                    if (!isMultiple) {
                                        self.values[code] = '';
                                        return;
                                    }
                                    var tag = event && event.getData ? event.getData().tag : null;
                                    var removedId = tag ? normalizeSelectorId(tag.getId(), type) : '';
                                    var current = normalizeMultipleValue(self.values[code]);
                                    self.values[code] = removedId ? current.filter(function(id) { return String(id) !== removedId; }) : current;
                                }
                            }
                        });

                        selector.renderTo(container);
                        self.selectors[code] = selector;
                    });
                },
                applyReadonlyRules: function(map) {
                    var self = this;
                    this.extractRuntimeRules().forEach(function(rule) {
                        if (!rule || rule.enabled === false) {
                            return;
                        }
                        var matched = self.isRuleMatched(rule);
                        self.getRuleActions(rule).forEach(function(action) {
                            var fieldCode = action.fieldCode || action.field || '';
                            if (!fieldCode || !map[fieldCode]) {
                                return;
                            }

                            var actionType = self.normalizeActionType(action.type || action.action || '');
                            if (actionType === 'show') {
                                map[fieldCode].visible = matched;
                                return;
                            }
                            if (actionType === 'hide') {
                                map[fieldCode].visible = !matched;
                                if (matched && action.clearOnHide) {
                                    map[fieldCode].clearOnHide = true;
                                }
                                return;
                            }
                            if (!matched) {
                                return;
                            }
                            if (actionType === 'require') { map[fieldCode].required = true; }
                            if (actionType === 'unrequire') { map[fieldCode].required = false; }
                            if (actionType === 'disable') { map[fieldCode].disabled = true; }
                            if (actionType === 'enable') { map[fieldCode].disabled = false; }
                            if (actionType === 'set_value') { map[fieldCode].hasValueOverride = true; map[fieldCode].value = action.value || ''; }
                            if (actionType === 'clear_value') { map[fieldCode].hasValueOverride = true; map[fieldCode].value = ''; map[fieldCode].clearOnHide = true; }
                        });
                    });
                },
                applyMutatingRules: function() {
                    var self = this;
                    if (this.applyingRules) {
                        return;
                    }
                    this.applyingRules = true;
                    this.extractRuntimeRules().forEach(function(rule) {
                        if (!rule || rule.enabled === false || !self.isRuleMatched(rule)) {
                            return;
                        }
                        self.getRuleActions(rule).forEach(function(action) {
                            var fieldCode = action.fieldCode || action.field || '';
                            if (!fieldCode) {
                                return;
                            }
                            var actionType = self.normalizeActionType(action.type || action.action || '');
                            if (actionType === 'clear_value') {
                                var emptyValue = self.isFieldMultiple({code: fieldCode, type: self.getFieldType(fieldCode), multiple: true}) ? [] : '';
                                if (JSON.stringify(self.values[fieldCode]) !== JSON.stringify(emptyValue)) {
                                    self.values[fieldCode] = emptyValue;
                                }
                            }
                            if (actionType === 'set_value') {
                                var targetValue = action.value || '';
                                if (self.values[fieldCode] !== targetValue) {
                                    self.values[fieldCode] = targetValue;
                                }
                            }
                        });
                    });
                    this.applyingRules = false;
                },
                extractRuntimeRules: function() {
                    if (this.schema && this.schema.compiledRules && Array.isArray(this.schema.compiledRules.rules) && this.schema.compiledRules.rules.length) {
                        return this.schema.compiledRules.rules;
                    }
                    if (this.schema && Array.isArray(this.schema.compiledRules) && this.schema.compiledRules.length) {
                        return this.schema.compiledRules;
                    }
                    if (this.schema && Array.isArray(this.schema.rules) && this.schema.rules.length) {
                        return this.schema.rules;
                    }
                    if (this.schema && Array.isArray(this.schema.ruleGroups) && this.schema.ruleGroups.length) {
                        return this.compileRuleGroupsForRuntime(this.schema.ruleGroups);
                    }
                    return [];
                },
                compileRuleGroupsForRuntime: function(groups) {
                    var self = this;
                    var rules = [];
                    (groups || []).forEach(function(group, groupIndex) {
                        if (!group || group.enabled === false) { return; }
                        var mode = group.mode || 'different_values_different_fields';
                        var logic = String(group.logic || 'and').toLowerCase() === 'or' ? 'or' : 'and';
                        var sourceField = group.sourceFieldCode || group.sourceField || group.fieldCode || '';
                        var groupActions = self.getRuleActions(group);

                        if (mode === 'conditions_same_fields') {
                            var conditions = self.normalizeRuntimeConditions(group.conditions || []);
                            if (conditions.length && groupActions.length) {
                                rules.push({uid: group.uid || ('group_' + groupIndex), enabled: true, logic: logic, conditions: conditions, actions: groupActions});
                            }
                            return;
                        }

                        var rows = Array.isArray(group.rows) ? group.rows : [];
                        if (mode === 'different_values_same_fields') {
                            var rowConditions = rows.map(function(row) {
                                return self.normalizeRuntimeCondition({fieldCode: sourceField, operator: row.operator || '=', value: row.value, values: row.values});
                            }).filter(Boolean);
                            if (rowConditions.length && groupActions.length) {
                                rules.push({uid: group.uid || ('group_' + groupIndex), enabled: true, logic: logic === 'and' ? 'or' : logic, conditions: rowConditions, actions: groupActions});
                            }
                            return;
                        }

                        rows.forEach(function(row, rowIndex) {
                            var condition = self.normalizeRuntimeCondition({fieldCode: sourceField, operator: row.operator || '=', value: row.value, values: row.values});
                            var action = self.normalizeRuleAction({type: row.actionType || row.type || group.actionType || 'show', fieldCode: row.targetFieldCode || row.fieldCode || '', value: row.valueToSet || row.setValue || row.actionValue || ''});
                            if (condition && action.type && action.fieldCode) {
                                rules.push({uid: (group.uid || ('group_' + groupIndex)) + '_row_' + rowIndex, enabled: true, logic: 'and', conditions: [condition], actions: [action]});
                            }
                        });
                    });
                    return rules;
                },
                normalizeRuntimeConditions: function(conditions) {
                    var self = this;
                    return (conditions || []).map(function(condition) {
                        return self.normalizeRuntimeCondition(condition);
                    }).filter(Boolean);
                },
                normalizeRuntimeCondition: function(condition) {
                    condition = condition || {};
                    var fieldCode = condition.fieldCode || condition.field || condition.sourceField || '';
                    if (!fieldCode) { return null; }
                    var operator = this.normalizeOperator(condition.operator || '=');
                    var value = condition.value !== undefined ? condition.value : '';
                    var values = Array.isArray(condition.values) ? condition.values : (value === '' ? [] : [value]);
                    return {fieldCode: String(fieldCode), operator: operator, value: value, values: values, compareBy: condition.compareBy || 'value'};
                },
                getRuleActions: function(rule) {
                    var actions = [];
                    if (Array.isArray(rule.actions) && rule.actions.length) {
                        actions = rule.actions;
                    } else if (rule.action && typeof rule.action === 'object') {
                        actions = [rule.action];
                    } else if (rule.fieldCode || rule.field || rule.targetFieldCode || rule.targetField || rule.actionType || rule.type) {
                        actions = [rule];
                    }

                    return actions.map(this.normalizeRuleAction, this).filter(function(action) {
                        return !!(action.type && action.fieldCode);
                    });
                },
                normalizeRuleAction: function(action) {
                    action = action || {};
                    var type = this.normalizeActionType(action.type || action.action || action.actionType || '');
                    var fieldCode = action.fieldCode || action.field || action.targetFieldCode || action.targetField || '';

                    return {
                        type: type,
                        fieldCode: String(fieldCode || ''),
                        value: action.value !== undefined ? action.value : (action.valueToSet !== undefined ? action.valueToSet : (action.setValue !== undefined ? action.setValue : '')),
                        clearOnHide: !!action.clearOnHide
                    };
                },
                normalizeActionType: function(type) {
                    type = String(type || '').toLowerCase();
                    if (['show', 'hide', 'require', 'unrequire', 'set_value', 'clear_value', 'disable', 'enable'].indexOf(type) >= 0) {
                        return type;
                    }
                    if (type === 'required') { return 'require'; }
                    if (type === 'optional') { return 'unrequire'; }
                    if (type === 'set') { return 'set_value'; }
                    if (type === 'clear') { return 'clear_value'; }
                    if (type === 'readonly') { return 'disable'; }
                    if (type === 'editable') { return 'enable'; }
                    return '';
                },
                getFieldType: function(code) {
                    var field = this.fields.filter(function(item) { return item && item.code === code; })[0];
                    return field ? field.type : 'string';
                },
                isRuleMatched: function(rule) {
                    var self = this;
                    var conditions = Array.isArray(rule.conditions) && rule.conditions.length ? rule.conditions : (rule.when ? [rule.when] : []);
                    var logic = String(rule.logic || 'and').toLowerCase() === 'or' ? 'or' : 'and';
                    if (!conditions.length) {
                        return true;
                    }
                    if (logic === 'or') {
                        return conditions.some(function(condition) { return self.isConditionMatched(condition || {}); });
                    }
                    return conditions.every(function(condition) { return self.isConditionMatched(condition || {}); });
                },
                isConditionMatched: function(condition) {
                    condition = condition || {};
                    var sourceValues = this.getComparableValues(this.values[condition.fieldCode || condition.field || ''], condition);
                    var operator = this.normalizeOperator(condition.operator || '=');
                    var expected = normalizeComparable(condition.value || '');

                    if (operator === 'filled') {
                        return sourceValues.some(function(value) { return value !== ''; });
                    }
                    if (operator === 'not_filled') {
                        return !sourceValues.some(function(value) { return value !== ''; });
                    }
                    if (operator === '!=') {
                        return sourceValues.indexOf(expected) < 0;
                    }
                    if (operator === 'in' || operator === 'not_in') {
                        var valuesList = Array.isArray(condition.values) && condition.values.length ? condition.values : String(condition.value || '').split(',');
                        var expectedList = valuesList.map(normalizeComparable).filter(function(value) { return value !== ''; });
                        var result = sourceValues.some(function(value) { return expectedList.indexOf(value) >= 0; });
                        return operator === 'in' ? result : !result;
                    }
                    if (operator === 'contains' || operator === 'not_contains') {
                        var contains = expected !== '' && sourceValues.some(function(value) { return value.indexOf(expected) >= 0; });
                        return operator === 'contains' ? contains : !contains;
                    }
                    if (['>', '>=', '<', '<='].indexOf(operator) >= 0) {
                        var expectedNumber = this.normalizeNumberOrDate(expected);
                        if (expectedNumber === null) { return false; }
                        return sourceValues.some(function(value) {
                            var actualNumber = this.normalizeNumberOrDate(value);
                            if (actualNumber === null) { return false; }
                            if (operator === '>') { return actualNumber > expectedNumber; }
                            if (operator === '>=') { return actualNumber >= expectedNumber; }
                            if (operator === '<') { return actualNumber < expectedNumber; }
                            return actualNumber <= expectedNumber;
                        }, this);
                    }
                    return sourceValues.indexOf(expected) >= 0;
                },
                normalizeOperator: function(operator) {
                    operator = String(operator || '=').toLowerCase();
                    var aliases = {
                        equals: '=',
                        equal: '=',
                        eq: '=',
                        '==': '=',
                        not_equals: '!=',
                        not_equal: '!=',
                        neq: '!=',
                        '<>': '!=',
                        empty: 'not_filled',
                        not_empty: 'filled',
                        contain: 'contains',
                        not_contain: 'not_contains'
                    };
                    return aliases[operator] || operator;
                },
                normalizeNumberOrDate: function(value) {
                    value = String(value == null ? '' : value).trim();
                    if (!value) { return null; }
                    var numeric = value.replace(',', '.');
                    if (!isNaN(numeric) && numeric !== '') { return Number(numeric); }
                    var timestamp = Date.parse(value);
                    return isNaN(timestamp) ? null : timestamp;
                },
                getComparableValues: function(source, condition) {
                    var rawList = Array.isArray(source) ? source : [source];
                    var values = rawList.map(normalizeComparable).filter(function(value) { return value !== ''; });
                    if ((condition.compareBy || 'value') !== 'xmlId') {
                        return values.length ? values : [''];
                    }
                    var map = {};
                    (condition.sourceItems || []).forEach(function(item) {
                        var value = normalizeComparable(item && item.value);
                        if (!value) {
                            return;
                        }
                        map[value] = normalizeComparable(item.xmlId || item.xml_id || item.value);
                    });
                    var mapped = values.map(function(value) { return map[value] || value; });
                    return mapped.length ? mapped : [''];
                },
                collectValues: function() {
                    var values = {};
                    this.fields.forEach(function(field) {
                        if (!field || !field.code) {
                            return;
                        }
                        var state = this.fieldStateMap[field.code] || {};
                        if (state.visible === false) {
                            if (state.clearOnHide) {
                                values[field.code] = this.isFieldMultiple(field) ? [] : '';
                            }
                            return;
                        }
                        if (state.hasValueOverride) {
                            values[field.code] = state.value;
                            return;
                        }
                        values[field.code] = this.values[field.code];
                    }, this);
                    return values;
                },
                validateBeforeSubmit: function() {
                    var errors = [];
                    this.fields.forEach(function(field) {
                        if (!field || !field.code || !this.isFieldVisible(field)) {
                            return;
                        }
                        if (!this.isFieldRequired(field)) {
                            return;
                        }
                        var value = this.values[field.code];
                        var empty = value === null || value === undefined || value === '' || (Array.isArray(value) && !value.filter(function(item) { return item !== null && item !== undefined && item !== ''; }).length);
                        if (empty) {
                            errors.push(localize('REXP_FORM_RUNTIME_JS_015') + (field.label || field.title || field.code) + '».');
                        }
                    }, this);
                    return errors;
                },
                submit: function() {
                    var self = this;
                    this.applyMutatingRules();
                    var validationErrors = this.validateBeforeSubmit();
                    if (validationErrors.length) {
                        this.errors = validationErrors;
                        return;
                    }
                    var formData = new FormData();
                    formData.append('code', this.formCode || '');
                    formData.append('mode', this.mode || 'create');
                    formData.append('entry_id', String(this.entryId || 0));
                    formData.append('values_json', JSON.stringify(this.collectValues()));

                    Object.keys(this.files || {}).forEach(function(code) {
                        Array.prototype.slice.call(self.files[code] || []).forEach(function(file) {
                            formData.append('upload_' + code + '[]', file);
                        });
                    });

                    this.submitting = true;
                    this.errors = [];
                    BX.ajax.runAction(this.controller + '.submit', {
                        data: formData,
                        preparePost: false
                    }).then(function(response) {
                        var data = response.data || {};
                        var postSubmit = self.layout && self.layout.postSubmit ? self.layout.postSubmit : {};
                        var message = postSubmit.message || data.message || localize('REXP_FORM_RUNTIME_JS_016');
                        self.success = message;
                        self.submitting = false;
                        if (postSubmit.mode === 'redirect') {
                            var redirectUrl = sanitizePublicUrl(postSubmit.redirectUrl || '');
                            if (redirectUrl) {
                                window.location.href = redirectUrl;
                            }
                        } else if (postSubmit.mode === 'reload') {
                            window.location.reload();
                        }
                    }).catch(function(error) {
                        self.submitting = false;
                        self.errors = self.extractErrors(error, localize('REXP_FORM_RUNTIME_JS_017'));
                    });
                },
                extractErrors: function(error, fallback) {
                    if (error && Array.isArray(error.errors) && error.errors.length) {
                        return error.errors.map(function(item) { return item.message || fallback; });
                    }
                    if (error && error.message) {
                        return [error.message];
                    }
                    return [fallback];
                }
            },
            template: localize('REXP_FORM_RUNTIME_JS_018')
        });

        app.mount(node);
        node.__rexpFormRuntimeApp = app;
    };
})(window, window.BX);
