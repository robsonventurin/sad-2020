<?php
namespace dimensoes;

class Data{
    public $data;
    public $dia;
    public $mes;
    public $ano;
    public $semanaAno;
    public $bimestre;
    public $trimestre;
    public $semestre;

    public function setData($data){
        $this->data = $data;
        $this->dia = date('d', strtotime($data));
        $this->mes = date('m', strtotime($data));
        $this->ano = date('y', strtotime($data));
        $this->semanaAno = date('W', strtotime($data));
        if($this->mes > 0 && $this->mes <3){
            $this->bimestre=1;
        }else if($this->mes > 2 && $this->mes < 5){
            $this->bimestre=2;
        }else if($this->mes > 4 && $this->mes < 7){
            $this->bimestre=3;
        }else if($this->mes > 6 && $this->mes < 9){
            $this->bimestre=4;
        }else if($this->mes > 8 && $this->mes < 11){
            $this->bimestre=5;
        }else{
            $this->bimestre=6;
        }

        if($this->mes > 0 && $this->mes < 4){
            $this->trimestre=1;
        }else if($this->mes>3 && $this->mes < 7){
            $this->trimestre=2;
        }else if($this->mes>6 && $this->mes < 10){
            $this->trimestre=3;
        }else{
            $this->trimestre=4;
        }

        $this->semestre = (date('m', strtotime($data)) <7 ) ? 1: 2;
    }
}


?>