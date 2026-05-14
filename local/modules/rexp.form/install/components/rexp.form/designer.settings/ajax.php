<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}


use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\DI\ServiceLocator;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);
/**
 * AJAX-контроллер сохранения прав конструктора форм.
 */
class RexpFormDesignerSettingsAjaxController extends Controller
{
    /**
     * Описывает AJAX-действия контроллера.
     *
     * @return array
     */
    public function configureActions(): array
    {
        return [
            'save' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(['POST']),
                    new ActionFilter\Csrf(),
                ],
            ],
            'delete' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                    new ActionFilter\HttpMethod(['POST']),
                    new ActionFilter\Csrf(),
                ],
            ],
            'load' => [
                'prefilters' => [
                    new ActionFilter\Authentication(),
                ],
            ],
        ];
    }

    /**
     * Сохраняет переданные данные.
     *
     * @param mixed $userGroups
     *
     * @return array
     */
    public function saveAction($userGroups = null): array
    {
        $service = $this->settingsService();
        $service->saveAccessRights($this->normalizeUserGroupsPayload($userGroups));

        return [
            'USER_GROUPS' => $service->getUserGroups(),
            'ACCESS_RIGHTS' => $service->getAccessRights(),
        ];
    }

    /**
     * Удаляет указанный объект.
     *
     * @param int $roleId
     * @param int $userGroupId
     * @param int $id
     *
     * @return void
     */
    public function deleteAction(int $roleId = 0, int $userGroupId = 0, int $id = 0): void
    {
        $roleId = $roleId > 0 ? $roleId : ($userGroupId > 0 ? $userGroupId : $id);
        if ($roleId <= 0) {
            return;
        }

        $this->settingsService()->deleteRole($roleId);
    }

    /**
     * Загружает данные страницы прав.
     *
     * @return array
     */
    public function loadAction(): array
    {
        return $this->settingsService()->getAccessRightsSettings();
    }

    /**
     * Инициализирует контроллер перед обработкой запроса.
     *
     * @return void
     */
    protected function init(): void
    {
        if (!Loader::includeModule('rexp.form')) {
            $this->errorCollection[] = new Error(\Bitrix\Main\Localization\Loc::getMessage('REXP_FORM_CMP_DESIGNER_SETTINGS_AJAX_001'));
        }

        parent::init();
    }

    /**
     * Возвращает сервис настроек прав конструктора.
     *
     * @return \Rexp\Form\Application\Service\FormSettingsService
     */
    private function settingsService(): \Rexp\Form\Application\Service\FormSettingsService
    {
        return ServiceLocator::getInstance()->get('rexp.form.service.settings');
    }

    /**
     * Нормализует payload ролей доступа из AJAX-запроса.
     *
     * @param mixed $userGroups
     *
     * @return array
     */
    private function normalizeUserGroupsPayload($userGroups): array
    {
        $payload = $this->decodePayload($userGroups);
        if ($payload === []) {
            $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
            $payload = $this->decodePayload($request->getPost('userGroups'));

            if ($payload === []) {
                $post = $request->getPostList()->toArray();
                foreach (['data', 'fields', 'payload'] as $containerKey) {
                    $container = $this->decodePayload($post[$containerKey] ?? null);
                    if ($container === []) {
                        continue;
                    }

                    $payload = $this->decodePayload($container['userGroups'] ?? $container['USER_GROUPS'] ?? null);
                    if ($payload !== []) {
                        break;
                    }
                }
            }
        }

        return $this->normalizeUserGroupsArray($payload);
    }

    /**
     * Декодирует payload из строки или массива.
     *
     * @param mixed $value
     *
     * @return array
     */
    private function decodePayload($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Нормализует массив ролей доступа.
     *
     * @param array $payload
     *
     * @return array
     */
    private function normalizeUserGroupsArray(array $payload): array
    {
        foreach (['userGroups', 'USER_GROUPS'] as $userGroupsKey) {
            if (isset($payload[$userGroupsKey]) && (is_array($payload[$userGroupsKey]) || is_string($payload[$userGroupsKey]))) {
                $payload = $this->decodePayload($payload[$userGroupsKey]);
                break;
            }
        }

        $result = [];
        foreach ($payload as $roleId => $roleSettings) {
            if (!is_array($roleSettings)) {
                continue;
            }

            if (!isset($roleSettings['id']) && is_numeric($roleId)) {
                $roleSettings['id'] = (int)$roleId;
            }

            $result[] = $roleSettings;
        }

        return $result;
    }
}
