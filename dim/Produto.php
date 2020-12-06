<?php
namespace dimensoes;

/**
 * Model da entidade cliente
 * @author Julio
 */
class Produto{
   
   public $codigo;
   public $nome;
   public $unidade_medida;

   public function setProduto($codigo, $nome, $unidade_medida){
      $this->codigo = $codigo;
      $this->nome = $nome;
      $this->unidade_medida = $unidade_medida;
   }
}
?>