<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FileOrders
 *
 * @ORM\Table(name="FOCUS_FILE_ORDERS")
 * @ORM\Entity
 */
class FileOrders
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
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\JobChains")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $job_chain;
    
    /**
     * @var string
     *
     * @ORM\Column(name="directory", type="string", length=512)
     */
    private $directory;

    /**
     * @var string
     *
     * @ORM\Column(name="regex", type="string", length=255)
     */
    private $regex;

    /**
     * @var boolean
     *
     * @ORM\Column(name="alert_when_directory_missing", type="boolean")
     */
    private $alertWhenDirectoryMissing;

    /**
     * @var integer
     *
     * @ORM\Column(name="delay_after_error", type="integer")
     */
    private $delayAfterError;

    /**
     * @var string
     *
     * @ORM\Column(name="next_state", type="string", length=100)
     */
    private $nextState;

    /**
     * @var integer
     *
     * @ORM\Column(name="retry", type="integer")
     */
    private $retry;

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
     * Set alertWhenDirectoryMissing
     *
     * @param boolean $alertWhenDirectoryMissing
     * @return FileOrderSources
     */
    public function setAlertWhenDirectoryMissing($alertWhenDirectoryMissing)
    {
        $this->alertWhenDirectoryMissing = $alertWhenDirectoryMissing;

        return $this;
    }

    /**
     * Get alertWhenDirectoryMissing
     *
     * @return boolean 
     */
    public function getAlertWhenDirectoryMissing()
    {
        return $this->alertWhenDirectoryMissing;
    }

    /**
     * Set delayAfterError
     *
     * @param integer $delayAfterError
     * @return FileOrderSources
     */
    public function setDelayAfterError($delayAfterError)
    {
        $this->delayAfterError = $delayAfterError;

        return $this;
    }

    /**
     * Get delayAfterError
     *
     * @return integer 
     */
    public function getDelayAfterError()
    {
        return $this->delayAfterError;
    }

    /**
     * Set directory
     *
     * @param string $directory
     * @return FileOrderSources
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;

        return $this;
    }

    /**
     * Get directory
     *
     * @return string 
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Set nextState
     *
     * @param string $nextState
     * @return FileOrderSources
     */
    public function setNextState($nextState)
    {
        $this->nextState = $nextState;

        return $this;
    }

    /**
     * Get nextState
     *
     * @return string 
     */
    public function getNextState()
    {
        return $this->nextState;
    }

    /**
     * Set regex
     *
     * @param string $regex
     * @return FileOrderSources
     */
    public function setRegex($regex)
    {
        $this->regex = $regex;

        return $this;
    }

    /**
     * Get regex
     *
     * @return string 
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * Set retry
     *
     * @param integer $retry
     * @return FileOrderSources
     */
    public function setRetry($retry)
    {
        $this->retry = $retry;

        return $this;
    }

    /**
     * Get retry
     *
     * @return integer 
     */
    public function getRetry()
    {
        return $this->retry;
    }
}
