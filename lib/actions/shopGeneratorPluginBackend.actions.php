<?php

class shopGeneratorPluginBackendActions extends waViewActions
{
    /** @var string */
    protected $template_folder = 'templates/actions/backend/';

    /** @var shopImportexportHelper */
    private $Profile;

    /** @var */
    private $Stock;

    /**
     * Страница настроек
     */
    public function setupAction()
    {
        $profile = $this->getProfile();
        $profiles = $this->Profile->getList();
        $stocks = $this->Stock->select('id,name')->order('`sort` ASC')->fetchAll('id', TRUE);

        $this->view->assign(compact('profile', 'profiles', 'stocks'));
    }

    /**
     * Инициализация нужных классов и переменных
     */
    protected function preExecute()
    {
        $this->Profile = new shopImportexportHelper('generator');
        $this->Stock = new shopStockModel();

        parent::preExecute();
    }

    /**
     * @param string $type
     * @return string
     */
    protected function respondAs($type = NULL)
    {
        if ($type !== NULL) {
            if ($type == 'json') {
                $type = 'application/json';
            }
            $this->getResponse()->addHeader('Content-type', $type);
        }

        return $this->getResponse()->getHeader('Content-type');
    }

    /**
     * @return string
     */
    protected function getTemplate()
    {
        $plugin_root = $this->getPluginRoot();

        if ($this->template === NULL) {
            if ($this->respondAs() === 'application/json') {
                return $plugin_root . 'templates/json.tpl';
            }
            $template = ucfirst($this->action);
        } else {
            if (strpbrk($this->template, '/:') !== FALSE) {
                return $this->template;
            }
            $template = $this->template;
        }

        return $plugin_root . $this->template_folder . $template . $this->view->getPostfix();
    }

    /**
     * @return array
     */
    private function getProfile()
    {
        $profile = $this->Profile->getConfig();
        $profile['config'] += array(
            'clear_qty'    => 1,
            'src_url'      => '',
            'stock_id'     => 0,
            'update_price' => 1,
            'update_qty'   => 1
        );

        return $profile;
    }

}