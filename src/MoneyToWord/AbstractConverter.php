<?php 

namespace ThiagoLimaDev\MoneyToWords;

class AbstractConverter
{
    protected $lang;

    public function __construct($language)
    {
        try {
            $namespace  = 'ThiagoLimaDev\\MoneyToWord\\i18n\\'.$language;
            $this->lang = new $namespace;
        }
        catch (\Exception $e) {
            throw new \Exception('Language file not found!');
        }
    }

    public function convert($number)
    {
        if ($number == 0) {
            throw new \Exception('The value entered must be greater than zero.');
        }

        foreach ($this->lang->specialCharacter as $special) {
            $number = str_ireplace($special, '', $number);
        }

        if (substr_count($number, ',') > 1) {
            throw new \Exception('The number has more than one comma.');
        }

        $values = explode('.', $number);
        $reais  = $this->real($values);
        $cents  = $this->cents($values);
        $and    = '';

        if (count($values) >= 2) {
            $and = $this->lang->etc[$this->lang->lang]['&'];
        }

        return trim($reais." {$and} ".$cents);
    }

    private function real($values)
    {
        $reaisValue = (array_key_exists(0, $values)) ? $values[0] : 0;
        $reais      = $this->numberToString($values[0]);

        if ($reaisValue && $reaisValue == 1) {
            $reais .= ' '. $this->lang->realType[0];
        }
        else if ($reaisValue) {
            $reais .= ' '. $this->lang->realType[1];
        }

        return $reais;
    }

    private function cents($values)
    {
        $centValue = (array_key_exists(1, $values)) ? $values[1] : 0;
        $cents     = $this->numberToString($centValue);

        if ($centValue && $centValue == 1) {
            $cents .= ' '. $this->lang->centsType[0];
        }
        else if ($centValue) {
            $cents .= ' '. $this->lang->centsType[1];
        }

        return $cents;
    }

    private function numberToString($number, $decimalPoint = 0)
    {
        $reaisCentValues = explode(
            '.', 
            number_format(
                preg_replace("/[,]/", "", ($number) ?: 0), 
                ($decimalPoint) ?: $this->lang->decimalPoint, 
                ".", 
                ","
            )
        );

        $formated = (array_key_exists(0, $reaisCentValues)) ? $reaisCentValues[0] : 0;
        $point    = (array_key_exists(1, $reaisCentValues)) ? $reaisCentValues[1] : 0;

        $this->lang->numberP = $formated . (empty($point) ? "" : "." . $point);
        $this->lang->number  = $formated;

        $groups  = explode(',', $formated);
        $stepNum = count($groups) - 1;

        $parts = array();
        foreach ($groups as $step => $group) {
            $groupWords = $this->groupToWords($group);

            if ($groupWords) {
                $part = implode(' ' . $this->lang->etc[$this->lang->lang]['&'] . ' ', $groupWords);
    
                if (count($groups) >= 3 && $groups[0] == 1 ){
                    if (isset($this->lang->stepsSingular[$this->lang->lang][$stepNum - $step])) {
                        $part .= ' ' . $this->lang->stepsSingular[$this->lang->lang][$stepNum - $step];
                    }
                }
                else {
                    if (isset($this->lang->stepsPlural[$this->lang->lang][$stepNum - $step])) {
                        $part .= ' ' . $this->lang->stepsPlural[$this->lang->lang][$stepNum - $step];
                    }
                }
    
                $parts[] = $part;
            }
        }

        return ($this->lang->result = implode(' ' . $this->lang->etc[$this->lang->lang]['&'] . ' ', $parts));
    }

    private function groupToWords($group, $groupPoint = 0) 
    {
        $group = sprintf('%03d', $group);

        $d1 = (int) $group[2];
        $d2 = (int) $group[1];
        $d3 = (int) $group[0];

        $groupArray = array();
        if (!$groupPoint) {
            if ($d3 != 0) {
                if ($d3 == 1) {
                    if( $d1 == 0 && $d2 == 0 && $this->lang->lang == 'pt_BR') {
                        $groupArray[] = $this->lang->digitTH[$this->lang->lang][2];
                    }
                    else {
                        $groupArray[] = $this->lang->digitTH[$this->lang->lang][$d3];
                    }
                } 
                else {
                    $groupArray[] = $this->lang->digitTH[$this->lang->lang][$d3 + 1];
                }
            }

            if ($d2 == 1 && $d1 != 0) {
                $groupArray[] = $this->lang->digitTE[$this->lang->lang][$d1];
            }
            else if ($d2 != 0 && $d1 == 0) {
                $groupArray[] = $this->lang->digitTW[$this->lang->lang][$d2];
            }
            else if ($d2 == 0 && $d1 == 0) {

            }
            else if ($d2 == 0 && $d1 != 0) {
                $groupArray[] = $this->lang->digitON[$this->lang->lang][$d1];
            } 
            else {
                $groupArray[] = $this->lang->digitTW[$this->lang->lang][$d2];
                $groupArray[] = $this->lang->digitON[$this->lang->lang][$d1];
            }
        } 
        elseif ($groupPoint) {
            if ($d3 != 0) {
                $groupArray[] = $this->lang->digitTH[$this->lang->lang][$d3];
            }

            if ($d2 == 1 && $d1 != 0) {
                $groupArray[] = $this->lang->digitTE[$this->lang->lang][$d1];
            }
            else if ($d2 != 0 && $d1 == 0) {
                $groupArray[] = $this->lang->digitTW[$this->lang->lang][$d2];
            }
            else if ($d2 == 0 && $d1 == 0) {
                
            }
            else if ($d2 == 0 && $d1 != 0) {
                $groupArray[] = $this->lang->digitON[$this->lang->lang][$d1];
            }
            else {
                $groupArray[] = $this->lang->digitTW[$this->lang->lang][$d2];
                $groupArray[] = $this->lang->digitON[$this->lang->lang][$d1];
            }
        }

        if (!count($groupArray)){
            return false;
        }

        return $groupArray;
    }
}