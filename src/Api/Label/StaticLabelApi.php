<?php

namespace App\Api\Label;

use App\Model\Repository;

/**
 * Dont fetch data from external source.
 *
 * This class is only used in tests.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class StaticLabelApi extends NullLabelApi
{
    private const array LABELS = [
        'Asset', 'AssetMapper', 'BrowserKit', 'Cache', 'Config', 'Console',
        'Contracts', 'CssSelector', 'Debug', 'DebugBundle', 'DependencyInjection',
        'Doctrine', 'DoctrineBridge', 'DomCrawler', 'Dotenv', 'Emoji',
        'Enhancement', 'ErrorHandler', 'EventDispatcher', 'ExpressionLanguage',
        'Feature', 'Filesystem', 'Finder', 'Form', 'FrameworkBundle',
        'HttpClient', 'HttpFoundation', 'HttpKernel', 'Inflector', 'Intl', 'JsonPath', 'JsonStreamer', 'Ldap',
        'Locale', 'Lock', 'Mailer', 'Messenger', 'Mime', 'MonologBridge', 'Notifier', 'ObjectMapper',
        'OptionsResolver', 'PasswordHasher', 'PhpUnitBridge', 'Process', 'PropertyAccess',
        'PropertyInfo', 'ProxyManagerBridge', 'PsrHttpMessageBridge', 'RemoteEvent', 'Routing',
        'Scheduler', 'Security', 'SecurityBundle', 'Serializer', 'Stopwatch', 'String',
        'Templating', 'Translation', 'TwigBridge', 'TwigBundle', 'TypeInfo', 'Uid', 'Validator', 'VarDumper',
        'VarExporter', 'Webhook', 'WebLink', 'WebProfilerBundle', 'WebServerBundle', 'Workflow',
        'Yaml',
    ];

    public function getAllLabelsForRepository(Repository $repository): array
    {
        $labels = self::LABELS;
        $labels[] = 'BC Break';
        $labels[] = 'Bug';
        $labels[] = 'Critical';
        $labels[] = 'Hack Day';
        $labels[] = 'RFC';
        $labels[] = 'Performance';
        $labels[] = 'DX';
        $labels[] = 'Deprecation';

        return $labels;
    }

    public function getIssueLabels(int $issueNumber, Repository $repository): array
    {
        return [];
    }
}
