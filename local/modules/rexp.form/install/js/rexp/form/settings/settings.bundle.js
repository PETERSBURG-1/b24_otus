(function(BX) {
    'use strict';
    if (!BX || !BX.namespace) { return; }
    BX.namespace('BX.RexpForm');

    function localize(code, fallback) {
        var value = BX && BX.message ? BX.message(code) : '';
        return value || fallback || code;
    }


    function notify(message, category) {
        if (BX.UI && BX.UI.Notification && BX.UI.Notification.Center) {
            BX.UI.Notification.Center.notify({content: message, category: category || 'success'});
            return;
        }
        alert(message);
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    BX.RexpForm.createSettings = function(options) {
        var node = document.getElementById(options.containerId);
        if (!node) { return; }

        var state = {
            loading: true,
            saving: false,
            roles: [],
            permissions: [],
            groups: []
        };

        function msg(code, fallback) { return options.messages && options.messages[code] ? options.messages[code] : fallback; }
        function hasAccess(role, accessCode) { return (role.accessCodes || []).indexOf(accessCode) >= 0; }
        function hasPermission(role, permissionId) { return (role.permissions || []).indexOf(permissionId) >= 0; }

        function render() {
            if (state.loading) {
                node.innerHTML = '<div class="rexp-form-settings">' + escapeHtml(msg('REXP_FORM_SETTINGS_LOADING', localize('REXP_FORM_SETTINGS_JS_001'))) + '</div>';
                return;
            }

            node.innerHTML = '<div class="rexp-form-settings">' +
                '<div class="rexp-form-settings__title">' + escapeHtml(msg('REXP_FORM_SETTINGS_TITLE', localize('REXP_FORM_SETTINGS_JS_002'))) + '</div>' +
                '<div class="rexp-form-settings__hint">' + escapeHtml(msg('REXP_FORM_SETTINGS_HINT', localize('REXP_FORM_SETTINGS_JS_003'))) + '</div>' +
                '<div class="rexp-form-settings__roles">' + state.roles.map(renderRole).join('') + '</div>' +
                '<div class="rexp-form-settings__actions"><button class="ui-btn ui-btn-primary" data-action="save"' + (state.saving ? ' disabled' : '') + '>' + escapeHtml(state.saving ? msg('REXP_FORM_SETTINGS_SAVING', localize('REXP_FORM_SETTINGS_JS_004')) : msg('REXP_FORM_SETTINGS_SAVE', localize('REXP_FORM_SETTINGS_JS_005'))) + '</button></div>' +
            '</div>';
        }

        function renderRole(role, roleIndex) {
            return '<div class="rexp-form-settings__card" data-role-index="' + roleIndex + '">' +
                '<div class="rexp-form-settings__card-title">' + escapeHtml(role.name || (localize('REXP_FORM_SETTINGS_JS_006') + role.id)) + '</div>' +
                '<div class="rexp-form-settings__hint">' + escapeHtml(msg('REXP_FORM_SETTINGS_ROLE_GROUPS', localize('REXP_FORM_SETTINGS_JS_007'))) + '</div>' +
                '<div class="rexp-form-settings__group-list">' + state.groups.map(function(group) {
                    return '<label class="rexp-form-settings__group"><input type="checkbox" data-role-index="' + roleIndex + '" data-type="access" data-code="' + escapeHtml(group.accessCode) + '"' + (hasAccess(role, group.accessCode) ? ' checked' : '') + '><span>' + escapeHtml(group.name) + (Number(group.id) > 0 ? ' #' + Number(group.id) : '') + '</span></label>';
                }).join('') + '</div>' +
                '<div class="rexp-form-settings__hint">' + escapeHtml(msg('REXP_FORM_SETTINGS_ROLE_PERMISSIONS', localize('REXP_FORM_SETTINGS_JS_008'))) + '</div>' +
                '<div class="rexp-form-settings__permission-list">' + state.permissions.map(function(permission) {
                    return '<label class="rexp-form-settings__group"><input type="checkbox" data-role-index="' + roleIndex + '" data-type="permission" data-code="' + escapeHtml(permission.id) + '"' + (hasPermission(role, permission.id) ? ' checked' : '') + '><span>' + escapeHtml(permission.name) + '</span></label>';
                }).join('') + '</div>' +
            '</div>';
        }

        function runAction(action, data) {
            return BX.ajax.runAction(options.controller + '.' + action, {data: data || {}}).then(function(response) { return response.data || {}; });
        }

        function load() {
            state.loading = true;
            render();
            runAction('getSettings').then(function(data) {
                var settings = data.item || data.settings || data;
                state.roles = settings.roles || [];
                state.permissions = settings.permissions || [];
                state.groups = settings.availableGroups || [];
                state.loading = false;
                render();
            }).catch(function() {
                state.loading = false;
                render();
                notify(msg('REXP_FORM_SETTINGS_LOAD_ERROR', localize('REXP_FORM_SETTINGS_JS_009')), 'error');
            });
        }

        function save() {
            state.saving = true;
            render();
            runAction('saveSettings', {settings: {roles: state.roles}}).then(function(data) {
                var settings = data.item || data.settings || data;
                state.roles = settings.roles || state.roles;
                state.permissions = settings.permissions || state.permissions;
                state.groups = settings.availableGroups || state.groups;
                state.saving = false;
                render();
                notify(msg('REXP_FORM_SETTINGS_SAVE_SUCCESS', localize('REXP_FORM_SETTINGS_JS_010')));
            }).catch(function(error) {
                state.saving = false;
                render();
                notify(error && error.errors && error.errors[0] ? error.errors[0].message : msg('REXP_FORM_SETTINGS_SAVE_ERROR', localize('REXP_FORM_SETTINGS_JS_011')), 'error');
            });
        }

        node.addEventListener('change', function(event) {
            var input = event.target;
            if (!input || input.type !== 'checkbox') { return; }
            var roleIndex = Number(input.getAttribute('data-role-index'));
            var role = state.roles[roleIndex];
            if (!role) { return; }

            var type = input.getAttribute('data-type');
            var code = input.getAttribute('data-code');
            var key = type === 'permission' ? 'permissions' : 'accessCodes';
            role[key] = role[key] || [];
            role[key] = role[key].filter(function(value) { return String(value) !== String(code); });
            if (input.checked) { role[key].push(code); }
        });

        node.addEventListener('click', function(event) {
            var button = event.target;
            if (button && button.getAttribute && button.getAttribute('data-action') === 'save') { save(); }
        });

        load();
    };
})(window.BX);
