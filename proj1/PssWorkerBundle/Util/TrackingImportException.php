<?php
/**
 * @author Alexey Kosmachev alex.kosmachev@itdelight.com
 */

namespace App\PssWorkerBundle\Util;

use Throwable;

class TrackingImportException extends \Exception
{
    private $content;
    public function __construct(string $message = "", array $content = [], int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->content = $content;
    }
    public function getContent()
    {
        return $this->content;
    }

}