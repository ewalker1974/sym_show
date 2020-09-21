<?php

namespace App\Controller\API\Annotations;

use Doctrine\Common\Annotations\Reader;
use FOS\RestBundle\Controller\Annotations\ParamInterface;
use FOS\RestBundle\Request\ParamReaderInterface;

class FosRestParamReader implements ParamReaderInterface
{
    private $annotationReader;

    /**
     * Initializes controller reader.
     *
     * @param Reader $annotationReader
     */
    public function __construct(Reader $annotationReader)
    {
        $this->annotationReader = $annotationReader;
    }

    /**
     * {@inheritdoc}
     */
    public function read(\ReflectionClass $reflection, $method)
    {
        if (!$reflection->hasMethod($method)) {
            throw new \InvalidArgumentException(sprintf("Class '%s' has no method '%s'.", $reflection->getName(), $method));
        }

        $methodParams = $this->getParamsFromMethod($reflection->getMethod($method));
        $classParams = $this->getParamsFromClass($reflection);

        return array_merge($methodParams, $classParams);
    }

    /**
     * {@inheritdoc}
     */
    public function getParamsFromMethod(\ReflectionMethod $method)
    {
        $annotations = $this->annotationReader->getMethodAnnotations($method);

        return $this->getParamsFromAnnotationArray($annotations);
    }

    /**
     * {@inheritdoc}
     */
    public function getParamsFromClass(\ReflectionClass $class)
    {
        $annotations = $this->annotationReader->getClassAnnotations($class);

        return $this->getParamsFromAnnotationArray($annotations);
    }

    /**
     * Fetches parameters from provided annotation array (fetched from annotationReader).
     *
     * @param array $annotations
     *
     * @return ParamInterface[]
     */
    protected function getParamsFromAnnotationArray(array $annotations)
    {
        $params = array();
        foreach ($annotations as $annotation) {
            if ($annotation instanceof IncludeRestAnnotations) {
                $params = array_merge(
                    $params,
                    $this->read(
                        new \ReflectionClass($annotation->class),
                        $annotation->method
                    )
                );
            }
            if ($annotation instanceof ParamInterface) {
                $params[$annotation->getName()] = $annotation;
            }
        }

        return $params;
    }
}
