<?php

use Devront\AdvancedStatistics\AdvancedStatistics;
use Devront\AdvancedStatistics\Statistics;
use Nette\PhpGenerator\ClassType;

it('can generate ide helper code', function () {
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

        $s = new \Devront\AdvancedStatistics\Tests\Attributes\OrderStatistics();
        $s->forSource('');

        $class->setExtends(Statistics::class);

        $params = $statistics_instance->getParams();
        foreach ($params as $param) {
            $methodName = 'for' . ucfirst(\Illuminate\Support\Str::camel($param));
            $class->addComment("@method self $methodName() $methodName(\$value)");
        }

        $printer = new Nette\PhpGenerator\Printer;

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

    echo $content;
});
