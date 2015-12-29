<?php

class shopGeneratorPluginRunController extends waLongActionController
{
    /** @var shopGeneratorPluginReader */
    private $reader;

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



            if ($reader_id = waRequest::post('reader')) {
                unset($options['reader']);
            }

            //todo: я тут ридер удалил, его надо посмотреть в основном плагине т.к. на него все завязано

            $this->data['reader'] = $this->getReader($reader_id, $options['config']);

            $this->reader = $this->data['reader'];
            $this->reader->init();

            $this->data += array(
                'timestamp'  => time(),
                'count'      => $this->reader->count(),
                'error'      => NULL,
                'memory'     => memory_get_peak_usage(),
                'memory_avg' => memory_get_usage(),
            );

            if(isset($options['config']['clear_qty']) && isset($options['config']['clear_qty'])) {
                $this->data['count']['clear_qty'] = 1;
            }

            $stages = array_keys($this->data['count']);
            $this->data['current'] = array_fill_keys($stages,0);
            $this->data['processed_count'] = array_fill_keys($stages,0);
            $this->data['stage'] = reset($stages);
            $this->data['stage_name'] = $this->getStageName($this->data['stage']);


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
        $step = $this->reader->step($this->data['current'], $this->data['count'], $this->data['processed_count'], $this->data['stage'], $this->data['error']);
        $this->data['memory'] = memory_get_peak_usage();
        $this->data['memory_avg'] = memory_get_usage();
        return !$this->isDone() && $step;
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
        $done = true;
        foreach ($this->data['current'] as $stage => $current) {
            if ($current < $this->data['count'][$stage]) {
                $done = false;
                $this->data['stage'] = $stage;
                break;
            }
        }
        if ($this->reader) {
            $this->data['stage_name'] = $this->getStageName($this->data['stage']);
        }
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

        $stage_num = 0;
        $stage_count = count($this->data['current']);
        foreach ($this->data['current'] as $stage => $current) {
            if ($current < $this->data['count'][$stage]) {
                $response['stage'] = $stage;
                $response['progress'] = sprintf('%0.3f%%', 100.0 * (1.0 * $current / $this->data['count'][$stage] + $stage_num) / $stage_count);
                break;
            }
            ++$stage_num;
        }
        $response['stage_name'] = $this->data['stage_name'];
        $response['stage_num'] = $stage_num;
        $response['stage_count'] = $stage_count;
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
        foreach ($this->data['current'] as $stage => $current) {
            if ($current) {
                if ($this->data['reader']) {
                    if ($data = $this->getStageReport($stage, $this->data['processed_count'])) {
                        $chunks[] = htmlentities($data, ENT_QUOTES, 'utf-8');
                    }
                }
            }
        }
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
            if ($this->reader) {
                $this->reader->finish();
            } elseif ($this->data['reader']) {
                $this->data['reader']->finish();
            }
        }
        return $result;
    }

    /**
     * @param string $id
     * @param array $options
     * @return shopGeneratorPluginReader
     * @throws waException
     */
    private function getReader($id, $options = array())
    {
        $class = 'shopGeneratorPlugin' . ucfirst($id) . 'Reader';

        if ($id && class_exists($class)) {
            if (isset($options['reader'])) {
                unset($options['reader']);
            }

            /** @var shopGeneratorPluginReader $reader */
            $reader = new $class($options);
        } else {
            throw new waException('Reader not found');
        }

        if (!($reader instanceof shopGeneratorPluginReader)) {
            throw new waException('Invalid reader');
        }

        return $reader;
    }

    /**
     * @param string $stage_id
     * @return string
     */
    private function getStageName($stage_id)
    {
        if($stage_id == 'clear_qty') {
            return "Обнуляем остатки на складе...";
        }

        return $this->reader->getStageName($stage_id);
    }

    private function getStageReport($stage_id, $data)
    {
        if($stage_id == 'clear_qty') {
            return "Обнулены остатки по всем товарам на складе.";
        }

        return $this->data['reader']->getStageReport($stage_id, $data);
    }

    protected function restore()
    {
        $this->reader = $this->data['reader'];
        $this->reader->restore();
    }

}