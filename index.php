<?php
header('Content-Type:text/html; charset=ISO-8859-1');

// use sessions
session_start();

// get language preference
if (isset($_GET["lang"])) {
    $language = $_GET["lang"];
}
else if (isset($_SESSION["lang"])) {
    $language  = $_SESSION["lang"];
}
else {
    $language = "en_US";
}

// save language preference for future page requests
$_SESSION["lang"]  = $language;

$folder = "locale";
$domain = "messages";
//$encoding = "UTF-8";
$encoding = "ISO-8859-15";

putenv("LANG=" . $language); 
setlocale(LC_ALL, $language);

bindtextdomain($domain, $folder); 
bind_textdomain_codeset($domain, $encoding);

textdomain($domain);

/* MAPPING FILE FORMAT
CRITERIA;CRITERIA_POS;CORRESPONDING_FIELD;CONVERSION_TYPE;START;SIZE;MANDATORY;TYPE;NAME;DESCRIPTION
*/

// Class to determine gaps in format
class LineSlot {
    public function __construct($start_value, $end_value) {
        $this->start = $start_value;
        $this->end = $end_value;
    }

    public function getStart() {
        return $this->start;
    }

    public function getEnd() {
        return $this->end;
    }

    public function setStart($new_start) {
        $this->start = $new_start;
    }

    public function setEnd($new_end) {
        $this->end = $new_end;
    }

    public function compare($slot1, $slot2) {
        if ($slot1->getStart() == $slot2->getStart())
            return 0;
        return ($slot1->getStart() < $slot2->getStart())?-1:1;
    }

    private $start;
    private $end;
}

// Class to determine gaps in format
class Line {
    public function __construct() {
        $this->line_slots = array();
    }

    public function insertSlot($slot)
    {
        $this->line_slots[] = $slot;
    }

    public function getFreeSlots() {

        $this->merge();

        if (count($this->line_slots) == 1)
            return Array();

        $unused_start = 1;
        $unused_slots = Array();
        foreach($this->line_slots as $used_slot) {
            if ($used_slot->getStart() > $unused_start) {
                $unused_slots[] = new LineSlot($unused_start, $used_slot->getStart() - $unused_start);
            }
            $unused_start = $used_slot->getEnd();
        }
        return $unused_slots;
    }

    private function merge() {
        usort($this->line_slots, array("LineSlot","compare"));

        $current = 0; $len = count($this->line_slots);
        for ($next = 1; $next < $len; ++$next)
        {
                if ($this->line_slots[$next]->getStart() > $this->line_slots[$current]->getEnd())
                        $current = $next;
                else
                {
                        if ($this->line_slots[$current]->getEnd() < $this->line_slots[$next]->getEnd())
                                $this->line_slots[$current]->setEnd($this->line_slots[$next]->getEnd());
                        unset($this->line_slots[$next]);
                }
        }

        $this->line_slots = array_values($this->line_slots);
    }

    private $line_slots=Array();
}

class MappingRecord {
        public function __construct($d01_field, $conversion_type, $start, $size, $mandatory, $type, $name, $description) {
                $this->d01_field = $d01_field;
                $this->conversion_type = $conversion_type;
                $this->start = intval($start);
                $this->size = intval($size);
                $this->mandatory = $mandatory;
                $this->type = $type;
                $this->name = $name;
                $this->description = $description;
                $this->isParsed = false;
        }

        public function __destruct() {

        }

        public function parse($line, &$d01_data, &$data_totals, $CSV_separator = '') {

                // If separator is defined, we must parse data as a demimited file (like a CSV file)
                if ($CSV_separator != '') {
                    $this->data = str_getcsv($line, $CSV_separator);
                    $this->data = $this->data[$this->start-1];
                } else {
                    $this->data = substr($line, $this->start-1, $this->size);
                }
                $this->isParsed = true;

                $this->isInputNull=false;
            if ($this->data=='' || $this->data=="\x0A" || $this->data=="\x0D" || $this->data=="\x0D\x0A") {
                $this->data=str_pad($this->data, $this->size, ' ');
                $this->isInputNull=true;
            }

            if ($this->getD01Field() != '') {
                    $d01fields = split(',',$this->getD01Field());
                    if (is_array($d01fields)) {
                            foreach($d01fields as $d01field) {
                                    $d01_data[$d01field]=$this->data;
                                    if ($this->getConversionType() != '') {
                                            switch($this->getConversionType()) {
                                                    case 'AAMMJJ':
                                                            $d01_data[$d01field]=substr($this->data,4,2).'/'.substr($this->data,2,2).'/'.substr($this->data,0,2);
                                                    break;
                                                    case 'CP2DPT':
                                                                   $d01_data[$d01field]=substr(substr("000000".$this->data,-6),1,2);
                                                    break;
                                                    case 'AAAAMMJJ':
                                                            $d01_data[$d01field]=substr($this->data,6,2).'/'.substr($this->data,4,2).'/'.substr($this->data,2,2);
                                                    break;
                                                    case 'JJMMAAAA':
                                                            $d01_data[$d01field]=substr($this->data,0,6);
                                                    break;
                                                    case 'TRIM':
                                                                    $d01_data[$d01field]=trim($this->data);
                                                    break;
                                                    case 'INTVAL':
                                                            $d01_data[$d01field]=intval($this->data);
                                                    break;
                                                    case 'FLOATVAL':
                                                            $d01_data[$d01field]=floatval($this->data);
                                                    break;
                                                    case 'VOLUMELEBO': // 0082251 = 0,082251 metres cubes => 000000000000008 (D01)
                                                                    $d01_data[$d01field]=substr('000000000000'.$this->data,0,15);
                                                    break;
                                                    case 'G2KG': // Grammes vers KG
                                                                    $d01_data[$d01field]=number_format($this->data/1000,2);
                                                    break;
                                                    case 'SWCPREST':
                                                        switch($this->data) {
                                                            case 'RE':
                                                                $d01_data[$d01field]='10';
                                                            break;
                                                            case 'GO':
                                                                $d01_data[$d01field]='31';
                                                            break;
                                                            default:
                                                                $d01_data[$d01field]='10';
                                                            break;
                                                        }
                                                    break;
                                                    case 'UNM49': // Codes pays numériques selon la norme UN M.49
                                                        switch(intval($this->data)) {
                                                            case 56:
                                                                $d01_data[$d01field]='BE';
                                                            break;
                                                            case 250:
                                                                $d01_data[$d01field]='FR';
                                                            break;
                                                            case 724:
                                                                $d01_data[$d01field]='ES';
                                                            break;
                                                            case 380:
                                                                $d01_data[$d01field]='IT';
                                                            break;
                                                            case 620:
                                                                $d01_data[$d01field]='PT';
                                                            break;
                                                            case 276:
                                                                $d01_data[$d01field]='DE';
                                                            break;
                                                            default:
                                                                $d01_data[$d01field]='FR';
                                                            break;
                                                        }
                                                    break;
                                                    default:
                                                                    throw new Exception('Conversion type "'.$this->getConversionType().'" unknown');
                                                    break;
                                            }
                                            }
                                            // Adding to total
                                            switch($d01field) {
                                                    case 'P0_POIDS':
                                                    case 'P0_UNITES_MANUTENTION':
                                                    case 'P0_NOMBRE_COLIS':
                                                    case 'P0_NOMBRE_PALETTES_EUROPE':
                                                    case 'L0_NOMBRE_PALETTES_EUROPE':
                                                    case 'L0_NOMBRE_PALETTES_PERDUE':
                                                    case 'L0_NOMBRE_PALETTES_DIVERSE':
                                                    case 'P0_VOLUME':
                                                    $data_totals[$d01field]+=$d01_data[$d01field];
                                                    break;
                                            }
                                    }
                            }
            }

        }


