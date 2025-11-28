<?php

namespace Neuron\Data\Parsers;

class Positional implements IParser
{
   /**
    * @param $text
    * @param array $userData name, start, length
    * @return array
    */

   public function parse( $text, $userData = array() ) : array
   {
      $results = array();

      foreach( $userData as $pos )
      {
         $results[ $pos[ 'name' ] ] = trim( substr( $text, $pos[ 'start' ], $pos[ 'length' ] ) );
      }

      return $results;
   }
}
