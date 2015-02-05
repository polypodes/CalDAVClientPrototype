<?php

    namespace LesPolypodes\AppBundle\Entity;

    class FormCal
    {
        protected $name;
        protected $startDate;
        protected $endDate;
        protected $location;
        protected $description;
        protected $price;
        protected $organizer;

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

       public function getOrganizer()
       {
            return $this->organizer;
       }

       public function setOrganizer($organizer)
       {
            $this->organizer = $organizer;
       }


    }