        // Accessors
        public function getD01Field() { return $this->d01_field; }
        public function getConversionType() { return $this->conversion_type; }
        public function getStart() { return $this->start; }
        public function getSize() { return $this->size; }
        public function getEnd() { return $this->start + $this->size; }
        public function getMandatory() { return $this->mandatory; }
        public function getType() { return $this->type; }
        public function getName() { return $this->name; }
        public function getDescription() { return $this->description; }
        public function getData() {
                if ($this->isParsed) {
                        return $this->data;
                } else {
                       throw new Exception(_("Can't get data when record has not been parsed"));
                }
        }

        //
        public function isDeclaredAsMandatory() { return $this->mandatory == 'o' || $this->mandatory == 'O' || $this->mandatory == 'Y' || $this->mandatory == 'y' || $this->mandatory == '1'; }
        public function isDeclardedAsNumeric() { return $this->type == 'N' || $this->type == "n"; }
        public function isDeclardedAsAlphaNumeric() { return !$this->isDeclardedAsNumeric(); }
        public function isInputNull() {
                if ($this->isParsed) {
                        return $this->isInputNull;
                } else {
                        throw new Exception(_("Can't tell if input is null when record has not been parsed"));
                }
        }

        // Error detection functions
        public function hasMandatoryError() {
                if ($this->isParsed) {
                        if ($this->isDeclaredAsMandatory()) {
                                return str_replace("0","",str_replace(" ","",$this->data))=="";
                        } else {
                                return false;
                        }
                } else {
                        throw new Exception(_("Can't check for errors when record has not been parsed"));
                }
        }
        public function hasNumericError() {
                if ($this->isParsed) {

                        if ($this->isDeclardedAsNumeric()) {
                                return preg_match("/[^0-9\.]/",$this->data)>0;
                        } else {
                                return false;
                        }
                } else {
                        throw new Exception(_("Can't check for errors when record has not been parsed"));
                }
        }

        public function compare($record1, $record2) {
            if ($record1->getStart() == $record2->getStart())
                return 0;
            return ($record1->getStart() < $record2->getStart())?-1:1;
        }

        private $d01_field;
        private $conversion_type;
        private $start;
        private $size;
        private $mandatory;
        private $type;
        private $name;
        private $description;
        private $data;
        private $isParsed;
        private $isInputNull;
}

class MappingCriteria {
        public function __construct($criteria, $start) {
                $this->criteria = $criteria;
                $this->start = $start;
                $this->records = array();
        }

        public function __destruct() {

        }

        public function isMatched($line) {
        	// Check if criteria could be treated as regexp
        	if ( strlen($this->criteria) > 2 && preg_match('%\/(.+)\/[gimsx]*%six', $this->criteria)) {
        			return preg_match($this->criteria, $line);
        	} else { // Criteria treated as litteral string
			if ( strcmp( substr($line,$this->start-1, strlen($this->criteria)), $this->criteria ) == 0)
			    return true;
			else
			    return false;
                }
        }

        public function parse($line, &$d01_data, &$data_totals) {
                foreach($this->records as $record) {
                        if ($this->criteria == 'CSV') {
                            $separator = ($this->start == '')?';':$this->start;
                        } else {
                            $separator = '';
                        }
                        $record->parse($line, $d01_data, $data_totals, $separator);
                }
        }

