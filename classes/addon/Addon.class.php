<?php
    /**
     * Copyright 2018 Cameron Wolfe
     *
     * Licensed under the Apache License, Version 2.0 (the "License");
     * you may not use this file except in compliance with the License.
     * You may obtain a copy of the License at
     *
     * http://www.apache.org/licenses/LICENSE-2.0
     *
     * Unless required by applicable law or agreed to in writing, software
     * distributed under the License is distributed on an "AS IS" BASIS,
     * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
     * See the License for the specific language governing permissions and
     * limitations under the License.
     */

    namespace Cameron\XenoPanel\Addons\Core;

    use Cameron\XenoPanel\Addons\Core\Addon\AddonType;
    use Cameron\XenoPanel\Addons\Core\Configuration\Configuration;
    use Cameron\XenoPanel\Addons\Core\Routing\Routing;

    /**
     * Class Addon
     *
     * @package Cameron\XenoPanel\Addons\Core
     */
    abstract class Addon {

        public $Core;
        public $database;

        /** @var string */
        private $name;
        /** @var String */
        private $title;
        /** @var string */
        private $version;
        /** @var AddonType */
        private $type;
        /** @var array */
        private $authors;
        /** @var Configuration */
        protected $config;
        /** @var Routing */
        private $routing;
        /** @var bool */
        private $premium;
        /** @var string */
        private $license = null;
        /** @var bool */
        private $license_valid = false;
        /** @var string */
        private $directory;
        /** @var string */
        private $root_directory;

        public function __construct($name, $title, $version, $authors, $type, $premium = false) {
            $this->name = $name;
            $this->title = $title;
            $this->version = $version;
            $this->authors = $authors;
            $this->type = $type;
            $this->premium = $premium;
        }

        /**
         * @param $table  string Table to check
         * @param $column array The column(s) to check for
         * @param $value  array
         *
         * @return bool Whether the column exists in the database
         */
        public function rowExistsInDatabase($table, $column, $value) {

            if ($this->database == null) {
                $this->database = $GLOBALS['database'];
            }


            $search = [];
            for ($i = 0; $i < sizeof($column); $i++) {
                $search[ $column[ $i ] ] = $value[ $i ];
            }

            return isset($this->database->select($table, $column, $search)[0]);
        }

        /**
         * @return bool
         */
        public function isLicenseValid() {
            return $this->license_valid;
        }

        /**
         * Initialize the addon
         */
        public function initialize() {
            $this->Core = isset($GLOBALS['Core']) ? $GLOBALS['Core'] : new \CoreAPIV2;

            $this->root_directory = __DIR__ . '/';

            while (!file_exists($this->getRootDirectory() . 'composer.json') && !file_exists($this->getRootDirectory() . 'includes/config.cfg')) {
                $this->root_directory .= '../';
            }
            $this->root_directory = realpath($this->root_directory) . '/';

            $this->directory = realpath($this->getRootDirectory() . 'addons/' . $this->getName() . '/') . '/';

            if ($this->isPremium()) {
                if (file_exists($this->getDirectory() . '.license')) {
                    $this->setLicense(file_get_contents($this->getDirectory() . '.license'));

                    /**
                     * Waiting to implement the real checker. Depending on how it's implemented on Xeno's level, we can
                     * have free addons with premium features built in, allowing us to have only one project to manage
                     * instead of multiple
                     */
                    $this->license_valid = true;
                } else {
                    file_put_contents($this->getDirectory() . '.license', 'Your license here :)');
                }
            }

            if ($this->onLoad()) $this->onEnable();
        }

        /**
         * @return string
         */
        public function getRootDirectory() {
            return $this->root_directory;
        }

        /**
         * Check whether the addon is a premium plugin or not.
         *
         * @return bool
         */
        public function isPremium() {
            return $this->premium;
        }

        /**
         * Get the directory of the addon
         *
         * @return string
         */
        public function getDirectory() {
            return $this->directory;
        }

        /**
         * @return bool Whether we loaded successfully
         */
        public function onLoad() {
            $this->config = new Configuration($this);
            $this->routing = new Routing($this);

            return true;
        }

        /**
         * @return Configuration
         */
        public function getConfig() {
            return $this->config;
        }

        /**
         * @return Routing
         */
        public function getRouting() {
            return $this->routing;
        }

        /**
         * @return bool Whether we enabled successfully
         */
        public function onEnable() { return true; }

        public function install() {
            if ($this->database == null) {
                $this->database = $GLOBALS['database'];
            }

            $this->database->insert('addons_list', [
                "status"  => "disabled",
                "name"    => $this->getTitle(),
                "image"   => "demo",
                "short"   => $this->getName(),
                "version" => $this->getVersion()
            ]);

        }

        /**
         * Run after the initial install process. Reserve this for our addon, the initial addon is for more XenoPanel
         * related stuff
         */
        public function postInstall() {
            $this->config->saveDefaultConfig();
            $this->routing->saveDefaultRoutes();
        }

        /**
         * @return String
         */
        public function getTitle() {
            return $this->title;
        }

        /**
         * @return string
         */
        public function getVersion() {
            return strtoupper($this->version);
        }

        /**
         * @param string $version
         */
        public function setVersion($version) {
            if ($this->version == $version) return;
            $this->version = $version;
            $this->database->update('addons_list', ['version' => $this->getVersion()], ['short' => $this->getName()]);
        }

        /**
         * @return string
         */
        public function getName() {
            return strtolower($this->name);
        }

        /**
         * Get the config required for XenoPanel to load the addon
         *
         * @param array $extra Extra parameters that needed to be loaded into the config array
         *
         * @return array
         */
        public function getAddonConfig($extra = []) {
            $config = [];
            $config['name'] = $this->getName();
            $config['title'] = $this->getTitle();
            $config['version'] = $this->getVersion();
            $config['authors'] = $this->getAuthors();
            $config['type'] = AddonType::toString($this->getType());
            $config['title'] = $this->getTitle();
            $config['location'] = 'page';

            // Licensing currently doesn't exist, and is pending discussion with Liam of how it will be implemented.
            if ($this->getLicense() != null) $config['license'] = $this->getLicense();

            return array_merge($config, $extra);
        }

        /**
         * @return array
         */
        public function getAuthors() {
            return $this->authors;
        }

        /**
         * @return AddonType
         */
        public function getType() {
            return $this->type;
        }

        /**
         * @return string
         */
        public function getLicense() {
            return $this->license;
        }

        /**
         * @param string $license
         */
        public function setLicense($license) {
            $this->license = $license;
            file_put_contents($this->getDirectory() . '.license', $this->getLicense());
        }

        /**
         * @return string Get formatted author list
         */
        public function getAuthor() {
            $author = "";
            foreach ($this->getAuthors() as $_author) {
                $author .= ", " . $_author;
            }

            return substr($author, strlen(", "));
        }

        /**
         * @return bool Development version
         */
        public function isVersionDev() {
            return str_ends_with($this->getVersion(), '-DEV') || str_ends_with($this->getVersion(), '-DEVELOPMENT');
        }

        /**
         * @return bool Snapshot version
         */
        public function isVersionSnapshot() {
            return str_ends_with($this->getVersion(), '-SNAPSHOT');
        }

        /**
         * @return bool Alpha version
         */
        public function isVersionAlpha() {
            return str_ends_with($this->getVersion(), '-ALPHA');
        }

        /**
         * @return bool Beta version
         */
        public function isVersionBeta() {
            return str_ends_with($this->getVersion(), '-BETA');
        }

        /**
         * @return bool Release version
         */
        public function isVersionRelease() {
            return str_ends_with($this->getVersion(), '-RELEASE');
        }

        /**
         * Log debug information to the addons logging file.
         *
         * @param $debug string Debug to log
         */
        public function debug($debug) {
            $this->Core->debug($debug);
        }

        /**
         * Log information to the addons logging file.
         *
         * @param $info string Information to log
         */
        public function info($info) {
            $this->Core->info($info);
        }

        /**
         * Log an error to the addons logging file.
         *
         * @param $error string Error to log
         */
        public function error($error) {
            $this->Core->error($error);
        }

    }