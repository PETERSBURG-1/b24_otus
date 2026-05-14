(function(BX) {
    'use strict';

    BX.namespace('BX.RexpForm');

    function localize(code, fallback) {
        var value = BX && BX.message ? BX.message(code) : '';
        return value || fallback || code;
    }


    var FIELD_TYPES = [
        ['string', localize('REXP_FORM_EDITOR_JS_001')],
        ['text', localize('REXP_FORM_EDITOR_JS_002')],
        ['email', 'E-mail'],
        ['phone', localize('REXP_FORM_EDITOR_JS_003')],
        ['url', localize('REXP_FORM_EDITOR_JS_004')],
        ['integer', localize('REXP_FORM_EDITOR_JS_005')],
        ['double', localize('REXP_FORM_EDITOR_JS_006')],
        ['money', localize('REXP_FORM_EDITOR_JS_007')],
        ['date', localize('REXP_FORM_EDITOR_JS_008')],
        ['datetime', localize('REXP_FORM_EDITOR_JS_009')],
        ['boolean', localize('REXP_FORM_EDITOR_JS_010')],
        ['enumeration', localize('REXP_FORM_EDITOR_JS_011')],
        ['user', localize('REXP_FORM_EDITOR_JS_012')],
        ['department', localize('REXP_FORM_EDITOR_JS_013')],
        ['file', localize('REXP_FORM_EDITOR_JS_014')]
    ];

    var OPERATORS = [
        ['=', localize('REXP_FORM_EDITOR_JS_015')],
        ['!=', localize('REXP_FORM_EDITOR_JS_016')],
        ['filled', localize('REXP_FORM_EDITOR_JS_017')],
        ['not_filled', localize('REXP_FORM_EDITOR_JS_018')],
        ['in', localize('REXP_FORM_EDITOR_JS_019')],
        ['not_in', localize('REXP_FORM_EDITOR_JS_020')],
        ['>', localize('REXP_FORM_EDITOR_JS_021')],
        ['>=', localize('REXP_FORM_EDITOR_JS_022')],
        ['<', localize('REXP_FORM_EDITOR_JS_023')],
        ['<=', localize('REXP_FORM_EDITOR_JS_024')],
        ['contains', localize('REXP_FORM_EDITOR_JS_025')],
        ['not_contains', localize('REXP_FORM_EDITOR_JS_026')]
    ];

    var ACTIONS = [
        ['show', localize('REXP_FORM_EDITOR_JS_027')],
        ['hide', localize('REXP_FORM_EDITOR_JS_028')],
        ['require', localize('REXP_FORM_EDITOR_JS_029')],
        ['unrequire', localize('REXP_FORM_EDITOR_JS_030')],
        ['clear_value', localize('REXP_FORM_EDITOR_JS_031')],
        ['set_value', localize('REXP_FORM_EDITOR_JS_032')],
        ['disable', localize('REXP_FORM_EDITOR_JS_033')],
        ['enable', localize('REXP_FORM_EDITOR_JS_034')]
    ];

    var RULE_GROUP_MODES = [
        ['different_values_different_fields', localize('REXP_FORM_EDITOR_JS_035')],
        ['different_values_same_fields', localize('REXP_FORM_EDITOR_JS_036')],
        ['conditions_same_fields', localize('REXP_FORM_EDITOR_JS_037')]
    ];

    var RULE_LOGICS = [
        ['and', localize('REXP_FORM_EDITOR_JS_038')],
        ['or', localize('REXP_FORM_EDITOR_JS_039')]
    ];

    var CRM_DEPENDENCY_ACTIONS = ACTIONS;

    function notify(message, category) {
        if (BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
            BX.UI.Notification.Center.notify({content: message, category: category || 'success'});
            return;
        }
        alert(message);
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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
        if (value === '100%') {
            return value;
        }
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

    function isSafePublicPath(value) {
        return sanitizePublicUrl(value) !== '';
    }


    var REXP_ICON_LABELS = {
        success: localize('REXP_FORM_EDITOR_JS_040'),
        done: localize('REXP_FORM_EDITOR_JS_041'),
        info: localize('REXP_FORM_EDITOR_JS_042'),
        alert: localize('REXP_FORM_EDITOR_JS_043'),
        fail: localize('REXP_FORM_EDITOR_JS_044'),
        task: localize('REXP_FORM_EDITOR_JS_045'),
        list: localize('REXP_FORM_EDITOR_JS_046'),
        page: localize('REXP_FORM_EDITOR_JS_047'),
        mail: localize('REXP_FORM_EDITOR_JS_048'),
        chat: localize('REXP_FORM_EDITOR_JS_049'),
        business: localize('REXP_FORM_EDITOR_JS_050'),
        'business-confirm': localize('REXP_FORM_EDITOR_JS_051'),
        'business-warning': localize('REXP_FORM_EDITOR_JS_052')
    };

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
            return '<span class="rexp-form-editor__bitrix-icon ui-btn ' + iconClass + ' is-original-color" aria-hidden="true"></span>';
        }
        return '<span class="rexp-form-editor__bitrix-icon ui-btn ' + iconClass + ' is-mask-color" aria-hidden="true"><span class="rexp-form-icon-mask rexp-form-icon-mask--' + escapeHtml(icon) + '"></span></span>';
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
                title: trimText(note.title || (index === 0 ? localize('REXP_FORM_EDITOR_JS_053') : localize('REXP_FORM_EDITOR_JS_054')), 80),
                text: trimText(note.text || '', 600),
                placement: safeEnum(note.placement || note.position || 'right', ['top', 'right', 'bottom', 'left'], 'right'),
                position: safeEnum(note.position || note.placement || 'right', ['left', 'right'], 'right'),
                variant: safeEnum(note.variant || 'info', ['info', 'warning', 'success', 'danger'], 'info'),
                icon: normalizeIconCode(note.icon, 'info'),
                width: clampIntString(note.width || '220', 160, 360, '220')
            };
        });
    }

    function getActiveSidebarNotes(layout) {
        layout = layout || {};
        var notes = normalizeSidebarNotes(layout.sidebarNotes, layout.sidebarNote || {});
        return notes.filter(function(note) { return note.enabled && note.text; });
    }

    function sanitizeLayout(layout, base) {
        base = base || defaultLayout();
        layout.colors.primary = safeHexColor(layout.colors.primary, base.colors.primary);
        layout.colors.background = safeHexColor(layout.colors.background, base.colors.background);
        layout.colors.card = safeHexColor(layout.colors.card, base.colors.card);
        layout.colors.text = safeHexColor(layout.colors.text, base.colors.text);

        layout.theme = safeEnum(layout.theme, ['light', 'dark', 'corporate', 'soft', 'emerald', 'mono', 'rexpress'], base.theme);
        layout.width = safeWidth(layout.width, base.width);
        layout.density = safeEnum(layout.density, ['compact', 'normal', 'comfortable'], base.density);

        layout.typography.font = safeEnum(layout.typography.font, ['system', 'arial', 'georgia', 'inter', 'onest'], base.typography.font);
        layout.typography.size = clampIntString(layout.typography.size, 13, 20, base.typography.size);
        layout.shape.radius = clampIntString(layout.shape.radius, 0, 40, base.shape.radius);
        layout.shape.fieldRadius = clampIntString(layout.shape.fieldRadius, 0, 24, base.shape.fieldRadius);

        layout.surface.fieldStyle = safeEnum(layout.surface.fieldStyle, ['outline', 'filled', 'underline'], base.surface.fieldStyle);
        layout.surface.shadow = safeEnum(layout.surface.shadow, ['none', 'soft', 'deep'], base.surface.shadow);
        layout.surface.border = safeEnum(layout.surface.border, ['none', 'subtle', 'accent'], base.surface.border);

        layout.container.align = safeEnum(layout.container.align, ['left', 'center', 'right'], base.container.align);
        layout.container.padding = safeEnum(layout.container.padding, ['compact', 'normal', 'wide'], base.container.padding);
        layout.container.backgroundMode = safeEnum(layout.container.backgroundMode, ['cover', 'contain', 'repeat'], base.container.backgroundMode);
        layout.container.backgroundOverlay = safeEnum(layout.container.backgroundOverlay, ['none', 'light', 'dark'], base.container.backgroundOverlay);
        layout.container.backgroundImageUrl = sanitizePublicUrl(layout.container.backgroundImageUrl);

        layout.media.icon = normalizeIconCode(layout.media.icon, base.media.icon);
        layout.media.imageUrl = sanitizePublicUrl(layout.media.imageUrl);
        layout.media.imageMode = safeEnum(layout.media.imageMode, ['cover', 'contain'], base.media.imageMode);
        layout.media.imageHeight = clampIntString(layout.media.imageHeight, 80, 360, base.media.imageHeight);

        layout.icons.enabled = toBool(layout.icons.enabled);
        layout.icons.formEnabled = toBool(layout.icons.formEnabled);
        layout.icons.noteEnabled = toBool(layout.icons.noteEnabled);
        layout.icons.successEnabled = toBool(layout.icons.successEnabled);
        layout.icons.shape = safeEnum(layout.icons.shape, ['none', 'circle', 'rounded', 'square'], base.icons.shape);
        layout.icons.background = safeHexColor(layout.icons.background, base.icons.background);
        layout.icons.color = safeHexColor(layout.icons.color, base.icons.color || '#525c69');
        layout.icons.size = safeEnum(layout.icons.size, ['sm', 'md', 'lg', 'xl'], base.icons.size);

        layout.submitButton.style = safeEnum(layout.submitButton.style, ['crm', 'primary', 'outline'], base.submitButton.style);
        layout.submitButton.align = safeEnum(layout.submitButton.align, ['left', 'center', 'right', 'stretch'], base.submitButton.align);
        layout.submitButton.textColor = safeEnum(layout.submitButton.textColor, ['dark', 'light'], base.submitButton.textColor);
        layout.submitButton.size = safeEnum(layout.submitButton.size, ['sm', 'default', 'lg'], base.submitButton.size);

        layout.sidebarNote.enabled = toBool(layout.sidebarNote.enabled);
        layout.sidebarNote.title = trimText(layout.sidebarNote.title, 80) || base.sidebarNote.title;
        layout.sidebarNote.text = trimText(layout.sidebarNote.text, 600);
        layout.sidebarNote.position = safeEnum(layout.sidebarNote.position, ['left', 'right'], base.sidebarNote.position);
        layout.sidebarNote.variant = safeEnum(layout.sidebarNote.variant, ['info', 'warning', 'success', 'danger'], base.sidebarNote.variant);
        layout.sidebarNote.icon = normalizeIconCode(layout.sidebarNote.icon, base.sidebarNote.icon);
        layout.sidebarNote.width = clampIntString(layout.sidebarNote.width, 160, 360, base.sidebarNote.width);
        layout.sidebarNotes = normalizeSidebarNotes(layout.sidebarNotes, layout.sidebarNote);
        if (layout.sidebarNotes.length) { layout.sidebarNote = Object.assign({}, layout.sidebarNote, layout.sidebarNotes[0]); }

        layout.responsive.enabled = toBool(layout.responsive.enabled);
        layout.responsive.breakpoint = safeEnum(layout.responsive.breakpoint, ['480', '640', '760'], base.responsive.breakpoint);
        layout.responsive.fullWidth = toBool(layout.responsive.fullWidth);
        layout.responsive.compact = toBool(layout.responsive.compact);
        layout.responsive.sideNoteMobile = safeEnum(layout.responsive.sideNoteMobile, ['top', 'bottom', 'hide'], base.responsive.sideNoteMobile);
        layout.responsive.imageMobile = safeEnum(layout.responsive.imageMobile, ['show', 'hide'], base.responsive.imageMobile);

        layout.postSubmit.mode = safeEnum(layout.postSubmit.mode, ['message', 'hideForm', 'hide_form', 'redirect', 'reload'], base.postSubmit.mode);
        layout.postSubmit.title = trimText(layout.postSubmit.title, 90) || base.postSubmit.title;
        layout.postSubmit.icon = normalizeIconCode(layout.postSubmit.icon, base.postSubmit.icon);
        layout.postSubmit.imageUrl = sanitizePublicUrl(layout.postSubmit.imageUrl);
        layout.postSubmit.message = trimText(layout.postSubmit.message, 800) || base.postSubmit.message;
        layout.postSubmit.buttonText = trimText(layout.postSubmit.buttonText, 80);
        layout.postSubmit.redirectUrl = sanitizePublicUrl(layout.postSubmit.redirectUrl);
        layout.postSubmit.showAgainButton = toBool(layout.postSubmit.showAgainButton);

        layout.advanced.cssClass = sanitizeCssClassList(layout.advanced.cssClass);
        layout.advanced.animation = safeEnum(layout.advanced.animation, ['none', 'fade', 'slide'], base.advanced.animation);
        return layout;
    }

    function createUid(prefix) {
        return prefix + '_' + Math.random().toString(36).slice(2, 9) + '_' + Date.now().toString(36);
    }

    function defaultLayout() {
        return {
            type: 'design',
            columns: 12,
            preset: 'classic',
            theme: 'light',
            width: '760',
            density: 'normal',
            colors: {primary: '#2fc6f6', background: '#f5f7fb', card: '#ffffff', text: '#172b4d'},
            typography: {font: 'system', size: '15'},
            shape: {radius: '14', fieldRadius: '10'},
            surface: {fieldStyle: 'outline', shadow: 'soft', border: 'subtle'},
            container: {align: 'center', padding: 'normal', backgroundImageUrl: '', backgroundMode: 'cover', backgroundOverlay: 'light'},
            media: {icon: 'info', imageUrl: '', imageMode: 'cover', imageHeight: '140'},
            icons: {enabled: true, formEnabled: true, noteEnabled: true, successEnabled: true, shape: 'rounded', background: '#e8f7ff', color: '#525c69', size: 'md'},
            submitButton: {style: 'crm', align: 'left', textColor: 'dark', size: 'default'},
            sidebarNote: {enabled: false, title: localize('REXP_FORM_EDITOR_JS_055'), text: '', placement: 'right', position: 'right', variant: 'info', icon: 'alert', width: '220'}, sidebarNotes: [],
            responsive: {enabled: true, breakpoint: '760', fullWidth: true, compact: true, sideNoteMobile: 'top', imageMobile: 'show'},
            postSubmit: {mode: 'message', title: localize('REXP_FORM_EDITOR_JS_056'), icon: 'success', imageUrl: '', message: localize('REXP_FORM_EDITOR_JS_057'), buttonText: '', redirectUrl: '', showAgainButton: false},
            advanced: {cssClass: '', animation: 'none'}
        };
    }

    function ensureLayout(layout) {
        var base = defaultLayout();
        layout = layout && typeof layout === 'object' ? layout : {};
        layout.type = layout.type || base.type;
        layout.columns = layout.columns || base.columns;
        layout.preset = layout.preset || base.preset;
        layout.theme = layout.theme || base.theme;
        layout.width = String(layout.width || base.width);
        layout.density = layout.density || base.density;
        layout.colors = Object.assign({}, base.colors, layout.colors && typeof layout.colors === 'object' ? layout.colors : {});
        layout.typography = Object.assign({}, base.typography, layout.typography && typeof layout.typography === 'object' ? layout.typography : {});
        layout.shape = Object.assign({}, base.shape, layout.shape && typeof layout.shape === 'object' ? layout.shape : {});
        layout.surface = Object.assign({}, base.surface, layout.surface && typeof layout.surface === 'object' ? layout.surface : {});
        layout.container = Object.assign({}, base.container, layout.container && typeof layout.container === 'object' ? layout.container : {});
        layout.media = Object.assign({}, base.media, layout.media && typeof layout.media === 'object' ? layout.media : {});
        layout.icons = Object.assign({}, base.icons, layout.icons && typeof layout.icons === 'object' ? layout.icons : {});
        layout.submitButton = Object.assign({}, base.submitButton, layout.submitButton && typeof layout.submitButton === 'object' ? layout.submitButton : {});
        layout.sidebarNote = Object.assign({}, base.sidebarNote, layout.sidebarNote && typeof layout.sidebarNote === 'object' ? layout.sidebarNote : {});
        layout.sidebarNotes = normalizeSidebarNotes(layout.sidebarNotes, layout.sidebarNote);
        layout.responsive = Object.assign({}, base.responsive, layout.responsive && typeof layout.responsive === 'object' ? layout.responsive : {});
        layout.postSubmit = Object.assign({}, base.postSubmit, layout.postSubmit && typeof layout.postSubmit === 'object' ? layout.postSubmit : {});
        layout.advanced = Object.assign({}, base.advanced, layout.advanced && typeof layout.advanced === 'object' ? layout.advanced : {});
        return sanitizeLayout(layout, base);
    }

    function setNestedValue(root, path, value) {
        var parts = String(path || '').split('.').filter(Boolean);
        var node = root;
        for (var i = 0; i < parts.length - 1; i++) {
            if (!node[parts[i]] || typeof node[parts[i]] !== 'object') { node[parts[i]] = {}; }
            node = node[parts[i]];
        }
        if (parts.length) { node[parts[parts.length - 1]] = value; }
    }

    function defaultSchema() {
        return {
            version: 2,
            layout: defaultLayout(),
            settings: {
                submitText: localize('REXP_FORM_EDITOR_JS_058'),
                successText: localize('REXP_FORM_EDITOR_JS_059'),
                errorText: localize('REXP_FORM_EDITOR_JS_060')
            },
            sections: [],
            fields: [],
            rules: [],
            ruleGroups: [],
            states: {
                success: {mode: 'message', text: localize('REXP_FORM_EDITOR_JS_061')},
                error: {text: localize('REXP_FORM_EDITOR_JS_062')}
            }
        };
    }

    function ensureSchema(schema) {
        schema = schema && typeof schema === 'object' ? schema : defaultSchema();
        schema.settings = schema.settings && typeof schema.settings === 'object' ? schema.settings : {};
        schema.layout = ensureLayout(schema.layout);
        schema.sections = Array.isArray(schema.sections) ? schema.sections : [];
        schema.fields = Array.isArray(schema.fields) ? schema.fields : [];
        schema.rules = Array.isArray(schema.rules) ? schema.rules : [];
        schema.ruleGroups = Array.isArray(schema.ruleGroups) ? schema.ruleGroups : buildRuleGroupsFromRules(schema.rules);
        schema.states = schema.states && typeof schema.states === 'object' ? schema.states : {};
        schema.states.success = schema.states.success && typeof schema.states.success === 'object' ? schema.states.success : {mode: 'message', text: schema.settings.successText || localize('REXP_FORM_EDITOR_JS_063')};
        schema.states.error = schema.states.error && typeof schema.states.error === 'object' ? schema.states.error : {text: localize('REXP_FORM_EDITOR_JS_064')};
        schema.fields.forEach(function(field) {
            field.uid = field.uid || createUid('field');
            field.sectionUid = (field.sectionUid === undefined || field.sectionUid === null) ? '' : field.sectionUid;
            field.settings = field.settings || {};
            field.items = Array.isArray(field.items) ? field.items : [];
        });
        return schema;
    }

    function normalizeForm(raw) {
        var schema = ensureSchema(raw && raw.schema && typeof raw.schema === 'object' ? raw.schema : defaultSchema());
        return {
            id: Number(raw && raw.id ? raw.id : 0),
            name: raw && raw.name ? String(raw.name) : localize('REXP_FORM_EDITOR_JS_065'),
            code: raw && raw.code ? String(raw.code) : 'new_form',
            active: raw && raw.active !== undefined ? toBool(raw.active) : true,
            archived: raw && raw.archived !== undefined ? toBool(raw.archived) : false,
            schema: schema
        };
    }

    function renderOptions(items, selected) {
        return items.map(function(item) {
            return '<option value="' + escapeHtml(item[0]) + '"' + (String(selected) === String(item[0]) ? ' selected' : '') + '>' + escapeHtml(item[1]) + '</option>';
        }).join('');
    }

    function fieldOptions(fields, selected) {
        var result = localize('REXP_FORM_EDITOR_JS_066');
        fields.forEach(function(field) {
            var code = field.code || '';
            if (!code) { return; }
            result += '<option value="' + escapeHtml(code) + '"' + (String(selected) === String(code) ? ' selected' : '') + '>' + escapeHtml((field.label || code) + ' [' + code + ']') + '</option>';
        });
        return result;
    }

    function multiFieldOptions(fields, selectedList) {
        selectedList = Array.isArray(selectedList) ? selectedList.map(String) : [];
        var result = '';
        fields.forEach(function(field) {
            var code = String(field.code || '');
            if (!code) { return; }
            result += '<option value="' + escapeHtml(code) + '"' + (selectedList.indexOf(code) >= 0 ? ' selected' : '') + '>' + escapeHtml((field.label || code) + ' [' + code + ']') + '</option>';
        });
        return result;
    }

    BX.RexpForm.createEditor = function(options) {
        var node = document.getElementById(options.containerId);
        if (!node) { return; }

        var state = {
            tab: 'general',
            loading: false,
            saving: false,
            form: normalizeForm({id: options.formId || 0}),
            permissions: {},
            diagnostics: null,
            showRuleModeCards: false,
            metadata: {fieldTypes: {}, ruleOperators: {}, ruleActions: {}, userFieldTypes: {}, userFields: []},
            userFieldEntityId: options.userFieldEntityId || 'CRM_LEAD',
            userFields: [],
            userFieldsLoading: false,
            previewWidth: '760',
            formExchangeText: ''
        };

        function message(code, fallback) {
            return options.messages && options.messages[code] ? options.messages[code] : fallback;
        }

        function isEditorCountersEnabled() {
            return !options.moduleOptions || options.moduleOptions.editor_counters_enabled !== false;
        }

        function isDraftsEnabled() {
            return !options.moduleOptions || options.moduleOptions.drafts_enabled !== false;
        }

        function isVersionsEnabled() {
            return !options.moduleOptions || options.moduleOptions.versions_enabled !== false;
        }

        function buildEntityUrl(baseUrl) {
            if (!state.form.id) {
                return '';
            }

            return (baseUrl || '') + '?FORM_ID=' + encodeURIComponent(state.form.id);
        }

        function openEntityPage(baseUrl, emptyMessage) {
            if (!state.form.id) {
                notify(emptyMessage || localize('REXP_FORM_EDITOR_JS_067'), 'error');
                return;
            }

            window.location.href = buildEntityUrl(baseUrl);
        }

        function runAction(action, data) {
            return BX.ajax.runAction(options.controller + '.' + action, {data: data || {}}).then(function(response) {
                return response.data || {};
            });
        }

        function refresh() {
            var form = state.form;
            var schema = ensureSchema(form.schema || defaultSchema());
            form.schema = schema;
            var settings = schema.settings || {};
            var fields = Array.isArray(schema.fields) ? schema.fields : [];
            var sections = Array.isArray(schema.sections) ? schema.sections : [];
            var tabs = ['general', 'constructor', 'sections', 'fields', 'rules', 'diagnostics', 'exchange'];
            var labels = {
                general: localize('REXP_FORM_EDITOR_JS_068'),
                constructor: localize('REXP_FORM_EDITOR_JS_069'),
                sections: localize('REXP_FORM_EDITOR_JS_070'),
                fields: localize('REXP_FORM_EDITOR_JS_071'),
                rules: localize('REXP_FORM_EDITOR_JS_072'),
                diagnostics: message('REXP_FORM_EDITOR_TAB_DIAGNOSTICS', localize('REXP_FORM_EDITOR_JS_073')),
                exchange: message('REXP_FORM_EDITOR_TAB_EXCHANGE', localize('REXP_FORM_EDITOR_JS_074'))
            };

            node.innerHTML = '' +
                '<div class="rexp-form-editor">' +
                    '<div class="rexp-form-editor__bar">' +
                        '<div><div class="rexp-form-editor__title">' + escapeHtml(form.id ? form.name : message('REXP_FORM_EDITOR_NEW_FORM', localize('REXP_FORM_EDITOR_JS_075'))) + '</div>' +
                        '</div>' +
                        '<div class="rexp-form-editor__actions">' +
                            '<button class="ui-btn ui-btn-success rexp-form-editor__save-button" data-action="save"' + (state.saving ? ' disabled' : '') + '>' + escapeHtml(state.saving ? message('REXP_FORM_EDITOR_SAVE_PROGRESS', localize('REXP_FORM_EDITOR_JS_076')) : message('REXP_FORM_EDITOR_SAVE', localize('REXP_FORM_EDITOR_JS_077'))) + '</button>' +
                            (isDraftsEnabled() ? '<button class="ui-btn ui-btn-light-border ui-btn-icon-page" data-action="save-draft"' + (!state.form.id ? localize('REXP_FORM_EDITOR_JS_078') : '') + localize('REXP_FORM_EDITOR_JS_079') : '') +
                            '<button class="ui-btn ui-btn-light-border ui-btn-icon-task" data-action="open-bizproc">' + escapeHtml(message('REXP_FORM_EDITOR_TAB_BIZPROC', localize('REXP_FORM_EDITOR_JS_080'))) + '</button>' +
                        '</div>' +
                    '</div>' +
                    '<div class="rexp-form-editor__tabs">' + tabs.map(function(tab) {
                        return '<button class="rexp-form-editor__tab' + (state.tab === tab ? ' --active' : '') + '" data-tab="' + tab + '">' + escapeHtml(labels[tab]) + '</button>';
                    }).join('') + '</div>' +
                    renderPanel(form, schema, settings, fields, sections) +
                '</div>';
            updatePreviewLayoutVars();
        }

        function renderPanel(form, schema, settings, fields, sections) {
            if (state.loading) {
                return localize('REXP_FORM_EDITOR_JS_081');
            }
            if (state.tab === 'general') {
                return '<div class="rexp-form-editor__panel"><div class="rexp-form-editor__grid">' +
                    input(localize('REXP_FORM_EDITOR_JS_082'), 'name', form.name) +
                    input(localize('REXP_FORM_EDITOR_JS_083'), 'code', form.code) +
                    checkbox(localize('REXP_FORM_EDITOR_JS_084'), 'active', form.active) +
                    renderRuntimeComponentSnippet(form) +
                '</div></div>';
            }
            if (state.tab === 'constructor') {
                return renderVisualBuilderPanel(fields, sections, settings, schema.layout);
            }
            if (state.tab === 'sections') {
                return '<div class="rexp-form-editor__panel rexp-form-editor__sections-panel">' +
                    renderSectionsHeader(sections) +
                    (sections.length ? sections.map(renderSection).join('') : localize('REXP_FORM_EDITOR_JS_085')) +
                '</div>';
            }
            if (state.tab === 'fields') {
                return '<div class="rexp-form-editor__panel rexp-form-editor__fields-panel">' +
                    renderFieldsHeader(fields) +
                    (fields.length ? fields.map(renderField).join('') : localize('REXP_FORM_EDITOR_JS_086')) +
                '</div>';
            }
            if (state.tab === 'rules') {
                var groups = Array.isArray(schema.ruleGroups) ? schema.ruleGroups : [];
                var body = (groups.length ? groups.map(function(group, index) { return renderRuleGroup(group, index, fields); }).join('') : '') + renderRulesAddArea(groups.length > 0, fields.length > 0);
                return '<div class="rexp-form-editor__panel rexp-form-editor__rules-builder">' +
                    (fields.length ? renderRulesSummary(groups, fields) : '') +
                    (!fields.length ? localize('REXP_FORM_EDITOR_JS_088') : '') +
                    '<div class="landing-ui-panel-form-settings-content-wrapper">' +
                        '<div class="landing-ui-form landing-ui-form-form-settings"><div class="landing-ui-form-header"></div><div class="landing-ui-form-body">' + body + '</div><div class="landing-ui-form-footer"></div></div>' +
                    '</div>' +
                '</div>';
            }
            if (state.tab === 'diagnostics') {
                return renderDiagnosticsPanel();
            }
            if (state.tab === 'exchange') {
                return renderExchangePanel(form, schema);
            }
            return '';
        }


        function renderExchangePanel(form, schema) {
            schema = ensureSchema(schema || defaultSchema());
            var formText = state.formExchangeText || '';
            return '<div class="rexp-form-editor__panel rexp-form-editor__exchange-panel">' +
                '<div class="rexp-form-editor__fields-head rexp-form-editor__exchange-head">' +
                    localize('REXP_FORM_EDITOR_JS_089') +
                '</div>' +
                '<div class="rexp-form-editor__exchange-grid">' +
                    '<div class="rexp-form-editor__design-card rexp-form-editor__design-card--exchange rexp-form-editor__exchange-card">' +
                        localize('REXP_FORM_EDITOR_JS_090') +
                        '<div class="rexp-form-editor__exchange-actions">' +
                            localize('REXP_FORM_EDITOR_JS_091') +
                            localize('REXP_FORM_EDITOR_JS_092') +
                            localize('REXP_FORM_EDITOR_JS_093') +
                            localize('REXP_FORM_EDITOR_JS_094') +
                            localize('REXP_FORM_EDITOR_JS_095') +
                        '</div>' +
                        localize('REXP_FORM_EDITOR_JS_096') + escapeHtml(formText) + '</textarea>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }


        function renderVisualBuilderPanel(fields, sections, settings, layout) {
            layout = ensureLayout(layout);
            return '<div class="rexp-form-editor__panel rexp-form-editor__visual-builder">' +
                '<div class="rexp-form-editor__fields-head rexp-form-editor__visual-head">' +
                    localize('REXP_FORM_EDITOR_JS_097') +
                    '</div>' +
                    '<div class="rexp-form-editor__visual-head-actions">' +
                        localize('REXP_FORM_EDITOR_JS_098') +
                    '</div>' +
                '</div>' +
                '<div class="rexp-form-editor__visual-layout">' +
                    '<div class="rexp-form-editor__visual-controls">' +
                        renderAppearanceCard(layout) + renderPageCard(layout) + renderFormTextsCard(settings) + renderSubmitButtonCard(layout) + renderBrandCard(layout) + renderIconStyleCard(layout) + renderSideNoteCard(layout) + renderPostSubmitCard(layout, settings) + renderAdvancedDesignCard(layout) +
                    '</div>' +
                    '<div class="rexp-form-editor__visual-preview-wrap">' +
                        '<div class="rexp-form-editor__visual-preview-top">' +
                            localize('REXP_FORM_EDITOR_JS_099') +
                            renderPreviewSizeSwitcher() +
                        '</div>' +
                        '<div class="rexp-form-editor__visual-preview-viewport ' + previewViewportClass() + '" style="max-width:' + escapeHtml(previewWidthValue()) + '">' + renderThemedPreview(fields, sections, settings, layout, false) + '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        function renderPreviewSizeSwitcher() {
            var sizes = ['360','480','640','760','960','1120'];
            return '<div class="rexp-form-editor__preview-size-switcher">' + sizes.map(function(size) {
                return '<button type="button" class="rexp-form-editor__preview-size' + (String(state.previewWidth) === size ? ' is-active' : '') + '" data-action="set-preview-width" data-preview-width="' + escapeHtml(size) + '">' + escapeHtml(size) + ' px</button>';
            }).join('') + '</div>';
        }

        function previewWidthValue() {
            var value = String(state.previewWidth || '760');
            return /^\d+$/.test(value) ? value + 'px' : '760px';
        }

        function previewViewportClass() {
            var value = String(state.previewWidth || '760');
            if (!/^\d+$/.test(value)) {
                value = '760';
            }
            return 'is-size-' + value;
        }

        function corporateIconList(extraIcons) {
            var fallback = ['success', 'fail', 'done', 'info', 'alert', 'task', 'list', 'page', 'mail', 'chat', 'business', 'business-confirm', 'business-warning'];
            var icons = [];
            var used = {};
            (extraIcons && extraIcons.length ? extraIcons : fallback).forEach(function(icon) {
                icon = normalizeIconCode(icon, '');
                if (icon && REXP_ICON_CLASSES[icon] && !used[icon]) {
                    used[icon] = true;
                    icons.push(icon);
                }
            });
            fallback.forEach(function(icon) {
                if (!used[icon]) {
                    used[icon] = true;
                    icons.push(icon);
                }
            });
            return icons;
        }

        function renderIconPicker(title, targetPath, selectedIcon, icons) {
            selectedIcon = selectedIcon || '';
            var list = corporateIconList(icons || []);
            return '<div class="rexp-form-editor__icon-picker --wide"><div class="rexp-form-editor__icon-picker-title">' + escapeHtml(title) + '</div>' +
                '<div class="rexp-form-editor__icon-preset-row">' + list.map(function(icon) {
                    var iconId = normalizeIconCode(icon, 'info');
                    var isActive = normalizeIconCode(selectedIcon, 'info') === iconId;
                    var label = escapeHtml(REXP_ICON_LABELS[iconId] || iconId);
                    var buttonClass = 'rexp-form-editor__icon-preset ' + (isActive ? 'is-active' : '');
                    var iconHtml = '';
                    if (isOriginalColorIcon(iconId)) {
                        buttonClass += ' ui-btn ' + escapeHtml(REXP_ICON_CLASSES[iconId] || REXP_ICON_CLASSES.info) + ' is-original-color';
                    } else {
                        buttonClass += ' rexp-form-editor__icon-preset--mask';
                        iconHtml = '<span class="rexp-form-icon-mask rexp-form-icon-mask--' + escapeHtml(iconId) + '" aria-hidden="true"></span>';
                    }
                    return '<button type="button" class="' + buttonClass + '" data-action="set-design-icon" data-icon-target="' + escapeHtml(targetPath) + '" data-icon="' + escapeHtml(iconId) + '" title="' + label + '" aria-label="' + label + '">' + iconHtml + '</button>';
                }).join('') + '</div></div>';
        }

        function renderIconShell(icon, className) {
            icon = normalizeIconCode(icon, 'info');
            if (!icon) { return ''; }
            return '<span class="rexp-form-editor__icon-shell ' + escapeHtml(className || '') + '" data-icon="' + escapeHtml(icon) + '">' + renderBitrixIcon(icon) + '</span>';
        }

        function renderIconStyleCard(layout) {
            var i = layout.icons || {};
            return localize('REXP_FORM_EDITOR_JS_100') +
                '<div class="rexp-form-editor__grid">' +
                    checkbox(localize('REXP_FORM_EDITOR_JS_101'), 'layout.icons.enabled', i.enabled !== false) +
                    checkbox(localize('REXP_FORM_EDITOR_JS_102'), 'layout.icons.formEnabled', i.formEnabled !== false) +
                    checkbox(localize('REXP_FORM_EDITOR_JS_103'), 'layout.icons.noteEnabled', i.noteEnabled !== false) +
                    checkbox(localize('REXP_FORM_EDITOR_JS_104'), 'layout.icons.successEnabled', i.successEnabled !== false) +
                    localize('REXP_FORM_EDITOR_JS_105') + renderOptions([['none',localize('REXP_FORM_EDITOR_JS_106')],['circle',localize('REXP_FORM_EDITOR_JS_107')],['rounded',localize('REXP_FORM_EDITOR_JS_108')],['square',localize('REXP_FORM_EDITOR_JS_109')]], i.shape || 'rounded') + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_110') + escapeHtml(i.background || '#e8f7ff') + '"></label>' +
                    localize('REXP_FORM_EDITOR_JS_111') + escapeHtml(i.color || '#525c69') + '"></label>' +
                    localize('REXP_FORM_EDITOR_JS_112') + renderOptions([['sm',localize('REXP_FORM_EDITOR_JS_113')],['md',localize('REXP_FORM_EDITOR_JS_114')],['lg',localize('REXP_FORM_EDITOR_JS_115')],['xl',localize('REXP_FORM_EDITOR_JS_116')]], i.size || 'md') + '</select></label>' +
                '</div></div>';
        }

        function renderDesignExchangeCard(layout) {
            return '';
        }

        function renderImageUploadControl(title, targetPath, value, placeholder) {
            var inputId = createUid('design_upload');
            return '<div class="rexp-form-editor__asset-control --wide">' +
                '<label><span>' + escapeHtml(title) + '</span><input data-field="layout.' + escapeHtml(targetPath) + '" value="' + escapeHtml(value || '') + '" placeholder="' + escapeHtml(placeholder || '/upload/image.png') + '"></label>' +
                '<div class="rexp-form-editor__asset-actions">' +
                    '<label class="ui-btn ui-btn-light-border ui-btn-xs" for="' + escapeHtml(inputId) + localize('REXP_FORM_EDITOR_JS_117') +
                    (value ? '<button type="button" class="ui-btn ui-btn-light-border ui-btn-xs" data-action="clear-design-image" data-image-target="' + escapeHtml(targetPath) + localize('REXP_FORM_EDITOR_JS_118') : '') +
                    '<input id="' + escapeHtml(inputId) + '" class="rexp-form-editor__asset-file" type="file" accept="image/*" data-upload-target="' + escapeHtml(targetPath) + '">' +
                '</div>' +
                (value ? '<div class="rexp-form-editor__asset-preview" style="background-image:url(' + escapeHtml(value) + ')"></div>' : '') +
            '</div>';
        }

        function renderAppearanceCard(layout) {
            return localize('REXP_FORM_EDITOR_JS_119') +
                '<div class="rexp-form-editor__theme-preset-row">' + renderThemePreset('light',localize('REXP_FORM_EDITOR_JS_120'),layout.theme) + renderThemePreset('dark',localize('REXP_FORM_EDITOR_JS_121'),layout.theme) + renderThemePreset('corporate',localize('REXP_FORM_EDITOR_JS_122'),layout.theme) + renderThemePreset('soft',localize('REXP_FORM_EDITOR_JS_123'),layout.theme) + renderThemePreset('emerald',localize('REXP_FORM_EDITOR_JS_124'),layout.theme) + renderThemePreset('mono',localize('REXP_FORM_EDITOR_JS_125'),layout.theme) + renderThemePreset('rexpress',localize('REXP_FORM_EDITOR_JS_126'),layout.theme) + '</div>' +
                '<div class="rexp-form-editor__grid">' +
                    localize('REXP_FORM_EDITOR_JS_127') + escapeHtml(layout.colors.primary) + '"></label>' +
                    localize('REXP_FORM_EDITOR_JS_128') + escapeHtml(layout.colors.background) + '"></label>' +
                    localize('REXP_FORM_EDITOR_JS_129') + escapeHtml(layout.colors.card) + '"></label>' +
                    localize('REXP_FORM_EDITOR_JS_130') + escapeHtml(layout.colors.text) + '"></label>' +
                    localize('REXP_FORM_EDITOR_JS_131') + renderOptions([['system',localize('REXP_FORM_EDITOR_JS_132')],['onest',localize('REXP_FORM_EDITOR_JS_133')],['arial','Arial'],['georgia','Georgia'],['inter','Inter / sans-serif']], layout.typography.font) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_134') + renderOptions([['14',localize('REXP_FORM_EDITOR_JS_135')],['15',localize('REXP_FORM_EDITOR_JS_136')],['16',localize('REXP_FORM_EDITOR_JS_137')]], layout.typography.size) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_138') + renderOptions([['6',localize('REXP_FORM_EDITOR_JS_139')],['14',localize('REXP_FORM_EDITOR_JS_140')],['22',localize('REXP_FORM_EDITOR_JS_141')],['30',localize('REXP_FORM_EDITOR_JS_142')]], layout.shape.radius) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_143') + renderOptions([['4',localize('REXP_FORM_EDITOR_JS_144')],['8',localize('REXP_FORM_EDITOR_JS_145')],['12',localize('REXP_FORM_EDITOR_JS_146')],['18',localize('REXP_FORM_EDITOR_JS_147')]], layout.shape.fieldRadius) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_148') + renderOptions([['outline',localize('REXP_FORM_EDITOR_JS_149')],['filled',localize('REXP_FORM_EDITOR_JS_150')],['underline',localize('REXP_FORM_EDITOR_JS_151')]], layout.surface.fieldStyle) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_152') + renderOptions([['none',localize('REXP_FORM_EDITOR_JS_153')],['subtle',localize('REXP_FORM_EDITOR_JS_154')],['accent',localize('REXP_FORM_EDITOR_JS_155')]], layout.surface.border) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_156') + renderOptions([['none',localize('REXP_FORM_EDITOR_JS_157')],['soft',localize('REXP_FORM_EDITOR_JS_158')],['deep',localize('REXP_FORM_EDITOR_JS_159')]], layout.surface.shadow) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_160') + renderOptions([['compact',localize('REXP_FORM_EDITOR_JS_161')],['normal',localize('REXP_FORM_EDITOR_JS_162')],['comfortable',localize('REXP_FORM_EDITOR_JS_163')]], layout.density) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_164') + renderOptions([['640','640 px'],['760','760 px'],['920','920 px'],['100%','100%']], layout.width) + '</select></label>' +
                '</div></div>';
        }
        function renderThemePreset(value, title, selected) { return '<button type="button" class="rexp-form-editor__theme-preset ' + (String(value) === String(selected) ? 'is-active' : '') + '" data-action="set-theme" data-theme="' + escapeHtml(value) + '">' + escapeHtml(title) + '</button>'; }
        function renderPageCard(layout) { var c = layout.container || {}; return localize('REXP_FORM_EDITOR_JS_165') +
            localize('REXP_FORM_EDITOR_JS_166') + renderOptions([['left',localize('REXP_FORM_EDITOR_JS_167')],['center',localize('REXP_FORM_EDITOR_JS_168')],['right',localize('REXP_FORM_EDITOR_JS_169')]], c.align || 'center') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_170') + renderOptions([['compact',localize('REXP_FORM_EDITOR_JS_171')],['normal',localize('REXP_FORM_EDITOR_JS_172')],['wide',localize('REXP_FORM_EDITOR_JS_173')]], c.padding || 'normal') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_174') + renderOptions([['cover',localize('REXP_FORM_EDITOR_JS_175')],['contain',localize('REXP_FORM_EDITOR_JS_176')],['repeat',localize('REXP_FORM_EDITOR_JS_177')]], c.backgroundMode || 'cover') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_178') + renderOptions([['none',localize('REXP_FORM_EDITOR_JS_179')],['light',localize('REXP_FORM_EDITOR_JS_180')],['dark',localize('REXP_FORM_EDITOR_JS_181')]], c.backgroundOverlay || 'light') + '</select></label>' +
            renderImageUploadControl(localize('REXP_FORM_EDITOR_JS_182'), 'container.backgroundImageUrl', c.backgroundImageUrl || '', '/upload/background.jpg') +
        '</div></div>'; }
        function renderFormTextsCard(settings) {
            settings = settings || {};
            return localize('REXP_FORM_EDITOR_JS_183') +
                '<div class="rexp-form-editor__grid">' +
                    localize('REXP_FORM_EDITOR_JS_184') + escapeHtml(settings.submitText || localize('REXP_FORM_EDITOR_JS_185')) + '"></label>' +
                    localize('REXP_FORM_EDITOR_JS_186') + escapeHtml(settings.errorText || localize('REXP_FORM_EDITOR_JS_187')) + '</textarea></label>' +
                '</div></div>';
        }

        function renderSubmitButtonCard(layout) { var b=layout.submitButton||{}; return localize('REXP_FORM_EDITOR_JS_188') +
            localize('REXP_FORM_EDITOR_JS_189') + renderOptions([['crm',localize('REXP_FORM_EDITOR_JS_190')],['primary',localize('REXP_FORM_EDITOR_JS_191')],['outline',localize('REXP_FORM_EDITOR_JS_192')]], b.style || 'crm') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_193') + renderOptions([['left',localize('REXP_FORM_EDITOR_JS_194')],['center',localize('REXP_FORM_EDITOR_JS_195')],['right',localize('REXP_FORM_EDITOR_JS_196')],['stretch',localize('REXP_FORM_EDITOR_JS_197')]], b.align || 'left') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_198') + renderOptions([['dark',localize('REXP_FORM_EDITOR_JS_199')],['light',localize('REXP_FORM_EDITOR_JS_200')]], b.textColor || 'dark') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_201') + renderOptions([['sm',localize('REXP_FORM_EDITOR_JS_202')],['default',localize('REXP_FORM_EDITOR_JS_203')],['lg',localize('REXP_FORM_EDITOR_JS_204')]], b.size || 'default') + '</select></label>' +
        '</div></div>'; }
        function renderBrandCard(layout) { return localize('REXP_FORM_EDITOR_JS_205') +
            renderIconPicker(localize('REXP_FORM_EDITOR_JS_206'), 'media.icon', layout.media.icon || 'info', ['info','business','list','page','task','mail']) +
            '<div class="rexp-form-editor__grid">' +
            localize('REXP_FORM_EDITOR_JS_207') + renderOptions([['100','100 px'],['140','140 px'],['180','180 px'],['220','220 px'],['280','280 px']], layout.media.imageHeight || '140') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_208') + renderOptions([['cover',localize('REXP_FORM_EDITOR_JS_209')],['contain',localize('REXP_FORM_EDITOR_JS_210')]], layout.media.imageMode || 'cover') + '</select></label>' +
            renderImageUploadControl(localize('REXP_FORM_EDITOR_JS_211'), 'media.imageUrl', layout.media.imageUrl || '', '/upload/path/image.png') +
        '</div></div>'; }
        function renderSideNoteCard(layout) {
            layout = ensureLayout(layout);
            var notes = normalizeSidebarNotes(layout.sidebarNotes, layout.sidebarNote || {});
            var html = localize('REXP_FORM_EDITOR_JS_212') +
                '<div class="rexp-form-editor__notice-list">';
            if (!notes.length) {
                html += localize('REXP_FORM_EDITOR_JS_213');
            }
            html += notes.map(function(note, index) {
                return renderSidebarNoteEditor(note, index, notes.length);
            }).join('') + '</div>' +
            '<div class="rexp-form-editor__actions"><button type="button" class="ui-btn ui-btn-light-border ui-btn-xs" data-action="add-sidebar-note"' + (notes.length >= 4 ? ' disabled' : '') + localize('REXP_FORM_EDITOR_JS_214') +
            '</div>';
            return html;
        }

        function renderSidebarNoteEditor(note, index, count) {
            note = note || {};
            var basePath = 'layout.sidebarNotes.' + index + '.';
            return '<div class="rexp-form-editor__notice-editor" data-note-index="' + index + '">' +
                localize('REXP_FORM_EDITOR_JS_215') + (index + 1) + '</b>' +
                    '<button type="button" class="ui-btn ui-btn-danger-light ui-btn-xs" data-action="delete-sidebar-note" data-note-index="' + index + localize('REXP_FORM_EDITOR_JS_216') +
                renderIconPicker(localize('REXP_FORM_EDITOR_JS_217') + (index + 1), 'sidebarNotes.' + index + '.icon', note.icon || 'alert', ['alert','info','business-warning','task','chat','mail']) +
                '<div class="rexp-form-editor__grid">' +
                    checkbox(localize('REXP_FORM_EDITOR_JS_218'), basePath + 'enabled', !!note.enabled) +
                    localize('REXP_FORM_EDITOR_JS_219') + basePath + 'variant">' + renderOptions([['info',localize('REXP_FORM_EDITOR_JS_220')],['warning',localize('REXP_FORM_EDITOR_JS_221')],['success',localize('REXP_FORM_EDITOR_JS_222')],['danger',localize('REXP_FORM_EDITOR_JS_223')]], note.variant || 'info') + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_224') + basePath + 'placement">' + renderOptions([['top',localize('REXP_FORM_EDITOR_JS_225')],['right',localize('REXP_FORM_EDITOR_JS_226')],['bottom',localize('REXP_FORM_EDITOR_JS_227')],['left',localize('REXP_FORM_EDITOR_JS_228')]], note.placement || note.position || 'right') + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_229') + basePath + 'width">' + renderOptions([['200','200 px'],['240','240 px'],['300','300 px'],['360','360 px']], note.width || '220') + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_230') + basePath + 'title" value="' + escapeHtml(note.title || localize('REXP_FORM_EDITOR_JS_231')) + '"></label>' +
                    localize('REXP_FORM_EDITOR_JS_232') + basePath + 'text">' + escapeHtml(note.text || '') + '</textarea></label>' +
                '</div>' +
            '</div>';
        }

        function renderPostSubmitCard(layout, settings) { var a=layout.postSubmit||{}; return localize('REXP_FORM_EDITOR_JS_233') +
            renderIconPicker(localize('REXP_FORM_EDITOR_JS_234'), 'postSubmit.icon', a.icon || 'success', ['success','fail','done','business-confirm','info','page','chat']) +
            '<div class="rexp-form-editor__grid">' +
            localize('REXP_FORM_EDITOR_JS_235') + renderOptions([['message',localize('REXP_FORM_EDITOR_JS_236')],['hide_form',localize('REXP_FORM_EDITOR_JS_237')],['redirect',localize('REXP_FORM_EDITOR_JS_238')],['reload',localize('REXP_FORM_EDITOR_JS_239')]], a.mode || 'message') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_240') + escapeHtml(a.title || localize('REXP_FORM_EDITOR_JS_241')) + '"></label>' +
            localize('REXP_FORM_EDITOR_JS_242') + escapeHtml(a.buttonText || '') + localize('REXP_FORM_EDITOR_JS_243') +
            renderImageUploadControl(localize('REXP_FORM_EDITOR_JS_244'), 'postSubmit.imageUrl', a.imageUrl || '', '/upload/success.png') +
            localize('REXP_FORM_EDITOR_JS_245') + escapeHtml(a.redirectUrl || '') + '" placeholder="/thank-you/"></label>' +
            checkbox(localize('REXP_FORM_EDITOR_JS_246'),'layout.postSubmit.showAgainButton',!!a.showAgainButton) +
            localize('REXP_FORM_EDITOR_JS_247') + escapeHtml(a.message || settings.successText || localize('REXP_FORM_EDITOR_JS_248')) + '</textarea></label>' +
        '</div></div>'; }
        function renderAdvancedDesignCard(layout) { var a=layout.advanced||{}; return localize('REXP_FORM_EDITOR_JS_249') +
            localize('REXP_FORM_EDITOR_JS_250') + renderOptions([['none',localize('REXP_FORM_EDITOR_JS_251')],['fade',localize('REXP_FORM_EDITOR_JS_252')],['slide',localize('REXP_FORM_EDITOR_JS_253')]], a.animation || 'none') + '</select></label>' +
            localize('REXP_FORM_EDITOR_JS_254') + escapeHtml(a.cssClass || '') + '" placeholder="my-form-theme"></label>' +
        '</div></div>'; }
        function layoutCssVars(layout) { var fontMap={system:'var(--ui-font-family-primary, Arial, sans-serif)',onest:'Onest, var(--ui-font-family-primary, Arial, sans-serif)',arial:'Arial, sans-serif',georgia:'Georgia, serif',inter:'Inter, Arial, sans-serif'}; var w=String(layout.width||'760'); if(/^\d+$/.test(w)){w+='px';} return '--rexp-form-primary:' + escapeHtml(layout.colors.primary) + ';--rexp-form-bg:' + escapeHtml(layout.colors.background) + ';--rexp-form-card:' + escapeHtml(layout.colors.card) + ';--rexp-form-text:' + escapeHtml(layout.colors.text) + ';--rexp-form-radius:' + escapeHtml(layout.shape.radius) + 'px;--rexp-form-field-radius:' + escapeHtml(layout.shape.fieldRadius) + 'px;--rexp-form-font:' + escapeHtml(fontMap[layout.typography.font] || fontMap.system) + ';--rexp-form-font-size:' + escapeHtml(layout.typography.size) + 'px;--rexp-form-note-width:' + escapeHtml((layout.sidebarNote&&layout.sidebarNote.width)||'220') + 'px;--rexp-form-image-height:' + escapeHtml((layout.media&&layout.media.imageHeight)||'140') + 'px;--rexp-form-page-image:url(' + escapeHtml((layout.container&&layout.container.backgroundImageUrl)||'') + ');--rexp-form-width:' + escapeHtml(w) + ';--rexp-form-icon-bg:' + escapeHtml((layout.icons&&layout.icons.background)||'#e8f7ff') + ';--rexp-form-icon-color:' + escapeHtml((layout.icons&&layout.icons.color)||'#525c69') + ';'; }

        function updatePreviewLayoutVars() {
            if (!node || !node.querySelectorAll || !state.form || !state.form.schema) {
                return;
            }
            state.form.schema.layout = ensureLayout(state.form.schema.layout);
            var vars = layoutCssVars(state.form.schema.layout);
            var iconBg = (state.form.schema.layout.icons && state.form.schema.layout.icons.background) || '#e8f7ff';
            var iconColor = (state.form.schema.layout.icons && state.form.schema.layout.icons.color) || '#525c69';
            Array.prototype.forEach.call(node.querySelectorAll('.rexp-form-editor__theme-preview'), function(preview) {
                preview.setAttribute('style', vars);
                Array.prototype.forEach.call(preview.querySelectorAll('.rexp-form-editor__icon-shell'), function(shell) {
                    shell.style.setProperty('--rexp-form-icon-bg', iconBg);
                    shell.style.setProperty('--rexp-form-icon-color', iconColor);
                });
                Array.prototype.forEach.call(preview.querySelectorAll('.rexp-form-icon-mask'), function(mask) {
                    mask.style.backgroundColor = iconColor;
                });
            });
        }
        function responsivePreviewClass(layout) {
            var r = layout && layout.responsive ? layout.responsive : {};
            if (r.enabled === false) { return ''; }
            return ' is-mobile-note-' + escapeHtml(r.sideNoteMobile || 'top') + ' is-mobile-image-' + escapeHtml(r.imageMobile || 'show') + (r.fullWidth ? ' is-mobile-full' : '') + (r.compact ? ' is-mobile-compact' : '');
        }

        function previewAdvancedClass(layout) {
            var cssClass = layout && layout.advanced && layout.advanced.cssClass ? String(layout.advanced.cssClass) : '';
            return cssClass.indexOf('rexp-public-form--rexpress') >= 0 ? ' is-rexpress' : '';
        }

        function groupSidebarNotesByPlacement(notes) {
            var groups = {top: [], right: [], bottom: [], left: []};
            (Array.isArray(notes) ? notes : []).forEach(function(note) {
                var placement = safeEnum(note.placement || note.position || 'right', ['top', 'right', 'bottom', 'left'], 'right');
                groups[placement].push(note);
            });
            return groups;
        }

        function renderPreviewNoticeZone(notes, placement, iconCfg, iconClass) {
            notes = Array.isArray(notes) ? notes : [];
            if (!notes.length) { return ''; }
            var iconsEnabled = iconCfg.enabled !== false && iconCfg.noteEnabled !== false;
            return '<div class="rexp-form-editor__theme-preview-notes is-' + escapeHtml(placement) + '">' + notes.map(function(note) {
                var icon = iconsEnabled ? renderIconShell(note.icon || 'info', iconClass) : '';
                return '<div class="rexp-form-editor__theme-preview-note is-' + escapeHtml(note.variant || 'info') + ' is-placement-' + escapeHtml(placement) + '"><b>' + icon + escapeHtml(note.title || '') + '</b><span>' + escapeHtml(note.text) + '</span></div>';
            }).join('') + '</div>';
        }

        function renderThemedPreview(fields, sections, settings, layout, compact) {
            layout = ensureLayout(layout);
            var activeNotes = getActiveSidebarNotes(layout);
            var groups = groupSidebarNotesByPlacement(activeNotes);
            var m = layout.media || {};
            var p = layout.postSubmit || {};
            var iconCfg = layout.icons || {};
            var iconsEnabled = iconCfg.enabled !== false;
            var iconClass = 'is-shape-' + escapeHtml(iconCfg.shape || 'rounded') + ' is-size-' + escapeHtml(iconCfg.size || 'md');
            var formIcon = iconsEnabled && iconCfg.formEnabled !== false ? renderIconShell(m.icon || 'info', iconClass) : '';
            var successIcon = iconsEnabled && iconCfg.successEnabled !== false ? renderIconShell(p.icon || 'success', iconClass) : '';
            var topNotes = renderPreviewNoticeZone(groups.top, 'top', iconCfg, iconClass);
            var rightNotes = renderPreviewNoticeZone(groups.right, 'right', iconCfg, iconClass);
            var bottomNotes = renderPreviewNoticeZone(groups.bottom, 'bottom', iconCfg, iconClass);
            var leftNotes = renderPreviewNoticeZone(groups.left, 'left', iconCfg, iconClass);
            var formHtml = '<div class="rexp-form-editor__theme-preview-form">' +
                (m.imageUrl ? '<div class="rexp-form-editor__theme-preview-image ' + (m.imageMode==='contain'?'is-contain':'') + '" style="background-image:url(' + escapeHtml(m.imageUrl) + ')"></div>' : '') +
                '<div class="rexp-form-editor__theme-preview-title">' + formIcon + escapeHtml(state.form.name || localize('REXP_FORM_EDITOR_JS_255')) + '</div>' +
                renderThemedPreviewFields(fields, sections) +
                '<div class="rexp-form-editor__theme-preview-submit is-' + escapeHtml(layout.submitButton.align || 'left') + ' is-size-' + escapeHtml(layout.submitButton.size || 'default') + '"><button type="button" class="ui-btn ui-btn-primary">' + escapeHtml((settings&&settings.submitText)||localize('REXP_FORM_EDITOR_JS_256')) + '</button></div>' +
                '<div class="rexp-form-editor__theme-preview-after">' + (p.imageUrl ? '<div class="rexp-form-editor__theme-preview-after-image" style="background-image:url(' + escapeHtml(p.imageUrl) + ')"></div>' : '') + successIcon + '<b>' + escapeHtml(p.title || localize('REXP_FORM_EDITOR_JS_257')) + '</b><em>' + escapeHtml(p.message || settings.successText || localize('REXP_FORM_EDITOR_JS_258')) + '</em></div>' +
            '</div>';
            return '<div class="rexp-form-editor__theme-preview is-' + escapeHtml(layout.theme) + previewAdvancedClass(layout) + ' is-field-' + escapeHtml(layout.surface.fieldStyle) + ' is-shadow-' + escapeHtml(layout.surface.shadow) + ' is-border-' + escapeHtml(layout.surface.border) + ' is-density-' + escapeHtml(layout.density) + ' is-align-' + escapeHtml(layout.container.align) + ' is-pad-' + escapeHtml(layout.container.padding) + ' is-bg-' + escapeHtml(layout.container.backgroundOverlay) + ' is-anim-' + escapeHtml(layout.advanced.animation) + responsivePreviewClass(layout) + (activeNotes.length ? ' has-notes' : ' has-no-notes') + (layout.container.backgroundImageUrl?' has-page-image':'') + (compact?' is-compact':'') + '" style="' + layoutCssVars(layout) + '"><div class="rexp-form-editor__theme-preview-stack">' + topNotes + '<div class="rexp-form-editor__theme-preview-row">' + leftNotes + formHtml + rightNotes + '</div>' + bottomNotes + '</div></div>';
        }
        function renderThemedPreviewFields(fields, sections) {
            fields = Array.isArray(fields) ? fields : [];
            sections = Array.isArray(sections) ? sections : [];
            if (!fields.length) { return localize('REXP_FORM_EDITOR_JS_259'); }
            var used = {};
            var html = sections.map(function(section) {
                var sectionUid = String(section.uid || '');
                var sectionFields = fields.filter(function(field) { return String(field.sectionUid || '') === sectionUid; });
                if (!sectionFields.length) { return ''; }
                sectionFields.forEach(function(field) { used[String(field.uid || field.code || '')] = true; });
                return renderThemedPreviewSection(section.title || '', section.description || '', sectionFields);
            }).join('');
            var rest = fields.filter(function(field) { return !used[String(field.uid || field.code || '')]; });
            if (rest.length) { html += renderThemedPreviewSection('', '', rest); }
            return html;
        }

        function renderThemedPreviewSection(title, description, fields) {
            return '<div class="rexp-form-editor__preview-section">' +
                (title ? '<h3>' + escapeHtml(title) + '</h3>' : '') +
                (description ? '<div class="rexp-form-editor__hint">' + escapeHtml(description) + '</div>' : '') +
                fields.map(function(field) {
                    return '<label class="rexp-form-editor__preview-field rexp-form-editor__preview-field--' + escapeHtml(field.type || 'string') + '"><span>' + escapeHtml(field.label || field.code || localize('REXP_FORM_EDITOR_JS_260')) + (field.required ? ' *' : '') + '</span>' + renderPreviewControl(field) + ((field.hint || (field.ui && field.ui.hint)) ? '<em class="rexp-form-editor__preview-hint">' + escapeHtml(field.hint || field.ui.hint) + '</em>' : '') + '</label>';
                }).join('') +
            '</div>';
        }

        function renderDiagnosticsPanel() {
            var hasDiagnostics = !!state.diagnostics;
            return '<div class="rexp-form-editor__panel rexp-form-editor__diagnostics-panel">' +
                '<div class="rexp-form-editor__diagnostics-hero">' +
                    '<div>' +
                        localize('REXP_FORM_EDITOR_JS_261') +
                        localize('REXP_FORM_EDITOR_JS_262') +
                    '</div>' +
                    localize('REXP_FORM_EDITOR_JS_263') +
                '</div>' +
                '<div class="rexp-form-editor__diagnostics-grid">' +
                    localize('REXP_FORM_EDITOR_JS_264') +
                    localize('REXP_FORM_EDITOR_JS_265') +
                    localize('REXP_FORM_EDITOR_JS_266') +
                '</div>' +
                (hasDiagnostics ? '<div class="rexp-form-editor__diagnostics-result">' + renderDiagnostics() + '</div>' : localize('REXP_FORM_EDITOR_JS_267')) +
            '</div>';
        }

        function linkedFrame(url, title) {
            if (!state.form.id) { return localize('REXP_FORM_EDITOR_JS_268'); }
            return '<div class="rexp-form-editor__panel"><div class="rexp-form-editor__actions"><a class="ui-btn ui-btn-primary" href="' + escapeHtml(url + '?FORM_ID=' + state.form.id) + '">' + escapeHtml(title) + '</a></div></div>';
        }

        function renderRuntimeComponentSnippet(form) {
            var formCode = String((form && form.code) || '').trim() || 'form_code';
            var snippet = '<?php\n$APPLICATION->IncludeComponent(\n    "rexp.form:runtime",\n    "",\n    [\n        "FORM_CODE" => "' + escapeSnippet(formCode) + '",\n        "TITLE" => "",\n        "MODE" => "auto",\n    ],\n    false\n);\n?>';
            return '<div class="rexp-form-editor__component-snippet --wide">' +
                '<div class="rexp-form-editor__component-snippet-head">' +
                    localize('REXP_FORM_EDITOR_JS_269') +
                    '</div>' +
                    localize('REXP_FORM_EDITOR_JS_270') +
                '</div>' +
                '<textarea class="rexp-form-editor__component-snippet-code" readonly data-runtime-snippet="1">' + escapeHtml(snippet) + '</textarea>' +
            '</div>';
        }

        function escapeSnippet(value) {
            return String(value || '').replace(/\\/g, '\\\\').replace(/"/g, '\\"');
        }

        function input(label, key, value, wide) {
            return '<label class="' + (wide ? '--wide' : '') + '"><span>' + escapeHtml(label) + '</span><input data-field="' + escapeHtml(key) + '" value="' + escapeHtml(value || '') + '"></label>';
        }

        function textarea(label, key, value, wide) {
            return '<label class="' + (wide ? '--wide' : '') + '"><span>' + escapeHtml(label) + '</span><textarea data-field="' + escapeHtml(key) + '">' + escapeHtml(value || '') + '</textarea></label>';
        }

        function checkbox(label, key, checked) {
            return '<label class="rexp-form-editor__check"><input type="checkbox" data-field="' + escapeHtml(key) + '"' + (checked ? ' checked' : '') + '><span>' + escapeHtml(label) + '</span></label>';
        }

        function renderSectionsHeader(sections) {
            sections = Array.isArray(sections) ? sections : [];
            return '<div class="rexp-form-editor__fields-head rexp-form-editor__sections-header">' +
                '<div>' +
                    localize('REXP_FORM_EDITOR_JS_271') +
                '</div>' +
                '<div class="rexp-form-editor__actions">' +
                    localize('REXP_FORM_EDITOR_JS_272') +
                '</div>' +
            '</div>' +
            (isEditorCountersEnabled() ? '<div class="rexp-form-editor__fields-summary rexp-form-editor__sections-summary">' +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_273'), sections.length) +
            '</div>' : '');
        }

        function renderFieldsHeader(fields) {
            var allExpanded = fields.length && fields.every(function(field) { return !!field.expanded; });
            var toggleText = allExpanded ? localize('REXP_FORM_EDITOR_JS_274') : localize('REXP_FORM_EDITOR_JS_275');

            return '<div class="rexp-form-editor__fields-head">' +
                '<div>' +
                    localize('REXP_FORM_EDITOR_JS_276') +
                '</div>' +
                '<div class="rexp-form-editor__actions">' +
                    localize('REXP_FORM_EDITOR_JS_277') +
                    '<button type="button" class="ui-btn ui-btn-light-border" data-action="toggle-all-fields"' + (!fields.length ? ' disabled' : '') + '>' + toggleText + '</button>' +
                '</div>' +
            '</div>' +
            (isEditorCountersEnabled() ? renderFieldsSummary(fields) : '');
        }


        function renderFieldsSummary(fields) {
            var counters = {required: 0, multiple: 0, lists: 0, warnings: 0};
            fields.forEach(function(field, index) {
                if (field.required || field.mandatory) { counters.required++; }
                if (field.multiple) { counters.multiple++; }
                if ((field.type || '') === 'enumeration') { counters.lists++; }
                counters.warnings += getFieldWarnings(field, index, fields).length;
            });
            return '<div class="rexp-form-editor__fields-summary">' +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_278'), fields.length) +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_279'), counters.required) +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_280'), counters.multiple) +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_281'), counters.lists) +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_282'), counters.warnings, counters.warnings ? '--warning' : '') +
            '</div>';
        }

        function renderSummaryPill(label, value, modifier) {
            return '<span class="rexp-form-editor__summary-pill ' + escapeHtml(modifier || '') + '"><span>' + escapeHtml(label) + ': </span><b>' + escapeHtml(value) + '</b></span>';
        }

