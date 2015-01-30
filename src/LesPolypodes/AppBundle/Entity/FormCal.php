<?php

    namespace LesPolypodes\AppBundle\Entity;

    class FormCal
    {
        protected $name;
        protected $startDate;
        protected $endDate;
        // protected $startTime;
        // protected $endTime;
        protected $location;
        protected $description;

        public function getName()
        {
            return $this->name;
        }
        public function setName($name)
        {
            $this->name = $name;
        }

        public function getStartDate()
        {
            return $this->startDate;
        }
        
        public function setStartDate(\DateTime $startDate = null)
        {
            $this->startDate = $startDate;
        }

       public function getEndDate()
       {
            return $this->endDate;
       }

       public function setEndDate(\DateTime $endDate = null)
       {
            $this->endDate = $endDate;
       }

       // public function getStartTime()
       // {
       //      return $this->startTime;
       // }

       // public function setStartTime(Time $startTime)
       // {
       //      $this->startTime = $startTime;
       // }

       // public function getEndTime()
       // {
       //      return $this->endTime;
       // }

       // public function setEndTime(Time $endTime)
       // {
       //      $this->endTime = $endTime;
       // }

       public function getDescription()
       {
            return $this->description;
       }

       public function setDescription($description)
       {
            $this->description = $description;
       }

    }