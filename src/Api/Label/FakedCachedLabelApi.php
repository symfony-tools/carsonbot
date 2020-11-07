<?php

namespace App\Api\Label;



use App\Model\Repository;

class FakedCachedLabelApi extends CachedLabelsApi
{
    public function getComponentLabelsForRepository(Repository $repository): array
    {
        return [
            'Asset', 'BrowserKit', 'Cache', 'Config', 'Console',
            'Contracts', 'CssSelector', 'Debug', 'DebugBundle', 'DependencyInjection',
            'Doctrine', 'DoctrineBridge', 'DomCrawler', 'Dotenv',
            'Enhancement', 'ErrorHandler', 'EventDispatcher', 'ExpressionLanguage',
            'Feature', 'Filesystem', 'Finder', 'Form', 'FrameworkBundle',
            'HttpClient', 'HttpFoundation', 'HttpKernel', 'Inflector', 'Intl', 'Ldap',
            'Locale', 'Lock', 'Mailer', 'Messenger', 'Mime', 'MonologBridge', 'Notifier',
            'OptionsResolver', 'PhpUnitBridge', 'Process', 'PropertyAccess',
            'PropertyInfo', 'ProxyManagerBridge', 'Routing', 'Security',
            'SecurityBundle', 'Serializer', 'Stopwatch', 'String', 'Templating',
            'Translator', 'TwigBridge', 'TwigBundle', 'Uid', 'Validator', 'VarDumper',
            'VarExporter', 'WebLink', 'WebProfilerBundle', 'WebServerBundle', 'Workflow',
            'Yaml',
        ];
    }

    public function getAllLabelsForRepository(Repository $repository): array
    {
        $labels = $this->getComponentLabelsForRepository($repository);
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
}
