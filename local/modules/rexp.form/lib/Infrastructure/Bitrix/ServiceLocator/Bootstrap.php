<?php

namespace Rexp\Form\Infrastructure\Bitrix\ServiceLocator;

use Bitrix\Main\DI\ServiceLocator;
use Rexp\Form\Application\Factory\FormFactory;
use Rexp\Form\Application\Service\FormDiagnosticsService;
use Rexp\Form\Application\Service\FormDraftService;
use Rexp\Form\Application\Service\FormExportService;
use Rexp\Form\Application\Service\FormImportService;
use Rexp\Form\Application\Service\FormImportExportService;
use Rexp\Form\Application\Service\FormManagerService;
use Rexp\Form\Application\Service\FormModuleOptionsService;
use Rexp\Form\Application\Service\FormPermissionService;
use Rexp\Form\Application\Service\FormPublishService;
use Rexp\Form\Application\Service\FormReadService;
use Rexp\Form\Application\Service\FormRuntimeService;
use Rexp\Form\Application\Service\RuntimeSubmissionService;
use Rexp\Form\Application\Service\RuntimeBizprocService;
use Rexp\Form\Application\Service\FormSettingsService;
use Rexp\Form\Application\Service\FormStateAssetService;
use Rexp\Form\Application\Service\FormTemplateCatalogService;
use Rexp\Form\Application\Service\FormTargetResolverService;
use Rexp\Form\Application\Service\FormFieldTypeRegistryService;
use Rexp\Form\Application\Service\FormRuleCompilerService;
use Rexp\Form\Application\Service\UserFieldTypeAdapterService;
use Rexp\Form\Application\Service\FormTemplateCreateService;
use Rexp\Form\Application\Service\FormTemplateService;
use Rexp\Form\Application\Service\FormVersionService;
use Rexp\Form\Application\Service\RuntimeDynamicOptionsService;
use Rexp\Form\Application\Service\RuntimeValueNormalizerService;
use Rexp\Form\Application\Service\RuntimeConditionEvaluatorService;
use Rexp\Form\Application\Service\RuntimeRuleEvaluatorService;
use Rexp\Form\Application\Service\RuntimeFieldValidationService;
use Rexp\Form\Application\Service\RuntimeFileService;
use Rexp\Form\Application\Service\RuntimePublicFormBuilderService;
use Rexp\Form\Application\Service\RuntimeDirectoryService;
use Rexp\Form\Application\Service\MockSubmitDiagnosticsService;
use Rexp\Form\Application\Service\TemplatePreviewService;
use Rexp\Form\Application\UseCase\Designer\DesignerFormUseCase;
use Rexp\Form\Application\UseCase\Designer\DesignerHistoryUseCase;
use Rexp\Form\Application\UseCase\Designer\DesignerMetadataUseCase;
use Rexp\Form\Application\UseCase\Designer\DesignerSubmitUseCase;
use Rexp\Form\Infrastructure\Event\FormEventDispatcher;
use Rexp\Form\Infrastructure\Persistence\FormDraftRepository;
use Rexp\Form\Infrastructure\Persistence\FormRepository;
use Rexp\Form\Infrastructure\Persistence\FormVersionRepository;

/**
 * Регистрация сервисов модуля в сервис-локаторе Bitrix.
 */
