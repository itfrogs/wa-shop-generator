<?php

// (C) 2012 hush2 <hushywushy@gmail.com>

class shopGeneratorPluginRobohash
{
    /**
     * @var shopGeneratorPlugin $plugin
     */
    private static $plugin;

    private $image_dir;

    private static $colors  = array(
        'blue', 'brown', 'green', 'grey', 'orange', 'pink', 'purple', 'red', 'white', 'yellow',
    );
    private static $sets    = array('set1','set2', 'set3');
    private static $bgsets  = array('bg1', 'bg2');

    private $set            = '',
            $bgset          = '',
            $hash_index     = 4,
            $hash_list      = array(),
            $set_dirs       = array(),
            $bg_dirs        = array(),
            $ext,
            $size,
            $temp_image,
            $image_width,
            $image_height;

    function  __construct($options=array()) {
        $this->temp_image = wa()->getDataPath('plugins/generator/', true, 'shop', true) . 'robohash.png';
        self::$plugin = wa()->getPlugin('generator');
        $settings = self::$plugin->getSettings();
        $this->image_dir = self::$plugin->getPluginPath() . '/img/robohash/';

        $color = isset($options['color']) ? $options['color'] : false;
        $set   = isset($options['set'])   ? $options['set']   : 'any';
        $bgset = isset($options['bgset']) ? $options['bgset'] : false;
        $size  = isset($options['size'])  ? $options['size']  : false;
        $width = isset($options['width'])  ? $options['width']  : 400;
        $height = isset($options['height'])  ? $options['height']  : 400;

        if ($set != 'set1') {
            $color = 'white';
        }

        $ext = 'png';

        $filename = md5("{$options['text']}_{$set}_{$bgset}_{$color}_{$size}") . ".$ext";

        $this->create_hashes($options['text']);
        $this->set_set($set);
        $this->set_color($color) ;

        if ($bgset)
        {
            $this->set_bgset($bgset);
        }
        else {
            if ($settings['background']) {
                $this->set_bgset(self::$bgsets[$this->my_bcmod($this->hash_list[2], count(self::$bgsets))]);
            }
        }

        $this->set_size($size) ;

        $this->filename     = $filename;
        $this->ext          = $ext;
        $this->image_width  = $width;
        $this->image_height = $height;


        if (!empty($options) && isset($options['set_dirs'])) {
            $this->set_dirs = $options['set_dirs'];
        }
        if (!empty($options) && isset($options['bg_dirs'])) {
            $this->bg_dirs = $options['bg_dirs'];
        }

    }

    private function create_hashes($text, $length=11)
    {
        $hashes = str_split(hash('sha512', $text), $length);
        foreach ($hashes as $hash)
        {
            $this->hash_list[] = base_convert($hash, 16, 10);
        }
    }

    function set_color($color)
    {
        if (empty($this->set)) {
            $this->set = 'set1/';
        }
        $set = explode('/', $this->set);
        $this->set = $set[0] . '/';

        if ($color && in_array($color, self::$colors))
        {
            $this->set .= $color;
        }
        else {
            $this->set .= self::$colors[$this->my_bcmod($this->hash_list[0], count(self::$colors))] ;
        }
    }

    function set_set($set)
    {
        if ($set == 'any') 
        {
            $set = self::$sets[$this->my_bcmod($this->hash_list[1], count(self::$sets))] ;
        }
        if ($set == 'set1' || !in_array($set, self::$sets))
        {
            return;  // Use set from set_color()
        }
        $this->set = $set;
    }

    function set_bgset($bgset)
    {
        if (!in_array($bgset, self::$bgsets))
        {
            $bgset = self::$bgsets[$this->my_bcmod($this->hash_list[2], count(self::$bgsets))];
        }

        if (!empty($this->bgsets_array)) {
            $this->bgset = $this->bgsets_array[$bgset];
            return $this->bgset;
        }

        if (!empty($this->bg_dirs)) {
            $bgfiles =  $this->bg_dirs[$this->bgset];
        }
        else {
            $bgfiles = glob($this->image_dir . "$bgset/*");
        }

        $this->bgset = $bgfiles[$this->my_bcmod($this->hash_list[3], count($bgfiles))];
        return $this->bgset;
    }

    public function get_bg_dirs() {
        $dirs = array();
        foreach (self::$bgsets as $bgset) {
            $dirs[$bgset] = glob($this->image_dir . "$bgset/*");
        }
        return $dirs;
    }

    public function get_set_dirs()
    {
        $dirs = array();
        foreach (self::$sets as $set) {
            if ($set == 'set1') {
                $colors = self::$colors;
            }
            else {
                $colors  = array('white');
            }

            foreach ($colors as $color) {
                $color_dirs = glob($this->image_dir . "{$set}/{$color}/*");
                foreach ($color_dirs as $key => $color_dir) {
                    $files = glob($color_dir . "/*");
                    foreach ($files as $file) {
                        $dirs[$set][$color][$key][] = $file;
                    }
                }
            }
        }
        return $dirs;
    }

