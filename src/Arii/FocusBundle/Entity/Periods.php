<?php

namespace Arii\FocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Periods
 *
 * @ORM\Table(name="FOCUS_PERIODS")
 * @ORM\Entity
 */
class Periods
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
     * @ORM\ManyToOne(targetEntity="Arii\FocusBundle\Entity\Schedules")
     * @ORM\JoinColumn(nullable=true)
     **/
    private $schedule;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="time", nullable=true)
     */
    private $startTime;

    /**
     * @var string
     *
     * @ORM\Column(name="start_type", type="string", length=15, nullable=true)
     */
    private $startType;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="begin", type="time", nullable=true)
     */
    private $begin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end", type="time", nullable=true)
     */
    private $end;

    /**
     * @var string
     *
     * @ORM\Column(name="months", type="string", length=26, nullable=true)
     */
    private $months;
    
    /**
     * @var string
     *
     * @ORM\Column(name="day_type", type="string", length=12, nullable=true)
     */
    private $day_type;
    
    /**
     * @var string
     *
     * @ORM\Column(name="days", type="string", length=84, nullable=true)
     */
    private $days;

    /**
     * @var integer
     *
     * @ORM\Column(name="which", type="integer", nullable=true)
     */
    private $which;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=true)
     */
    private $date;
    
    /**
     * @var string
     *
     * @ORM\Column(name="when_holiday", type="string", length=25, nullable=true)
     */
    private $when_holiday;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="let_run", type="boolean", nullable=true)
     */
    private $let_run;

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
     * Set singleStart
     *
     * @param \DateTime $singleStart
     * @return Periods
     */
    public function setSingleStart($singleStart)
    {
        $this->singleStart = $singleStart;

        return $this;
    }

    /**
     * Get singleStart
     *
     * @return \DateTime 
     */
    public function getSingleStart()
    {
        return $this->singleStart;
    }

    /**
     * Set absoluteRepeat
     *
     * @param \DateTime $absoluteRepeat
     * @return Periods
     */
    public function setAbsoluteRepeat($absoluteRepeat)
    {
        $this->absoluteRepeat = $absoluteRepeat;

        return $this;
    }

    /**
     * Get absoluteRepeat
     *
     * @return \DateTime 
     */
    public function getAbsoluteRepeat()
    {
        return $this->absoluteRepeat;
    }

    /**
     * Set begin
     *
     * @param \DateTime $begin
     * @return Periods
     */
    public function setBegin($begin)
    {
        $this->begin = $begin;

        return $this;
    }

    /**
     * Get begin
     *
     * @return \DateTime 
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * Set end
     *
     * @param \DateTime $end
     * @return Periods
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Get end
     *
     * @return \DateTime 
     */
    public function getEnd()
    {
        return $this->end;
    }
}