final class Bootstrap
{
    /**
     * Регистрирует сервисы модуля в сервис-локаторе.
     *
     * @return void
     */
    public static function register(): void
    {
        $locator = ServiceLocator::getInstance();

        if (!$locator->has('rexp.form.factory.form')) {
            $locator->addInstance('rexp.form.factory.form', new FormFactory());
        }

        if (!$locator->has('rexp.form.repository.form')) {
            $locator->addInstance('rexp.form.repository.form', new FormRepository($locator->get('rexp.form.factory.form')));
        }

        if (!$locator->has('rexp.form.repository.version')) {
            $locator->addInstance('rexp.form.repository.version', new FormVersionRepository());
        }

        if (!$locator->has('rexp.form.repository.draft')) {
            $locator->addInstance('rexp.form.repository.draft', new FormDraftRepository());
        }

        if (!$locator->has('rexp.form.event.dispatcher')) {
            $locator->addInstance('rexp.form.event.dispatcher', new FormEventDispatcher());
        }

        if (!$locator->has('rexp.form.service.module_options')) {
            $locator->addInstance('rexp.form.service.module_options', new FormModuleOptionsService());
        }

        if (!$locator->has('rexp.form.service.permission')) {
            $locator->addInstance('rexp.form.service.permission', new FormPermissionService());
        }

        if (!$locator->has('rexp.form.service.field_type_registry')) {
            $locator->addInstance('rexp.form.service.field_type_registry', new FormFieldTypeRegistryService());
        }

        if (!$locator->has('rexp.form.service.rule_compiler')) {
            $locator->addInstance('rexp.form.service.rule_compiler', new FormRuleCompilerService());
        }

        if (!$locator->has('rexp.form.service.userfield_adapter')) {
            $locator->addInstance('rexp.form.service.userfield_adapter', new UserFieldTypeAdapterService(
                $locator->get('rexp.form.service.field_type_registry')
            ));
        }

        if (!$locator->has('rexp.form.service.target_resolver')) {
            $locator->addInstance('rexp.form.service.target_resolver', new FormTargetResolverService(
                $locator->get('rexp.form.service.field_type_registry'),
                $locator->get('rexp.form.service.rule_compiler')
            ));
        }

        if (!$locator->has('rexp.form.service.draft')) {
            $locator->addInstance('rexp.form.service.draft', new FormDraftService(
                $locator->get('rexp.form.repository.draft'),
                $locator->get('rexp.form.event.dispatcher'),
                $locator->get('rexp.form.service.module_options')
            ));
        }

        if (!$locator->has('rexp.form.service.read')) {
            $locator->addInstance('rexp.form.service.read', new FormReadService(
                $locator->get('rexp.form.repository.form'),
                $locator->get('rexp.form.service.draft'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.service.target_resolver')
            ));
        }

        if (!$locator->has('rexp.form.service.publish')) {
            $locator->addInstance('rexp.form.service.publish', new FormPublishService(
                $locator->get('rexp.form.repository.form'),
                $locator->get('rexp.form.repository.version'),
                $locator->get('rexp.form.service.draft'),
                $locator->get('rexp.form.event.dispatcher'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.service.target_resolver'),
                $locator->get('rexp.form.service.module_options')
            ));
        }

        if (!$locator->has('rexp.form.service.template.preview')) {
            $locator->addInstance('rexp.form.service.template.preview', new TemplatePreviewService());
        }

        if (!$locator->has('rexp.form.service.template.catalog')) {
            $locator->addInstance('rexp.form.service.template.catalog', new FormTemplateCatalogService(
                $locator->get('rexp.form.service.template.preview')
            ));
        }

        if (!$locator->has('rexp.form.service.template.create')) {
            $locator->addInstance('rexp.form.service.template.create', new FormTemplateCreateService(
                $locator->get('rexp.form.service.template.catalog'),
                $locator->get('rexp.form.repository.form'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.event.dispatcher'),
                $locator->get('rexp.form.service.target_resolver')
            ));
        }

        if (!$locator->has('rexp.form.service.template')) {
            $locator->addInstance('rexp.form.service.template', new FormTemplateService(
                $locator->get('rexp.form.service.template.preview'),
                $locator->get('rexp.form.repository.form'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.event.dispatcher')
            ));
        }

        if (!$locator->has('rexp.form.service.export')) {
            $locator->addInstance('rexp.form.service.export', new FormExportService(
                $locator->get('rexp.form.event.dispatcher')
            ));
        }

        if (!$locator->has('rexp.form.service.import')) {
            $locator->addInstance('rexp.form.service.import', new FormImportService(
                $locator->get('rexp.form.repository.form'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.event.dispatcher'),
                $locator->get('rexp.form.service.target_resolver')
            ));
        }

        if (!$locator->has('rexp.form.service.import_export')) {
            $locator->addInstance('rexp.form.service.import_export', new FormImportExportService(
                $locator->get('rexp.form.repository.form'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.event.dispatcher'),
                $locator->get('rexp.form.service.target_resolver')
            ));
        }

        if (!$locator->has('rexp.form.service.mock_diagnostics')) {
            $locator->addInstance('rexp.form.service.mock_diagnostics', new MockSubmitDiagnosticsService());
        }




        if (!$locator->has('rexp.form.service.manager')) {
            $locator->addInstance('rexp.form.service.manager', new FormManagerService(
                $locator->get('rexp.form.service.read'),
                $locator->get('rexp.form.service.publish')
            ));
        }

        if (!$locator->has('rexp.form.service.version')) {
            $locator->addInstance('rexp.form.service.version', new FormVersionService(
                $locator->get('rexp.form.service.read'),
                $locator->get('rexp.form.service.publish'),
                $locator->get('rexp.form.repository.version'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.event.dispatcher')
            ));
        }

        if (!$locator->has('rexp.form.service.diagnostics')) {
            $locator->addInstance('rexp.form.service.diagnostics', new FormDiagnosticsService(
                $locator->get('rexp.form.repository.form')
            ));
        }


        if (!$locator->has('rexp.form.service.settings')) {
            $locator->addInstance('rexp.form.service.settings', new FormSettingsService(
                $locator->get('rexp.form.service.permission')
            ));
        }


        if (!$locator->has('rexp.form.usecase.designer.form')) {
            $locator->addInstance('rexp.form.usecase.designer.form', new DesignerFormUseCase(
                $locator->get('rexp.form.service.read'),
                $locator->get('rexp.form.service.publish'),
                $locator->get('rexp.form.service.draft'),
                $locator->get('rexp.form.service.template.catalog'),
                $locator->get('rexp.form.service.template.create'),
                $locator->get('rexp.form.service.import'),
                $locator->get('rexp.form.service.export'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.service.target_resolver')
            ));
        }

        if (!$locator->has('rexp.form.service.state_asset')) {
            $locator->addInstance('rexp.form.service.state_asset', new FormStateAssetService());
        }

        if (!$locator->has('rexp.form.usecase.designer.metadata')) {
            $locator->addInstance('rexp.form.usecase.designer.metadata', new DesignerMetadataUseCase(
                $locator->get('rexp.form.service.read'),
                $locator->get('rexp.form.service.permission'),
                $locator->get('rexp.form.service.diagnostics'),
                $locator->get('rexp.form.service.settings'),
                $locator->get('rexp.form.service.state_asset'),
                $locator->get('rexp.form.service.target_resolver'),
                $locator->get('rexp.form.service.userfield_adapter')
            ));
        }

        if (!$locator->has('rexp.form.usecase.designer.history')) {
            $locator->addInstance('rexp.form.usecase.designer.history', new DesignerHistoryUseCase(
                $locator->get('rexp.form.service.read'),
                $locator->get('rexp.form.service.version'),
                $locator->get('rexp.form.service.draft')
            ));
        }

        if (!$locator->has('rexp.form.usecase.designer.submit')) {
            $locator->addInstance('rexp.form.usecase.designer.submit', new DesignerSubmitUseCase(
                $locator->get('rexp.form.service.read'),
                $locator->get('rexp.form.service.mock_diagnostics')
            ));
        }


        if (!$locator->has('rexp.form.service.runtime.value_normalizer')) {
            $locator->addInstance('rexp.form.service.runtime.value_normalizer', new RuntimeValueNormalizerService());
        }

        if (!$locator->has('rexp.form.service.runtime.condition_evaluator')) {
            $locator->addInstance('rexp.form.service.runtime.condition_evaluator', new RuntimeConditionEvaluatorService(
                $locator->get('rexp.form.service.runtime.value_normalizer')
            ));
        }

        if (!$locator->has('rexp.form.service.runtime.rule_evaluator')) {
            $locator->addInstance('rexp.form.service.runtime.rule_evaluator', new RuntimeRuleEvaluatorService(
                $locator->get('rexp.form.service.runtime.condition_evaluator'),
                $locator->get('rexp.form.service.runtime.value_normalizer')
            ));
        }


        if (!$locator->has('rexp.form.service.runtime.field_validation')) {
            $locator->addInstance('rexp.form.service.runtime.field_validation', new RuntimeFieldValidationService(
                $locator->get('rexp.form.service.runtime.rule_evaluator')
            ));
        }

        if (!$locator->has('rexp.form.service.runtime.file')) {
            $locator->addInstance('rexp.form.service.runtime.file', new RuntimeFileService());
        }



        if (!$locator->has('rexp.form.service.runtime.directory')) {
            $locator->addInstance('rexp.form.service.runtime.directory', new RuntimeDirectoryService());
        }

        if (!$locator->has('rexp.form.service.runtime.public_form_builder')) {
            $locator->addInstance('rexp.form.service.runtime.public_form_builder', new RuntimePublicFormBuilderService(
                $locator->get('rexp.form.service.target_resolver'),
                $locator->get('rexp.form.service.runtime.directory'),
                $locator->get('rexp.form.service.runtime.value_normalizer'),
                $locator->get('rexp.form.service.runtime.condition_evaluator'),
                $locator->get('rexp.form.service.runtime.rule_evaluator')
            ));
        }








        if (!$locator->has('rexp.form.service.runtime_dynamic_options')) {
            $locator->addInstance('rexp.form.service.runtime_dynamic_options', new RuntimeDynamicOptionsService());
        }

        if (!$locator->has('rexp.form.service.runtime_submission')) {
            $locator->addInstance('rexp.form.service.runtime_submission', new RuntimeSubmissionService(
                $locator->get('rexp.form.service.module_options')
            ));
        }

        if (!$locator->has('rexp.form.service.runtime.bizproc')) {
            $locator->addInstance('rexp.form.service.runtime.bizproc', new RuntimeBizprocService());
        }

        if (!$locator->has('rexp.form.service.runtime')) {
            $locator->addInstance('rexp.form.service.runtime', new FormRuntimeService(
                $locator->get('rexp.form.service.runtime.public_form_builder'),
                $locator->get('rexp.form.service.runtime_submission'),
                $locator->get('rexp.form.service.runtime.bizproc'),
                $locator->get('rexp.form.service.runtime.field_validation'),
                $locator->get('rexp.form.service.runtime.file'),
                $locator->get('rexp.form.service.permission')
            ));
        }
    }
}
