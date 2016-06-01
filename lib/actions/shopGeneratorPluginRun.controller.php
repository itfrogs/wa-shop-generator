<?php

class shopGeneratorPluginRunController extends waLongActionController
{
    private $lipsum_short;
    private $lipsum_full;

    /**
     * @var shopConfig $config
     */
    private $config;

    /**
     * @var shopGeneratorPlugin $plugin
     */
    private $plugin;

    public function __construct() {
        $this->lipsum_short = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce luctus posuere augue. Maecenas mattis venenatis metus sit amet fermentum. Morbi in feugiat quam. Maecenas commodo eget elit aliquam posuere. Integer vitae eros id velit cursus euismod ac eget odio. Sed volutpat tellus id tortor molestie, a varius arcu ornare. Aliquam quis risus sit amet libero mollis efficitur. Aenean condimentum lectus vel suscipit sollicitudin. Sed venenatis quis arcu nec gravida.';
        $this->lipsum_full = '<p> Lorem ipsum dolor sit amet, consectetur adipiscing elit. Fusce luctus posuere augue. Maecenas mattis venenatis metus sit amet fermentum. Morbi in feugiat quam. Maecenas commodo eget elit aliquam posuere. Integer vitae eros id velit cursus euismod ac eget odio. Sed volutpat tellus id tortor molestie, a varius arcu ornare. Aliquam quis risus sit amet libero mollis efficitur. Aenean condimentum lectus vel suscipit sollicitudin. Sed venenatis quis arcu nec gravida. </p>'
                            .'<p> In non vestibulum dui, sed maximus magna. Cras lacinia dolor ut velit mattis, eu convallis lorem mollis. Vivamus vulputate efficitur varius. Etiam eleifend pharetra interdum. Integer quam lectus, pharetra sed placerat eu, faucibus quis odio. Proin tempus vel orci ac consectetur. Nullam tristique libero ut libero finibus, sit amet pellentesque ligula varius. In hac habitasse platea dictumst. Vestibulum urna quam, rhoncus vel posuere eget, pellentesque semper massa. Quisque justo lorem, iaculis sed neque ut, semper dapibus ipsum. Nullam posuere at velit sit amet rhoncus. Phasellus ut nisl a urna rutrum porta. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Aliquam facilisis sit amet tortor nec convallis. </p>'
                            .'<p> Ut vitae leo enim. Ut finibus ut erat in consectetur. Duis iaculis elementum consectetur. Vivamus placerat sed urna vel consequat. Phasellus vel nibh imperdiet, mollis metus at, dignissim est. Praesent accumsan, mauris nec vulputate vestibulum, magna massa elementum nibh, eget maximus purus nunc quis orci. Sed semper turpis ac hendrerit mollis. Maecenas quis dui ante. Vivamus eu dui et quam lobortis maximus ut sed purus. Maecenas sollicitudin condimentum dolor, non molestie enim dignissim in. </p>'
                            .'<p> Maecenas sagittis bibendum augue in lobortis. Quisque lacinia, magna id feugiat aliquam, diam dui fringilla nunc, at molestie mi dui in massa. Donec euismod ipsum ante, in varius odio dapibus vel. Sed ornare, ligula sed faucibus tempus, lectus nibh tristique turpis, eget tempus mi mauris ac sem. Quisque accumsan neque at lorem molestie tincidunt. Aenean malesuada velit vitae turpis bibendum sagittis. Suspendisse ullamcorper luctus nibh sit amet vehicula. Pellentesque pulvinar consequat dolor, ac fermentum dui hendrerit eu. Proin feugiat pretium tincidunt. Aenean ac massa quis enim vehicula consequat. Aliquam laoreet, enim in condimentum gravida, eros massa volutpat mauris, viverra feugiat justo turpis sit amet augue. In ullamcorper ipsum lacus, feugiat dapibus sapien posuere quis. Morbi ac euismod orci, nec ornare nisl. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. </p>'
                            .'<p> Fusce euismod sapien eget massa rutrum finibus. Nam vel tempus quam. Praesent ut posuere justo, ac tristique magna. Quisque elementum augue nunc, quis pretium est posuere id. Pellentesque rhoncus dui massa, non imperdiet nisi consequat eu. Phasellus ac arcu ac nisl auctor commodo. Sed maximus lorem ut massa luctus commodo. Fusce nec libero ultricies, bibendum mi congue, hendrerit nulla. Vivamus eget diam enim. Aliquam ut nunc eget mi commodo hendrerit at id est. Cras sit amet mi urna. Mauris non turpis dictum, viverra ante ac, egestas arcu. Maecenas eget tortor libero. Vivamus sed lobortis est. Maecenas lectus tortor, mattis at pharetra a, porta non turpis. </p>';
        $this->config = wa('shop')->getConfig();
    }

