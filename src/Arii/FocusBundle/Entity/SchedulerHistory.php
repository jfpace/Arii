<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Scheduler_history
 *
 * @ORM\Table(name="FOCUS_HISTORY")
 * @ORM\Entity(repositoryClass="Arii\FocusBundle\Entity\SchedulerHistoryRepository")
 */
class SchedulerHistory
{
    /**
     * @var integer
     *
     * @ORM\Column(name="ID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $ID;

    /**
     * @var integer
     *
     * @ORM\Column(name="REPOSITORY_ID", type="integer")
     */
    private $REPOSITORY_ID;

    /**
     * @var integer
     *
     * @ORM\Column(name="ID_HISTORY", type="integer")
     */
    private $ID_HISTORY;

    /**
     * @var string
     *
     * @ORM\Column(name="SPOOLER", type="string", length=100)
     */
    private $SPOOLER;

    /**
     * @var string
     *
     * @ORM\Column(name="CLUSTER_MEMBER", type="string", length=100)
     */
    private $CLUSTER_MEMBER;

    /**
     * @var string
     *
     * @ORM\Column(name="JOB_NAME", type="string", length=255)
     */
    private $JOB_NAME;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="START_TIME", type="datetime")
     */
    private $START_TIME;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="END_TIME", type="datetime")
     */
    private $END_TIME;

    /**
     * @var string
     *
     * @ORM\Column(name="CAUSE", type="string", length=50)
     */
    private $CAUSE;

    /**
     * @var integer
     *
     * @ORM\Column(name="STEPS", type="integer")
     */
    private $STEPS;

    /**
     * @var integer
     *
     * @ORM\Column(name="EXIT_CODE", type="integer")
     */
    private $EXIT_CODE;

    /**
     * @var boolean
     *
     * @ORM\Column(name="ERROR", type="boolean")
     */
    private $ERROR;

    /**
     * @var string
     *
     * @ORM\Column(name="ERROR_CODE", type="string", length=50)
     */
    private $ERROR_CODE;

    /**
     * @var string
     *
     * @ORM\Column(name="ERROR_TEXT", type="string", length=250)
     */
    private $ERROR_TEXT;

    /**
     * @var string
     *
     * @ORM\Column(name="PARAMETERS", type="text")
     */
    private $PARAMETERS;

    /**
     * @var string
     *
     * @ORM\Column(name="ITEM_START", type="string", length=250)
     */
    private $ITEM_START;

    /**
     * @var string
     *
     * @ORM\Column(name="ITEM_STOP", type="string", length=250)
     */
    private $ITEM_STOP;

    /**
     * @var integer
     *
     * @ORM\Column(name="PID", type="integer")
     */
    private $PID;

     /**
     * @var float
     *
     * @ORM\Column(name="AVG_RUNTIME", type="float")
     */
    private $AVG_RUNTIME;

     /**
     * @var float
     *
     * @ORM\Column(name="MIN_RUNTIME", type="float")
     */
    private $MIN_RUNTIME;

     /**
     * @var float
     *
     * @ORM\Column(name="MAX_RUNTIME", type="float")
     */
    private $MAX_RUNTIME;

    /**
     * @var integer
     *
     * @ORM\Column(name="COUNT", type="integer")
     */
    private $COUNT;

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
     * Set ID_HISTORY
     *
     * @param integer $IDHISTORY
     * @return Scheduler_history
     */
    public function setIDHISTORY($IDHISTORYID)
    {
        $this->ID_HISTORY = $IDHISTORYID;
    
        return $this;
    }

    /**
     * Get HISTORY_ID
     *
     * @return integer 
     */
    public function getIDHISTORYID()
    {
        return $this->ID_HISTORY;
    }

    /**
     * Set SPOOLER_ID
     *
     * @param string $sPOOLERID
     * @return Scheduler_history
     */
    public function setSPOOLERID($sPOOLERID)
    {
        $this->SPOOLER_ID = $sPOOLERID;
    
        return $this;
    }

    /**
     * Get SPOOLER_ID
     *
     * @return string 
     */
    public function getSPOOLERID()
    {
        return $this->SPOOLER_ID;
    }

    /**
     * Set CLUSTER_MEMBER_ID
     *
     * @param string $cLUSTERMEMBERID
     * @return Scheduler_history
     */
    public function setCLUSTERMEMBERID($cLUSTERMEMBERID)
    {
        $this->CLUSTER_MEMBER_ID = $cLUSTERMEMBERID;
    
        return $this;
    }

    /**
     * Get CLUSTER_MEMBER_ID
     *
     * @return string 
     */
    public function getCLUSTERMEMBERID()
    {
        return $this->CLUSTER_MEMBER_ID;
    }

    /**
     * Set JOB_NAME
     *
     * @param string $jOBNAME
     * @return Scheduler_history
     */
    public function setJOBNAME($jOBNAME)
    {
        $this->JOB_NAME = $jOBNAME;
    
        return $this;
    }

    /**
     * Get JOB_NAME
     *
     * @return string 
     */
    public function getJOBNAME()
    {
        return $this->JOB_NAME;
    }

    /**
     * Set START_TIME
     *
     * @param \DateTime $sTARTTIME
     * @return Scheduler_history
     */
    public function setSTARTTIME($sTARTTIME)
    {
        $this->START_TIME = $sTARTTIME;
    
        return $this;
    }

    /**
     * Get START_TIME
     *
     * @return \DateTime 
     */
    public function getSTARTTIME()
    {
        return $this->START_TIME;
    }

    /**
     * Set END_TIME
     *
     * @param \DateTime $eNDTIME
     * @return Scheduler_history
     */
    public function setENDTIME($eNDTIME)
    {
        $this->END_TIME = $eNDTIME;
    
        return $this;
    }

    /**
     * Get END_TIME
     *
     * @return \DateTime 
     */
    public function getENDTIME()
    {
        return $this->END_TIME;
    }

    /**
     * Set CAUSE
     *
     * @param string $cAUSE
     * @return Scheduler_history
     */
    public function setCAUSE($cAUSE)
    {
        $this->CAUSE = $cAUSE;
    
        return $this;
    }

    /**
     * Get CAUSE
     *
     * @return string 
     */
    public function getCAUSE()
    {
        return $this->CAUSE;
    }

    /**
     * Set STEPS
     *
     * @param integer $sTEPS
     * @return Scheduler_history
     */
    public function setSTEPS($sTEPS)
    {
        $this->STEPS = $sTEPS;
    
        return $this;
    }

    /**
     * Get STEPS
     *
     * @return integer 
     */
    public function getSTEPS()
    {
        return $this->STEPS;
    }

    /**
     * Set EXIT_CODE
     *
     * @param integer $eXITCODE
     * @return Scheduler_history
     */
    public function setEXITCODE($eXITCODE)
    {
        $this->EXIT_CODE = $eXITCODE;
    
        return $this;
    }

    /**
     * Get EXIT_CODE
     *
     * @return integer 
     */
    public function getEXITCODE()
    {
        return $this->EXIT_CODE;
    }

    /**
     * Set ERROR
     *
     * @param boolean $eRROR
     * @return Scheduler_history
     */
    public function setERROR($eRROR)
    {
        $this->ERROR = $eRROR;
    
        return $this;
    }

    /**
     * Get ERROR
     *
     * @return boolean 
     */
    public function getERROR()
    {
        return $this->ERROR;
    }

    /**
     * Set ERROR_CODE
     *
     * @param string $eRRORCODE
     * @return Scheduler_history
     */
    public function setERRORCODE($eRRORCODE)
    {
        $this->ERROR_CODE = $eRRORCODE;
    
        return $this;
    }

    /**
     * Get ERROR_CODE
     *
     * @return string 
     */
    public function getERRORCODE()
    {
        return $this->ERROR_CODE;
    }

    /**
     * Set ERROR_TEXT
     *
     * @param string $eRRORTEXT
     * @return Scheduler_history
     */
    public function setERRORTEXT($eRRORTEXT)
    {
        $this->ERROR_TEXT = $eRRORTEXT;
    
        return $this;
    }

    /**
     * Get ERROR_TEXT
     *
     * @return string 
     */
    public function getERRORTEXT()
    {
        return $this->ERROR_TEXT;
    }

    /**
     * Set PARAMETERS
     *
     * @param string $pARAMETERS
     * @return Scheduler_history
     */
    public function setPARAMETERS($pARAMETERS)
    {
        $this->PARAMETERS = $pARAMETERS;
    
        return $this;
    }

    /**
     * Get PARAMETERS
     *
     * @return string 
     */
    public function getPARAMETERS()
    {
        return $this->PARAMETERS;
    }

    /**
     * Set LOG
     *
     * @param string $lOG
     * @return Scheduler_history
     */
    public function setLOG($lOG)
    {
        $this->LOG = $lOG;
    
        return $this;
    }

    /**
     * Get LOG
     *
     * @return string 
     */
    public function getLOG()
    {
        return $this->LOG;
    }

    /**
     * Set ITEM_START
     *
     * @param string $iTEMSTART
     * @return Scheduler_history
     */
    public function setITEMSTART($iTEMSTART)
    {
        $this->ITEM_START = $iTEMSTART;
    
        return $this;
    }

    /**
     * Get ITEM_START
     *
     * @return string 
     */
    public function getITEMSTART()
    {
        return $this->ITEM_START;
    }

    /**
     * Set ITEM_STOP
     *
     * @param string $iTEMSTOP
     * @return Scheduler_history
     */
    public function setITEMSTOP($iTEMSTOP)
    {
        $this->ITEM_STOP = $iTEMSTOP;
    
        return $this;
    }

    /**
     * Get ITEM_STOP
     *
     * @return string 
     */
    public function getITEMSTOP()
    {
        return $this->ITEM_STOP;
    }

    /**
     * Set PID
     *
     * @param integer $pID
     * @return Scheduler_history
     */
    public function setPID($pID)
    {
        $this->PID = $pID;
    
        return $this;
    }

    /**
     * Get PID
     *
     * @return integer 
     */
    public function getPID()
    {
        return $this->PID;
    }

    /**
     * Get ID_HISTORY
     *
     * @return integer 
     */
    public function getIDHISTORY()
    {
        return $this->ID_HISTORY;
    }
}