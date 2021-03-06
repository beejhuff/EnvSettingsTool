<?php

class Est_Processor {

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $settingsFilePath;

    /**
     * @var array
     */
    protected $handlerCollection;

    /**
     * @var array
     */
    protected $groups = array();

    /**
     * @var array
     */
    protected $excludeGroups = array();


    /**
     * Constructor
     *
     * @param $environment
     * @param $settingsFilePath
     * @param array $options
     * @throws InvalidArgumentException
     */
    public function __construct($environment, $settingsFilePath, array $options=array()) {
        if (empty($environment)) {
            throw new InvalidArgumentException('No environment parameter set.');
        }
        if (empty($settingsFilePath)) {
            throw new InvalidArgumentException('No settings file set.');
        }
        if (!file_exists($settingsFilePath)) {
            throw new InvalidArgumentException('Could not read settings file.');
        }

        $this->environment = $environment;
        $this->settingsFilePath = $settingsFilePath;
        $this->handlerCollection = new Est_HandlerCollection();

        if (isset($options['groups'])) {
            $this->groups = Est_Div::trimExplode(',', $options['groups'], true);
        }
        if (isset($options['excludeGroups'])) {
            $this->excludeGroups = Est_Div::trimExplode(',', $options['excludeGroups'], true);
        }
    }

    /**
     * Apply settings to current environment
     *
     * @throws Exception
     * @return bool
     */
    public function apply() {
        $this->handlerCollection->buildFromSettingsCSVFile($this->settingsFilePath, $this->environment, 'DEFAULT', $this->groups, $this->excludeGroups);
        foreach ($this->handlerCollection as $handler) { /* @var $handler Est_Handler_Abstract */
            $res = $handler->apply();
            if (!$res) {
                throw new Exception('Error in handler: ' . $handler->getLabel());
            }
        }
        return true;
    }

    /**
     * Apply settings to current environment
     *
     * @throws Exception
     * @return bool
     */
    public function dryRun() {
        $this->handlerCollection->buildFromSettingsCSVFile($this->settingsFilePath, $this->environment, 'DEFAULT', $this->groups, $this->excludeGroups);
        foreach ($this->handlerCollection as $handler) { /* @var $handler Est_Handler_Abstract */
            $this->output($handler->getLabel());
        }
        return true;
    }

    /**
     * Get value
     *
     * @param $handlerClassName
     * @param $param1
     * @param $param2
     * @param $param3
     * @throws Exception
     * @internal param $handler
     * @return Est_Handler_Abstract
     */
    public function getHandler($handlerClassName, $param1, $param2, $param3) {
        $this->handlerCollection->buildFromSettingsCSVFile($this->settingsFilePath,$this->environment);
        $handler = $this->handlerCollection->getHandler($handlerClassName, $param1, $param2, $param3);
        if ($handler === false) {
            throw new Exception('No handler found with given specification: '."$handlerClassName, $param1, $param2, $param3");
        }
        return $handler;
    }

    /**
     * Print result
     */
    public function printResults() {
        $statistics = array();
        foreach ($this->handlerCollection as $handler) { /* @var $handler Est_Handler_Abstract */
            // Collecting some statistics
            $statistics[$handler->getStatus()][] = $handler;

            // skipping handlers that weren't executed
            if ($handler->getStatus() == Est_Handler_Interface::STATUS_NOTEXECUTED) {
                continue;
            }

            $this->output();
            $label = $handler->getLabel();
            $this->output($label);
            $this->output(str_repeat('-', strlen($label)));

            foreach ($handler->getMessages() as $message) { /* @var $message Est_Message */
                $this->output($message->getColoredText());
            }
        }

        $this->output();
        $this->output('Status summary:');
        $this->output(str_repeat('=', strlen(('Status summary:'))));

        foreach ($statistics as $status => $handlers) {
            $this->output(sprintf("%s: %s handler(s)", $status, count($handlers)));
        }

        $this->output();
        if (count($this->groups)) {
            $this->output('Groups: ' . implode(', ', $this->groups));
        }
        if (count($this->excludeGroups)) {
            $this->output('Excluded groups: ' . implode(', ', $this->excludeGroups));
        }

    }

    protected function output($message='', $newLine=true) {
        echo $message;
        if ($newLine) {
            echo "\n";
        }
    }

}