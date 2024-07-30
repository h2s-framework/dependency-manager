<?php

namespace Siarko\DependencyManager\Paths\Files\Parsers;

use Siarko\DependencyManager\Type\TypedValue;
use Siarko\Files\Api\FileInterface;
use Symfony\Component\Yaml\Tag\TaggedValue;

class YamlConfigParser extends YamlFileParser
{

    /**
     * @param FileInterface $file
     * @return mixed
     */
    function parse(FileInterface $file): mixed
    {
        return $this->postProcess(parent::parse($file));
    }


    /**
     * @param array $result
     * @return mixed
     */
    private function postProcess(array $result): array
    {
        foreach ($result as $key => $value) {
            if($value instanceof TaggedValue){
                $result[$key] = new TypedValue($value->getTag(), $value->getValue());
            }
            if(is_array($value)){
                $result[$key] = $this->postProcess($value);
            }
        }
        return $result;
    }
}