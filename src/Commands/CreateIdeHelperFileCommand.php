<?php

namespace Devront\AdvancedStatistics\Commands;

use Devront\AdvancedStatistics\AdvancedStatistics;
use Devront\AdvancedStatistics\Statistics;
use Illuminate\Console\Command;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Printer;

class CreateIdeHelperFileCommand extends Command
{
    protected $signature = 'ide-helper:advanced-statistics';

    protected $description = 'Generates an ide helper file for better auto-completion in your statistics files.';

    public function handle()
    {
        $content = "";
        $namespaces = [];
        foreach (app(AdvancedStatistics::class)->statistics as $statistics_class) {
            $statistics_instance = new $statistics_class;
            if (!is_a($statistics_instance, Statistics::class)) continue;

            $r = new \ReflectionClass($statistics_instance);
            $namespace = $r->getNamespaceName();
            if (!isset($namespaces[$namespace])) {
                $namespaces[$namespace] = [];
            }

            $class = new ClassType($r->getShortName());

            $class->setExtends(Statistics::class);

            $params = $statistics_instance->getParams();
            foreach ($params as $param) {
                $methodName = 'for' . ucfirst(\Illuminate\Support\Str::camel($param));
                $class->addComment("@method self $methodName() $methodName(\$value)");
            }

            $printer = new Printer();

            $namespaces[$namespace][] = $printer->printClass($class);
        }

        $content .= "<?php \n \n";
        $content .= "// @formatter:off \n";
        foreach ($namespaces as $namespace => $classes) {
            $content .= "namespace $namespace{ \n";
            foreach ($classes as $class) {
                $content .= $class . "\n";
            }
            $content .= "}";
        }

        file_put_contents(base_path('_ide_helper_statistics.php'), $content);
    }
}
