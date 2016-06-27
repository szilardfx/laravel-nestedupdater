<?php
namespace Czim\NestedModelUpdater\Exceptions;

trait StoresNestedKeyTrait
{

    /**
     * Dot-notation nested key for the record in the data array
     *
     * @var string
     */
    protected $nestedKey;

    /**
     * Set the dot-notation nested key for the affected model
     *
     * @param  string   $nestedKey
     * @return $this
     */
    public function setNestedKey($nestedKey)
    {
        $this->nestedKey = $nestedKey;

        if ($nestedKey) {
            $this->message .= " (nesting: {$nestedKey})";
        }

        return $this;
    }

    /**
     * Get the dot-notation nested key for the affected model
     *
     * @return string
     */
    public function getNestedKey()
    {
        return $this->nestedKey;
    }

}