<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * state_schedulers
 *
 * @ORM\Table(name="FOCUS_SPOOLERS")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\SpoolersRepository")
 *  */
class Spoolers
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=32)
     */
    private $name;

   /**
    * @ORM\OneToOne(targetEntity="Arii\CoreBundle\Entity\Spooler")
    * @ORM\JoinColumn(nullable=true)
    */
   private $spooler;
    
    /**
     * @var string
     *
     * @ORM\Column(name="connection", type="string", length=30, nullable=true )
     */
    private $connection;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer", nullable=true )
     */
    private $updated;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="spooler_running_since", type="datetime", nullable=true)
     */
    private $spooler_running_since;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=100, nullable=true)
     */
    private $state;
    /**
     * @var string
     *
     * @ORM\Column(name="log_file", type="string", length=255, nullable=true)
     */
    private $log_file;
     /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=100, nullable=true)
     */
    private $version;

    /**
     * @var integer
     *
     * @ORM\Column(name="pid", type="integer", nullable=true)
     */
    private $pid;

    /**
     * @var string
     *
     * @ORM\Column(name="host", type="string", length=100, nullable=true)
     */
    private $host;

    /**
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=100, nullable=true)
     */
    private $ip_address;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="need_db", type="boolean", nullable=true)
     */
    private $need_db;

    /**
     * @var integer
     *
     * @ORM\Column(name="tcp_port", type="integer", nullable=true)
     */
    private $tcp_port;

    /**
     * @var integer
     *
     * @ORM\Column(name="udp_port", type="integer", nullable=true)
     */
    private $udp_port;

    /**
     * @var string
     *
     * @ORM\Column(name="config_file", type="string", length=512, nullable=true)
     */
    private $config_file;

    /**
     * @var string
     *
     * @ORM\Column(name="db", type="string", length=255, nullable=true)
     */
    private $db;

    /**
     * @var float
     *
     * @ORM\Column(name="cpu_time", type="decimal", nullable=true)
     */
    private $cpu_time;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time", type="datetime", nullable=true)
     */
    private $time;

    /**
     * @var integer
     *
     * @ORM\Column(name="waits", type="integer", nullable=true)
     */
    private $waits;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="wait_until", type="datetime", nullable=true)
     */
    private $wait_until;

    /**
     * @var integer
     *
     * @ORM\Column(name="loops", type="integer", nullable=true)
     */
    private $loops;

    /**
    * @ORM\ManyToOne(targetEntity="Arii\CoreBundle\Entity\Enterprise")
    * @ORM\JoinColumn(nullable=true)
    */
    private $enterprise;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return state_schedulers
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    
        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set spooler_running_since
     *
     * @param \DateTime $spoolerRunningSince
     * @return state_schedulers
     */
    public function setSpoolerRunningSince($spoolerRunningSince)
    {
        $this->spooler_running_since = $spoolerRunningSince;
    
        return $this;
    }

    /**
     * Get spooler_running_since
     *
     * @return \DateTime 
     */
    public function getSpoolerRunningSince()
    {
        return $this->spooler_running_since;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return state_schedulers
     */
    public function setState($state)
    {
        $this->state = $state;
    
        return $this;
    }

    /**
     * Get state
     *
     * @return string 
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set pid
     *
     * @param integer $pid
     * @return state_schedulers
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    
        return $this;
    }

    /**
     * Get pid
     *
     * @return integer 
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set config_file
     *
     * @param string $configFile
     * @return state_schedulers
     */
    public function setConfigFile($configFile)
    {
        $this->config_file = $configFile;
    
        return $this;
    }

    /**
     * Get config_file
     *
     * @return string 
     */
    public function getConfigFile()
    {
        return $this->config_file;
    }

    /**
     * Set host
     *
     * @param string $host
     * @return state_schedulers
     */
    public function setHost($host)
    {
        $this->host = $host;
    
        return $this;
    }

    /**
     * Get host
     *
     * @return string 
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set need_db
     *
     * @param boolean $needDb
     * @return state_schedulers
     */
    public function setNeedDb($needDb)
    {
        $this->need_db = $needDb;
    
        return $this;
    }

    /**
     * Get need_db
     *
     * @return boolean 
     */
    public function getNeedDb()
    {
        return $this->need_db;
    }

    /**
     * Set tcp_port
     *
     * @param integer $tcpPort
     * @return state_schedulers
     */
    public function setTcpPort($tcpPort)
    {
        $this->tcp_port = $tcpPort;
    
        return $this;
    }

    /**
     * Get tcp_port
     *
     * @return integer 
     */
    public function getTcpPort()
    {
        return $this->tcp_port;
    }

    /**
     * Set udp_port
     *
     * @param integer $udpPort
     * @return state_schedulers
     */
    public function setUdpPort($udpPort)
    {
        $this->udp_port = $udpPort;
    
        return $this;
    }

    /**
     * Get udp_port
     *
     * @return integer 
     */
    public function getUdpPort()
    {
        return $this->udp_port;
    }

    /**
     * Set db
     *
     * @param string $db
     * @return state_schedulers
     */
    public function setDb($db)
    {
        $this->db = $db;
    
        return $this;
    }

    /**
     * Get db
     *
     * @return string 
     */
    public function getDb()
    {
        return $this->db;
    }

    /**
     * Set cpu_time
     *
     * @param float $cpuTime
     * @return state_schedulers
     */
    public function setCpuTime($cpuTime)
    {
        $this->cpu_time = $cpuTime;
    
        return $this;
    }

    /**
     * Get cpu_time
     *
     * @return float 
     */
    public function getCpuTime()
    {
        return $this->cpu_time;
    }

    /**
     * Set time
     *
     * @param \DateTime $time
     * @return state_schedulers
     */
    public function setTime($time)
    {
        $this->time = $time;
    
        return $this;
    }

    /**
     * Get time
     *
     * @return \DateTime 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set waits
     *
     * @param integer $waits
     * @return state_schedulers
     */
    public function setWaits($waits)
    {
        $this->waits = $waits;
    
        return $this;
    }

    /**
     * Get waits
     *
     * @return integer 
     */
    public function getWaits()
    {
        return $this->waits;
    }

    /**
     * Set waits_until
     *
     * @param \DateTime $waitsUntil
     * @return state_schedulers
     */
    public function setWaitsUntil($waitsUntil)
    {
        $this->waits_until = $waitsUntil;
    
        return $this;
    }

    /**
     * Get waits_until
     *
     * @return \DateTime 
     */
    public function getWaitsUntil()
    {
        return $this->waits_until;
    }

    /**
     * Set log_file
     *
     * @param string $logFile
     * @return state_schedulers
     */
    public function setLogFile($logFile)
    {
        $this->log_file = $logFile;
    
        return $this;
    }

    /**
     * Get log_file
     *
     * @return string 
     */
    public function getLogFile()
    {
        return $this->log_file;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return state_schedulers
     */
    public function setVersion($version)
    {
        $this->version = $version;
    
        return $this;
    }

    /**
     * Get version
     *
     * @return string 
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Set lock
     *
     * @param boolean $lock
     * @return state_schedulers
     */
    public function setLock($lock)
    {
        $this->lock = $lock;
    
        return $this;
    }

    /**
     * Get lock
     *
     * @return boolean 
     */
    public function getLock()
    {
        return $this->lock;
    }

    /**
     * Set wait_until
     *
     * @param \DateTime $waitUntil
     * @return state_schedulers
     */
    public function setWaitUntil($waitUntil)
    {
        $this->wait_until = $waitUntil;
    
        return $this;
    }

    /**
     * Get wait_until
     *
     * @return \DateTime 
     */
    public function getWaitUntil()
    {
        return $this->wait_until;
    }

    /**
     * Set spooler_name
     *
     * @param string $spoolerName
     * @return Schedulers
     */
    public function setSpoolerName($spoolerName)
    {
        $this->spooler_name = $spoolerName;
    
        return $this;
    }

    /**
     * Get spooler_name
     *
     * @return string 
     */
    public function getSpoolerName()
    {
        return $this->spooler_name;
    }

    /**
     * Set connection
     *
     * @param string $connection
     * @return Spoolers
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    
        return $this;
    }

    /**
     * Get connection
     *
     * @return string 
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set ip_address
     *
     * @param string $ipAddress
     * @return Spoolers
     */
    public function setIpAddress($ipAddress)
    {
        $this->ip_address = $ipAddress;
    
        return $this;
    }

    /**
     * Get ip_address
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * Set loop
     *
     * @param integer $loop
     * @return Spoolers
     */
    public function setLoop($loop)
    {
        $this->loop = $loop;
    
        return $this;
    }

    /**
     * Get loop
     *
     * @return integer 
     */
    public function getLoop()
    {
        return $this->loop;
    }

    /**
     * Set loops
     *
     * @param integer $loops
     * @return Spoolers
     */
    public function setLoops($loops)
    {
        $this->loops = $loops;
    
        return $this;
    }

    /**
     * Get loops
     *
     * @return integer 
     */
    public function getLoops()
    {
        return $this->loops;
    }

    /**
     * Set spooler
     *
     * @param \Arii\CoreBundle\Entity\Spooler $spooler
     * @return Spoolers
     */
    public function setSpooler(\Arii\CoreBundle\Entity\Spooler $spooler = null)
    {
        $this->spooler = $spooler;
    
        return $this;
    }

    /**
     * Get spooler
     *
     * @return \Arii\CoreBundle\Entity\Spooler 
     */
    public function getSpooler()
    {
        return $this->spooler;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Spoolers
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set enterprise
     *
     * @param \Arii\CoreBundle\Entity\Enterprise $enterprise
     * @return Spoolers
     */
    public function setEnterprise(\Arii\CoreBundle\Entity\Enterprise $enterprise = null)
    {
        $this->enterprise = $enterprise;
    
        return $this;
    }

    /**
     * Get enterprise
     *
     * @return \Arii\CoreBundle\Entity\Enterprise 
     */
    public function getEnterprise()
    {
        return $this->enterprise;
    }
}