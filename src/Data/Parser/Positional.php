<?php

namespace Neuron\Data\Parser;

class Positional implements IParser
{
   /**
    * @param $Text
    * @param array $Locations name, start, length
    * @return array
    */

   public function parse( $Text, $Locations = array() )
   {
      $Results = array();

      foreach( $Locations as $Pos )
      {
         $Results[ $Pos[ 'name' ] ] = trim( substr( $Text, $Pos[ 'start' ], $Pos[ 'length' ] ) );
      }

      return $Results;
   }
}