        public function generateHtmlTableHeader($shortDisplayMode) {
                $return_html = '<tr>';
                foreach($this->records as $record) {
                        if ($shortDisplayMode) {
                                $return_html .= '<th title=""><a href="#">'.substr($record->getName(),0,$record->getSize()).'<span><strong>'._("Field").' :</strong> '.$record->getName().'<hr /><strong>'._("Position").' :</strong> '.$record->getStart().'<br /><strong>'._("Size").' :</strong> '.$record->getSize().'<br /><strong>'._("Type (A/N)").' :</strong> '.$record->getType().'<br /><strong>'._("mandatory (Y/N)").' :</strong> '.$record->getMandatory().'<br /><strong>'._("Info").' :</strong> '.$record->getDescription().'</span></a></th>';
                        } else {
                                $return_html .= '<th title=""><a href="#">'.$record->getName().'<span><strong>'._("Field").' :</strong> '.$record->getName().'<hr /><strong>'._("Position").' :</strong> '.$record->getStart().'<br /><strong>'._("Size").' :</strong> '.$record->getSize().'<br /><strong>'._("Type (A/N)").' :</strong> '.$record->getType().'<br /><strong>'._("mandatory (Y/N)").' :</strong> '.$record->getMandatory().'<br /><strong>'._("Info").' :</strong> '.$record->getDescription().'</span></a></th>';
                        }
                }
                $return_html .= '</tr>';
                return $return_html;
        }

        public function generateHtmlTableData() {
                $return_html = '<tr>';
                foreach($this->records as $record) {
                        $error_comment = '';
                        $background_color_class = '';

                        if ($record->isDeclaredAsMandatory()) { $background_color_class="mandatory"; }
                        if ($record->hasNumericError()) { $background_color_class="errornum"; $error_comment.=_("Numerical field with letters")." / "; }
                        if ($record->hasMandatoryError()) { $background_color_class="errormandatory"; $error_comment.=_("Empty mandatory field")." / ";}
                        if ($record->isInputNull()) { $background_color_class="void"; $error_comment.=_("Empty field")." / ";}



                        $data = $record->getData();
		        if ($background_color_class!="")
			        $return_html.='<td nowrap class="'.$background_color_class.'" title="'.substr($error_comment,0,strlen($error_comment)-3).'">';
		        else
			        $return_html.='<td nowrap>';

                        $data=str_replace(' ','&nbsp;',$data);
		        $data=str_replace("\x0A",'<strong class="specialchar">&lt;LF&gt;</strong>',$data);
		        $data=str_replace("\x0D",'<strong class="specialchar">&lt;CR&gt;</strong>',$data);
		        $data=str_replace("\t",'<strong class="specialchar">&lt;TAB&gt;</strong>',$data);

                // MORY barcodes detection
		        $data = preg_replace("/(4(?:2[0-9]{24}|19[0-9]{23}))/","<a href=\"cab_mory.php?cab=\\1\" onclick=\"window.open(this.href, 'position', 'height=560, width=873, top='+(screen.height-560)/2+', left='+(screen.width-873)/2+', toolbar=no, menubar=no, location=no, resizable=no, scrollbars=no, status=no'); return false;\">\\1</a>",$data);

		        $return_html.=$data;
		        $return_html.='</td>';
                }
                $return_html .= '</tr>';
                return $return_html;
        }

        public function addRecord($d01_field, $conversion_type, $start, $size, $mandatory, $type, $name, $description) {
                $this->records[] = new MappingRecord($d01_field, $conversion_type, $start, $size, $mandatory, $type, $name, $description);
        }

        public function getRecords() {
            return $this->records;
        }

        public function sortRecordsByStartPosition() {
            return usort($this->records, array("MappingRecord","compare"));
        }

        public function getValue() { return $this->criteria; }
        public function getStart() { return $this->start; }

        private $criteria;
        private $start; // Position to get criteria
        private $records; // array of MappingRecord elements
}


class Mapping {

        // PUBLIC

        // Constructor : $mapping_formats_file_path is the path to files formats descriptors
        public function __construct($mapping_formats_file_path='.') {
                $this->mapping_formats_file_path = $mapping_formats_file_path;
                $this->data_totals=array();
                $this->d01_data=array();
                $this->criterias=array();
                $this->shipment_data=array();
                $this->number_of_shipments_to_be_displayed=0;
        }

        // Parse file format description
        public function parseFormat($mapping_format_file, $displayGaps=false) {
                if ($mapping_format_file == '')
                        throw new Exception(_("No mapping file specified"));
                $handle = @fopen($this->mapping_formats_file_path.'/'.$mapping_format_file,'r');
                if (!$handle) { throw new Exception(sprintf(_("Can't open mapping file specified ('%s')"), $this->mapping_formats_file_path.'/'.$mapping_format_file)); }
                $line_number=0;
                while (!feof($handle)) {
                        $line_number++;
                        $line = fgets($handle);
                        // Ignore header line
                        if ($line_number>1 && $line!='' && $line != "\r" && $line != "\n" && $line != "\r\n" && $line!="\t") {
                                $data[] = split(';',$line);
                                end($data);
                                $current_data_index = key($data);
                                $criteria_index = $this->getOrCreateCriteriaIndex($data[$current_data_index][Mapping::CRITERIA_FIELD_INDEX],$data[$current_data_index][Mapping::CRITERIA_POS_FIELD_INDEX]);
                                $this->criterias[$criteria_index]->addRecord($data[$current_data_index][Mapping::D01_CORRESPONDING_FIELD_INDEX], $data[$current_data_index][Mapping::CONVERSION_TYPE_FIELD_INDEX], $data[$current_data_index][Mapping::START_FIELD_INDEX], $data[$current_data_index][Mapping::SIZE_FIELD_INDEX], $data[$current_data_index][Mapping::MANDATORY_FIELD_INDEX], $data[$current_data_index][Mapping::TYPE_FIELD_INDEX], $data[$current_data_index][Mapping::NAME_FIELD_INDEX], $data[$current_data_index][Mapping::DESCRIPTION_FIELD_INDEX]);
                        }
                }

                // Displaying format's unspecified fields
                if ($displayGaps) {
                    foreach($this->criterias as $criteria) {
                        $line = new Line();
                        foreach($criteria->getRecords() as $record) {
                            $line->insertSlot(new LineSlot($record->getStart(), $record->getEnd()));
                        }
                        $free_slots = $line->getFreeSlots();
                        foreach($free_slots as $free_slot) {
                            $criteria->addRecord('', '', $free_slot->getStart(), $free_slot->getEnd(), '', '', '<b>'._("unknown").'</b>', _("unspecified in format"));
                        }
                        $criteria->sortRecordsByStartPosition();
                    }
                }
        }

