<?php
namespace Landers\Interfaces;

interface Task {
    /**
     * 执行任务
     * @return void
     */
    public function execute(&$retmsg);
}