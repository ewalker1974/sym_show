<?php

namespace App\ElasticsearchDSL\Utils;

use ONGR\ElasticsearchBundle\Mapping\Caser;
use Doctrine\Common\Annotations\Reader;
use App\ElasticsearchDSL\Utils\Annotation\SearchMap;
use App\ElasticsearchDSL\AbstractSearch;

class SearchMetaParser
{
    private $reader;
    private $maps = [];

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }
    public function parse(\ReflectionClass $class)
    {
        $className = $class->getName();

        if (!$class->isSubclassOf(AbstractSearch::class)) {
            throw new \LogicException('Search class should be subclass of '.AbstractSearch::class);
        }

        if (!isset($this->maps[$className])) {
            $this->maps[$className] = $this->getQueryMethods($class);
        }
        return $this->maps[$className];
    }

    private function getQueryMethods(\ReflectionClass $reflectionClass)
    {
        if (in_array($reflectionClass->getName(), $this->maps)) {
            return $this->maps[$reflectionClass->getName()];
        }

        $maps = [];

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_FINAL | \ReflectionMethod::IS_PUBLIC)
                 as $name => $method) {
            $methodName = $method->getName();
            $annotation = $this->getMethodAnnotationData($method);
            if ($annotation) {
                $maps[$annotation->name] = $methodName;
            }
        }

        $this->maps[$reflectionClass->getName()] = $maps;

        return $maps;
    }

    private function getMethodAnnotationData(\ReflectionMethod $method)
    {
        $result = $this->reader->getMethodAnnotation($method, SearchMap::class);

        if ($result !== null && $result->name === null) {
            $result->name = Caser::snake($method->getName());
        }
        return $result;
    }
}
