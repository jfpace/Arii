<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * RemoteSchedulers
 *
 * @ORM\Table(name="FOCUS_REMOTE_SCHEDULERS")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\RemoteSchedulersRepository")
 */
class RemoteSchedulers
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
     * @ORM\OneToOne(targetEntity="Arii\FocusBundle\Entity\Spoolers")
     * @ORM\JoinColumn(nullable=true)
     **/
     private $spooler;

     /**
     * @var boolean
     *
     * @ORM\Column(name="configuration_changed", type="boolean", nullable=true)
     */
    private $configurationChanged;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="configuration_changed_at", type="datetime", nullable=true)
     */
    private $configurationChangedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="configuration_transfered_at", type="datetime", nullable=true)
     */
    private $configurationTransferedAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="connected", type="boolean")
     */
    private $connected;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="connected_at", type="datetime")
     */
    private $connectedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="hostname", type="string", length=128)
     */
    private $hostname;

    /**
     * @var string
     *
     * @ORM\Column(name="ip", type="string", length=15)
     */
    private $ip;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=32)
     */
    private $name;

    /**
     * @var integer
     *
     * @ORM\Column(name="tcp_port", type="integer")
     */
    private $tcpPort;

    /**
     * @var string
     *
     * @ORM\Column(name="version", type="string", length=10)
     */
    private $version;

    /**
     * @var string
     *
     * @ORM\Column(name="error", type="string", length=128, nullable=true)
     */
    private $error;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="error_at", type="datetime", nullable=true)
     */
    private $errorAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="disconnected_at", type="datetime", nullable=true)
     */
    private $disconnectedAt;

    /**
     * @var string
     *
     * @ORM\Column(name="crc", type="string", length=9, nullable=true)
     */
    private $crc;

    /**
     * @var integer
     *
     * @ORM\Column(name="updated", type="integer")
     */
    private $updated;

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
     * Set configurationChanged
     *
     * @param boolean $configurationChanged
     * @return RemoteSchedulers
     */
    public function setConfigurationChanged($configurationChanged)
    {
        $this->configurationChanged = $configurationChanged;
    
        return $this;
    }

    /**
     * Get configurationChanged
     *
     * @return boolean 
     */
    public function getConfigurationChanged()
    {
        return $this->configurationChanged;
    }

    /**
     * Set configurationChangedAt
     *
     * @param \DateTime $configurationChangedAt
     * @return RemoteSchedulers
     */
    public function setConfigurationChangedAt($configurationChangedAt)
    {
        $this->configurationChangedAt = $configurationChangedAt;
    
        return $this;
    }

    /**
     * Get configurationChangedAt
     *
     * @return \DateTime 
     */
    public function getConfigurationChangedAt()
    {
        return $this->configurationChangedAt;
    }

    /**
     * Set configurationTransferedAt
     *
     * @param \DateTime $configurationTransferedAt
     * @return RemoteSchedulers
     */
    public function setConfigurationTransferedAt($configurationTransferedAt)
    {
        $this->configurationTransferedAt = $configurationTransferedAt;
    
        return $this;
    }

    /**
     * Get configurationTransferedAt
     *
     * @return \DateTime 
     */
    public function getConfigurationTransferedAt()
    {
        return $this->configurationTransferedAt;
    }

    /**
     * Set connected
     *
     * @param boolean $connected
     * @return RemoteSchedulers
     */
    public function setConnected($connected)
    {
        $this->connected = $connected;
    
        return $this;
    }

    /**
     * Get connected
     *
     * @return boolean 
     */
    public function getConnected()
    {
        return $this->connected;
    }

    /**
     * Set connectedAt
     *
     * @param \DateTime $connectedAt
     * @return RemoteSchedulers
     */
    public function setConnectedAt($connectedAt)
    {
        $this->connectedAt = $connectedAt;
    
        return $this;
    }

    /**
     * Get connectedAt
     *
     * @return \DateTime 
     */
    public function getConnectedAt()
    {
        return $this->connectedAt;
    }

    /**
     * Set hostname
     *
     * @param string $hostname
     * @return RemoteSchedulers
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    
        return $this;
    }

    /**
     * Get hostname
     *
     * @return string 
     */
    public function getHostname()
    {
        return $this->hostname;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return RemoteSchedulers
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    
        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return RemoteSchedulers
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
     * Set tcpPort
     *
     * @param integer $tcpPort
     * @return RemoteSchedulers
     */
    public function setTcpPort($tcpPort)
    {
        $this->tcpPort = $tcpPort;
    
        return $this;
    }

    /**
     * Get tcpPort
     *
     * @return integer 
     */
    public function getTcpPort()
    {
        return $this->tcpPort;
    }

    /**
     * Set version
     *
     * @param string $version
     * @return RemoteSchedulers
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
     * Set errorCode
     *
     * @param string $errorCode
     * @return RemoteSchedulers
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
    
        return $this;
    }

    /**
     * Get errorCode
     *
     * @return string 
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set errorText
     *
     * @param string $errorText
     * @return RemoteSchedulers
     */
    public function setErrorText($errorText)
    {
        $this->errorText = $errorText;
    
        return $this;
    }

    /**
     * Get errorText
     *
     * @return string 
     */
    public function getErrorText()
    {
        return $this->errorText;
    }

    /**
     * Set errorAt
     *
     * @param \DateTime $errorAt
     * @return RemoteSchedulers
     */
    public function setErrorAt($errorAt)
    {
        $this->errorAt = $errorAt;
    
        return $this;
    }

    /**
     * Get errorAt
     *
     * @return \DateTime 
     */
    public function getErrorAt()
    {
        return $this->errorAt;
    }

    /**
     * Set disconnectedAt
     *
     * @param \DateTime $disconnectedAt
     * @return RemoteSchedulers
     */
    public function setDisconnectedAt($disconnectedAt)
    {
        $this->disconnectedAt = $disconnectedAt;
    
        return $this;
    }

    /**
     * Get disconnectedAt
     *
     * @return \DateTime 
     */
    public function getDisconnectedAt()
    {
        return $this->disconnectedAt;
    }

    /**
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return RemoteSchedulers
     */
    public function setSpooler(\Arii\FocusBundle\Entity\Spoolers $spooler = null)
    {
        $this->spooler = $spooler;
    
        return $this;
    }

    /**
     * Get spooler
     *
     * @return \Arii\FocusBundle\Entity\Spoolers 
     */
    public function getSpooler()
    {
        return $this->spooler;
    }

    /**
     * Set supervisor
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $supervisor
     * @return RemoteSchedulers
     */
    public function setSupervisor(\Arii\FocusBundle\Entity\Spoolers $supervisor = null)
    {
        $this->supervisor = $supervisor;
    
        return $this;
    }

    /**
     * Get supervisor
     *
     * @return \Arii\FocusBundle\Entity\Spoolers 
     */
    public function getSupervisor()
    {
        return $this->supervisor;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return RemoteSchedulers
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
}