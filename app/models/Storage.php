<?php

/**
 * Nette patients
 *
 * @copyright  Copyright (c) 2010 Igor Hlina
 */



/**
 * Model representing XML storage file
 *
 * @author Igor Hlina
 */
class Storage extends Object
{
    /*
     * Path to XML file
     */
	protected $path = '';

    /*
     * DOMDocument
     */
    protected $dom  = null;

    /*
     * Singleton for Storage class
     */
    protected static $instance = null;


    /**
     * Setups path to data file and load that file as DOMDocument
     *
     * Instantiate using {@link getInstance()}; Storage is a singleton!
     *
     * @return void
     */
    protected function __construct()
    {
    	$this->path = Environment::getConfig('variable')->dataFile;
    	$this->loadFile();
    }


    /**
     * Enforce singleton; disallow cloning
     *
     * @return void
     */
    private function __clone()
    {
    }



  /* ************* *
   *   [C] r u d   *
   * ************* */

    /**
     * Create new patients
     * Save properties of new patient to XML file
     *
     * @param array $data
     */
    public function createPatient($data)
    {
        $patients = $this->dom->getElementsByTagName('patient');

        // generate ID for new patient
        if (!$patients->length) { // no patients in Storage
            $id = 1;
        } else { // there are some patients in Storage
            // find very last patient in Storage
            $lastIndex = $patients->length - 1;
            $lastNode = $patients->item($lastIndex);
            $lastId = $lastNode->getAttribute('id');
            $id = $lastId + 1; // ID for new patient will be greater by 1 from last patient ID
        }

        // construct XML tree of nodes of new patient
        $newPatientNode = $this->dom->createElement('patient');
        $newPatientNode->setAttribute('id', $id);

        $this->dom->getElementsByTagName('patients') // atach node to the end
                   ->item(0)
                   ->appendChild($newPatientNode);

        foreach ($this->getStoredFields() as $field => $props) { // create all "properties holding" nodes
            $node = $this->dom->createElement($field);
            $node->nodeValue = $data[$field]; // fill with received value
            $newPatientNode->appendChild($node);
        }

        $this->saveFile();
    }



  /* ************* *
   *   c [R] u d   *
   * ************* */

    /**
     * Return all patients with properties in nice array
     *
     * @return array
     */
	public function getAllPatients()
	{
        $patients = $this->dom->getElementsByTagName('patient');

        // construct array representing patients nodes
        $out = array();
        foreach ($patients as $patient) {
        	$id = $patient->getAttribute('id');
        	$out[$id] = array();
        	foreach ($patient->childNodes as $prop) {
                $out[$id][$prop->nodeName] = $prop->nodeValue;
        	}
        }

        return $out;
	}


    /**
     * Return properties of the first patient in nice array
     *
     * @return array
     */
	public function getFirstPatient()
    {
        $first = $this->dom->getElementsByTagName('patient')->item(0);

        // construct array representing patient node
        $out['_ID'] = $first->getAttribute('id');
        foreach ($first->childNodes as $prop) {
            $out[$prop->nodeName] = $prop->nodeValue;
        }

        return $out;
    }


    /**
     * Return properties of patient selected by ID in nice array
     *
     * @param integer $id
     * @return array
     */
    public function getPatientById($id)
    {
        $patient = $this->getElementById($id);

        if (!$patient)
            return null;

        // construct array representing patient node
        $out['_ID'] = $patient->getAttribute('id');
        foreach ($patient->childNodes as $prop) {
            $out[$prop->nodeName] = $prop->nodeValue;
        }

        return $out;
    }


    /**
     * Return the list of stored properties of patients in nice array
     * Include informations about flags (classes) of properties
     *
     * @return array
     */
    public function getStoredFields()
    {
        // list is stored in <template> tag
        $fields = $this->dom
                       ->getElementsByTagName('template')
                       ->item(0)
                       ->getElementsByTagName('fields')
                       ->item(0);

        // construct array
        foreach ($fields->childNodes as $prop) {
            $nodeClass = $prop->getAttribute('class');
            $classes = array();

            if (!empty($nodeClass))
                $classes = explode(' ', $nodeClass);

            $out[$prop->nodeValue] = array('classes'=>$classes);
        }

        return $out;
    }


