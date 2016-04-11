<?php
namespace Landers\Substrate\Interfaces;

interface TaskInterface {
    /**
     * 执行任务
     * @return void
     */
    public function execute(&$retmsg = NULL);
}