<?php
    /**
     *    Copyright 2018 Cameron Wolfe
     *
     *    Licensed under the Apache License, Version 2.0 (the "License");
     *    you may not use this file except in compliance with the License.
     *    You may obtain a copy of the License at
     *
     *        http://www.apache.org/licenses/LICENSE-2.0
     *
     *    Unless required by applicable law or agreed to in writing, software
     *    distributed under the License is distributed on an "AS IS" BASIS,
     *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     *    See the License for the specific language governing permissions and
     *    limitations under the License.
     */

    namespace Cameron\XenoPanel\Addons\Core;

    require __DIR__ . '/classes/addon/Addon.class.php';

    use Cameron\XenoPanel\Addons\Core\Addon\AddonType;

    class AddonCore extends Addon {

        public function __construct() {
            parent::__construct("_core", "Cameron's Core", "1.0.0-DEV", ["Cameron Wolfe"], AddonType::HEADER);
        }

    }

    $Ccore = new AddonCore();
    $Ccore->initialize();