    /**
     * Return values of all patients for selected property in nice array
     *
     * @param string $field
     * @return array
     */
    public function getPatientsData($field)
    {
        $patients = $this->dom->getElementsByTagName('patient');
        $out = array();

        // iterate trought patients
        foreach ($patients as $patient) {
            // iterate trought patient's fields
            foreach ($patient->childNodes as $prop) {
                if ($prop->nodeName == $field) {
                    $out[$prop->nodeName][$patient->childNodes->item(0)->nodeValue] = (int) $prop->nodeValue;
                }
            }
        }

        return $out;
    }



  /* ************* *
   *   c r [U] d   *
   * ************* */

    /**
     * Store new values of patient's properties for selected patient
     *
     * @param integer $id
     * @param array $values
     */
	public function savePatientProperties($id, $values)
    {
        $patient = $this->getElementById($id);

        foreach ($patient->childNodes as $prop) {
            $prop->nodeValue = $values[$prop->nodeName];
        }

        $this->saveFile();
    }


    /**
     * Update 'graph flags' on properties
     *
     * @param array $values
     */
    public function saveGraphsSettings($values)
    {
        // flags are saved in <template> tag as class attribute
        $fields = $this->dom->getElementsByTagName('template')
                             ->item(0)
                             ->getElementsByTagName('fields')
                             ->item(0);

        foreach ($fields->childNodes as $field) {
            // cache class attribute, will need more times
            $nodeClass = $newNodeClass = $field->getAttribute('class');

            // determine if node is to be signed by graph class
            $giveGraphFlag = $values[$field->nodeValue];

            // determine if node have a graph class
            $isGraph = (bool) strstr($nodeClass, 'graph');

            if (!$isGraph && $giveGraphFlag) { // add graph class
                if (empty($nodeClass)) {  // just add graph class
                    $newNodeClass = 'graph';
                } else {  // concatenate with existing classes
                    $newNodeClass = $nodeClass . ' graph';
                }

            } elseif ($isGraph && !$giveGraphFlag) { // remove graph class
                if (strpos($nodeClass, ' ')) {  // have multiple classes?
                    $newNodeClass = str_replace('graph', '', $nodeClass);
                } else {
                    $newNodeClass = '';
                }
            }

            $field->setAttribute('class', trim($newNodeClass));
        }

        $this->saveFile();
    }



  /* ************* *
   *   c r u [D]   *
   * ************* */

    /**
     * Delete selected patient
     *
     * @param integer $id
     */
    public function delete($id)
	{
        $element = $this->getElementById($id);
        if (!$element)
            throw new BadRequestException('Patient not foud!');

        $rootNode = $this->dom->getElementsByTagName('patients')->item(0);
        $rootNode->removeChild($element);

        $this->saveFile();
	}



  /* ************************************************ *
   *                     utility                      *
   * ************************************************ */

    /**
     * Create Singleton instance
     *
     * @return Storage
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }


    /**
     * Load XML file as DOMDocument
     *
     * @return void
     */
    private function loadFile()
    {
        if (get_class($this->dom) == 'DOMDocument') {
            return;
        } else {
            $this->dom = new DomDocument();
            $this->dom->preserveWhiteSpace = false;
            $this->dom->load($this->path);
        }
    }


    /**
     * Save actual node tree in DOMDocument into file
     */
    private function saveFile()
    {
        $this->dom->formatOutput = true;
        $this->dom->save($this->path);
    }


    /**
     * Find DOMElement in DOMDocument by ID
     *
     * @param integer $id
     * @return DOMElement
     */
    private function getElementById($id)
    {
        $xpath = new DOMXPath($this->dom);
        return $xpath->query("//*[@id='$id']")->item(0);
    }

}
