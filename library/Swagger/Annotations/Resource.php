<?php

namespace Swagger\Annotations;

/**
 * @license    http://www.apache.org/licenses/LICENSE-2.0
 *             Copyright [2013] [Robert Allen]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @package
 * @category
 * @subpackage
 */
use Swagger\Logger;

/**
 * @package
 * @category
 * @subpackage
 *
 * @Annotation
 */
class Resource extends AbstractAnnotation
{
    /**
     * The basePath is the end-point of your API.
     * Not the Developer or the  Admin Portal but the endpoint that serves your API requests.
     * @var string
     */
    public $basePath;

    /**
     * the version of Swagger
     * @var string
     */
    public $swaggerVersion;

    /**
     * The version of your API
     * @var string
     */
    public $apiVersion;

    /**
     * @var "/store"
     */
    public $resourcePath;

    /**
     * @var array|Api
     */
    public $apis = array();

    /**
     * @var array
     */
    public $models = array();

    public function setNestedAnnotations($annotations)
    {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Api) {
                $this->apis[] = $annotation;
            } else {
                Logger::notice('Unexpected '.get_class($annotation).' in a '.get_class($this).' in '.AbstractAnnotation::$context);
            }
        }
    }

    public function validate()
    {
        if ($this->swaggerVersion) {
            if (version_compare($this->swaggerVersion, '1.2', '<')) {
                Logger::warning('swaggerVersion: '.$this->swaggerVersion.' not supported. Use a swagger-php older than 0.8');
                $this->swaggerVersion = null;
            }
        }
        $apis = array();
        foreach ($this->apis as $api) {
            if ($api->validate()) {
                $append = true;
                foreach ($apis as $validApi) {
                    if ($validApi->path === $api->path && $validApi->description === $api->description) { // A similar api call?
                        $append = false;
                        // merge operations
                        foreach ($api->operations as $operation) {
                            $validApi->operations[] = $operation;
                        }
                    }
                }
                if ($append) {
                    $apis[] = $api;
                }
            }
        }
        if (count($apis) === 0 && count($this->_partials) === 0) {
            Logger::notice('Resource "'.$this->basePath.'" doesn\'t have any valid api calls');
            return false;
        }
        $this->apis = $apis;
        return true;
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        if (count($this->models) === 0) {
            unset($data['models']);
        }
        return $data;
    }

    /**
     * @param Resource $resource
     */
    public function merge($resource)
    {
        foreach ($resource->apis as $api) {
            $this->apis[] = $api;
        }
        $this->validate();
    }
}