<?php

class shopGeneratorPlugin extends shopPlugin
{
    /**
     * @var waView $view
     */
    private static $view;
    private static function getView()
    {
        if (!isset(self::$view)) {
            self::$view = waSystem::getInstance()->getView();
        }
        return self::$view;
    }

    /**
     * @var shopGeneratorPlugin $plugin
     */
    private static $plugin;
    private static function getPlugin()
    {
        if (!isset(self::$plugin)) {
            self::$plugin = wa()->getPlugin('generator');
        }
        return self::$plugin;
    }

    public function getPluginPath() {
        return $this->path;
    }

    public static function settingCustomControlHint() {
        $view = self::getView();
        $plugin = self::getPlugin();
        return $view->fetch($plugin->getPluginPath() . '/templates/hint.html');
    }

    public static function getFeedbackControl() {
        $plugin = self::getPlugin();
        $view = self::getView();
        return $view->fetch($plugin->getPluginPath() . '/templates/feedbackControl.html');
    }
}
