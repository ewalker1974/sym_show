<?php


namespace App\ElasticsearchDSL\Utils\Annotation;

/**
 * Annotation used to check mapping type during the parsing process.
 *
 * @Annotation
 * @Target("METHOD")
 */
class SearchMap
{
    public $name;
}
