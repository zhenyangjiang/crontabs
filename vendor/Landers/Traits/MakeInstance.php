<?php
namespace Landers\Traits;

Trait MakeInstance {
    /**
     * 自定义实例化类的对象
     * @param  [type] $class [description]
     * @param  [type] $args  [description]
     * @return [type]        [description]
     */
    public static function makeBy($class, $args) {
        $class or $class = static::class;
        $args or $args = array();
        switch (count($args)) {
            case 0 : $o = new $class(); break;
            case 1 : $o = new $class($args[0]); break;
            case 2 : $o = new $class($args[0], $args[1]); break;
            case 3 : $o = new $class($args[0], $args[1], $args[2]); break;
            case 4 : $o = new $class($args[0], $args[1], $args[2], $args[3]); break;
        }
        return $o;
    }

    /**
     * 实例化一个新的本类对象
     * @return [type] [description]
     */
    public static function make() {
        return self::makeBy(static::class, func_get_args());
    }

    /**
     * 根据参数作为单例标识，实例化一个新的单例对象
     * @var array
     */
    private static $_instances = array();
    public static function singleton() {
        $args = func_get_args();
        $unqiue = serialize($unique);
        $unique = md5($unique);

        $ret = &self::$_instances[$unique];
        if ( !$ret ) {
            $ret = self::makeBy(static::class, $args);
        }
        return $ret;
    }

}
