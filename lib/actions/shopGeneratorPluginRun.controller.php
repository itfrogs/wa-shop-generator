<?php

class shopGeneratorPluginRunController extends waLongActionController
{

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

            $this->data += array(
                'timestamp'  => time(),
                'count'      => $options['config']['num'],
                'current'    => $options['config']['num'],
                'processed_count'    => 0,
                'error'      => NULL,
                'memory'     => memory_get_peak_usage(),
                'memory_avg' => memory_get_usage(),
            );

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
        $step = 0;
        $this->data['memory'] = memory_get_peak_usage();
        $this->data['memory_avg'] = memory_get_usage();
        $this->data['current']--;
        $this->data['processed_count'] = $this->data['count'] - $this->data['current'];
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