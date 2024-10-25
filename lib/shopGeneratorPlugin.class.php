<?php

/**
 *
 */
class shopGeneratorPlugin extends shopPlugin
{
    /**
     * @var waView $view
     */
    private static $view;

    /**
     * @return object|waSmarty3View|waView
     * @throws waException
     */
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

    /**
     * @return shopGeneratorPlugin|waPlugin
     * @throws waException
     */
    private static function getPlugin()
    {
        if (!isset(self::$plugin)) {
            self::$plugin = wa()->getPlugin('generator');
        }
        return self::$plugin;
    }

    /**
     * @return string
     */
    public function getPluginPath() {
        return $this->path;
    }

    /**
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    public static function settingCustomControlHint() {
        $view = self::getView();
        $plugin = self::getPlugin();
        return $view->fetch($plugin->getPluginPath() . '/templates/hint.html');
    }

    /**
     * @return string
     * @throws SmartyException
     * @throws waException
     */
    public static function getFeedbackControl() {
        $plugin = self::getPlugin();
        $view = self::getView();
        return $view->fetch($plugin->getPluginPath() . '/templates/feedbackControl.html');
    }
}