    function get_image_list()
    {
        if (!empty($this->set_dirs)) {
            $params = explode('/', $this->set);
            $dirs =  $this->set_dirs[$params[0]][$params[1]];
        }
        else {
            $dirs = glob($this->image_dir . "{$this->set}/*");
        }

        $image_list = array();

        foreach ($dirs as $dir)
        {
            //$files = glob("$dir/*");
            $files = $dir;
            $img_index = $this->my_bcmod($this->hash_list[$this->hash_index], count($files));
            $this->hash_index++;
            $s = explode('#', $files[$img_index], 2);
            krsort($s);
            $temp[] = implode("|", $s);
        }
        sort($temp);

        foreach ($temp as $file)
        {
            $s = explode('|',$file, 2);
            krsort($s);
            $image_list[] = implode("#", $s);
        }
        if ($this->bgset)
        {
            array_unshift($image_list, $this->bgset);
        }
        return $image_list;
    }

    function set_size($size)
    {
        $this->size = $size;
    }

    function get_width_height()
    {
        $width  = $this->image_width;
        $height = $this->image_width;

        if ($this->size)
        {
            $width_height = explode('x', $this->size);

            $width  = isset($width_height[0]) ? (int) $width_height[0] : $this->image_width;
            $height = isset($width_height[1]) ? (int) $width_height[1] : $this->image_height;

            if ($width  > 1024 || $width  < 10)
            {
                $width  = $this->image_width;
            }
            if ($height > 1024 || $height < 10)
            {
                $height = $this->image_height;
            }
        }
        return array($width, $height);
    }

    // Use ImageMagick for processing images.
    function generate_image_imagick($image_list)
    {
        $body = array_shift($image_list);
        $body = new Imagick($body);

        $body->resizeImage($this->image_width, $this->image_height, Imagick::FILTER_LANCZOS, 1);

        foreach ($image_list as $image_file)
        {
            $image = new Imagick($image_file);
            // Since some of the images varies in width/height (Set3 in particular),
            // they need to be resized first so that they are centered properly.
            $image->resizeImage($this->image_width, $this->image_height, Imagick::FILTER_LANCZOS, 1);
            $body->compositeImage($image, $image->getImageCompose(), 0, 0);
            $image->clear();
        }

        list($width, $height) = $this->get_width_height();

        if ($width != $this->image_width && $height != $this->image_height)
        {
            $body->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
        }
        $body->setImageFormat('png');
        $body->writeImage($this->temp_image);
        //$body->setImageFormat($this->ext);
        return $body;

    }

    // Use GD as a fallback if host does not support ImageMagick.
    function generate_image_gd($image_list)
    {
        $body = array_shift($image_list);

        $body = imagecreatefrompng($body);
        $body = $this->image_resize($body, $this->image_width, $this->image_height);

        foreach ($image_list as $image_file) {
            $image = imagecreatefrompng($image_file);
            $image = $this->image_resize($image, $this->image_width, $this->image_height);
            $this->imagecopymerge_alpha($body, $image, 0, 0, 0, 0, imagesx($image), imagesy($image), 100);
            imagedestroy($image);
        }

        list($width, $height) = $this->get_width_height();

        $body = $this->image_resize($body, $width, $height);

        imagesavealpha($body, true);
        imagepng($body, $this->temp_image);

        return $body;
    }

    public function generate_image()
    {
        $image_list = $this->get_image_list();

        if (extension_loaded('imagick'))
        {
            return $this->generate_image_imagick($image_list);
        }
        if (extension_loaded('gd'))
        {
            $image = $this->generate_image_gd($image_list);

            // Buffer image so we can cache it.
            ob_start();

            switch ($this->ext) {
                case 'jpg':
                    imagejpeg($image);
                    break;

                case 'gif':
                    imagegif($image);
                    break;

                case 'bmp':
                    imagewbmp($image);
                    break;

                default:
                    imagepng($image);
                    break;
            }

            $body = ob_get_clean();
            return $body;
        }
    }

    static function rand_text($length = 8)
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle($chars), 0, $length);
    }

    static function rand_set()
    {
        return self::$sets[array_rand(self::$sets)];
    }

    static function rand_color()
    {
        return self::$colors[array_rand(self::$colors)];
    }

    static function rand_bgset()
    {
        return self::$bgsets[array_rand(self::$bgsets)];
    }

    function image_resize($src, $width, $height){

        $img = imagecreatetruecolor($width, $height);

        imagecolortransparent($img, imagecolorallocatealpha($img, 0, 0, 0, 127));

        imagealphablending($img, false);
        imagesavealpha($img, true);

        imagecopyresampled($img, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));

        imagealphablending($img, true);

        return $img;
    }

    function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);

        $trans_color = imagecolorallocatealpha($cut, 255, 255, 255, 127);
        imagefill($cut, 0, 0, $trans_color);

        // copying relevant section from background to the cut resource
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        // insert cut resource to destination image
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);

        imagedestroy($cut);
    }

    public function generate() {
        $im = $this->generate_image();
        return $im;
    }

    public function deleteTempImage() {
        waFiles::delete($this->temp_image);
    }
    
    /**
     * my_bcmod - get modulus (substitute for bcmod)
     * string my_bcmod ( string left_operand, int modulus )
     * left_operand can be really big, but be carefull with modulus :(
     * by Andrius Baranauskas and Laurynas Butkus :) Vilnius, Lithuania
     **/
    private function my_bcmod( $x, $y )
    {
        // how many numbers to take at once? carefull not to exceed (int) 
        $take = 5;
        $mod = '';

        do
        {
            $a = (int)$mod.substr( $x, 0, $take );
            $x = substr( $x, $take );
            $mod = $a % $y;
        }
        while ( strlen($x) );

        return (int)$mod;
    }
}
