<?php

abstract class shopGeneratorPluginReader implements Serializable
{
    private $options = array();
    protected $offset = array();
    protected $updated = array('products'=>0);
    private $temp_path;

    function __construct($options)
    {
        foreach ($options as $k => $v) {
            $this->setOption($k, $v);
        }
    }

    /**
     * @param string $stage
     * @return string
     */
    abstract public function getStageName($stage);

    /**
     * @param string $stage
     * @param array $data
     * @return string
     */
    abstract public function getStageReport($stage, $data);

    abstract public function count();

    abstract public function step(&$current, &$count, &$processed, $stage, &$error);

    public function init()
    {

    }

    public function finish()
    {
        waFiles::delete($this->getTempPath(), true);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        $data = array();
        $data['offset'] = $this->offset;
        $data['updated'] = $this->updated;
        $data['options'] = array();
        foreach ($this->options as $name=>&$option) {
            if(isset($option['value'])) {
                $data['options'][$name] = $option['value'];
            }
        }
        return serialize($data);
    }

    /**
     * (PHP 5 >= 5.1.0)
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized The string representation of the object
     * @return void
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        if(!empty($data['options'])) {
            foreach($data['options'] as $name=>$value) {
                $this->setOption($name, $value);
            }
        }

        if(!empty($data['offset'])) {
            $this->offset = $data['offset'];
        }

        if(!empty($data['updated'])) {
            $this->updated = $data['updated'];
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function setOption($name, $value)
    {
        $this->options[$name]['value'] = $value;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getOption($name, $default = NULL)
    {
        return
            isset($this->options[$name]['value']) ?
                $this->options[$name]['value'] :
                $default;
    }

    public function restore()
    {
    }

    /**
     *
     * @param string $file_prefix
     * @return string
     */
    protected function getTempPath($file_prefix = null)
    {
        if (!$this->temp_path) {
            $this->temp_path = wa()->getTempPath('wa-apps/shop/plugins/generator/'.$this->getOption('processId'));
            waFiles::create($this->temp_path);
        }
        return ($file_prefix === null) ? $this->temp_path : tempnam($this->temp_path, $file_prefix);
    }
}