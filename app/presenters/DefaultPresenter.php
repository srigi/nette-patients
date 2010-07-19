<?php

/**
 * Nette patients
 *
 * @copyright  Copyright (c) 2010 Igor Hlina
 */



/**
 * Main Presenter handling CRUD actions on Storage
 *
 * @author Igor Hlina
 */
class DefaultPresenter extends BasePresenter
{

  /* ************************************************ *
   *                     actions                      *
   * ************************************************ */

    /**
     * Form - add patient
     */
	public function actionAdd()
	{
        // set callback for submit
        $this['formPatientProperties']->onSubmit[] = array($this, 'SubmittedPatientAdd');
    }


	/**
     * Form - edit patient's properties
     */
	public function actionEdit($id)
	{
        // set callback for submit
        $this['formPatientProperties']->onSubmit[] = array($this, 'SubmittedPatientPropertiesEdit');
    }


	public function actionDelete()
    {
    	if (!$this->getParam('id')) {
    		throw new BadRequestException('Invalid request', 400);
    	} else {
    		$id = $this->getParam('id');
    	}

        Storage::getInstance()->delete($id);
        Graphs::model()->clearAll();
        $this->redirect('Default');
        
    }



  /* ************************************************ *
   *                      views                       *
   * ************************************************ */

	public function renderDefault()
	{
       	$patients = Storage::getInstance()->getAllPatients();
        $graphs = Graphs::model()->getGraphs();
        $this->template->patients = $patients;
        $this->template->graphs = $graphs;
    }


	/**
     * Form - edit patient's properties
     */
	public function renderEdit($id)
	{
        if ($id) {
            $patient = Storage::getInstance()->getPatientById($id);
            if (!$patient)
                $this->redirect('default');

        } else {
            $this->redirect('default');
        }

        $form = $this['formPatientProperties'];

        // prefill form with stored data
        $fields = Storage::getInstance()->getStoredFields();
        foreach ($fields as $field => $props) {
            $form[$field]->setValue($patient[$field]);
        }
    }



  /* ************************************************ *
   *               component factories                *
   * ************************************************ */

    /*
     * Form - edit patient's properties
     */
    protected function createComponentFormPatientProperties($name)
    {
        $form = new AppForm($this, $name);

        // construct form fields by first patient
        $fields = Storage::getInstance()->getStoredFields();
        foreach ($fields as $field => $props) {
            $form->addText($field, $field)
                 ->addRule(Form::FILLED, 'Nesmie byt prazdne!');
        }

        $form->addSubmit('save', 'Uložiť');
        return $form;
    }



  /* ************************************************ *
   *                    callbacks                     *
   * ************************************************ */

    /*
     * Form - edit patient's properties
     */
    public function SubmittedPatientPropertiesEdit(AppForm $form)
    {
        $id = $this->getParam('id');

        if ($id === null) {
            throw new BadRequestException('Invalid request', 400);
        }

        Storage::getInstance()->savePatientProperties($id, $form->values);
        Graphs::model()->clearAll();

        $this->flashMessage('Parametre pacienta boli úspešne uložené', 'info');
        $this->redirect('default'); // redirect to listing
    }


    /*
     * Form - add patient
     */
    public function SubmittedPatientAdd(AppForm $form)
    {
        Storage::getInstance()->createPatient($form->values);
        Graphs::model()->clearAll();

        $this->flashMessage('Novy pacient bol úspešne pridaný', 'info');
        $this->redirect('default'); // redirect to listing
    }

}
