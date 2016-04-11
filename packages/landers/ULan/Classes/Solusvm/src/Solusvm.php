<?php

namespace ULan\SolusVM;
use Exception;
use ULan\SolusVM\Utils\Http;

class Solusvm {
    /**
     * API 地址
     *
     * @var string
     */
    private $host;

    /**
     * API id
     * @var string
     */
    private $id;

    /**
     * API key
     *
     * @var string
     */
    private $key;

    /**
     * API port
     *
     * @var int
     */
    private $port;

    /**
     * constructor
     *
     * @param string $id
     * @param string $key
     * @param string $host
     * @param int    $port
     * @param string $format
     */
    function __construct( $id, $key, $host, $port = 5656 ) {
        $this->id = $id;
        $this->key = $key;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * 执行参数
     *
     * @param array $params
     * @return array
     */
    private function execute(array $params) {
        $params["id"] = $this->id;
        $params["key"] = $this->key;
        $params["rdtype"] = 'json';

        $url = "https://" . $this->host . ":" . $this->port . "/api/admin/command.php";

        $http = new Http();
        $options['headers'] = array("Expect:");
        $response = $http->post($url,$params,$options);

        return $response;
    }

    /**
     * 重启指定虚拟机
     *
     * @param int $serverID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Reboot+Virtual+Server
     */
    public function reboot($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-reboot", "vserverid"=>$serverID));
    }

    /**
     * 启动指定虚拟机
     *
     * @param int $serverID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Boot+Virtual+Server
     */
    public function boot($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-boot", "vserverid"=>$serverID));
    }

    /**
     * 关闭指定虚拟机
     *
     * @param int $serverID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Shutdown+Virtual+Server
     */
    public function shutdown($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-shutdown", "vserverid"=>$serverID));
    }

    /**
     * 可用 ISO 镜像列表
     *
     * @param string $type
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/List+ISO+Images
     */
    public function listISO($type = 'kvm') {
        if(!in_array($type, array("xen hvm", "kvm", "xen", "openvz")))
            throw new Exception("Invalid Type");
        return $this->execute(array("action"=>"listiso", "type"=>$type));
    }

    /**
     * 给指定虚拟机挂载 ISO 镜像
     *
     * @param int $serverID 虚拟机ID
     * @param int $iso      ISO 镜像ID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Mount+ISO
     */
    public function mountISO($serverID, $iso) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-mountiso", "vserverid"=>$serverID, "iso"=>$iso));
    }

    /**
     * 卸载指定虚拟机 ISO 镜像
     *
     * @param int $serverID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Unmount+ISO
     */
    public function unmountISO($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-unmountiso", "vserverid"=>$serverID));
    }


    /**
     * 获取指定虚拟机的 VNC 信息
     *
     * @param int $serverID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/VNC+Info
     */
    public function getVNC($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-vnc", "vserverid"=>$serverID));
    }

    /**
     * 获取指定虚拟机细节
     *
     * @param int $serverID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Virtual+Server+Information
     */
    public function getServerInfo($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-info", "vserverid"=>$serverID));
    }

    /**
     * 获取指定虚拟机服务器状态
     *
     * @param $serverID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Virtual+Server+State
     */
    public function getServerState($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-infoall", "vserverid"=>$serverID));
    }

    /**
     * 获取当前状态通过ID指定的虚拟服务器
     *
     * @param $serverID
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Virtual+Server+Status
     */
    public function getServerStatus($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-status", "vserverid"=>$serverID));
    }

    /**
     * 验证客户端的凭据
     *
     * @param $username
     * @param $password
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Client+Authenticate
     */
    public function authenticateClient($username, $password) {
        if(!ctype_alnum($username))
            throw new Exception("Invalid Username");
        return $this->execute(array("action"=>"vserver-authenticate", "username"=>$username, "password"=>$password));
    }