    public function execute()
    {
        try {
            parent::execute();
        } catch (waException $e) {
            if($e->getCode() == '302') {
                echo json_encode(array('warning'=>$e->getMessage()));
            } else {
                echo json_encode(array('error'=>$e->getMessage()));
            }
        }
    }

    protected function preExecute()
    {
//        $this->getResponse()->addHeader('Content-type', 'application/json');
//        $this->getResponse()->sendHeaders();
    }

    /**
     * Initializes new process.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     */
    protected function init()
    {
        try {

            $options = waRequest::post();
            $options['processId'] = $this->processId;
            $robohash_options = array(
                'text' => md5(microtime()),
            );
            $robohash = new shopGeneratorPluginRobohash($robohash_options);

            $this->data += array(
                'timestamp'         => time(),
                'count'             => intval($options['config']['num']),
                'current'           => intval($options['config']['num']),
                'category_id'       => $options['config']['category_id'],
                'prefix'            => $options['config']['prefix'],
                'type_id'           => $options['config']['type_id'],
                'images_num'        => intval($options['config']['images_num']),
                'images_count'      => intval($options['config']['images_num']),
                'images'            => array(),
                'set_dirs'          => $robohash->get_set_dirs(),
                'bg_dirs'           => $robohash->get_bg_dirs(),
                'processed_count'   => 0,
                'error'             => NULL,
                'memory'            => memory_get_peak_usage(),
                'memory_avg'        => memory_get_usage(),
            );

            $this->plugin = wa()->getPlugin('generator');

        } catch (waException $e) {
            echo json_encode(array('error' => $e->getMessage()));
        }
    }

    /**
     * Performs a small piece of work.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     *
     * The longer it takes to complete one step, the more time it is possible to lose if script fails.
     * The shorter, the more overhead there are because of copying $this->data and $this->fd after
     * each step. So, it should be reasonably long and reasonably short at the same time.
     * 5-10% of max execution time is recommended.
     *
     * $this->getStorage() session is already closed.
     * @return boolean false to end this Runner and call info(); true to continue.
     */
    protected function step()
    {
        $this->plugin = wa()->getPlugin('generator');
        $settings = $this->plugin->getSettings();
        $temp_image_path = wa()->getDataPath('plugins/generator/', true, 'shop', true) . 'robohash.png';
        /**
         * @var shopConfig $config
         */
        $config = $this->getConfig();

        $price = rand(100, 10000);
        $data = array(
            'name' => $this->data['prefix'],
            'summary' => $this->lipsum_short,
            'description' => $this->lipsum_full,
            'contact_id' => wa()->getUser()->getId(),
            'contact' => wa()->getUser(),
            'status' => 1,
            'url' => shopHelper::genUniqueUrl($this->data['prefix'].time().rand(1,1000), new shopProductModel()),
            'price' => $price,
            'count' => null,
            'currency' => $this->config->getCurrency(true),
        );

        $i = $this->data['images_num'] - $this->data['images_count'];

        $this->data['images'][$i]['name'] = 'image'.$i.'.png';
        $this->data['images'][$i]['tmp_path'] = wa()->getDataPath('plugins/generator/'.$this->data['images'][$i]['name'], true, 'shop', true);
        $options = array(
            'text'          => $data['url'].$this->data['images'][$i]['name'],
            'set_dirs'      => $this->data['set_dirs'],
            'bg_dirs'       => $this->data['bg_dirs'],
            'width'         => $settings['image_width'],
            'height'        => $settings['image_height'],
        );
        $robohash = new shopGeneratorPluginRobohash($options);

        $im = $robohash->generate();
        if (!empty($im)) {
            waFiles::move($temp_image_path, $this->data['images'][$i]['tmp_path']);
        }
        $this->data['images_count']--;

        if ($this->data['images_count'] < 1) {
            $product = new shopProduct();
            $product->save($data);

            $data['name'] = $this->data['prefix'].' (' . $product->getId() . ')';
            $data['price'] = $price;
            $data['min_price'] = $price;
            $data['max_price'] = $price;
            $data['category_id'] = $this->data['category_id'];
            $data['type_id'] = $this->data['type_id'];
            $product->name = $data['name'];
            $data['url'] = shopHelper::genUniqueUrl($this->data['prefix'].'-'.$product->getId(), new shopProductModel());
            $product->url = $data['url'];

            $category_model =  new shopCategoryModel();

            foreach ($this->data['images'] as $j => $im) {
                $image = waImage::factory($im['tmp_path']);

                if (!file_exists($im['tmp_path']) || !$image) {
                    $spm = new shopProductModel();
                    $spm->deleteById($product->getId());
                }
                else {
                    $img = array(
                        'product_id'        => $product->getId(),
                        'upload_datetime'   => date('Y-m-d H:i:s'),
                        'width'             => $image->width,
                        'height'            => $image->height,
                        'size'              => filesize($image->file),
                        'filename'          => basename($image->file),
                        'original_filename' => basename($image->file),
                        'ext'               => 'png',
                    );

                    $pim = new shopProductImagesModel();
                    $this->data['images'][$j]['id'] = $img['id'] =  $pim->add($img);

                    $image_path = shopImage::getPath($img);
                    $image->save($image_path);
                    shopImage::generateThumbs($img, $config->getImageSizes());
                }
            }

            if (!empty($this->data['images'])) {
                $image = reset($this->data['images']);
                $data['image_id'] = $image['id'];
            }

            $product->save($data);

            $this->data['memory'] = memory_get_peak_usage();
            $this->data['memory_avg'] = memory_get_usage();
            $this->data['current'] = $this->data['current'] - 1;
            if ($this->data['count'] > 0) {
                $this->data['processed_count'] = $this->data['count'] - $this->data['current'];
            }
            else {
                $this->data['processed_count'] = 0;
            }


            $sku_model = new shopProductSkusModel();

            if (isset($product->sku_id) && $product->sku_id) {
                $sku = $sku_model->getById($product->sku_id);
                $sku['price'] = $price;
                $sku['primary_price'] = $price;
                $sku['available'] = 1;
                $sku_model->updateById($sku['id'], $sku);
            }
            else {
                $sku = array(
                    'product_id'    => $product->getId(),
                    'sku'           => uniqid(),
                    'sort'          => 0,
                    'name'          => '',
                    'price'         => $price,
                    'primary_price' => $price,
                    'purchase_price' => 0,
                    'compare_price' => 0,
                    'available'     => 1,
                    'virtual'       => 0,
                );
                if (!empty($p['stocks_expected'])) {
                    $sku['count'] = null;
                }
                else {
                    $sku['count'] = 0;
                }
                $sku['id'] = $sku_model->insert($sku);
                $data['sku_id'] = $sku['id'];
            }




            $scp = new shopCategoryProductsModel();
            $category_product = array(
                'product_id' => $product->getId(),
                'category_id' => $data['category_id'],
                'sort' => 0,
            );
            $scp->insert($category_product);
            $category_model->query('UPDATE shop_category SET count = count + 1 WHERE id = i:category_id', array('category_id' => $data['category_id']));

            foreach ($this->data['images'] as $i => $im) {
                waFiles::delete($im['tmp_path']);
            }

            $this->data['images_count'] = intval($this->data['images_num']);
            $this->data['images'] = array();
        }

        return !$this->isDone();
    }