function renderFieldMeta(parts) {
            parts = Array.isArray(parts) ? parts.filter(Boolean) : [];
            if (!parts.length) {
                return '';
            }
            return '<div class="rexp-form-editor__field-meta">' + parts.map(function(part) {
                return '<span class="rexp-form-editor__field-meta-chip">' + escapeHtml(part) + '</span>';
            }).join('') + '</div>';
        }

        function renderSection(section, index) {
            return '<div class="rexp-form-editor__section rexp-form-editor__section-card" data-section-index="' + index + '">' +
                '<div class="rexp-form-editor__field-head">' +
                    '<div>' +
                        localize('REXP_FORM_EDITOR_JS_284') + (index + 1) + ': ' + escapeHtml(section.title || localize('REXP_FORM_EDITOR_JS_285')) + '</div>' +
                    '</div>' +
                    '<div class="rexp-form-editor__field-actions">' +
                        '<button type="button" class="ui-btn ui-btn-light-border ui-btn-xs" data-action="section-up"' + (index <= 0 ? ' disabled' : '') + localize('REXP_FORM_EDITOR_JS_286') +
                        '<button type="button" class="ui-btn ui-btn-light-border ui-btn-xs" data-action="section-down"' + (index >= state.form.schema.sections.length - 1 ? ' disabled' : '') + localize('REXP_FORM_EDITOR_JS_287') +
                        localize('REXP_FORM_EDITOR_JS_288') +
                    '</div>' +
                '</div>' +
                '<div class="rexp-form-editor__grid">' +
                    input(localize('REXP_FORM_EDITOR_JS_289'), 'section.title', section.title || '') +
                    input(localize('REXP_FORM_EDITOR_JS_290'), 'section.uid', section.uid || '') +
                    textarea(localize('REXP_FORM_EDITOR_JS_291'), 'section.description', section.description || '', true) +
                '</div>' +
            '</div>';
        }

        function renderField(field, index) {
            field.ui = field.ui && typeof field.ui === 'object' ? field.ui : {};
            field.settings = field.settings && typeof field.settings === 'object' ? field.settings : {};
            field.validation = field.validation && typeof field.validation === 'object' ? field.validation : {};
            var type = field.type || 'string';
            var view = getFieldView(field);
            var isExpanded = !!field.expanded;
            var itemsText = Array.isArray(field.items) ? field.items.map(function(item) { return item.label || item.title || item.value || ''; }).join('\n') : '';
            var isList = type === 'enumeration' || type === 'select' || type === 'radio' || type === 'checkbox_list';
            var isEntitySelector = type === 'user' || type === 'department' || type === 'entity_selector';
            var showMultiple = typeSupportsMultiple(type, view);
            var showView = ['enumeration', 'boolean'].indexOf(type) >= 0;
            var showItems = isList;
            var sectionOptions = '<option value=""' + (!field.sectionUid ? ' selected' : '') + localize('REXP_FORM_EDITOR_JS_292') + (state.form.schema.sections.length ? state.form.schema.sections.map(function(section) {
                return '<option value="' + escapeHtml(section.uid) + '"' + (String(field.sectionUid) === String(section.uid) ? ' selected' : '') + '>' + escapeHtml(section.title || section.uid) + '</option>';
            }).join('') : '');
            var viewOptions = getViewOptions(type);
            var summaryParts = [getFieldTypeTitle(type)];
            if (view) { summaryParts.push(getViewTitle(view)); }
            if (field.required || field.mandatory) { summaryParts.push(localize('REXP_FORM_EDITOR_JS_293')); }
            if (field.multiple) { summaryParts.push(localize('REXP_FORM_EDITOR_JS_294')); }
            var usesPlaceholder = fieldUsesPlaceholder(type, view);
            var placeholder = usesPlaceholder ? (field.placeholder || (field.ui && field.ui.placeholder) || getDefaultPlaceholder(type, view)) : '';
            var warnings = getFieldWarnings(field, index, state.form.schema.fields || []);

            return '<div class="rexp-form-editor__field rexp-form-editor__field-card" data-field-index="' + index + '">' +
                '<div class="rexp-form-editor__field-head">' +
                    '<div class="rexp-form-editor__field-head-main"><div class="rexp-form-editor__field-title-row"><span class="rexp-form-editor__field-index">#' + (index + 1) + '</span><div class="rexp-form-editor__field-title">' + escapeHtml(field.label || field.title || field.code || localize('REXP_FORM_EDITOR_JS_295')) + '</div></div>' +
                    '<div class="rexp-form-editor__hint">' + escapeHtml(summaryParts.join(' · ')) + '</div></div>' +
                    '<div class="rexp-form-editor__field-actions">' +
                        '<button type="button" class="ui-btn ui-btn-light-border ui-btn-xs" data-action="move-up"' + (index <= 0 ? ' disabled' : '') + localize('REXP_FORM_EDITOR_JS_296') +
                        '<button type="button" class="ui-btn ui-btn-light-border ui-btn-xs" data-action="move-down"' + (index >= state.form.schema.fields.length - 1 ? ' disabled' : '') + localize('REXP_FORM_EDITOR_JS_297') +
                        '<button type="button" class="ui-btn ui-btn-light-border ui-btn-xs ui-btn-icon-setting" data-action="toggle-field-full">' + (isExpanded ? localize('REXP_FORM_EDITOR_JS_298') : localize('REXP_FORM_EDITOR_JS_299')) + '</button>' +
                        localize('REXP_FORM_EDITOR_JS_300') +
                        localize('REXP_FORM_EDITOR_JS_301') +
                    '</div>' +
                '</div>' +
                renderFieldWarnings(warnings) +
                renderFieldTypeHint(field, view) +
                '<div class="rexp-form-editor__field-basic rexp-form-editor__grid">' +
                    input(localize('REXP_FORM_EDITOR_JS_302'), 'field.label', field.label || field.title || '') +
                    localize('REXP_FORM_EDITOR_JS_303') + renderOptions(getFieldTypeOptions(), type) + '</select></label>' +
                    localize('REXP_FORM_EDITOR_JS_304') + sectionOptions + '</select></label>' +
                    (usesPlaceholder ? input(localize('REXP_FORM_EDITOR_JS_305'), 'field.placeholder', placeholder) : '') +
                    checkbox(localize('REXP_FORM_EDITOR_JS_306'), 'field.required', !!(field.required || field.mandatory)) +
                    (showMultiple ? checkbox(localize('REXP_FORM_EDITOR_JS_307'), 'field.multiple', !!field.multiple) : '') +
                '</div>' +
                (showView || showItems || isEntitySelector ? '<div class="rexp-form-editor__field-special">' +
                    localize('REXP_FORM_EDITOR_JS_308') + escapeHtml(getFieldTypeTitle(type)) + '»</div>' +
                    '<div class="rexp-form-editor__grid">' +
                        (showView ? localize('REXP_FORM_EDITOR_JS_309') + renderOptions(viewOptions, view || getDefaultView(type)) + '</select></label>' : '') +
                        (showItems ? textarea(localize('REXP_FORM_EDITOR_JS_310'), 'field.items', itemsText, true) : '') +
                        (isList ? localize('REXP_FORM_EDITOR_JS_311') : '') +
                        (isEntitySelector ? localize('REXP_FORM_EDITOR_JS_312') + escapeHtml(type === 'department' ? localize('REXP_FORM_EDITOR_JS_313') : localize('REXP_FORM_EDITOR_JS_314')) + '</div></div>' : '') +
                    '</div>' +
                '</div>' : '') +
                (isExpanded ? '<div class="rexp-form-editor__field-advanced">' +
                    localize('REXP_FORM_EDITOR_JS_315') +
                    '<div class="rexp-form-editor__grid">' +
                        input(localize('REXP_FORM_EDITOR_JS_316'), 'field.code', field.code || '') +
                        input(localize('REXP_FORM_EDITOR_JS_317'), 'field.defaultValue', field.defaultValue || '') +
                        textarea(localize('REXP_FORM_EDITOR_JS_318'), 'field.hint', field.hint || (field.ui && field.ui.hint) || '', true) +
                        input(localize('REXP_FORM_EDITOR_JS_319'), 'field.sort', field.sort || ((index + 1) * 100)) +
                        localize('REXP_FORM_EDITOR_JS_320') + renderOptions([['full', localize('REXP_FORM_EDITOR_JS_321')], ['half', localize('REXP_FORM_EDITOR_JS_322')], ['third', localize('REXP_FORM_EDITOR_JS_323')]], (field.ui && field.ui.width) || 'full') + '</select></label>' +
                        localize('REXP_FORM_EDITOR_JS_324') + textarea(localize('REXP_FORM_EDITOR_JS_325'), 'field.settingsJson', JSON.stringify(field.settings || {}, null, 2), true) + localize('REXP_FORM_EDITOR_JS_326') +
                    '</div>' +
                '</div>' : '') +
            '</div>';
        }


        function getFieldWarnings(field, index, fields) {
            var warnings = [];
            field = field || {};
            var code = String(field.code || '').trim();
            var type = String(field.type || 'string');
            var view = getFieldView(field);
            if (!String(field.label || field.title || '').trim()) { warnings.push(localize('REXP_FORM_EDITOR_JS_327')); }
            if (!code) { warnings.push(localize('REXP_FORM_EDITOR_JS_328')); }
            if (code) {
                var duplicates = (fields || []).filter(function(item, itemIndex) {
                    return itemIndex !== index && String(item && item.code || '').trim() === code;
                });
                if (duplicates.length) { warnings.push(localize('REXP_FORM_EDITOR_JS_329')); }
            }
            if (type === 'enumeration' && !getFieldValueItems(field).length) {
                warnings.push(localize('REXP_FORM_EDITOR_JS_330'));
            }
            return warnings;
        }

        function renderFieldWarnings(warnings) {
            if (!warnings || !warnings.length) { return ''; }
            return '<div class="rexp-form-editor__field-warnings">' + warnings.map(function(warning) {
                return '<div class="rexp-form-editor__field-warning">' + escapeHtml(warning) + '</div>';
            }).join('') + '</div>';
        }

        function renderFieldTypeHint(field, view) {
            field = field || {};
            var type = String(field.type || 'string');
            var text = '';
            if (type === 'enumeration') {
                if (view === 'checkbox') { text = localize('REXP_FORM_EDITOR_JS_331'); }
                else if (view === 'radio') { text = localize('REXP_FORM_EDITOR_JS_332'); }
                else { text = localize('REXP_FORM_EDITOR_JS_333'); }
            }
            if (type === 'user') { text = localize('REXP_FORM_EDITOR_JS_334'); }
            if (type === 'department') { text = localize('REXP_FORM_EDITOR_JS_335'); }
            if (!text) { return ''; }
            return '<div class="rexp-form-editor__field-type-hint">' + escapeHtml(text) + '</div>';
        }

        function renderRulesAddArea(hasRules, hasFields) {
            if (!hasFields) {
                return '';
            }
            if (!hasRules) {
                return renderRuleModeCards(false);
            }
            if (!state.showRuleModeCards) {
                return localize('REXP_FORM_EDITOR_JS_336');
            }
            return '<div class="rexp-form-editor__rules-builder-add-panel">' +
                '<div class="rexp-form-editor__rules-builder-add-panel-head">' +
                    localize('REXP_FORM_EDITOR_JS_337') +
                    localize('REXP_FORM_EDITOR_JS_338') +
                '</div>' +
                renderRuleModeCards(true) +
            '</div>';
        }

        function renderRuleModeCards(compact) {
            var scenarios = [
                {
                    mode: 'different_values_different_fields',
                    icon: 'different',
                    title: localize('REXP_FORM_EDITOR_JS_340')
                },
                {
                    mode: 'different_values_same_fields',
                    icon: 'same',
                    title: localize('REXP_FORM_EDITOR_JS_343')
                },
                {
                    mode: 'conditions_same_fields',
                    icon: 'aggregate',
                    title: localize('REXP_FORM_EDITOR_JS_346')
                }
            ];
            return '<div class="rexp-form-editor__crm-rules-panel' + (compact ? ' rexp-form-editor__crm-add-rule-modes' : '') + '">' +
                (!compact ? localize('REXP_FORM_EDITOR_JS_348') + localize('REXP_FORM_EDITOR_JS_349') : '') +
                '<div class="rexp-form-editor__crm-rule-mode-cards">' + scenarios.map(renderRuleModeCard).join('') + '</div>' +
            '</div>';
        }

        function renderRuleModeCard(scenario) {
            scenario = scenario || {};
            var icon = scenario.icon || 'different';
            var mode = scenario.mode || 'different_values_different_fields';
            return '<div class="rexp-form-editor__crm-rule-mode-card" data-rule-mode="' + escapeHtml(mode) + '">' +
                '<div class="rexp-form-editor__crm-rule-mode-icon rexp-form-editor__crm-rule-mode-icon--' + escapeHtml(icon) + ' rexp-form-editor__crm-rule-mode-icon--svg">' + renderRuleModeSvg(icon) + '</div>' +
                '<div class="rexp-form-editor__crm-rule-mode-title">' + escapeHtml(scenario.title || '') + '</div>' +
                '<button type="button" class="rexp-form-editor__crm-rule-create" data-action="add-rule" data-mode="' + escapeHtml(mode) + '">' + escapeHtml(localize('REXP_FORM_EDITOR_JS_351')) + '</button>' +
            '</div>';
        }

        function renderRuleModeSvg(icon) {
            var stroke = '#9fb0c4';
            var fill = '#9fb0c4';
            var common = 'fill="none" stroke="' + stroke + '" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"';
            if (icon === 'same') {
                return '<svg viewBox="0 0 92 92" aria-hidden="true" focusable="false">' +
                    '<circle cx="23" cy="26" r="8" fill="' + fill + '"/>' +
                    '<circle cx="23" cy="46" r="8" fill="' + fill + '"/>' +
                    '<circle cx="23" cy="66" r="8" fill="' + fill + '"/>' +
                    '<path d="M34 27 C45 27 45 46 56 46" ' + common + '/>' +
                    '<path d="M34 46 H56" ' + common + '/>' +
                    '<path d="M34 65 C45 65 45 46 56 46" ' + common + '/>' +
                    '<rect x="58" y="38" width="25" height="16" rx="3" fill="' + fill + '"/>' +
                '</svg>';
            }
            if (icon === 'aggregate') {
                return '<svg viewBox="0 0 92 92" aria-hidden="true" focusable="false">' +
                    '<circle cx="22" cy="25" r="8" fill="' + fill + '"/>' +
                    '<circle cx="22" cy="46" r="8" fill="' + fill + '"/>' +
                    '<circle cx="22" cy="67" r="8" fill="' + fill + '"/>' +
                    '<path d="M35 25 H44 V67 H35" ' + common + '/>' +
                    '<path d="M44 46 H57" ' + common + '/>' +
                    '<rect x="59" y="38" width="25" height="16" rx="3" fill="' + fill + '"/>' +
                '</svg>';
            }

            return '<svg viewBox="0 0 92 92" aria-hidden="true" focusable="false">' +
                '<circle cx="22" cy="23" r="8" fill="' + fill + '"/>' +
                '<circle cx="22" cy="46" r="8" fill="' + fill + '"/>' +
                '<circle cx="22" cy="69" r="8" fill="' + fill + '"/>' +
                '<path d="M35 23 H46" ' + common + '/>' +
                '<path d="M35 46 H46" ' + common + '/>' +
                '<path d="M35 69 H46" ' + common + '/>' +
                '<path d="M44 19 L52 23 L44 27" ' + common + '/>' +
                '<path d="M44 42 L52 46 L44 50" ' + common + '/>' +
                '<path d="M44 65 L52 69 L44 73" ' + common + '/>' +
                '<rect x="58" y="16" width="25" height="13" rx="3" fill="' + fill + '"/>' +
                '<rect x="58" y="39" width="25" height="13" rx="3" fill="' + fill + '"/>' +
                '<rect x="58" y="62" width="25" height="13" rx="3" fill="' + fill + '"/>' +
            '</svg>';
        }

        function renderRulesSummary(groups, fields) {
            if (!isEditorCountersEnabled()) { return ''; }
            var stats = {active: 0, inactive: 0, incomplete: 0, actions: 0};
            (groups || []).forEach(function(group) {
                group = normalizeRuleGroup(group);
                if (group.enabled === false) { stats.inactive++; } else { stats.active++; }
                stats.actions += getGroupActions(group).length;
                if (getRuleWarnings(group, fields).length) { stats.incomplete++; }
            });
            return '<div class="rexp-form-editor__rules-summary">' +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_352'), stats.active) +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_353'), stats.inactive) +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_354'), stats.actions) +
                renderSummaryPill(localize('REXP_FORM_EDITOR_JS_355'), stats.incomplete, stats.incomplete ? '--warning' : '') +
            '</div>';
        }

        function renderRuleGroup(group, index, fields) {
            group = normalizeRuleGroup(group);
            state.form.schema.ruleGroups[index] = group;
            var mode = group.mode || 'different_values_different_fields';
            var title = getRuleModeTitle(mode);
            var warnings = getRuleWarnings(group, fields);
            var statusClass = group.enabled === false ? ' is-disabled' : (warnings.length ? ' has-warnings' : ' is-ready');
            var html = '<div class="rexp-form-editor__rule rexp-form-editor__friendly-rule' + statusClass + '" data-rule-index="' + index + '">' +
                '<div class="rexp-form-editor__friendly-rule-head">' +
                    '<div class="rexp-form-editor__friendly-rule-index">' + (index + 1) + '</div>' +
                    '<div class="rexp-form-editor__friendly-rule-title-wrap">' +
                        '<div class="rexp-form-editor__friendly-rule-title">' + escapeHtml(title) + '</div>' +
                        '<div class="rexp-form-editor__friendly-rule-summary">' + escapeHtml(getRuleHumanSummary(group, fields)) + '</div>' +
                    '</div>' +
                    '<div class="rexp-form-editor__friendly-rule-controls">' +
                        renderRuleEnabledSwitcher(group) +
                        localize('REXP_FORM_EDITOR_JS_356') +
                    '</div>' +
                '</div>' +
                renderRuleWarnings(warnings) +
                '<div class="rexp-form-editor__friendly-rule-note">' + escapeHtml(getRuleModeDescription(mode)) + '</div>' +
                '<div class="rexp-form-editor__friendly-rule-grid">' +
                    '<div class="rexp-form-editor__friendly-rule-column rexp-form-editor__friendly-rule-column--if">' +
                        localize('REXP_FORM_EDITOR_JS_357') +
                        renderRuleConditionsArea(group, fields) +
                    '</div>' +
                    '<div class="rexp-form-editor__friendly-rule-arrow">→</div>' +
                    '<div class="rexp-form-editor__friendly-rule-column rexp-form-editor__friendly-rule-column--then">' +
                        localize('REXP_FORM_EDITOR_JS_358') +
                        renderRuleActionsArea(group, fields) +
                    '</div>' +
                '</div>' +
            '</div>';
            return html;
        }

        function renderRuleEnabledSwitcher(group) {
            var checked = group.enabled !== false;
            return localize('REXP_FORM_EDITOR_JS_359') +
                '<input class="rexp-form-editor__rule-enabled-input" type="checkbox" data-field="ruleGroup.enabled"' + (checked ? ' checked' : '') + '>' +
                '<span data-switcher-init="y" class="ui-switcher ui-switcher-size-sm ui-switcher-color-green' + (checked ? ' ui-switcher-checked' : '') + '">' +
                    '<span class="ui-switcher-cursor"></span>' +
                    localize('REXP_FORM_EDITOR_JS_360') +
                    localize('REXP_FORM_EDITOR_JS_361') +
                '</span>' +
            '</label>';
        }

        function getRuleModeDescription(mode) {
            if (mode === 'different_values_same_fields') {
                return localize('REXP_FORM_EDITOR_JS_362');
            }
            if (mode === 'conditions_same_fields') {
                return localize('REXP_FORM_EDITOR_JS_363');
            }
            return localize('REXP_FORM_EDITOR_JS_364');
        }

        function renderRuleConditionsArea(group, fields) {
            if (group.mode === 'conditions_same_fields') {
                return renderConditionsSameFields(group, fields);
            }
            if (group.mode === 'different_values_same_fields') {
                return renderDifferentValuesSameFields(group, fields);
            }
            return renderDifferentValuesDifferentFields(group, fields);
        }

        function renderRuleActionsArea(group, fields) {
            if (group.mode === 'different_values_different_fields') {
                return renderDifferentValuesDifferentFieldsActions(group, fields);
            }
            return renderRuleActionsBlock(group, fields);
        }

        function getRuleWarnings(group, fields) {
            var warnings = [];
            group = normalizeRuleGroup(group);
            var mode = group.mode || 'different_values_different_fields';
            if (mode === 'conditions_same_fields') {
                var conditions = (group.conditions || []).filter(function(condition) { return condition && condition.fieldCode; });
                if (!conditions.length) { warnings.push(localize('REXP_FORM_EDITOR_JS_365')); }
                (group.conditions || []).forEach(function(condition, index) {
                    if (!condition || !condition.fieldCode) { return; }
                    var field = findFieldByCode(condition.fieldCode, fields);
                    if (!field) { warnings.push(localize('REXP_FORM_EDITOR_JS_366') + (index + 1) + localize('REXP_FORM_EDITOR_JS_367')); }
                    if (!operatorDoesNotNeedValue(condition.operator) && (condition.value === '' || condition.value === undefined || condition.value === null)) {
                        warnings.push(localize('REXP_FORM_EDITOR_JS_368') + (index + 1) + localize('REXP_FORM_EDITOR_JS_369'));
                    }
                });
            } else {
                if (!group.sourceFieldCode) {
                    warnings.push(localize('REXP_FORM_EDITOR_JS_370'));
                } else if (!findFieldByCode(group.sourceFieldCode, fields)) {
                    warnings.push(localize('REXP_FORM_EDITOR_JS_371'));
                }
                (group.rows || []).forEach(function(row, index) {
                    if (!operatorDoesNotNeedValue(row.operator) && (row.value === '' || row.value === undefined || row.value === null)) {
                        warnings.push(localize('REXP_FORM_EDITOR_JS_372') + (index + 1) + localize('REXP_FORM_EDITOR_JS_373'));
                    }
                    if (mode === 'different_values_different_fields' && !row.targetFieldCode) {
                        warnings.push(localize('REXP_FORM_EDITOR_JS_374') + (index + 1) + localize('REXP_FORM_EDITOR_JS_375'));
                    }
                });
            }
            if (mode !== 'different_values_different_fields') {
                if (!getGroupActions(group).filter(function(action) { return action.fieldCode; }).length) {
                    warnings.push(localize('REXP_FORM_EDITOR_JS_376'));
                }
            }
            return warnings;
        }

        function operatorDoesNotNeedValue(operator) {
            operator = normalizeOperator(operator || '=');
            return operator === 'filled' || operator === 'not_filled';
        }

        function renderRuleWarnings(warnings) {
            if (!warnings || !warnings.length) { return ''; }
            var items = warnings.map(function(warning) {
                return '<li>' + escapeHtml(warning) + '</li>';
            }).join('');
            return '<div class="rexp-form-editor__rule-warnings">' +
                '<div class="rexp-form-editor__rule-warning rexp-form-editor__rule-warning--combined">' +
                    '<span class="rexp-form-editor__rule-warning-icon">!</span>' +
                    '<div class="rexp-form-editor__rule-warning-text">' +
                        localize('REXP_FORM_EDITOR_JS_377') +
                        '<ul>' + items + '</ul>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        function getRuleHumanSummary(group, fields) {
            group = normalizeRuleGroup(group);
            var actions = getGroupActions(group).filter(function(action) { return action.fieldCode; });
            var actionText = actions.length ? actions.map(function(action) {
                return getOptionLabel(ACTIONS, action.type || 'show') + ' «' + (getFieldLabel(action.fieldCode, fields) || action.fieldCode) + '»';
            }).join(', ') : localize('REXP_FORM_EDITOR_JS_378');
            if (group.mode === 'conditions_same_fields') {
                var conditionText = (group.conditions || []).filter(function(condition) { return condition.fieldCode; }).map(function(condition) {
                    return '«' + (getFieldLabel(condition.fieldCode, fields) || condition.fieldCode) + '» ' + getOptionLabel(OPERATORS, normalizeOperator(condition.operator || '=')) + (operatorDoesNotNeedValue(condition.operator) ? '' : ' «' + getValueLabel(condition.value, findFieldByCode(condition.fieldCode, fields)) + '»');
                }).join(group.logic === 'or' ? localize('REXP_FORM_EDITOR_JS_379') : localize('REXP_FORM_EDITOR_JS_380'));
                return localize('REXP_FORM_EDITOR_JS_381') + (conditionText || localize('REXP_FORM_EDITOR_JS_382')) + localize('REXP_FORM_EDITOR_JS_383') + actionText + '.';
            }
            var sourceName = getFieldLabel(group.sourceFieldCode || '', fields) || localize('REXP_FORM_EDITOR_JS_384');
            if (group.mode === 'different_values_same_fields') {
                var values = (group.rows || []).map(function(row) { return getValueLabel(row.value, findFieldByCode(group.sourceFieldCode || '', fields)); }).filter(Boolean).join(group.logic === 'or' ? localize('REXP_FORM_EDITOR_JS_385') : localize('REXP_FORM_EDITOR_JS_386'));
                return localize('REXP_FORM_EDITOR_JS_387') + sourceName + localize('REXP_FORM_EDITOR_JS_388') + (values || localize('REXP_FORM_EDITOR_JS_389')) + localize('REXP_FORM_EDITOR_JS_390') + actionText + '.';
            }
            return localize('REXP_FORM_EDITOR_JS_391') + sourceName + localize('REXP_FORM_EDITOR_JS_392');
        }

        function getRuleModeTitle(mode) {
            for (var i = 0; i < RULE_GROUP_MODES.length; i++) {
                if (RULE_GROUP_MODES[i][0] === mode) { return RULE_GROUP_MODES[i][1]; }
            }
            return localize('REXP_FORM_EDITOR_JS_393');
        }

        function renderRuleLogicSelector(group) {
            return '<div class="rexp-form-editor__friendly-logic">' +
                localize('REXP_FORM_EDITOR_JS_394') +
                '<select data-field="ruleGroup.logic">' + renderOptions(RULE_LOGICS, group.logic || 'and') + '</select>' +
            '</div>';
        }

        function renderDifferentValuesDifferentFields(group, fields) {
            var sourceField = findFieldByCode(group.sourceFieldCode || '', fields);
            var rows = Array.isArray(group.rows) && group.rows.length ? group.rows : [createRuleValueRow()];
            group.rows = rows;
            return '<div class="rexp-form-editor__friendly-source-card">' +
                    localize('REXP_FORM_EDITOR_JS_395') +
                    renderFieldSelect('ruleGroup.sourceFieldCode', group.sourceFieldCode || '', fields, localize('REXP_FORM_EDITOR_JS_396')) +
                '</div>' +
                '<div class="rexp-form-editor__friendly-list">' +
                    rows.map(function(row, rowIndex) {
                        return '<div class="rexp-form-editor__friendly-row rexp-form-editor__rule-value-row" data-row-index="' + rowIndex + '">' +
                            '<div class="rexp-form-editor__friendly-row-number">' + (rowIndex + 1) + '</div>' +
                            '<div class="rexp-form-editor__friendly-row-body">' +
                                localize('REXP_FORM_EDITOR_JS_397') +
                                '<div class="rexp-form-editor__friendly-condition-line">' +
                                    renderOperatorSelect('ruleGroup.row.operator', normalizeOperator(row.operator || '='), sourceField) +
                                    renderRuleValueNative('ruleGroup.row.value', row.value || '', sourceField) +
                                '</div>' +
                            '</div>' +
                            (rows.length > 1 ? localize('REXP_FORM_EDITOR_JS_398') : '') +
                        '</div>';
                    }).join('') +
                '</div>' +
                localize('REXP_FORM_EDITOR_JS_399');
        }

        function renderDifferentValuesDifferentFieldsActions(group, fields) {
            var rows = Array.isArray(group.rows) && group.rows.length ? group.rows : [createRuleValueRow()];
            group.rows = rows;
            return '<div class="rexp-form-editor__friendly-list rexp-form-editor__friendly-list--actions">' +
                rows.map(function(row, rowIndex) {
                    row.actionType = normalizeActionType(row.actionType || 'show');
                    var targetField = findFieldByCode(row.targetFieldCode || '', fields);
                    return '<div class="rexp-form-editor__friendly-row rexp-form-editor__rule-value-row rexp-form-editor__friendly-action-map-row" data-row-index="' + rowIndex + '">' +
                        '<div class="rexp-form-editor__friendly-row-number">' + (rowIndex + 1) + '</div>' +
                        '<div class="rexp-form-editor__friendly-row-body">' +
                            localize('REXP_FORM_EDITOR_JS_400') + (rowIndex + 1) + '</div>' +
                            '<div class="rexp-form-editor__friendly-action-line">' +
                                renderActionSelect('ruleGroup.row.type', row.actionType || 'show') +
                                renderFieldSelect('ruleGroup.row.fieldCode', row.targetFieldCode || '', fields, localize('REXP_FORM_EDITOR_JS_401')) +
                            '</div>' +
                            (row.actionType === 'set_value' ? '<div class="rexp-form-editor__friendly-set-value">' + renderRuleSetValueNative('ruleGroup.row.valueToSet', row.valueToSet || '', targetField) + '</div>' : '') +
                        '</div>' +
                    '</div>';
                }).join('') +
            '</div>' +
            localize('REXP_FORM_EDITOR_JS_402');
        }

        function renderDifferentValuesSameFields(group, fields) {
            var sourceField = findFieldByCode(group.sourceFieldCode || '', fields);
            var rows = Array.isArray(group.rows) && group.rows.length ? group.rows : [createRuleValueRow()];
            group.rows = rows;
            return '<div class="rexp-form-editor__friendly-source-card">' +
                    localize('REXP_FORM_EDITOR_JS_403') +
                    renderFieldSelect('ruleGroup.sourceFieldCode', group.sourceFieldCode || '', fields, localize('REXP_FORM_EDITOR_JS_404')) +
                '</div>' +
                renderRuleLogicSelector(group) +
                '<div class="rexp-form-editor__friendly-list">' +
                    rows.map(function(row, rowIndex) {
                        return '<div class="rexp-form-editor__friendly-row rexp-form-editor__rule-value-row" data-row-index="' + rowIndex + '">' +
                            '<div class="rexp-form-editor__friendly-row-number">' + (rowIndex + 1) + '</div>' +
                            '<div class="rexp-form-editor__friendly-row-body">' +
                                localize('REXP_FORM_EDITOR_JS_405') +
                                '<div class="rexp-form-editor__friendly-condition-line">' +
                                    renderOperatorSelect('ruleGroup.row.operator', normalizeOperator(row.operator || '='), sourceField) +
                                    renderRuleValueNative('ruleGroup.row.value', row.value || '', sourceField) +
                                '</div>' +
                            '</div>' +
                            (rows.length > 1 ? localize('REXP_FORM_EDITOR_JS_406') : '') +
                        '</div>';
                    }).join('') +
                '</div>' +
                localize('REXP_FORM_EDITOR_JS_407');
        }

        function renderConditionsSameFields(group, fields) {
            var conditions = Array.isArray(group.conditions) && group.conditions.length ? group.conditions : [createRuleCondition()];
            group.conditions = conditions;
            return renderRuleLogicSelector(group) +
                '<div class="rexp-form-editor__friendly-list">' +
                    conditions.map(function(condition, conditionIndex) {
                        var conditionField = findFieldByCode(condition.fieldCode || '', fields);
                        return '<div class="rexp-form-editor__friendly-row rexp-form-editor__rule-condition-row" data-condition-index="' + conditionIndex + '">' +
                            '<div class="rexp-form-editor__friendly-row-number">' + (conditionIndex + 1) + '</div>' +
                            '<div class="rexp-form-editor__friendly-row-body">' +
                                localize('REXP_FORM_EDITOR_JS_408') +
                                '<div class="rexp-form-editor__friendly-condition-line rexp-form-editor__friendly-condition-line--full">' +
                                    renderFieldSelect('ruleGroup.condition.fieldCode', condition.fieldCode || '', fields, localize('REXP_FORM_EDITOR_JS_409')) +
                                    renderOperatorSelect('ruleGroup.condition.operator', normalizeOperator(condition.operator || '='), conditionField) +
                                    renderRuleValueNative('ruleGroup.condition.value', condition.value || '', conditionField) +
                                '</div>' +
                            '</div>' +
                            (conditions.length > 1 ? localize('REXP_FORM_EDITOR_JS_410') : '') +
                        '</div>';
                    }).join('') +
                '</div>' +
                localize('REXP_FORM_EDITOR_JS_411');
        }

        function renderRuleActionsBlock(group, fields) {
            var actions = getGroupActions(group);
            if (!actions.length) { actions = [createRuleAction()]; }
            group.actions = actions;
            return '<div class="rexp-form-editor__friendly-list rexp-form-editor__friendly-list--actions">' +
                actions.map(function(action, actionIndex) {
                    return '<div class="rexp-form-editor__friendly-row rexp-form-editor__rule-action-row" data-action-index="' + actionIndex + '">' +
                        '<div class="rexp-form-editor__friendly-row-number">' + (actionIndex + 1) + '</div>' +
                        '<div class="rexp-form-editor__friendly-row-body">' +
                            renderRuleActionRow(action, fields, 'ruleGroup.action', actions.length > 1, localize('REXP_FORM_EDITOR_JS_412') + (actionIndex + 1)) +
                        '</div>' +
                        (actions.length > 1 ? localize('REXP_FORM_EDITOR_JS_413') : '') +
                    '</div>';
                }).join('') +
            '</div>' +
            localize('REXP_FORM_EDITOR_JS_414');
        }

        function renderRuleActionRow(action, fields, keyPrefix, removable, title) {
            action = normalizeRuleAction(action || {});
            var targetField = findFieldByCode(action.fieldCode || action.targetFieldCode || '', fields);
            var fieldCode = action.fieldCode || action.targetFieldCode || '';
            var actionType = normalizeActionType(action.type || action.actionType || 'show');
            var needsValue = actionType === 'set_value';
            var valueKey = keyPrefix === 'ruleGroup.row' ? keyPrefix + '.valueToSet' : keyPrefix + '.value';
            return '<div class="rexp-form-editor__friendly-action-line">' +
                    renderActionSelect(keyPrefix + '.type', actionType) +
                    renderFieldSelect(keyPrefix + '.fieldCode', fieldCode, fields, localize('REXP_FORM_EDITOR_JS_415')) +
                '</div>' +
                (needsValue ? '<div class="rexp-form-editor__friendly-set-value">' + renderRuleSetValueNative(valueKey, action.value || action.valueToSet || '', targetField) + '</div>' : '');
        }

        function renderRuleSetValueNative(key, value, field) {
            return localize('REXP_FORM_EDITOR_JS_416') + renderRuleValueNative(key, value, field) + '</label>';
        }

        function renderFieldSelect(key, value, fields, placeholder) {
            return '<select class="rexp-form-editor__friendly-select" data-field="' + escapeHtml(key) + '">' + fieldOptions(fields, value || '').replace(localize('REXP_FORM_EDITOR_JS_417'), '>' + escapeHtml(placeholder || localize('REXP_FORM_EDITOR_JS_418')) + '<') + '</select>';
        }

        function getOperatorOptionsForField(field, selectedOperator) {
            field = field || {};
            var type = String(field.type || '');
            var view = getFieldView(field);
            var allowed;

            if (!type) {
                allowed = ['=', '!=', 'filled', 'not_filled'];
            } else if (type === 'integer' || type === 'double' || type === 'money' || type === 'date' || type === 'datetime') {
                allowed = ['=', '!=', '>', '>=', '<', '<=', 'filled', 'not_filled'];
            } else if (type === 'boolean' || type === 'checkbox' || type === 'switch') {
                allowed = ['=', '!=', 'filled', 'not_filled'];
            } else if (type === 'enumeration' || type === 'select' || type === 'radio' || type === 'checkbox_list') {
                allowed = (field.multiple || view === 'checkbox')
                    ? ['in', 'not_in', '=', '!=', 'filled', 'not_filled']
                    : ['=', '!=', 'filled', 'not_filled'];
            } else if (type === 'file' || type === 'user' || type === 'department' || type === 'entity_selector') {
                allowed = ['filled', 'not_filled'];
            } else {
                allowed = ['=', '!=', 'contains', 'not_contains', 'filled', 'not_filled'];
            }

            selectedOperator = normalizeOperator(selectedOperator || '=');
            if (allowed.indexOf(selectedOperator) < 0 && getOptionLabel(OPERATORS, selectedOperator)) {
                allowed.push(selectedOperator);
            }

            return OPERATORS.filter(function(item) {
                return allowed.indexOf(item[0]) >= 0;
            });
        }

        function renderOperatorSelect(key, operatorValue, field) {
            operatorValue = normalizeOperator(operatorValue || '=');
            var operatorOptions = getOperatorOptionsForField(field, operatorValue);
            return '<select class="rexp-form-editor__friendly-select rexp-form-editor__friendly-select--operator" data-field="' + escapeHtml(key) + '">' + renderOptions(operatorOptions, operatorValue) + '</select>';
        }

        function renderActionSelect(key, value) {
            value = normalizeActionType(value || 'show');
            return '<select class="rexp-form-editor__friendly-select rexp-form-editor__friendly-select--action" data-field="' + escapeHtml(key) + '">' + renderOptions(ACTIONS, value) + '</select>';
        }

        function renderRuleValueNative(key, value, field) {
            var items = getFieldValueItems(field);
            if (items.length) {
                return '<select class="rexp-form-editor__friendly-select rexp-form-editor__friendly-select--value" data-field="' + escapeHtml(key) + '">' +
                    localize('REXP_FORM_EDITOR_JS_419') +
                    items.map(function(item) { return '<option value="' + escapeHtml(item.value) + '"' + (String(value) === String(item.value) ? ' selected' : '') + '>' + escapeHtml(item.label) + '</option>'; }).join('') +
                '</select>';
            }
            return '<input class="rexp-form-editor__friendly-input" data-field="' + escapeHtml(key) + '" value="' + escapeHtml(value || '') + localize('REXP_FORM_EDITOR_JS_420');
        }

        function getGroupActions(group) {
            var actions = normalizeRuleActions(group.actions);
            if (actions.length) {
                return actions;
            }
            var selected = Array.isArray(group.targetFieldCodes) ? group.targetFieldCodes : [];
            return selected.map(function(fieldCode) {
                return {uid: createUid('action'), type: normalizeActionType(group.actionType || 'show'), fieldCode: fieldCode, value: group.actionValue || ''};
            });
        }

        function resolveActionsForCompilation(group) {
            var actions = getGroupActions(group);
            if (!actions.length) {
                return [];
            }
            return actions.map(function(action) {
                action = normalizeRuleAction(action);
                return {type: action.type, fieldCode: action.fieldCode, value: action.value || '', clearOnHide: !!action.clearOnHide};
            });
        }

        function getFieldTypeOptions() {
            var definitions = state.metadata && state.metadata.fieldTypes ? state.metadata.fieldTypes : {};
            var keys = Object.keys(definitions);
            var hiddenTypes = ['entity_selector', 'userfield'];
            if (!keys.length) {
                return FIELD_TYPES.filter(function(item) { return hiddenTypes.indexOf(item[0]) < 0; });
            }
            return keys.filter(function(code) { return hiddenTypes.indexOf(code) < 0; }).map(function(code) {
                return [code, definitions[code].title || code];
            });
        }

        function getFieldTypeTitle(type) {
            var definitions = state.metadata && state.metadata.fieldTypes ? state.metadata.fieldTypes : {};
            if (definitions[type] && definitions[type].title) {
                return definitions[type].title;
            }
            return getOptionLabel(FIELD_TYPES, type) || type;
        }

        function getViewOptions(type) {
            var definitions = state.metadata && state.metadata.fieldTypes ? state.metadata.fieldTypes : {};
            var supported = definitions[type] && Array.isArray(definitions[type].supportedViews) ? definitions[type].supportedViews : [];
            if (!supported.length) {
                if (type === 'enumeration') { supported = ['select', 'radio', 'checkbox']; }
                else if (type === 'boolean') { supported = ['checkbox', 'switch']; }
                else { supported = [getDefaultView(type)]; }
            }
            if (type === 'enumeration') {
                supported = supported.filter(function(view) { return ['select', 'radio', 'checkbox'].indexOf(view) >= 0; });
                if (!supported.length) { supported = ['select', 'radio', 'checkbox']; }
            }
            return supported.map(function(view) { return [view, getViewTitle(view)]; });
        }

        function getViewTitle(view) {
            var titles = {
                input: localize('REXP_FORM_EDITOR_JS_421'),
                textarea: localize('REXP_FORM_EDITOR_JS_422'),
                select: localize('REXP_FORM_EDITOR_JS_423'),
                radio: localize('REXP_FORM_EDITOR_JS_424'),
                checkbox: localize('REXP_FORM_EDITOR_JS_425'),
                switch: localize('REXP_FORM_EDITOR_JS_426'),
                entity_selector: localize('REXP_FORM_EDITOR_JS_427'),
                date: localize('REXP_FORM_EDITOR_JS_428'),
                datetime: localize('REXP_FORM_EDITOR_JS_429'),
                file: localize('REXP_FORM_EDITOR_JS_430'),
                money: localize('REXP_FORM_EDITOR_JS_431')
            };
            return titles[view] || view || '';
        }

        function getDefaultView(type) {
            if (type === 'text') { return 'textarea'; }
            if (type === 'boolean') { return 'checkbox'; }
            if (type === 'enumeration') { return 'select'; }
            if (type === 'date') { return 'date'; }
            if (type === 'datetime') { return 'datetime'; }
            if (type === 'file') { return 'file'; }
            if (type === 'money') { return 'money'; }
            if (type === 'user' || type === 'entity_selector') { return 'entity_selector'; }
            if (type === 'department') { return 'department_select'; }
            return 'input';
        }

        function fieldUsesPlaceholder(type, view) {
            type = String(type || 'string');
            view = String(view || getDefaultView(type));
            if (type === 'boolean' || type === 'checkbox' || type === 'switch' || type === 'file') { return false; }
            if (type === 'date' || type === 'datetime') { return false; }
            if ((type === 'enumeration' || type === 'radio' || type === 'checkbox_list' || type === 'select') && (view === 'radio' || view === 'checkbox')) { return false; }
            return true;
        }

        function getDefaultPlaceholder(type, view) {
            if (!fieldUsesPlaceholder(type, view)) { return ''; }
            var definitions = state.metadata && state.metadata.fieldTypes ? state.metadata.fieldTypes : {};
            if (definitions[type] && definitions[type].placeholder) {
                return definitions[type].placeholder;
            }
            if (type === 'user') { return localize('REXP_FORM_EDITOR_JS_432'); }
            if (type === 'department') { return localize('REXP_FORM_EDITOR_JS_433'); }
            if (type === 'enumeration') { return localize('REXP_FORM_EDITOR_JS_434'); }
            if (type === 'date') { return localize('REXP_FORM_EDITOR_JS_435'); }
            if (type === 'datetime') { return localize('REXP_FORM_EDITOR_JS_436'); }
            if (type === 'file') { return localize('REXP_FORM_EDITOR_JS_437'); }
            if (type === 'text') { return localize('REXP_FORM_EDITOR_JS_438'); }
            if (type === 'email') { return localize('REXP_FORM_EDITOR_JS_439'); }
            if (type === 'phone') { return localize('REXP_FORM_EDITOR_JS_440'); }
            if (type === 'url') { return localize('REXP_FORM_EDITOR_JS_441'); }
            if (type === 'integer' || type === 'double' || type === 'money') { return localize('REXP_FORM_EDITOR_JS_442'); }
            return localize('REXP_FORM_EDITOR_JS_443');
        }

        function typeSupportsMultiple(type, view) {
            if (type === 'enumeration' || type === 'file' || type === 'user' || type === 'department' || type === 'entity_selector') { return true; }
            return view === 'checkbox';
        }

        function getOptionLabel(options, value) {
            for (var i = 0; i < options.length; i++) {
                if (String(options[i][0]) === String(value)) { return options[i][1]; }
            }
            return value || '';
        }

        function getValueLabel(value, field) {
            if (!value) { return localize('REXP_FORM_EDITOR_JS_444'); }
            var items = getFieldValueItems(field);
            for (var i = 0; i < items.length; i++) {
                if (String(items[i].value) === String(value)) { return items[i].label; }
            }
            return value;
        }

        function getFieldLabel(code, fields) {
            var field = findFieldByCode(code, fields);
            return field ? (field.label || field.code || '') : '';
        }

        function getFirstTargetLabel(codes, fields) {
            if (!Array.isArray(codes) || !codes.length) { return ''; }
            var labels = codes.map(function(code) { return getFieldLabel(code, fields); }).filter(Boolean);
            return labels.join(', ');
        }

        function normalizeOperator(operator) {
            var aliases = {
                equals: '=',
                equal: '=',
                eq: '=',
                '==': '=',
                not_equals: '!=',
                not_equal: '!=',
                notEqual: '!=',
                neq: '!=',
                '<>': '!=',
                empty: 'not_filled',
                not_empty: 'filled',
                contain: 'contains',
                not_contain: 'not_contains'
            };
            operator = String(operator || '=').toLowerCase();
            return aliases[operator] || operator;
        }

        function normalizeActionType(actionType) {
            var allowed = ACTIONS.map(function(item) { return item[0]; });
            actionType = actionType || 'show';
            return allowed.indexOf(actionType) >= 0 ? actionType : 'show';
        }

        function getFieldView(field) {
            field = field || {};
            if (field.ui && field.ui.view) { return field.ui.view; }
            if (field.type === 'switch') { return 'switch'; }
            if (field.type === 'checkbox') { return 'checkbox'; }
            if (field.type === 'select') { return 'select'; }
            if (field.type === 'radio') { return 'radio'; }
            if (field.type === 'checkbox_list') { return 'checkbox'; }
            if (field.type === 'user' || field.type === 'department' || field.type === 'entity_selector') { return 'entity_selector'; }
            return '';
        }

        function setFieldView(field, view) {
            field.ui = field.ui && typeof field.ui === 'object' ? field.ui : {};
            field.ui.view = view || '';
        }

        function createRuleValueRow() {
            return {uid: createUid('value'), operator: '=', value: '', actionType: 'show', targetFieldCode: '', valueToSet: ''};
        }

        function createRuleCondition() {
            return {uid: createUid('condition'), fieldCode: '', operator: '=', value: ''};
        }

        function createRuleAction() {
            return {uid: createUid('action'), type: 'show', fieldCode: '', value: '', clearOnHide: false};
        }

        function normalizeRuleAction(action) {
            action = action && typeof action === 'object' ? action : createRuleAction();
            return {
                uid: action.uid || createUid('action'),
                type: normalizeActionType(action.type || action.actionType || 'show'),
                fieldCode: action.fieldCode || action.targetFieldCode || '',
                value: action.value !== undefined ? action.value : (action.valueToSet !== undefined ? action.valueToSet : ''),
                clearOnHide: !!action.clearOnHide
            };
        }

        function normalizeRuleActions(actions) {
            if (!Array.isArray(actions)) {
                return [];
            }

            return actions.map(normalizeRuleAction).filter(function(action) {
                return !!(action.type || action.fieldCode || action.value);
            });
        }

        function normalizeRuleGroup(group) {
            group = group && typeof group === 'object' ? group : {};
            group.uid = group.uid || createUid('rule_group');
            group.enabled = group.enabled !== false;
            group.mode = group.mode || 'different_values_different_fields';
            group.logic = String(group.logic || 'and').toLowerCase() === 'or' ? 'or' : 'and';
            group.sourceFieldCode = group.sourceFieldCode || group.sourceField || '';
            group.rows = Array.isArray(group.rows) ? group.rows : [];
            group.rows = group.rows.map(function(row) {
                row = row && typeof row === 'object' ? row : createRuleValueRow();
                row.uid = row.uid || createUid('value');
                row.operator = normalizeOperator(row.operator || '=');
                row.value = row.value !== undefined ? row.value : '';
                row.actionType = normalizeActionType(row.actionType || row.type || group.actionType || 'show');
                row.targetFieldCode = row.targetFieldCode || row.fieldCode || '';
                row.valueToSet = row.valueToSet || row.setValue || '';
                return row;
            });
            group.conditions = Array.isArray(group.conditions) ? group.conditions : [];
            group.conditions = group.conditions.map(function(condition) {
                condition = condition && typeof condition === 'object' ? condition : createRuleCondition();
                condition.uid = condition.uid || createUid('condition');
                condition.fieldCode = condition.fieldCode || condition.field || '';
                condition.operator = normalizeOperator(condition.operator || '=');
                condition.value = condition.value !== undefined ? condition.value : '';
                return condition;
            });
            group.actionType = normalizeActionType(group.actionType || 'show');
            group.targetFieldCodes = Array.isArray(group.targetFieldCodes) ? group.targetFieldCodes : [];
            group.actions = Array.isArray(group.actions) ? group.actions.map(normalizeRuleAction) : [];
            return group;
        }

        function buildRuleGroupsFromRules(rules) {
            if (!Array.isArray(rules) || !rules.length) { return []; }
            return rules.map(function(rule) {
                var actions = Array.isArray(rule.actions) ? rule.actions.map(normalizeRuleAction) : [];
                var action = actions[0] || {type: 'show', fieldCode: ''};
                if (Array.isArray(rule.conditions) && rule.conditions.length) {
                    return {
                        uid: rule.uid || createUid('rule_group'),
                        enabled: rule.enabled !== false,
                        mode: 'conditions_same_fields',
                        logic: String(rule.logic || 'and').toLowerCase() === 'or' ? 'or' : 'and',
                        conditions: rule.conditions.map(function(condition) { return {uid: createUid('condition'), fieldCode: condition.fieldCode || '', operator: normalizeOperator(condition.operator || '='), value: condition.value !== undefined ? condition.value : '', values: Array.isArray(condition.values) ? condition.values : []}; }),
                        actionType: normalizeActionType(action.type || 'show'),
                        targetFieldCodes: actions.map(function(item) { return item.fieldCode || ''; }).filter(Boolean),
                        actions: actions
                    };
                }
                return {
                    uid: rule.uid || createUid('rule_group'),
                    enabled: rule.enabled !== false,
                    mode: 'different_values_different_fields',
                    logic: String(rule.logic || 'and').toLowerCase() === 'or' ? 'or' : 'and',
                    sourceFieldCode: rule.when && rule.when.fieldCode ? rule.when.fieldCode : '',
                    rows: [{uid: createUid('value'), operator: rule.when && rule.when.operator ? normalizeOperator(rule.when.operator) : '=', value: rule.when && rule.when.value !== undefined ? rule.when.value : '', actionType: normalizeActionType(action.type || 'show'), targetFieldCode: action.fieldCode || '', valueToSet: action.value || ''}]
                };
            });
        }

        function findFieldByCode(code, fields) {
            code = String(code || '');
            for (var i = 0; i < fields.length; i++) {
                if (String(fields[i].code || '') === code) { return fields[i]; }
            }
            return null;
        }