    /**
     * 更新主机的ID指定的虚拟服务器相关
     *
     * @param $serverID
     * @param $hostname
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Change+Hostname
     */
    public function changeHostname($serverID, $hostname) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        if(!preg_match('/[\w-.]+/', $hostname))
            throw new Exception("Invalid Hostname");
        return $this->execute(array("action"=>"vserver-hostname", "vserverid"=>$serverID, "hostname"=>$hostname));
    }
    /**
     * Retrieves client list
     *
     *  https://documentation.solusvm.com/display/DOCS/List+Clients
     *
     * @access       public
     * @param
     * @return       str
     */
    public function listClients() {
        return $this->execute("client-list");
    }
    /**
     * Retrieves a list of virtual servers on specified node
     *
     *  https://documentation.solusvm.com/display/DOCS/List+Virtual+Servers
     *
     * @access       public
     * @param        int
     * @return       str
     */
    public function listServers($nodeid) {
        if(!is_numeric($nodeid))
            throw new Exception("Invalid NodeID");

        //return $this->execute(array("action"=>"vserver-virtualservers", "nodeid"=>$nodeid));
        return $this->execute(array("action"=>"node-virtualservers", "nodeid"=>$nodeid));
    }
    /**
     * Determines if a vserver exists as specified by its ID
     *
     *  https://documentation.solusvm.com/display/DOCS/Check+Exists
     *
     * @access       public
     * @param        int
     * @return       str
     */
    public function vserverExists($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-checkexists", "vserverid"=>$serverID));
    }

    /**
     * Adds an IP address to specified vserver
     *
     *  https://documentation.solusvm.com/display/DOCS/Add+IP+Address
     *
     * @access       public
     * @param        int, str, bool
     * @return       str
     */
    public function addIP($serverID, $ipv4addr=0, $forceaddip=0) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        $args = array(
            "action" => "vserver-addip",
            "vserverid" => $serverID
        );
        if($ipv4addr) {
            if(filter_var($ipv4addr, FILTER_VALIDATE_IP) === false)
                throw new Exception("Invalid IPv4 Address");
            if(filter_var($forceaddip, FILTER_VALIDATE_BOOLEAN) === false)
                throw new Exception("forceaddip must be boolean");
            $args['ipv4addr'] = $ipv4addr;
            $args['forceaddip'] = $forceaddip;
        }
        return $this->execute($args);
    }

    /**
     * 删除指定服务器 IP
     *
     * @param $serverID
     * @param $ipaddr
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Delete+IP+Address
     */
    public function deleteIP($serverID, $ipaddr) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        if(filter_var($ipaddr, FILTER_VALIDATE_IP) === false)
            throw new Exception("Invalid IPv4 Address");
        return $this->execute(array("action"=>"vserver-delip", "vserverid"=>$serverID, "ipaddr"=>$ipaddr));
    }
    /**
     * Updates owner of specified vserver
     *
     *  https://documentation.solusvm.com/display/DOCS/Change+Owner
     *
     * @access       public
     * @param        int, int
     * @return       str
     */
    public function changeOwner($serverID, $clientID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        if(!is_numeric($clientID))
            throw new Exception("Invalid ClientID");
        return $this->execute(array("action"=>"vserver-changeowner", "vserverid"=>$serverID, "clientid"=>$clientID));
    }

    /**
     * 修改计划
     *
     * @param $serverID
     * @param $plan
     * @param bool $changeHDD
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Change+Plan
     */
    public function changePlan($serverID, $plan, $changeHDD=false) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        if(filter_var($changeHDD, FILTER_VALIDATE_BOOLEAN) === false)
            throw new Exception("changeHDD must be boolean");
        return $this->execute(array("action"=>"vserver-change", "vserverid"=>$serverID, "plan"=>$plan, "changehdd"=>$changeHDD));
    }

    /**
     * 删除实例
     *
     * @param $serverID
     * @param bool $deleteclient
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Terminate+Virtual+Server
     */
    public function terminate($serverID, $deleteclient=false) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        /*
        if(filter_var($deleteclient, FILTER_VALIDATE_BOOLEAN) === false)
            throw new Exception("deleteclient must be boolean");
        */

        return $this->execute(array("action"=>"vserver-terminate", "vserverid"=>$serverID, "deleteclient"=>$deleteclient));
    }

    /**
     * Suspends specified vserver
     *
     *  https://documentation.solusvm.com/display/DOCS/Suspend+Virtual+Server
     *
     * @access       public
     * @param        int
     * @return       str
     */
    public function suspend($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-suspend", "vserverid"=>$serverID));
    }
    /**
     * Unsuspends specified vserver
     *
     *  https://documentation.solusvm.com/display/DOCS/Unsuspend+Virtual+Server
     *
     * @access       public
     * @param        int
     * @return       str
     */
    public function unsuspend($serverID) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-unsuspend", "vserverid"=>$serverID));
    }

    /**
     * 修改主机CPU核心数
     *
     * @param $serverID
     * @param $limit
     * @param $overlimit
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Change+Bandwidth+Limits
     */
    public function changeCpu($serverID, $cpu) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        if(!is_numeric($cpu))
            throw new Exception("Invalid CPU");
        return $this->execute(array("action"=>"vserver-change-cpu", "vserverid"=>$serverID, "cpu"=>$cpu));
    }

    /**
     * 修改服务器带宽大小
     *
     * @param $serverID
     * @param $limit
     * @param $overlimit
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Change+Bandwidth+Limits
     */
    public function changeBandwidth($serverID, $limit, $overlimit) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        if(!is_numeric($limit))
            throw new Exception("Invalid Limit");
        if(!is_numeric($overlimit))
            throw new Exception("Invalid OverLimit");
        return $this->execute(array("action"=>"vserver-bandwidth", "vserverid"=>$serverID, "limit"=>$limit, "overlimit"=>$overlimit));
    }

    /**
     * 修改服务器内存大小
     *
     * @param $serverID
     * @param $memory
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Change+Memory
     */
    public function changeMemory($serverID, $memory) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        if(!is_numeric($memory))
            throw new Exception("Invalid Memory");
        return $this->execute(array("action"=>"vserver-change-memory", "vserverid"=>$serverID, "memory"=>$memory));
    }

    /**
     * 修改服务器硬盘大小
     *
     * @param $serverID
     * @param $hdd
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Change+Hard+Disk+Size
     */
    public function changeDiskSize($serverID, $hdd) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        if(!is_numeric($hdd))
            throw new Exception("Invalid HDD");
        return $this->execute(array("action"=>"vserver-change-hdd", "vserverid"=>$serverID, "hdd"=>$hdd));
    }

    /**
     * 重新建立服务器
     *
     * @param $serverID
     * @param $template
     * @return array
     * @throws Exception
     * @link
     */
    public function rebuild($serverID, $template) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-rebuild", "vserverid"=>$serverID, "template"=>$template));
    }

    /**
     * 修改虚拟服务器密码
     *
     * @param $serverID
     * @param $rootpassword
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Change+Root+Password
     */
    public function changeRootPassword($serverID, $rootpassword) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-rootpassword", "vserverid"=>$serverID, "rootpassword"=>$rootpassword));
    }

    /**
     * 修改 VPC 密码
     *
     * @param $serverID
     * @param $vncpassword
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/Change+VNC+Password
     */
    public function changeVNCpassword($serverID, $vncpassword) {
        if(!is_numeric($serverID))
            throw new Exception("Invalid ServerID");
        return $this->execute(array("action"=>"vserver-vncpass", "vserverid"=>$serverID, "vncpassword"=>$vncpassword));
    }

    /**
     * 获取可用模板列表
     *
     * @param string $type
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/List+Templates
     */
    public function listTemplates($type = "kvm") {
        if(!in_array($type, array("xen hvm", "kvm", "xen", "openvz")))
            throw new Exception("Invalid Type");
        return $this->execute(array("action"=>"listtemplates", "type"=>$type));
    }

    /**
     * 获取现有计划列表
     *
     * @param string $type
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/List+Plans
     */
    public function listPlans($type = "kvm") {
        if(!in_array($type, array("xen hvm", "kvm", "xen", "openvz")))
            throw new Exception("Invalid Type");
        return $this->execute(array("action"=>"listplans", "type"=>$type));
    }
    /**
     * 获取节点列表注明的ID
     *
     * @param string $type
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/List+Nodes+by+ID
     */
    public function listNodesByID($type = "kvm") {
        if(!in_array($type, array("xen hvm", "kvm", "xen", "openvz")))
            throw new Exception("Invalid Type");
        return $this->execute(array("action"=>"node-idlist", "type"=>$type));
    }

    /**
     * 获取节点列表注明的名称
     *
     * @param string $type
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/List+Nodes+by+Name
     */
    public function listNodesByName($type = "kvm") {
        if(!in_array($type, array("xen hvm", "kvm", "xen", "openvz")))
            throw new Exception("Invalid Type");
        return $this->execute(array("action"=>"listnodes", "type"=>$type));
    }

    /**
     * 检索IP地址指定节点关联的列表
     *
     * @param $nodeid
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/List+All+IP+Addresses+for+a+Node
     */
    public function getNodeIPs($nodeid) {
        if(!is_numeric($nodeid))
            throw new Exception("Invalid NodeID");
        return $this->execute(array("action"=>"node-iplist", "nodeid"=>$nodeid));
    }

    /**
     * 检索节点组列表
     *
     * @param string $type
     * @return array
     * @throws Exception
     * @link https://documentation.solusvm.com/display/DOCS/List+Node+Groups
     */
    public function listNodeGroups($type = "kvm") {
        if(!in_array($type, array("xen hvm", "kvm")))
            throw new Exception("Invalid Type");
        return $this->execute(array("action"=>"listnodegroups", "type"=>$type));
    }

    /**
     * 创建虚拟机
     *
     * @param array $param
     *              int node                   节点ID
     *              int nodegroup              节点组ID                                    ##必填
     *              string hostname            主机名                                      ##随机生成
     *              string password            主机密码
     *              string username            所属用户                                    #必填
     *              string plan                套餐名                                      ##必填
     *              string template            模板名                                      ##必填
     *              int ips                    IP数                                       ##必填 1
     *              boole randomipv4           随机 IPv4 [true|false] 默认 false
     *              boole hvmt                 这允许定义 Xen HVM 模板和 ISO  [0|1] 默认 0
     *              int custommemory           修改 plan 内存
     *              int customdiskspace        修改 plan 硬盘
     *              int custombandwidth        修改 plan 带宽
     *              int customcpu              修改 plan CPU 数
     *              int customextraip          添加额外的 IP 数
     *              string issuelicense        面板 license
     *              boole internalip           内网 IP [0|1] 默认 0
     *
     * @param string $type
     * @return array
     * @link https://documentation.solusvm.com/display/DOCS/Create+Virtual+Server
     */
    public function vserverCreate(array $param, $type = "kvm") {
        $param = [
            "action"    => "vserver-create",
            "type"      => $type,
            'username'  => 'ULanNetwork',
            'template'  => '',
        ] + $param;

        if(!in_array($param['type'], array("xen hvm", "kvm")))
            throw new Exception("Invalid Type");
        return $this->execute($param);
    }
}