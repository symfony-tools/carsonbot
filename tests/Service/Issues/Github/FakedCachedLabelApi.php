<?php

declare(strict_types=1);

namespace App\Tests\Service\Issues\Github;

use App\Issues\GitHub\CachedLabelsApi;
use App\Repository\Repository;

class FakedCachedLabelApi extends CachedLabelsApi
{
    public function getAllLabelsForRepository(Repository $repository): array
    {
        return [
            'Asset', 'BC Break', 'BrowserKit', 'Bug', 'Cache', 'Config', 'Console',
            'Contracts', 'Critical', 'CssSelector', 'Debug', 'DebugBundle', 'DependencyInjection',
            'Deprecation', 'Doctrine', 'DoctrineBridge', 'DomCrawler', 'Dotenv',
            'DX', 'Enhancement', 'ErrorHandler', 'EventDispatcher', 'ExpressionLanguage',
            'Feature', 'Filesystem', 'Finder', 'Form', 'FrameworkBundle', 'Hack Day',
            'HttpClient', 'HttpFoundation', 'HttpKernel', 'Inflector', 'Intl', 'Ldap',
            'Locale', 'Lock', 'Mailer', 'Messenger', 'Mime', 'MonologBridge', 'Notifier',
            'OptionsResolver', 'Performance', 'PhpUnitBridge', 'Process', 'PropertyAccess',
            'PropertyInfo', 'ProxyManagerBridge', 'RFC', 'Routing', 'Security',
            'SecurityBundle', 'Serializer', 'Stopwatch', 'String', 'Templating',
            'Translator', 'TwigBridge', 'TwigBundle', 'Uid', 'Validator', 'VarDumper',
            'VarExporter', 'WebLink', 'WebProfilerBundle', 'WebServerBundle', 'Workflow',
            'Yaml',
        ];
    }
}