        // Parse data
        public function parseData($data, $shortDisplayMode=false, $displayTotals=false, $displayTotalsOnly=false, $maskShipmentsHeaders=false, $maskUnknownLines=false) {
                $return_html = '';
                if (get_magic_quotes_gpc()) {
                        $data=explode("\n",stripslashes($data));
                } else {
                        $data=explode("\n",$data);
                }

                $this->shipment_data = '';
                $this->number_of_shipments_to_be_displayed=0;
                $this->d01_data=array();
                foreach($data as $i=>$line) {
                        // S'il y a plus d'une ligne et qu'il ne s'agit pas de la derniere ligne
                        // on ajoute le retour à la ligne supprime par la fonction 'explode'
                        if (count($data)>1 && $i!=count($data)-1)
                            $line .= "\n";
                        if ($line != '') {
                            $criteria_index = $this->getFirstMatchedCriteriaIndex($line);
                            if ($criteria_index != -1) {
                                    if ($criteria_index==0) {
                                            if ($this->number_of_shipments_to_be_displayed>0) {
                                                    $return_html.=$this->getShipmentOutput($maskShipmentsHeaders);
                                                    $this->d01_data=array();
                                                    $this->shipment_data='';
                                            }
                                            $this->number_of_shipments_to_be_displayed++;
                                    }
                                    $this->shipment_data .= '<table>';
                                    $this->shipment_data .= $this->criterias[$criteria_index]->generateHtmlTableHeader($shortDisplayMode);
                                    $this->criterias[$criteria_index]->parse($line, $this->d01_data, $this->data_totals);
                                    $this->shipment_data .= $this->criterias[$criteria_index]->generateHtmlTableData();
                                    $this->shipment_data .= '</table>';
                            } else if(!$maskUnknownLines) {
                                    // Unknown format
                                    $line=str_replace("\x0A",'<strong class="specialchar">&lt;LF&gt;</strong>',$line);
                                    $line=str_replace("\x0D",'<strong class="specialchar">&lt;CR&gt;</strong>',$line);
                                    $line=str_replace("\t",'<strong class="specialchar">&lt;TAB&gt;</strong>',$line);
                                    $this->shipment_data .= '<table class="error"><tr><th title="'._("unknown").'">'._("Unknown").'</th></tr><tr><td>'.$line.'</td></tr></table>';
                            }
                        }
                }

                //if ($this->number_of_shipments_to_be_displayed) {
                        $return_html.=$this->getShipmentOutput($maskShipmentsHeaders);
                        $this->d01_data=array();
                        $this->shipment_data='';
                //}

                $this->data_totals['NOMBRE_EXPEDITIONS']=$this->number_of_shipments_to_be_displayed;
                if ($displayTotals)
                        $return_html.=$this->getTotalsOutput();
                if ($displayTotalsOnly)
                    $return_html='<div class="warningbox">'._("Hidden data").' ('._("option")." <em>'"._("display only totals")."')</em></div>".$this->getTotalsOutput();

                return $return_html;
        }

        // Get all available data formats
        public function getAvailableFormats() {
                return $this->list_files_preg($this->mapping_formats_file_path, '/.+\.csv/i',true);
        }

        // Destructor
        public function __destruct() {

        }

        // Constants
        const CRITERIA_FIELD_INDEX = 0;
        const CRITERIA_POS_FIELD_INDEX = 1;
        const D01_CORRESPONDING_FIELD_INDEX = 2;
        const CONVERSION_TYPE_FIELD_INDEX = 3;
        const START_FIELD_INDEX = 4;
        const SIZE_FIELD_INDEX = 5;
        const MANDATORY_FIELD_INDEX = 6;
        const TYPE_FIELD_INDEX = 7;
        const NAME_FIELD_INDEX = 8;
        const DESCRIPTION_FIELD_INDEX = 9;

        // PRIVATE

        private $mapping_formats_file_path;
        private $criterias; // array of MappingCriteria elements
        private $d01_data; // Array of corresponding data in D01 by shipment
        private $data_totals; // Total values for the whole file/data stream
        private $shipment_data; // Array of shipments to be displayed
        private $number_of_shipments_to_be_displayed;

        private function getFirstMatchedCriteriaIndex($line) {
                foreach($this->criterias as $index=>$criteria) {
                        if ($criteria->isMatched($line))
                                return $index;
                }
                $index = $this->getCriteriaIndex('CSV');
                return $index;
        }

        private function getOrCreateCriteriaIndex($criteria_value, $criteria_start) {
                if (is_array($this->criterias) && (count($this->criterias)>0) ) {
                        foreach($this->criterias as $index=>$criteria)
                        {
                                if ($criteria_value == $criteria->getValue())
                                        return $index;
                        }

                        // Adding a new MappingCriteria
                        $this->criterias[]=new MappingCriteria($criteria_value,$criteria_start);
                        end($this->criterias);
                        return key($this->criterias);
                } else {
                        // Adding a new MappingCriteria
                        $this->criterias[]=new MappingCriteria($criteria_value,$criteria_start);
                        end($this->criterias);
                        return key($this->criterias);
                }
        }

