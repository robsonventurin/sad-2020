<?php
namespace dimensoes;

class Sumario{
    public $quantidadeInclusoes;
    public $quantidadeAlteracoes;

    function __construct(){
        $this->quantidadeInclusoes = 0;
        $this->quantidadeAlteracoes = 0;
    }

    public function setQuantidadeInclusoes(){
        $this->quantidadeInclusoes ++;
    }
    public function setQuantidadeAlteracoes(){
        $this->quantidadeAlteracoes ++;
    }
}

?>