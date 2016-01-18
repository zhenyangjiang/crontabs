<?php
namespace Landers\Framework\Core;

use Pheanstalk\Pheanstalk;
use Pheanstalk\PheanstalkInterface;
use Landers\Interfaces\TaskInterface;

class Queue {
    const FAILD_RETRY_COUNT = 5;
    private $config;
    private $pheanstalk;

    /**
     * 构造方法
     */
    public function __construct() {
        $this->config = Config::get_default('queue', 'pheanstalk');
        $this->pheanstalk = new Pheanstalk($this->config['host']);
    }

    /**
     * 任务入队
     * @return void
     */
    public function push(TaskInterface $task, $is_note = false) {
        $priority = PheanstalkInterface::DEFAULT_PRIORITY;
        $delay = PheanstalkInterface::DEFAULT_DELAY;
        $ttr = $this->config['ttr'] or $ttr = PheanstalkInterface::DEFAULT_TTR;
        $ret = $this->pheanstalk
            ->useTube($this->config['queue'])
            // ->put(serialize($task), $priority, $delay, $ttr);
            ->put(json_encode(['data' => serialize($task)]));
        if ($ret) {
            if ($is_note) Log::note('#tab任务成功进入队列，ID：%s', $ret);
            return $ret;
        } else {
            if ($is_note) Log::error('#tab任务入队失败！');
            return false;
        }
    }

    /**
     * 监听队列
     * @return [type] [description]
     */
    private $tasks_faild_counts = array(
        //任务id => 失败次数
    );
    public function listen() {
        while(true) {
            $task = $this->pheanstalk->watch($this->config['queue'])->ignore('default')->reserve();

            if ($task) {
                $task_id = $task->getId();
                $logpre = '队列任务 #'.$task_id.'：';

                Log::note('抽取到一项队列任务，正在执行中...');
                $str = $task->getData();

                $object = json_decode($str);
                $object = unserialize($object->data);
                $bool = $object->execute($retmsg);
                $logmsg = $logpre.$retmsg;
                if ($bool) {
                    Log::note($logmsg);
                    $this->pheanstalk->delete($task);
                } else {
                    $counts = &$this->tasks_faild_counts;
                    if (!array_key_exists($task_id, $counts)) $counts[$task_id] = 1;
                    $count = &$counts[$task_id];

                    //到达重试次数，删除该任务
                    if ($count >= self::FAILD_RETRY_COUNT) {
                        Log::warn($logpre.'已重试%s次仍失败，该任务被删除。', self::FAILD_RETRY_COUNT);
                        $this->pheanstalk->delete($task);
                        unset($counts[$task_id]);
                    } else {
                        Log::error($logmsg.'(失败%s次)', $count);
                        $count++;
                    }
                }
                Log::note('#line');
            } else {
                Log::warn('无效队列任务，请查看控制台');
                Log::note('#line');
                sleep(1);
            }
        }
    }

    /**
     * 删除任务
     * @param  [type] $task [description]
     * @return [type]       [description]
     */
    public function delete($task) {
        $this->pheanstalk->delete($task);
    }

    /**
     * 检查是否在监听
     * @return [type] [description]
     */
    public function check_listening(){
        $ret = $this->pheanstalk->getConnection()->isServiceListening();
        Log::note('检查结果：%s', $ret);
    }
}