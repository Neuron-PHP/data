<?php

namespace Neuron\Data\Parser;

class Positional implements IParser
{
   /**
    * @param $Text
    * @param array $UserData name, start, length
    * @return array
    */

   public function parse( $Text, $UserData = array() ) : array
   {
      $Results = array();

      foreach( $UserData as $Pos )
      {
         $Results[ $Pos[ 'name' ] ] = trim( substr( $Text, $Pos[ 'start' ], $Pos[ 'length' ] ) );
      }

      return $Results;
   }
}
