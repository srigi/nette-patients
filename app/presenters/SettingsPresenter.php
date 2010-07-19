<?php

/**
 * Nette patients
 *
 * @copyright  Copyright (c) 2010 Igor Hlina
 */



/**
 * Presenter handling setup of patients properties
 *
 * @author Igor Hlina
 */
class SettingsPresenter extends BasePresenter
{

  /* ************************************************ *
   *                     actions                      *
   * ************************************************ */

    /**
     * Invoke regeneration of all graphs
     */
    public function actionGrefresh()
    {
        Graphs::model()->clearAll();
        $this->redirect('Default:');
    }



  /* ************************************************ *
   *                      views                       *
   * ************************************************ */

    /*
     * Form - Graphs settings
     */
    public function renderDefault()
    {
        $formGraphsSettings = $this['formGraphsSettings'];
        // prefill form with stored data
        $fields = Storage::getInstance()->getStoredFields();
        foreach ($fields as $field => $props) {
            if (in_array('graph', $props['classes']))
                $formGraphsSettings[$field]->setValue(true);
        }

    }



  /* ************************************************ *
   *               component factories                *
   * ************************************************ */

    /*
     * Form - Graphs settings
     */
    protected function createComponentFormGraphsSettings($name)
    {
        $form = new AppForm($this, $name);

        // construct form fields by first patient
        $fields = Storage::getInstance()->getStoredFields();
        foreach ($fields as $field => $props) {
            $form->addCheckbox($field, $field);
        }

        $form->addSubmit('save', 'Uložiť');
        $form->onSubmit[] = array($this, 'SubmittedGraphsSettings');
        return $form;
    }



  /* ************************************************ *
   *                    callbacks                     *
   * ************************************************ */

    /*
     * Form - Graphs settings
     */
    public function SubmittedGraphsSettings(AppForm $form)
    {
        Storage::getInstance()->saveGraphsSettings($form->values);

        $this->flashMessage('Parametre grafov boli úspešne uložené', 'info');
        $this->redirect('default');
    }

}