        private function getCriteriaIndex($criteria_value) {
                if (is_array($this->criterias) && (count($this->criterias)>0) ) {
                        foreach($this->criterias as $index=>$criteria)
                        {
                                if ($criteria_value == $criteria->getValue())
                                        return $index;
                        }
                        return -1;
                }
                return -1;
        }

        private function list_files_preg($path, $preg_expr='/.+/', $sortbyfilenameasc=false) {
                $files=array();
                $dir_handle = @opendir($path);
                if (!$dir_handle) {
                        throw new Exception(sprintf(_("Can't list files in specified directory ('%s')"),$path));
                }
                else {
                        while (false !== ($filename = @readdir($dir_handle))) {
                                if (!is_dir($path.'/'.$filename) && strstr($filename, '.php')==false && preg_match($preg_expr,$filename)) {
                                        $files[]=$filename;
                                }
                        }
                }

                if ($sortbyfilenameasc) {
                                natcasesort($files);
                }

                return $files;
        }

        private function getDisplayInAspLink()
        {
                $construct_url='';
                if (is_array($this->d01_data)) {
                        foreach($this->d01_data as $field=>$data) {
                                $construct_url.=$field.'='.$data.'&';
                        }

                        return '<a href="afficher_position.php?'.substr($construct_url,0,-1).'" onclick="window.open(this.href, \'position\', \'height=560, width=873, top=\'+(screen.height-560)/2+\', left=\'+(screen.width-873)/2+\', toolbar=no, menubar=no, location=no, resizable=no, scrollbars=no, status=no\'); return false;">Affichage ASP</a>';
                } else {
                        return '';
                }
        }

        private function getShipmentOutput($maskShipmentsHeaders)
        {
                if (!$maskShipmentsHeaders)
                        return '<h2>'._("Record").' '.$this->number_of_shipments_to_be_displayed.'&nbsp;'.$this->getDisplayInAspLink().'</h2>'.$this->shipment_data;
                else
                        return $this->shipment_data;
        }

        private function getTotalsOutput() {
                $return_html='<p>&nbsp;</p><h2>'._("Totals").'</h2><table><tr><th>'._("Field").'</th><th>'._("Total").'</th></tr>';
                if (is_array($this->data_totals)) {
                        foreach($this->data_totals as $variable=>$total) {
                                if ($variable=='NOMBRE_EXPEDITIONS') {
                                        $return_html.='<tr><td class="mandatory">'.$variable.'</td><td class="mandatory">'.$total.'</td></tr>';
                                } else {
                                        $return_html.='<tr><td>'.$variable.'</td><td>'.$total.'</td></tr>';
                                }
                        }
                }
                $return_html.='</table>';
                return $return_html;
        }
}


/*
AUTHOR : udo dot schroeter at gmail dot com 26-May-2007 06:40
SCRIPT URL : http://php.net/manual/fr/function.eval.php
COMMENTS :
Safer Eval

eval() is used way to often. It slows down code, makes it harder to maintain and it created security risks. However, sometimes, I found myself wishing I could allow some user-controlled scripting in my software, without giving access to dangerous functions.

That's what the following class does: it uses PHP's tokenizer to parse a script, compares every function call against a list of allowed functions. Only if the script is "clean", it gets eval'd.

Usage example:
<?php
  $ls = new SaferScript('horribleCode();');

  $ls->allowHarmlessCalls();
  print_r($ls->parse());
  $ls->execute();
*/
class SaferScript {
    var $source, $allowedCalls, $data;

    function SaferScript($scriptText, $data='') {
      $this->source = $scriptText;
      $this->allowedCalls = array();
      $this->data = $data;
    }

    function allowHarmlessCalls() {
      $this->allowedCalls = explode(',',
        'explode,implode,date,time,round,rand,ceil,floor,srand,'.
        'strtolower,strtoupper,substr,stristr,strpos,print,print_r,'.
        'foreach,return,break,substr_replace,str_pad,intval,'.
        'intval,floatval,strval,true,false,STR_PAD_LEFT,STR_PAD_RIGHT,STR_PAD_BOTH,bin2hex');
    }

    function parse() {
      $this->parseErrors = array();
      $tokens = token_get_all('<?'.'php '.$this->source.' ?'.'>');
      $vcall = '';

      foreach ($tokens as $token) {
        if (is_array($token)) {
          $id = $token[0];
          switch ($id) {
            case(T_VARIABLE): { $vcall .= 'v'; break; }
            case(T_STRING): { $vcall .= 's'; }
            case(T_REQUIRE_ONCE): case(T_REQUIRE): case(T_NEW): case(T_RETURN):
            case(T_BREAK): case(T_CATCH): case(T_CLONE): case(T_EXIT):
            case(T_PRINT): case(T_GLOBAL): case(T_ECHO): case(T_INCLUDE_ONCE):
            case(T_INCLUDE): case(T_EVAL): case(T_FUNCTION): {
              if (array_search($token[1], $this->allowedCalls) === false)
                $this->parseErrors[] = _("illegal call").': '.$token[1];
            }
          }
        }
        else
          $vcall .= $token;
      }

      if (stristr($vcall, 'v(') != '')
        $this->parseErrors[] = array(_("illegal dynamic function call"));

      return($this->parseErrors);
    }

