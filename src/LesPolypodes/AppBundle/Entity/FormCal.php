<?php

    namespace LesPolypodes\AppBundle\Entity;

    class FormCal
    {
        protected $name;
        protected $startDate;
        protected $endDate;
        protected $startTime;
        protected $endTime;
        protected $location;
        protected $description;
        protected $price;

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

       public function getStartTime()
        {
            return $this->startTime;
        }
        
        public function setStartTime(\DateTime $startTime = null)
        {
            $this->startTime = $startTime;
        }

        public function getEndTime()
        {
            return $this->endTime;
        }
        
        public function setEndTime(\DateTime $endTime = null)
        {
            $this->endTime = $endTime;
        }

       public function getLocation()
       {
            return $this->location;
       }

       public function setLocation($location)
       {
            $this->location = $location;
       }

       public function getPrice()
       {
          return $this->price;
       }

       public function setPrice($price)
       {
          $this->price = $price;
       }

       public function getDescription()
       {
            return $this->description;
       }

       public function setDescription($description)
       {
            $this->description = $description;
       }

    }