    /**
     * Checks if there is any more work for $this->step() to do.
     * Runs inside a transaction ($this->data and $this->fd are accessible).
     *
     * $this->getStorage() session is already closed.
     *
     * @return boolean whether all the work is done
     */
    protected function isDone()
    {
        if ($this->data['current'] == 0) {
            $done = true;
        }
        else $done = false;
        return $done;
    }

    protected function info()
    {
        $interval = 0;
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
        }
        $response = array(
            'time'       => sprintf('%d:%02d:%02d', floor($interval / 3600), floor($interval / 60) % 60, $interval % 60),
            'processId'  => $this->processId,
            'stage'      => false,
            'progress'   => 0.0,
            'ready'      => $this->isDone(),
            'count'      => empty($this->data['count']) ? false : $this->data['count'],
            'memory'     => sprintf('%0.2fMByte', $this->data['memory'] / 1048576),
            'memory_avg' => sprintf('%0.2fMByte', $this->data['memory_avg'] / 1048576),
        );

        if ($this->data['count'] > 0) {
            $response['progress'] = sprintf('%0.3f%%', 100.0 * $this->data['processed_count'] / $this->data['count']);
        }
        else {
            $response['progress'] = '100%';
        }

        $response['current_count'] = $this->data['current'];
        $response['processed_count'] = $this->data['processed_count'];
        if ($response['ready']) {
            $response['report'] = $this->report();
        }
        echo json_encode($response);
    }

    protected function report()
    {
        $report = '<div class="successmsg">';
        $report .= sprintf('<i class="icon16 yes"></i>%s ', _wp('Successfully'));
        $chunks = array();

        $report .= implode(', ', $chunks);
        if (!empty($this->data['timestamp'])) {
            $interval = time() - $this->data['timestamp'];
            $interval = sprintf(_wp('%02d hr %02d min %02d sec'), floor($interval / 3600), floor($interval / 60) % 60, $interval % 60);
            $report .= ' '.sprintf(_wp('(total time: %s)'), $interval);
        }
        $report .= '</div>';
        return $report;
    }

    /**
     * Called when $this->isDone() is true
     * $this->data is read-only, $this->fd is not available.
     *
     * $this->getStorage() session is already closed.
     *
     * @param $filename string full path to resulting file
     * @return boolean true to delete all process files; false to be able to access process again.
     */
    protected function finish($filename)
    {
        $this->info();
        $result = false;
        if ($this->getRequest()->post('cleanup')) {
            $result = true;

        }
        return $result;
    }
}