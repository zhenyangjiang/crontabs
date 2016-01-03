<?php
namespace Landers\Interfaces;

interface TaskInterface {
    /**
     * 执行任务
     * @return void
     */
    public function execute(&$retmsg);
}