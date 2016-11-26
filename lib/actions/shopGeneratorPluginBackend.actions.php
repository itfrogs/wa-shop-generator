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
        $type_model = new shopTypeModel();
        $feature_model = new shopFeatureModel();
        $product_types = $type_model->getTypes(true);
        $features = $feature_model->where('type NOT IN ("2d.double","3d.double","divider","text") AND parent_id IS NULL')->fetchAll();
        $this->view->assign('product_types', $product_types);
        $this->view->assign('categories', new shopCategories());
        $this->view->assign('features', $features);
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