function getFieldValueItems(field) {
            if (!field) { return []; }
            var type = String(field.type || '');
            if (type === 'boolean' || type === 'checkbox' || type === 'switch') {
                return [{value: '1', label: localize('REXP_FORM_EDITOR_JS_447')}, {value: '0', label: localize('REXP_FORM_EDITOR_JS_448')}];
            }
            if (type === 'enumeration' || type === 'select' || type === 'radio' || type === 'checkbox_list') {
                return (Array.isArray(field.items) ? field.items : []).map(function(item) {
                    var value = item.value || item.xmlId || item.id || item.label || item.title || '';
                    return {value: value, label: item.label || item.title || item.value || value};
                }).filter(function(item) { return item.value !== ''; });
            }
            return [];
        }

        function renderPreview(fields, sections, settings) {
            if (!fields.length) { return localize('REXP_FORM_EDITOR_JS_449'); }
            var previewSections = sections.length ? sections : [{uid: '', title: '', description: ''}];
            return previewSections.map(function(section) {
                var sectionUid = String(section.uid || '');
                var sectionFields = fields.filter(function(field) { return String(field.sectionUid || '') === sectionUid; });
                if (!sectionFields.length) { return ''; }
                return '<div class="rexp-form-editor__preview-section">' + (section.title ? '<h3>' + escapeHtml(section.title) + '</h3>' : '') + (section.description ? '<div class="rexp-form-editor__hint">' + escapeHtml(section.description) + '</div>' : '') + sectionFields.map(function(field) {
                    return '<label><span>' + escapeHtml(field.label || field.code || localize('REXP_FORM_EDITOR_JS_450')) + (field.required ? ' *' : '') + '</span>' + renderPreviewControl(field) + ((field.hint || (field.ui && field.ui.hint)) ? '<em class="rexp-form-editor__preview-hint">' + escapeHtml(field.hint || field.ui.hint) + '</em>' : '') + '</label>';
                }).join('') + '</div>';
            }).join('') + '<div style="margin-top:12px"><button class="ui-btn ui-btn-primary" disabled>' + escapeHtml(settings.submitText || localize('REXP_FORM_EDITOR_JS_451')) + '</button></div>';
        }

        function renderPreviewControl(field) {
            field = field || {};
            var type = field.type || 'string';
            var view = getFieldView(field) || getDefaultView(type);
            var placeholder = field.placeholder || (field.ui && field.ui.placeholder) || getDefaultPlaceholder(type, view);
            var items = getFieldValueItems(field);
            if (type === 'text' || view === 'textarea') { return '<textarea disabled placeholder="' + escapeHtml(placeholder || '') + '"></textarea>'; }
            if (type === 'boolean') { return view === 'switch' ? '<span class="rexp-form-editor__switch-preview"><span></span></span>' : localize('REXP_FORM_EDITOR_JS_452'); }
            if (type === 'enumeration') {
                if (view === 'radio') {
                    return '<div class="rexp-form-editor__preview-choice-list">' + (items.length ? items : [{label:localize('REXP_FORM_EDITOR_JS_453'),value:'1'},{label:localize('REXP_FORM_EDITOR_JS_454'),value:'2'}]).map(function(item){ return '<label><input type="radio" disabled><span>' + escapeHtml(item.label || item.value || localize('REXP_FORM_EDITOR_JS_455')) + '</span></label>'; }).join('') + '</div>';
                }
                if (view === 'checkbox') {
                    return '<div class="rexp-form-editor__preview-choice-list">' + (items.length ? items : [{label:localize('REXP_FORM_EDITOR_JS_456'),value:'1'},{label:localize('REXP_FORM_EDITOR_JS_457'),value:'2'}]).map(function(item){ return '<label><input type="checkbox" disabled><span>' + escapeHtml(item.label || item.value || localize('REXP_FORM_EDITOR_JS_458')) + '</span></label>'; }).join('') + '</div>';
                }
                return '<select disabled' + (field.multiple ? ' multiple' : '') + '><option>' + escapeHtml(placeholder || localize('REXP_FORM_EDITOR_JS_459')) + '</option>' + items.slice(0, 4).map(function(item){ return '<option>' + escapeHtml(item.label || item.value) + '</option>'; }).join('') + '</select>';
            }
            if (type === 'user' || type === 'entity_selector') { return localize('REXP_FORM_EDITOR_JS_460'); }
            if (type === 'department') { return localize('REXP_FORM_EDITOR_JS_461'); }
            if (type === 'file') { return localize('REXP_FORM_EDITOR_JS_462'); }
            if (type === 'integer' || type === 'double') { return '<input type="number" disabled placeholder="' + escapeHtml(placeholder || localize('REXP_FORM_EDITOR_JS_463')) + '">'; }
            if (type === 'money') { return '<div class="rexp-form-editor__money-preview"><input type="number" disabled placeholder="0.00"><span>RUB</span></div>'; }
            if (type === 'date') { return '<input type="date" disabled>'; }
            if (type === 'datetime') { return '<input type="datetime-local" disabled>'; }
            if (type === 'email') { return '<input type="email" disabled placeholder="' + escapeHtml(placeholder || 'email@example.ru') + '">'; }
            if (type === 'phone') { return '<input type="tel" disabled placeholder="' + escapeHtml(placeholder || '+7 000 000-00-00') + '">'; }
            if (type === 'url') { return '<input type="url" disabled placeholder="' + escapeHtml(placeholder || 'https://') + '">'; }
            return '<input disabled placeholder="' + escapeHtml(placeholder || localize('REXP_FORM_EDITOR_JS_464')) + '">';
        }
        function renderDiagnostics() {
            if (!state.diagnostics) { return ''; }
            var data = state.diagnostics || {};
            var errors = Array.isArray(data.errors) ? data.errors : [];
            var warnings = Array.isArray(data.warnings) ? data.warnings : [];
            var usedTypes = data.fieldTypes && data.fieldTypes.used ? data.fieldTypes.used : {};
            var rules = data.rules || {};
            var ok = data.ok !== false && !errors.length;
            var typeItems = Object.keys(usedTypes).length
                ? Object.keys(usedTypes).map(function(type) { return '<span class="rexp-form-editor__diagnostics-chip">' + escapeHtml(getFieldTypeTitle(type)) + ': ' + escapeHtml(usedTypes[type]) + '</span>'; }).join('')
                : localize('REXP_FORM_EDITOR_JS_465');
            return '<div class="rexp-form-editor__diagnostics-visual">' +
                '<div class="rexp-form-editor__diagnostics-status ' + (ok ? 'is-ok' : 'is-error') + '">' +
                    '<div class="rexp-form-editor__diagnostics-status-icon">' + (ok ? '✓' : '!') + '</div>' +
                    '<div><b>' + (ok ? localize('REXP_FORM_EDITOR_JS_466') : localize('REXP_FORM_EDITOR_JS_467')) + '</b><span>' + (ok ? localize('REXP_FORM_EDITOR_JS_468') : localize('REXP_FORM_EDITOR_JS_469')) + '</span></div>' +
                '</div>' +
                '<div class="rexp-form-editor__diagnostics-stats">' +
                    '<div><b>' + errors.length + localize('REXP_FORM_EDITOR_JS_470') +
                    '<div><b>' + warnings.length + localize('REXP_FORM_EDITOR_JS_471') +
                    '<div><b>' + escapeHtml(rules.groups || 0) + localize('REXP_FORM_EDITOR_JS_472') +
                    '<div><b>' + escapeHtml(rules.compiled || 0) + localize('REXP_FORM_EDITOR_JS_473') +
                '</div>' +
                renderDiagnosticsList(localize('REXP_FORM_EDITOR_JS_474'), errors, 'error') +
                renderDiagnosticsList(localize('REXP_FORM_EDITOR_JS_475'), warnings, 'warning') +
                localize('REXP_FORM_EDITOR_JS_476') + typeItems + '</div></div>' +
                localize('REXP_FORM_EDITOR_JS_477') + escapeHtml(JSON.stringify(data, null, 2)) + '</pre></details>' +
            '</div>';
        }

        function renderDiagnosticsList(title, items, type) {
            if (!items || !items.length) {
                return '<div class="rexp-form-editor__diagnostics-section is-empty"><div class="rexp-form-editor__diagnostics-section-title">' + escapeHtml(title) + localize('REXP_FORM_EDITOR_JS_478');
            }
            return '<div class="rexp-form-editor__diagnostics-section is-' + escapeHtml(type || '') + '">' +
                '<div class="rexp-form-editor__diagnostics-section-title">' + escapeHtml(title) + '</div>' +
                '<ul>' + items.map(function(item) { return '<li>' + escapeHtml(item) + '</li>'; }).join('') + '</ul>' +
            '</div>';
        }

        function setValue(key, value, context, targetNode) {
            var schema = state.form.schema;
            schema.settings = schema.settings || {};
            schema.states = schema.states || {success: {}, error: {}};
            if (key === 'name') { state.form.name = value; return; }
            if (key === 'code') { state.form.code = value; return; }
            if (key === 'active') { state.form.active = toBool(value); return; }
            if (key === 'archived') { state.form.archived = toBool(value); return; }
            if (key === 'submitText' || key === 'successText' || key === 'errorText') {
                schema.settings[key] = value;
                if (key === 'successText') { schema.states.success.text = value; schema.layout = ensureLayout(schema.layout); schema.layout.postSubmit.message = value; }
                if (key === 'errorText') { schema.states.error.text = value; }
                return;
            }
            if (key === 'designer.userFieldEntityId') { state.userFieldEntityId = value; return; }
            if (key === 'formExchangeText') { state.formExchangeText = value; return; }
            if (key.indexOf('layout.') === 0) {
                schema.layout = ensureLayout(schema.layout);
                var layoutPath = key.slice(7);
                if (/\.enabled$/.test(layoutPath) || layoutPath === 'postSubmit.showAgainButton' || layoutPath === 'responsive.enabled' || layoutPath === 'responsive.fullWidth' || layoutPath === 'responsive.compact' || layoutPath === 'icons.enabled' || layoutPath === 'icons.formEnabled' || layoutPath === 'icons.noteEnabled' || layoutPath === 'icons.successEnabled') { value = !!value; }
                setNestedValue(schema.layout, layoutPath, value);
                if (layoutPath.indexOf('sidebarNotes.') === 0) {
                    schema.layout.sidebarNotes = normalizeSidebarNotes(schema.layout.sidebarNotes, schema.layout.sidebarNote || {});
                    schema.layout.sidebarNote = schema.layout.sidebarNotes[0] || Object.assign({}, defaultLayout().sidebarNote);
                }
                if (layoutPath === 'postSubmit.message') { schema.states.success.text = value; schema.settings.successText = value; }
                return;
            }
            if (key.indexOf('state.success.') === 0) { schema.states.success[key.slice(14)] = value; if (key === 'state.success.text') { schema.settings.successText = value; } return; }
            if (key.indexOf('state.error.') === 0) { schema.states.error[key.slice(12)] = value; if (key === 'state.error.text') { schema.settings.errorText = value; } return; }
            if (key.indexOf('section.') === 0 && context) {
                var sidx = Number(context.getAttribute('data-section-index'));
                var section = schema.sections[sidx];
                if (!section) { return; }
                section[key.slice(8)] = value;
                return;
            }
            if (key.indexOf('field.') === 0 && context) {
                var idx = Number(context.getAttribute('data-field-index'));
                var field = schema.fields[idx];
                var prop = key.slice(6);
                if (!field) { return; }
                if (prop === 'label' || prop === 'title') { field.label = value; field.title = value; return; }
                if (prop === 'required' || prop === 'mandatory' || prop === 'multiple') { field[prop] = !!value; if (prop === 'required') { field.mandatory = !!value; } return; }
                if (prop === 'type') {
                    field.type = value || 'string';
                    field.ui = field.ui && typeof field.ui === 'object' ? field.ui : {};
                    field.ui.view = getDefaultView(field.type);
                    field.placeholder = fieldUsesPlaceholder(field.type, field.ui.view) ? getDefaultPlaceholder(field.type, field.ui.view) : '';
                    field.ui.placeholder = field.placeholder;
                    if (field.type === 'user') {
                        field.source = {provider: 'ui.entity-selector', context: 'REXP_FORM', entities: ['user']};
                        field.selectorEntities = ['user'];
                        field.relationType = 'user';
                    } else if (field.type === 'department') {
                        field.source = {provider: 'iblock.department', context: 'REXP_FORM', entities: ['department']};
                        field.selectorEntities = ['department'];
                        field.relationType = 'department';
                    } else if (field.type !== 'enumeration') {
                        delete field.source;
                        delete field.selectorEntities;
                        delete field.relationType;
                    }
                    return;
                }
                if (prop.indexOf('ui.') === 0) {
                    field.ui = field.ui && typeof field.ui === 'object' ? field.ui : {};
                    field.ui[prop.slice(3)] = value;
                    if (prop === 'ui.view') {
                        field.placeholder = fieldUsesPlaceholder(field.type || 'string', value) ? getDefaultPlaceholder(field.type || 'string', value) : '';
                        field.ui.placeholder = field.placeholder;
                    }
                    return;
                }
                if (prop.indexOf('source.') === 0) {
                    field.source = field.source && typeof field.source === 'object' ? field.source : {};
                    var sourceProp = prop.slice(7);
                    if (sourceProp === 'entities') {
                        field.source.entities = String(value || '').split(',').map(function(item) { return item.trim(); }).filter(Boolean);
                    } else {
                        field.source[sourceProp] = value;
                    }
                    return;
                }
                if (prop === 'placeholder') { field.placeholder = value; field.ui = field.ui && typeof field.ui === 'object' ? field.ui : {}; field.ui.placeholder = value; return; }
                if (prop === 'hint') { field.hint = value; field.ui = field.ui && typeof field.ui === 'object' ? field.ui : {}; field.ui.hint = value; return; }
                if (prop === 'items') {
                    field.items = String(value || '').split('\n').map(function(line) { return line.trim(); }).filter(Boolean).map(function(line, i) { return {id: line, value: line, label: line, sort: (i + 1) * 100}; });
                    return;
                }
                if (prop === 'settingsJson') {
                    try { field.settings = JSON.parse(value || '{}') || {}; } catch (error) { notify(localize('REXP_FORM_EDITOR_JS_479'), 'error'); }
                    return;
                }
                field[prop] = value;
                return;
            }
            if (key.indexOf('ruleGroup.') === 0 && context) {
                var rgidx = Number(context.getAttribute('data-rule-index'));
                var group = normalizeRuleGroup(schema.ruleGroups[rgidx]);
                schema.ruleGroups[rgidx] = group;
                var prop = key.slice(10);

                if (prop === 'mode') {
                    group.mode = value || 'different_values_different_fields';
                    if (group.mode === 'different_values_different_fields' || group.mode === 'different_values_same_fields') {
                        group.rows = group.rows.length ? group.rows : [createRuleValueRow()];
                    }
                    if (group.mode === 'conditions_same_fields') {
                        group.conditions = group.conditions.length ? group.conditions : [createRuleCondition()];
                    }
                    return;
                }
                if (prop === 'enabled') { group.enabled = !!(targetNode && targetNode.checked); return; }
                if (prop === 'logic') { group.logic = String(value || 'and').toLowerCase() === 'or' ? 'or' : 'and'; return; }
                if (prop === 'sourceFieldCode') { group.sourceFieldCode = value; return; }
                if (prop === 'actionType') { group.actionType = normalizeActionType(value); return; }
                if (prop === 'targetFieldCodes') {
                    group.targetFieldCodes = Array.prototype.slice.call(targetNode && targetNode.options ? targetNode.options : []).filter(function(option) { return option.selected; }).map(function(option) { return option.value; }).filter(Boolean);
                    group.actions = group.targetFieldCodes.map(function(fieldCode) { return {uid: createUid('action'), type: group.actionType || 'show', fieldCode: fieldCode, value: ''}; });
                    return;
                }
                if (prop.indexOf('row.') === 0) {
                    var rowNode = BX.findParent(targetNode, {className: 'rexp-form-editor__rule-value-row'});
                    var rowIndex = rowNode ? Number(rowNode.getAttribute('data-row-index')) : -1;
                    if (rowIndex < 0) { return; }
                    group.rows[rowIndex] = group.rows[rowIndex] || createRuleValueRow();
                    var rowProp = prop.slice(4);
                    if (rowProp === 'operator') { value = normalizeOperator(value); if (value === 'filled' || value === 'not_filled') { group.rows[rowIndex].value = ''; } }
                    if (rowProp === 'type' || rowProp === 'actionType') { rowProp = 'actionType'; value = normalizeActionType(value); }
                    if (rowProp === 'fieldCode') { rowProp = 'targetFieldCode'; }
                    group.rows[rowIndex][rowProp] = value;
                    return;
                }
                if (prop.indexOf('condition.') === 0) {
                    var conditionNode = BX.findParent(targetNode, {className: 'rexp-form-editor__rule-condition-row'});
                    var conditionIndex = conditionNode ? Number(conditionNode.getAttribute('data-condition-index')) : -1;
                    if (conditionIndex < 0) { return; }
                    group.conditions[conditionIndex] = group.conditions[conditionIndex] || createRuleCondition();
                    var conditionProp = prop.slice(10);
                    if (conditionProp === 'operator') { value = normalizeOperator(value); if (value === 'filled' || value === 'not_filled') { group.conditions[conditionIndex].value = ''; } }
                    group.conditions[conditionIndex][conditionProp] = value;
                    return;
                }
                if (prop.indexOf('action.') === 0) {
                    var actionNode = BX.findParent(targetNode, {className: 'rexp-form-editor__rule-action-row'});
                    var actionIndex = actionNode ? Number(actionNode.getAttribute('data-action-index')) : -1;
                    if (actionIndex < 0) { return; }
                    group.actions = Array.isArray(group.actions) ? group.actions : [];
                    group.actions[actionIndex] = normalizeRuleAction(group.actions[actionIndex] || createRuleAction());
                    var actionProp = prop.slice(7);
                    if (actionProp === 'type') { value = normalizeActionType(value); }
                    group.actions[actionIndex][actionProp] = value;
                    group.targetFieldCodes = group.actions.map(function(action) { return action.fieldCode || ''; }).filter(Boolean);
                    group.actionType = group.actions[0] ? group.actions[0].type : group.actionType;
                    return;
                }
            }
        }


        function addField() {
            var type = 'string';
            var view = getDefaultView(type);
            state.form.schema.fields.push({uid: createUid('field'), sectionUid: '', code: 'field_' + (state.form.schema.fields.length + 1), label: localize('REXP_FORM_EDITOR_JS_480'), title: localize('REXP_FORM_EDITOR_JS_481'), type: type, required: false, mandatory: false, multiple: false, expanded: false, ui: {view: view, placeholder: getDefaultPlaceholder(type, view), width: 'full'}, settings: {}, validation: {}, items: []});
            refresh();
        }

        function addUserField(index) {
            var field = state.userFields[index];
            if (!field) { return; }
            var clone = JSON.parse(JSON.stringify(field));
            clone.uid = createUid('field');
            clone.sectionUid = clone.sectionUid || '';
            clone.expanded = false;
            clone.sort = (state.form.schema.fields.length + 1) * 100;
            if (!clone.ui || typeof clone.ui !== 'object') { clone.ui = {}; }
            clone.ui.placeholder = clone.ui.placeholder || getDefaultPlaceholder(clone.type || 'string', clone.ui.view || getDefaultView(clone.type || 'string'));
            clone.placeholder = clone.placeholder || clone.ui.placeholder;
            state.form.schema.fields.push(clone);
            refresh();
        }


        function duplicateField(index) {
            var field = state.form.schema.fields[index];
            if (!field) { return; }
            var clone = JSON.parse(JSON.stringify(field));
            clone.uid = createUid('field');
            clone.code = createUniqueFieldCode((clone.code || 'field') + '_copy');
            clone.label = (clone.label || clone.title || localize('REXP_FORM_EDITOR_JS_482')) + localize('REXP_FORM_EDITOR_JS_483');
            clone.title = clone.label;
            clone.expanded = false;
            clone.sort = (state.form.schema.fields.length + 1) * 100;
            state.form.schema.fields.splice(index + 1, 0, clone);
            refresh();
        }

        function createUniqueFieldCode(baseCode) {
            var normalized = String(baseCode || 'field').replace(/[^a-zA-Z0-9_]/g, '_').replace(/_+/g, '_');
            if (!normalized) { normalized = 'field'; }
            var exists = {};
            (state.form.schema.fields || []).forEach(function(field) { if (field && field.code) { exists[String(field.code)] = true; } });
            var candidate = normalized;
            var counter = 1;
            while (exists[candidate]) {
                candidate = normalized + '_' + counter;
                counter++;
            }
            return candidate;
        }

        function loadMetadata(entityId) {
            return runAction('getFieldTypes', {entityId: entityId || ''}).then(function(data) {
                state.metadata = data || state.metadata;
                if (Array.isArray(data.userFields)) {
                    state.userFields = data.userFields;
                }
                return data;
            }).catch(function() {
                state.metadata = state.metadata || {fieldTypes: {}, ruleOperators: {}, ruleActions: {}, userFieldTypes: {}, userFields: []};
                return state.metadata;
            });
        }

        function loadUserFields() {
            var entityId = String(state.userFieldEntityId || '').trim();
            if (!entityId) { notify(localize('REXP_FORM_EDITOR_JS_484'), 'error'); return; }
            state.userFieldsLoading = true;
            refresh();
            runAction('getUserFields', {entityId: entityId}).then(function(data) {
                state.userFields = Array.isArray(data.items) ? data.items : [];
                state.userFieldsLoading = false;
                if (!state.userFields.length) {
                    notify(localize('REXP_FORM_EDITOR_JS_485') + entityId + localize('REXP_FORM_EDITOR_JS_486'), 'error');
                }
                refresh();
            }).catch(function() {
                state.userFieldsLoading = false;
                refresh();
                notify(localize('REXP_FORM_EDITOR_JS_487'), 'error');
            });
        }

        function addSection() {
            state.form.schema.sections.push({uid: createUid('section'), title: localize('REXP_FORM_EDITOR_JS_488'), description: '', sort: (state.form.schema.sections.length + 1) * 100});
            refresh();
        }

        function addRule(mode) {
            mode = mode || 'different_values_different_fields';
            state.form.schema.ruleGroups = Array.isArray(state.form.schema.ruleGroups) ? state.form.schema.ruleGroups : [];
            state.form.schema.ruleGroups.push({uid: createUid('rule_group'), enabled: true, mode: mode, logic: 'and', sourceFieldCode: '', rows: [createRuleValueRow()], actionType: 'show', targetFieldCodes: [], actions: [createRuleAction()], conditions: [createRuleCondition()]});
            state.showRuleModeCards = false;
            refresh();
        }


        function addSidebarNote() {
            state.form.schema.layout = ensureLayout(state.form.schema.layout);
            var notes = normalizeSidebarNotes(state.form.schema.layout.sidebarNotes, state.form.schema.layout.sidebarNote || {});
            if (notes.length >= 4) {
                notify(localize('REXP_FORM_EDITOR_JS_489'), 'warning');
                return;
            }
            notes.push({uid: createUid('note'), enabled: true, title: notes.length ? localize('REXP_FORM_EDITOR_JS_490') : localize('REXP_FORM_EDITOR_JS_491'), text: '', placement: notes.length ? 'bottom' : 'right', position: 'right', variant: 'info', icon: 'alert', width: '240'});
            state.form.schema.layout.sidebarNotes = notes;
            state.form.schema.layout.sidebarNote = notes[0] || Object.assign({}, defaultLayout().sidebarNote);
            refresh();
        }

        function deleteSidebarNote(index) {
            state.form.schema.layout = ensureLayout(state.form.schema.layout);
            var notes = normalizeSidebarNotes(state.form.schema.layout.sidebarNotes, state.form.schema.layout.sidebarNote || {});
            notes.splice(index, 1);
            state.form.schema.layout.sidebarNotes = notes;
            state.form.schema.layout.sidebarNote = notes[0] || Object.assign({}, defaultLayout().sidebarNote);
            refresh();
        }

