<?php

/**
 * Nette patients
 *
 * @copyright  Copyright (c) 2010 Igor Hlina
 */



/**
 * Generator of graphs
 *
 * @author Igor Hlina
 */
class Graphs extends Object
{
    private $fontsPath = '';
    private $graphsPath = '';


    public function __construct()
    {
        $this->fontsPath = Environment::getConfig('variable')->fontsPath;
        $this->graphsPath = Environment::getConfig('variable')->graphsPath;
    }


  /* ************* *
   *   [C] r u d   *
   * ************* */

    /**
     * Create graph (a .png file)
     *
     * @param string $file
     */
    public function generateGraph($file)
    {
        $patientValues = Storage::getInstance()->getPatientsData($file);

        /*
         * pGraph work
         */
        $dataSet = new pData;

        if (!empty($patientValues)) {
            $dataSet->AddPoint(array_keys($patientValues[$file]), 'label');
            $dataSet->SetAbsciseLabelSerie('label');
            $serie1 = array_values($patientValues[$file]);
            $average = round(array_sum($serie1) / count($serie1), 2);
            $dataSet->AddPoint($serie1, "Serie1");
            $dataSet->AddSerie("Serie1");


            // Initialise the graph
            $graph = new MyHorBar(450, 600);
            $graph->setFontProperties($this->fontsPath . '/tahoma.ttf', 8);
            $graph->setGraphArea(120, 60, 410, 550);
            $graph->drawFilledRoundedRectangle(7, 7, 443, 593, 5, 240, 240, 240);
            $graph->drawRoundedRectangle(5, 5, 443, 595, 5, 230, 230, 230);
            $graph->drawGraphArea(255, 255, 255, true);
            $graph->drawHorScale($dataSet->GetData(), $dataSet->GetDataDescription(), SCALE_START0, 150, 150, 150, true, 0, 2, true);
            $graph->drawHorGrid(10, true, 230, 230, 230, 50);

            // Draw the 0 line
            $graph->setFontProperties($this->fontsPath . '/tahoma.ttf', 6);
            $graph->drawTreshold($average, 143, 55, 72, true, false, 2, null, 90);

            // Draw the bar graph
            $graph->drawHorBarGraph($dataSet->GetData(), $dataSet->GetDataDescription(), false);

            // Finish the graph
            $graph->setFontProperties($this->fontsPath . '/tahoma.ttf', 10);
            $graph->drawLegend(15, 15, $dataSet->GetDataDescription(), 255, 255, 255);
            $graph->setFontProperties($this->fontsPath . '/tahoma.ttf', 10);
            $graph->drawTitle(170, 27, $file, 50, 50, 50, -1);

        } else {
            $graph = new pChart(450, 150);
            $graph->setGraphArea(120, 60, 410, 100);
            $graph->drawFilledRoundedRectangle(7, 7, 443, 143, 5, 240, 240, 240);
            $graph->drawRoundedRectangle(5, 5, 443, 145, 5, 230, 230, 230);
            $graph->setFontProperties($this->fontsPath . '/tahoma.ttf', 10);
            $graph->drawTitle(170, 27, $file, 50, 50, 50, -1);
            $graph->setFontProperties($this->fontsPath . '/tahoma.ttf', 36);
            $graph->drawTitle(125, 90, 'No data!', 245, 50, 50, -1);
        }

        $graph->Render(WWW_DIR . '/images/graphs/' . $file . '.png');
    }



  /* ************* *
   *   c [R] u d   *
   * ************* */

    /**
     * Return list of images in graphs storage directory
     * Also generates missing graphs
     *
     * @return array
     */
    public function getGraphs()
    {
        // see what graphs are needed
        $fields = Storage::getInstance()->getStoredFields();
        foreach ($fields as $field => $props) {
            if (in_array('graph', $props['classes']))
                $grapedhProps[] = $field;
        }

        $iterator = 0;
        do {
            $iterator++;
            if ($iterator > 3)
                throw new AbortException('Generating of missing graphs failed!');

            // get list of already generated graphs
            $imageFiles = $this->getGeneratedGraphs();
            $alreadyGenerated = array_intersect($grapedhProps, $imageFiles);
            $needToGenerate = array_diff($grapedhProps, $alreadyGenerated);

            foreach ($needToGenerate as $file) {
                $this->generateGraph($file);
            }

        } while (!empty($needToGenerate));

        return $imageFiles;
    }


    /**
     * Return list of generated graphs
     *
     * @return array
     */
    public function getGeneratedGraphs()
    {
        $files = glob($this->graphsPath.'/*');

        $out = array();
        if ($files) {
            foreach ($files as $file) {
                $fileInfo = pathinfo($file);
                if ($fileInfo['extension'] == 'png') // only PNGs are interesting
                    $out[] = $fileInfo['filename'];
            }
        }

        return $out;
    }



  /* ************* *
   *   c r u [D]   *
   * ************* */

    /**
     * Delete all graphs
     *
     * @return void
     */
    public function clearAll()
    {
        $imagesFiles = $this->getGeneratedGraphs();

        foreach ($imagesFiles as $file) {
            unlink($this->graphsPath . '/' . $file . '.png');
        }
    }



  /* ************************************************ *
   *                     utility                      *
   * ************************************************ */

    /**
     * Return instance of class
     *
     * @return Graphs
     */
	public static function model()
    {
        $class = __CLASS__;
        return new $class;
    }

}
