<?php

namespace Siarko\DependencyManager\Config\Init\Merge;

use Siarko\DependencyManager\Config\DMKeys;

class ConfigMerger extends \Siarko\ConfigFiles\Merger\ConfigMerger
{
    /**
     * Almost the same as array_replace_recursive, but numeric keys are not replaced
     * @param array $base
     * @param array $override
     * @return array
     */
    public function merge(array $base, array $override): array
    {
        unset($override[DMKeys::ID]);
        unset($override[DMKeys::DEPENDENCIES]);
        return parent::merge($base, $override);
    }


}