function getThemeSettings(theme) {
            var settings = {
                light: {colors: {primary: '#2fc6f6', background: '#f5f7fb', card: '#ffffff', text: '#172b4d'}, typography: {font: 'system', size: '15'}, shape: {radius: '14', fieldRadius: '10'}, surface: {fieldStyle: 'outline', shadow: 'soft', border: 'subtle'}},
                dark: {colors: {primary: '#58c9ff', background: '#101828', card: '#1d2939', text: '#eef4ff'}, typography: {font: 'system', size: '15'}, shape: {radius: '18', fieldRadius: '10'}, surface: {fieldStyle: 'filled', shadow: 'deep', border: 'none'}},
                corporate: {colors: {primary: '#2066b0', background: '#eef5ff', card: '#ffffff', text: '#12355b'}, typography: {font: 'system', size: '15'}, shape: {radius: '12', fieldRadius: '8'}, surface: {fieldStyle: 'outline', shadow: 'soft', border: 'subtle'}},
                soft: {colors: {primary: '#8e6cff', background: '#f7f3ff', card: '#ffffff', text: '#33275f'}, typography: {font: 'inter', size: '15'}, shape: {radius: '22', fieldRadius: '14'}, surface: {fieldStyle: 'filled', shadow: 'soft', border: 'none'}},
                emerald: {colors: {primary: '#14b886', background: '#effaf6', card: '#ffffff', text: '#123d33'}, typography: {font: 'system', size: '15'}, shape: {radius: '18', fieldRadius: '12'}, surface: {fieldStyle: 'outline', shadow: 'soft', border: 'subtle'}},
                mono: {colors: {primary: '#667085', background: '#f3f4f6', card: '#ffffff', text: '#111827'}, typography: {font: 'system', size: '14'}, shape: {radius: '8', fieldRadius: '6'}, surface: {fieldStyle: 'underline', shadow: 'none', border: 'none'}},
                rexpress: {colors: {primary: '#ef233c', background: '#ffffff', card: '#eef2f5', text: '#07060d'}, typography: {font: 'onest', size: '15'}, shape: {radius: '12', fieldRadius: '12'}, surface: {fieldStyle: 'outline', shadow: 'none', border: 'none'}, icons: {enabled: false, formEnabled: false, noteEnabled: false, successEnabled: false, shape: 'none', background: '#ffffff', color: '#525c69', size: 'md'}}
            };
            return settings[theme] || settings.light;
        }

        function applyThemeSettings(theme) {
            state.form.schema.layout = ensureLayout(state.form.schema.layout);
            var settings = getThemeSettings(theme);
            state.form.schema.layout.theme = theme;
            state.form.schema.layout.colors = Object.assign({}, state.form.schema.layout.colors || {}, settings.colors || {});
            state.form.schema.layout.typography = Object.assign({}, state.form.schema.layout.typography || {}, settings.typography || {});
            state.form.schema.layout.shape = Object.assign({}, state.form.schema.layout.shape || {}, settings.shape || {});
            state.form.schema.layout.surface = Object.assign({}, state.form.schema.layout.surface || {}, settings.surface || {});
        }

        function save() {
            state.form.schema.layout = ensureLayout(state.form.schema.layout);
            normalizeDependencyRules();
            state.saving = true;
            refresh();
            runAction('save', {form: state.form}).then(function(data) {
                state.form = normalizeForm(data.item || state.form);
                notify(localize('REXP_FORM_EDITOR_JS_492'));
                state.saving = false;
                refresh();
            }).catch(function(error) {
                state.saving = false;
                refresh();
                notify(error && error.errors && error.errors[0] ? error.errors[0].message : localize('REXP_FORM_EDITOR_JS_493'), 'error');
            });
        }

        function load() {
            state.loading = true;
            refresh();
            loadMetadata(state.userFieldEntityId).then(function() {
                if (!options.formId) {
                    state.loading = false;
                    refresh();
                    return;
                }
                runAction('get', {id: Number(options.formId)}).then(function(data) {
                    state.form = normalizeForm(data.item || {});
                    state.permissions = data.permissions || {};
                    state.loading = false;
                    refresh();
                }).catch(function() { state.loading = false; refresh(); notify(localize('REXP_FORM_EDITOR_JS_494'), 'error'); });
            });
        }

        function openBizprocTemplates() {
            var formId = Number(state.form && state.form.id || options.formId || 0);
            if (!formId) {
                notify(localize('REXP_FORM_EDITOR_JS_495'), 'error');
                return;
            }
            var url = options.bizprocUrl || '';
            if (!url) { return; }
            url += (url.indexOf('?') === -1 ? '?' : '&') + 'FORM_ID=' + encodeURIComponent(String(formId));
            if (window.top && window.top !== window && window.top.location) {
                window.top.location.href = url;
                return;
            }
            window.location.href = url;
        }

        function openDrafts() {
            openEntityPage(options.draftsUrl || '', localize('REXP_FORM_EDITOR_JS_496'));
        }

        function openVersions() {
            openEntityPage(options.versionsUrl || '', localize('REXP_FORM_EDITOR_JS_497'));
        }

        function saveDraft() {
            if (!isDraftsEnabled()) {
                notify(localize('REXP_FORM_EDITOR_JS_498'), 'error');
                return;
            }
            if (!state.form.id) {
                notify(localize('REXP_FORM_EDITOR_JS_499'), 'error');
                return;
            }
            state.form.schema.layout = ensureLayout(state.form.schema.layout);
            normalizeDependencyRules();
            runAction('saveDraft', {formId: Number(state.form.id), form: state.form}).then(function() {
                notify(message('REXP_FORM_EDITOR_DRAFT_SAVE_SUCCESS', localize('REXP_FORM_EDITOR_JS_500')));
            }).catch(function(error) {
                notify(error && error.errors && error.errors[0] ? error.errors[0].message : message('REXP_FORM_EDITOR_DRAFT_SAVE_ERROR', localize('REXP_FORM_EDITOR_JS_501')), 'error');
            });
        }

        function restoreDraft() {
            if (!isDraftsEnabled()) {
                notify(localize('REXP_FORM_EDITOR_JS_502'), 'error');
                return;
            }
            if (!state.form.id) {
                notify(localize('REXP_FORM_EDITOR_JS_503'), 'error');
                return;
            }
            if (!confirm(localize('REXP_FORM_EDITOR_JS_504'))) {
                return;
            }
            runAction('getDraft', {formId: Number(state.form.id)}).then(function(data) {
                var draft = data.draft || {};
                if (!draft.form) {
                    notify(localize('REXP_FORM_EDITOR_JS_505'), 'error');
                    return;
                }
                state.form = normalizeForm(draft.form || state.form);
                state.form.id = Number(state.form.id || options.formId || 0);
                notify(message('REXP_FORM_EDITOR_DRAFT_RESTORE_SUCCESS', localize('REXP_FORM_EDITOR_JS_506')));
                refresh();
            }).catch(function(error) {
                notify(error && error.errors && error.errors[0] ? error.errors[0].message : localize('REXP_FORM_EDITOR_JS_507'), 'error');
            });
        }

        function diagnostics() {
            if (!state.form.id) { state.diagnostics = {warnings: [localize('REXP_FORM_EDITOR_JS_508')]}; state.tab = 'diagnostics'; refresh(); return; }
            runAction('diagnostics', {id: state.form.id}).then(function(data) { state.diagnostics = data.item || data; state.tab = 'diagnostics'; refresh(); }).catch(function() { notify(localize('REXP_FORM_EDITOR_JS_509'), 'error'); });
        }

        function copyRuntimeSnippet(button) {
            var container = BX.findParent(button, {className: 'rexp-form-editor__component-snippet'});
            var textarea = container ? container.querySelector('[data-runtime-snippet]') : null;
            var value = textarea ? textarea.value : '';
            if (!value) { return; }
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(function() { notify(localize('REXP_FORM_EDITOR_JS_510')); }).catch(function() { fallbackCopy(textarea); });
                return;
            }
            fallbackCopy(textarea);
        }

        function fallbackCopy(textarea) {
            if (!textarea) { return; }
            textarea.focus();
            textarea.select();
            try { document.execCommand('copy'); notify(localize('REXP_FORM_EDITOR_JS_511')); } catch (e) { notify(localize('REXP_FORM_EDITOR_JS_512'), 'error'); }
        }

        function getCurrentFormExportPayload() {
            var schema = ensureSchema(state.form.schema || defaultSchema());
            return {
                name: String(state.form.name || localize('REXP_FORM_EDITOR_JS_513')),
                code: String(state.form.code || 'form'),
                exportedAt: new Date().toISOString(),
                schemaVersion: 1,
                module: 'rexp.form',
                form: {
                    name: String(state.form.name || localize('REXP_FORM_EDITOR_JS_514')),
                    code: String(state.form.code || 'form'),
                    status: state.form.active ? 'published' : 'draft',
                    schema: schema
                }
            };
        }

        function downloadJson(filename, payload) {
            var text = typeof payload === 'string' ? payload : JSON.stringify(payload, null, 2);
            var blob = new Blob([text], {type: 'application/json;charset=utf-8'});
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename || 'form.json';
            link.click();
            URL.revokeObjectURL(link.href);
        }

        function exportForm() {
            downloadJson((state.form.code || 'form') + '.json', getCurrentFormExportPayload());
        }

        function exportFormJsonToText() {
            state.formExchangeText = JSON.stringify(getCurrentFormExportPayload(), null, 2);
            notify(localize('REXP_FORM_EDITOR_JS_515'), 'success');
            refresh();
        }

        function importFormJson() {
            var text = String(state.formExchangeText || '').trim();
            if (!text) {
                notify(localize('REXP_FORM_EDITOR_JS_516'), 'error');
                return;
            }
            try {
                var parsed = JSON.parse(text);
                var payload = parsed && parsed.form ? parsed : {form: parsed};
                runAction('import', {payload: payload}).then(function(data) {
                    var item = data.item || data;
                    state.form = normalizeForm(item || {});
                    state.formExchangeText = '';
                    state.tab = 'general';
                    notify(localize('REXP_FORM_EDITOR_JS_517'), 'success');
                    refresh();
                }).catch(function() {
                    notify(localize('REXP_FORM_EDITOR_JS_518'), 'error');
                });
            } catch (error) {
                notify(localize('REXP_FORM_EDITOR_JS_519'), 'error');
            }
        }

        function copyFormJson() {
            var text = state.formExchangeText || JSON.stringify(getCurrentFormExportPayload(), null, 2);
            state.formExchangeText = text;
            copyTextToClipboard(text, localize('REXP_FORM_EDITOR_JS_520'), localize('REXP_FORM_EDITOR_JS_521'));
            refresh();
        }


        function downloadFormJson() {
            exportForm();
        }

        function copyTextToClipboard(text, successMessage, fallbackMessage) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function() {
                    notify(successMessage || localize('REXP_FORM_EDITOR_JS_522'), 'success');
                }).catch(function() {
                    notify(fallbackMessage || localize('REXP_FORM_EDITOR_JS_523'), 'warning');
                });
            } else {
                notify(fallbackMessage || localize('REXP_FORM_EDITOR_JS_524'), 'success');
            }
        }


        function moveItem(list, from, to) {
            if (from < 0 || to < 0 || from >= list.length || to >= list.length) { return; }
            var item = list.splice(from, 1)[0];
            list.splice(to, 0, item);
        }

        function normalizeDependencyRules() {
            var schema = state.form.schema || {};
            sanitizeRuleGroups();
            schema.ruleGroups = Array.isArray(schema.ruleGroups) ? schema.ruleGroups.map(normalizeRuleGroup) : [];
            schema.rules = [];

            schema.ruleGroups.forEach(function(group) {
                if (group.enabled === false) { return; }
                if (group.mode === 'different_values_same_fields') {
                    expandDifferentValuesSameFields(group, schema.rules);
                    return;
                }
                if (group.mode === 'conditions_same_fields') {
                    expandConditionsSameFields(group, schema.rules);
                    return;
                }
                expandDifferentValuesDifferentFields(group, schema.rules);
            });

            schema.compiledRules = {
                schemaVersion: schema.schemaVersion || schema.version || 2,
                rules: schema.rules.slice(),
                dependsOn: buildRulesDependsOn(schema.rules)
            };
        }

        function buildRulesDependsOn(rules) {
            var result = {};
            (rules || []).forEach(function(rule) {
                var uid = rule.uid || createUid('rule');
                (rule.conditions || []).forEach(function(condition) {
                    var fieldCode = condition.fieldCode || condition.field || '';
                    if (!fieldCode) { return; }
                    result[fieldCode] = result[fieldCode] || [];
                    if (result[fieldCode].indexOf(uid) < 0) { result[fieldCode].push(uid); }
                });
            });
            return result;
        }

        function makeSimpleAction(type, fieldCode, value) {
            return {type: normalizeActionType(type || 'show'), fieldCode: fieldCode || '', value: value !== undefined ? value : '', clearOnHide: normalizeActionType(type || 'show') === 'clear_value'};
        }

        function makeSimpleCondition(fieldCode, value, operator) {
            operator = normalizeOperator(operator || '=');
            var condition = {enabled: true, fieldCode: fieldCode || '', operator: operator, value: value !== undefined ? value : '', compareBy: 'value'};
            if (operator === 'in' || operator === 'not_in') {
                condition.values = Array.isArray(value) ? value : String(value || '').split(',').map(function(item) { return item.trim(); }).filter(Boolean);
            } else {
                condition.values = condition.value === '' ? [] : [condition.value];
            }
            return condition;
        }

        function conditionHasEnoughData(condition) {
            if (!condition || !condition.fieldCode) { return false; }
            var operator = normalizeOperator(condition.operator || '=');
            if (operator === 'filled' || operator === 'not_filled') { return true; }
            if (operator === 'in' || operator === 'not_in') {
                if (Array.isArray(condition.values) && condition.values.length) { return true; }
                return String(condition.value || '').trim() !== '';
            }
            return condition.value !== undefined && String(condition.value).trim() !== '';
        }

        function expandDifferentValuesDifferentFields(group, targetRules) {
            if (!group.sourceFieldCode) { return; }
            (group.rows || []).forEach(function(row) {
                var condition = makeSimpleCondition(group.sourceFieldCode, row.value, normalizeOperator(row.operator || '='));
                var actionFieldCode = row.targetFieldCode || row.fieldCode || '';
                var actionType = normalizeActionType(row.actionType || 'show');
                if (!conditionHasEnoughData(condition) || !actionFieldCode) { return; }
                targetRules.push({
                    uid: createUid('rule'),
                    enabled: true,
                    sourceGroupUid: group.uid || '',
                    logic: group.logic || 'and',
                    conditions: [condition],
                    when: condition,
                    actions: [makeSimpleAction(actionType, actionFieldCode, row.valueToSet || row.setValue || '')]
                });
            });
        }

        function expandDifferentValuesSameFields(group, targetRules) {
            if (!group.sourceFieldCode) { return; }
            var actions = resolveActionsForCompilation(group);
            if (!actions.length) { return; }
            var rows = (group.rows || []).map(function(row) { return makeSimpleCondition(group.sourceFieldCode, row.value, normalizeOperator(row.operator || '=')); }).filter(conditionHasEnoughData);
            if (!rows.length) { return; }
            var canUseIn = rows.every(function(row) { return normalizeOperator(row.operator || '=') === '='; });
            if (canUseIn) {
                var values = rows.map(function(row) { return row.value; });
                var condition = {enabled: true, fieldCode: group.sourceFieldCode, operator: 'in', value: '', values: values, compareBy: 'value'};
                targetRules.push({
                    uid: createUid('rule'),
                    enabled: true,
                    sourceGroupUid: group.uid || '',
                    logic: group.logic || 'and',
                    conditions: [condition],
                    when: condition,
                    actions: actions
                });
                return;
            }
            rows.forEach(function(condition) {
                targetRules.push({
                    uid: createUid('rule'),
                    enabled: true,
                    sourceGroupUid: group.uid || '',
                    logic: group.logic || 'and',
                    conditions: [condition],
                    when: condition,
                    actions: actions
                });
            });
        }

        function expandConditionsSameFields(group, targetRules) {
            var conditions = (group.conditions || []).map(function(condition) { return makeSimpleCondition(condition.fieldCode, condition.value, normalizeOperator(condition.operator || '=')); }).filter(conditionHasEnoughData);
            var actions = resolveActionsForCompilation(group);
            if (!conditions.length || !actions.length) { return; }
            targetRules.push({
                uid: createUid('rule'),
                enabled: true,
                sourceGroupUid: group.uid || '',
                logic: group.logic || 'and',
                conditions: conditions,
                when: conditions[0],
                actions: actions
            });
        }

        function sanitizeRuleGroups() {
            var schema = state.form.schema || {};
            var known = {};
            (schema.fields || []).forEach(function(field) { if (field && field.code) { known[String(field.code)] = true; } });
            schema.ruleGroups = (schema.ruleGroups || []).map(function(group) {
                group = normalizeRuleGroup(group);
                if (group.sourceFieldCode && !known[String(group.sourceFieldCode)]) { group.sourceFieldCode = ''; }
                group.rows = group.rows.filter(function(row) {
                    if (row.targetFieldCode && !known[String(row.targetFieldCode)]) { row.targetFieldCode = ''; }
                    return true;
                });
                group.conditions = group.conditions.filter(function(condition) {
                    return condition.fieldCode && known[String(condition.fieldCode)];
                });
                if (!group.conditions.length) { group.conditions = [createRuleCondition()]; }
                group.actions = (group.actions || []).filter(function(action) { return action.fieldCode && known[String(action.fieldCode)]; });
                group.targetFieldCodes = (group.targetFieldCodes || []).filter(function(fieldCode) { return known[String(fieldCode)]; });
                return group;
            });
        }

        function removeFieldReferences(fieldCode) {
            fieldCode = String(fieldCode || '');
            if (!fieldCode) { return; }
            var schema = state.form.schema || {};
            schema.ruleGroups = (schema.ruleGroups || []).map(function(group) {
                group = normalizeRuleGroup(group);
                if (String(group.sourceFieldCode || '') === fieldCode) { group.sourceFieldCode = ''; }
                group.rows.forEach(function(row) {
                    if (String(row.targetFieldCode || '') === fieldCode) { row.targetFieldCode = ''; }
                });
                group.conditions = group.conditions.filter(function(condition) { return String(condition.fieldCode || '') !== fieldCode; });
                if (!group.conditions.length) { group.conditions = [createRuleCondition()]; }
                group.actions = group.actions.filter(function(action) { return String(action.fieldCode || '') !== fieldCode; });
                group.targetFieldCodes = group.targetFieldCodes.filter(function(code) { return String(code || '') !== fieldCode; });
                return group;
            });
        }

        node.addEventListener('input', function(event) {
            var target = event.target;
            if (!target || !target.getAttribute) { return; }
            var key = target.getAttribute('data-field');
            if (!key) { return; }
            var context = BX.findParent(target, {className: 'rexp-form-editor__field'}) || BX.findParent(target, {className: 'rexp-form-editor__section'}) || BX.findParent(target, {className: 'rexp-form-editor__rule'});
            setValue(key, target.value, context, target);
            if (key.indexOf('layout.') === 0 && target.type === 'color') {
                updatePreviewLayoutVars();
            }
        });

        node.addEventListener('change', function(event) {
            var target = event.target;
            var uploadTarget = target && target.getAttribute ? target.getAttribute('data-upload-target') : '';
            if (uploadTarget) {
                if (target.files && target.files[0]) { uploadDesignImage(target.files[0], uploadTarget); }
                target.value = '';
                return;
            }
            var key = target && target.getAttribute ? target.getAttribute('data-field') : '';
            if (!key) { return; }
            var context = BX.findParent(target, {className: 'rexp-form-editor__field'}) || BX.findParent(target, {className: 'rexp-form-editor__section'}) || BX.findParent(target, {className: 'rexp-form-editor__rule'});
            setValue(key, target.type === 'checkbox' ? target.checked : target.value, context, target);
            if (key.indexOf('layout.') === 0 && target.type === 'color') {
                updatePreviewLayoutVars();
                return;
            }
            refresh();
        });


        node.addEventListener('click', function(event) {
            var target = event.target;
            if (!target || !target.getAttribute) { return; }
            var actionTarget = target.closest ? target.closest('[data-action], [data-tab]') : target;
            if (!actionTarget || !node.contains(actionTarget)) { return; }
            var tab = actionTarget.getAttribute('data-tab');
            if (tab) { state.tab = tab; refresh(); return; }
            var action = actionTarget.getAttribute('data-action');
            target = actionTarget;
            var fieldBox = BX.findParent(target, {className: 'rexp-form-editor__field'});
            var sectionBox = BX.findParent(target, {className: 'rexp-form-editor__section'});
            var ruleBox = BX.findParent(target, {className: 'rexp-form-editor__rule'});
            var index = fieldBox ? Number(fieldBox.getAttribute('data-field-index')) : -1;
            var sectionIndex = sectionBox ? Number(sectionBox.getAttribute('data-section-index')) : -1;
            var ruleIndex = ruleBox ? Number(ruleBox.getAttribute('data-rule-index')) : -1;
            if (action === 'save') { save(); }
            if (action === 'open-exchange-tab') { state.tab = 'exchange'; refresh(); return; }
            if (action === 'add-field') { addField(); }
            if (action === 'set-theme') {
                state.form.schema.layout = ensureLayout(state.form.schema.layout);
                var requestedTheme = actionTarget.getAttribute('data-theme') || 'light';
                if (String(state.form.schema.layout.theme || '') === String(requestedTheme)) { return; }
                applyThemeSettings(requestedTheme);
                refresh();
                return;
            }
            if (action === 'add-sidebar-note') { addSidebarNote(); return; }
            if (action === 'delete-sidebar-note') { deleteSidebarNote(Number(actionTarget.getAttribute('data-note-index'))); return; }
            if (action === 'set-preview-width') { state.previewWidth = actionTarget.getAttribute('data-preview-width') || '760'; refresh(); return; }
            if (action === 'reset-layout') { state.form.schema.layout = defaultLayout(); notify(localize('REXP_FORM_EDITOR_JS_525')); refresh(); return; }
            if (action === 'set-layout-icon') { state.form.schema.layout = ensureLayout(state.form.schema.layout); state.form.schema.layout.media.icon = normalizeIconCode(actionTarget.getAttribute('data-icon'), 'info'); refresh(); return; }
            if (action === 'set-design-icon') { state.form.schema.layout = ensureLayout(state.form.schema.layout); setNestedValue(state.form.schema.layout, actionTarget.getAttribute('data-icon-target') || 'media.icon', normalizeIconCode(actionTarget.getAttribute('data-icon'), 'info')); refresh(); return; }
            if (action === 'clear-design-image') { state.form.schema.layout = ensureLayout(state.form.schema.layout); setNestedValue(state.form.schema.layout, actionTarget.getAttribute('data-image-target') || 'media.imageUrl', ''); refresh(); return; }
            if (action === 'export-form-json') { exportFormJsonToText(); return; }
            if (action === 'download-form-json') { downloadFormJson(); return; }
            if (action === 'copy-form-json') { copyFormJson(); return; }
            if (action === 'import-form-json') { importFormJson(); return; }
            if (action === 'clear-form-json') { state.formExchangeText = ''; refresh(); return; }
            if (action === 'toggle-all-fields') {
                var allExpanded = state.form.schema.fields.length && state.form.schema.fields.every(function(field) { return !!field.expanded; });
                state.form.schema.fields.forEach(function(field) { field.expanded = !allExpanded; });
                refresh();
            }
            if (action === 'add-section') { addSection(); }
            if (action === 'show-rule-mode-cards') { state.showRuleModeCards = true; refresh(); return; }
            if (action === 'hide-rule-mode-cards') { state.showRuleModeCards = false; refresh(); return; }
            if (action === 'add-rule') { addRule(target.getAttribute('data-mode') || 'different_values_different_fields'); }
            if (action === 'open-bizproc') { openBizprocTemplates(); }
            if (action === 'save-draft') { saveDraft(); }
            if (action === 'restore-draft') { restoreDraft(); }
            if (action === 'open-drafts') { openDrafts(); }
            if (action === 'open-versions') { openVersions(); }
            if (action === 'diagnostics') { diagnostics(); }
            if (action === 'copy-runtime-snippet') { copyRuntimeSnippet(target); }
            if (action === 'export') { exportForm(); }
            if (action === 'delete-field' && index >= 0) { var deletedCode = state.form.schema.fields[index] && state.form.schema.fields[index].code; state.form.schema.fields.splice(index, 1); removeFieldReferences(deletedCode); normalizeDependencyRules(); refresh(); }
            if (action === 'toggle-field-full' && index >= 0) { state.form.schema.fields[index].expanded = !state.form.schema.fields[index].expanded; refresh(); }
            if (action === 'duplicate-field' && index >= 0) { duplicateField(index); }
            if (action === 'move-up' && index > 0) { moveItem(state.form.schema.fields, index, index - 1); refresh(); }
            if (action === 'move-down' && index >= 0 && index < state.form.schema.fields.length - 1) { moveItem(state.form.schema.fields, index, index + 1); refresh(); }
            if (action === 'delete-section' && sectionIndex >= 0) {
                var sectionUid = state.form.schema.sections[sectionIndex].uid;
                state.form.schema.sections.splice(sectionIndex, 1);
                state.form.schema.fields.forEach(function(field) {
                    if (field.sectionUid === sectionUid) {
                        field.sectionUid = '';
                    }
                });
                refresh();
            }
            if (action === 'section-up' && sectionIndex > 0) { moveItem(state.form.schema.sections, sectionIndex, sectionIndex - 1); refresh(); }
            if (action === 'section-down' && sectionIndex >= 0 && sectionIndex < state.form.schema.sections.length - 1) { moveItem(state.form.schema.sections, sectionIndex, sectionIndex + 1); refresh(); }
            if (action === 'add-rg-value' && ruleIndex >= 0) {
                state.form.schema.ruleGroups[ruleIndex] = normalizeRuleGroup(state.form.schema.ruleGroups[ruleIndex]);
                state.form.schema.ruleGroups[ruleIndex].rows.push(createRuleValueRow());
                refresh();
            }
            if (action === 'delete-rg-value' && ruleIndex >= 0) {
                var rowBox = BX.findParent(target, {className: 'rexp-form-editor__rule-value-row'});
                var rowIndex = rowBox ? Number(rowBox.getAttribute('data-row-index')) : -1;
                var rows = state.form.schema.ruleGroups[ruleIndex] && state.form.schema.ruleGroups[ruleIndex].rows ? state.form.schema.ruleGroups[ruleIndex].rows : [];
                if (rowIndex >= 0 && rows.length > 1) { rows.splice(rowIndex, 1); refresh(); }
            }
            if (action === 'add-rg-condition' && ruleIndex >= 0) {
                state.form.schema.ruleGroups[ruleIndex] = normalizeRuleGroup(state.form.schema.ruleGroups[ruleIndex]);
                state.form.schema.ruleGroups[ruleIndex].conditions.push(createRuleCondition());
                refresh();
            }
            if (action === 'add-rg-action' && ruleIndex >= 0) {
                state.form.schema.ruleGroups[ruleIndex] = normalizeRuleGroup(state.form.schema.ruleGroups[ruleIndex]);
                state.form.schema.ruleGroups[ruleIndex].actions.push(createRuleAction());
                refresh();
            }
            if (action === 'delete-rg-action' && ruleIndex >= 0) {
                var actionBox = BX.findParent(target, {className: 'rexp-form-editor__rule-action-row'});
                var actionIndex = actionBox ? Number(actionBox.getAttribute('data-action-index')) : -1;
                var actions = state.form.schema.ruleGroups[ruleIndex] && state.form.schema.ruleGroups[ruleIndex].actions ? state.form.schema.ruleGroups[ruleIndex].actions : [];
                if (actionIndex >= 0 && actions.length > 1) { actions.splice(actionIndex, 1); refresh(); }
            }
            if (action === 'delete-rg-condition' && ruleIndex >= 0) {
                var conditionBox = BX.findParent(target, {className: 'rexp-form-editor__rule-condition-row'});
                var conditionIndex = conditionBox ? Number(conditionBox.getAttribute('data-condition-index')) : -1;
                var conditions = state.form.schema.ruleGroups[ruleIndex] && state.form.schema.ruleGroups[ruleIndex].conditions ? state.form.schema.ruleGroups[ruleIndex].conditions : [];
                if (conditionIndex >= 0 && conditions.length > 1) { conditions.splice(conditionIndex, 1); refresh(); }
            }
            if (action === 'delete-rule' && ruleIndex >= 0) { state.form.schema.ruleGroups.splice(ruleIndex, 1); normalizeDependencyRules(); refresh(); }
            if (action === 'clear-source-field' && ruleIndex >= 0) { state.form.schema.ruleGroups[ruleIndex] = normalizeRuleGroup(state.form.schema.ruleGroups[ruleIndex]); state.form.schema.ruleGroups[ruleIndex].sourceFieldCode = ''; refresh(); }
            if (action === 'clear-target-field' && ruleIndex >= 0) { var rowBox2 = BX.findParent(target, {className: 'rexp-form-editor__rule-value-row'}); var rowIndex2 = rowBox2 ? Number(rowBox2.getAttribute('data-row-index')) : -1; if (rowIndex2 >= 0) { state.form.schema.ruleGroups[ruleIndex] = normalizeRuleGroup(state.form.schema.ruleGroups[ruleIndex]); state.form.schema.ruleGroups[ruleIndex].rows[rowIndex2] = state.form.schema.ruleGroups[ruleIndex].rows[rowIndex2] || createRuleValueRow(); state.form.schema.ruleGroups[ruleIndex].rows[rowIndex2].targetFieldCode = ''; refresh(); } }
            if (action === 'clear-condition-field' && ruleIndex >= 0) { var cBox2 = BX.findParent(target, {className: 'rexp-form-editor__rule-condition-row'}); var cIndex2 = cBox2 ? Number(cBox2.getAttribute('data-condition-index')) : -1; if (cIndex2 >= 0) { state.form.schema.ruleGroups[ruleIndex] = normalizeRuleGroup(state.form.schema.ruleGroups[ruleIndex]); state.form.schema.ruleGroups[ruleIndex].conditions[cIndex2] = state.form.schema.ruleGroups[ruleIndex].conditions[cIndex2] || createRuleCondition(); state.form.schema.ruleGroups[ruleIndex].conditions[cIndex2].fieldCode = ''; refresh(); } }
        });

        load();
    };
})(window.BX);
