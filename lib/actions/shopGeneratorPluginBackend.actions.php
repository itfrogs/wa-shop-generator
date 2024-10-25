<?php

class shopGeneratorPluginBackendActions extends waViewActions
{
    /** @var string */
    protected $template_folder = 'templates/actions/backend/';

    /**
     * Страница настроек
     */
    public function setupAction()
    {
        $features = wao(new shopFeatureModel)
            ->where('type NOT LIKE "_d.%" AND type NOT IN ("divider", "text")')
            ->where('parent_id IS NULL')
            ->fetchAll('id');
        $this->view->assign('features', $features);
        $feature_types = (new shopTypeFeaturesModel)
            ->select('type_id, feature_id')
            ->where('feature_id IN (?)', [array_keys($features)])
            ->fetchAll('feature_id', 2);
        $this->view->assign('feature_types', $feature_types);
        $this->view->assign('product_types', wao(new shopTypeModel)->getTypes(true));
        $this->view->assign('categories', wao(new shopCategories())->getList());

        $plugin = wa('shop')->getPlugin('generator');
        $this->view->assign('ui', wa('shop')->whichUI());

        if(wa('shop')->whichUI() === '1.3') {
            $this->setTemplate($plugin->getPluginPath() . '/templates/actions-legacy/backend/Setup.html');
        }else{
            $this->setTemplate($plugin->getPluginPath() . '/templates/actions/backend/Setup.html');
        }
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
}