    function execute($parameters = array()) {
      foreach ($parameters as $k => $v)
        $$k = $v;

      $data = $this->data;
      if (get_magic_quotes_gpc()) {
          $data=stripslashes($data);
      } else {
          $data=$data;
      }

      if (sizeof($this->parseErrors) == 0)
        return eval($this->source);
      else
        return false;//print('cannot execute, script contains errors');
    }
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<link rel="stylesheet" type="text/css" media="screen, projection" href="default_theme.css" />
<link rel="icon" type="image/png" href="favicon.png" />
<script src="js/jquery-1.8.2.min.js" type="text/javascript"></script>
<script src="ace/ace.js" type="text/javascript"></script>
<script language="Javascript">
function hide(id) {
	if (document.getElementById) {
		document.getElementById(id).style.display = 'none';
	}
	else {
		if (document.layers) {
			document.id.display = 'none';
		}
		else {
			document.all.id.style.display = 'none';
		}
	}
}

function show(id) {
	if (document.getElementById) {
		document.getElementById(id).style.display = 'block';
	}
	else {
		if (document.layers) {
			document.id.display = 'block';
		}
		else {
			document.all.id.style.display = 'block';
		}
	}
}

function setvalue(id, value, value_unescape) {
    if (value_unescape) { value = unescape(value); }
	if (document.getElementById) {
		document.getElementById(id).value = value;
	}
	else {
		if (document.layers) {
			document.id.value = value;
		}
		else {
			document.all.id.value = value;
		}
	}
}

</script>

</head>
<body>
<div id="page">
<div id="header"></div>
<div id="content">
<div id="lang">
    <?php echo _("Language"); ?>
    &nbsp;
    <a href="?lang=fr_FR">Fran&ccedil;ais&nbsp;<img src="img/fr.gif" /></a>
    &nbsp;
    <a href="?lang=en_US">English&nbsp;<img src="img/gb.gif" /></a>
</div>
<div id="help">
    <a href="doc.php" onclick="window.open(this.href, 'documentation', 'height=600, width=1024, top=100, left=100, toolbar=yes, menubar=yes, location=yes, resizable=yes, scrollbars=yes, status=yes'); return false;"><?php echo _("Documentation"); ?>&nbsp;<img src="img/help.png" /></a>
</div>
<div id="data">
<h1><?php echo _("Analysis results"); ?> (<?php if (isset($_POST['fileformat'])) echo str_replace('_',' ',substr($_POST['fileformat'],0,-4)); ?>)&nbsp;<a href="#form"><?php echo _("jump to  source data form"); ?></a></h1>

<div id="legende">
<h2><?php echo _("Fields color code"); ?></h2>
<table>
<tr>
<td class=""></td>
<td class="noborder"><?php echo _("optional"); ?></td>
<td class="noborder">&nbsp;</td>
<td class="mandatory"></td>
<td class="noborder"><?php echo _("mandatory"); ?></td>
<td class="noborder">&nbsp;</td>
<td class="errornum"></td>
<td class="noborder"><?php echo _("optional error"); ?></td>
<td class="noborder">&nbsp;</td>
<td class="errormandatory"></td>
<td class="noborder"><?php echo _("mandatory error"); ?></td>
<td class="noborder">&nbsp;</td>
<td class="void"></td>
<td class="noborder">vide</td>
</tr>
</table>

</div>


<?php
    try {
        $myMapping = new Mapping('formats');

        if (isset($_POST['shortDisplayMode']) && $_POST['shortDisplayMode'])
                $shortDisplayMode=true;
        else
                $shortDisplayMode=false;

        if (isset($_POST['displayTotals']) && $_POST['displayTotals'])
                $displayTotals=true;
        else
                $displayTotals=false;

        if (isset($_POST['displayTotalsOnly']) && $_POST['displayTotalsOnly'])
                $displayTotalsOnly=true;
        else
                $displayTotalsOnly=false;

        if (isset($_POST['maskShipmentsHeaders']) && $_POST['maskShipmentsHeaders'])
                $maskShipmentsHeaders=true;
        else
                $maskShipmentsHeaders=false;

        if (isset($_POST['executeCustomScript']) && $_POST['executeCustomScript'])
                $executeCustomScript=true;
        else
                $executeCustomScript=false;

        if (isset($_POST['displayCustomScriptOutput']) && $_POST['displayCustomScriptOutput'])
                $displayCustomScriptOutput=true;
        else
                $displayCustomScriptOutput=false;

        if (isset($_POST['maskUnknownLines']) && $_POST['maskUnknownLines'])
                $maskUnknownLines=true;
        else
                $maskUnknownLines=false;

        if (isset($_POST['displayGaps']) && $_POST['displayGaps'])
                $displayGaps=true;
        else
                $displayGaps=false;

        $convertToUTF8=false;
        $convertToISO885915=false;
        $convertToNone=true;                
        if (isset($_POST['convert'])) {
            
            switch($_POST['convert']) {
                case 'convertToUTF8':
                    $convertToUTF8=true;
                break;
                case 'convertToISO885915':
                    $convertToISO885915=true;
                break;
                case 'convertToNone':
                    $convertToNone=true;
                break;
                default:
                    $convertToNone=true;
                break;
            }
        }
        

        $modified_data = '';
        $data = '';
        if (isset($_POST['compute_data']) && $_POST['compute_data'] == '1') {
            $data = $_POST["data"];

            if ($convertToUTF8)
                $data = iconv("UTF-8", "ISO-8859-15", $data);

            if ($convertToISO885915)
                $data = iconv("ISO-8859-15", "UTF-8", $data);

            $customscript = $_POST["customscript"];
            // Strip PHP opening and closing tags
            $customscript = preg_replace('/^<\?php(.*)\?>$/s', '$1', $_POST["customscript"]);
            if ($executeCustomScript && $customscript!='') {
                $modified_data = $data;
                $ls = new SaferScript($customscript, $modified_data);
                $ls->allowHarmlessCalls();
                $parse_errors = $ls->parse();
                if (count($parse_errors)>0) {
                    $modified_data = _("Error raised").' :'."\n".print_r($parse_errors, true);
                } else {
                    $modified_data = $ls->execute();
                    if ($modified_data!=NULL && !$modified_data)
                        $modified_data = _("Error in script. Please check its format");
                    if ($modified_data==NULL)
                        $modified_data = _("Nothing was returned by the script");
                }
            }

            if ($displayCustomScriptOutput && $executeCustomScript) {
                $data = $modified_data;
            }
            
            $myMapping->parseFormat($_POST['fileformat'], $displayGaps);
            echo $myMapping->parseData($data,$shortDisplayMode,$displayTotals,$displayTotalsOnly, $maskShipmentsHeaders, $maskUnknownLines);
        }

?>

<p>&nbsp;</p>
</div>
<div id="form">
<h1><?php echo _("Source data"); ?></h1>
<a name="form"></a>
<form name="send_data" onsubmit="" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post">
<?php echo _("Data format"); ?>&nbsp;
<select name="fileformat">
<?php
        foreach($myMapping->getAvailableFormats() as $format) {
            echo '<option ';
            if (isset($_POST['fileformat']) && $_POST['fileformat']==$format)
                echo 'selected="selected" ';
            echo 'value="'.$format.'">'.str_replace('_',' ',substr($format,0,-4)).'</option>';
        }
?>
</select>
<textarea name="data">
<?php
        if (isset($_POST['compute_data']) && $_POST['compute_data'] == '1') {
            echo $_POST["data"];
        }
?>
</textarea>
<input type="checkbox" id="shortDisplayMode" <?php if ($shortDisplayMode) echo 'checked="checked"'; ?> name="shortDisplayMode" value="1" /> <?php echo _("<b>compact</b> mode"); ?><br />
<input type="checkbox" id="displayTotals" <?php if ($displayTotals) echo 'checked="checked"'; ?> name="displayTotals" value="1" /> <?php echo _("display <b>totals</b>"); ?><br />
<input type="checkbox" id="displayTotalsOnly" <?php if ($displayTotalsOnly) echo 'checked="checked"'; ?> name="displayTotalsOnly" value="1" /> <?php echo _("display <b>totals only</b>"); ?><br />
<input type="checkbox" id="maskShipmentsHeaders" <?php if ($maskShipmentsHeaders) echo 'checked="checked"'; ?> name="maskShipmentsHeaders" value="1" /> <?php echo _("hide each record <b>title</b>"); ?><br />
<input type="checkbox" id="maskUnknownLines" <?php if ($maskUnknownLines) echo 'checked="checked"'; ?> name="maskUnknownLines" value="1" /> <?php echo _("hide lines with <b>undefined</b> format"); ?><br />
<input type="checkbox" id="displayGaps" <?php if ($displayGaps) echo 'checked="checked"'; ?> name="displayGaps" value="1" /> <?php echo _("display data <b>gaps</b>"); ?><br />
<p><?php echo _("Data charset"); ?> :<br/>
<input type="radio" id="convertToNone" name="convert" value="convertToNone" <?php if ($convertToNone) echo 'checked="checked"'; ?> name="convertToNone" value="1" /> <b><?php echo _("none"); ?></b><br />
<input type="radio" id="convertToUTF8" name="convert" value="convertToUTF8" <?php if ($convertToUTF8) echo 'checked="checked"'; ?> name="convertToUTF8" value="1" /> <?php echo _("in"); ?> <b>UTF-8</b><br />
<input type="radio" id="convertToISO885915" name="convert" value="convertToISO885915" <?php if ($convertToISO885915) echo 'checked="checked"'; ?> name="convertToISO885915" value="1" /> <?php echo _("in"); ?> <b>ISO-8859-15</b><br />
</p>

<input type="submit" name="send_data1" value="<?php echo _("Analyse"); ?>" />
<p>&nbsp;</p>
<input type="checkbox" <?php if ($executeCustomScript) echo 'checked="checked"'; ?> name="executeCustomScript" value="1" onchange="//if (this.checked) {show('customscript_div');} else {hide('customscript_div');};" /> <?php echo _("advanced <b>scripting</b>"); ?><br />
<div id="customscript_div" style="display:<?php echo ($executeCustomScript)?'block':'block'; ?>;">
<?php echo _("PHP script"); ?> (<a href="#customscriptform" onclick="editor.setValue(unescape('%3C%3F%70%68%70%0A//%20on%20eclate%20les%20donnees%20par%20ligne%20sous%20forme%20de%20tableau%0A%24data%3Dexplode%28%22%5Cn%22%2C%24data%29%3B%0A//%20parcours%20des%20donnees%20ligne%20par%20ligne%20%28la%20ligne%20est%20passee%20par%20reference%20%3D%3E%20elle%20est%20directement%20modifiable%29%0Aforeach%28%24data%20as%20%26%24line%29%0A%7B%0A%20%20%20%20//%20on%20recupere%20l%27identifiant%20de%20la%20ligne%0A%20%20%20%20%24identifiant%20%3D%20substr%28%24line%2C0%2C2%29%3B%0A%0A%20%20%20%20switch%28%24identifiant%29%20%7B%0A%20%20%20%20%20%20%20%20//%20il%20s%27agit%20d%27une%20ligne%20P0%0A%20%20%20%20%20%20%20%20case%20%27P0%27%3A%0A%20%20%20%20%20%20%20%20%20%20%20%20//%20On%20remplace%20le%20poids%20avec%20padding%20des%20donnees%20%28str_pad%29%0A%20%20%20%20%20%20%20%20%20%20%20%20%24line%20%3D%20substr_replace%28%24line%2Cstr_pad%28%271001.00%27%2C15%2C%270%27%2CSTR_PAD_LEFT%29%2C29-1%2C15%29%3B%0A%20%20%20%20%20%20%20%20%20%20%20%20//%20On%20remplace%20le%20matricule%20chargeur%20%28position%20327%2C%20taille%206%29%0A%20%20%20%20%20%20%20%20%20%20%20%20%24line%20%3D%20substr_replace%28%24line%2C%27123456%27%2C327-1%2C6%29%3B%0A%20%20%20%20%20%20%20%20%20%20%20%20//%20On%20remplace%20le%20nom%20du%20destinataire%20%28position%2044%2C%20taille%2035%29%20avec%20padding%20des%20donnees%20%28str_pad%29%0A%20%20%20%20%20%20%20%20%20%20%20%20%24line%20%3D%20substr_replace%28%24line%2Cstr_pad%28%27TEST%27%2C35%2C%27%20%27%29%2C44-1%2C35%29%3B%0A%20%20%20%20%20%20%20%20%20%20%20%20//%20On%20remplace%20l%27adresse%201%20%28position%2079%2C%20taille%2035%29%20avec%20padding%20des%20donnees%20%28str_pad%29%0A%20%20%20%20%20%20%20%20%20%20%20%20%24line%20%3D%20substr_replace%28%24line%2Cstr_pad%28%27RUE%20DU%20TEST%27%2C35%2C%27%20%27%29%2C79-1%2C35%29%3B%0A%20%20%20%20%20%20%20%20%20%20%20%20//%20On%20remplace%20l%27adresse%202%20%28position%20114%2C%20taille%2035%29%20avec%20padding%20des%20donnees%20%28str_pad%29%0A%20%20%20%20%20%20%20%20%20%20%20%20%24line%20%3D%20substr_replace%28%24line%2Cstr_pad%28%27NE%20PAS%20INTEGRER%27%2C35%2C%27%20%27%29%2C114-1%2C35%29%3B%0A%20%20%20%20%20%20%20%20%20%20%20%20//%20Si%20le%20poids%20%28position%2029%2C%20taille%2015%29%20est%20superieur%20a%201000%20Kg%20on%20passe%20la%20prestation%20%28position%20385%2C%20taille%202%29%20a%20MORYPAL%0A%20%20%20%20%20%20%20%20%20%20%20%20%24poids%20%3D%20floatval%28substr%28%24line%2C29-1%2C15%29%29%3B%0A%20%20%20%20%20%20%20%20%20%20%20%20if%20%28%24poids%20%3E%3D%201000%29%20%7B%0A%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%20%24line%20%3D%20substr_replace%28%24line%2C%2722%27%2C385-1%2C2%29%3B%0A%20%20%20%20%20%20%20%20%20%20%20%20%7D%0A%20%20%20%20%20%20%20%20break%3B%0A%20%20%20%20%20%20%20%20//%20il%20s%27agit%20d%27un%20autre%20type%20d%27enregistrement%0A%20%20%20%20%20%20%20%20default%3A%0A%20%20%20%20%20%20%20%20break%3B%0A%20%20%20%20%7D%0A%7D%0A//%20on%20reconstitue%20les%20donnees%20modifiees%20et%20on%20les%20retourne%0Areturn%20implode%28%22%5Cn%22%2C%24data%29%3B%0A%3F%3E'), 'php');return false;"><?php echo _("load sample script"); ?></a> / <a href="#form" onclick="editor.setValue('');return false;"><?php echo _("clear script"); ?></a>) :
<a name="customscriptform"></a>
<textarea name="customscript">
<?php
    echo "<?php \n";
    if (isset($_POST['customscript'])) {
        echo preg_replace('/^<\?php[\r\n\ ]*(.*)[\r\n\ ]*\?>$/s', '$1', $_POST["customscript"]);
    }
    echo "\n?>";
?>
</textarea>
<div id="customscript" name="customscript" class="">
</div>
<?php echo _("Script output"); ?> :
<textarea id="modified_data" name="modified_data">
<?php
        if (isset($modified_data) && $modified_data!='') {
            echo $modified_data;
        }
?>
</textarea>
<input type="checkbox" <?php if ($displayCustomScriptOutput) echo 'checked="checked"'; ?> name="displayCustomScriptOutput" value="1" /> <?php echo _("<b>analyse</b> script output (instead of source data)"); ?><br />
</div>
<input type="submit" name="send_data2" value="<?php echo _("Analyse"); ?>" />
<input type="hidden" name="compute_data" value="1" />
</form>
<?php
    } catch(Exception $e) {
            echo '<div class="errorbox"><?php echo _("Exception raised"); ?> : ',  $e->getMessage(), "\n", '</div>';
    }
?>
<form name="get_as_file" onsubmit="" action="get_as_file.php" method="post">
<input type="submit" name="get_as_file" value="<?php echo _("Get script output as file"); ?>" />
<input type="hidden" name="data" value="<?php if (isset($modified_data) && $modified_data!='') {echo htmlentities($modified_data);} ?>" />
<input type="hidden" name="filename" value="<?php if (isset($_POST['fileformat'])) { echo $_POST['fileformat']; } ?>" />
</form>
</div>
</div>
</div>
<script language="Javascript">
    var editor = ace.edit("customscript");
    var textarea = $('textarea[name="customscript"]').hide();

    editor.getSession().setValue(textarea.val());
    editor.getSession().on('change', function(){
      textarea.val(editor.getSession().getValue());
    });

    editor.setTheme("ace/theme/github");
    editor.getSession().setMode("ace/mode/php");
    editor.setValue(getSession().getValue());
</script>
</body>
</html>
