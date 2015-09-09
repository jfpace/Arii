<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Connections
 *
 * @ORM\Table(name="FOCUS_CONNECTIONS")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\ConnectionsRepository")
 */
class Connections
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
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Spoolers")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $spooler;

    /**
     * @var string
     *
     * @ORM\Column(name="operation_type", type="string", length=5)
     */
    private $operationType;

    /**
     * @var integer
     *
     * @ORM\Column(name="responses", type="bigint")
     */
    private $responses;

    /**
     * @var integer
     *
     * @ORM\Column(name="received_bytes", type="bigint")
     */
    private $receivedBytes;

    /**
     * @var integer
     *
     * @ORM\Column(name="send_bytes", type="bigint")
     */
    private $sendBytes;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", length=20)
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="host_ip", type="string", length=15)
     */
    private $hostIp;

    /**
     * @var integer
     *
     * @ORM\Column(name="port", type="integer")
     */
    private $port;

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
     * Set operationType
     *
     * @param string $operationType
     * @return Connections
     */
    public function setOperationType($operationType)
    {
        $this->operationType = $operationType;
    
        return $this;
    }

    /**
     * Get operationType
     *
     * @return string 
     */
    public function getOperationType()
    {
        return $this->operationType;
    }

    /**
     * Set responses
     *
     * @param integer $responses
     * @return Connections
     */
    public function setResponses($responses)
    {
        $this->responses = $responses;
    
        return $this;
    }

    /**
     * Get responses
     *
     * @return integer 
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Set receivedBytes
     *
     * @param integer $receivedBytes
     * @return Connections
     */
    public function setReceivedBytes($receivedBytes)
    {
        $this->receivedBytes = $receivedBytes;
    
        return $this;
    }

    /**
     * Get receivedBytes
     *
     * @return integer 
     */
    public function getReceivedBytes()
    {
        return $this->receivedBytes;
    }

    /**
     * Set sendBytes
     *
     * @param integer $sendBytes
     * @return Connections
     */
    public function setSendBytes($sendBytes)
    {
        $this->sendBytes = $sendBytes;
    
        return $this;
    }

    /**
     * Get sendBytes
     *
     * @return integer 
     */
    public function getSendBytes()
    {
        return $this->sendBytes;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return Connections
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
     * Set hostIp
     *
     * @param string $hostIp
     * @return Connections
     */
    public function setHostIp($hostIp)
    {
        $this->hostIp = $hostIp;
    
        return $this;
    }

    /**
     * Get hostIp
     *
     * @return string 
     */
    public function getHostIp()
    {
        return $this->hostIp;
    }

    /**
     * Set port
     *
     * @param integer $port
     * @return Connections
     */
    public function setPort($port)
    {
        $this->port = $port;
    
        return $this;
    }

    /**
     * Get port
     *
     * @return integer 
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Set spooler
     *
     * @param \Arii\FocusBundle\Entity\Spoolers $spooler
     * @return Connections
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
     * Set updated
     *
     * @param \DateTime $updated
     * @return Connections
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